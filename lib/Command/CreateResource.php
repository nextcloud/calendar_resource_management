<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Service\UidValidationService;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateResource extends Command {
	private const UID = 'uid';
	private const BUILDING_ID = 'building_id';
	private const DISPLAY_NAME = 'display_name';
	private const EMAIL = 'email';
	private const TYPE = 'resource_type';
	private const CONTACT = 'contact-person-user-id';

	/** @var LoggerInterface */
	private $logger;

	/** @var ResourceMapper */
	private $resourceMapper;

	public function __construct(
		LoggerInterface $logger,
		ResourceMapper $resourceMapper,
		private IResourceManager $resourceManager,
		private UidValidationService $uidValidationService,
	) {
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
		$this->addOption(self::CONTACT, null, InputOption::VALUE_REQUIRED);
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
		$contact = (string)$input->getOption(self::CONTACT);

		$this->uidValidationService->validateUidAndThrow($uid);

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
			$output->writeln('<info>' . $inserted->getId() . '</info>');
		} catch (Exception $e) {
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
