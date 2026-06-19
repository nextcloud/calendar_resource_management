<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateStory extends Command {
	private const DISPLAY_NAME = 'display_name';
	private const BUILDING_ID = 'building_id';

	/** @var LoggerInterface */
	private $logger;

	/** @var StoryMapper */
	private $storyMapper;

	/** @var BuildingMapper */
	private $buildingMapper;

	public function __construct(LoggerInterface $logger, StoryMapper $storyMapper, BuildingMapper $buildingMapper) {
		parent::__construct();
		$this->logger = $logger;
		$this->storyMapper = $storyMapper;
		$this->buildingMapper = $buildingMapper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:story:create');
		$this->setDescription('Create a story resource');
		$this->addArgument(
			self::BUILDING_ID,
			InputArgument::REQUIRED,
			'ID of the building, e.g. 17'
		);
		$this->addArgument(
			self::DISPLAY_NAME,
			InputArgument::REQUIRED,
			'Name of the floor, e.g. "2"'
		);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$displayName = (string)$input->getArgument(self::DISPLAY_NAME);
		$building = (int)$input->getArgument(self::BUILDING_ID);

		try {
			$this->buildingMapper->find($building);
		} catch (DoesNotExistException) {
			$output->writeln('<error>Building with ID ' . $building . ' does not exist. Use `calendar-resource:resources:list` to see available buildings.</error>');
			return 1;
		}

		$storyModel = new StoryModel();
		$storyModel->setDisplayName($displayName);
		$storyModel->setBuildingId($building);

		try {
			$inserted = $this->storyMapper->insert($storyModel);
			$output->writeln('<info>Created new Story with ID:</info>');
			$output->writeln('<info>' . $inserted->getId() . '</info>');
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry: ' . $e->getMessage() . '</error>');
			return 1;
		}

		return 0;
	}
}
