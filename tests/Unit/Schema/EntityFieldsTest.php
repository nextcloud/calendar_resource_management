<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Schema;

use OCA\CalendarResourceManagement\Db\Relation;
use OCA\CalendarResourceManagement\Schema\Entities\Buildings;
use OCA\CalendarResourceManagement\Schema\Entities\Resources;
use OCA\CalendarResourceManagement\Schema\Entities\Rooms;
use OCA\CalendarResourceManagement\Schema\Entities\Stories;
use OCA\CalendarResourceManagement\Schema\Entities\Vehicles;
use OCA\CalendarResourceManagement\Schema\EntityDefinition;
use OCA\CalendarResourceManagement\Schema\FieldDefinition;
use OCP\IL10N;
use Test\TestCase;

/**
 * Pins every entity's field declarations; change a line here only on purpose.
 */
class EntityFieldsTest extends TestCase {
	/** @var array<string, array{meta: string, fields: string[]}> */
	private const EXPECTED = [
		'buildings' => [
			'meta' => 'buildings|calresources_buildings|/buildings|Buildings|building|cascades',
			'fields' => [
				'id|ID|int|-|id|table|-|-|-|number|-|-|-',
				'displayName|Name|string|text|display_name|table|required|-|-|text|-|-|-',
				'address|Address|string|text|address|table|-|-|-|text|-|-|-',
				'description|Description|string|textarea|description|table|-|-|-|text|-|-|-',
				'isWheelchairAccessible|Wheelchair accessible|bool|bool|is_wheelchair_accessible|table|-|-|-|bool|-|-|-',
			],
		],
		'stories' => [
			'meta' => 'stories|calresources_stories|/stories|Stories|story|cascades',
			'fields' => [
				'id|ID|int|-|id|table|-|-|-|number|-|-|-',
				'buildingId|Building|int|select|building_id|table|required|-|-|buildings|-|-|-',
				'displayName|Name|string|text|display_name|table|required|-|-|text|-|-|-',
			],
		],
		'rooms' => [
			'meta' => 'rooms|calresources_rooms|/rooms|Rooms|room|-',
			'fields' => [
				'id|ID|int|-|id|table|-|-|-|number|-|-|-',
				'buildingId|Building|int|select|-|-|required|-|-|buildings|story_id>calresources_stories.id|building_id|storyId',
				'storyId|Story|int|select|story_id|-|required|-|-|select|-|-|-',
				'displayName|Name|string|text|display_name|table|required|-|-|text|-|-|-',
				'email|Email|string|email|email|table|-|-|-|text|-|-|-',
				'roomType|Room type|string|combobox|room_type|table|-|-|-|enum|-|-|-',
				'capacity|Capacity|int|number|capacity|table|-|0|-|range|-|-|-',
				'uid|UID|string|-|uid|table|-|-|-|text|-|-|-',
				'contactPersonUserId|Contact person (user ID)|string|user|contact_person_user_id|-|-|-|-|user|-|-|-',
				'roomNumber|Room number|string|text|room_number|-|-|-|-|text|-|-|-',
				'hasPhone|Has phone|bool|bool|has_phone|-|-|-|-|bool|-|-|-',
				'hasVideoConferencing|Has video conferencing|bool|bool|has_video_conferencing|-|-|-|-|bool|-|-|-',
				'hasTv|Has TV|bool|bool|has_tv|-|-|-|-|bool|-|-|-',
				'hasProjector|Has projector|bool|bool|has_projector|-|-|-|-|bool|-|-|-',
				'hasWhiteboard|Has whiteboard|bool|bool|has_whiteboard|-|-|-|-|bool|-|-|-',
				'isWheelchairAccessible|Wheelchair accessible|bool|bool|is_wheelchair_accessible|-|-|-|-|bool|-|-|-',
				'restricted|Restricted|bool|bool|restricted|-|-|-|-|bool|-|-|-',
			],
		],
		'resources' => [
			'meta' => 'resources|calresources_resources|/resources|Resources|resource|-',
			'fields' => [
				'id|ID|int|-|id|table|-|-|-|number|-|-|-',
				'buildingId|Building|int|select|building_id|-|required|-|-|buildings|-|-|-',
				'displayName|Name|string|text|display_name|table|required|-|-|text|-|-|-',
				'email|Email|string|email|email|table|-|-|-|text|-|-|-',
				'resourceType|Resource type|string|combobox|resource_type|table|-|-|-|enum|-|-|-',
				'uid|UID|string|-|uid|table|-|-|-|text|-|-|-',
				'contactPersonUserId|Contact person (user ID)|string|user|contact_person_user_id|-|-|-|-|user|-|-|-',
				'restricted|Restricted|bool|bool|restricted|-|-|-|-|bool|-|-|-',
			],
		],
		'vehicles' => [
			'meta' => 'vehicles|calresources_vehicles|/vehicles|Vehicles|vehicle|-',
			'fields' => [
				'id|ID|int|-|id|table|-|-|-|number|-|-|-',
				'buildingId|Building|int|select|building_id|-|required|-|-|buildings|-|-|-',
				'displayName|Name|string|text|display_name|table|required|-|-|text|-|-|-',
				'email|Email|string|email|email|table|-|-|-|text|-|-|-',
				'vehicleType|Vehicle type|string|combobox|vehicle_type|table|-|-|-|enum|-|-|-',
				'seatingCapacity|Seating capacity|int|number|seating_capacity|table|-|0|-|range|-|-|-',
				'vehicleMake|Make|string|text|vehicle_make|-|-|-|-|text|-|-|-',
				'vehicleModel|Model|string|text|vehicle_model|-|-|-|-|text|-|-|-',
				'uid|UID|string|-|uid|table|-|-|-|text|-|-|-',
				'contactPersonUserId|Contact person (user ID)|string|user|contact_person_user_id|-|-|-|-|user|-|-|-',
				'range|Range|int|number|range|-|-|0|-|range|-|-|-',
				'isElectric|Electric|bool|bool|is_electric|-|-|-|-|bool|-|-|-',
				'restricted|Restricted|bool|bool|restricted|-|-|-|-|bool|-|-|-',
			],
		],
	];

