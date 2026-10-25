<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Listener;

use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserRemovedEvent;
use Psr\Log\LoggerInterface;

/**
 * Removes the booking calendars a user may no longer see after leaving a group
 */
class GroupMembershipListener implements IEventListener {
	public function __construct(
		private BookingCalendarShareService $shareService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
			return;
		}

		try {
			$this->shareService->syncUser($event->getUser()->getUID());
		} catch (\Throwable $e) {
			// The background job retries, a failure must not break the group change
			$this->logger->error('Could not update the booking calendars of a user that left a group', ['exception' => $e]);
		}
	}
}
