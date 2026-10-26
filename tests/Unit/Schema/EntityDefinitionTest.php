<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Schema;

use OCA\CalendarResourceManagement\Db\Criterion;
use OCA\CalendarResourceManagement\Db\Relation;
use OCA\CalendarResourceManagement\Schema\Entities\Buildings;
use OCA\CalendarResourceManagement\Schema\Entities\Resources;
use OCA\CalendarResourceManagement\Schema\Entities\Rooms;
use OCA\CalendarResourceManagement\Schema\Entities\Stories;
use OCA\CalendarResourceManagement\Schema\Entities\Vehicles;
use OCA\CalendarResourceManagement\Schema\EntityDefinition;
use OCA\CalendarResourceManagement\Schema\FieldDefinition;
use OCA\CalendarResourceManagement\Schema\RelationDefinition;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IL10N;
use Test\TestCase;

class EntityDefinitionTest extends TestCase {
	/** @var array<string, EntityDefinition> keyed by schema key */
	private array $definitions;

	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$this->definitions = [
			'buildings' => new Buildings($l10n),
			'stories' => new Stories($l10n),
			'rooms' => new Rooms($l10n),
			'resources' => new Resources($l10n),
			'vehicles' => new Vehicles($l10n),
		];
	}

	public function testCriteriaDropsUnknownAndBlankValues(): void {
		$criteria = $this->entity('rooms')->criteria([
			// Not a filterable field of rooms.
			'bogus' => 'x',
			// Absent means "any".
			'displayName' => '',
			'roomNumber' => null,
		]);

		$this->assertSame([], $criteria);
	}

	public function testCriteriaKeepsFalseAndZero(): void {
		$criteria = $this->entity('rooms')->criteria([
			'hasTv' => false,
			'capacityMin' => 0,
		]);

		$this->assertCount(2, $criteria);
		$this->assertSame(0, $this->criterion($criteria, 'capacity')->value);
		$this->assertFalse($this->criterion($criteria, 'has_tv')->value);
	}

	public function testCriteriaMapsTheFilterKindToAnOperator(): void {
		$criteria = $this->entity('rooms')->criteria([
			'displayName' => 'conf',
			'roomType' => 'meeting',
			'id' => 7,
		]);

		$this->assertSame(Criterion::OP_LIKE, $this->criterion($criteria, 'display_name')->op);
		$this->assertSame(Criterion::OP_EQ, $this->criterion($criteria, 'room_type')->op);
		$this->assertSame(Criterion::OP_EQ, $this->criterion($criteria, 'id')->op);
	}

	public function testCriteriaExpandsARangeIntoBothBounds(): void {
		$criteria = $this->entity('rooms')->criteria([
			'capacityMin' => 4,
			'capacityMax' => 12,
		]);

		$this->assertCount(2, $criteria);
		$this->assertSame(Criterion::OP_GTE, $criteria[0]->op);
		$this->assertSame(4, $criteria[0]->value);
		$this->assertSame(Criterion::OP_LTE, $criteria[1]->op);
		$this->assertSame(12, $criteria[1]->value);
		$this->assertSame('capacity', $criteria[1]->column);
	}

	/**
	 * A room's building filter joins through the story table.
	 */
	public function testCriteriaResolvesAVirtualFieldThroughItsRelation(): void {
		$criteria = $this->entity('rooms')->criteria(['buildingId' => 3]);

		$this->assertCount(1, $criteria);
		$criterion = $criteria[0];
		$this->assertSame('building_id', $criterion->column);
		$this->assertSame(3, $criterion->value);

		$this->assertCount(1, $criterion->joins);
		$hop = $criterion->joins[0];
		$this->assertSame('story_id', $hop->foreignKey);
		$this->assertSame('calresources_stories', $hop->table);
		$this->assertSame('id', $hop->primaryKey);
	}

	public function testCriteriaNeedsNoJoinForAFieldWithItsOwnColumn(): void {
		foreach ([['rooms', 'storyId'], ['stories', 'buildingId'], ['vehicles', 'buildingId']] as [$entity, $field]) {
			$criteria = $this->entity($entity)->criteria([$field => 1]);

			$this->assertCount(1, $criteria);
			$this->assertSame([], $criteria[0]->joins);
		}
	}

	public function testFrontendDefinitionDescribesTheEntityForTheAdminUi(): void {
		$rooms = $this->frontend('rooms');
		$this->assertSame('/rooms', $rooms['path']);
		$this->assertFalse($rooms['cascades']);
		$this->assertSame(
			['id', 'displayName', 'email', 'roomType', 'capacity'],
			array_column($rooms['columns'], 'key')
		);
		$this->assertSame('range', $this->descriptor($rooms['filters'], 'capacity')['type']);
		$this->assertSame('select', $this->descriptor($rooms['filters'], 'roomType')['type']);
	}

	/**
	 * Every entity's select filters name their options path, so the paths can't drift.
	 */
	public function testFrontendDefinitionPointsEverySelectFilterAtItsOptions(): void {
		$entities = $this->frontendEntities();

		$this->assertSame('/buildings/options', $this->descriptor($entities['stories']['filters'], 'buildingId')['options']);

		$rooms = $entities['rooms']['filters'];
		$this->assertSame('/buildings/options', $this->descriptor($rooms, 'buildingId')['options']);
		$this->assertSame('/stories/options', $this->descriptor($rooms, 'storyId')['options']);
		$this->assertSame('/rooms/types', $this->descriptor($rooms, 'roomType')['options']);

		$resources = $entities['resources']['filters'];
		$this->assertSame('/buildings/options', $this->descriptor($resources, 'buildingId')['options']);
		$this->assertSame('/resources/types', $this->descriptor($resources, 'resourceType')['options']);

		$vehicles = $entities['vehicles']['filters'];
		$this->assertSame('/buildings/options', $this->descriptor($vehicles, 'buildingId')['options']);
		$this->assertSame('/vehicles/types', $this->descriptor($vehicles, 'vehicleType')['options']);

		// Every path named above is a path the entity it belongs to declares.
		$this->assertSame('/buildings', $entities['buildings']['path']);
		$this->assertSame('/stories', $entities['stories']['path']);
	}

	/**
	 * A form field carries its filter's options; a field without options carries none.
	 */
	public function testFrontendDefinitionGivesAFormFieldTheSameOptionsAsItsFilter(): void {
		$rooms = $this->frontend('rooms');

		$this->assertSame('/rooms/types', $this->descriptor($rooms['fields'], 'roomType')['options']);
		$this->assertSame('/buildings/options', $this->descriptor($rooms['fields'], 'buildingId')['options']);
		$this->assertArrayNotHasKey('options', $this->descriptor($rooms['fields'], 'hasTv'));
	}

	/**
	 * A filter states only its kind, not its data type.
	 */
	public function testFrontendDefinitionStatesOnlyTheFilterKind(): void {
		$rooms = $this->frontend('rooms');

		foreach (['roomType', 'storyId'] as $key) {
			$filter = $this->descriptor($rooms['filters'], $key);
			$this->assertSame('select', $filter['type']);
			$this->assertArrayNotHasKey('valueType', $filter);
		}
	}

	/**
	 * A boolean has its own filter kind and names no options.
	 */
	public function testFrontendDefinitionGivesABooleanItsOwnFilterKind(): void {
		$rooms = $this->frontend('rooms');
		$hasTv = $this->descriptor($rooms['filters'], 'hasTv');

		$this->assertSame('bool', $hasTv['type']);
		$this->assertArrayNotHasKey('options', $hasTv);
		$this->assertSame(Criterion::OP_EQ, $this->entity('rooms')->field('hasTv')->operator());
	}

	/**
	 * A select filter without options is refused, boolean included.
	 */
	public function testASelectThatNamesNoOptionsIsRefused(): void {
		foreach ([FieldDefinition::TYPE_STRING, FieldDefinition::TYPE_BOOL] as $type) {
			try {
				new FieldDefinition(
					name: 'roomType',
					label: 'Room type',
					type: $type,
					column: 'room_type',
					filter: FieldDefinition::FILTER_SELECT,
				);
				$this->fail('A ' . $type . ' select without options was accepted');
			} catch (\LogicException) {
				$this->addToAssertionCount(1);
			}
		}
	}

	/**
	 * A form select must name its options.
	 */
	public function testAFormSelectMustNameItsOptions(): void {
		$this->expectException(\LogicException::class);

		new FieldDefinition(name: 'size', label: 'Size', form: FieldDefinition::FORM_SELECT, column: 'size');
	}

	/**
	 * A relation must join at least one table.
	 */
	public function testARelationJoinsAtLeastOneTable(): void {
		$this->assertSame('id', (new Relation(foreignKey: 'story_id', table: 'calresources_stories'))->primaryKey);

		$this->expectException(\LogicException::class);
		new FieldDefinition(name: 'buildingId', label: 'Building', type: FieldDefinition::TYPE_INT, relation: new RelationDefinition(through: [], column: 'building_id'));
	}

	/**
	 * A relation does not dictate the form control.
	 */
	public function testARelationNeedsNoParticularFormControl(): void {
		$filterOnly = new FieldDefinition(name: 'buildingId', label: 'Building', type: FieldDefinition::TYPE_INT, filter: FieldDefinition::FILTER_NUMBER, relation: $this->roomToBuilding());
		$this->assertNull($filterOnly->form);

		$typedId = new FieldDefinition(name: 'buildingId', label: 'Building', type: FieldDefinition::TYPE_INT, form: FieldDefinition::FORM_NUMBER, relation: $this->roomToBuilding());
		$this->assertSame(FieldDefinition::FORM_NUMBER, $typedId->form);
	}

	/**
	 * A field with its own column cannot also declare a relation.
	 */
	public function testAFieldWithAColumnDeclaresNoRelation(): void {
		$this->expectException(\LogicException::class);

		new FieldDefinition(name: 'buildingId', label: 'Building', type: FieldDefinition::TYPE_INT, column: 'building_id', relation: $this->roomToBuilding());
	}

	/**
	 * Only a field with a column can be in the table.
	 */
	public function testOnlyAFieldWithAColumnCanBeInTheTable(): void {
		$this->expectException(\LogicException::class);

		new FieldDefinition(name: 'buildingId', label: 'Building', type: FieldDefinition::TYPE_INT, inTable: true, relation: $this->roomToBuilding());
	}

	/**
	 * Binding follows the data type, not the form control.
	 */
	public function testASelectBindsAsItsValuesDo(): void {
		$storyId = $this->entity('rooms')->field('storyId');
		$this->assertSame(IQueryBuilder::PARAM_INT, $storyId->paramType());

		$size = new FieldDefinition(
			name: 'size',
			label: 'Size',
			form: FieldDefinition::FORM_SELECT,
			column: 'size',
			filter: FieldDefinition::FILTER_SELECT,
			options: ['big', 'medium', 'small'],
		);
		$this->assertSame(IQueryBuilder::PARAM_STR, $size->paramType());
	}

	/**
	 * A persisted field targets its column; a virtual one its relation's column and hops.
	 */
	public function testAFieldTargetsItsColumnOrItsRelation(): void {
		$this->assertSame(['capacity', []], $this->entity('rooms')->field('capacity')->target());

		[$column, $joins] = $this->entity('rooms')->field('buildingId')->target();
		$this->assertSame('building_id', $column);
		$this->assertCount(1, $joins);
		$this->assertSame('calresources_stories', $joins[0]->table);
	}

	/**
	 * A field is in the form exactly when it names a form control.
	 */
	public function testAFieldIsInTheFormWhenItNamesAControl(): void {
		$this->assertFalse($this->entity('rooms')->field('id')->isInForm());
		$this->assertNull($this->entity('rooms')->field('id')->form);
		$this->assertTrue($this->entity('rooms')->field('capacity')->isInForm());
		$this->assertSame(FieldDefinition::FORM_NUMBER, $this->entity('rooms')->field('capacity')->form);
	}

	public function testABooleanFilterOnANonBooleanIsRefused(): void {
		$this->expectException(\LogicException::class);

		new FieldDefinition(
			name: 'roomType',
			label: 'Room type',
			column: 'room_type',
			filter: FieldDefinition::FILTER_BOOL,
		);
	}

	/**
	 * No descriptor names a referenced entity.
	 */
	public function testFrontendDefinitionNamesNoReferencedEntity(): void {
		foreach ($this->frontendEntities() as $entity) {
			foreach (['columns', 'fields', 'filters'] as $view) {
				foreach ($entity[$view] as $descriptor) {
					$this->assertArrayNotHasKey('source', $descriptor);
				}
			}
		}
	}

	public function testFrontendDefinitionKeepsTableAndColumnNamesOnTheServer(): void {
		$encoded = json_encode($this->frontendEntities());

		$this->assertStringNotContainsString('calresources_', $encoded);
		$this->assertStringNotContainsString('display_name', $encoded);
	}

	/**
	 * The room's building select is transient and cascades to the story.
	 */
	public function testFrontendDefinitionMarksTheRoomBuildingFieldTransient(): void {
		$field = $this->descriptor($this->frontend('rooms')['fields'], 'buildingId');

		$this->assertTrue($field['transient']);
		$this->assertSame('storyId', $field['cascade']);
		$this->assertSame('select', $field['type']);
	}

	/**
	 * Every view travels as a list in display order.
	 */
	public function testFrontendDefinitionSendsEveryViewAsAList(): void {
		foreach ($this->frontendEntities() as $entity) {
			$this->assertTrue(array_is_list($entity['fields']));
			$this->assertTrue(array_is_list($entity['columns']));
			$this->assertTrue(array_is_list($entity['filters']));
		}

		$this->assertSame(
			['displayName', 'address', 'description', 'isWheelchairAccessible'],
			array_column($this->frontend('buildings')['fields'], 'key')
		);
	}

	/**
	 * Each form field names the control its declaration implies.
	 */
	public function testFrontendDefinitionNamesEachFormControl(): void {
		$entities = $this->frontendEntities();

		$this->assertSame('select', $this->descriptor($entities['rooms']['fields'], 'storyId')['type']);
		$this->assertSame('combobox', $this->descriptor($entities['rooms']['fields'], 'roomType')['type']);
		$this->assertSame('user', $this->descriptor($entities['rooms']['fields'], 'contactPersonUserId')['type']);
		$this->assertSame('textarea', $this->descriptor($entities['buildings']['fields'], 'description')['type']);
		$this->assertSame('text', $this->descriptor($entities['buildings']['fields'], 'address')['type']);
		$this->assertSame('email', $this->descriptor($entities['rooms']['fields'], 'email')['type']);
		$this->assertSame('number', $this->descriptor($entities['rooms']['fields'], 'capacity')['type']);
		$this->assertSame('bool', $this->descriptor($entities['rooms']['fields'], 'hasTv')['type']);
	}

	/**
	 * A textarea field filters like plain text.
	 */
	public function testATextareaFieldFiltersLikeText(): void {
		$description = $this->entity('buildings')->field('description');

		$this->assertSame(Criterion::OP_LIKE, $description->operator());
		$this->assertSame('text', $this->descriptor($this->frontend('buildings')['filters'], 'description')['type']);
		$this->assertSame('textarea', $this->descriptor($this->frontend('buildings')['fields'], 'description')['type']);
	}

	public function testFrontendDefinitionPassesNumericBoundsToTheClientValidator(): void {
		$field = $this->descriptor($this->frontend('rooms')['fields'], 'capacity');

		$this->assertSame(['from' => 0], $field['range']);
	}

	/**
	 * Vehicles omit the inherited `resourceType`, which is always 'vehicle'.
	 */
	public function testVehiclesDoNotOfferTheInheritedResourceType(): void {
		$vehicles = $this->frontend('vehicles');

		$this->assertNotContains('resourceType', array_column($vehicles['filters'], 'key'));
		$this->assertNotContains('resourceType', array_column($vehicles['columns'], 'key'));
	}

	/**
	 * A type field can be looked up by name for the `/types` endpoints.
	 *
	 * @dataProvider typeFieldData
	 *
	 * @param string $entity the schema key
	 * @param string $name the type field's name
	 * @param string $column the column it is collected from
	 */
	public function testAFieldCanBeLookedUpByName(string $entity, string $name, string $column): void {
		$field = $this->entity($entity)->field($name);

		$this->assertSame($name, $field->name);
		$this->assertSame($column, $field->column);
	}

	/**
	 * The three fields the `/types` endpoints are built on.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public static function typeFieldData(): array {
		return [
			'rooms' => ['rooms', 'roomType', 'room_type'],
			'resources' => ['resources', 'resourceType', 'resource_type'],
			'vehicles' => ['vehicles', 'vehicleType', 'vehicle_type'],
		];
	}

	/**
	 * A type field must have a column to collect its options from.
	 *
	 * @dataProvider typeFieldData
	 *
	 * @param string $entity the schema key
	 * @param string $name the type field's name
	 */
	public function testEveryTypeFieldHasAColumnToCollectFrom(string $entity, string $name): void {
		$this->assertNotNull(
			$this->entity($entity)->field($name)->column,
			$entity . '.' . $name . ' has no column'
		);
	}

	/**
	 * A type field is also filterable, so the filter panel and `/types` agree.
	 *
	 * @dataProvider typeFieldData
	 *
	 * @param string $entity the schema key
	 * @param string $name the type field's name
	 */
	public function testEveryTypeFieldIsAlsoAFilterableOne(string $entity, string $name): void {
		$definition = $this->entity($entity);

		$this->assertContains(
			$name,
			array_map(static fn ($filterable) => $filterable->name, $definition->filterableFields()),
			$entity . '.' . $name . ' is not filterable'
		);
	}

	/**
	 * Asking for a field the entity does not declare is refused.
	 */
	public function testAskingForAFieldAnEntityDoesNotDeclareIsRefused(): void {
		$this->expectException(\LogicException::class);
		$this->entity('buildings')->field('buildingType');
	}

	/**
	 * One entity's definition, by schema key.
	 */
	private function entity(string $key): EntityDefinition {
		return $this->definitions[$key];
	}

	/**
	 * One entity as the admin UI receives it.
	 *
	 * @return array<string, mixed>
	 */
	private function frontend(string $key): array {
		return $this->entity($key)->frontendDefinition();
	}

	/**
	 * Every entity as the admin UI receives it, keyed by schema key.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function frontendEntities(): array {
		return array_map(static fn (EntityDefinition $definition) => $definition->frontendDefinition(), $this->definitions);
	}

	/**
	 * Find one criterion by the column it compares.
	 *
	 * @param Criterion[] $criteria
	 */
	private function criterion(array $criteria, string $column): Criterion {
		foreach ($criteria as $criterion) {
			if ($criterion->column === $column) {
				return $criterion;
			}
		}

		$this->fail('No criterion on ' . $column);
	}

	/**
	 * Find one column/field/filter descriptor by its key.
	 *
	 * @param array<int, array<string, mixed>> $descriptors
	 * @return array<string, mixed>
	 */
	private function descriptor(array $descriptors, string $key): array {
		foreach ($descriptors as $descriptor) {
			if ($descriptor['key'] === $key) {
				return $descriptor;
			}
		}

		$this->fail('No descriptor for ' . $key);
	}

	/**
	 * The room → story → building id path, as a relation.
	 */
	private function roomToBuilding(): RelationDefinition {
		return new RelationDefinition(
			through: [new Relation(foreignKey: 'story_id', table: 'calresources_stories')],
			column: 'building_id',
		);
	}
}
