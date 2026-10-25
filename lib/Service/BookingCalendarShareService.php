<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\AppInfo\Application;
use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Share\IManager as IShareManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\PropPatch;

/**
 * Shares the booking calendars of rooms, resources and vehicles read-only with users and groups.
 *
 * The server keeps one booking calendar per room or resource principal, scheduling puts every
 * accepted booking into it. Sharing that calendar makes it show up in the calendar home of the
 * sharee, so every CalDAV client lists it. This is the only class that uses the internal DAV
 * classes, the public calendar API cannot share calendars.
 *
 * Direct shares to users are the state of what a user picked, they are only removed when the user
 * loses access. Group shares mirror the defaults an admin configured.
 *
 * Restrictions, default groups and viewer groups are read once and kept for the lifetime of the
 * service, call resetCaches() after changing them.
 */
class BookingCalendarShareService {
	private const USER_PREFIX = 'principals/users/';
	private const GROUP_PREFIX = 'principals/groups/';
	private const ROOM_PRINCIPAL_PREFIX = 'principals/calendar-rooms/';
	private const RESOURCE_PRINCIPAL_PREFIX = 'principals/calendar-resources/';

	/** @var array<string, string[]> Group ids per user */
	private array $userGroupIds = [];
	/** @var string[]|null */
	private ?array $viewerGroupIds = null;
	/** @var array<string, string[]>|null Restriction group ids per "type:id" */
	private ?array $restrictionGroupIds = null;
	/** @var array<string, string[]>|null Default group ids per "type:id" */
	private ?array $defaultGroupIds = null;

