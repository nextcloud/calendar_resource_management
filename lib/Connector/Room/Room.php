<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Connector\Room;

use OCA\CalendarResourceManagement\Db;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCP\Calendar\IMetadataProvider;
use OCP\Calendar\Room\IBackend;
use OCP\Calendar\Room\IRoom;
use OCP\Calendar\Room\IRoomMetadata;

/**
 * Class Resource
 *
 * @package OCA\CalendarResourceManagement\Connector\Room
 */
class Room implements IRoom, IMetadataProvider {
	/**
	 * Room constructor.
	 *
	 * @param Db\RoomModel $entity
	 * @param Db\StoryModel $storyEntity
	 * @param Db\BuildingModel $buildingEntity
	 * @param array $restrictions
	 * @param Backend $backend
	 */
	public function __construct(
		protected RoomModel $entity,
		protected StoryModel $storyEntity,
		protected BuildingModel $buildingEntity,
		private array $restrictions,
		private Backend $backend,
	) {
	}

	/**
	 * @return IBackend
	 */
	public function getBackend(): IBackend {
		return $this->backend;
	}

	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->entity->getDisplayName();
	}

	/**
	 * @return string
	 */
	public function getEMail(): string {
		return $this->entity->getEmail();
	}

	/**
	 * @return array
	 */
	public function getGroupRestrictions(): array {
		return $this->restrictions;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->entity->getUid();
	}

	/**
	 * @return array
	 */
	public function getAllAvailableMetadataKeys(): array {
		$keys = [];

		if ($this->entity->getRoomType()) {
			$keys[] = IRoomMetadata::ROOM_TYPE;
		}
		if ($this->entity->getCapacity()) {
			$keys[] = IRoomMetadata::CAPACITY;
		}
		if ($this->entity->getRoomNumber()) {
			$keys[] = IRoomMetadata::BUILDING_ROOM_NUMBER;
		}
		$keys[] = IRoomMetadata::BUILDING_NAME;
		$keys[] = IRoomMetadata::BUILDING_ADDRESS;
		$keys[] = IRoomMetadata::BUILDING_STORY;
		if ($this->getFeatures() !== '') {
			$keys[] = IRoomMetadata::FEATURES;
		}

		return $keys;
	}

	/**
	 * @param string $key
	 * @return string|null
	 */
	public function getMetadataForKey(string $key): ?string {
		switch ($key) {
			case IRoomMetadata::ROOM_TYPE:
				return $this->entity->getRoomType();
			case IRoomMetadata::CAPACITY:
				return (string)$this->entity->getCapacity();
			case IRoomMetadata::BUILDING_ROOM_NUMBER:
				return $this->entity->getRoomNumber();
			case IRoomMetadata::BUILDING_NAME:
				return $this->buildingEntity->getDisplayName();
			case IRoomMetadata::BUILDING_ADDRESS:
				return $this->buildingEntity->getAddress();
			case IRoomMetadata::BUILDING_STORY:
				return $this->storyEntity->getDisplayName();
			case IRoomMetadata::FEATURES:
				return $this->getFeatures();
			default:
				return null;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasMetadataForKey(string $key): bool {
		return \in_array($key, $this->getAllAvailableMetadataKeys(), true);
	}

	/**
	 * @return string
	 */
	private function getFeatures():string {
		$features = [];

		if ($this->entity->getHasPhone()) {
			$features[] = 'PHONE';
		}
		if ($this->entity->getHasVideoConferencing()) {
			$features[] = 'VIDEO-CONFERENCING';
		}
		if ($this->entity->getHasTv()) {
			$features[] = 'TV';
		}
		if ($this->entity->getHasProjector()) {
			$features[] = 'PROJECTOR';
		}
		if ($this->entity->getHasWhiteboard()) {
			$features[] = 'WHITEBOARD';
		}
		if ($this->entity->getIsWheelchairAccessible()) {
			$features[] = 'WHEELCHAIR-ACCESSIBLE';
		}

		return implode(',', $features);
	}
}
