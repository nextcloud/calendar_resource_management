<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BookingCalendarSync extends Command {
	public function __construct(
		private BookingCalendarShareService $shareService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('calendar-resource:booking-calendars:sync');
		$this->setDescription('Align the shares of all booking calendars with the viewer and default groups');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$failures = $this->shareService->syncAll();
		if ($failures > 0) {
			$output->writeln('<error>The shares of ' . $failures . ' booking calendars could not be updated. See the log for details.</error>');
			return self::FAILURE;
		}
		$output->writeln('<info>Booking calendar shares updated.</info>');

		return self::SUCCESS;
	}
}
