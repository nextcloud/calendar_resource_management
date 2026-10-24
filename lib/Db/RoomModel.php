<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Db;

use JsonSerializable;
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
 * @method void setRestricted(bool $restricted)
 */
class RoomModel extends Entity implements JsonSerializable {
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

	/** @var boolean|null */
	protected $restricted;

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
		$this->addType('restricted', 'boolean');
	}

	public function isRestricted(): bool {
		return $this->restricted ?? false;
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'storyId' => $this->getStoryId(),
			'uid' => $this->getUid(),
			'displayName' => $this->getDisplayName(),
			'email' => $this->getEmail(),
			'roomType' => $this->getRoomType(),
			'contactPersonUserId' => $this->getContactPersonUserId(),
			'capacity' => $this->getCapacity(),
			'roomNumber' => $this->getRoomNumber(),
			'hasPhone' => $this->getHasPhone(),
			'hasVideoConferencing' => $this->getHasVideoConferencing(),
			'hasTv' => $this->getHasTv(),
			'hasProjector' => $this->getHasProjector(),
			'hasWhiteboard' => $this->getHasWhiteboard(),
			'isWheelchairAccessible' => $this->getIsWheelchairAccessible(),
			'restricted' => $this->isRestricted(),
		];
	}
}
