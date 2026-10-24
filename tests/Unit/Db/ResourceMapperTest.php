<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Db;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\Criterion;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use Test\TestCase;

class ResourceMapperTest extends TestCase {
	/** @var ResourceMapper */
	private $mapper;

	private BuildingMapper $buildingMapper;

	private RestrictionMapper $restrictionMapper;

	protected function setUp(): void {
		parent::setUp();

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_resources')->executeStatement();
		$qb->delete('calresources_restricts')->executeStatement();
		$qb->delete('calresources_buildings')->executeStatement();

		$this->buildingMapper = new BuildingMapper(self::$realDatabase);
		$this->mapper = new ResourceMapper(self::$realDatabase);
		$this->restrictionMapper = new RestrictionMapper(self::$realDatabase);

		$buildings = $this->getSampleBuildings();
		array_map(function ($building): void {
			$this->buildingMapper->insert($building);
		}, $buildings);

		$resources = $this->getSampleResources();
		array_map(function ($resource): void {
			$this->mapper->insert($resource);
		}, $resources);
	}

	public function testFind(): void {
		$allResources = $this->mapper->findAll();

		$resource0 = $this->mapper->find($allResources[0]->getId());
		$this->assertEquals($allResources[0]->getDisplayName(), $resource0->getDisplayName());

		$resource1 = $this->mapper->find($allResources[1]->getId());
		$this->assertEquals($allResources[1]->getDisplayName(), $resource1->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find(-1);
	}

	public function testFindByUID(): void {
		$resource = $this->mapper->findByUID('uid0');
		$this->assertEquals('Resource 0', $resource->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->findByUID('uid-non-exist');
	}

	public function testFindAll(): void {
		$resourceSet0 = $this->mapper->findAll('display_name', true, 2, 0);

		$this->assertCount(2, $resourceSet0);

		$this->assertEquals('Resource 0', $resourceSet0[0]->getDisplayName());
		$this->assertEquals('Resource 1', $resourceSet0[1]->getDisplayName());

		$resourceSet1 = $this->mapper->findAll('display_name', true, 3, 5);

		$this->assertCount(3, $resourceSet1);

		$this->assertEquals('Resource 5', $resourceSet1[0]->getDisplayName());
		$this->assertEquals('Resource 6', $resourceSet1[1]->getDisplayName());
		$this->assertEquals('Resource 7', $resourceSet1[2]->getDisplayName());
	}

	public function testFindAllUIDs(): void {
		$uids = $this->mapper->findAllUIDs();
		$this->assertEquals([
			'uid0',
			'uid1',
			'uid2',
			'uid3',
			'uid4',
			'uid5',
			'uid6',
			'uid7',
			'uid8',
			'uid9',
		], $uids);

		$uids = $this->mapper->findAllUIDs('display_name', true, 3, 5);
		$this->assertEquals([
			'uid5',
			'uid6',
			'uid7',
		], $uids);
	}

	public function testFindAllByBuilding(): void {
		$resourceSet0 = $this->mapper->findAllByBuilding(3, 'display_name', true);

		$this->assertCount(3, $resourceSet0);
		$this->assertEquals('Resource 0', $resourceSet0[0]->getDisplayName());
		$this->assertEquals('Resource 1', $resourceSet0[1]->getDisplayName());
		$this->assertEquals('Resource 2', $resourceSet0[2]->getDisplayName());
	}

	public function testFindAllByResourceType(): void {
		$resourceSet0 = $this->mapper->findAllByResourceType('resource_type_5', 'display_name', true);

		$this->assertCount(2, $resourceSet0);
		$this->assertEquals('Resource 8', $resourceSet0[0]->getDisplayName());
		$this->assertEquals('Resource 9', $resourceSet0[1]->getDisplayName());
	}

	public function testFindAllByBuildingIdAndResourceType(): void {
		$resourceSet0 = $this->mapper->findAllByBuildingAndResourceType(3, 'resource_type_1', 'display_name', true);

		$this->assertCount(2, $resourceSet0);
		$this->assertEquals('Resource 0', $resourceSet0[0]->getDisplayName());
		$this->assertEquals('Resource 1', $resourceSet0[1]->getDisplayName());
	}

	public function testUpdateRestricted(): void {
		$resource = $this->mapper->findByUID('uid0');
		$this->assertFalse($resource->isRestricted());

		$resource->setRestricted(true);
		$this->mapper->update($resource);
		$this->assertTrue($this->mapper->find($resource->getId())->isRestricted());

		$resource->setRestricted(false);
		$this->mapper->update($resource);
		$this->assertFalse($this->mapper->find($resource->getId())->isRestricted());
	}

	public function testFindAllVisibleUIDs(): void {
		$restrictedResourceWithGroup = $this->mapper->findByUID('uid1');
		$restrictedResourceWithoutGroup = $this->mapper->findByUID('uid2');

		$restrictedResourceWithGroup->setRestricted(true);
		$this->mapper->update($restrictedResourceWithGroup);
		$restrictedResourceWithoutGroup->setRestricted(true);
		$this->mapper->update($restrictedResourceWithoutGroup);
		$this->restrictionMapper->insert(RestrictionModel::fromParams([
			'entityType' => Constants::RESOURCE,
			'entityId' => $restrictedResourceWithGroup->getId(),
			'groupId' => 'group-1',
		]));

		$this->assertSame([
			'uid0',
			'uid1',
			'uid3',
			'uid4',
			'uid5',
			'uid6',
			'uid7',
			'uid8',
			'uid9',
		], $this->mapper->findAllVisibleUIDs(Constants::RESOURCE));
	}

	public function testCountAllFilteredMatchesTheFilteredTotal(): void {
		$this->assertSame(10, $this->mapper->countAllFiltered([]));

		$criteria = [new Criterion('resource_type', Criterion::OP_EQ, 'resource_type_5', IQueryBuilder::PARAM_STR)];
		$this->assertSame(2, $this->mapper->countAllFiltered($criteria));
		$this->assertSame(
			count($this->mapper->findAllFiltered($criteria)),
			$this->mapper->countAllFiltered($criteria)
		);

		$this->assertSame(3, $this->mapper->countAllFiltered([
			new Criterion('building_id', Criterion::OP_EQ, 3, IQueryBuilder::PARAM_INT),
		]));
		$this->assertSame(0, $this->mapper->countAllFiltered([
			new Criterion('building_id', Criterion::OP_EQ, 4711, IQueryBuilder::PARAM_INT),
		]));
	}

	/**
	 * The count describes the whole filtered set, not the page taken from it.
	 */
	public function testCountAllFilteredIgnoresTheWindow(): void {
		$this->assertCount(4, $this->mapper->findAllFiltered([], 'display_name', true, 4, 0));
		$this->assertSame(10, $this->mapper->countAllFiltered([]));
	}

	public function testFindAllFilteredWindowsTheResultSet(): void {
		$names = static fn (array $records) => array_map(
			static fn ($record) => $record->getDisplayName(),
			$records
		);

		$this->assertSame(
			['Resource 0', 'Resource 1', 'Resource 2', 'Resource 3'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 0))
		);
		$this->assertSame(
			['Resource 4', 'Resource 5', 'Resource 6', 'Resource 7'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 4))
		);
		$this->assertSame(
			['Resource 8', 'Resource 9'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 8))
		);
		$this->assertSame([], $names($this->mapper->findAllFiltered([], 'display_name', true, 4, 12)));
	}

	/**
	 * Pages follow one stable order, so no row repeats or is skipped.
	 */
	public function testFindAllFilteredOrdersStablyAcrossPages(): void {
		$expected = array_map(static fn ($record) => $record->getId(), $this->mapper->findAllFiltered([]));

		$paged = [];
		for ($offset = 0; $offset < 10; $offset += 3) {
			foreach ($this->mapper->findAllFiltered([], 'display_name', true, 3, $offset) as $record) {
				$paged[] = $record->getId();
			}
		}

		$this->assertSame($expected, $paged);
		$this->assertSame($paged, array_values(array_unique($paged)));
	}

	/**
	 * Type options cover the whole table, not just the current page.
	 */
	public function testDistinctValuesCollectsEveryType(): void {
		$this->assertSame(
			['resource_type_1', 'resource_type_2', 'resource_type_3', 'resource_type_4', 'resource_type_5'],
			$this->mapper->distinctValues('resource_type')
		);
	}

	protected function getSampleBuildings(): array {
		return [
			BuildingModel::fromParams([
				'id' => 1,
				'displayName' => 'Building 1',
			]),
			BuildingModel::fromParams([
				'id' => 3,
				'displayName' => 'Building 3',
			]),
			BuildingModel::fromParams([
				'id' => 4,
				'displayName' => 'Building 4',
			]),
			BuildingModel::fromParams([
				'id' => 99,
				'displayName' => 'Building 99',
			]),
		];
	}

	protected function getSampleResources(): array {
		return [
			ResourceModel::fromParams([
				'uid' => 'uid0',
				'buildingId' => 3,
				'displayName' => 'Resource 0',
				'email' => 'resource0@example.com',
				'resourceType' => 'resource_type_1',
				'contactPersonUserId' => 'user_1',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid1',
				'buildingId' => 3,
				'displayName' => 'Resource 1',
				'email' => 'resource1@example.com',
				'resourceType' => 'resource_type_1',
				'contactPersonUserId' => 'user_1',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid2',
				'buildingId' => 3,
				'displayName' => 'Resource 2',
				'email' => 'resource2@example.com',
				'resourceType' => 'resource_type_2',
				'contactPersonUserId' => 'user_1',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid3',
				'buildingId' => 99,
				'displayName' => 'Resource 3',
				'email' => 'resource3@example.com',
				'resourceType' => 'resource_type_2',
				'contactPersonUserId' => 'user_2',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid4',
				'buildingId' => 99,
				'displayName' => 'Resource 4',
				'email' => 'resource4@example.com',
				'resourceType' => 'resource_type_3',
				'contactPersonUserId' => 'user_2',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid5',
				'buildingId' => 1,
				'displayName' => 'Resource 5',
				'email' => 'resource5@example.com',
				'resourceType' => 'resource_type_3',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid6',
				'buildingId' => 1,
				'displayName' => 'Resource 6',
				'email' => 'resource6@example.com',
				'resourceType' => 'resource_type_4',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid7',
				'buildingId' => 4,
				'displayName' => 'Resource 7',
				'email' => 'resource7@example.com',
				'resourceType' => 'resource_type_4',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid8',
				'buildingId' => 4,
				'displayName' => 'Resource 8',
				'email' => 'resource8@example.com',
				'resourceType' => 'resource_type_5',
			]),
			ResourceModel::fromParams([
				'uid' => 'uid9',
				'buildingId' => 4,
				'displayName' => 'Resource 9',
				'email' => 'resource9@example.com',
				'resourceType' => 'resource_type_5',
			]),
		];
	}
}
