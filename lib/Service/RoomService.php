<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;

class RoomService {
	/** @var RoomMapper */
	private $roomMapper;

	/** @var RestrictionMapper */
	private $restrictionMapper;

	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
		// ...
	];

	/**
	 * ResourceService constructor.
	 * @param RoomMapper $roomMapper
	 * @param RestrictionMapper $restrictionMapper
	 */
	public function __construct(RoomMapper $roomMapper,
		RestrictionMapper $restrictionMapper) {
		$this->roomMapper = $roomMapper;
		$this->restrictionMapper = $restrictionMapper;
	}
	/**
	 * Liste aller Räume
	 */
	public function listRooms(): array {
		return $this->roomMapper->findAll();
	}

	/**
	 * Raum anlegen
	 */
	public function createRoom(
		string $name,
		string $email = '',
		string $roomType = 'default',
		int $storyId = 1,
		string $roomNumber = '',
		string $contactPersonUserId = '',
		?int $capacity = null,
		bool $hasPhone = false,
		bool $hasVideo = false,
		bool $hasTv = false,
		bool $hasProjector = false,
		bool $hasWhiteboard = false,
		bool $wheelchairAccessible = false,
	): RoomModel {
		$room = new RoomModel();
		$room->setUid(bin2hex(random_bytes(16)));
		$room->setDisplayName($name);
		$room->setEmail($email);
		$room->setRoomType($roomType);
		$room->setStoryId($storyId);
		$room->setRoomNumber($roomNumber);
		$room->setContactPersonUserId($contactPersonUserId);
		if ($capacity !== null) {
			$room->setCapacity($capacity);
		}
		$room->setHasPhone($hasPhone);
		$room->setHasVideoConferencing($hasVideo);
		$room->setHasTv($hasTv);
		$room->setHasProjector($hasProjector);
		$room->setHasWhiteboard($hasWhiteboard);
		$room->setIsWheelchairAccessible($wheelchairAccessible);
		return $this->roomMapper->insert($room);
	}

	/**
	 * Raum löschen
	 */
	public function deleteRoom(int $id): void {
		$room = $this->roomMapper->find($id);
		$this->roomMapper->delete($room);
	}
}
