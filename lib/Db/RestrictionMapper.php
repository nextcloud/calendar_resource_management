<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Class RestrictionMapper
 *
 * @package OCA\CalendarResourceManagement\Db
 */
class RestrictionMapper extends QBMapper {
	/** @var string */
	public const TYPE_RESOURCE = 'resource';

	/** @var string */
	public const TYPE_ROOM = 'room';

	/** @var string */
	public const TYPE_VEHICLE = 'vehicle';

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
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
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
	 * @return BuildingModel[]
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
