<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class BuildingService {
	/** @var BuildingMapper */
	private $buildingMapper;

	/** @var StoryMapper */
	private $storyMapper;

	/** @var int */
	private const LIMIT_STORIES_PER_BUILDING = 1000;

	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
	];

	/**
	 * BuildingService constructor.
	 * @param BuildingMapper $buildingMapper
	 * @param StoryMapper $storyMapper
	 */
	public function __construct(BuildingMapper $buildingMapper,
		StoryMapper $storyMapper) {
		$this->buildingMapper = $buildingMapper;
		$this->storyMapper = $storyMapper;
	}

	/**
	 * List all buildings
	 */
	public function listBuildings(): array {
		return $this->buildingMapper->findAll();
	}

	/**
	 * Create a building
	 */
	public function createBuilding(string $name, string $address = ''): BuildingModel {
		$building = new BuildingModel();
		$building->setDisplayName($name);
		$building->setAddress($address);
		return $this->buildingMapper->insert($building);
	}

	/**
	 * Delete a building
	 *
	 * @throws DoesNotExistException If the building does not exist.
	 */
	public function deleteBuilding(int $id): void {
		$building = $this->buildingMapper->find($id);
		$this->buildingMapper->delete($building);
	}
}
