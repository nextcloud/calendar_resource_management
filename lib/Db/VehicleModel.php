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
 * @method int|null getRange()
 * @method void setRange(?int $range)
 * @method int|null getSeatingCapacity()
 * @method void setSeatingCapacity(?int $seatingCapacity)
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

	/**
	 * @return array{id: int, uid: ?string, name: ?string, email: ?string, resourceType: ?string, buildingId: ?int, contactPersonUserId: ?string, restricted: bool, vehicleType: ?string, vehicleMake: ?string, vehicleModel: ?string, isElectric: bool, range: ?int, seatingCapacity: ?int}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			...parent::jsonSerialize(),
			'vehicleType' => $this->getVehicleType(),
			'vehicleMake' => $this->getVehicleMake(),
			'vehicleModel' => $this->getVehicleModel(),
			'isElectric' => (bool)$this->getIsElectric(),
			// The commands store 0 when no value was given
			'range' => $this->getRange() ?: null,
			'seatingCapacity' => $this->getSeatingCapacity() ?: null,
		];
	}
}
