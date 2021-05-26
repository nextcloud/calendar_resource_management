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
 * Class RestrictionModel
 *
 * @package OCA\CalendarResourceManagement\Db
 *
 * @method string getEntityType()
 * @method void setEntityType(string $entityType)
 * @method int getEntityId()
 * @method void setEntityId(int $entityId)
 * @method string getGroupId()
 * @method void setGroupId(string $groupId)
 */
class RestrictionModel extends Entity {

	/** @var string */
	protected $entityType;

	/** @var integer */
	protected $entityId;

	/** @var string */
	protected $groupId;

	/**
	 * Restriction constructor.
	 */
	public function __construct() {
		$this->addType('entityType', 'string');
		$this->addType('entityId', 'integer');
		$this->addType('groupId', 'string');
	}
}
