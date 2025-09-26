<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Db;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;

class RestrictionMapperTest extends TestCase {
	/** @var RestrictionMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_restricts')->executeStatement();

		$this->mapper = new RestrictionMapper(self::$realDatabase);

		$restrictions = $this->getSampleRestrictions();
		array_map(function ($restriction): void {
			$this->mapper->insert($restriction);
		}, $restrictions);
	}

	public function testFind(): void {
		$allRestrictions = $this->mapper->findAllByEntityTypeAndId('type_1', 99);

		$story0 = $this->mapper->find($allRestrictions[0]->getId());
		$this->assertEquals($allRestrictions[0]->getEntityType(), $story0->getEntityType());
		$this->assertEquals($allRestrictions[0]->getEntityId(), $story0->getEntityId());
		$this->assertEquals($allRestrictions[0]->getGroupId(), $story0->getGroupId());

		$story1 = $this->mapper->find($allRestrictions[1]->getId());
		$this->assertEquals($allRestrictions[1]->getEntityType(), $story1->getEntityType());
		$this->assertEquals($allRestrictions[1]->getEntityId(), $story1->getEntityId());
		$this->assertEquals($allRestrictions[1]->getGroupId(), $story1->getGroupId());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find(-1);
	}

	public function testFindAllByEntityTypeAndId(): void {
		$allRestrictions = $this->mapper->findAllByEntityTypeAndId('type_1', 99);

		$this->assertCount(3, $allRestrictions);

		$this->assertEquals('type_1', $allRestrictions[0]->getEntityType());
		$this->assertEquals(99, $allRestrictions[0]->getEntityId());
		$this->assertEquals('group_1', $allRestrictions[0]->getGroupId());
		$this->assertEquals('type_1', $allRestrictions[1]->getEntityType());
		$this->assertEquals(99, $allRestrictions[1]->getEntityId());
		$this->assertEquals('group_2', $allRestrictions[1]->getGroupId());
		$this->assertEquals('type_1', $allRestrictions[2]->getEntityType());
		$this->assertEquals(99, $allRestrictions[2]->getEntityId());
		$this->assertEquals('group_99', $allRestrictions[2]->getGroupId());
	}

	public function testDeleteAllByEntityTypeAndId(): void {
		$allRestrictions = $this->mapper->findAllByEntityTypeAndId('type_1', 99);

		$this->assertCount(3, $allRestrictions);

		$this->assertEquals('type_1', $allRestrictions[0]->getEntityType());
		$this->assertEquals(99, $allRestrictions[0]->getEntityId());
		$this->assertEquals('group_1', $allRestrictions[0]->getGroupId());
		$this->assertEquals('type_1', $allRestrictions[1]->getEntityType());
		$this->assertEquals(99, $allRestrictions[1]->getEntityId());
		$this->assertEquals('group_2', $allRestrictions[1]->getGroupId());
		$this->assertEquals('type_1', $allRestrictions[2]->getEntityType());
		$this->assertEquals(99, $allRestrictions[2]->getEntityId());
		$this->assertEquals('group_99', $allRestrictions[2]->getGroupId());

		$this->mapper->deleteAllByEntityTypeAndId('type_1', 99);

		$allRestrictions = $this->mapper->findAllByEntityTypeAndId('type_1', 99);

		$this->assertCount(0, $allRestrictions);
	}

	public function testDeleteAllRestrictionsByGroupId(): void {
		$allRestrictions = $this->mapper->findAllByEntityTypeAndId('type_1', 99);

		$this->assertCount(3, $allRestrictions);
		$this->assertEquals('type_1', $allRestrictions[0]->getEntityType());
		$this->assertEquals(99, $allRestrictions[0]->getEntityId());
		$this->assertEquals('group_1', $allRestrictions[0]->getGroupId());
		$this->assertEquals('type_1', $allRestrictions[1]->getEntityType());
		$this->assertEquals(99, $allRestrictions[1]->getEntityId());
		$this->assertEquals('group_2', $allRestrictions[1]->getGroupId());
		$this->assertEquals('type_1', $allRestrictions[2]->getEntityType());
		$this->assertEquals(99, $allRestrictions[2]->getEntityId());
		$this->assertEquals('group_99', $allRestrictions[2]->getGroupId());

		$this->mapper->deleteAllRestrictionsByGroupId('group_99');

		$allRestrictions = $this->mapper->findAllByEntityTypeAndId('type_1', 99);

		$this->assertCount(2, $allRestrictions);
		$this->assertEquals('type_1', $allRestrictions[0]->getEntityType());
		$this->assertEquals(99, $allRestrictions[0]->getEntityId());
		$this->assertEquals('group_1', $allRestrictions[0]->getGroupId());
		$this->assertEquals('type_1', $allRestrictions[1]->getEntityType());
		$this->assertEquals(99, $allRestrictions[1]->getEntityId());
		$this->assertEquals('group_2', $allRestrictions[1]->getGroupId());
	}

	protected function getSampleRestrictions(): array {
		return [
			RestrictionModel::fromParams([
				'entityType' => 'type_1',
				'entityId' => 99,
				'groupId' => 'group_1',
			]),
			RestrictionModel::fromParams([
				'entityType' => 'type_1',
				'entityId' => 99,
				'groupId' => 'group_2',
			]),
			RestrictionModel::fromParams([
				'entityType' => 'type_2',
				'entityId' => 123,
				'groupId' => 'group_1',
			]),
			RestrictionModel::fromParams([
				'entityType' => 'type_3',
				'entityId' => 456,
				'groupId' => 'group_1',
			]),
			RestrictionModel::fromParams([
				'entityType' => 'type_1',
				'entityId' => 99,
				'groupId' => 'group_99',
			]),
		];
	}
}
