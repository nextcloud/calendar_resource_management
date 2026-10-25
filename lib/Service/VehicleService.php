<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\ISecureRandom;

class VehicleService {
	public function __construct(
		private VehicleMapper $vehicleMapper,
		private RestrictionMapper $restrictionMapper,
		private BuildingMapper $buildingMapper,
		private DefaultShareMapper $defaultShareMapper,
		private ISecureRandom $secureRandom,
	) {
	}

	/**
	 * List all vehicles
	 *
	 * @return VehicleModel[]
	 */
	public function listVehicles(): array {
		return $this->vehicleMapper->findAll();
	}

	/**
	 * @return array<int, string[]> The restriction groups per vehicle id
	 */
	public function listRestrictionGroupIds(): array {
		return $this->restrictionMapper->findGroupIdsByEntityType(Constants::VEHICLE);
	}

	/**
	 * Create a vehicle
	 *
	 * @throws DoesNotExistException If the building does not exist.
	 * @throws EmailAlreadyUsedException If another vehicle already uses the email address.
	 */
	public function createVehicle(
		string $name,
		int $buildingId,
		string $email,
		string $vehicleType = '',
		string $vehicleMake = '',
		string $vehicleModel = '',
		bool $isElectric = false,
		?int $range = null,
		?int $seatingCapacity = null,
		string $contactPersonUserId = '',
	): VehicleModel {
		// A vehicle without an existing building cannot be resolved by the calendar backend
		$this->buildingMapper->find($buildingId);
		$this->assertEmailIsFree($email, null);

		$vehicle = new VehicleModel();
		$vehicle->setUid($this->secureRandom->generate(32, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS));
		// The column default, set so the returned vehicle carries it too
		$vehicle->setResourceType('vehicle');
		$this->apply($vehicle, $name, $buildingId, $email, $vehicleType, $vehicleMake, $vehicleModel, $isElectric, $range, $seatingCapacity, $contactPersonUserId);
		return $this->vehicleMapper->insert($vehicle);
	}

	/**
	 * Change a vehicle and its restrictions
	 *
	 * @param string[] $restrictionGroupIds Without any, a restricted vehicle is hidden from everybody
	 * @throws DoesNotExistException If the vehicle or the building does not exist.
	 * @throws EmailAlreadyUsedException If another vehicle already uses the email address.
	 */
	public function updateVehicle(
		int $id,
		string $name,
		int $buildingId,
		string $email,
		string $vehicleType,
		string $vehicleMake,
		string $vehicleModel,
		bool $isElectric,
		?int $range,
		?int $seatingCapacity,
		string $contactPersonUserId,
		bool $restricted,
		array $restrictionGroupIds,
	): VehicleModel {
		$vehicle = $this->vehicleMapper->find($id);
		$this->buildingMapper->find($buildingId);
		$this->assertEmailIsFree($email, $id);

		$this->apply($vehicle, $name, $buildingId, $email, $vehicleType, $vehicleMake, $vehicleModel, $isElectric, $range, $seatingCapacity, $contactPersonUserId);
		$vehicle->setRestricted($restricted);
		$vehicle = $this->vehicleMapper->update($vehicle);
		$this->restrictionMapper->setGroupIds(Constants::VEHICLE, $id, $restricted ? $restrictionGroupIds : []);

		return $vehicle;
	}

	/**
	 * Delete a vehicle
	 *
	 * @throws DoesNotExistException If the vehicle does not exist.
	 */
	public function deleteVehicle(int $id): void {
		$vehicle = $this->vehicleMapper->find($id);
		$this->vehicleMapper->delete($vehicle);
		$this->restrictionMapper->deleteAllByEntityTypeAndId(Constants::VEHICLE, $id);
		$this->defaultShareMapper->deleteAllByEntityTypeAndId(Constants::VEHICLE, $id);
	}

	/**
	 * @throws EmailAlreadyUsedException
	 */
	private function assertEmailIsFree(string $email, ?int $ownId): void {
		try {
			if ($this->vehicleMapper->findByEmail($email)->getId() !== $ownId) {
				throw new EmailAlreadyUsedException('A vehicle with this email address already exists');
			}
		} catch (DoesNotExistException) {
			// The email address is still free
		}
	}

	private function apply(
		VehicleModel $vehicle,
		string $name,
		int $buildingId,
		string $email,
		string $vehicleType,
		string $vehicleMake,
		string $vehicleModel,
		bool $isElectric,
		?int $range,
		?int $seatingCapacity,
		string $contactPersonUserId,
	): void {
		$vehicle->setDisplayName($name);
		$vehicle->setBuildingId($buildingId);
		$vehicle->setEmail($email);
		$vehicle->setVehicleType($vehicleType);
		$vehicle->setVehicleMake($vehicleMake);
		$vehicle->setVehicleModel($vehicleModel);
		$vehicle->setIsElectric($isElectric);
		$vehicle->setRange($range);
		$vehicle->setSeatingCapacity($seatingCapacity);
		$vehicle->setContactPersonUserId($contactPersonUserId);
	}
}
