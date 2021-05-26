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

use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateStory extends Command {
	// which arguments do we need?

	private const DISPLAY_NAME = 'display_name';
	private const BUILDING_ID = 'building_id';

	/** @var LoggerInterface */
	private $logger;

	/** @var StoryMapper */
	private $storyMapper;

	public function __construct(LoggerInterface $logger, StoryMapper $storyMapper) {
		parent::__construct();
		$this->logger = $logger;
		$this->storyMapper = $storyMapper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:story:create');
		$this->setDescription('Create a Story Resource');
		$this->addArgument(self::DISPLAY_NAME, InputArgument::REQUIRED);
		$this->addArgument(self::BUILDING_ID, InputArgument::REQUIRED);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$displayName = (string)$input->getArgument(self::DISPLAY_NAME);
		$building = (int)$input->getArgument(self::BUILDING_ID);

		$storyModel = new StoryModel();
		$storyModel->setDisplayName($displayName);
		$storyModel->setBuildingId($building);

		try {
			$inserted = $this->storyMapper->insert($storyModel);
			$output->writeln('<info>Created new Story with ID:</info>');
			$output->writeln("<info>" . $inserted->getId() . "</info>");
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry.</error>');
			return 1;
		}

		return 0;
	}
}
