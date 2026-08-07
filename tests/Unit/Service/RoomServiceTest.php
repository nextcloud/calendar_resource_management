<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Service\RoomService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RoomServiceTest extends TestCase {
	private RoomMapper&MockObject $roomMapper;
	private StoryMapper&MockObject $storyMapper;
	private RoomService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->roomMapper = $this->createMock(RoomMapper::class);
		$this->storyMapper = $this->createMock(StoryMapper::class);

		$secureRandom = $this->createMock(ISecureRandom::class);
		$secureRandom->method('generate')->willReturn('deadbeef');

		$this->service = new RoomService(
			$this->roomMapper,
			$this->createMock(RestrictionMapper::class),
			$this->storyMapper,
			$secureRandom,
		);
	}

	public function testCreateRoomRejectsUnknownStory(): void {
		$this->storyMapper->expects(self::once())
			->method('find')
			->with(404)
			->willThrowException(new DoesNotExistException('nope'));
		$this->roomMapper->expects(self::never())->method('insert');

		$this->expectException(DoesNotExistException::class);

		$this->service->createRoom('Meeting room', 404);
	}

	public function testCreateRoomPersistsAllProperties(): void {
		$this->roomMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (RoomModel $room): RoomModel {
				self::assertSame('deadbeef', $room->getUid());
				self::assertSame('Meeting room', $room->getDisplayName());
				self::assertSame('room@example.com', $room->getEmail());
				self::assertSame('meeting-room', $room->getRoomType());
				self::assertSame(2, $room->getStoryId());
				self::assertSame('1.23', $room->getRoomNumber());
				self::assertSame('admin', $room->getContactPersonUserId());
				self::assertSame(12, $room->getCapacity());
				self::assertTrue($room->getHasPhone());
				self::assertTrue($room->getHasVideoConferencing());
				self::assertFalse($room->getHasTv());
				self::assertTrue($room->getIsWheelchairAccessible());

				return $room;
			});

		$this->service->createRoom(
			'Meeting room',
			2,
			'room@example.com',
			'meeting-room',
			'1.23',
			'admin',
			12,
			true,
			true,
			false,
			false,
			false,
			true,
		);
	}

	public function testCreateRoomLeavesCapacityUnsetWhenNotGiven(): void {
		$this->roomMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (RoomModel $room): RoomModel {
				self::assertNull($room->getCapacity());

				return $room;
			});

		$this->service->createRoom('Meeting room', 2);
	}

	public function testDeleteRoomDeletesTheFoundEntity(): void {
		$room = new RoomModel();
		$room->setId(9);
		$this->roomMapper->expects(self::once())->method('find')->with(9)->willReturn($room);
		$this->roomMapper->expects(self::once())->method('delete')->with($room);

		$this->service->deleteRoom(9);
	}
}
