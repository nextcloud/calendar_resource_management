<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Controller;

use OCA\CalendarResourceManagement\BackgroundJob\SyncAllBookingSharesJob;
use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Db\DefaultShareModel;
use OCA\CalendarResourceManagement\Service\BookingCalendarSettings;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCA\CalendarResourceManagement\Settings\AdminSettings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\JSONResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Who sees the booking calendars of rooms, resources and vehicles
 */
class BookingCalendarAdminController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private BookingCalendarSettings $settings,
		private BookingCalendarShareService $shareService,
		private DefaultShareMapper $defaultShareMapper,
		private IGroupManager $groupManager,
		private IJobList $jobList,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Groups that can be picked as viewer or default groups
	 */
	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/groups')]
	public function getGroups(string $search = '', int $limit = 25): JSONResponse {
		$groups = $this->groupManager->search($search, max(1, min($limit, 100)));

		return new JSONResponse(array_map($this->serializeGroup(...), $groups));
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/booking-calendars')]
	public function getBookingCalendars(): JSONResponse {
		return new JSONResponse([
			'viewerGroups' => $this->serializeGroupIds($this->settings->getViewerGroups()),
			'entities' => array_map(
				fn (array $typeAndEntity): array => [
					'type' => $typeAndEntity[0],
					'id' => $typeAndEntity[1]->getId(),
					'name' => $typeAndEntity[1]->getDisplayName(),
					// Restricted entities can only be defaults of viewer groups that may see them
					'allowedGroupIds' => $this->shareService->getAllowedGroupIds($typeAndEntity[0], $typeAndEntity[1]),
				],
				$this->shareService->listEntities(),
			),
			'defaultGroups' => array_map(
				static fn (DefaultShareModel $share): array => [
					'entityType' => $share->getEntityType(),
					'entityId' => $share->getEntityId(),
					'groupId' => $share->getGroupId(),
				],
				$this->defaultShareMapper->findAll(),
			),
		]);
	}

	/**
	 * @param string[] $groupIds
	 */
	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/booking-calendars/viewer-groups')]
	public function setViewerGroups(array $groupIds = []): JSONResponse {
		$groupIds = array_values(array_unique(array_map('strval', $groupIds)));
		foreach ($groupIds as $groupId) {
			if (!$this->groupManager->groupExists($groupId)) {
				return $this->error('A selected group does not exist', Http::STATUS_BAD_REQUEST);
			}
		}

		try {
			$this->settings->setViewerGroups($groupIds);
			// Touches every booking calendar, too slow for a request with many of them
			$this->jobList->add(SyncAllBookingSharesJob::class);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not save the viewer groups', $e);
		}

		return new JSONResponse($this->serializeGroupIds($groupIds));
	}

	/**
	 * @param string[] $groupIds
	 */
	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/booking-calendars/{entityType}/{entityId}/default-groups')]
	public function setDefaultGroups(string $entityType, int $entityId, array $groupIds = []): JSONResponse {
		if (!in_array($entityType, Constants::RESTRICTABLE_TYPES, true)) {
			return $this->error('Unknown entity type', Http::STATUS_BAD_REQUEST);
		}
		$groupIds = array_values(array_unique(array_map('strval', $groupIds)));

		try {
			$entity = $this->shareService->findEntity($entityType, $entityId);
		} catch (DoesNotExistException) {
			return $this->error('The entity does not exist', Http::STATUS_NOT_FOUND);
		}
		if (array_diff($groupIds, $this->shareService->getAllowedGroupIds($entityType, $entity)) !== []) {
			return $this->error('Default groups have to be viewer groups that may see the entity', Http::STATUS_BAD_REQUEST);
		}

		try {
			$this->defaultShareMapper->setGroupIds($entityType, $entityId, $groupIds);
			$this->shareService->syncEntityById($entityType, $entityId);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not save the default groups', $e);
		}

		return new JSONResponse($groupIds);
	}

	/**
	 * @param string[] $groupIds
	 * @return list<array{id: string, displayName: string}>
	 */
	private function serializeGroupIds(array $groupIds): array {
		$groups = [];
		foreach ($groupIds as $groupId) {
			$group = $this->groupManager->get($groupId);
			// A group of a vanished user backend still shows up, so it can be removed
			$groups[] = $group === null
				? ['id' => $groupId, 'displayName' => $groupId]
				: $this->serializeGroup($group);
		}

		return $groups;
	}

	/**
	 * @return array{id: string, displayName: string}
	 */
	private function serializeGroup(IGroup $group): array {
		return [
			'id' => $group->getGID(),
			'displayName' => $group->getDisplayName(),
		];
	}

	private function error(string $message, int $status): JSONResponse {
		return new JSONResponse(['error' => $message], $status);
	}

	private function unexpectedError(string $message, Throwable $e): JSONResponse {
		$this->logger->error($message, ['exception' => $e]);

		return $this->error($message, Http::STATUS_INTERNAL_SERVER_ERROR);
	}
}
