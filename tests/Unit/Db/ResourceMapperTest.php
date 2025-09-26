<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Db;

use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;

class ResourceMapperTest extends TestCase {
	/** @var ResourceMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_resources')->executeStatement();

		$this->mapper = new ResourceMapper(self::$realDatabase);

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
