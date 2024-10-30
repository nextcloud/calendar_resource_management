<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateRestriction extends Command {
	private const ENTITY_TYPE = 'entity_type';
	private const ENTITY_ID = 'entity_id';
	private const GROUP_ID = 'group_id';

	/** @var LoggerInterface */
	private $logger;

	/** @var RestrictionMapper */
	private $restrictionMapper;

	public function __construct(
		LoggerInterface $logger,
		RestrictionMapper $restrictionMapper,
		private IResourceManager $resourceManager,
		private IRoomManager $roomManager,
	) {
		parent::__construct();
		$this->logger = $logger;
		$this->restrictionMapper = $restrictionMapper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:restriction:create');
		$this->setDescription('Create a restriction on a resource');
		$this->addArgument(self::ENTITY_TYPE, InputArgument::REQUIRED);
		$this->addArgument(self::ENTITY_ID, InputArgument::REQUIRED);
		$this->addArgument(self::GROUP_ID, InputArgument::REQUIRED);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$entityType = (string)$input->getArgument(self::ENTITY_TYPE);
		$entityId = (int)$input->getArgument(self::ENTITY_ID);
		$groupId = (string)$input->getArgument(self::GROUP_ID);

		$restrictionModel = new RestrictionModel();
		$restrictionModel->setEntityType($entityType);
		$restrictionModel->setEntityId($entityId);
		$restrictionModel->setGroupId($groupId);

		try {
			$inserted = $this->restrictionMapper->insert($restrictionModel);
			$output->writeln('<info>Created new Restriction with ID:</info>');
			$output->writeln('<info>' . $inserted->getId() . '</info>');
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry: ' . $e->getMessage() . '</error>');
			return 1;
		}

		switch ($entityType) {
			case 'vehicle':
			case 'resource':
				if (method_exists($this->resourceManager, 'update')) {
					$this->resourceManager->update();
				}
				break;
			case 'room':
				if (method_exists($this->roomManager, 'update')) {
					$this->roomManager->update();
				}
				break;
		}

		return 0;
	}
}
