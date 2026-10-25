<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
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
		private DefaultShareMapper $defaultShareMapper,
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
	 * Change a resource and its restrictions
	 *
	 * @param string[] $restrictionGroupIds Without any, a restricted resource is hidden from everybody
	 * @throws DoesNotExistException If the resource or the building does not exist.
	 * @throws EmailAlreadyUsedException If another resource already uses the email address.
	 */
	public function updateResource(
		int $id,
		string $name,
		int $buildingId,
		string $email,
		string $resourceType,
		bool $restricted,
		array $restrictionGroupIds,
	): ResourceModel {
		$resource = $this->resourceMapper->find($id);
		$this->buildingMapper->find($buildingId);

		try {
			if ($this->resourceMapper->findByEmail($email)->getId() !== $id) {
				throw new EmailAlreadyUsedException('A resource with this email address already exists');
			}
		} catch (DoesNotExistException) {
			// The email address is still free
		}

		$resource->setDisplayName($name);
		$resource->setEmail($email);
		$resource->setResourceType($resourceType);
		$resource->setBuildingId($buildingId);
		$resource->setRestricted($restricted);
		$resource = $this->resourceMapper->update($resource);
		$this->restrictionMapper->setGroupIds(Constants::RESOURCE, $id, $restricted ? $restrictionGroupIds : []);

		return $resource;
	}

	/**
	 * @return array<int, string[]> The restriction groups per resource id
	 */
	public function listRestrictionGroupIds(): array {
		return $this->restrictionMapper->findGroupIdsByEntityType(Constants::RESOURCE);
	}

	/**
	 * Delete a resource
	 *
	 * @throws DoesNotExistException If the resource does not exist.
	 */
	public function deleteResource(int $id): void {
		$resource = $this->resourceMapper->find($id);
		$this->resourceMapper->delete($resource);
		$this->restrictionMapper->deleteAllByEntityTypeAndId(Constants::RESOURCE, $id);
		$this->defaultShareMapper->deleteAllByEntityTypeAndId(Constants::RESOURCE, $id);
	}
}
