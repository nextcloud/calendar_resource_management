<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Listener;

use OCA\CalendarResourceManagement\BackgroundJob\SyncAllBookingSharesJob;
use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Listener\GroupDeletedListener;
use OCA\CalendarResourceManagement\Service\BookingCalendarSettings;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\IGroup;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class GroupDeletedListenerTest extends TestCase {
	private RestrictionMapper&MockObject $mapper;
	private DefaultShareMapper&MockObject $defaultShareMapper;
	private BookingCalendarSettings&MockObject $settings;
	private BookingCalendarShareService&MockObject $shareService;
	private IJobList&MockObject $jobList;
	private GroupDeletedListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(RestrictionMapper::class);
		$this->defaultShareMapper = $this->createMock(DefaultShareMapper::class);
		$this->settings = $this->createMock(BookingCalendarSettings::class);
		$this->shareService = $this->createMock(BookingCalendarShareService::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->listener = new GroupDeletedListener(
			$this->mapper,
			$this->defaultShareMapper,
			$this->settings,
			$this->shareService,
			$this->jobList,
			$this->createMock(LoggerInterface::class),
		);
	}

	private function group(string $groupId, array $memberIds = []): IGroup&MockObject {
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn($groupId);
		$group->method('getUsers')->willReturn(array_map(function (string $userId): IUser {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($userId);
			return $user;
		}, $memberIds));
		return $group;
	}

	public function testHandleIgnoresOtherEvents(): void {
		$this->mapper->expects(self::never())
			->method('deleteAllRestrictionsByGroupId');

		$this->listener->handle(new Event());
	}

	public function testHandleDeletesRestrictionsForDeletedGroup(): void {
		$this->mapper->expects(self::once())
			->method('deleteAllRestrictionsByGroupId')
			->with('group-to-delete');

		$this->listener->handle(new GroupDeletedEvent($this->group('group-to-delete')));
	}

	public function testHandleRemovesTheDeletedGroupFromDefaultsAndViewers(): void {
		$this->defaultShareMapper->expects(self::once())
			->method('deleteAllByGroupId')
			->with('staff');
		$this->settings->method('getViewerGroups')->willReturn(['admin', 'staff']);
		$this->settings->expects(self::once())
			->method('setViewerGroups')
			->with(['admin']);

		$this->listener->handle(new GroupDeletedEvent($this->group('staff')));
	}

	public function testHandleKeepsTheViewerGroupsOfOtherGroups(): void {
		$this->settings->method('getViewerGroups')->willReturn(['staff']);
		$this->settings->expects(self::never())->method('setViewerGroups');

		$this->listener->handle(new GroupDeletedEvent($this->group('other')));
	}

	public function testFormerMembersAreSyncedRightAwayAndAFullSyncIsQueued(): void {
		$this->listener->handle(new BeforeGroupDeletedEvent($this->group('staff', ['alice', 'bob'])));

		$synced = [];
		$this->shareService->method('syncUser')->willReturnCallback(static function (string $userId) use (&$synced): void {
			$synced[] = $userId;
		});
		$this->jobList->expects(self::once())->method('add')->with(SyncAllBookingSharesJob::class);

		// The members are gone from the group once it is deleted
		$this->listener->handle(new GroupDeletedEvent($this->group('staff')));

		self::assertSame(['alice', 'bob'], $synced);
	}
}
