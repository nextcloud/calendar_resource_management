<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Connector\Room;

use OCA\CalendarResourceManagement\Db;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Calendar\BackendTemporarilyUnavailableException;
use OCP\Calendar\Room\IBackend;
use OCP\Calendar\Room\IRoom;
use Psr\Log\LoggerInterface;

/**
 * Class Backend
 *
 * @package OCA\CalendarResourceManagement\Connector\Room
 */
class Backend implements IBackend {

	/**
	 * Backend constructor.
	 *
	 * @param string $appName
	 * @param Db\RoomMapper $mapper
	 * @param Db\RestrictionMapper $restrictionMapper
	 * @param Db\StoryMapper $storyMapper
	 * @param Db\BuildingMapper $buildingMapper
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private string $appName,
		private Db\RoomMapper $mapper,
		private Db\RestrictionMapper $restrictionMapper,
		private Db\StoryMapper $storyMapper,
		private Db\BuildingMapper $buildingMapper,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @return array
	 */
	public function getAllRooms(): array {
		return [];
	}

	/**
	 * @param string $id
	 * @return IRoom|null
	 * @throws BackendTemporarilyUnavailableException
	 */
	public function getRoom($id):?IRoom {
		try {
			$room = $this->mapper->findByUID($id);
		} catch (DoesNotExistException $ex) {
			return null;
		} catch (\Exception $ex) {
			$this->logger->error('Could not fetch room with id ' . $id, ['exception' => $ex]);
			throw new BackendTemporarilyUnavailableException($ex->getMessage());
		}

		$restrictions = $this->restrictionMapper->findAllByEntityTypeAndId('room', $room->getId());

		try {
			$story = $this->storyMapper->find($room->getStoryId());
			$building = $this->buildingMapper->find($story->getBuildingId());
		} catch (\Exception $ex) {
			$this->logger->error($ex->getMessage(), ['exception' => $ex]);
			throw new BackendTemporarilyUnavailableException($ex->getMessage());
		}

		return new Room($room, $story, $building, $restrictions, $this);
	}

	/**
	 * @return String[]
	 */
	public function listAllRooms(): array {
		return $this->mapper->findAllUIDs();
	}

	/**
	 * @return string
	 */
	public function getBackendIdentifier(): string {
		return $this->appName;
	}
}
