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
 * Class BuildingMapper
 *
 * @package OCA\CalendarResourceManagement\Db
 */
class BuildingMapper extends QBMapper {
	/**
	 * BuildingMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'calresources_buildings', BuildingModel::class);
	}

	/**
	 * @param int $id
	 * @return BuildingModel
	 * @throws DoesNotExistException if not found
	 * @throws MultipleObjectsReturnedException if more than one result
	 */
	public function find(int $id):BuildingModel {
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
	 * @param string $search
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return BuildingModel[]
	 */
	public function search(string $search,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->iLike(
				'display_name',
				$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($search) . '%', IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR
			))
			->orWhere($qb->expr()->iLike(
				'description',
				$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($search) . '%', IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR
			))
			->orWhere($qb->expr()->iLike(
				'address',
				$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($search) . '%', IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR
			))
			->orderBy($orderBy, $ascending ? 'ASC' : 'DESC');

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}
}
