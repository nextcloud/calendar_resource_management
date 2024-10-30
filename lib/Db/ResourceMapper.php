<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
