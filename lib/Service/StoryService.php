<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Service;

use InvalidArgumentException;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCA\CalendarResourceManagement\Schema\Entities\Stories;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\DB\Exception;

class StoryService {
	public function __construct(
		private StoryMapper $storyMapper,
		private FieldValidationService $fieldValidation,
		private Stories $stories,
		private IRoomManager $roomManager,
	) {
	}

	/**
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @param int|null $limit null for all
	 * @param int|null $offset null for none
	 * @return StoryModel[]
	 */
	public function findAll(array $filters = [], ?int $limit = null, ?int $offset = null): array {
		return $this->storyMapper->findAllFiltered(
			$this->stories->criteria($filters),
			limit: $limit,
			offset: $offset,
		);
	}

	/**
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @return int the number of matching rows
	 * @throws Exception on a database error
	 */
	public function countAll(array $filters = []): int {
		return $this->storyMapper->countAllFiltered(
			$this->stories->criteria($filters)
		);
	}

	/**
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @return array<int, array{id: int, displayName: string, buildingId: int}> ascending by name
	 * @throws Exception on a database error
	 */
	public function storyOptions(array $filters = []): array {
		return array_map(static fn (StoryModel $story): array => [
			'id' => (int)$story->getId(),
			'displayName' => $story->getDisplayName(),
			'buildingId' => $story->getBuildingId(),
		], $this->findAll($filters));
	}

	/**
	 * @throws InvalidArgumentException If input is missing or references a missing building.
	 */
	public function create(int $buildingId, string $displayName): StoryModel {
		$this->assertValid($buildingId, $displayName);

		$story = new StoryModel();
		$story->setBuildingId($buildingId);
		$story->setDisplayName($displayName);

		$story = $this->storyMapper->insert($story);
		// A story only ever holds rooms.
		$this->refreshManagers();
		return $story;
	}

	/**
	 * @throws DoesNotExistException If there is no such story.
	 * @throws InvalidArgumentException If input is missing or references a missing building.
	 */
	public function update(int $id, int $buildingId, string $displayName): StoryModel {
		$story = $this->storyMapper->find($id);
		$this->assertValid($buildingId, $displayName);

		$story->setBuildingId($buildingId);
		$story->setDisplayName($displayName);

		$story = $this->storyMapper->update($story);
		$this->refreshManagers();
		return $story;
	}

	/**
	 * @throws DoesNotExistException If there is no such story.
	 */
	public function delete(int $id): void {
		$story = $this->storyMapper->find($id);
		$this->storyMapper->delete($story);
		$this->refreshManagers();
	}

	/**
	 * @throws InvalidArgumentException If input is missing or references a missing building.
	 */
	private function assertValid(int $buildingId, string $displayName): void {
		$this->fieldValidation->assertRequired('Display name', $displayName);
		$this->fieldValidation->assertBuildingExists($buildingId);
	}

	/**
	 * Nudge Calendar to re-read what it caches from this app's backends.
	 */
	private function refreshManagers(): void {
		if (method_exists($this->roomManager, 'update')) {
			$this->roomManager->update();
		}
	}
}
