<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Db;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\Criterion;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Schema\Entities\Rooms;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IL10N;
use Test\TestCase;

class RoomMapperTest extends TestCase {
	/** @var RoomMapper */
	private $mapper;

	private BuildingMapper $buildingMapper;

	private RestrictionMapper $restrictionMapper;

	private Rooms $rooms;

	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$this->rooms = new Rooms($l10n);

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_rooms')->executeStatement();
		$qb->delete('calresources_restricts')->executeStatement();
		$qb->delete('calresources_stories')->executeStatement();
		$qb->delete('calresources_buildings')->executeStatement();

		$this->buildingMapper = new BuildingMapper(self::$realDatabase);
		$buildings = $this->getSampleBuildings();
		array_map(function ($building): void {
			$this->buildingMapper->insert($building);
		}, $buildings);
		$this->insertStory(1, 1, 'Story 1');
		$this->insertStory(3, 1, 'Story 3');
		$this->insertStory(4, 2, 'Story 4');
		$this->insertStory(99, 2, 'Story 99');

		$this->mapper = new RoomMapper(self::$realDatabase);
		$this->restrictionMapper = new RestrictionMapper(self::$realDatabase);

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

	public function testFindAllFilteredWithoutCriteriaReturnsEverything(): void {
		$this->assertCount(10, $this->mapper->findAllFiltered([]));
	}

	public function testFindAllFilteredMatchesTextCaseInsensitively(): void {
		$rooms = $this->mapper->findAllFiltered([
			new Criterion('display_name', Criterion::OP_LIKE, 'rOoM 7', IQueryBuilder::PARAM_STR),
		]);

		$this->assertCount(1, $rooms);
		$this->assertEquals('Room 7', $rooms[0]->getDisplayName());
	}

	public function testFindAllFilteredEscapesLikeWildcards(): void {
		// Escaped, % and _ are literals no room contains.
		$this->assertCount(0, $this->mapper->findAllFiltered([
			new Criterion('display_name', Criterion::OP_LIKE, 'Room%', IQueryBuilder::PARAM_STR),
		]));
		$this->assertCount(0, $this->mapper->findAllFiltered([
			new Criterion('display_name', Criterion::OP_LIKE, 'Room_', IQueryBuilder::PARAM_STR),
		]));
	}

	public function testFindAllFilteredByBooleanFalseAlsoMatchesNull(): void {
		// Only rooms 6 and 7 are accessible; 8 is false, the rest unset.
		$rooms = $this->mapper->findAllFiltered([
			new Criterion('is_wheelchair_accessible', Criterion::OP_EQ, false, IQueryBuilder::PARAM_BOOL),
		]);

		$this->assertCount(8, $rooms);
		$this->assertNotContains('Room 6', array_map(static fn ($room) => $room->getDisplayName(), $rooms));
	}

	public function testFindAllFilteredByBooleanTrue(): void {
		$rooms = $this->mapper->findAllFiltered([
			new Criterion('has_tv', Criterion::OP_EQ, true, IQueryBuilder::PARAM_BOOL),
		]);

		$this->assertCount(1, $rooms);
		$this->assertEquals('Room 7', $rooms[0]->getDisplayName());
	}

	public function testFindAllFilteredByRangeIsInclusive(): void {
		// Room 7 is the only room with a capacity, and it is exactly 50.
		$this->assertCount(1, $this->mapper->findAllFiltered([
			new Criterion('capacity', Criterion::OP_GTE, 50, IQueryBuilder::PARAM_INT),
		]));
		$this->assertCount(1, $this->mapper->findAllFiltered([
			new Criterion('capacity', Criterion::OP_LTE, 50, IQueryBuilder::PARAM_INT),
		]));
		$this->assertCount(0, $this->mapper->findAllFiltered([
			new Criterion('capacity', Criterion::OP_GTE, 51, IQueryBuilder::PARAM_INT),
		]));
	}

