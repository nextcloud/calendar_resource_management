<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateBuilding extends Command {
	private const DISPLAY_NAME = 'display_name';
	private const DESCRIPTION = 'description';
	private const ADDRESS = 'address';
	private const WHEELCHAIR = 'wheelchair-accessible';

	/** @var LoggerInterface */
	private $logger;

	/** @var BuildingMapper */
	private $buildingMapper;

	public function __construct(LoggerInterface $logger, BuildingMapper $buildingMapper) {
		parent::__construct();
		$this->logger = $logger;
		$this->buildingMapper = $buildingMapper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:building:create');
		$this->setDescription('Create a building resource');
		$this->addArgument(
			self::DISPLAY_NAME,
			InputArgument::REQUIRED,
			'The name of this building, e.g. Berlin HQ'
		);
		$this->addOption(
			self::ADDRESS,
			null,
			InputOption::VALUE_REQUIRED,
			'The address of the building, e.g. "Gerichtstraße 23, 13347 Berlin, Germany"'
		);
		$this->addOption(
			self::DESCRIPTION,
			null,
			InputOption::VALUE_REQUIRED,
			'An optional description of the building'
		);
		$this->addOption(
			self::WHEELCHAIR,
			null,
			InputOption::VALUE_REQUIRED,
			'Is this building wheelchair accessible? 0 (no) or 1 (yes)',
			'0' // Defaults to 0 to not wrongly advertise a building with barriers with default arguments
		);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$displayName = (string)$input->getArgument(self::DISPLAY_NAME);
		$description = (string)$input->getOption(self::DESCRIPTION);
		$address = (string)$input->getOption(self::ADDRESS);
		$wheelchair = (bool)$input->getOption(self::WHEELCHAIR);

		$buildingModel = new BuildingModel();
		$buildingModel->setDisplayName($displayName);
		$buildingModel->setAddress($address);
		$buildingModel->setDescription($description);
		$buildingModel->setIsWheelchairAccessible($wheelchair);

		try {
			$inserted = $this->buildingMapper->insert($buildingModel);
			$output->writeln('<info>Created new Building with ID:</info>');
			$output->writeln('<info>' . $inserted->getId() . '</info>');
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry: ' . $e->getMessage() . '</error>');
			return 1;
		}

		return 0;
	}
}
