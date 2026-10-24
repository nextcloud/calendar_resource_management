<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

/**
 * One join hop: `<previous>.$foreignKey = $table.$primaryKey`.
 */
final class Relation {
	/**
	 * @param string $foreignKey the column on the previous table that references `$table`
	 * @param string $table the table the hop joins
	 * @param string $primaryKey the column on `$table` that `$foreignKey` matches
	 */
	public function __construct(
		public readonly string $foreignKey,
		public readonly string $table,
		public readonly string $primaryKey = 'id',
	) {
	}
}
