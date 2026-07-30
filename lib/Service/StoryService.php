<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCP\AppFramework\Db\DoesNotExistException;

class StoryService {
	public function __construct(
		private StoryMapper $storyMapper,
		private BuildingMapper $buildingMapper,
	) {
	}

	/**
	 * List all stories
	 */
	public function listStories(): array {
		return $this->storyMapper->findAll();
	}

	/**
	 * Create a story
	 *
	 * @throws DoesNotExistException If the building does not exist.
	 */
	public function createStory(string $name, int $buildingId): StoryModel {
		// A story without an existing building cannot be resolved by the calendar backend
		$this->buildingMapper->find($buildingId);

		$story = new StoryModel();
		$story->setDisplayName($name);
		$story->setBuildingId($buildingId);
		return $this->storyMapper->insert($story);
	}

	/**
	 * Delete a story
	 *
	 * @throws DoesNotExistException If the story does not exist.
	 */
	public function deleteStory(int $id): void {
		$story = $this->storyMapper->find($id);
		$this->storyMapper->delete($story);
	}
}
