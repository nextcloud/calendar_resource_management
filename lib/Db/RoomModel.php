<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class RoomModel
 *
 * @package OCA\CalendarResourceManagement\Db
 *
 * @method string getUid()
 * @method void setUid(string $uid)
 * @method int getStoryId()
 * @method void setStoryId(int $storyId)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getRoomType()
 * @method void setRoomType(string $roomType)
 * @method string getContactPersonUserId()
 * @method void setContactPersonUserId(string $contactPersonUserId)
 * @method int getCapacity()
 * @method void setCapacity(int $capacity)
 * @method string getRoomNumber()
 * @method void setRoomNumber(string $roomNumber)
 * @method bool getHasPhone()
 * @method void setHasPhone(bool $hasPhone)
 * @method bool getHasVideoConferencing()
 * @method void setHasVideoConferencing(bool $hasVideoConferencing)
 * @method bool getHasTv()
 * @method void setHasTv(bool $hasTv)
 * @method bool getHasProjector()
 * @method void setHasProjector(bool $hasProjector)
 * @method bool getHasWhiteboard()
 * @method void setHasWhiteboard(bool $hasWhiteboard)
 * @method bool getIsWheelchairAccessible()
 * @method void setIsWheelchairAccessible(bool $isWheelchairAccessible)
 */
class RoomModel extends Entity {
	/** @var integer */
	protected $storyId;

	/** @var string */
	protected $uid;

	/** @var string */
	protected $displayName;

	/** @var string */
	protected $email;

	/** @var string */
	protected $roomType;

	/** @var string */
	protected $contactPersonUserId;

	/** @var integer */
	protected $capacity;

	/** @var string */
	protected $roomNumber;

	/** @var boolean */
	protected $hasPhone;

	/** @var boolean */
	protected $hasVideoConferencing;

	/** @var boolean */
	protected $hasTv;

	/** @var boolean */
	protected $hasProjector;

	/** @var boolean */
	protected $hasWhiteboard;

	/** @var boolean */
	protected $isWheelchairAccessible;

	/**
	 * Room constructor.
	 */
	public function __construct() {
		$this->addType('storyId', 'integer');
		$this->addType('uid', 'string');
		$this->addType('displayName', 'string');
		$this->addType('email', 'string');
		$this->addType('roomType', 'string');
		$this->addType('contactPersonUserId', 'string');
		$this->addType('capacity', 'integer');
		$this->addType('roomNumber', 'string');
		$this->addType('hasPhone', 'boolean');
		$this->addType('hasVideoConferencing', 'boolean');
		$this->addType('hasTv', 'boolean');
		$this->addType('hasProjector', 'boolean');
		$this->addType('hasWhiteboard', 'boolean');
		$this->addType('isWheelchairAccessible', 'boolean');
	}
}
