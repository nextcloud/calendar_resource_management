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
			->execute();
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
			->execute();
	}
}
