<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Schema;

use OCA\CalendarResourceManagement\Db\Relation;
final class RelationDefinition {
	/**
	 * @param Relation[] $through the tables joined from the entity's own table, in order
	 * @param string $column the column on the last joined table that holds the value
	 */
	public function __construct(
		public readonly array $through,
		public readonly string $column,
	) {
	}
}
