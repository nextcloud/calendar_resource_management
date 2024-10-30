<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
