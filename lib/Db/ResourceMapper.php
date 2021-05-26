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

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Class ResourceMapper
 *
 * @package OCA\CalendarResourceManagement\Db
 */
class ResourceMapper extends AMapper {

	/**
	 * ResourceMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'calresources_resources', ResourceModel::class);
	}

	/**
	 * @param int $buildingId
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByBuilding(int $buildingId,
									  string $orderBy = 'display_name',
									  bool $ascending = true,
									  ?int $limit = null,
									  ?int $offset = null): array {
		return $this->findAllByFilter([
			['building_id', $buildingId, IQueryBuilder::PARAM_INT],
		], $orderBy, $ascending, $limit, $offset);
	}

	/**
	 * @param string $resourceType
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByResourceType(string $resourceType,
										 string $orderBy = 'display_name',
										 bool $ascending = true,
										 ?int $limit = null,
										 ?int $offset = null):array {
		return $this->findAllByFilter([
			['resource_type', $resourceType, IQueryBuilder::PARAM_STR],
		], $orderBy, $ascending, $limit, $offset);
	}

	/**
	 * @param int $buildingId
	 * @param string $resourceType
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByBuildingAndResourceType(int $buildingId,
													string $resourceType,
													string $orderBy = 'display_name',
													bool $ascending = true,
													?int $limit = null,
													?int $offset = null): array {
		return $this->findAllByFilter([
			['building_id', $buildingId, IQueryBuilder::PARAM_INT],
			['resource_type', $resourceType, IQueryBuilder::PARAM_STR],
		], $orderBy, $ascending, $limit, $offset);
	}
}
