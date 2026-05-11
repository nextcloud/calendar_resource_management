<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;

abstract class AMapper extends QBMapper {
	/**
	 * @param int $id
	 * @return Entity
	 * @throws DoesNotExistException if not found
	 * @throws MultipleObjectsReturnedException if more than one result
	 */
	public function find(int $id):Entity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity($qb);
	}

	/**
	 * @param string $uid
	 * @return Entity
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function findByUID(string $uid):Entity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	/**
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return Entity[]
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
	 * @param array $filter
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return Entity[]
	 */
	protected function findAllByFilter(array $filter,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null):array {
		if (empty($filter)) {
			return $this->findAll($orderBy, $ascending, $limit, $offset);
		}

		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->orderBy($orderBy, $ascending ? 'ASC' : 'DESC');

		foreach ($filter as [$column, $value, $type]) {
			if ($value === null) {
				$qb->andWhere(
					$qb->expr()->isNull($column)
				);
			} else {
				$qb->andWhere(
					$qb->expr()->eq($column, $qb->createNamedParameter($value, $type))
				);
			}
		}

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return string[]
	 * @throws Exception
	 */
	public function findAllUIDs(string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('uid')
			->from($this->tableName)
			->orderBy($orderBy, $ascending ? 'ASC' : 'DESC');

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}
		$stmt = $qb->executeQuery();

		$uids = [];
		while ($row = $stmt->fetch()) {
			$uids[] = $row['uid'] ;
		}

		return $uids;
	}

	/**
	 * @param string $search
	 * @param string $searchBy
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function search(string $search,
		string $searchBy = 'display_name',
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->iLike(
				$searchBy,
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

	/**
	 * @param string $userId
	 * @throws Exception
	 */
	public function removeContactUserId(string $userId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->update($this->tableName)
			->set('contact_person_user_id', $qb->createNamedParameter(null))
			->where($qb->expr()->eq('contact_person_user_id', $qb->createNamedParameter($userId)));

		$qb->executeStatement();
	}

	/**
	 * @param string $type
	 * @param \OCP\IDBConnection $db
	 * @return BuildingMapper|ResourceMapper|RestrictionMapper|RoomMapper|StoryMapper|VehicleMapper|null
	 */
	public static function getMapper(string $type, \OCP\IDBConnection $db) {
		$type = strtolower($type);
		$mapper = null;
		switch ($type) {
			case 'building':
				$mapper = new BuildingMapper($db);
				break;
			case 'resource':
				$mapper = new ResourceMapper($db);
				break;
			case 'restriction':
				$mapper = new RestrictionMapper($db);
				break;
			case 'room':
				$mapper = new RoomMapper($db);
				break;
			case 'story':
				$mapper = new StoryMapper($db);
				break;
			case 'vehicle':
				$mapper = new VehicleMapper($db);
				break;
			default:
				break;
		}
		return $mapper;
	}
}
