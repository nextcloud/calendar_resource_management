<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version1000Date20200805220319
 *
 * @package OCA\CalendarResourceManagement\Migration
 */
class Version1000Date20200805220319 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options):?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		/**
		 * @see \OCA\CalendarResourceManagement\Db\BuildingModel
		 */
		if (!$schema->hasTable('calresources_buildings')) {
			$table = $schema->createTable('calresources_buildings');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('description', Types::STRING, [
				'notnull' => false,
				'length' => 4000,
			]);
			$table->addColumn('address', Types::STRING, [
				'notnull' => false,
				'length' => 4000,
			]);
			$table->addColumn('is_wheelchair_accessible', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$table->setPrimaryKey(['id']);
		}

		/**
		 * @see \OCA\CalendarResourceManagement\Db\StoryModel
		 */
		if (!$schema->hasTable('calresources_stories')) {
			$table = $schema->createTable('calresources_stories');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('building_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['building_id'], 'calresources_stories_bid');
		}

		/**
		 * @see \OCA\CalendarResourceManagement\Db\ResourceModel
		 */
		if (!$schema->hasTable('calresources_resources')) {
			$table = $schema->createTable('calresources_resources');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('building_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('email', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('resource_type', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('contact_person_user_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['building_id'], 'calresources_resources_bid');
			$table->addUniqueIndex(['uid'], 'calresources_resources_uid');
			$table->addUniqueIndex(['email'], 'calresources_resources_eml');
		}

		/**
		 * @see \OCA\CalendarResourceManagement\Db\RestrictionModel
		 */
		if (!$schema->hasTable('calresources_restricts')) {
			$table = $schema->createTable('calresources_restricts');
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
			$table->addIndex(['entity_type', 'entity_id'], 'calresources_restricts_ent');
			$table->addUniqueIndex(['entity_type', 'entity_id', 'group_id'], 'calresources_restricts_eeg');
		}

		/**
		 * @see \OCA\CalendarResourceManagement\Db\RoomModel
		 */
		if (!$schema->hasTable('calresources_rooms')) {
			$table = $schema->createTable('calresources_rooms');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('story_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('email', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('room_type', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('contact_person_user_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('capacity', Types::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('room_number', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('has_phone', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$table->addColumn('has_video_conferencing', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$table->addColumn('has_tv', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$table->addColumn('has_projector', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$table->addColumn('has_whiteboard', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$table->addColumn('is_wheelchair_accessible', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['story_id'], 'calresources_rooms_sid');
			$table->addUniqueIndex(['uid'], 'calresources_rooms_uid');
			$table->addUniqueIndex(['email'], 'calresources_rooms_eml');
		}

		/**
		 * @see \OCA\CalendarResourceManagement\Db\VehicleModel
		 */
		if (!$schema->hasTable('calresources_vehicles')) {
			$table = $schema->createTable('calresources_vehicles');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('building_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('email', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('resource_type', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => 'vehicle',
			]);
			$table->addColumn('contact_person_user_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('vehicle_type', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('vehicle_make', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('vehicle_model', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('is_electric', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$table->addColumn('range', Types::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('seating_capacity', Types::INTEGER, [
				'notnull' => false,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['building_id'], 'calresources_vehicles_bid');
			$table->addUniqueIndex(['uid'], 'calresources_vehicles_uid');
			$table->addUniqueIndex(['email'], 'calresources_vehicles_eml');
		}

		$buildings = $schema->getTable('calresources_buildings');

		// add building FK to resources
		$resources = $schema->getTable('calresources_resources');
		$resources->addForeignKeyConstraint($buildings, ['building_id'], ['id'], ['onDelete' => 'CASCADE']);

		// add building FK to vehicles
		$vehicles = $schema->getTable('calresources_vehicles');
		$vehicles->addForeignKeyConstraint($buildings, ['building_id'], ['id'], ['onDelete' => 'CASCADE']);

		// add building FK to stories
		$stories = $schema->getTable('calresources_stories');
		$stories->addForeignKeyConstraint($buildings, ['building_id'], ['id'], ['onDelete' => 'CASCADE']);

		// add stories FK to rooms
		$rooms = $schema->getTable('calresources_rooms');
		$rooms->addForeignKeyConstraint($stories, ['story_id'], ['id'], ['onDelete' => 'CASCADE']);

		return $schema;
	}
}
