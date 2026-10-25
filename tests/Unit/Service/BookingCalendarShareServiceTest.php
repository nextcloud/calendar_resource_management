<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Db\DefaultShareModel;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCA\CalendarResourceManagement\Service\BookingCalendarSettings;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class BookingCalendarShareServiceTest extends TestCase {
	private const ROOM_PRINCIPAL = 'principals/calendar-rooms/calendar_resource_management-room-1';

	private CalDavBackend&MockObject $calDavBackend;
	private RoomMapper&MockObject $roomMapper;
	private ResourceMapper&MockObject $resourceMapper;
	private VehicleMapper&MockObject $vehicleMapper;
	private RestrictionMapper&MockObject $restrictionMapper;
	private DefaultShareMapper&MockObject $defaultShareMapper;
	private BookingCalendarSettings&MockObject $settings;
	private IUserSession&MockObject $userSession;
	private BookingCalendarShareService $service;

	/** @var array<string, string[]> */
	private array $groupsOfUsers = [
		'alice' => ['staff'],
		'bob' => ['other'],
		'carol' => ['staff', 'management'],
	];

	protected function setUp(): void {
		parent::setUp();

		$this->calDavBackend = $this->createMock(CalDavBackend::class);
		$this->roomMapper = $this->createMock(RoomMapper::class);
		$this->resourceMapper = $this->createMock(ResourceMapper::class);
		$this->vehicleMapper = $this->createMock(VehicleMapper::class);
		$this->restrictionMapper = $this->createMock(RestrictionMapper::class);
		$this->defaultShareMapper = $this->createMock(DefaultShareMapper::class);
		$this->settings = $this->createMock(BookingCalendarSettings::class);
		$this->settings->method('getViewerGroups')->willReturn(['staff']);

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->willReturnCallback(function (string $userId): ?IUser {
			if (!isset($this->groupsOfUsers[$userId])) {
				return null;
			}
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($userId);
			return $user;
		});
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')
			->willReturnCallback(fn (IUser $user): array => $this->groupsOfUsers[$user->getUID()]);

		$l10nFactory = $this->createMock(IL10NFactory::class);
		$l10nFactory->method('get')->willReturn($this->createMock(IL10N::class));

		$this->service = new BookingCalendarShareService(
			$this->calDavBackend,
			$this->roomMapper,
			$this->resourceMapper,
			$this->vehicleMapper,
			$this->restrictionMapper,
			$this->defaultShareMapper,
			$this->settings,
			$userManager,
			$groupManager,
			$this->userSession = $this->createMock(IUserSession::class),
			$this->createMock(IShareManager::class),
			$l10nFactory,
			$this->createMock(IConfig::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	private function room(bool $restricted = false): RoomModel {
		$room = new RoomModel();
		$room->setId(1);
		$room->setUid('room-1');
		$room->setDisplayName('Meeting room');
		$room->setRestricted($restricted);
		return $room;
	}

	private function withBookingCalendar(string $displayName = 'Meeting room'): void {
		$this->calDavBackend->method('getCalendarByUri')
			->with(self::ROOM_PRINCIPAL, 'calendar')
			->willReturn([
				'id' => 42,
				'uri' => 'calendar',
				'principaluri' => self::ROOM_PRINCIPAL,
				'{DAV:}displayname' => $displayName,
			]);
	}

	/**
	 * @param string[] $principals
	 */
	private function withShares(array $principals): void {
		$this->calDavBackend->method('getShares')
			->with(42)
			->willReturn(array_map(static fn (string $principal): array => [
				'href' => 'principal:' . $principal,
				'commonName' => '',
				'status' => 1,
				'readOnly' => true,
			], $principals));
	}

	private function restrictTo(string $groupId): void {
		$restriction = new RestrictionModel();
		$restriction->setEntityType('room');
		$restriction->setEntityId(1);
		$restriction->setGroupId($groupId);
		$this->restrictionMapper->method('findAll')->willReturn([$restriction]);
	}

	/**
	 * @param string[] $groupIds
	 */
	private function withDefaultGroups(array $groupIds): void {
		$this->defaultShareMapper->method('findAll')->willReturn(array_map(static function (string $groupId): DefaultShareModel {
			$defaultShare = new DefaultShareModel();
			$defaultShare->setEntityType('room');
			$defaultShare->setEntityId(1);
			$defaultShare->setGroupId($groupId);
			return $defaultShare;
		}, $groupIds));
	}

	public function testPrincipalUriMatchesTheServerFormat(): void {
		self::assertSame(self::ROOM_PRINCIPAL, $this->service->principalUri('room', 'room-1'));
		self::assertSame('principals/calendar-resources/calendar_resource_management-car-1', $this->service->principalUri('vehicle', 'car-1'));
		self::assertSame('principals/calendar-resources/calendar_resource_management-beamer', $this->service->principalUri('resource', 'beamer'));
	}

	public function testOnlyViewersCanView(): void {
		self::assertTrue($this->service->canView('alice', 'room', $this->room()));
		self::assertFalse($this->service->canView('bob', 'room', $this->room()));
		self::assertFalse($this->service->canView('unknown', 'room', $this->room()));
	}

	public function testRestrictedEntitiesNeedARestrictionGroup(): void {
		$this->restrictTo('management');

		self::assertFalse($this->service->canView('alice', 'room', $this->room(true)));
		self::assertTrue($this->service->canView('carol', 'room', $this->room(true)));
	}

	public function testSyncWithoutBookingCalendarDoesNothing(): void {
		$this->calDavBackend->method('getCalendarByUri')->willReturn(null);
		$this->calDavBackend->expects(self::never())->method('updateShares');

		$this->service->syncEntity('room', $this->room());
	}

	public function testSyncSharesWithDefaultViewerGroupsAndRevokesUsersWithoutAccess(): void {
		$this->withBookingCalendar();
		// "other" is no viewer group, so it must not be shared with
		$this->withDefaultGroups(['staff', 'other']);
		$this->withShares(['principals/users/alice', 'principals/users/bob', 'principals/groups/old']);

		$this->calDavBackend->expects(self::once())
			->method('updateShares')
			->with(
				self::isInstanceOf(Calendar::class),
				[['href' => 'principal:principals/groups/staff', 'commonName' => '', 'readOnly' => true]],
				['principal:principals/users/bob', 'principal:principals/groups/old'],
			);

		$this->service->syncEntity('room', $this->room());
	}

	public function testSyncDoesNotTouchSharesThatAreAlreadyRight(): void {
		$this->withBookingCalendar();
		$this->withDefaultGroups(['staff']);
		$this->withShares(['principals/users/alice', 'principals/groups/staff']);

		$this->calDavBackend->expects(self::never())->method('updateShares');

		$this->service->syncEntity('room', $this->room());
	}

	public function testSyncLimitsDefaultGroupsOfRestrictedEntitiesToTheRestrictionGroups(): void {
		$this->withBookingCalendar();
		$this->restrictTo('management');
		$this->withDefaultGroups(['staff']);
		$this->withShares(['principals/groups/staff']);

		$this->calDavBackend->expects(self::once())
			->method('updateShares')
			->with(self::anything(), [], ['principal:principals/groups/staff']);

		$this->service->syncEntity('room', $this->room(true));
	}

	public function testSyncNamesTheBookingCalendarAfterTheEntity(): void {
		$this->withBookingCalendar('Calendar');
		$this->withDefaultGroups([]);
		$this->withShares([]);

		$this->calDavBackend->expects(self::once())
			->method('updateCalendar')
			->with(42, self::callback(static fn ($propPatch): bool => $propPatch->getMutations() === ['{DAV:}displayname' => 'Meeting room']));

		$this->service->syncEntity('room', $this->room());
	}

	public function testHandleCreatedSharesWithUsersThatWantNewCalendars(): void {
		$this->withBookingCalendar();
		$this->withDefaultGroups([]);
		$this->withShares([]);
		$this->settings->method('getAutoSubscribeUsers')->willReturn(['alice', 'bob']);

		// Bob is no viewer and must not get it
		$this->calDavBackend->expects(self::once())
			->method('updateShares')
			->with(
				self::anything(),
				[['href' => 'principal:principals/users/alice', 'commonName' => '', 'readOnly' => true]],
				[],
			);

		$this->service->handleCreated('room', $this->room());
	}

	public function testSubscribeSharesReadOnlyWithTheUser(): void {
		$this->withBookingCalendar();

		$this->calDavBackend->expects(self::once())
			->method('updateShares')
			->with(
				self::anything(),
				[['href' => 'principal:principals/users/alice', 'commonName' => '', 'readOnly' => true]],
				[],
			);

		self::assertTrue($this->service->subscribe('alice', 'room', $this->room()));
	}

	public function testSubscribeFailsWithoutBookingCalendar(): void {
		$this->calDavBackend->method('getCalendarByUri')->willReturn(null);
		$this->calDavBackend->expects(self::never())->method('updateShares');

		self::assertFalse($this->service->subscribe('alice', 'room', $this->room()));
	}

	public function testUnsubscribeUnsharesSoGroupSharesAreHiddenToo(): void {
		$this->withBookingCalendar();

		$this->calDavBackend->expects(self::once())
			->method('unshare')
			->with(self::anything(), 'principal:principals/users/alice');

		$this->service->unsubscribe('alice', 'room', $this->room());
	}

	public function testVisiblePrincipalsOnlyListBookingCalendars(): void {
		$this->calDavBackend->method('getCalendarsForUser')
			->with('principals/users/alice')
			->willReturn([
				['id' => 1, '{http://owncloud.org/ns}owner-principal' => 'principals/users/alice'],
				['id' => 42, '{http://owncloud.org/ns}owner-principal' => self::ROOM_PRINCIPAL],
				['id' => 43, '{http://owncloud.org/ns}owner-principal' => 'principals/calendar-rooms/other_app-room'],
			]);

		self::assertSame([self::ROOM_PRINCIPAL => true], $this->service->getVisiblePrincipals('alice'));
	}

	public function testSyncUserRemovesCalendarsTheUserMayNoLongerSee(): void {
		$this->calDavBackend->method('getCalendarsForUser')
			->with('principals/users/bob')
			->willReturn([
				['id' => 42, '{http://owncloud.org/ns}owner-principal' => self::ROOM_PRINCIPAL],
			]);
		$this->roomMapper->method('findByUID')->with('room-1')->willReturn($this->room());
		$this->calDavBackend->method('getCalendarById')->with(42)->willReturn([
			'id' => 42,
			'uri' => 'calendar',
			'principaluri' => self::ROOM_PRINCIPAL,
		]);

		$this->calDavBackend->expects(self::once())
			->method('updateShares')
			->with(self::anything(), [], ['principal:principals/users/bob']);

		$this->service->syncUser('bob');
	}

	public function testSyncAllDropsDefaultsOfDeletedEntities(): void {
		$this->roomMapper->method('findAll')->willReturn([]);
		$this->resourceMapper->method('findAll')->willReturn([]);
		$this->vehicleMapper->method('findAll')->willReturn([]);

		$orphan = new DefaultShareModel();
		$orphan->setEntityType('room');
		$orphan->setEntityId(7);
		$orphan->setGroupId('staff');
		$this->defaultShareMapper->method('findAll')->willReturn([$orphan]);
		$this->defaultShareMapper->expects(self::once())->method('delete')->with($orphan);

		self::assertSame(0, $this->service->syncAll());
	}

	public function testResourcesAndVehiclesShareThePrincipalPrefix(): void {
		$resource = new ResourceModel();
		$resource->setId(3);
		$resource->setUid('beamer');
		$resource->setDisplayName('Beamer');
		$this->calDavBackend->expects(self::once())
			->method('getCalendarByUri')
			->with('principals/calendar-resources/calendar_resource_management-beamer', 'calendar')
			->willReturn(null);

		$this->service->syncEntity('resource', $resource);
	}

	public function testAllowedGroupsOfRestrictedEntitiesAreViewerAndRestrictionGroups(): void {
		$this->restrictTo('staff');

		self::assertSame(['staff'], $this->service->getAllowedGroupIds('room', $this->room(true)));
		self::assertSame(['staff'], $this->service->getAllowedGroupIds('room', $this->room()));
	}

	public function testRestrictedSharingWithoutSessionActsAsAnAdmin(): void {
		$service = $this->serviceWithRestrictedSharing($admin = $this->createMock(IUser::class));
		$admin->method('isEnabled')->willReturn(true);
		$this->withBookingCalendar();

		$this->userSession->expects(self::exactly(2))
			->method('setVolatileActiveUser')
			->willReturnCallback(function (?IUser $user) use ($admin): void {
				static $calls = 0;
				// First the admin for the share, then back to nobody
				self::assertSame($calls++ === 0 ? $admin : null, $user);
			});
		$this->calDavBackend->expects(self::once())->method('updateShares');

		self::assertTrue($service->subscribe('alice', 'room', $this->room()));
	}

	public function testRestrictedSharingWithoutAdminFails(): void {
		$service = $this->serviceWithRestrictedSharing(null);
		$this->withBookingCalendar();
		$this->calDavBackend->expects(self::never())->method('updateShares');

		$this->expectException(ServiceException::class);

		$service->subscribe('alice', 'room', $this->room());
	}

	private function serviceWithRestrictedSharing(?IUser $admin): BookingCalendarShareService {
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')->willReturn(null);
		$shareManager = $this->createMock(IShareManager::class);
		$shareManager->method('shareWithGroupMembersOnly')->willReturn(true);
		$adminGroup = $this->createMock(IGroup::class);
		$adminGroup->method('getUsers')->willReturn($admin === null ? [] : ['admin' => $admin]);
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('get')->with('admin')->willReturn($adminGroup);
		$l10nFactory = $this->createMock(IL10NFactory::class);
		$l10nFactory->method('get')->willReturn($this->createMock(IL10N::class));

		return new BookingCalendarShareService(
			$this->calDavBackend,
			$this->roomMapper,
			$this->resourceMapper,
			$this->vehicleMapper,
			$this->restrictionMapper,
			$this->defaultShareMapper,
			$this->settings,
			$this->createMock(IUserManager::class),
			$groupManager,
			$this->userSession,
			$shareManager,
			$l10nFactory,
			$this->createMock(IConfig::class),
			$this->createMock(LoggerInterface::class),
		);
	}
}
