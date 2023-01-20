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
 * Class StoryModel
 *
 * @package OCA\CalendarResourceManagement\Db
 *
 * @method int getBuildingId()
 * @method void setBuildingId(int $buildingId)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 */
class StoryModel extends Entity {
	/** @var integer */
	protected $buildingId;

	/** @var string */
	protected $displayName;

	/**
	 * Story constructor.
	 */
	public function __construct() {
		$this->addType('buildingId', 'integer');
		$this->addType('displayName', 'string');
	}
}
