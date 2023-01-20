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
	}
}
