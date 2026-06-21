<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\AMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\DB\Exception;
use OCP\IDBConnection;
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
		private IDBConnection $db,
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

		if (!in_array($entityType, Constants::RESTRICTABLE_TYPES, true)) {
			$output->writeln('<error>Restrictions can only be assigned to resource, room, or vehicle entities.</error>');
			return self::FAILURE;
		}

		// resolve corresponding mapper
		$resourceMapper = AMapper::getMapper($entityType, $this->db);
		if ($resourceMapper === null) {
			$output->writeln('<error>No such resource type found!</error>');
			return self::FAILURE;
		}
		// find corresponding entity
		try {
			$resource = $resourceMapper->find($entityId);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not find resource type ' . $entityType . ' with ID ' . $entityId . '</error>');
			return self::FAILURE;
		}
		// create restriction and set resource to restricted
		$restrictionModel = new RestrictionModel();
		$restrictionModel->setEntityType($entityType);
		$restrictionModel->setEntityId($entityId);
		$restrictionModel->setGroupId($groupId);

		try {
			/** @var ResourceModel $resource */
			$resource->setRestricted(true);
			$resourceMapper->update($resource);
			$inserted = $this->restrictionMapper->insert($restrictionModel);

			$output->writeln('<info>Created new Restriction with ID:</info>');
			$output->writeln('<info>' . $inserted->getId() . '</info>');
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry: ' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
		// trigger update of resource for other apps
		switch ($entityType) {
			case Constants::VEHICLE:
			case Constants::RESOURCE:
				$this->resourceManager->update();
				break;
			case Constants::ROOM:
				$this->roomManager->update();
				break;
		}

		return self::SUCCESS;
	}
}
