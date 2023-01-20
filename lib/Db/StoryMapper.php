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
			->execute();
	}
}
