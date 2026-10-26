<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use InvalidArgumentException;
use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCA\CalendarResourceManagement\Schema\Entities\Vehicles;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\TTransactional;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\DB\Exception;
use OCP\IDBConnection;

class VehicleService {
	use TTransactional;

	public function __construct(
		private VehicleMapper $vehicleMapper,
		private UidGenerationService $uidGeneration,
		private EmailGenerationService $emailGeneration,
		private FieldValidationService $fieldValidation,
		private Vehicles $vehicles,
		private IResourceManager $resourceManager,
		private RestrictionService $restrictions,
		private IDBConnection $db,
	) {
	}

	/**
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @param int|null $limit null for all
	 * @param int|null $offset null for none
	 * @return VehicleModel[]
	 */
	public function findAll(array $filters = [], ?int $limit = null, ?int $offset = null): array {
		return $this->vehicleMapper->findAllFiltered(
			$this->vehicles->criteria($filters),
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
		return $this->vehicleMapper->countAllFiltered(
			$this->vehicles->criteria($filters)
		);
	}

	/**
	 * Distinct vehicle types in use across the whole table, ascending.
	 *
	 * @return string[] the distinct types, non-empty and re-indexed
	 * @throws Exception on a database error
	 */
	public function vehicleTypes(): array {
		$field = $this->vehicles->field('vehicleType');
		if ($field->column === null) {
			return [];
		}

		return $this->vehicleMapper->distinctValues($field->column);
	}

	/**
	 * @param string|null $email
	 * @param string[] $groupRestrictions only allowed when restricted
	 * @throws InvalidArgumentException If input is invalid 
	 */
	public function create(int $buildingId,
		string $displayName,
		?string $email,
		string $vehicleType,
		string $vehicleMake,
		string $vehicleModel,
		?string $contactPersonUserId = null,
		?bool $isElectric = null,
		?int $range = null,
		?int $seatingCapacity = null,
		bool $restricted = false,
		array $groupRestrictions = []): VehicleModel {
		$uid = $this->uidGeneration->generateUid();
		$email = $this->emailGeneration->resolveEmail($email, $displayName, Constants::VEHICLE);
		$groupRestrictions = $this->restrictions->normalize($groupRestrictions);
		$this->assertValid($buildingId, $displayName, $email, $contactPersonUserId, $range, $seatingCapacity, $restricted, $groupRestrictions);

		$vehicle = new VehicleModel();
		$vehicle->setUid($uid);
		$vehicle->setBuildingId($buildingId);
		$vehicle->setDisplayName($displayName);
		$vehicle->setEmail($email);
		$vehicle->setResourceType(Constants::VEHICLE);
		$vehicle->setVehicleType($vehicleType);
		$vehicle->setVehicleMake($vehicleMake);
		$vehicle->setVehicleModel($vehicleModel);
		$vehicle->setContactPersonUserId($contactPersonUserId);
		$vehicle->setIsElectric($isElectric);
		$vehicle->setRange($range);
		$vehicle->setSeatingCapacity($seatingCapacity);
		$vehicle->setRestricted($restricted);

		$vehicle = $this->atomic(function () use ($vehicle, $groupRestrictions) {
			$vehicle = $this->vehicleMapper->insert($vehicle);
			$this->restrictions->create(Constants::VEHICLE, $vehicle->getId(), $groupRestrictions);
			return $vehicle;
		}, $this->db);
		$this->refreshManagers();
		return $vehicle;
	}

	/**
	 * @param string|null $email 
	 * @param string[]|null $groupRestrictions null to keep current
	 * @throws DoesNotExistException If there is no such vehicle.
	 * @throws InvalidArgumentException If input is invalid
	 */
	public function update(int $id,
		int $buildingId,
		string $displayName,
		?string $email,
		string $vehicleType,
		string $vehicleMake,
		string $vehicleModel,
		?string $contactPersonUserId = null,
		?bool $isElectric = null,
		?int $range = null,
		?int $seatingCapacity = null,
		?bool $restricted = null,
		?array $groupRestrictions = null): VehicleModel {
		$vehicle = $this->vehicleMapper->find($id);
		$email = $this->emailGeneration->resolveEmail($email, $displayName, Constants::VEHICLE);
		$restricted ??= $vehicle->isRestricted();
		if ($groupRestrictions !== null) {
			$groupRestrictions = $this->restrictions->normalize($groupRestrictions);
		}
		$this->assertValid($buildingId, $displayName, $email, $contactPersonUserId, $range, $seatingCapacity, $restricted, $groupRestrictions ?? []);
		if (!$restricted) {
			$groupRestrictions = [];
		}

		$vehicle->setBuildingId($buildingId);
		$vehicle->setDisplayName($displayName);
		$vehicle->setEmail($email);
		$vehicle->setResourceType(Constants::VEHICLE);
		$vehicle->setVehicleType($vehicleType);
		$vehicle->setVehicleMake($vehicleMake);
		$vehicle->setVehicleModel($vehicleModel);
		$vehicle->setContactPersonUserId($contactPersonUserId);
		$vehicle->setIsElectric($isElectric);
		$vehicle->setRange($range);
		$vehicle->setSeatingCapacity($seatingCapacity);
		$vehicle->setRestricted($restricted);

		$vehicle = $this->atomic(function () use ($vehicle, $groupRestrictions) {
			$vehicle = $this->vehicleMapper->update($vehicle);
			if ($groupRestrictions !== null) {
				$this->restrictions->replace(Constants::VEHICLE, $vehicle->getId(), $groupRestrictions);
			}
			return $vehicle;
		}, $this->db);
		$this->refreshManagers();
		return $vehicle;
	}

	/**
	 * @throws DoesNotExistException If there is no such vehicle.
	 */
	public function delete(int $id): void {
		$vehicle = $this->vehicleMapper->find($id);
		$this->atomic(function () use ($vehicle) {
			$this->vehicleMapper->delete($vehicle);
			$this->restrictions->deleteFor(Constants::VEHICLE, $vehicle->getId());
		}, $this->db);
		$this->refreshManagers();
	}

	/**
	 * @param string[] $groupRestrictions the normalised groups to be saved
	 * @throws InvalidArgumentException If input is invalid 
	 */
	private function assertValid(int $buildingId,
		string $displayName,
		string $email,
		?string $contactPersonUserId,
		?int $range,
		?int $seatingCapacity,
		bool $restricted,
		array $groupRestrictions): void {
		$this->fieldValidation->assertRequired('Display name', $displayName);
		$this->fieldValidation->assertRequired('Email', $email);
		$this->fieldValidation->assertEmail('Email', $email);
		$this->fieldValidation->assertUserExists('Contact person', $contactPersonUserId);
		$this->fieldValidation->assertInRange($this->vehicles->field('range'), $range);
		$this->fieldValidation->assertInRange($this->vehicles->field('seatingCapacity'), $seatingCapacity);
		$this->fieldValidation->assertBuildingExists($buildingId);
		$this->fieldValidation->assertGroupRestrictions($restricted, $groupRestrictions);
	}

	/**
	 * Nudge Calendar to re-read what it caches from this app's backends.
	 */
	private function refreshManagers(): void {
		if (method_exists($this->resourceManager, 'update')) {
			$this->resourceManager->update();
		}
	}
}
