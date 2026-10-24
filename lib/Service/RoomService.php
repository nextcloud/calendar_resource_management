<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use InvalidArgumentException;
use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Schema\Entities\Rooms;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\TTransactional;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\DB\Exception;
use OCP\IDBConnection;

class RoomService {
	use TTransactional;

	public function __construct(
		private RoomMapper $roomMapper,
		private UidGenerationService $uidGeneration,
		private EmailGenerationService $emailGeneration,
		private FieldValidationService $fieldValidation,
		private Rooms $rooms,
		private IRoomManager $roomManager,
		private RestrictionService $restrictions,
		private IDBConnection $db,
	) {
	}

	/**
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @param int|null $limit null for all
	 * @param int|null $offset null for none
	 * @return RoomModel[]
	 */
	public function findAll(array $filters = [], ?int $limit = null, ?int $offset = null): array {
		return $this->roomMapper->findAllFiltered(
			$this->rooms->criteria($filters),
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
		return $this->roomMapper->countAllFiltered(
			$this->rooms->criteria($filters)
		);
	}

	/**
	 * Distinct room types in use across the whole table, ascending.
	 *
	 * @return string[] the distinct types, non-empty and re-indexed
	 * @throws Exception on a database error
	 */
	public function roomTypes(): array {
		$field = $this->rooms->field('roomType');
		if ($field->column === null) {
			return [];
		}
		return $this->roomMapper->distinctValues($field->column);
	}

	/**
	 * @param string|null $email 
	 * @param string[] $groupRestrictions only allowed when restricted
	 * @throws InvalidArgumentException If input is invalid
	 */
	public function create(int $storyId,
		string $displayName,
		?string $email,
		string $roomType,
		?string $contactPersonUserId = null,
		?int $capacity = null,
		?string $roomNumber = null,
		bool $hasPhone = false,
		bool $hasVideoConferencing = false,
		bool $hasTv = false,
		bool $hasProjector = false,
		bool $hasWhiteboard = false,
		bool $isWheelchairAccessible = false,
		bool $restricted = false,
		array $groupRestrictions = []): RoomModel {
		$uid = $this->uidGeneration->generateUid();
		$email = $this->emailGeneration->resolveEmail($email, $displayName, Constants::ROOM);
		$groupRestrictions = $this->restrictions->normalize($groupRestrictions);
		$this->assertValid($storyId, $displayName, $email, $contactPersonUserId, $capacity, $restricted, $groupRestrictions);

		$room = new RoomModel();
		$room->setStoryId($storyId);
		$room->setUid($uid);
		$room->setDisplayName($displayName);
		$room->setEmail($email);
		$room->setRoomType($roomType);
		$room->setContactPersonUserId($contactPersonUserId);
		$room->setCapacity($capacity);
		$room->setRoomNumber($roomNumber);
		$room->setHasPhone($hasPhone);
		$room->setHasVideoConferencing($hasVideoConferencing);
		$room->setHasTv($hasTv);
		$room->setHasProjector($hasProjector);
		$room->setHasWhiteboard($hasWhiteboard);
		$room->setIsWheelchairAccessible($isWheelchairAccessible);
		$room->setRestricted($restricted);

		$room = $this->atomic(function () use ($room, $groupRestrictions) {
			$room = $this->roomMapper->insert($room);
			$this->restrictions->create(Constants::ROOM, $room->getId(), $groupRestrictions);
			return $room;
		}, $this->db);
		$this->refreshManagers();
		return $room;
	}

	/**
	 * @param string|null $email 
	 * @param string[]|null $groupRestrictions null to keep current
	 * @throws DoesNotExistException If there is no such room.
	 * @throws InvalidArgumentException If input is invalid 
	 */
	public function update(int $id,
		int $storyId,
		string $displayName,
		?string $email,
		string $roomType,
		?string $contactPersonUserId = null,
		?int $capacity = null,
		?string $roomNumber = null,
		bool $hasPhone = false,
		bool $hasVideoConferencing = false,
		bool $hasTv = false,
		bool $hasProjector = false,
		bool $hasWhiteboard = false,
		bool $isWheelchairAccessible = false,
		?bool $restricted = null,
		?array $groupRestrictions = null): RoomModel {

		$room = $this->roomMapper->find($id);
		$email = $this->emailGeneration->resolveEmail($email, $displayName, Constants::ROOM);
		$restricted ??= $room->isRestricted();
		if ($groupRestrictions !== null) {
			$groupRestrictions = $this->restrictions->normalize($groupRestrictions);
		}
		$this->assertValid($storyId, $displayName, $email, $contactPersonUserId, $capacity, $restricted, $groupRestrictions ?? []);
		if (!$restricted) {
			$groupRestrictions = [];
		}

		$room->setStoryId($storyId);
		$room->setDisplayName($displayName);
		$room->setEmail($email);
		$room->setRoomType($roomType);
		$room->setContactPersonUserId($contactPersonUserId);
		$room->setCapacity($capacity);
		$room->setRoomNumber($roomNumber);
		$room->setHasPhone($hasPhone);
		$room->setHasVideoConferencing($hasVideoConferencing);
		$room->setHasTv($hasTv);
		$room->setHasProjector($hasProjector);
		$room->setHasWhiteboard($hasWhiteboard);
		$room->setIsWheelchairAccessible($isWheelchairAccessible);
		$room->setRestricted($restricted);

		$room = $this->atomic(function () use ($room, $groupRestrictions) {
			$room = $this->roomMapper->update($room);
			if ($groupRestrictions !== null) {
				$this->restrictions->replace(Constants::ROOM, $room->getId(), $groupRestrictions);
			}
			return $room;
		}, $this->db);
		$this->refreshManagers();
		return $room;
	}

	/**
	 * @throws DoesNotExistException If there is no such room.
	 */
	public function delete(int $id): void {
		$room = $this->roomMapper->find($id);
		$this->atomic(function () use ($room) {
			$this->roomMapper->delete($room);
			$this->restrictions->deleteFor(Constants::ROOM, $room->getId());
		}, $this->db);
		$this->refreshManagers();
	}

	/**
	 * @param string[] $groupRestrictions the normalised groups to be saved
	 * @throws InvalidArgumentException If input is invalid 
	 */
	private function assertValid(int $storyId,
		string $displayName,
		string $email,
		?string $contactPersonUserId,
		?int $capacity,
		bool $restricted,
		array $groupRestrictions): void {
		$this->fieldValidation->assertRequired('Display name', $displayName);
		$this->fieldValidation->assertRequired('Email', $email);
		$this->fieldValidation->assertEmail('Email', $email);
		$this->fieldValidation->assertUserExists('Contact person', $contactPersonUserId);
		$this->fieldValidation->assertInRange($this->rooms->field('capacity'), $capacity);
		$this->fieldValidation->assertStoryExists($storyId);
		$this->fieldValidation->assertGroupRestrictions($restricted, $groupRestrictions);
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
