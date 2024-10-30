<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class BuildingModel
 *
 * @package OCA\CalendarResourceManagement\Db
 *
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getAddress()
 * @method void setAddress(string $address)
 * @method string getIsWheelchairAccessible()
 * @method void setIsWheelchairAccessible(bool $IsWheelchairAccessible)
 */
class BuildingModel extends Entity {
	/** @var string */
	public $displayName;

	/** @var string */
	protected $description;

	/** @var string */
	protected $address;

	/** @var boolean */
	protected $isWheelchairAccessible;

	/**
	 * Building constructor.
	 */
	public function __construct() {
		$this->addType('displayName', 'string');
		$this->addType('description', 'string');
		$this->addType('address', 'string');
		$this->addType('isWheelchairAccessible', 'boolean');
	}
}
