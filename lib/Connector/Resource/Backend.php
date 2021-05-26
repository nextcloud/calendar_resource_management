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
namespace OCA\CalendarResourceManagement\Connector\Resource;

use OCA\CalendarResourceManagement\Db;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Calendar\BackendTemporarilyUnavailableException;
use OCP\Calendar\Resource\IBackend;
use OCP\Calendar\Resource\IResource;
use OCP\ILogger;

/**
 * Class Backend
 *
 * @package OCA\CalendarResourceManagement\Connector\Resource
 */
class Backend implements IBackend {

	/** @var string */
	private $appName;

	/** @var Db\ResourceMapper */
	private $resourceMapper;

	/** @var Db\VehicleMapper */
	private $vehicleMapper;

	/** @var Db\RestrictionMapper */
	private $restrictionMapper;

	/** @var ILogger */
	private $logger;

	/**
	 * Backend constructor.
	 *
	 * @param string $appName
	 * @param Db\ResourceMapper $resourceMapper
	 * @param Db\VehicleMapper $vehicleMapper
	 * @param Db\RestrictionMapper $restrictionMapper
	 * @param ILogger $logger
	 */
	public function __construct(string $appName,
								Db\ResourceMapper $resourceMapper,
								Db\VehicleMapper $vehicleMapper,
								Db\RestrictionMapper $restrictionMapper,
								ILogger $logger) {
		$this->appName = $appName;
		$this->resourceMapper = $resourceMapper;
		$this->vehicleMapper = $vehicleMapper;
		$this->restrictionMapper = $restrictionMapper;
		$this->logger = $logger;
	}

	/**
	 * @return array
	 */
	public function getAllResources(): array {
		return [];
	}

	/**
	 * @param string $id
	 * @return IResource|null
	 * @throws BackendTemporarilyUnavailableException
	 */
	public function getResource($id):?IResource {
		$resource = $this->getResourceEntity($id);
		if ($resource) {
			$restrictions = $this->restrictionMapper->findAllByEntityTypeAndId('resource', $resource->getId());
			return new ResourceObject($resource, $restrictions, $this);
		}

		$vehicle = $this->getVehicleEntity($id);
		if ($vehicle) {
			$restrictions = $this->restrictionMapper->findAllByEntityTypeAndId('vehicle', $vehicle->getId());
			return new Vehicle($vehicle, $restrictions, $this);
		}

		return null;
	}

	/**
	 * @return String[]
	 */
	public function listAllResources(): array {
		return array_merge(
			$this->resourceMapper->findAllUIDs(),
			$this->vehicleMapper->findAllUIDs()
		);
	}

	/**
	 * @return string
	 */
	public function getBackendIdentifier(): string {
		return $this->appName;
	}

	/**
	 * @param $uid
	 * @return Db\ResourceModel|null
	 * @throws BackendTemporarilyUnavailableException
	 */
	private function getResourceEntity($uid):?Db\ResourceModel {
		try {
			return $this->resourceMapper->findByUID($uid);
		} catch (DoesNotExistException $ex) {
			return null;
		} catch (\Exception $ex) {
			$this->logger->logException($ex);
			throw new BackendTemporarilyUnavailableException($ex->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @return Db\VehicleModel|null
	 * @throws BackendTemporarilyUnavailableException
	 */
	private function getVehicleEntity($uid):?Db\VehicleModel {
		try {
			return $this->vehicleMapper->findByUID($uid);
		} catch (DoesNotExistException $ex) {
			return null;
		} catch (\Exception $ex) {
			$this->logger->logException($ex);
			throw new BackendTemporarilyUnavailableException($ex->getMessage());
		}
	}
}
