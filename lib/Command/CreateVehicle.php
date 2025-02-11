<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCA\CalendarResourceManagement\Service\UidValidationService;
use OCP\Calendar\Resource\IManager as IResourceManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateVehicle extends Command {
	private const UID = 'uid';
	private const BUILDING_ID = 'building_id';
	private const DISPLAY_NAME = 'display_name';
	private const EMAIL = 'email';
	private const CONTACT = 'contact-person-user-id';
	private const VEHICLE_TYPE = 'vehicle_type';
	private const VEHICLE_MAKE = 'vehicle_make';
	private const VEHICLE_MODEL = 'vehicle_model';
	private const IS_ELECTRIC = 'is-electric';
	private const RANGE = 'range';
	private const SEATING_CAPACITY = 'seating-capacity';

	/** @var LoggerInterface */
	private $logger;

	/** @var VehicleMapper */
	private $vehicleMapper;

	public function __construct(
		LoggerInterface $logger,
		VehicleMapper $vehicleMapper,
		private IResourceManager $resourceManager,
		private UidValidationService $uidValidationService,
	) {
		parent::__construct();
		$this->logger = $logger;
		$this->vehicleMapper = $vehicleMapper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:vehicle:create');
		$this->setDescription('Create a vehicle resource');
		$this->addArgument(self::UID, InputArgument::REQUIRED);
		$this->addArgument(self::BUILDING_ID, InputArgument::REQUIRED);
		$this->addArgument(self::DISPLAY_NAME, InputArgument::REQUIRED);
		$this->addArgument(self::EMAIL, InputArgument::REQUIRED);
		$this->addArgument(self::VEHICLE_TYPE, InputArgument::REQUIRED);
		$this->addArgument(self::VEHICLE_MAKE, InputArgument::REQUIRED);
		$this->addArgument(self::VEHICLE_MODEL, InputArgument::REQUIRED);
		$this->addOption(self::CONTACT, null, InputOption::VALUE_REQUIRED);
		$this->addOption(self::IS_ELECTRIC, null, InputOption::VALUE_REQUIRED);
		$this->addOption(self::RANGE, null, InputOption::VALUE_REQUIRED);
		$this->addOption(self::SEATING_CAPACITY, null, InputOption::VALUE_REQUIRED);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = (string)$input->getArgument(self::UID);
		$buildingId = (int)$input->getArgument(self::BUILDING_ID);
		$displayName = (string)$input->getArgument(self::DISPLAY_NAME);
		$email = (string)$input->getArgument(self::EMAIL);
		$contact = (string)$input->getOption(self::CONTACT);
		$vehicleType = (string)$input->getArgument(self::VEHICLE_TYPE);
		$vehicleMake = (string)$input->getArgument(self::VEHICLE_MAKE);
		$model = (string)$input->getArgument(self::VEHICLE_MODEL);
		$isElectric = (bool)$input->getOption(self::IS_ELECTRIC);
		$range = (int)$input->getOption(self::RANGE);
		$seating = (int)$input->getOption(self::SEATING_CAPACITY);

		$this->uidValidationService->validateUidAndThrow($uid);

		$vehicleModel = new VehicleModel();
		$vehicleModel->setBuildingId($buildingId);
		$vehicleModel->setUid($uid);
		$vehicleModel->setDisplayName($displayName);
		$vehicleModel->setEmail($email);
		$vehicleModel->setContactPersonUserId($contact);
		$vehicleModel->setVehicleType($vehicleType);
		$vehicleModel->setVehicleMake($vehicleMake);
		$vehicleModel->setVehicleModel($model);
		$vehicleModel->setIsElectric($isElectric);
		$vehicleModel->setRange($range);
		$vehicleModel->setSeatingCapacity($seating);

		try {
			$inserted = $this->vehicleMapper->insert($vehicleModel);
			$output->writeln('<info>Created new Vehicle with ID:</info>');
			$output->writeln('<info>' . $inserted->getId() . '</info>');
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry: ' . $e->getMessage() . '</error>');
			return 1;
		}

		if (method_exists($this->resourceManager, 'update')) {
			$this->resourceManager->update();
		}

		return 0;
	}
}
