<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1100Date20261025000000 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		/**
		 * @see \OCA\CalendarResourceManagement\Db\DefaultShareModel
		 */
		if ($schema->hasTable('calresources_def_shares')) {
			return null;
		}

		$table = $schema->createTable('calresources_def_shares');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 11,
			'unsigned' => true,
		]);
		$table->addColumn('entity_type', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('entity_id', Types::BIGINT, [
			'notnull' => true,
			'length' => 11,
			'unsigned' => true,
		]);
		$table->addColumn('group_id', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);

		$table->setPrimaryKey(['id']);
		// Also serves lookups by entity, its first columns are the entity
		$table->addUniqueIndex(['entity_type', 'entity_id', 'group_id'], 'calresources_defshares_eeg');

		return $schema;
	}
}
