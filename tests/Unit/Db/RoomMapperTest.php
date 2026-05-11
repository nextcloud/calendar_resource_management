<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Db;

use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;

class RoomMapperTest extends TestCase {
	/** @var RoomMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_rooms')->executeStatement();

		$this->mapper = new RoomMapper(self::$realDatabase);

		$rooms = $this->getSampleRooms();
		array_map(function ($room): void {
			$this->mapper->insert($room);
		}, $rooms);
	}

	public function testFind(): void {
		$allRooms = $this->mapper->findAll();

		$room0 = $this->mapper->find($allRooms[0]->getId());
		$this->assertEquals($allRooms[0]->getDisplayName(), $room0->getDisplayName());

		$room1 = $this->mapper->find($allRooms[1]->getId());
		$this->assertEquals($allRooms[1]->getDisplayName(), $room1->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find(-1);
	}

	public function testFindByUID(): void {
		$room = $this->mapper->findByUID('uid0');
		$this->assertEquals('Room 0', $room->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->findByUID('uid-non-exist');
	}

	public function testFindAll(): void {
		$roomSet0 = $this->mapper->findAll('display_name', true, 2, 0);

		$this->assertCount(2, $roomSet0);

		$this->assertEquals('Room 0', $roomSet0[0]->getDisplayName());
		$this->assertEquals('Room 1', $roomSet0[1]->getDisplayName());

		$roomSet1 = $this->mapper->findAll('display_name', true, 3, 5);

		$this->assertCount(3, $roomSet1);

		$this->assertEquals('Room 5', $roomSet1[0]->getDisplayName());
		$this->assertEquals('Room 6', $roomSet1[1]->getDisplayName());
		$this->assertEquals('Room 7', $roomSet1[2]->getDisplayName());
	}

	public function testFindAllUIDs(): void {
		$uids = $this->mapper->findAllUIDs();
		$this->assertEquals([
			'uid0',
			'uid1',
			'uid2',
			'uid3',
			'uid4',
			'uid5',
			'uid6',
			'uid7',
			'uid8',
			'uid9',
		], $uids);

		$uids = $this->mapper->findAllUIDs('display_name', true, 3, 5);
		$this->assertEquals([
			'uid5',
			'uid6',
			'uid7',
		], $uids);
	}

	public function testFindAllByRoomType(): void {
		$rooms = $this->mapper->findAllByRoomType('room_type_1');

		$this->assertCount(2, $rooms);

		$this->assertEquals('Room 0', $rooms[0]->getDisplayName());
		$this->assertEquals('Room 1', $rooms[1]->getDisplayName());
	}

	public function testFindAllByStoryId(): void {
		$rooms = $this->mapper->findAllByStoryId(99);

		$this->assertCount(2, $rooms);

		$this->assertEquals('Room 3', $rooms[0]->getDisplayName());
		$this->assertEquals('Room 4', $rooms[1]->getDisplayName());
	}

	public function testFindAllByRoomTypeAndStoryId(): void {
		$rooms = $this->mapper->findAllByRoomTypeAndStoryId('room_type_2', 99);

		$this->assertCount(1, $rooms);

		$this->assertEquals('Room 3', $rooms[0]->getDisplayName());
	}

	protected function getSampleRooms(): array {
		return [
			RoomModel::fromParams([
				'uid' => 'uid0',
				'storyId' => 3,
				'displayName' => 'Room 0',
				'email' => 'room0@example.com',
				'roomType' => 'room_type_1',
				'contactPersonUserId' => 'user_1',
			]),
			RoomModel::fromParams([
				'uid' => 'uid1',
				'storyId' => 3,
				'displayName' => 'Room 1',
				'email' => 'room1@example.com',
				'roomType' => 'room_type_1',
				'contactPersonUserId' => 'user_1',
			]),
			RoomModel::fromParams([
				'uid' => 'uid2',
				'storyId' => 3,
				'displayName' => 'Room 2',
				'email' => 'room2@example.com',
				'roomType' => 'room_type_2',
				'contactPersonUserId' => 'user_1',
			]),
			RoomModel::fromParams([
				'uid' => 'uid3',
				'storyId' => 99,
				'displayName' => 'Room 3',
				'email' => 'room3@example.com',
				'roomType' => 'room_type_2',
				'contactPersonUserId' => 'user_2',
			]),
			RoomModel::fromParams([
				'uid' => 'uid4',
				'storyId' => 99,
				'displayName' => 'Room 4',
				'email' => 'room4@example.com',
				'roomType' => 'room_type_3',
				'contactPersonUserId' => 'user_2',
			]),
			RoomModel::fromParams([
				'uid' => 'uid5',
				'storyId' => 1,
				'displayName' => 'Room 5',
				'email' => 'room5@example.com',
				'roomType' => 'room_type_3',
			]),
			RoomModel::fromParams([
				'uid' => 'uid6',
				'storyId' => 1,
				'displayName' => 'Room 6',
				'email' => 'room6@example.com',
				'roomType' => 'room_type_4',
				'isWheelchairAccessible' => true,
			]),
			RoomModel::fromParams([
				'uid' => 'uid7',
				'storyId' => 4,
				'displayName' => 'Room 7',
				'email' => 'room7@example.com',
				'roomType' => 'room_type_4',
				'capacity' => 50,
				'roomNumber' => '204.1a',
				'hasPhone' => true,
				'hasVideoConferencing' => false,
				'hasTv' => true,
				'hasProjector' => true,
				'hasWhiteboard' => false,
				'isWheelchairAccessible' => true,
			]),
			RoomModel::fromParams([
				'uid' => 'uid8',
				'storyId' => 4,
				'displayName' => 'Room 8',
				'email' => 'room8@example.com',
				'roomType' => 'room_type_5',
				'isWheelchairAccessible' => false,
			]),
			RoomModel::fromParams([
				'uid' => 'uid9',
				'storyId' => 4,
				'displayName' => 'Room 9',
				'email' => 'room9@example.com',
				'roomType' => 'room_type_5',
			]),
		];
	}
}
