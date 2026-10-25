<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Controller;

use OCA\CalendarResourceManagement\Controller\PersonalController;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCA\CalendarResourceManagement\Service\BookingCalendarSettings;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PersonalControllerTest extends TestCase {
	private BookingCalendarShareService&MockObject $shareService;
	private BookingCalendarSettings&MockObject $settings;
	private BuildingMapper&MockObject $buildingMapper;
	private StoryMapper&MockObject $storyMapper;
	private PersonalController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->shareService = $this->createMock(BookingCalendarShareService::class);
		$this->settings = $this->createMock(BookingCalendarSettings::class);
		$this->buildingMapper = $this->createMock(BuildingMapper::class);
		$this->storyMapper = $this->createMock(StoryMapper::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$this->controller = new PersonalController(
			'calendar_resource_management',
			$this->createMock(IRequest::class),
			$this->shareService,
			$this->settings,
			$this->buildingMapper,
			$this->storyMapper,
			$userSession,
			$this->createMock(LoggerInterface::class),
		);
	}

	private function room(): RoomModel {
		$room = new RoomModel();
		$room->setId(9);
		$room->setUid('room-9');
		$room->setDisplayName('Meeting room');
		$room->setStoryId(2);
		$room->setCapacity(0);
		return $room;
	}

	public function testNonViewersAreRejected(): void {
		$this->shareService->method('isViewer')->with('alice')->willReturn(false);
		$this->shareService->expects(self::never())->method('subscribe');

		self::assertSame(Http::STATUS_FORBIDDEN, $this->controller->getBookingCalendars()->getStatus());
		self::assertSame(Http::STATUS_FORBIDDEN, $this->controller->setSubscribed('room', 9, true)->getStatus());
		self::assertSame(Http::STATUS_FORBIDDEN, $this->controller->setPreferences(true)->getStatus());
	}

	public function testListsTheCalendarsTheUserMaySee(): void {
		$building = new BuildingModel();
		$building->setId(1);
		$building->setDisplayName('Berlin office');
		$story = new StoryModel();
		$story->setId(2);
		$story->setBuildingId(1);
		$story->setDisplayName('Second floor');
		$hidden = $this->room();
		$hidden->setId(10);

		$this->shareService->method('isViewer')->willReturn(true);
		$this->buildingMapper->method('findAll')->willReturn([$building]);
		$this->storyMapper->method('findAll')->willReturn([$story]);
		$this->shareService->method('listEntities')->willReturn([['room', $this->room()], ['room', $hidden]]);
		$this->shareService->method('canView')
			->willReturnCallback(static fn (string $userId, string $type, RoomModel $room): bool => $room->getId() === 9);
		$this->shareService->method('principalUri')->willReturn('principals/calendar-rooms/calendar_resource_management-room-9');
		$this->shareService->method('getVisiblePrincipals')
			->willReturn(['principals/calendar-rooms/calendar_resource_management-room-9' => true]);
		$this->shareService->method('isDefaultFor')->willReturn(false);
		$this->settings->method('getAutoSubscribeNew')->willReturn(true);

		$response = $this->controller->getBookingCalendars();

		self::assertSame([
			'autoSubscribeNew' => true,
			'calendars' => [[
				'type' => 'room',
				'id' => 9,
				'name' => 'Meeting room',
				'buildingName' => 'Berlin office',
				'storyName' => 'Second floor',
				// The commands store 0 when no capacity was given
				'capacity' => null,
				'subscribed' => true,
				'isDefault' => false,
			]],
		], $response->getData());
	}

	public function testSubscribingToAnInvisibleCalendarLooksLikeAMissingOne(): void {
		$this->shareService->method('isViewer')->willReturn(true);
		$this->shareService->method('findEntity')->willReturn($this->room());
		$this->shareService->method('canView')->willReturn(false);
		$this->shareService->expects(self::never())->method('subscribe');

		self::assertSame(Http::STATUS_NOT_FOUND, $this->controller->setSubscribed('room', 9, true)->getStatus());
	}

	public function testSubscribingToAnUnknownEntityIsNotFound(): void {
		$this->shareService->method('isViewer')->willReturn(true);
		$this->shareService->method('findEntity')->willThrowException(new DoesNotExistException('nope'));

		self::assertSame(Http::STATUS_NOT_FOUND, $this->controller->setSubscribed('room', 404, true)->getStatus());
	}

	public function testSubscribingBeforeTheServerCreatedTheCalendarIsAConflict(): void {
		$this->shareService->method('isViewer')->willReturn(true);
		$this->shareService->method('findEntity')->willReturn($this->room());
		$this->shareService->method('canView')->willReturn(true);
		$this->shareService->method('subscribe')->willReturn(false);

		self::assertSame(Http::STATUS_CONFLICT, $this->controller->setSubscribed('room', 9, true)->getStatus());
	}

	public function testUnsubscribe(): void {
		$room = $this->room();
		$this->shareService->method('isViewer')->willReturn(true);
		$this->shareService->method('findEntity')->willReturn($room);
		$this->shareService->method('canView')->willReturn(true);
		$this->shareService->expects(self::once())->method('unsubscribe')->with('alice', 'room', $room);

		$response = $this->controller->setSubscribed('room', 9, false);

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame(['subscribed' => false], $response->getData());
	}

	public function testSavesTheAutoSubscribePreference(): void {
		$this->shareService->method('isViewer')->willReturn(true);
		$this->settings->expects(self::once())->method('setAutoSubscribeNew')->with('alice', true);

		self::assertSame(['autoSubscribeNew' => true], $this->controller->setPreferences(true)->getData());
	}
}
