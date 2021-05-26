<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <georg-nextcloud@ehrke.email>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		$qb->delete('calresources_restricts')->execute();

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
