<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\AMapper;
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

class RestrictedResource extends Command {
	private const ENTITY_TYPE = 'entity_type';
	private const ENTITY_ID = 'entity_id';
	private const RESTRICTED = 'restricted';

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		LoggerInterface $logger,
		private IDBConnection $db,
		private IResourceManager $resourceManager,
		private IRoomManager $roomManager,
	) {
		parent::__construct();
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:restrict');
		$this->setDescription('Set the restricted flag on a resource, room, or vehicle');
		$this->addArgument(self::ENTITY_TYPE, InputArgument::REQUIRED);
		$this->addArgument(self::ENTITY_ID, InputArgument::REQUIRED);
		$this->addArgument(self::RESTRICTED, InputArgument::REQUIRED);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$entityType = (string)$input->getArgument(self::ENTITY_TYPE);
		$entityId = (int)$input->getArgument(self::ENTITY_ID);
		$restrictedValue = (string)$input->getArgument(self::RESTRICTED);

		// verify input is valid
		if (!in_array($entityType, Constants::RESTRICTABLE_TYPES, true)) {
			$output->writeln('<error>Restricted flag can only be changed for resource, room, or vehicle entities.</error>');
			return self::FAILURE;
		}
		$restricted = match (strtolower($restrictedValue)) {
			'1', 'true', 'on', 'yes' => true,
			'0', 'false', 'off', 'no' => false,
			default => null,
		};
		if ($restricted === null) {
			$output->writeln('<error>Restricted must be one of: on, off, true, false, 1, or 0.</error>');
			return self::FAILURE;
		}
		// resolve corresponding mapper
		$resourceMapper = AMapper::getMapper($entityType, $this->db);
		if ($resourceMapper === null) {
			$output->writeln('<error>No such resource type found!</error>');
			return self::FAILURE;
		}
		// find corresponding entity and update restricted flag
		try {
			$resource = $resourceMapper->find($entityId);
			$resource->setRestricted($restricted);
			$resourceMapper->update($resource);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not find resource type ' . $entityType . ' with ID ' . $entityId . '</error>');
			return self::FAILURE;
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not update restricted flag: ' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
		// trigger update of resource for other apps
		switch ($entityType) {
			case Constants::VEHICLE:
			case Constants::RESOURCE:
				if (method_exists($this->resourceManager, 'update')) {
					$this->resourceManager->update();
				}
				break;
			case Constants::ROOM:
				if (method_exists($this->roomManager, 'update')) {
					$this->roomManager->update();
				}
				break;
		}

		$output->writeln('<info>Restricted flag set to ' . ($restricted ? 'on' : 'off') . '.</info>');

		return self::SUCCESS;
	}
}
