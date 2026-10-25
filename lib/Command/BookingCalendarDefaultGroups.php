<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BookingCalendarDefaultGroups extends Command {
	private const ENTITY_TYPE = 'entity_type';
	private const ENTITY_ID = 'entity_id';
	private const GROUP_IDS = 'group_ids';
	private const CLEAR = 'clear';

	public function __construct(
		private BookingCalendarShareService $shareService,
		private DefaultShareMapper $defaultShareMapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('calendar-resource:booking-calendars:default-groups');
		$this->setDescription('Show or set the groups that get the booking calendar of a room, resource or vehicle without picking it');
		$this->addArgument(self::ENTITY_TYPE, InputArgument::REQUIRED, 'room, resource or vehicle');
		$this->addArgument(self::ENTITY_ID, InputArgument::REQUIRED);
		$this->addArgument(
			self::GROUP_IDS,
			InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
			'IDs of viewer groups, replaces the current ones. Without any, the current groups are shown'
		);
		$this->addOption(self::CLEAR, null, InputOption::VALUE_NONE, 'Remove all default groups');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$entityType = (string)$input->getArgument(self::ENTITY_TYPE);
		$entityId = (int)$input->getArgument(self::ENTITY_ID);
		/** @var string[] $groupIds */
		$groupIds = $input->getArgument(self::GROUP_IDS);
		$clear = (bool)$input->getOption(self::CLEAR);

		if (!in_array($entityType, Constants::RESTRICTABLE_TYPES, true)) {
			$output->writeln('<error>Default groups can only be assigned to resource, room, or vehicle entities.</error>');
			return self::FAILURE;
		}
		try {
			$entity = $this->shareService->findEntity($entityType, $entityId);
		} catch (DoesNotExistException) {
			$output->writeln('<error>Could not find resource type ' . $entityType . ' with ID ' . $entityId . '</error>');
			return self::FAILURE;
		}

		if ($groupIds === [] && !$clear) {
			foreach ($this->defaultShareMapper->findGroupIds($entityType, $entityId) as $groupId) {
				$output->writeln($groupId);
			}
			return self::SUCCESS;
		}

		$notAllowed = array_diff($groupIds, $this->shareService->getAllowedGroupIds($entityType, $entity));
		if ($notAllowed !== []) {
			$output->writeln('<error>Not a viewer group that may see this ' . $entityType . ': ' . implode(', ', $notAllowed) . '. Check `calendar-resource:booking-calendars:viewer-groups` and the restrictions.</error>');
			return self::FAILURE;
		}

		$this->defaultShareMapper->setGroupIds($entityType, $entityId, $clear ? [] : $groupIds);
		try {
			$this->shareService->syncEntityById($entityType, $entityId);
		} catch (\Throwable $e) {
			$output->writeln('<error>Default groups saved, but the shares could not be updated: ' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
		$output->writeln('<info>Default groups saved and booking calendar shares updated.</info>');

		return self::SUCCESS;
	}
}
