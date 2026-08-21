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
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\ISecureRandom;

class RoomService {
	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
		// ...
	];

	public function __construct(
		private RoomMapper $roomMapper,
		private RestrictionMapper $restrictionMapper,
		private StoryMapper $storyMapper,
		private ISecureRandom $secureRandom,
	) {
	}

	/**
	 * List all rooms
	 */
	public function listRooms(): array {
		return $this->roomMapper->findAll();
	}

	/**
	 * Create a room
	 *
	 * @throws DoesNotExistException If the story does not exist.
	 * @throws EmailAlreadyUsedException If another room already uses the email address.
	 */
	public function createRoom(
		string $name,
		int $storyId,
		string $email = '',
		string $roomType = 'default',
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
		// A room without an existing story cannot be resolved by the calendar backend
		$this->storyMapper->find($storyId);

		try {
			$this->roomMapper->findByEmail($email);
			throw new EmailAlreadyUsedException('A room with this email address already exists');
		} catch (DoesNotExistException) {
			// The email address is still free
		}

		$room = new RoomModel();
		$room->setUid($this->secureRandom->generate(32, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS));
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
	 * Delete a room
	 *
	 * @throws DoesNotExistException If the room does not exist.
	 */
	public function deleteRoom(int $id): void {
		$room = $this->roomMapper->find($id);
		$this->roomMapper->delete($room);
	}
}
