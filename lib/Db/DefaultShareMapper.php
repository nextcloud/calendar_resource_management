<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<DefaultShareModel>
 */
class DefaultShareMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'calresources_def_shares', DefaultShareModel::class);
	}

	/**
	 * @return list<DefaultShareModel>
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->orderBy('id', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return string[] The ids of the groups
	 */
	public function findGroupIds(string $entityType, int $entityId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('group_id')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('entity_type', $qb->createNamedParameter($entityType, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('entity_id', $qb->createNamedParameter($entityId, IQueryBuilder::PARAM_INT))
			)
			->orderBy('group_id', 'ASC');

		$result = $qb->executeQuery();
		$groupIds = [];
		while ($row = $result->fetch()) {
			$groupIds[] = (string)$row['group_id'];
		}
		$result->closeCursor();

		return $groupIds;
	}

	/**
	 * Replace the groups of an entity
	 *
	 * @param string[] $groupIds
	 */
	public function setGroupIds(string $entityType, int $entityId, array $groupIds): void {
		$this->db->beginTransaction();
		try {
			$this->deleteAllByEntityTypeAndId($entityType, $entityId);
			foreach (array_unique($groupIds) as $groupId) {
				$share = new DefaultShareModel();
				$share->setEntityType($entityType);
				$share->setEntityId($entityId);
				$share->setGroupId($groupId);
				$this->insert($share);
			}
			$this->db->commit();
		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	public function deleteAllByEntityTypeAndId(string $entityType, int $entityId): void {
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

	public function deleteAllByGroupId(string $groupId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('group_id', $qb->createNamedParameter($groupId, IQueryBuilder::PARAM_STR))
			)
			->executeStatement();
	}
}
