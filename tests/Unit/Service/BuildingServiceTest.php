<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Service\BuildingService;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BuildingServiceTest extends TestCase {
	private BuildingMapper&MockObject $buildingMapper;
	private BuildingService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->buildingMapper = $this->createMock(BuildingMapper::class);
		$this->service = new BuildingService($this->buildingMapper, $this->createMock(StoryMapper::class));
	}

	public function testCreateBuildingPersistsNameAndAddress(): void {
		$this->buildingMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (BuildingModel $building): BuildingModel {
				self::assertSame('Headquarters', $building->getDisplayName());
				self::assertSame('Somewhere 1', $building->getAddress());

				return $building;
			});

		$this->service->createBuilding('Headquarters', 'Somewhere 1');
	}

	public function testDeleteBuildingDeletesTheFoundEntity(): void {
		$building = new BuildingModel();
		$building->setId(3);
		$this->buildingMapper->expects(self::once())->method('find')->with(3)->willReturn($building);
		$this->buildingMapper->expects(self::once())->method('delete')->with($building);

		$this->service->deleteBuilding(3);
	}

	public function testUpdateBuildingChangesAllFields(): void {
		$building = new BuildingModel();
		$building->setId(1);
		$this->buildingMapper->method('find')->with(1)->willReturn($building);
		$this->buildingMapper->expects(self::once())->method('update')->willReturnArgument(0);

		$updated = $this->service->updateBuilding(1, 'Head office', 'Main Street 1', 'Since 2020', true);

		self::assertSame('Head office', $updated->getDisplayName());
		self::assertSame('Main Street 1', $updated->getAddress());
		self::assertSame('Since 2020', $updated->getDescription());
		self::assertTrue((bool)$updated->getIsWheelchairAccessible());
	}
}
