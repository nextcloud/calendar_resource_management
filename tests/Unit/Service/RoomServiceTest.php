<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCA\CalendarResourceManagement\Service\RoomService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RoomServiceTest extends TestCase {
	private RoomMapper&MockObject $roomMapper;
	private StoryMapper&MockObject $storyMapper;
	private DefaultShareMapper&MockObject $defaultShareMapper;
	private RestrictionMapper&MockObject $restrictionMapper;
	private RoomService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->roomMapper = $this->createMock(RoomMapper::class);
		$this->storyMapper = $this->createMock(StoryMapper::class);
		$this->defaultShareMapper = $this->createMock(DefaultShareMapper::class);

		$secureRandom = $this->createMock(ISecureRandom::class);
		$secureRandom->method('generate')->willReturn('deadbeef');

		$this->service = new RoomService(
			$this->roomMapper,
			$this->restrictionMapper = $this->createMock(RestrictionMapper::class),
			$this->storyMapper,
			$secureRandom,
			$this->defaultShareMapper,
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

	public function testCreateRoomRejectsAnEmailUsedByAnotherRoom(): void {
		$this->roomMapper->expects(self::once())
			->method('findByEmail')
			->with('room@example.com')
			->willReturn(new RoomModel());
		$this->roomMapper->expects(self::never())->method('insert');

		$this->expectException(EmailAlreadyUsedException::class);

		$this->service->createRoom('Meeting room', 2, 'room@example.com');
	}

	public function testCreateRoomPersistsAllProperties(): void {
		$this->roomMapper->method('findByEmail')
			->willThrowException(new DoesNotExistException('nope'));
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
		$this->roomMapper->method('findByEmail')
			->willThrowException(new DoesNotExistException('nope'));
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
		$this->defaultShareMapper->expects(self::once())->method('deleteAllByEntityTypeAndId')->with('room', 9);

		$this->service->deleteRoom(9);
	}

	private function existingRoom(): RoomModel {
		$room = new RoomModel();
		$room->setId(9);
		$room->setUid('room-9');
		$room->setDisplayName('Old name');
		$room->setEmail('room@example.com');
		$this->roomMapper->method('find')->with(9)->willReturn($room);
		$this->roomMapper->method('update')->willReturnArgument(0);
		return $room;
	}

	public function testUpdateRoomKeepsItsOwnEmailAndSavesRestrictions(): void {
		$room = $this->existingRoom();
		$this->roomMapper->method('findByEmail')->with('room@example.com')->willReturn($room);
		$this->restrictionMapper->expects(self::once())
			->method('setGroupIds')
			->with('room', 9, ['board']);

		$updated = $this->service->updateRoom(9, 'New name', 3, 'room@example.com', 'meeting-room', '1.23', '', null, true, false, false, false, false, false, true, ['board']);

		self::assertSame('New name', $updated->getDisplayName());
		self::assertSame('room-9', $updated->getUid());
		self::assertSame(3, $updated->getStoryId());
		self::assertNull($updated->getCapacity());
		self::assertTrue($updated->getHasPhone());
		self::assertTrue($updated->isRestricted());
	}

	public function testUpdateRoomRejectsAnEmailOfAnotherRoom(): void {
		$this->existingRoom();
		$other = new RoomModel();
		$other->setId(10);
		$this->roomMapper->method('findByEmail')->willReturn($other);
		$this->roomMapper->expects(self::never())->method('update');

		$this->expectException(EmailAlreadyUsedException::class);

		$this->service->updateRoom(9, 'Name', 3, 'taken@example.com', '', '', '', null, false, false, false, false, false, false, false, []);
	}

	public function testUnrestrictingARoomDropsItsRestrictions(): void {
		$this->existingRoom();
		$this->roomMapper->method('findByEmail')->willThrowException(new DoesNotExistException('free'));
		$this->restrictionMapper->expects(self::once())
			->method('setGroupIds')
			->with('room', 9, []);

		$this->service->updateRoom(9, 'Name', 3, 'new@example.com', '', '', '', null, false, false, false, false, false, false, false, ['board']);
	}
}
