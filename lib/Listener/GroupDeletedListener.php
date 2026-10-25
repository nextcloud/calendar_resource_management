<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Listener;

use OCA\CalendarResourceManagement\BackgroundJob\SyncAllBookingSharesJob;
use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Service\BookingCalendarSettings;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * Class GroupDeletedListener
 *
 * @package OCA\CalendarResourceManagement\Listener
 */
class GroupDeletedListener implements IEventListener {
	/** @var array<string, string[]> Members per group that is about to be deleted */
	private array $members = [];

	/**
	 * GroupDeletedListener constructor.
	 *
	 * @param RestrictionMapper $mapper
	 */
	public function __construct(
		private RestrictionMapper $mapper,
		private DefaultShareMapper $defaultShareMapper,
		private BookingCalendarSettings $settings,
		private BookingCalendarShareService $shareService,
		private IJobList $jobList,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if ($event instanceof BeforeGroupDeletedEvent) {
			// The members are unknown once the group is gone
			$group = $event->getGroup();
			$this->members[$group->getGID()] = array_map(static fn ($user) => $user->getUID(), array_values($group->getUsers()));
			return;
		}
		if (!($event instanceof GroupDeletedEvent)) {
			return;
		}

		$groupId = $event->getGroup()->getGID();
		$this->mapper->deleteAllRestrictionsByGroupId($groupId);
		$this->defaultShareMapper->deleteAllByGroupId($groupId);

		$viewerGroups = $this->settings->getViewerGroups();
		if (in_array($groupId, $viewerGroups, true)) {
			$this->settings->setViewerGroups(array_values(array_diff($viewerGroups, [$groupId])));
		}

		// Former members lose the calendars they picked right away
		$this->shareService->resetCaches();
		foreach ($this->members[$groupId] ?? [] as $userId) {
			try {
				$this->shareService->syncUser($userId);
			} catch (\Throwable $e) {
				$this->logger->error('Could not update the booking calendars of a former group member', ['exception' => $e]);
			}
		}
		unset($this->members[$groupId]);

		// Removes the group shares of the deleted group
		$this->jobList->add(SyncAllBookingSharesJob::class);
	}
}
