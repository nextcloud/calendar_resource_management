<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna.larch@nextcloud.com>
 * @author 2024 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Db\AMapper;
use OCA\DAV\Events\ScheduleResourcesRoomsUpdateEvent;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
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

	private IEventDispatcher $eventDispatcher;

	public function __construct(LoggerInterface $logger, IDBConnection $connection, IEventDispatcher $eventDispatcher) {
		parent::__construct();
		$this->logger = $logger;
		$this->connection = $connection;
		$this->eventDispatcher = $eventDispatcher;
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
			"Type of resource (building, story, room, vehicle, resource, restriction)"
		);
		$this->addArgument(
			self::ID,
			InputArgument::REQUIRED,
			"ID of the resource to delete, e.g. 42"
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
		} catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
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

		if (class_exists(ScheduleResourcesRoomsUpdateEvent::class)) {
			$this->eventDispatcher->dispatchTyped(new ScheduleResourcesRoomsUpdateEvent());
		}

		return 0;
	}
}
