<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BookingCalendarSubscribe extends Command {
	private const USER_ID = 'user_id';
	private const ENTITY_TYPE = 'entity_type';
	private const ENTITY_ID = 'entity_id';
	private const UNSUBSCRIBE = 'unsubscribe';

	public function __construct(
		private BookingCalendarShareService $shareService,
		private IUserManager $userManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('calendar-resource:booking-calendars:subscribe');
		$this->setDescription('Add the booking calendar of a room, resource or vehicle to the calendars of a user');
		$this->addArgument(self::USER_ID, InputArgument::REQUIRED);
		$this->addArgument(self::ENTITY_TYPE, InputArgument::REQUIRED, 'room, resource or vehicle');
		$this->addArgument(self::ENTITY_ID, InputArgument::REQUIRED);
		$this->addOption(self::UNSUBSCRIBE, null, InputOption::VALUE_NONE, 'Remove the booking calendar instead');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = (string)$input->getArgument(self::USER_ID);
		$entityType = (string)$input->getArgument(self::ENTITY_TYPE);
		$entityId = (int)$input->getArgument(self::ENTITY_ID);

		if (!$this->userManager->userExists($userId)) {
			$output->writeln('<error>User ' . $userId . ' does not exist.</error>');
			return self::FAILURE;
		}
		try {
			$entity = $this->shareService->findEntity($entityType, $entityId);
		} catch (DoesNotExistException) {
			$output->writeln('<error>Could not find resource type ' . $entityType . ' with ID ' . $entityId . '</error>');
			return self::FAILURE;
		}

		try {
			if ($input->getOption(self::UNSUBSCRIBE)) {
				$this->shareService->unsubscribe($userId, $entityType, $entity);
				$output->writeln('<info>Booking calendar removed.</info>');
				return self::SUCCESS;
			}

			if (!$this->shareService->canView($userId, $entityType, $entity)) {
				$output->writeln('<error>The user is not in a viewer group or not allowed to see this ' . $entityType . '.</error>');
				return self::FAILURE;
			}
			if (!$this->shareService->subscribe($userId, $entityType, $entity)) {
				$output->writeln('<error>The server has no booking calendar for this ' . $entityType . ' yet. It is created with the next update of the calendar rooms and resources by the background jobs.</error>');
				return self::FAILURE;
			}
		} catch (\Throwable $e) {
			$output->writeln('<error>Could not update the booking calendar share: ' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}

		$output->writeln('<info>Booking calendar added.</info>');

		return self::SUCCESS;
	}
}
