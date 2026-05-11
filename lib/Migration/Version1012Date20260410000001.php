<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCA\CalendarResourceManagement\Constants;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1012Date20260410000001 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$resourceTables = [
			'calresources_resources',
			'calresources_rooms',
			'calresources_vehicles',
		];

		foreach ($resourceTables as $tableName) {
			if (!$schema->hasTable($tableName)) {
				continue;
			}

			$table = $schema->getTable($tableName);
			if ($table->hasColumn('restricted')) {
				continue;
			}

			$table->addColumn('restricted', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$this->backfillRestrictedFlag($schema, 'calresources_resources', Constants::RESOURCE);
		$this->backfillRestrictedFlag($schema, 'calresources_rooms', Constants::ROOM);
		$this->backfillRestrictedFlag($schema, 'calresources_vehicles', Constants::VEHICLE);
	}

	private function backfillRestrictedFlag(ISchemaWrapper $schema, string $tableName, string $entityType): void {
		if (!$schema->hasTable($tableName) || !$schema->hasTable('calresources_restricts')) {
			return;
		}

		$table = $schema->getTable($tableName);
		if (!$table->hasColumn('restricted')) {
			return;
		}

		$select = $this->connection->getQueryBuilder();
		$select->selectDistinct('entity_id')
			->from('calresources_restricts')
			->where(
				$select->expr()->eq('entity_type', $select->createNamedParameter($entityType, IQueryBuilder::PARAM_STR))
			);

		$entityIds = array_map('intval', $select->executeQuery()->fetchFirstColumn());
		if ($entityIds === []) {
			return;
		}

		$update = $this->connection->getQueryBuilder();
		$update->update($tableName)
			->set('restricted', $update->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
			->where(
				$update->expr()->in('id', $update->createParameter('ids'))
			);

		foreach (array_chunk($entityIds, 1000) as $entityIdChunk) {
			$update->setParameter('ids', $entityIdChunk, IQueryBuilder::PARAM_INT_ARRAY);
			$update->executeStatement();
		}
	}
}