	public function testEveryEntityDeclaresTheSameFieldsAsBeforeTheySharedAny(): void {
		foreach ($this->definitions() as $key => $entity) {
			$this->assertSame(self::EXPECTED[$key]['meta'], $this->meta($entity), $key . ': entity metadata');
			$this->assertSame(
				self::EXPECTED[$key]['fields'],
				array_map([$this, 'signature'], $entity->fields),
				$key . ': field declarations'
			);
		}
	}

	public function testTheSchemaCoversEveryEntityTheAdminUiHasATabFor(): void {
		$this->assertSame(array_keys(self::EXPECTED), array_keys($this->definitions()));
	}

	/**
	 * Each definition carries the key the admin UI files it under.
	 */
	public function testEachDefinitionNamesItsOwnKey(): void {
		foreach ($this->definitions() as $key => $definition) {
			$this->assertSame($key, $definition->key);
		}
	}

	/**
	 * The five entity definitions, keyed by their schema key in tab order.
	 *
	 * @return array<string, EntityDefinition>
	 */
	private function definitions(): array {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return [
			'buildings' => new Buildings($l10n),
			'stories' => new Stories($l10n),
			'rooms' => new Rooms($l10n),
			'resources' => new Resources($l10n),
			'vehicles' => new Vehicles($l10n),
		];
	}

	/**
	 * Everything about the entity itself, as one comparable line.
	 */
	private function meta(EntityDefinition $entity): string {
		return implode('|', [
			$entity->key,
			$entity->table,
			$entity->path,
			$entity->label,
			$entity->singular,
			$entity->cascades ? 'cascades' : '-',
		]);
	}

	/**
	 * Every constructor property of one field, as one comparable line.
	 */
	private function signature(FieldDefinition $field): string {
		return implode('|', [
			$field->name,
			$field->label,
			$field->type,
			$field->form ?? '-',
			$field->column ?? '-',
			$field->inTable ? 'table' : '-',
			$field->required ? 'required' : '-',
			$field->min ?? '-',
			$field->max ?? '-',
			$field->filter ?? '-',
			$field->relation === null ? '-' : (implode('+', array_map(
				static fn (Relation $hop) => $hop->foreignKey . '>' . $hop->table . '.' . $hop->primaryKey,
				$field->relation->through,
			)) ?: '-'),
			$field->relation?->column ?? '-',
			$field->cascade ?? '-',
		]);
	}
}
