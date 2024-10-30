<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListResources extends Command {
	/** @var BuildingMapper */
	private $buildingMapper;

	/** @var ResourceMapper */
	private $resourceMapper;

	/** @var RestrictionMapper */
	private $restrictionMapper;

	/** @var RoomMapper */
	private $roomMapper;

	/** @var StoryMapper */
	private $storyMapper;

	/** @var VehicleMapper */
	private $vehicleMapper;

	public function __construct(LoggerInterface $logger,
		BuildingMapper $buildingMapper,
		ResourceMapper $resourceMapper,
		RestrictionMapper $restrictionMapper,
		RoomMapper $roomMapper,
		StoryMapper $storyMapper,
		VehicleMapper $vehicleMapper) {
		parent::__construct();
		$this->buildingMapper = $buildingMapper;
		$this->resourceMapper = $resourceMapper;
		$this->restrictionMapper = $restrictionMapper;
		$this->roomMapper = $roomMapper;
		$this->storyMapper = $storyMapper;
		$this->vehicleMapper = $vehicleMapper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:resources:list');
		$this->setDescription('List all resources');
	}

	/** @return int */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		// Buildings
		$table = new Table($output);
		$output->writeln('<info><options=bold>Buildings:</></info>');
		$table->setHeaders(
			[
				'ID',
				'Name',
				'Address',
				'Description',
				'Wheelchair Accessible'
			]
		);
		$buildings = $this->buildingMapper->findAll();
		$row = 1;
		foreach ($buildings as $building) {
			$table->setRow($row,
				[
					$building->getId(),
					$building->getDisplayName(),
					$building->getAddress(),
					$building->getDescription(),
					($building->getIsWheelchairAccessible() ? 'yes' : 'no')
				]
			);
			$row++;
		}
		$table->render();

		// Stories
		$table = new Table($output);
		$output->writeln('<info><options=bold>Stories:</></info>');
		$table->setHeaders(
			[
				'ID',
				'Located in',
				'Display Name'
			]
		);
		foreach ($buildings as $building) {
			$stories = $this->storyMapper->findAllByBuilding($building->getId());
			/** @var StoryModel $story */
			foreach ($stories as $story) {
				$table->setRow($row,
					[
						$story->getId(),
						$building->getDisplayName(),
						$story->getDisplayName(),
					]
				);
				$row++;
			}
		}
		$table->render();

		// Rooms
		$table = new Table($output);
		$output->writeln('<info><options=bold>Rooms:</></info>');
		foreach ($buildings as $building) {
			$stories = $this->storyMapper->findAllByBuilding($building->getId());
			$table->setHeaders(
				[
					'ID',
					'UID',
					'Name',
					'Located in',
					'Email',
					'Room Type',
					'Contact Person',
					'Capacity',
					'Room Number',
					'Phone',
					'Video Conferencing',
					'TV',
					'Projector',
					'Whiteboard',
					'Wheelchair Accessible'
				]
			);
			foreach ($stories as $story) {
				$rooms = $this->roomMapper->findAllByStoryId($story->getId());
				/** @var RoomModel $room */
				foreach ($rooms as $room) {
					$table->setRow($row,
						[
							$room->getId(),
							$room->getUid(),
							$room->getDisplayName(),
							$building->getDisplayName() . ', ' . $story->getDisplayName(),
							$room->getEmail(),
							$room->getRoomType(),
							$room->getContactPersonUserId(),
							$room->getCapacity(),
							$room->getRoomNumber(),
							($room->getHasPhone() ? 'yes' : 'no'),
							($room->getHasVideoConferencing() ? 'yes' : 'no'),
							($room->getHasTv() ? 'yes' : 'no'),
							($room->getHasProjector() ? 'yes' : 'no'),
							($room->getHasWhiteboard() ? 'yes' : 'no'),
							($room->getIsWheelchairAccessible() ? 'yes' : 'no'),
						]
					);
					$row++;
				}
				$row++;
			}
		}
		$table->render();

		// Resources
		$table = new Table($output);
		$output->writeln('<info><options=bold>Resources:</></info>');
		$table->setHeaders(
			[
				'ID',
				'UID',
				'Name',
				'Located in',
				'Contact Person',
				'Type'
			]
		);
		foreach ($buildings as $building) {
			$resources = $this->resourceMapper->findAllByBuilding($building->getId());
			/** @var ResourceModel $resource */
			foreach ($resources as $resource) {
				$table->setRow($row,
					[
						$resource->getId(),
						$resource->getUid(),
						$resource->getDisplayName(),
						$building->getId() . ' ' . $building->getDisplayName(),
						$resource->getContactPersonUserId(),
						$resource->getResourceType()
					]
				);
				$row++;
			}
		}
		$table->render();

		// Vehicles
		$table = new Table($output);
		$output->writeln('<info><options=bold>Vehicles:</></info>');
		$table->setHeaders(
			[
				'ID',
				'UID',
				'Name',
				'Located in',
				'Email',
				'Contact Person',
				'Type',
				'Make',
				'Model',
				'Electric',
				'Range',
				'Capacity'
			]
		);
		foreach ($buildings as $building) {
			$vehicles = $this->vehicleMapper->findAllByBuilding($building->getId());
			/** @var VehicleModel $vehicle */
			foreach ($vehicles as $vehicle) {
				$table->setRow($row,
					[
						$vehicle->getId(),
						$vehicle->getUid(),
						$vehicle->getDisplayName(),
						$building->getId() . ' ' . $building->getDisplayName(),
						$vehicle->getEmail(),
						$vehicle->getContactPersonUserId(),
						$vehicle->getVehicleType(),
						$vehicle->getVehicleMake(),
						$vehicle->getVehicleModel(),
						($vehicle->getIsElectric() ? 'yes' : 'no'),
						$vehicle->getRange(),
						$vehicle->getSeatingCapacity()
					]
				);
				$row++;
			}
		}
		$table->render();

		// Restrictions
		$table = new Table($output);
		$output->writeln('<info><options=bold>Restrictions:</></info>');
		$table->setHeaders(
			[
				'ID',
				'Entity',
				'Entity ID',
				'Restricted To'
			]
		);

		$restrictions = $this->restrictionMapper->findAll();
		/** @var RestrictionModel $restriction */
		foreach ($restrictions as $restriction) {
			$table->setRow($row,
				[
					$restriction->getId(),
					$restriction->getEntityType(),
					$restriction->getEntityId(),
					$restriction->getGroupId(),
				]
			);
			$row++;
		}
		$table->render();

		return 0;
	}
}
