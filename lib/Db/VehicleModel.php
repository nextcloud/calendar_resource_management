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
namespace OCA\CalendarResourceManagement\Db;

/**
 * Class VehicleModel
 *
 * @package OCA\CalendarResourceManagement\Db
 *
 * @method string getVehicleType()
 * @method void setVehicleType(string $vehicleType)
 * @method string getVehicleMake()
 * @method void setVehicleMake(string $vehicleMake)
 * @method string getVehicleModel()
 * @method void setVehicleModel(string $vehicleModel)
 * @method bool getIsElectric()
 * @method void setIsElectric(bool $isElectric)
 * @method int getRange()
 * @method void setRange(int $range)
 * @method int getSeatingCapacity()
 * @method void setSeatingCapacity(int $seatingCapacity)
 */
class VehicleModel extends ResourceModel {
	/** @var string */
	protected $vehicleType;

	/** @var string */
	protected $vehicleMake;

	/** @var string */
	protected $vehicleModel;

	/** @var boolean */
	protected $isElectric;

	/** @var integer */
	protected $range;

	/** @var integer */
	protected $seatingCapacity;

	/**
	 * Vehicle constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->addType('vehicleType', 'string');
		$this->addType('vehicleMake', 'string');
		$this->addType('vehicleModel', 'string');
		$this->addType('isElectric', 'boolean');
		$this->addType('range', 'integer');
		$this->addType('seatingCapacity', 'integer');
	}
}