	public function __construct(
		private CalDavBackend $calDavBackend,
		private RoomMapper $roomMapper,
		private ResourceMapper $resourceMapper,
		private VehicleMapper $vehicleMapper,
		private RestrictionMapper $restrictionMapper,
		private DefaultShareMapper $defaultShareMapper,
		private BookingCalendarSettings $settings,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IUserSession $userSession,
		private IShareManager $shareManager,
		private IL10NFactory $l10nFactory,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Forget the cached groups, restrictions and defaults
	 */
	public function resetCaches(): void {
		$this->userGroupIds = [];
		$this->viewerGroupIds = null;
		$this->restrictionGroupIds = null;
		$this->defaultGroupIds = null;
	}

	/**
	 * The principal the server created for a room, resource or vehicle
	 */
	public function principalUri(string $type, string $uid): string {
		$prefix = $type === Constants::ROOM ? self::ROOM_PRINCIPAL_PREFIX : self::RESOURCE_PRINCIPAL_PREFIX;

		return $prefix . Application::APP_ID . '-' . $uid;
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findEntity(string $type, int $id): RoomModel|ResourceModel {
		return match ($type) {
			Constants::ROOM => $this->roomMapper->find($id),
			Constants::RESOURCE => $this->resourceMapper->find($id),
			Constants::VEHICLE => $this->vehicleMapper->find($id),
			default => throw new DoesNotExistException('Unknown entity type ' . $type),
		};
	}

	/**
	 * @return list<array{0: string, 1: RoomModel|ResourceModel}> Type and entity of everything bookable
	 */
	public function listEntities(): array {
		$entities = [];
		foreach ($this->roomMapper->findAll() as $room) {
			$entities[] = [Constants::ROOM, $room];
		}
		foreach ($this->resourceMapper->findAll() as $resource) {
			$entities[] = [Constants::RESOURCE, $resource];
		}
		foreach ($this->vehicleMapper->findAll() as $vehicle) {
			$entities[] = [Constants::VEHICLE, $vehicle];
		}

		return $entities;
	}

	/**
	 * Whether the user is in one of the groups that may see booking calendars
	 */
	public function isViewer(string $userId): bool {
		return array_intersect($this->getViewerGroupIds(), $this->getUserGroupIds($userId)) !== [];
	}

	/**
	 * Whether the user may see the booking calendar of the entity
	 */
	public function canView(string $userId, string $type, RoomModel|ResourceModel $entity): bool {
		if (!$this->isViewer($userId)) {
			return false;
		}
		if (!$entity->isRestricted()) {
			return true;
		}

		return array_intersect($this->getRestrictionGroupIds($type, $entity), $this->getUserGroupIds($userId)) !== [];
	}

	/**
	 * The viewer groups whose members may see the entity, only these can be default groups
	 *
	 * @return string[]
	 */
	public function getAllowedGroupIds(string $type, RoomModel|ResourceModel $entity): array {
		$groupIds = $this->getViewerGroupIds();
		if ($entity->isRestricted()) {
			$groupIds = array_intersect($groupIds, $this->getRestrictionGroupIds($type, $entity));
		}

		return array_values($groupIds);
	}

	/**
	 * Whether the user gets the booking calendar through a default group share
	 */
	public function isDefaultFor(string $userId, string $type, RoomModel|ResourceModel $entity): bool {
		return array_intersect($this->getAllowedDefaultGroupIds($type, $entity), $this->getUserGroupIds($userId)) !== [];
	}

	/**
	 * @return array<string, true> The booking calendar principals the user currently sees, as keys
	 */
	public function getVisiblePrincipals(string $userId): array {
		$principals = [];
		foreach ($this->calDavBackend->getCalendarsForUser(self::USER_PREFIX . $userId) as $calendar) {
			$owner = $calendar['{http://owncloud.org/ns}owner-principal'] ?? null;
			if (is_string($owner) && $this->isBookingPrincipal($owner)) {
				$principals[$owner] = true;
			}
		}

		return $principals;
	}

	/**
	 * Share the booking calendar of the entity with the user
	 *
	 * @return bool false if the server did not create the booking calendar yet
	 */
	public function subscribe(string $userId, string $type, RoomModel|ResourceModel $entity): bool {
		$calendar = $this->getCalendar($type, $entity);
		if ($calendar === null) {
			return false;
		}

		// Also removes a hidden marker the user left on a default calendar
		$this->updateShares($calendar, [$this->shareElement(self::USER_PREFIX . $userId)], []);

		return true;
	}

	/**
	 * Remove the booking calendar of the entity from the calendars of the user
	 */
	public function unsubscribe(string $userId, string $type, RoomModel|ResourceModel $entity): void {
		$calendar = $this->getCalendar($type, $entity);
		if ($calendar === null) {
			return;
		}

		// Deletes the direct share and hides a calendar the user gets through a group
		$this->withActingUser(fn () => $this->calDavBackend->unshare($calendar, 'principal:' . self::USER_PREFIX . $userId));
	}

	/**
	 * Share a new entity with the default groups and the users that want new calendars.
	 *
	 * Call it after the room or resource manager was updated, the server creates the booking
	 * calendar while updating.
	 */
	public function handleCreated(string $type, RoomModel|ResourceModel $entity): void {
		$calendar = $this->getCalendar($type, $entity);
		if ($calendar === null) {
			return;
		}

		$add = [];
		foreach ($this->settings->getAutoSubscribeUsers() as $userId) {
			if ($this->canView($userId, $type, $entity)) {
				$add[] = $this->shareElement(self::USER_PREFIX . $userId);
			}
		}
		if ($add !== []) {
			$this->updateShares($calendar, $add, []);
		}

		$this->syncCalendar($calendar, $type, $entity);
	}

	/**
	 * Align the shares of the booking calendar with the default groups and remove users that
	 * lost access
	 */
	public function syncEntity(string $type, RoomModel|ResourceModel $entity): void {
		$calendar = $this->getCalendar($type, $entity);
		if ($calendar === null) {
			return;
		}

		$this->syncCalendar($calendar, $type, $entity);
	}

	/**
	 * Sync one entity after its restrictions or default groups changed
	 */
	public function syncEntityById(string $type, int $id): void {
		$this->resetCaches();

		try {
			$entity = $this->findEntity($type, $id);
		} catch (DoesNotExistException) {
			// The server deletes the booking calendar together with the entity
			return;
		}

		$this->syncEntity($type, $entity);
	}

	/**
	 * Align the shares of every booking calendar
	 *
	 * @return int The number of entities whose shares could not be updated
	 */
	public function syncAll(): int {
		$this->resetCaches();

		$failures = 0;
		$existing = [];
		foreach ($this->listEntities() as [$type, $entity]) {
			$existing[$type][$entity->getId()] = true;
			try {
				$this->syncEntity($type, $entity);
			} catch (\Throwable $e) {
				$failures++;
				$this->logger->error('Could not sync the shares of booking calendar ' . $type . ' ' . $entity->getId(), ['exception' => $e]);
			}
		}

		// Deleting a building or floor deletes its rooms without telling us
		foreach ($this->defaultShareMapper->findAll() as $defaultShare) {
			if (!isset($existing[$defaultShare->getEntityType()][$defaultShare->getEntityId()])) {
				$this->defaultShareMapper->delete($defaultShare);
			}
		}

		return $failures;
	}

	/**
	 * Remove the booking calendars the user lost access to
	 */
	public function syncUser(string $userId): void {
		unset($this->userGroupIds[$userId]);

		foreach ($this->calDavBackend->getCalendarsForUser(self::USER_PREFIX . $userId) as $info) {
			$owner = $info['{http://owncloud.org/ns}owner-principal'] ?? null;
			if (!is_string($owner) || !$this->isBookingPrincipal($owner)) {
				continue;
			}

			[$type, $entity] = $this->findEntityByPrincipal($owner) ?? [null, null];
			if ($entity !== null && $this->canView($userId, $type, $entity)) {
				continue;
			}

			$calendarInfo = $this->calDavBackend->getCalendarById((int)$info['id']);
			if ($calendarInfo !== null) {
				$this->updateShares($this->createCalendar($calendarInfo), [], ['principal:' . self::USER_PREFIX . $userId]);
			}
		}
	}

	private function getCalendar(string $type, RoomModel|ResourceModel $entity): ?Calendar {
		$info = $this->calDavBackend->getCalendarByUri(
			$this->principalUri($type, $entity->getUid()),
			CalDavBackend::RESOURCE_BOOKING_CALENDAR_URI,
		);

		return $info === null ? null : $this->createCalendar($info);
	}

	/**
	 * @param array<string, mixed> $info
	 */
	private function createCalendar(array $info): Calendar {
		return new Calendar($this->calDavBackend, $info, $this->l10nFactory->get('dav'), $this->config, $this->logger);
	}

	private function syncCalendar(Calendar $calendar, string $type, RoomModel|ResourceModel $entity): void {
		$this->syncDisplayName($calendar, $entity);

		$wantedGroupIds = $this->getAllowedDefaultGroupIds($type, $entity);

		$sharedGroupIds = [];
		$remove = [];
		foreach ($this->calDavBackend->getShares($calendar->getResourceId()) as $share) {
			$principal = substr($share['href'], strlen('principal:'));
			if (str_starts_with($principal, self::GROUP_PREFIX)) {
				$groupId = urldecode(substr($principal, strlen(self::GROUP_PREFIX)));
				if (in_array($groupId, $wantedGroupIds, true)) {
					$sharedGroupIds[] = $groupId;
				} else {
					$remove[] = $share['href'];
				}
			} elseif (str_starts_with($principal, self::USER_PREFIX)) {
				$userId = urldecode(substr($principal, strlen(self::USER_PREFIX)));
				if (!$this->canView($userId, $type, $entity)) {
					$remove[] = $share['href'];
				}
			}
		}

		$add = array_map(
			fn (string $groupId): array => $this->shareElement(self::GROUP_PREFIX . urlencode($groupId)),
			array_values(array_diff($wantedGroupIds, $sharedGroupIds)),
		);

		if ($add !== [] || $remove !== []) {
			$this->updateShares($calendar, $add, $remove);
		}
	}

	/**
	 * Booking calendars are created by the server as "Calendar", name them after the entity so
	 * sharees do not get a list of calendars that are all called the same
	 */
	private function syncDisplayName(Calendar $calendar, RoomModel|ResourceModel $entity): void {
		$displayName = $entity->getDisplayName();
		$current = $calendar->getProperties(['{DAV:}displayname'])['{DAV:}displayname'] ?? null;
		if ($displayName === '' || $current === $displayName) {
			return;
		}

		$propPatch = new PropPatch(['{DAV:}displayname' => $displayName]);
		$this->calDavBackend->updateCalendar($calendar->getResourceId(), $propPatch);
		$propPatch->commit();
	}

	/**
	 * @param list<array{href: string, commonName: string, readOnly: bool}> $add
	 * @param list<string> $remove
	 */
	private function updateShares(Calendar $calendar, array $add, array $remove): void {
		$this->withActingUser(fn () => $this->calDavBackend->updateShares($calendar, $add, $remove));
	}

	/**
	 * The DAV principal backend refuses to resolve sharees without a logged-in user when sharing
	 * is restricted to group members, so the background job and the commands act as an admin,
	 * the same as an admin saving the settings in the web interface
	 *
	 * @throws ServiceException If there is no admin to act as
	 */
	private function withActingUser(callable $callback): void {
		if ($this->userSession->getUser() !== null || !$this->shareManager->shareWithGroupMembersOnly()) {
			$callback();
			return;
		}

		$this->userSession->setVolatileActiveUser($this->findAdmin());
		try {
			$callback();
		} finally {
			$this->userSession->setVolatileActiveUser(null);
		}
	}

	/**
	 * @throws ServiceException
	 */
	private function findAdmin(): IUser {
		foreach ($this->groupManager->get('admin')?->getUsers() ?? [] as $user) {
			if ($user->isEnabled()) {
				return $user;
			}
		}

		throw new ServiceException('Sharing is restricted to group members and there is no enabled admin to change booking calendar shares as');
	}

	/**
	 * @return string[]
	 */
	private function getViewerGroupIds(): array {
		return $this->viewerGroupIds ??= $this->settings->getViewerGroups();
	}

	/**
	 * @return string[] The default groups that may see the entity
	 */
	private function getAllowedDefaultGroupIds(string $type, RoomModel|ResourceModel $entity): array {
		if ($this->defaultGroupIds === null) {
			$this->defaultGroupIds = [];
			foreach ($this->defaultShareMapper->findAll() as $defaultShare) {
				$this->defaultGroupIds[$defaultShare->getEntityType() . ':' . $defaultShare->getEntityId()][] = $defaultShare->getGroupId();
			}
		}

		return array_values(array_intersect(
			$this->defaultGroupIds[$type . ':' . $entity->getId()] ?? [],
			$this->getAllowedGroupIds($type, $entity),
		));
	}

	/**
	 * @return string[]
	 */
	private function getRestrictionGroupIds(string $type, RoomModel|ResourceModel $entity): array {
		if ($this->restrictionGroupIds === null) {
			$this->restrictionGroupIds = [];
			foreach ($this->restrictionMapper->findAll() as $restriction) {
				$this->restrictionGroupIds[$restriction->getEntityType() . ':' . $restriction->getEntityId()][] = $restriction->getGroupId();
			}
		}

		return $this->restrictionGroupIds[$type . ':' . $entity->getId()] ?? [];
	}

	/**
	 * @return string[]
	 */
	private function getUserGroupIds(string $userId): array {
		if (!isset($this->userGroupIds[$userId])) {
			$user = $this->userManager->get($userId);
			$this->userGroupIds[$userId] = $user === null ? [] : $this->groupManager->getUserGroupIds($user);
		}

		return $this->userGroupIds[$userId];
	}

	private function isBookingPrincipal(string $principal): bool {
		return str_starts_with($principal, self::ROOM_PRINCIPAL_PREFIX . Application::APP_ID . '-')
			|| str_starts_with($principal, self::RESOURCE_PRINCIPAL_PREFIX . Application::APP_ID . '-');
	}

	/**
	 * @return array{0: string, 1: RoomModel|ResourceModel}|null
	 */
	private function findEntityByPrincipal(string $principal): ?array {
		$isRoom = str_starts_with($principal, self::ROOM_PRINCIPAL_PREFIX);
		$prefix = ($isRoom ? self::ROOM_PRINCIPAL_PREFIX : self::RESOURCE_PRINCIPAL_PREFIX) . Application::APP_ID . '-';
		$uid = substr($principal, strlen($prefix));

		$candidates = $isRoom
			? [Constants::ROOM => $this->roomMapper]
			: [Constants::RESOURCE => $this->resourceMapper, Constants::VEHICLE => $this->vehicleMapper];
		foreach ($candidates as $type => $mapper) {
			try {
				return [$type, $mapper->findByUID($uid)];
			} catch (DoesNotExistException) {
				// Resources and vehicles share the principal prefix
			}
		}

		return null;
	}

	/**
	 * @return array{href: string, commonName: string, readOnly: bool}
	 */
	private function shareElement(string $principal): array {
		return [
			'href' => 'principal:' . $principal,
			'commonName' => '',
			'readOnly' => true,
		];
	}
}
