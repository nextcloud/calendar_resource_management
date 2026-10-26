<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Controller;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCA\CalendarResourceManagement\Http\PaginationParams;
use OCA\CalendarResourceManagement\Service\RestrictionService;
use OCA\CalendarResourceManagement\Service\VehicleService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\DB\Exception;
use OCP\IRequest;

class VehicleController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private VehicleService $service,
		private RestrictionService $restrictions,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * One page of vehicles as `{items, total, page, limit, pages}`.
	 *
	 * Page size is fixed at {@see PaginationParams::DEFAULT_LIMIT}.
	 *
	 * @param int|null $page 1-based, clamped into range
	 * @return DataResponse the pagination envelope
	 */
	public function index(?int $id = null,
		?int $buildingId = null,
		?string $uid = null,
		?string $name = null,
		?string $email = null,
		?string $vehicleType = null,
		?string $vehicleMake = null,
		?string $vehicleModel = null,
		?string $contactPersonUserId = null,
		?bool $isElectric = null,
		?int $rangeMin = null,
		?int $rangeMax = null,
		?int $seatingCapacityMin = null,
		?int $seatingCapacityMax = null,
		?bool $restricted = null,
		?int $page = null): DataResponse {
		$filters = [
			'id' => $id,
			'buildingId' => $buildingId,
			'uid' => $uid,
			'name' => $name,
			'email' => $email,
			'vehicleType' => $vehicleType,
			'vehicleMake' => $vehicleMake,
			'vehicleModel' => $vehicleModel,
			'contactPersonUserId' => $contactPersonUserId,
			'isElectric' => $isElectric,
			'rangeMin' => $rangeMin,
			'rangeMax' => $rangeMax,
			'seatingCapacityMin' => $seatingCapacityMin,
			'seatingCapacityMax' => $seatingCapacityMax,
			'restricted' => $restricted,
		];

		$pagination = new PaginationParams($page, $this->service->countAll($filters));
		$items = $this->service->findAll($filters, PaginationParams::DEFAULT_LIMIT, $pagination->offset());
		$groups = [];
		foreach ($this->restrictions->findForEntities(Constants::VEHICLE, $items) as $restriction) {
			$groups[$restriction->getEntityId()][] = $restriction->getGroupId();
		}

		return new DataResponse([
			'items' => array_map(
				fn (VehicleModel $vehicle) => $this->addGroupRestrictions($vehicle, $groups[$vehicle->getId()] ?? []),
				$items,
			),
			'total' => $pagination->total,
			'page' => $pagination->page,
			'limit' => PaginationParams::DEFAULT_LIMIT,
			'pages' => $pagination->pages,
		]);
	}

	/**
	 * Every vehicle type in use across the whole table, as a bare list of strings.
	 *
	 * @return DataResponse the distinct types, ascending; `[]` when there are none
	 */
	public function vehicleTypes(): DataResponse {
		return new DataResponse($this->service->vehicleTypes());
	}

	public function create(int $buildingId,
		string $name,
		string $vehicleType,
		string $vehicleMake,
		string $vehicleModel,
		?string $email = null,
		?string $contactPersonUserId = null,
		?bool $isElectric = null,
		?int $range = null,
		?int $seatingCapacity = null,
		bool $restricted = false,
		?array $groupRestrictions = null): DataResponse {
		try {
			$vehicle = $this->service->create(
				$buildingId,
				$name,
				$email,
				$vehicleType,
				$vehicleMake,
				$vehicleModel,
				$contactPersonUserId,
				$isElectric,
				$range,
				$seatingCapacity,
				$restricted,
				$groupRestrictions ?? [],
			);

			return new DataResponse($this->addGroupRestrictions($vehicle, $this->fetchGroupIds($vehicle)));
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
		int $buildingId,
		string $name,
		string $vehicleType,
		string $vehicleMake,
		string $vehicleModel,
		?string $email = null,
		?string $contactPersonUserId = null,
		?bool $isElectric = null,
		?int $range = null,
		?int $seatingCapacity = null,
		?bool $restricted = null,
		?array $groupRestrictions = null): DataResponse {
		try {
			$vehicle = $this->service->update(
				$id,
				$buildingId,
				$name,
				$email,
				$vehicleType,
				$vehicleMake,
				$vehicleModel,
				$contactPersonUserId,
				$isElectric,
				$range,
				$seatingCapacity,
				$restricted,
				$groupRestrictions,
			);

			return new DataResponse($this->addGroupRestrictions($vehicle, $this->fetchGroupIds($vehicle)));
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
	 * The vehicle as the API returns it: its JSON plus its group ids.
	 *
	 * @param string[] $groupIds the vehicle's group ids
	 * @return array<string, mixed>
	 */
	private function addGroupRestrictions(VehicleModel $vehicle, array $groupIds): array {
		return $vehicle->jsonSerialize() + ['groupRestrictions' => $groupIds];
	}

	/**
	 * Load the group ids of one vehicle, for the create and update responses.
	 *
	 * @return string[]
	 */
	private function fetchGroupIds(VehicleModel $vehicle): array {
		return array_map(
			static fn (RestrictionModel $restriction) => $restriction->getGroupId(),
			$this->restrictions->findFor(Constants::VEHICLE, $vehicle->getId()),
		);
	}
}
