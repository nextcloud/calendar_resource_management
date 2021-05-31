<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna.larch@nextcloud.com>
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

use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateResource extends Command {
	private const UID = 'uid';
	private const BUILDING_ID = 'building_id';
	private const DISPLAY_NAME = 'display_name';
	private const EMAIL = 'email';
	private const TYPE = 'resource_type';
	private const CONTACT = 'contact_person_user_id';

	/** @var LoggerInterface */
	private $logger;

	/** @var ResourceMapper */
	private $resourceMapper;

	public function __construct(LoggerInterface $logger,
								ResourceMapper $resourceMapper) {
		parent::__construct();
		$this->logger = $logger;
		$this->resourceMapper = $resourceMapper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:resource:create');
		$this->setDescription('Create a general resource');
		$this->addArgument(self::UID, InputArgument::REQUIRED);
		$this->addArgument(self::BUILDING_ID, InputArgument::REQUIRED);
		$this->addArgument(self::DISPLAY_NAME, InputArgument::REQUIRED);
		$this->addArgument(self::EMAIL, InputArgument::REQUIRED);
		$this->addArgument(self::TYPE, InputArgument::REQUIRED);
		$this->addArgument(self::CONTACT, InputArgument::OPTIONAL);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = (string)$input->getArgument(self::UID);
		$buildingId = (int)$input->getArgument(self::BUILDING_ID);
		$displayName = (string)$input->getArgument(self::DISPLAY_NAME);
		$email = (string)$input->getArgument(self::EMAIL);
		$type = (string)$input->getArgument(self::TYPE);
		$contact = (string)$input->getArgument(self::CONTACT);


		$resourceModel = new ResourceModel();
		$resourceModel->setUid($uid);
		$resourceModel->setDisplayName($displayName);
		$resourceModel->setBuildingId($buildingId);
		$resourceModel->setEmail($email);
		$resourceModel->setResourceType($type);
		$resourceModel->setContactPersonUserId($contact);

		try {
			$inserted = $this->resourceMapper->insert($resourceModel);
			$output->writeln('<info>Created new Resource with ID:</info>');
			$output->writeln("<info>" . $inserted->getId() . "</info>");
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry.</error>');
			return 1;
		}

		return 0;
	}
}
