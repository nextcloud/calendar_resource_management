<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <georg-nextcloud@ehrke.email>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\CalendarResourceManagement\Connector\Room;

use OCA\CalendarResourceManagement\Db;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Calendar\BackendTemporarilyUnavailableException;
use OCP\Calendar\Room\IBackend;
use OCP\Calendar\Room\IRoom;
use OCP\ILogger;

/**
 * Class Backend
 *
 * @package OCA\CalendarResourceManagement\Connector\Room
 */
class Backend implements IBackend {
	/** @var string */
	private $appName;

	/** @var Db\RoomMapper */
	private $mapper;

	/** @var Db\RestrictionMapper */
	private $restrictionMapper;

	/** @var Db\StoryMapper */
	private $storyMapper;

	/** @var Db\BuildingMapper */
	private $buildingMapper;

	/** @var ILogger */
	private $logger;

	/**
	 * Backend constructor.
	 *
	 * @param string $appName
	 * @param Db\RoomMapper $mapper
	 * @param Db\RestrictionMapper $restrictionMapper
	 * @param Db\StoryMapper $storyMapper
	 * @param Db\BuildingMapper $buildingMapper
	 * @param ILogger $logger
	 */
	public function __construct(string $appName,
		Db\RoomMapper $mapper,
		Db\RestrictionMapper $restrictionMapper,
		Db\StoryMapper $storyMapper,
		Db\BuildingMapper $buildingMapper,
		ILogger $logger) {
		$this->appName = $appName;
		$this->mapper = $mapper;
		$this->restrictionMapper = $restrictionMapper;
		$this->storyMapper = $storyMapper;
		$this->buildingMapper = $buildingMapper;
		$this->logger = $logger;
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
			$this->logger->logException($ex);
			throw new BackendTemporarilyUnavailableException($ex->getMessage());
		}

		$restrictions = $this->restrictionMapper->findAllByEntityTypeAndId('room', $room->getId());

		try {
			$story = $this->storyMapper->find($room->getStoryId());
			$building = $this->buildingMapper->find($story->getBuildingId());
		} catch (\Exception $ex) {
			$this->logger->logException($ex);
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
