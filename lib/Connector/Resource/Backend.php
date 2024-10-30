<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Connector\Resource;

use OCA\CalendarResourceManagement\Db;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Calendar\BackendTemporarilyUnavailableException;
use OCP\Calendar\Resource\IBackend;
use OCP\Calendar\Resource\IResource;
use Psr\Log\LoggerInterface;

/**
 * Class Backend
 *
 * @package OCA\CalendarResourceManagement\Connector\Resource
 */
class Backend implements IBackend {

	/**
	 * Backend constructor.
	 *
	 * @param string $appName
	 * @param Db\ResourceMapper $resourceMapper
	 * @param Db\VehicleMapper $vehicleMapper
	 * @param Db\RestrictionMapper $restrictionMapper
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private string $appName,
		private Db\ResourceMapper $resourceMapper,
		private Db\VehicleMapper $vehicleMapper,
		private Db\RestrictionMapper $restrictionMapper,
		private LoggerInterface $logger,
	) {
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
			$this->logger->error('Could not fetch resource entity', ['exception' => $ex]);
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
			$this->logger->error('Could not fetch vehicle entity', ['exception' => $ex]);
			throw new BackendTemporarilyUnavailableException($ex->getMessage());
		}
	}
}
