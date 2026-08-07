<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\ISecureRandom;

class ResourceService {
	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
		// ...
	];

	public function __construct(
		private ResourceMapper $resourceMapper,
		private RestrictionMapper $restrictionMapper,
		private BuildingMapper $buildingMapper,
		private ISecureRandom $secureRandom,
	) {
	}

	/**
	 * List all resources
	 */
	public function listResources(): array {
		return $this->resourceMapper->findAll();
	}

	/**
	 * Create a resource
	 *
	 * @throws DoesNotExistException If the building does not exist.
	 * @throws EmailAlreadyUsedException If another resource already uses the email address.
	 */
	public function createResource(string $name, int $buildingId, string $email = '', string $resourceType = 'default'): ResourceModel {
		// A resource without an existing building cannot be resolved by the calendar backend
		$this->buildingMapper->find($buildingId);

		try {
			$this->resourceMapper->findByEmail($email);
			throw new EmailAlreadyUsedException('A resource with this email address already exists');
		} catch (DoesNotExistException) {
			// The email address is still free
		}

		$resource = new ResourceModel();
		$resource->setUid($this->secureRandom->generate(32, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS));
		$resource->setDisplayName($name);
		$resource->setEmail($email);
		$resource->setResourceType($resourceType);
		$resource->setBuildingId($buildingId);
		return $this->resourceMapper->insert($resource);
	}

	/**
	 * Delete a resource
	 *
	 * @throws DoesNotExistException If the resource does not exist.
	 */
	public function deleteResource(int $id): void {
		$resource = $this->resourceMapper->find($id);
		$this->resourceMapper->delete($resource);
	}
}
