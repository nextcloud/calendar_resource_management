<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class ResourceModel
 *
 * @package OCA\CalendarResourceManagement\Db
 *
 * @method string getUid()
 * @method void setUid(string $uid)
 * @method int getBuildingId()
 * @method void setBuildingId(int $buildingId)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getResourceType()
 * @method void setResourceType(string $resourceType)
 * @method string getContactPersonUserId()
 * @method void setContactPersonUserId(string $contactPersonUserId)
 * @method void setRestricted(bool $restricted)
 */
class ResourceModel extends Entity {
	/** @var string */
	protected $uid;

	/** @var integer */
	protected $buildingId;

	/** @var string */
	protected $displayName;

	/** @var string */
	protected $email;

	/** @var string */
	protected $resourceType;

	/** @var string */
	protected $contactPersonUserId;

	/** @var boolean|null */
	protected $restricted;

	/**
	 * Resource constructor.
	 */
	public function __construct() {
		$this->addType('uid', 'string');
		$this->addType('buildingId', 'integer');
		$this->addType('displayName', 'string');
		$this->addType('email', 'string');
		$this->addType('resourceType', 'string');
		$this->addType('contactPersonUserId', 'string');
		$this->addType('restricted', 'boolean');
	}

	public function isRestricted(): bool {
		return $this->restricted ?? false;
	}
}
