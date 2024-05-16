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

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCA\DAV\Events\ScheduleResourcesRoomsUpdateEvent;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
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

	private IEventDispatcher $eventDispatcher;

	public function __construct(LoggerInterface $logger,
								RestrictionMapper $restrictionMapper,
								IEventDispatcher $eventDispatcher) {
		parent::__construct();
		$this->logger = $logger;
		$this->restrictionMapper = $restrictionMapper;
		$this->eventDispatcher = $eventDispatcher;
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
			$output->writeln("<info>" . $inserted->getId() . "</info>");
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry: ' . $e->getMessage() . '</error>');
			return 1;
		}

		if (class_exists(ScheduleResourcesRoomsUpdateEvent::class)) {
			$this->eventDispatcher->dispatchTyped(new ScheduleResourcesRoomsUpdateEvent());
		}

		return 0;
	}
}
