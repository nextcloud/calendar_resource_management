<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\BackgroundJob;

use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

/**
 * Syncs all booking calendar shares once, queued by changes that affect many calendars so the
 * request that made them does not have to wait
 */
class SyncAllBookingSharesJob extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private BookingCalendarShareService $shareService,
	) {
		parent::__construct($time);
	}

	protected function run($argument): void {
		$this->shareService->syncAll();
	}
}
