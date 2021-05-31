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

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateBuilding extends Command {
	private const DISPLAY_NAME = 'display_name';
	private const DESCRIPTION = 'description';
	private const ADDRESS = 'address';
	private const WHEELCHAIR = 'wheelchair_accessible';

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
			"The name of this building, e.g. Berlin HQ"
		);
		$this->addArgument(
			self::ADDRESS,
			InputArgument::OPTIONAL,
			"The address of the building, e.g. \"Gerichtstraße 23, 13347 Berlin, Germany\""
		);
		$this->addArgument(
			self::DESCRIPTION,
			InputArgument::OPTIONAL,
			"An optional description of the building",
			""
		);
		$this->addArgument(
			self::WHEELCHAIR,
			InputArgument::OPTIONAL,
			"Whether or not the building is wheelchair accessible (0 or 1)",
			"0" // Defaults to 0 to not wrongly advertise a building with barriers with default arguments
		);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$displayName = (string)$input->getArgument(self::DISPLAY_NAME);
		$description = (string)$input->getArgument(self::DESCRIPTION);
		$address = (string)$input->getArgument(self::ADDRESS);
		$wheelchair = (bool)$input->getArgument(self::WHEELCHAIR);

		$buildingModel = new BuildingModel();
		$buildingModel->setDisplayName($displayName);
		$buildingModel->setAddress($address);
		$buildingModel->setDescription($description);
		$buildingModel->setIsWheelchairAccessible($wheelchair);

		try {
			$inserted = $this->buildingMapper->insert($buildingModel);
			$output->writeln('<info>Created new Building with ID:</info>');
			$output->writeln("<info>" . $inserted->getId() . "</info>");
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry.</error>');
			return 1;
		}

		return 0;
	}
}
