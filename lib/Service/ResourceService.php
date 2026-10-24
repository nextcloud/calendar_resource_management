<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use InvalidArgumentException;
use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Schema\Entities\Resources;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\TTransactional;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\DB\Exception;
use OCP\IDBConnection;

class ResourceService {
	use TTransactional;

	public function __construct(
		private ResourceMapper $resourceMapper,
		private UidGenerationService $uidGeneration,
		private EmailGenerationService $emailGeneration,
		private FieldValidationService $fieldValidation,
		private Resources $resources,
		private IResourceManager $resourceManager,
		private RestrictionService $restrictions,
		private IDBConnection $db,
	) {
	}

	/**
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @param int|null $limit null for all
	 * @param int|null $offset null for none
	 * @return ResourceModel[]
	 */
	public function findAll(array $filters = [], ?int $limit = null, ?int $offset = null): array {
		return $this->resourceMapper->findAllFiltered(
			$this->resources->criteria($filters),
			limit: $limit,
			offset: $offset,
		);
	}

	/**
	 * @param array<string, mixed> $filters keyed by parameter name
	 * @return int the number of matching rows
	 * @throws Exception on a database error
	 */
	public function countAll(array $filters = []): int {
		return $this->resourceMapper->countAllFiltered(
			$this->resources->criteria($filters)
		);
	}

	/**
	 * Distinct resource types in use across the whole table, ascending.
	 *
	 * @return string[] the distinct types, non-empty and re-indexed
	 * @throws Exception on a database error
	 */
	public function resourceTypes(): array {
		$field = $this->resources->field('resourceType');
		if ($field->column === null) {
			return [];
		}
		return $this->resourceMapper->distinctValues($field->column);
	}

	/**
	 * @param string|null $email 
	 * @param string[] $groupRestrictions only allowed when restricted
	 * @throws InvalidArgumentException If input is invalid
	 */
	public function create(int $buildingId,
		string $displayName,
		?string $email,
		string $resourceType,
		?string $contactPersonUserId = null,
		bool $restricted = false,
		array $groupRestrictions = []): ResourceModel {
		$uid = $this->uidGeneration->generateUid();
		$email = $this->emailGeneration->resolveEmail($email, $displayName, Constants::RESOURCE);
		$groupRestrictions = $this->restrictions->normalize($groupRestrictions);
		$this->assertValid($buildingId, $displayName, $email, $contactPersonUserId, $restricted, $groupRestrictions);

		$resource = new ResourceModel();
		$resource->setUid($uid);
		$resource->setBuildingId($buildingId);
		$resource->setDisplayName($displayName);
		$resource->setEmail($email);
		$resource->setResourceType($resourceType);
		$resource->setContactPersonUserId($contactPersonUserId);
		$resource->setRestricted($restricted);

		$resource = $this->atomic(function () use ($resource, $groupRestrictions) {
			$resource = $this->resourceMapper->insert($resource);
			$this->restrictions->create(Constants::RESOURCE, $resource->getId(), $groupRestrictions);
			return $resource;
		}, $this->db);
		$this->refreshManagers();
		return $resource;
	}

	/**
	 * @param string|null $email 
	 * @param string[]|null $groupRestrictions null to keep current
	 * @throws DoesNotExistException If there is no such resource.
	 * @throws InvalidArgumentException If input is invalid 
	 */
	public function update(int $id,
		int $buildingId,
		string $displayName,
		?string $email,
		string $resourceType,
		?string $contactPersonUserId = null,
		?bool $restricted = null,
		?array $groupRestrictions = null): ResourceModel {

		$resource = $this->resourceMapper->find($id);
		$email = $this->emailGeneration->resolveEmail($email, $displayName, Constants::RESOURCE);
		$restricted ??= $resource->isRestricted();
		if ($groupRestrictions !== null) {
			$groupRestrictions = $this->restrictions->normalize($groupRestrictions);
		}
		$this->assertValid($buildingId, $displayName, $email, $contactPersonUserId, $restricted, $groupRestrictions ?? []);
		if (!$restricted) {
			$groupRestrictions = [];
		}

		$resource->setBuildingId($buildingId);
		$resource->setDisplayName($displayName);
		$resource->setEmail($email);
		$resource->setResourceType($resourceType);
		$resource->setContactPersonUserId($contactPersonUserId);
		$resource->setRestricted($restricted);

		$resource = $this->atomic(function () use ($resource, $groupRestrictions) {
			$resource = $this->resourceMapper->update($resource);
			if ($groupRestrictions !== null) {
				$this->restrictions->replace(Constants::RESOURCE, $resource->getId(), $groupRestrictions);
			}
			return $resource;
		}, $this->db);
		$this->refreshManagers();
		return $resource;
	}

	/**
	 * @throws DoesNotExistException If there is no such resource.
	 */
	public function delete(int $id): void {
		$resource = $this->resourceMapper->find($id);
		$this->atomic(function () use ($resource) {
			$this->resourceMapper->delete($resource);
			$this->restrictions->deleteFor(Constants::RESOURCE, $resource->getId());
		}, $this->db);
		$this->refreshManagers();
	}

	/**
	 * @throws InvalidArgumentException If input is invalid 
	 */
	private function assertValid(int $buildingId,
		string $displayName,
		string $email,
		?string $contactPersonUserId,
		bool $restricted,
		array $groupRestrictions): void {
		$this->fieldValidation->assertRequired('Display name', $displayName);
		$this->fieldValidation->assertRequired('Email', $email);
		$this->fieldValidation->assertEmail('Email', $email);
		$this->fieldValidation->assertUserExists('Contact person', $contactPersonUserId);
		$this->fieldValidation->assertBuildingExists($buildingId);
		$this->fieldValidation->assertGroupRestrictions($restricted, $groupRestrictions);
	}

	/**
	 * Nudge Calendar to re-read what it caches from this app's backends.
	 */
	private function refreshManagers(): void {
		if (method_exists($this->resourceManager, 'update')) {
			$this->resourceManager->update();
		}
	}
}