	public function testFindAllFilteredCombinesCriteriaWithAnd(): void {
		$rooms = $this->mapper->findAllFiltered([
			new Criterion('room_type', Criterion::OP_EQ, 'room_type_4', IQueryBuilder::PARAM_STR),
			new Criterion('has_tv', Criterion::OP_EQ, true, IQueryBuilder::PARAM_BOOL),
		]);

		// Rooms 6 and 7 are both room_type_4, but only room 7 has a TV.
		$this->assertCount(1, $rooms);
		$this->assertEquals('Room 7', $rooms[0]->getDisplayName());
	}

	/**
	 * Filtering by building joins through the story table.
	 */
	public function testFindAllFilteredByBuildingJoinsTheStoryTable(): void {
		$rooms = $this->mapper->findAllFiltered($this->rooms->criteria(['buildingId' => 1]));

		$this->assertEquals(
			['Room 0', 'Room 1', 'Room 2', 'Room 5', 'Room 6'],
			array_map(static fn ($room) => $room->getDisplayName(), $rooms)
		);
	}

	public function testFindAllFilteredByBuildingAppliesTheOtherCriteriaToo(): void {
		$rooms = $this->mapper->findAllFiltered($this->rooms->criteria([
			'buildingId' => 2,
			'roomType' => 'room_type_5',
		]));

		$this->assertEquals(
			['Room 8', 'Room 9'],
			array_map(static fn ($room) => $room->getDisplayName(), $rooms)
		);
	}

	/**
	 * Both filters need the story table, which must not be joined twice.
	 */
	public function testFindAllFilteredByBuildingAndStorySharesTheJoin(): void {
		$rooms = $this->mapper->findAllFiltered($this->rooms->criteria([
			'buildingId' => 1,
			'storyId' => 1,
		]));

		$this->assertEquals(
			['Room 5', 'Room 6'],
			array_map(static fn ($room) => $room->getDisplayName(), $rooms)
		);
	}

	public function testFindAllFilteredByAnotherBuildingsStoryMatchesNothing(): void {
		// Story 1 belongs to building 1, so this contradicts itself.
		$this->assertCount(0, $this->mapper->findAllFiltered($this->rooms->criteria([
			'buildingId' => 2,
			'storyId' => 1,
		])));
	}

	public function testUpdateRestricted(): void {
		$room = $this->mapper->findByUID('uid0');
		$this->assertFalse($room->isRestricted());

		$room->setRestricted(true);
		$this->mapper->update($room);
		$this->assertTrue($this->mapper->find($room->getId())->isRestricted());

		$room->setRestricted(false);
		$this->mapper->update($room);
		$this->assertFalse($this->mapper->find($room->getId())->isRestricted());
	}

	public function testFindAllVisibleUIDs(): void {
		$restrictedRoomWithGroup = $this->mapper->findByUID('uid1');
		$restrictedRoomWithoutGroup = $this->mapper->findByUID('uid2');

		$restrictedRoomWithGroup->setRestricted(true);
		$this->mapper->update($restrictedRoomWithGroup);
		$restrictedRoomWithoutGroup->setRestricted(true);
		$this->mapper->update($restrictedRoomWithoutGroup);
		$this->restrictionMapper->insert(RestrictionModel::fromParams([
			'entityType' => Constants::ROOM,
			'entityId' => $restrictedRoomWithGroup->getId(),
			'groupId' => 'group-1',
		]));

		$this->assertSame([
			'uid0',
			'uid1',
			'uid3',
			'uid4',
			'uid5',
			'uid6',
			'uid7',
			'uid8',
			'uid9',
		], $this->mapper->findAllVisibleUIDs(Constants::ROOM));
	}

