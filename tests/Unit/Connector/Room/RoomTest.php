<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Connector\Room;

use OCA\CalendarResourceManagement\Connector\Room\Backend;
use OCA\CalendarResourceManagement\Connector\Room\Room;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCP\Calendar\Room\IRoomMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RoomTest extends TestCase {
	private RoomModel $roomEntity;

	private StoryModel $storyEntity;

	private BuildingModel $buildingEntity;

	private Backend&MockObject $backend;

	private Room $room;

	protected function setUp(): void {
		parent::setUp();

		$this->roomEntity = new RoomModel();
		$this->roomEntity->setDisplayName('Room 1');
		$this->roomEntity->setEmail('room1@example.com');

		$this->storyEntity = new StoryModel();
		$this->storyEntity->setDisplayName('Floor 1');

		$this->buildingEntity = new BuildingModel();
		$this->buildingEntity->setDisplayName('Headquarters');
		$this->buildingEntity->setAddress('Main Street 1');

		$this->backend = $this->createMock(Backend::class);

		$this->room = new Room(
			$this->roomEntity,
			$this->storyEntity,
			$this->buildingEntity,
			[],
			$this->backend,
		);
	}

	public function testGetAllAvailableMetadataKeysIncludesBuildingName(): void {
		$this->assertContains(IRoomMetadata::BUILDING_NAME, $this->room->getAllAvailableMetadataKeys());
	}

	public function testGetMetadataForKeyReturnsBuildingName(): void {
		$this->assertSame('Headquarters', $this->room->getMetadataForKey(IRoomMetadata::BUILDING_NAME));
	}

	public function testHasMetadataForKeyReturnsTrueForBuildingName(): void {
		$this->assertTrue($this->room->hasMetadataForKey(IRoomMetadata::BUILDING_NAME));
	}
}
