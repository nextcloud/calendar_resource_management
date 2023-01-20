<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <georg-nextcloud@ehrke.email>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
				return (string) $this->entity->getRange();

			case IResourceMetadata::VEHICLE_SEATING_CAPACITY:
				return (string) $this->entity->getSeatingCapacity();

			default:
				return parent::getMetadataForKey($key);
		}
	}
}
