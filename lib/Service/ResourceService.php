<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;

class ResourceService {
	/** @var ResourceMapper */
	private $resourceMapper;

	/** @var RestrictionMapper */
	private $restrictionMapper;

	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
		// ...
	];

	/**
	 * ResourceService constructor.
	 * @param ResourceMapper $resourceMapper
	 * @param RestrictionMapper $restrictionMapper
	 */
	public function __construct(ResourceMapper $resourceMapper,
		RestrictionMapper $restrictionMapper) {
		$this->resourceMapper = $resourceMapper;
		$this->restrictionMapper = $restrictionMapper;
	}
	/**
	 * List all resources
	 */
	public function listResources(): array {
		return $this->resourceMapper->findAll();
	}

	/**
	 * Create a resource
	 */
	public function createResource(string $name, string $email = '', string $resourceType = 'default', int $buildingId = 1): ResourceModel {
		$resource = new ResourceModel();
		$resource->setUid(bin2hex(random_bytes(16)));
		$resource->setDisplayName($name);
		$resource->setEmail($email);
		$resource->setResourceType($resourceType);
		$resource->setBuildingId($buildingId);
		// Additional fields can be set here
		return $this->resourceMapper->insert($resource);
	}

	/**
	 * Delete a resource
	 */
	public function deleteResource(int $id): void {
		$resource = $this->resourceMapper->find($id);
		$this->resourceMapper->delete($resource);
	}
}
