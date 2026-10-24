<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Controller;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCA\CalendarResourceManagement\Http\PaginationParams;
use OCA\CalendarResourceManagement\Service\ResourceService;
use OCA\CalendarResourceManagement\Service\RestrictionService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\DB\Exception;
use OCP\IRequest;

class ResourceController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ResourceService $service,
		private RestrictionService $restrictions,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * One page of resources as `{items, total, page, limit, pages}`.
	 *
	 * Page size is fixed at {@see PaginationParams::DEFAULT_LIMIT}.
	 *
	 * @param int|null $page 1-based, clamped into range
	 * @return DataResponse the pagination envelope
	 */
	public function index(?int $id = null,
		?int $buildingId = null,
		?string $uid = null,
		?string $name = null,
		?string $email = null,
		?string $resourceType = null,
		?string $contactPersonUserId = null,
		?bool $restricted = null,
		?int $page = null): DataResponse {
		$filters = [
			'id' => $id,
			'buildingId' => $buildingId,
			'uid' => $uid,
			'name' => $name,
			'email' => $email,
			'resourceType' => $resourceType,
			'contactPersonUserId' => $contactPersonUserId,
			'restricted' => $restricted,
		];

		$pagination = new PaginationParams($page, $this->service->countAll($filters));
		$items = $this->service->findAll($filters, PaginationParams::DEFAULT_LIMIT, $pagination->offset());
		$groups = [];
		foreach ($this->restrictions->findForEntities(Constants::RESOURCE, $items) as $restriction) {
			$groups[$restriction->getEntityId()][] = $restriction->getGroupId();
		}

		return new DataResponse([
			'items' => array_map(
				fn (ResourceModel $resource) => $this->addGroupRestrictions($resource, $groups[$resource->getId()] ?? []),
				$items,
			),
			'total' => $pagination->total,
			'page' => $pagination->page,
			'limit' => PaginationParams::DEFAULT_LIMIT,
			'pages' => $pagination->pages,
		]);
	}

	/**
	 * Every resource type in use across the whole table, as a bare list of strings.
	 *
	 * @return DataResponse the distinct types, ascending; `[]` when there are none
	 */
	public function resourceTypes(): DataResponse {
		return new DataResponse($this->service->resourceTypes());
	}

	public function create(int $buildingId,
		string $name,
		string $resourceType,
		?string $email = null,
		?string $contactPersonUserId = null,
		bool $restricted = false,
		?array $groupRestrictions = null): DataResponse {
		try {
			$resource = $this->service->create(
				$buildingId,
				$name,
				$email,
				$resourceType,
				$contactPersonUserId,
				$restricted,
				$groupRestrictions ?? [],
			);

			return new DataResponse($this->addGroupRestrictions($resource, $this->fetchGroupIds($resource)));
		} catch (\InvalidArgumentException $e) {
			throw new OCSBadRequestException($e->getMessage());
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new OCSBadRequestException('An entity with this UID or email already exists');
			}
			throw $e;
		}
	}

	public function update(int $id,
		int $buildingId,
		string $name,
		string $resourceType,
		?string $email = null,
		?string $contactPersonUserId = null,
		?bool $restricted = null,
		?array $groupRestrictions = null): DataResponse {
		try {
			$resource = $this->service->update(
				$id,
				$buildingId,
				$name,
				$email,
				$resourceType,
				$contactPersonUserId,
				$restricted,
				$groupRestrictions,
			);

			return new DataResponse($this->addGroupRestrictions($resource, $this->fetchGroupIds($resource)));
		} catch (\InvalidArgumentException $e) {
			throw new OCSBadRequestException($e->getMessage());
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new OCSBadRequestException('An entity with this UID or email already exists');
			}
			throw $e;
		}
	}

	public function destroy(int $id): DataResponse {
		try {
			$this->service->delete($id);
			return new DataResponse(null);
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException();
		}
	}

	/**
	 * The resource as the API returns it: its JSON plus its group ids.
	 *
	 * @param string[] $groupIds the resource's group ids
	 * @return array<string, mixed>
	 */
	private function addGroupRestrictions(ResourceModel $resource, array $groupIds): array {
		return $resource->jsonSerialize() + ['groupRestrictions' => $groupIds];
	}

	/**
	 * Load the group ids of one resource, for the create and update responses.
	 *
	 * @return string[]
	 */
	private function fetchGroupIds(ResourceModel $resource): array {
		return array_map(
			static fn (RestrictionModel $restriction) => $restriction->getGroupId(),
			$this->restrictions->findFor(Constants::RESOURCE, $resource->getId()),
		);
	}
}
