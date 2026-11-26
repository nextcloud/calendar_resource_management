<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\Security\ISecureRandom;

class VehicleService {

	public function __construct(
		private VehicleMapper $vehicleMapper,
		private RestrictionMapper $restrictionMapper,
		private BuildingMapper $buildingMapper,
		private ISecureRandom $secureRandom,
	) {
	}

	/**
	 * List all vehicles
	 */
	public function listVehicles(): array {
		return $this->vehicleMapper->findAll();
	}

	/**
	 * Create a vehicle
	 *
	 * @throws DoesNotExistException If the building does not exist.
	 * @throws EmailAlreadyUsedException If another vehicle already uses the email address.
	 */
	public function createVehicle(string $name, int $buildingId, string $email = '', string $vehicleType = 'car', string $vehicleMake = '', string $vehicleModel = '', string $contactPersonUserId = '', bool $isElectric = false, ?int $range = null, ?int $seatingCapacity = null): VehicleModel {
		// A vehicle without an existing building cannot be resolved by the calendar backend
		$this->buildingMapper->find($buildingId);

		try {
			$this->vehicleMapper->findByEmail($email);
			throw new EmailAlreadyUsedException('A vehicle with this email address already exists');
		} catch (DoesNotExistException) {
			// The email address is still free
		}

		$vehicle = new VehicleModel();
		$vehicle->setUid($this->secureRandom->generate(32, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS));
		$vehicle->setDisplayName($name);
		$vehicle->setEmail($email);
		$vehicle->setResourceType('vehicle');
		$vehicle->setVehicleType($vehicleType);
		$vehicle->setVehicleMake($vehicleMake);
		$vehicle->setVehicleModel($vehicleModel);
		$vehicle->setContactPersonUserId($contactPersonUserId);
		$vehicle->setIsElectric($isElectric);
		$vehicle->setRange($range);
		$vehicle->setSeatingCapacity($seatingCapacity);
		$vehicle->setBuildingId($buildingId);
		return $this->vehicleMapper->insert($vehicle);
	}

	/**
	 * Update a vehicle
	 *
	 * @throws DoesNotExistException If the vehicle does not exist.
	 * @throws ServiceException If the building does not exist.
	 * @throws EmailAlreadyUsedException If another vehicle already uses the email address.
	 */
	public function updateVehicle(int $id, string $name, int $buildingId, string $email = '', string $vehicleType = 'car', string $vehicleMake = '', string $vehicleModel = '', string $contactPersonUserId = '', bool $isElectric = false, ?int $range = null, ?int $seatingCapacity = null): VehicleModel {
		$vehicle = $this->vehicleMapper->find($id);
		try {
			$this->buildingMapper->find($buildingId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('The selected building does not exist', previous: $e, httpCode: Http::STATUS_BAD_REQUEST);
		}

		try {
			$existing = $this->vehicleMapper->findByEmail($email);
			if ($existing->getId() !== $id) {
				throw new EmailAlreadyUsedException('A vehicle with this email address already exists');
			}
		} catch (DoesNotExistException) {
			// The email address is still free
		}

		$vehicle->setDisplayName($name);
		$vehicle->setEmail($email);
		$vehicle->setResourceType('vehicle');
		$vehicle->setVehicleType($vehicleType);
		$vehicle->setVehicleMake($vehicleMake);
		$vehicle->setVehicleModel($vehicleModel);
		$vehicle->setContactPersonUserId($contactPersonUserId);
		$vehicle->setIsElectric($isElectric);
		$vehicle->setRange($range);
		$vehicle->setSeatingCapacity($seatingCapacity);
		$vehicle->setBuildingId($buildingId);
		return $this->vehicleMapper->update($vehicle);
	}

	/**
	 * Delete a vehicle
	 *
	 * @throws DoesNotExistException If the vehicle does not exist.
	 */
	public function deleteVehicle(int $id): void {
		$vehicle = $this->vehicleMapper->find($id);
		$this->vehicleMapper->delete($vehicle);
		$this->restrictionMapper->deleteAllByEntityTypeAndId('vehicle', $id);
	}
}
