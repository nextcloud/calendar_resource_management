<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Schema-driven filtering for mappers, from {@see Criterion} objects.
 */
trait FilterableTrait {
	/** The alias the entity's own table is queried under. */
	private const BASE_ALIAS = 'e';

	/**
	 * Add criteria as AND-ed WHERE clauses, joining the tables they need.
	 *
	 * Joins are deduped by table and go FK to PK, so rows never multiply.
	 *
	 * @param IQueryBuilder $qb the query under construction
	 * @param Criterion[] $criteria the conditions to apply
	 * @throws \InvalidArgumentException on an unknown operator
	 */
	protected function applyCriteria(IQueryBuilder $qb, array $criteria): void {
		$joined = [];

		foreach ($criteria as $criterion) {
			$alias = self::BASE_ALIAS;

			foreach ($criterion->joins as $hop) {
				if (!isset($joined[$hop->table])) {
					$joined[$hop->table] = true;
					$qb->innerJoin(
						$alias,
						$hop->table,
						$hop->table,
						$qb->expr()->eq(
							$alias . '.' . $hop->foreignKey,
							$hop->table . '.' . $hop->primaryKey,
						)
					);
				}
				$alias = $hop->table;
			}

			$field = $alias . '.' . $criterion->column;
			$value = $criterion->value;

			if ($value === null) {
				$qb->andWhere($qb->expr()->isNull($field));
				continue;
			}

			switch ($criterion->op) {
				case Criterion::OP_LIKE:
					$qb->andWhere($qb->expr()->iLike(
						$field,
						$qb->createNamedParameter(
							'%' . $this->db->escapeLikeParameter((string)$value) . '%',
							IQueryBuilder::PARAM_STR
						),
						IQueryBuilder::PARAM_STR
					));
					break;
				case Criterion::OP_GTE:
					$qb->andWhere($qb->expr()->gte($field, $qb->createNamedParameter($value, $criterion->paramType)));
					break;
				case Criterion::OP_LTE:
					$qb->andWhere($qb->expr()->lte($field, $qb->createNamedParameter($value, $criterion->paramType)));
					break;
				case Criterion::OP_EQ:
					$qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value, $criterion->paramType)));
					break;
				default:
					throw new \InvalidArgumentException('Unknown filter operator ' . $criterion->op);
			}
		}
	}

	/**
	 * Find all entities matching the given criteria.
	 *
	 * @param Criterion[] $criteria the conditions to apply
	 * @param string $orderBy the column to sort by
	 * @param bool $ascending sort direction
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return Entity[]
	 */
	public function findAllFiltered(array $criteria,
		string $orderBy = 'display_name',
		bool $ascending = true,
		?int $limit = null,
		?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::BASE_ALIAS . '.*')
			->from($this->tableName, self::BASE_ALIAS)
			->orderBy(self::BASE_ALIAS . '.' . $orderBy, $ascending ? 'ASC' : 'DESC');

		$this->applyCriteria($qb, $criteria);

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * Count the entities matching the given criteria.
	 *
	 * Mirrors {@see self::findAllFiltered()}; FK-to-PK joins need no `DISTINCT`.
	 *
	 * @param Criterion[] $criteria the conditions to apply
	 * @return int the number of matching rows
	 * @throws Exception on a database error
	 */
	public function countAllFiltered(array $criteria): int {
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->count(self::BASE_ALIAS . '.id'))
			->from($this->tableName, self::BASE_ALIAS);

		$this->applyCriteria($qb, $criteria);

		$result = $qb->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		return $count;
	}

	/**
	 * The distinct non-empty values of one column, for a filter's options.
	 *
	 * @param string $column the column to collect, unqualified
	 * @return string[] the distinct values, ascending, as a re-indexed list
	 * @throws Exception on a database error
	 */
	public function distinctValues(string $column): array {
		$qb = $this->db->getQueryBuilder();
		$field = self::BASE_ALIAS . '.' . $column;

		$qb->selectDistinct($field)
			->from($this->tableName, self::BASE_ALIAS)
			->where($qb->expr()->isNotNull($field))
			->andWhere($qb->expr()->neq(
				$field,
				$qb->createNamedParameter('', IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR
			))
			->orderBy($field, 'ASC');

		$result = $qb->executeQuery();
		$values = $result->fetchFirstColumn();
		$result->closeCursor();

		return $values;
	}
}
