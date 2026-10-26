<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use InvalidArgumentException;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Schema\Entities\Buildings;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\DB\Exception;

class BuildingService {
	public function __construct(
		private BuildingMapper $buildingMapper,
		private FieldValidationService $fieldValidation,
		private Buildings $buildings,
		private IRoomManager $roomManager,
		private IResourceManager $resourceManager,
	) {
	}

	/**
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @param int|null $limit null for all
	 * @param int|null $offset null for none
	 * @return BuildingModel[]
	 */
	public function findAll(array $filters = [], ?int $limit = null, ?int $offset = null): array {
		return $this->buildingMapper->findAllFiltered(
			$this->buildings->criteria($filters),
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
		return $this->buildingMapper->countAllFiltered(
			$this->buildings->criteria($filters)
		);
	}

	/**
	 * id/name pairs for pickers and lookups.
	 *
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @return array<int, array{id: int, displayName: string}> ascending by name
	 * @throws Exception on a database error
	 */
	public function buildingOptions(array $filters = []): array {
		return array_map(static fn (BuildingModel $building): array => [
			'id' => (int)$building->getId(),
			'displayName' => $building->getDisplayName(),
		], $this->findAll($filters));
	}

	/**
	 * @throws InvalidArgumentException If a field is missing or malformed.
	 */
	public function create(string $displayName,
		?string $description = null,
		?string $address = null,
		bool $isWheelchairAccessible = false): BuildingModel {
		$this->assertValid($displayName);

		$building = new BuildingModel();
		$building->setDisplayName($displayName);
		$building->setDescription($description);
		$building->setAddress($address);
		$building->setIsWheelchairAccessible($isWheelchairAccessible);

		$building = $this->buildingMapper->insert($building);

		$this->refreshManagers();
		return $building;
	}

	/**
	 * @throws DoesNotExistException If there is no such building.
	 * @throws InvalidArgumentException If a field is missing or malformed.
	 */
	public function update(int $id,
		string $displayName,
		?string $description = null,
		?string $address = null,
		bool $isWheelchairAccessible = false): BuildingModel {
		$building = $this->buildingMapper->find($id);
		$this->assertValid($displayName);

		$building->setDisplayName($displayName);
		$building->setDescription($description);
		$building->setAddress($address);
		$building->setIsWheelchairAccessible($isWheelchairAccessible);

		$building = $this->buildingMapper->update($building);
		$this->refreshManagers();
		return $building;
	}

	/**
	 * @throws DoesNotExistException If there is no such building.
	 */
	public function delete(int $id): void {
		$building = $this->buildingMapper->find($id);
		$this->buildingMapper->delete($building);
		$this->refreshManagers();
	}

	/**
	 * @throws InvalidArgumentException If a field is missing or malformed.
	 */
	private function assertValid(string $displayName): void {
		$this->fieldValidation->assertRequired('Display name', $displayName);
	}

	/**
	 * Nudge Calendar to re-read what it caches from this app's backends.
	 */
	private function refreshManagers(): void {
		if (method_exists($this->roomManager, 'update')) {
			$this->roomManager->update();
		}
		if (method_exists($this->resourceManager, 'update')) {
			$this->resourceManager->update();
		}
	}
}
