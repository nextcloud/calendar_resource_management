<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Class StoryMapper
 *
 * @package OCA\CalendarResourceManagement\Db
 */
class StoryMapper extends QBMapper {
	/**
	 * StoryMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'calresources_stories', StoryModel::class);
	}

	/**
	 * @param int $id
	 * @return StoryModel
	 * @throws DoesNotExistException if not found
	 * @throws MultipleObjectsReturnedException if more than one result
	 */
	public function find(int $id):StoryModel {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity($qb);
	}

	/**
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return BuildingModel[]
	 */
	public function findAll(string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->orderBy($orderBy, $ascending ? 'ASC' : 'DESC');

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * @param int $buildingId
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return StoryModel[]
	 */
	public function findAllByBuilding(int $buildingId,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('building_id', $qb->createNamedParameter($buildingId, IQueryBuilder::PARAM_INT))
			)
			->orderBy('display_name', 'ASC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		return $this->findEntities($qb);
	}

	/**
	 * @param int[] $buildingIds
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return StoryModel[]
	 */
	public function findAllByBuildings(array $buildingIds,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->in('building_id', $qb->createNamedParameter($buildingIds, IQueryBuilder::PARAM_INT_ARRAY))
			)
			->orderBy('display_name', 'ASC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		return $this->findEntities($qb);
	}

	/**
	 * @param int $buildingId
	 */
	public function deleteAllByBuildingId(int $buildingId):void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('building_id', $qb->createNamedParameter($buildingId, IQueryBuilder::PARAM_INT))
			)
			->executeStatement();
	}
}
