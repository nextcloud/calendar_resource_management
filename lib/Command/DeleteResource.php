<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

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

class DeleteResource extends Command {
	private const TYPE = 'type';
	private const ID = 'resource_id';

	/** @var LoggerInterface */
	private $logger;

	/** @var IDBConnection */
	private $connection;

	public function __construct(
		LoggerInterface $logger,
		IDBConnection $connection,
		private IResourceManager $resourceManager,
		private IRoomManager $roomManager,
	) {
		parent::__construct();
		$this->logger = $logger;
		$this->connection = $connection;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:resource:delete');
		$this->setDescription('Delete a resource and anything that belongs to them');
		$this->addArgument(
			self::TYPE,
			InputArgument::REQUIRED,
			'Type of resource (building, story, room, vehicle, resource, restriction)'
		);
		$this->addArgument(
			self::ID,
			InputArgument::REQUIRED,
			'ID of the resource to delete, e.g. 42'
		);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$type = (string)$input->getArgument(self::TYPE);
		$id = (int)$input->getArgument(self::ID);

		$mapper = AMapper::getMapper($type, $this->connection);

		if ($mapper === null) {
			$output->writeln('<error>No such resource type found!</error>');
			return 3;
		}

		try {
			$entity = $mapper->find($id);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
			$output->writeln('<error>Could not find resource type ' . $type . ' with ID ' . $id . '</error>');
			return 2;
		}

		// delete cascading with FKs
		try {
			$mapper->delete($entity);
			$output->writeln('<info>Deleted resource type ' . $type . ' with ID ' . $id . ' and all associated entries.</info>');
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not delete resource type ' . $type . ' with ID ' . $id . ': ' . $e->getMessage() . '</error>');
			return 1;
		}

		switch ($type) {
			case 'building':
			case 'story':
			case 'room':
				$this->updateRooms();
				break;
			case 'vehicle':
			case 'resource':
				$this->updateResources();
				break;
			default:
				$this->updateResources();
				$this->updateRooms();
				break;
		}

		return 0;
	}

	private function updateResources(): void {
		if (method_exists($this->resourceManager, 'update')) {
			$this->resourceManager->update();
		}
	}

	private function updateRooms(): void {
		if (method_exists($this->roomManager, 'update')) {
			$this->roomManager->update();
		}
	}
}
