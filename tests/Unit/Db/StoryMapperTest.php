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

use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;

class StoryMapperTest extends TestCase {

	/** @var StoryMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_stories')->execute();

		$this->mapper = new StoryMapper(self::$realDatabase);

		$stories = $this->getSampleStories();
		array_map(function ($story): void {
			$this->mapper->insert($story);
		}, $stories);
	}

	public function testFind(): void {
		$allStories = $this->mapper->findAllByBuilding(0);

		$story0 = $this->mapper->find($allStories[0]->getId());
		$this->assertEquals($allStories[0]->getDisplayName(), $story0->getDisplayName());

		$story1 = $this->mapper->find($allStories[1]->getId());
		$this->assertEquals($allStories[1]->getDisplayName(), $story1->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find(-1);
	}

	public function testFindAllByBuilding(): void {
		$allStories = $this->mapper->findAllByBuilding(0);

		$this->assertCount(5, $allStories);

		$this->assertEquals('Floor 1', $allStories[0]->getDisplayName());
		$this->assertEquals(0, $allStories[0]->getBuildingId());
		$this->assertEquals('Floor 2', $allStories[1]->getDisplayName());
		$this->assertEquals(0, $allStories[1]->getBuildingId());
		$this->assertEquals('Floor 3', $allStories[2]->getDisplayName());
		$this->assertEquals(0, $allStories[2]->getBuildingId());
		$this->assertEquals('Floor 4', $allStories[3]->getDisplayName());
		$this->assertEquals(0, $allStories[3]->getBuildingId());
		$this->assertEquals('Ground-floor', $allStories[4]->getDisplayName());
		$this->assertEquals(0, $allStories[4]->getBuildingId());
	}

	public function testFindAllByBuildings(): void {
		$allStories = $this->mapper->findAllByBuildings([0, 2]);

		$this->assertCount(6, $allStories);

		$this->assertEquals('Floor 1', $allStories[0]->getDisplayName());
		$this->assertEquals(0, $allStories[0]->getBuildingId());
		$this->assertEquals('Floor 2', $allStories[1]->getDisplayName());
		$this->assertEquals(0, $allStories[1]->getBuildingId());
		$this->assertEquals('Floor 3', $allStories[2]->getDisplayName());
		$this->assertEquals(0, $allStories[2]->getBuildingId());
		$this->assertEquals('Floor 4', $allStories[3]->getDisplayName());
		$this->assertEquals(0, $allStories[3]->getBuildingId());
		$this->assertEquals('Ground-floor', $allStories[4]->getDisplayName());
		$this->assertEquals(0, $allStories[4]->getBuildingId());
		$this->assertEquals('Ground-floor', $allStories[5]->getDisplayName());
		$this->assertEquals(2, $allStories[5]->getBuildingId());
	}

	public function deleteAllByBuildingId(): void {
		$allStories = $this->mapper->findAllByBuildings([0, 2]);
		$this->assertCount(6, $allStories);

		$this->mapper->deleteAllByBuildingId(0);

		$allStories = $this->mapper->findAllByBuildings([0, 2]);
		$this->assertCount(1, $allStories);
	}

	protected function getSampleStories(): array {
		return [
			StoryModel::fromParams([
				'buildingId' => 0,
				'displayName' => 'Ground-floor',
			]),
			StoryModel::fromParams([
				'buildingId' => 0,
				'displayName' => 'Floor 1',
			]),
			StoryModel::fromParams([
				'buildingId' => 0,
				'displayName' => 'Floor 2',
			]),
			StoryModel::fromParams([
				'buildingId' => 0,
				'displayName' => 'Floor 3',
			]),
			StoryModel::fromParams([
				'buildingId' => 0,
				'displayName' => 'Floor 4',
			]),
			StoryModel::fromParams([
				'buildingId' => 1,
				'displayName' => 'Ground-floor',
			]),
			StoryModel::fromParams([
				'buildingId' => 1,
				'displayName' => 'Floor 1',
			]),
			StoryModel::fromParams([
				'buildingId' => 2,
				'displayName' => 'Ground-floor',
			]),
		];
	}
}
