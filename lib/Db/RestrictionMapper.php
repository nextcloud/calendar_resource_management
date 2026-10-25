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
 * Class RestrictionMapper
 *
 * @package OCA\CalendarResourceManagement\Db
 * @template-extends QBMapper<RestrictionModel>
 */
class RestrictionMapper extends QBMapper {
	/**
	 * RestrictionMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'calresources_restricts', RestrictionModel::class);
	}

	/**
	 * @param int $id
	 * @return RestrictionModel
	 * @throws DoesNotExistException if not found
	 * @throws MultipleObjectsReturnedException if more than one result
	 */
	public function find(int $id):RestrictionModel {
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
	 * @return list<RestrictionModel>
	 */
	public function findAll(string $orderBy = 'id',
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
	 * @param string $entityType
	 * @param int $entityId
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return RestrictionModel[]
	 */
	public function findAllByEntityTypeAndId(string $entityType,
		int $entityId,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('entity_type', $qb->createNamedParameter($entityType, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('entity_id', $qb->createNamedParameter($entityId, IQueryBuilder::PARAM_INT))
			)
			->orderBy('group_id', 'ASC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		return $this->findEntities($qb);
	}

	/**
	 * @param string $entityType
	 * @param int $entityId
	 */
	public function deleteAllByEntityTypeAndId(string $entityType,
		int $entityId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('entity_type', $qb->createNamedParameter($entityType, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('entity_id', $qb->createNamedParameter($entityId, IQueryBuilder::PARAM_INT))
			)
			->executeStatement();
	}

	/**
	 * @return array<int, string[]> The group ids of the restrictions per entity id
	 */
	public function findGroupIdsByEntityType(string $entityType): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('entity_id', 'group_id')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('entity_type', $qb->createNamedParameter($entityType, IQueryBuilder::PARAM_STR))
			)
			->orderBy('group_id', 'ASC');

		$result = $qb->executeQuery();
		$groupIds = [];
		while ($row = $result->fetch()) {
			$groupIds[(int)$row['entity_id']][] = (string)$row['group_id'];
		}
		$result->closeCursor();

		return $groupIds;
	}

	/**
	 * Replace the restrictions of an entity
	 *
	 * @param string[] $groupIds
	 */
	public function setGroupIds(string $entityType, int $entityId, array $groupIds): void {
		$this->db->beginTransaction();
		try {
			$this->deleteAllByEntityTypeAndId($entityType, $entityId);
			foreach (array_unique($groupIds) as $groupId) {
				$restriction = new RestrictionModel();
				$restriction->setEntityType($entityType);
				$restriction->setEntityId($entityId);
				$restriction->setGroupId($groupId);
				$this->insert($restriction);
			}
			$this->db->commit();
		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/**
	 * @param string $groupId
	 */
	public function deleteAllRestrictionsByGroupId(string $groupId):void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('group_id', $qb->createNamedParameter($groupId, IQueryBuilder::PARAM_STR))
			)
			->executeStatement();
	}
}
