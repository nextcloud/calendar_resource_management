<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Connector\Resource;

use OCP\Calendar\Resource\IResourceMetadata;

/**
 * Class Vehicle
 *
 * @package OCA\CalendarResourceManagement\Connector\Resource
 * @property \OCA\CalendarResourceManagement\Db\VehicleModel $entity
 */
class Vehicle extends ResourceObject {
	/**
	 * @return array
	 */
	public function getAllAvailableMetadataKeys(): array {
		$keys = parent::getAllAvailableMetadataKeys();

		if ($this->entity->getVehicleType()) {
			$keys[] = IResourceMetadata::VEHICLE_TYPE;
		}
		if ($this->entity->getVehicleMake()) {
			$keys[] = IResourceMetadata::VEHICLE_MAKE;
		}
		if ($this->entity->getVehicleModel()) {
			$keys[] = IResourceMetadata::VEHICLE_MODEL;
		}
		$keys[] = IResourceMetadata::VEHICLE_IS_ELECTRIC;
		if ($this->entity->getRange() !== null) {
			$keys[] = IResourceMetadata::VEHICLE_RANGE;
		}
		if ($this->entity->getSeatingCapacity() !== null) {
			$keys[] = IResourceMetadata::VEHICLE_SEATING_CAPACITY;
		}

		return $keys;
	}

	/**
	 * @param string $key
	 * @return string|null
	 */
	public function getMetadataForKey(string $key): ?string {
		switch ($key) {
			case IResourceMetadata::VEHICLE_TYPE:
				return $this->entity->getVehicleType();

			case IResourceMetadata::VEHICLE_MAKE:
				return $this->entity->getVehicleMake();

			case IResourceMetadata::VEHICLE_MODEL:
				return $this->entity->getVehicleModel();

			case IResourceMetadata::VEHICLE_IS_ELECTRIC:
				return $this->entity->getIsElectric()
					? '1'
					: '0';

			case IResourceMetadata::VEHICLE_RANGE:
				return (string)$this->entity->getRange();

			case IResourceMetadata::VEHICLE_SEATING_CAPACITY:
				return (string)$this->entity->getSeatingCapacity();

			default:
				return parent::getMetadataForKey($key);
		}
	}
}
