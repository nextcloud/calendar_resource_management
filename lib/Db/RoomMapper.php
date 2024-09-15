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
