<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Controller;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Http\PaginationParams;
use OCA\CalendarResourceManagement\Service\RestrictionService;
use OCA\CalendarResourceManagement\Service\RoomService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\DB\Exception;
use OCP\IRequest;

class RoomController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private RoomService $service,
		private RestrictionService $restrictions,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * One page of rooms as `{items, total, page, limit, pages}`.
	 *
	 * Page size is fixed at {@see PaginationParams::DEFAULT_LIMIT}.
	 *
	 * @param int|null $page 1-based, clamped into range
	 * @return DataResponse the pagination envelope
	 */
	public function index(?int $id = null,
		?int $buildingId = null,
		?int $storyId = null,
		?string $uid = null,
		?string $displayName = null,
		?string $email = null,
		?string $roomType = null,
		?string $contactPersonUserId = null,
		?int $capacityMin = null,
		?int $capacityMax = null,
		?string $roomNumber = null,
		?bool $hasPhone = null,
		?bool $hasVideoConferencing = null,
		?bool $hasTv = null,
		?bool $hasProjector = null,
		?bool $hasWhiteboard = null,
		?bool $isWheelchairAccessible = null,
		?bool $restricted = null,
		?int $page = null): DataResponse {
		$filters = [
			'id' => $id,
			'buildingId' => $buildingId,
			'storyId' => $storyId,
			'uid' => $uid,
			'displayName' => $displayName,
			'email' => $email,
			'roomType' => $roomType,
			'contactPersonUserId' => $contactPersonUserId,
			'capacityMin' => $capacityMin,
			'capacityMax' => $capacityMax,
			'roomNumber' => $roomNumber,
			'hasPhone' => $hasPhone,
			'hasVideoConferencing' => $hasVideoConferencing,
			'hasTv' => $hasTv,
			'hasProjector' => $hasProjector,
			'hasWhiteboard' => $hasWhiteboard,
			'isWheelchairAccessible' => $isWheelchairAccessible,
			'restricted' => $restricted,
		];

		$pagination = new PaginationParams($page, $this->service->countAll($filters));
		$items = $this->service->findAll($filters, PaginationParams::DEFAULT_LIMIT, $pagination->offset());
		$groups = [];
		foreach ($this->restrictions->findForEntities(Constants::ROOM, $items) as $restriction) {
			$groups[$restriction->getEntityId()][] = $restriction->getGroupId();
		}

		return new DataResponse([
			'items' => array_map(
				fn (RoomModel $room) => $this->addGroupRestrictions($room, $groups[$room->getId()] ?? []),
				$items,
			),
			'total' => $pagination->total,
			'page' => $pagination->page,
			'limit' => PaginationParams::DEFAULT_LIMIT,
			'pages' => $pagination->pages,
		]);
	}

	/**
	 * Every room type in use across the whole table, as a bare list of strings.
	 *
	 * @return DataResponse the distinct types, ascending; `[]` when there are none
	 */
	public function roomTypes(): DataResponse {
		return new DataResponse($this->service->roomTypes());
	}

	public function create(int $storyId,
		string $displayName,
		string $roomType,
		?string $email = null,
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
		?array $groupRestrictions = null): DataResponse {
		try {
			$room = $this->service->create(
				$storyId,
				$displayName,
				$email,
				$roomType,
				$contactPersonUserId,
				$capacity,
				$roomNumber,
				$hasPhone,
				$hasVideoConferencing,
				$hasTv,
				$hasProjector,
				$hasWhiteboard,
				$isWheelchairAccessible,
				$restricted,
				$groupRestrictions ?? [],
			);

			return new DataResponse($this->addGroupRestrictions($room, $this->fetchGroupIds($room)));
		} catch (\InvalidArgumentException $e) {
			throw new OCSBadRequestException($e->getMessage());
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new OCSBadRequestException('An entity with this UID or email already exists');
			}
			throw $e;
		}
	}

	public function update(int $id,
		int $storyId,
		string $displayName,
		string $roomType,
		?string $email = null,
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
		?array $groupRestrictions = null): DataResponse {
		try {
			$room = $this->service->update(
				$id,
				$storyId,
				$displayName,
				$email,
				$roomType,
				$contactPersonUserId,
				$capacity,
				$roomNumber,
				$hasPhone,
				$hasVideoConferencing,
				$hasTv,
				$hasProjector,
				$hasWhiteboard,
				$isWheelchairAccessible,
				$restricted,
				$groupRestrictions,
			);

			return new DataResponse($this->addGroupRestrictions($room, $this->fetchGroupIds($room)));
		} catch (\InvalidArgumentException $e) {
			throw new OCSBadRequestException($e->getMessage());
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new OCSBadRequestException('An entity with this UID or email already exists');
			}
			throw $e;
		}
	}

	public function destroy(int $id): DataResponse {
		try {
			$this->service->delete($id);
			return new DataResponse(null);
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException();
		}
	}

	/**
	 * The room as the API returns it: its JSON plus its group ids.
	 *
	 * @param string[] $groupIds the room's group ids
	 * @return array<string, mixed>
	 */
	private function addGroupRestrictions(RoomModel $room, array $groupIds): array {
		return $room->jsonSerialize() + ['groupRestrictions' => $groupIds];
	}

	/**
	 * Load the group ids of one room, for the create and update responses.
	 *
	 * @return string[]
	 */
	private function fetchGroupIds(RoomModel $room): array {
		return array_map(
			static fn (RestrictionModel $restriction) => $restriction->getGroupId(),
			$this->restrictions->findFor(Constants::ROOM, $room->getId()),
		);
	}
}
