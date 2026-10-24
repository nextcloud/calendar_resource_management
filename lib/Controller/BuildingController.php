<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Controller;

use OCA\CalendarResourceManagement\Http\PaginationParams;
use OCA\CalendarResourceManagement\Service\BuildingService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\DB\Exception;
use OCP\IRequest;

class BuildingController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private BuildingService $service,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * One page of buildings as `{items, total, page, limit, pages}`.
	 *
	 * Page size is fixed at {@see PaginationParams::DEFAULT_LIMIT}.
	 *
	 * @param int|null $page 1-based, clamped into range
	 * @return DataResponse the pagination envelope
	 */
	public function index(?int $id = null,
		?string $displayName = null,
		?string $description = null,
		?string $address = null,
		?bool $isWheelchairAccessible = null,
		?int $page = null): DataResponse {
		$filters = [
			'id' => $id,
			'displayName' => $displayName,
			'description' => $description,
			'address' => $address,
			'isWheelchairAccessible' => $isWheelchairAccessible,
		];

		$pagination = new PaginationParams($page, $this->service->countAll($filters));

		return new DataResponse([
			'items' => $this->service->findAll($filters, PaginationParams::DEFAULT_LIMIT, $pagination->offset()),
			'total' => $pagination->total,
			'page' => $pagination->page,
			'limit' => PaginationParams::DEFAULT_LIMIT,
			'pages' => $pagination->pages,
		]);
	}

	/**
	 * Every matching building as `{id, displayName}`, unpaginated, for pickers.
	 *
	 * Takes the same filters as `index()`.
	 *
	 * @param int|null $id only this building
	 * @param string|null $displayName substring match on the name
	 * @param string|null $description substring match on the description
	 * @param string|null $address substring match on the address
	 * @param bool|null $isWheelchairAccessible only accessible, or only not
	 * @return DataResponse the options, ascending by name; `[]` when there are none
	 */
	public function buildingOptions(?int $id = null,
		?string $displayName = null,
		?string $description = null,
		?string $address = null,
		?bool $isWheelchairAccessible = null): DataResponse {
		return new DataResponse($this->service->buildingOptions([
			'id' => $id,
			'displayName' => $displayName,
			'description' => $description,
			'address' => $address,
			'isWheelchairAccessible' => $isWheelchairAccessible,
		]));
	}

	public function create(string $displayName,
		?string $description = null,
		?string $address = null,
		bool $isWheelchairAccessible = false): DataResponse {
		try {
			return new DataResponse($this->service->create(
				$displayName,
				$description,
				$address,
				$isWheelchairAccessible,
			));
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
		string $displayName,
		?string $description = null,
		?string $address = null,
		bool $isWheelchairAccessible = false): DataResponse {
		try {
			return new DataResponse($this->service->update(
				$id,
				$displayName,
				$description,
				$address,
				$isWheelchairAccessible,
			));
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
}
