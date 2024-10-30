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
 * Class RoomMapper
 *
 * @package OCA\CalendarResourceManagement\Db
 */
class RoomMapper extends AMapper {
	/**
	 * RoomMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'calresources_rooms', RoomModel::class);
	}

	/**
	 * @param string $roomType
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByRoomType(string $roomType,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		return $this->findAllByFilter([
			['room_type', $roomType, IQueryBuilder::PARAM_STR],
		], $orderBy, $ascending, $limit, $offset);
	}

	/**
	 * @param int $storyId
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByStoryId(int $storyId,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		return $this->findAllByFilter([
			['story_id', $storyId, IQueryBuilder::PARAM_INT]
		], $orderBy, $ascending, $limit, $offset);
	}

	/**
	 * @param int $buildingId
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByBuildingId(int $buildingId,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('r.*')
			->from('calresources_rooms', 'r')
			->join('r', 'calresources_stories', 's', $qb->expr()->eq('r.story_id', 's.id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('s.building_id', $qb->createNamedParameter($buildingId, IQueryBuilder::PARAM_INT))
			)
			->orderBy('r.' . $orderBy, $ascending ? 'ASC' : 'DESC');


		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * @param string $roomType
	 * @param int $storyId
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByRoomTypeAndStoryId(string $roomType,
		int $storyId,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		return $this->findAllByFilter([
			['room_type', $roomType, IQueryBuilder::PARAM_STR],
			['story_id', $storyId, IQueryBuilder::PARAM_INT],
		], $orderBy, $ascending, $limit, $offset);
	}

	/**
	 * @param string $roomType
	 * @param int $buildingId
	 * @param string $orderBy
	 * @param bool $ascending
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllByRoomTypeAndBuildingId(string $roomType,
		int $buildingId,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('r.*')
			->from('calresources_rooms', 'r')
			->join('calresources_stories', 's', $qb->expr()->eq('r.storyId', 's.id'))
			->where(
				$qb->expr()->eq('s.building_id', $qb->createNamedParameter($buildingId, IQueryBuilder::PARAM_INT))
			)
			->andWhere(
				$qb->expr()->eq('r.room_type', $qb->createNamedParameter($roomType, IQueryBuilder::PARAM_STR))
			)
			->orderBy('r.' . $orderBy, $ascending ? 'ASC' : 'DESC');


		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}
}