	public function testCountAllFilteredMatchesTheFilteredTotal(): void {
		$this->assertSame(10, $this->mapper->countAllFiltered([]));

		$criteria = [new Criterion('room_type', Criterion::OP_EQ, 'room_type_1', IQueryBuilder::PARAM_STR)];
		$this->assertSame(2, $this->mapper->countAllFiltered($criteria));
		$this->assertSame(
			count($this->mapper->findAllFiltered($criteria)),
			$this->mapper->countAllFiltered($criteria)
		);

		$this->assertSame(0, $this->mapper->countAllFiltered([
			new Criterion('room_type', Criterion::OP_EQ, 'nope', IQueryBuilder::PARAM_STR),
		]));
	}

	/**
	 * The building filter's story join does not inflate the count.
	 */
	public function testCountAllFilteredCountsAJoinedFilterOnce(): void {
		$criteria = $this->rooms->criteria(['buildingId' => 1]);

		$this->assertSame(5, $this->mapper->countAllFiltered($criteria));
		$this->assertSame(
			count($this->mapper->findAllFiltered($criteria)),
			$this->mapper->countAllFiltered($criteria)
		);
	}

	/**
	 * Two filters sharing the story join do not double the count.
	 */
	public function testCountAllFilteredCountsASharedJoinOnce(): void {
		$criteria = $this->rooms->criteria(['buildingId' => 1, 'storyId' => 1]);

		$this->assertSame(2, $this->mapper->countAllFiltered($criteria));
	}

	/**
	 * The count describes the whole filtered set, not the page taken from it.
	 */
	public function testCountAllFilteredIgnoresTheWindow(): void {
		$this->assertCount(4, $this->mapper->findAllFiltered([], 'display_name', true, 4, 0));
		$this->assertSame(10, $this->mapper->countAllFiltered([]));
	}

	public function testFindAllFilteredWindowsTheResultSet(): void {
		$names = static fn (array $rooms) => array_map(
			static fn ($room) => $room->getDisplayName(),
			$rooms
		);

		$this->assertSame(
			['Room 0', 'Room 1', 'Room 2', 'Room 3'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 0))
		);
		$this->assertSame(
			['Room 4', 'Room 5', 'Room 6', 'Room 7'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 4))
		);
		$this->assertSame(
			['Room 8', 'Room 9'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 8))
		);
		$this->assertSame([], $names($this->mapper->findAllFiltered([], 'display_name', true, 4, 12)));
	}

	/**
	 * The window applies to the filtered set, not to the table.
	 */
	public function testFindAllFilteredWindowsWhatTheCriteriaMatched(): void {
		$criteria = $this->rooms->criteria(['buildingId' => 1]);

		$this->assertSame(
			['Room 2', 'Room 5'],
			array_map(
				static fn ($room) => $room->getDisplayName(),
				$this->mapper->findAllFiltered($criteria, 'display_name', true, 2, 2)
			)
		);
	}

	/**
	 * Pages follow one stable order, so no row repeats or is skipped.
	 */
	public function testFindAllFilteredOrdersStablyAcrossPages(): void {
		$expected = array_map(static fn ($room) => $room->getId(), $this->mapper->findAllFiltered([]));

		$paged = [];
		for ($offset = 0; $offset < 10; $offset += 3) {
			foreach ($this->mapper->findAllFiltered([], 'display_name', true, 3, $offset) as $room) {
				$paged[] = $room->getId();
			}
		}

		$this->assertSame($expected, $paged);
		$this->assertSame($paged, array_values(array_unique($paged)));
	}

	/**
	 * Room type options cover the whole table, not just the current page.
	 */
	public function testDistinctValuesCollectsEveryRoomType(): void {
		$this->assertSame(
			['room_type_1', 'room_type_2', 'room_type_3', 'room_type_4', 'room_type_5'],
			$this->mapper->distinctValues('room_type')
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
		];
	}

	private function insertStory(int $id, int $buildingId, string $displayName): void {
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->insert('calresources_stories')
			->values([
				'id' => $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				'building_id' => $qb->createNamedParameter($buildingId, IQueryBuilder::PARAM_INT),
				'display_name' => $qb->createNamedParameter($displayName, IQueryBuilder::PARAM_STR),
			])
			->executeStatement();
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
