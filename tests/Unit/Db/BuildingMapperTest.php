<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Db;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;

class BuildingMapperTest extends TestCase {
	/** @var BuildingMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_buildings')->executeStatement();

		$this->mapper = new BuildingMapper(self::$realDatabase);

		$buildings = $this->getSampleBuildings();
		array_map(function ($building): void {
			$this->mapper->insert($building);
		}, $buildings);
	}

	public function testFind(): void {
		// Should sort alphabetically
		$allBuildings = $this->mapper->findAll('display_name', true, 1, 1);
		$this->assertEquals('Building 1', $allBuildings[0]->getDisplayName());

		$building = $this->mapper->find($allBuildings[0]->getId());
		$this->assertEquals('Building 1', $building->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find(-1);
	}

	public function testFindAll(): void {
		// Should sort alphabetically
		$allBuildings = $this->mapper->findAll();
		$this->assertCount(5, $allBuildings);
		$this->assertEquals('Another Building 4', $allBuildings[0]->getDisplayName());
		$this->assertEquals('Building 1', $allBuildings[1]->getDisplayName());
		$this->assertEquals('Building 2', $allBuildings[2]->getDisplayName());
		$this->assertEquals('Building 3', $allBuildings[3]->getDisplayName());
		$this->assertEquals('Building 5', $allBuildings[4]->getDisplayName());

		$allBuildings = $this->mapper->findAll('display_name', false);
		$this->assertCount(5, $allBuildings);
		$this->assertEquals('Building 5', $allBuildings[0]->getDisplayName());
		$this->assertEquals('Building 3', $allBuildings[1]->getDisplayName());
		$this->assertEquals('Building 2', $allBuildings[2]->getDisplayName());
		$this->assertEquals('Building 1', $allBuildings[3]->getDisplayName());
		$this->assertEquals('Another Building 4', $allBuildings[4]->getDisplayName());

		$allBuildings = $this->mapper->findAll('display_name', true, 2, 1);
		$this->assertCount(2, $allBuildings);
		$this->assertEquals('Building 1', $allBuildings[0]->getDisplayName());
		$this->assertEquals('Building 2', $allBuildings[1]->getDisplayName());
	}

	public function testSearch(): void {
		$searchResults = $this->mapper->search('Another');
		$this->assertCount(1, $searchResults);
		$this->assertEquals('Another Building 4', $searchResults[0]->getDisplayName());

		$searchResults = $this->mapper->search('Foo');
		$this->assertCount(1, $searchResults);
		$this->assertEquals('Building 2', $searchResults[0]->getDisplayName());

		$searchResults = $this->mapper->search('Headquarters');
		$this->assertCount(1, $searchResults);
		$this->assertEquals('Building 5', $searchResults[0]->getDisplayName());

		$searchResults = $this->mapper->search('City');
		$this->assertCount(1, $searchResults);
		$this->assertEquals('Building 5', $searchResults[0]->getDisplayName());
	}

	protected function getSampleBuildings(): array {
		return [
			BuildingModel::fromParams([
				'displayName' => 'Building 1',
				'description' => 'Small offices',
			]),
			BuildingModel::fromParams([
				'displayName' => 'Building 2',
				'description' => 'Foo',
			]),
			BuildingModel::fromParams([
				'displayName' => 'Building 3',
			]),
			BuildingModel::fromParams([
				'displayName' => 'Another Building 4',
			]),
			BuildingModel::fromParams([
				'displayName' => 'Building 5',
				'description' => 'Headquarters',
				'address' => 'Example Street 123' . PHP_EOL . '12345 Random City',
				'isWheelchairAccessible' => true,
			]),
		];
	}
}
