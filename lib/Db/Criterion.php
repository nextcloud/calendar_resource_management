<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

/**
 * One `WHERE` condition, ready for {@see FilterableTrait::applyCriteria()}.
 */
final class Criterion {
	/** Exact match. */
	public const OP_EQ = 'eq';

	/** Case-insensitive substring match. */
	public const OP_LIKE = 'like';

	/** Greater than or equal (inclusive lower bound). */
	public const OP_GTE = 'gte';

	/** Less than or equal (inclusive upper bound). */
	public const OP_LTE = 'lte';

	/**
	 * @param string $column the column to compare, unqualified
	 * @param string $op one of the `OP_*` constants
	 * @param mixed $value the value to compare against
	 * @param int|string $paramType an `IQueryBuilder::PARAM_*` constant
	 * @param Relation[] $joins the hops the column lives behind, in order
	 */
	public function __construct(
		public readonly string $column,
		public readonly string $op,
		public readonly mixed $value,
		public readonly int|string $paramType,
		public readonly array $joins = [],
	) {
	}
}
