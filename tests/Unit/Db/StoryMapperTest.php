<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Db;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\Criterion;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use Test\TestCase;

class StoryMapperTest extends TestCase {
	/** @var StoryMapper */
	private $mapper;

	private BuildingMapper $buildingMapper;

	protected function setUp(): void {
		parent::setUp();

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_rooms')->executeStatement();
		$qb->delete('calresources_stories')->executeStatement();
		$qb->delete('calresources_resources')->executeStatement();
		$qb->delete('calresources_vehicles')->executeStatement();
		$qb->delete('calresources_buildings')->executeStatement();

		$this->buildingMapper = new BuildingMapper(self::$realDatabase);
		$this->mapper = new StoryMapper(self::$realDatabase);

		$buildings = $this->getSampleBuildings();
		array_map(function ($building): void {
			$this->buildingMapper->insert($building);
		}, $buildings);

		$stories = $this->getSampleStories();
		array_map(function ($story): void {
			$this->mapper->insert($story);
		}, $stories);
	}

	public function testFind(): void {
		$allStories = $this->mapper->findAllByBuilding(1);

		$story0 = $this->mapper->find($allStories[0]->getId());
		$this->assertEquals($allStories[0]->getDisplayName(), $story0->getDisplayName());

		$story1 = $this->mapper->find($allStories[1]->getId());
		$this->assertEquals($allStories[1]->getDisplayName(), $story1->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find(-1);
	}

	public function testFindAllByBuilding(): void {
		$allStories = $this->mapper->findAllByBuilding(1);

		$this->assertCount(5, $allStories);

		$this->assertEquals('Floor 1', $allStories[0]->getDisplayName());
		$this->assertEquals(1, $allStories[0]->getBuildingId());
		$this->assertEquals('Floor 2', $allStories[1]->getDisplayName());
		$this->assertEquals(1, $allStories[1]->getBuildingId());
		$this->assertEquals('Floor 3', $allStories[2]->getDisplayName());
		$this->assertEquals(1, $allStories[2]->getBuildingId());
		$this->assertEquals('Floor 4', $allStories[3]->getDisplayName());
		$this->assertEquals(1, $allStories[3]->getBuildingId());
		$this->assertEquals('Ground-floor', $allStories[4]->getDisplayName());
		$this->assertEquals(1, $allStories[4]->getBuildingId());
	}

	/**
	 * StoryMapper gets the filter engine from the trait, not AMapper.
	 */
	public function testFindAllFilteredByBuildingId(): void {
		$stories = $this->mapper->findAllFiltered([
			new Criterion('building_id', Criterion::OP_EQ, 1, IQueryBuilder::PARAM_INT),
		]);

		$this->assertEquals(
			array_map(static fn ($story) => $story->getDisplayName(), $this->mapper->findAllByBuilding(1)),
			array_map(static fn ($story) => $story->getDisplayName(), $stories)
		);
	}

	public function testFindAllFilteredMatchesTextCaseInsensitively(): void {
		$stories = $this->mapper->findAllFiltered([
			new Criterion('display_name', Criterion::OP_LIKE, 'GROUND', IQueryBuilder::PARAM_STR),
		]);

		$this->assertNotEmpty($stories);
		foreach ($stories as $story) {
			$this->assertStringContainsStringIgnoringCase('ground', $story->getDisplayName());
		}
	}

	public function testFindAllByBuildings(): void {
		$allStories = $this->mapper->findAllByBuildings([1, 2]);

		$this->assertCount(6, $allStories);

		$this->assertEquals('Floor 1', $allStories[0]->getDisplayName());
		$this->assertEquals(1, $allStories[0]->getBuildingId());
		$this->assertEquals('Floor 2', $allStories[1]->getDisplayName());
		$this->assertEquals(1, $allStories[1]->getBuildingId());
		$this->assertEquals('Floor 3', $allStories[2]->getDisplayName());
		$this->assertEquals(1, $allStories[2]->getBuildingId());
		$this->assertEquals('Floor 4', $allStories[3]->getDisplayName());
		$this->assertEquals(1, $allStories[3]->getBuildingId());
		$this->assertEquals('Ground-floor', $allStories[4]->getDisplayName());
		$this->assertEquals(1, $allStories[4]->getBuildingId());
		$this->assertEquals('Ground-floor', $allStories[5]->getDisplayName());
		$this->assertEquals(2, $allStories[5]->getBuildingId());
	}

	public function deleteAllByBuildingId(): void {
		$allStories = $this->mapper->findAllByBuildings([1, 2]);
		$this->assertCount(6, $allStories);

		$this->mapper->deleteAllByBuildingId(1);

		$allStories = $this->mapper->findAllByBuildings([1, 2]);
		$this->assertCount(1, $allStories);
	}

	public function testCountAllFilteredMatchesTheFilteredTotal(): void {
		$this->assertSame(8, $this->mapper->countAllFiltered([]));

		$criteria = [new Criterion('building_id', Criterion::OP_EQ, 1, IQueryBuilder::PARAM_INT)];
		$this->assertSame(5, $this->mapper->countAllFiltered($criteria));
		$this->assertSame(
			count($this->mapper->findAllFiltered($criteria)),
			$this->mapper->countAllFiltered($criteria)
		);

		$this->assertSame(0, $this->mapper->countAllFiltered([
			new Criterion('building_id', Criterion::OP_EQ, 4711, IQueryBuilder::PARAM_INT),
		]));
	}

	/**
	 * The id tiebreaker keeps pages stable across repeated display names.
	 */
	public function testFindAllFilteredOrdersStablyAcrossPages(): void {
		$expected = array_map(
			static fn ($story) => $story->getId(),
			$this->mapper->findAllFiltered([])
		);
		$this->assertCount(8, $expected);

		$paged = [];
		for ($offset = 0; $offset < 8; $offset += 3) {
			foreach ($this->mapper->findAllFiltered([], 'display_name', true, 3, $offset) as $story) {
				$paged[] = $story->getId();
			}
		}

		$this->assertSame($expected, $paged);
		// No row on two pages, and none skipped.
		$this->assertSame($paged, array_values(array_unique($paged)));
	}

	public function testFindAllFilteredWindowsTheResultSet(): void {
		$page = $this->mapper->findAllFiltered([], 'display_name', true, 3, 3);

		$this->assertCount(3, $page);
		$this->assertSame(
			['Floor 3', 'Floor 4', 'Ground-floor'],
			array_map(static fn ($story) => $story->getDisplayName(), $page)
		);
	}

	/**
	 * Repeated story names are offered once.
	 */
	public function testDistinctValuesCollapsesRepeatedValues(): void {
		$this->assertSame(
			['Floor 1', 'Floor 2', 'Floor 3', 'Floor 4', 'Ground-floor'],
			$this->mapper->distinctValues('display_name')
		);
	}

	protected function getSampleBuildings(): array {
		return [
			BuildingModel::fromParams([
				'id' => 1,
				'displayName' => 'Building 1',
			]),
			BuildingModel::fromParams([
				'id' => 2,
				'displayName' => 'Building 2',
			]),
			BuildingModel::fromParams([
				'id' => 3,
				'displayName' => 'Building 3',
			]),
		];
	}

	protected function getSampleStories(): array {
		return [
			StoryModel::fromParams([
				'buildingId' => 3,
				'displayName' => 'Ground-floor',
			]),
			StoryModel::fromParams([
				'buildingId' => 3,
				'displayName' => 'Floor 1',
			]),
			StoryModel::fromParams([
				'buildingId' => 1,
				'displayName' => 'Floor 2',
			]),
			StoryModel::fromParams([
				'buildingId' => 1,
				'displayName' => 'Floor 3',
			]),
			StoryModel::fromParams([
				'buildingId' => 1,
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
