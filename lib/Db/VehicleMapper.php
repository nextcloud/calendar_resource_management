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
 * Class VehicleMapper
 *
 * @package OCA\CalendarResourceManagement\Db
 */
class VehicleMapper extends AMapper {
	/**
	 * VehicleMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'calresources_vehicles', VehicleModel::class);
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
	 * @param string $vehicleType
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByVehicleType(string $vehicleType,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null):array {
		return $this->findAllByFilter([
			['vehicle_type', $vehicleType, IQueryBuilder::PARAM_STR],
		], $orderBy, $ascending, $limit, $offset);
	}

	/**
	 * @param int $buildingId
	 * @param string $vehicleType
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByBuildingAndVehicleType(int $buildingId,
		string $vehicleType,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		return $this->findAllByFilter([
			['building_id', $buildingId, IQueryBuilder::PARAM_INT],
			['vehicle_type', $vehicleType, IQueryBuilder::PARAM_STR],
		], $orderBy, $ascending, $limit, $offset);
	}
}
