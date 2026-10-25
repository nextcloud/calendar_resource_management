<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Service\BookingCalendarSettings;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\IGroupManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BookingCalendarViewerGroups extends Command {
	private const GROUP_IDS = 'group_ids';
	private const CLEAR = 'clear';

	public function __construct(
		private BookingCalendarSettings $settings,
		private BookingCalendarShareService $shareService,
		private IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('calendar-resource:booking-calendars:viewer-groups');
		$this->setDescription('Show or set the groups whose members may add booking calendars of rooms, resources and vehicles');
		$this->addArgument(
			self::GROUP_IDS,
			InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
			'IDs of the groups, replaces the current ones. Without any, the current groups are shown'
		);
		$this->addOption(
			self::CLEAR,
			null,
			InputOption::VALUE_NONE,
			'Remove all groups, nobody sees booking calendars anymore'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string[] $groupIds */
		$groupIds = $input->getArgument(self::GROUP_IDS);
		$clear = (bool)$input->getOption(self::CLEAR);

		if ($groupIds === [] && !$clear) {
			foreach ($this->settings->getViewerGroups() as $groupId) {
				$output->writeln($groupId);
			}
			return self::SUCCESS;
		}

		foreach ($groupIds as $groupId) {
			if (!$this->groupManager->groupExists($groupId)) {
				$output->writeln('<error>Group ' . $groupId . ' does not exist.</error>');
				return self::FAILURE;
			}
		}

		$this->settings->setViewerGroups($clear ? [] : $groupIds);
		$failures = $this->shareService->syncAll();
		if ($failures > 0) {
			$output->writeln('<error>Viewer groups saved, but the shares of ' . $failures . ' booking calendars could not be updated. See the log for details.</error>');
			return self::FAILURE;
		}
		$output->writeln('<info>Viewer groups saved and booking calendar shares updated.</info>');

		return self::SUCCESS;
	}
}
