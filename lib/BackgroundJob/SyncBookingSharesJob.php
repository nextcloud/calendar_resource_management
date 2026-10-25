<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\BackgroundJob;

use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;

/**
 * Catches up on changes that did not sync the booking calendar shares right away, e.g. a
 * booking calendar the server created later or group memberships changed by a user backend
 */
class SyncBookingSharesJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private BookingCalendarShareService $shareService,
	) {
		parent::__construct($time);

		$this->setInterval(60 * 60);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
	}

	protected function run($argument): void {
		$this->shareService->syncAll();
	}
}
