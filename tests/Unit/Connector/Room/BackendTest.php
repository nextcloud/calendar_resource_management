<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Connector\Room;

use OCA\CalendarResourceManagement\Connector\Room\Backend;
use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class BackendTest extends TestCase {
	/** @var RoomMapper&MockObject */
	private $roomMapper;

	/** @var RestrictionMapper&MockObject */
	private $restrictionMapper;

	/** @var StoryMapper&MockObject */
	private $storyMapper;

	/** @var BuildingMapper&MockObject */
	private $buildingMapper;

	/** @var LoggerInterface&MockObject */
	private $logger;

	private Backend $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->roomMapper = $this->createMock(RoomMapper::class);
		$this->restrictionMapper = $this->createMock(RestrictionMapper::class);
		$this->storyMapper = $this->createMock(StoryMapper::class);
		$this->buildingMapper = $this->createMock(BuildingMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->backend = new Backend(
			'calendar_resource_management',
			$this->roomMapper,
			$this->restrictionMapper,
			$this->storyMapper,
			$this->buildingMapper,
			$this->logger,
		);
	}

	public function testListAllRoomsKeepsPublicAndRestrictedWithGroups(): void {
		$this->roomMapper->expects(self::once())
			->method('findAllVisibleUIDs')
			->with(Constants::ROOM)
			->willReturn(['room-public', 'room-restricted-group']);

		$this->assertSame([
			'room-public',
			'room-restricted-group',
		], $this->backend->listAllRooms());
	}
}
