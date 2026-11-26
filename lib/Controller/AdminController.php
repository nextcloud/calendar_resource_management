<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Controller;

use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCA\CalendarResourceManagement\Service\BuildingService;
use OCA\CalendarResourceManagement\Service\ResourceService;
use OCA\CalendarResourceManagement\Service\RoomService;
use OCA\CalendarResourceManagement\Service\StoryService;
use OCA\CalendarResourceManagement\Service\VehicleService;
use OCA\CalendarResourceManagement\Settings\AdminSettings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Throwable;

class AdminController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private BuildingService $buildingService,
		private StoryService $storyService,
		private RoomService $roomService,
		private ResourceService $resourceService,
		private VehicleService $vehicleService,
		private IRoomManager $roomManager,
		private IResourceManager $resourceManager,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private IL10N $l10n,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Accounts that can be picked as the contact person of a room.
	 */
	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/users')]
	public function getUsers(string $search = '', int $limit = 25): JSONResponse {
		$users = $this->userManager->searchDisplayName($search, max(1, min($limit, 100)));

		return new JSONResponse(array_map(
			static fn (IUser $user): array => [
				'id' => $user->getUID(),
				'displayName' => $user->getDisplayName(),
			],
			array_values($users),
		));
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/buildings')]
	public function getBuildings(): JSONResponse {
		return new JSONResponse($this->buildingService->listBuildings());
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'POST', url: '/admin/buildings')]
	public function createBuilding(string $name = '', string $address = ''): JSONResponse {
		if (trim($name) === '') {
			return $this->error($this->l10n->t('A name is required'), Http::STATUS_BAD_REQUEST);
		}

		try {
			$building = $this->buildingService->createBuilding($name, $address);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		return new JSONResponse($building, Http::STATUS_CREATED);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/buildings/{id}')]
	public function updateBuilding(int $id, string $name = '', string $address = ''): JSONResponse {
		if (trim($name) === '') {
			return $this->error($this->l10n->t('A name is required'), Http::STATUS_BAD_REQUEST);
		}

		try {
			$building = $this->buildingService->updateBuilding($id, $name, $address);
		} catch (DoesNotExistException) {
			return $this->error($this->l10n->t('The building does not exist'), Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		// Rooms expose the building name and address in their metadata
		$this->roomManager->update();

		return new JSONResponse($building);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/buildings/{id}')]
	public function deleteBuilding(int $id): JSONResponse {
		try {
			$this->buildingService->deleteBuilding($id);
		} catch (DoesNotExistException) {
			return $this->error($this->l10n->t('The building does not exist'), Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		$this->roomManager->update();
		$this->resourceManager->update();

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/stories')]
	public function getStories(): JSONResponse {
		return new JSONResponse($this->storyService->listStories());
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'POST', url: '/admin/stories')]
	public function createStory(string $name = '', ?int $buildingId = null): JSONResponse {
		if (trim($name) === '') {
			return $this->error($this->l10n->t('A name is required'), Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error($this->l10n->t('A building has to be selected'), Http::STATUS_BAD_REQUEST);
		}

		try {
			$story = $this->storyService->createStory($name, $buildingId);
		} catch (ServiceException $e) {
			return $this->serviceError($e);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		return new JSONResponse($story, Http::STATUS_CREATED);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/stories/{id}')]
	public function updateStory(int $id, string $name = '', ?int $buildingId = null): JSONResponse {
		if (trim($name) === '') {
			return $this->error($this->l10n->t('A name is required'), Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error($this->l10n->t('A building has to be selected'), Http::STATUS_BAD_REQUEST);
		}

		try {
			$story = $this->storyService->updateStory($id, $name, $buildingId);
		} catch (DoesNotExistException) {
			return $this->error($this->l10n->t('The floor does not exist'), Http::STATUS_NOT_FOUND);
		} catch (ServiceException $e) {
			return $this->serviceError($e);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		// Rooms expose the story name and their building in their metadata
		$this->roomManager->update();

		return new JSONResponse($story);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/stories/{id}')]
	public function deleteStory(int $id): JSONResponse {
		try {
			$this->storyService->deleteStory($id);
		} catch (DoesNotExistException) {
			return $this->error($this->l10n->t('The floor does not exist'), Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/rooms')]
	public function getRooms(): JSONResponse {
		return new JSONResponse($this->roomService->listRooms());
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'POST', url: '/admin/rooms')]
	public function createRoom(
		string $name = '',
		?int $storyId = null,
		string $email = '',
		string $roomType = 'default',
		string $roomNumber = '',
		string $contactPersonUserId = '',
		?int $capacity = null,
		bool $hasPhone = false,
		bool $hasVideo = false,
		bool $hasTv = false,
		bool $hasProjector = false,
		bool $hasWhiteboard = false,
		bool $wheelchairAccessible = false,
	): JSONResponse {
		if (trim($name) === '') {
			return $this->error($this->l10n->t('A name is required'), Http::STATUS_BAD_REQUEST);
		}
		if ($storyId === null) {
			return $this->error($this->l10n->t('A floor has to be selected'), Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error($this->l10n->t('A valid email address is required'), Http::STATUS_BAD_REQUEST);
		}

		try {
			$room = $this->roomService->createRoom(
				$name,
				$storyId,
				$email,
				$roomType,
				$roomNumber,
				$contactPersonUserId,
				$capacity,
				$hasPhone,
				$hasVideo,
				$hasTv,
				$hasProjector,
				$hasWhiteboard,
				$wheelchairAccessible,
			);
		} catch (ServiceException $e) {
			return $this->serviceError($e);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		$this->roomManager->update();

		return new JSONResponse($room, Http::STATUS_CREATED);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/rooms/{id}')]
	public function updateRoom(
		int $id,
		string $name = '',
		?int $storyId = null,
		string $email = '',
		string $roomType = 'default',
		string $roomNumber = '',
		string $contactPersonUserId = '',
		?int $capacity = null,
		bool $hasPhone = false,
		bool $hasVideo = false,
		bool $hasTv = false,
		bool $hasProjector = false,
		bool $hasWhiteboard = false,
		bool $wheelchairAccessible = false,
	): JSONResponse {
		if (trim($name) === '') {
			return $this->error($this->l10n->t('A name is required'), Http::STATUS_BAD_REQUEST);
		}
		if ($storyId === null) {
			return $this->error($this->l10n->t('A floor has to be selected'), Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error($this->l10n->t('A valid email address is required'), Http::STATUS_BAD_REQUEST);
		}

		try {
			$room = $this->roomService->updateRoom(
				$id,
				$name,
				$storyId,
				$email,
				$roomType,
				$roomNumber,
				$contactPersonUserId,
				$capacity,
				$hasPhone,
				$hasVideo,
				$hasTv,
				$hasProjector,
				$hasWhiteboard,
				$wheelchairAccessible,
			);
		} catch (DoesNotExistException) {
			return $this->error($this->l10n->t('The room does not exist'), Http::STATUS_NOT_FOUND);
		} catch (ServiceException $e) {
			return $this->serviceError($e);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		$this->roomManager->update();

		return new JSONResponse($room);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/rooms/{id}')]
	public function deleteRoom(int $id): JSONResponse {
		try {
			$this->roomService->deleteRoom($id);
		} catch (DoesNotExistException) {
			return $this->error($this->l10n->t('The room does not exist'), Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		$this->roomManager->update();

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/resources')]
	public function getResources(): JSONResponse {
		return new JSONResponse($this->resourceService->listResources());
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'POST', url: '/admin/resources')]
	public function createResource(string $name = '', ?int $buildingId = null, string $email = '', string $resourceType = 'default'): JSONResponse {
		if (trim($name) === '') {
			return $this->error($this->l10n->t('A name is required'), Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error($this->l10n->t('A building has to be selected'), Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error($this->l10n->t('A valid email address is required'), Http::STATUS_BAD_REQUEST);
		}

		try {
			$resource = $this->resourceService->createResource($name, $buildingId, $email, $resourceType);
		} catch (ServiceException $e) {
			return $this->serviceError($e);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		$this->resourceManager->update();

		return new JSONResponse($resource, Http::STATUS_CREATED);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/resources/{id}')]
	public function updateResource(int $id, string $name = '', ?int $buildingId = null, string $email = '', string $resourceType = 'default'): JSONResponse {
		if (trim($name) === '') {
			return $this->error($this->l10n->t('A name is required'), Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error($this->l10n->t('A building has to be selected'), Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error($this->l10n->t('A valid email address is required'), Http::STATUS_BAD_REQUEST);
		}

		try {
			$resource = $this->resourceService->updateResource($id, $name, $buildingId, $email, $resourceType);
		} catch (DoesNotExistException) {
			return $this->error($this->l10n->t('The resource does not exist'), Http::STATUS_NOT_FOUND);
		} catch (ServiceException $e) {
			return $this->serviceError($e);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		$this->resourceManager->update();

		return new JSONResponse($resource);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/resources/{id}')]
	public function deleteResource(int $id): JSONResponse {
		try {
			$this->resourceService->deleteResource($id);
		} catch (DoesNotExistException) {
			return $this->error($this->l10n->t('The resource does not exist'), Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError($e);
		}

		$this->resourceManager->update();

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/vehicles')]
	public function getVehicles(): JSONResponse {
		return new JSONResponse($this->vehicleService->listVehicles());
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'POST', url: '/admin/vehicles')]
	public function createVehicle(string $name = '', ?int $buildingId = null, string $email = '', string $vehicleType = 'car', string $vehicleMake = '', string $vehicleModel = '', string $contactPersonUserId = '', bool $isElectric = false, ?int $range = null, ?int $seatingCapacity = null): JSONResponse {
		$error = $this->validateVehicle($name, $buildingId, $email, $vehicleType, $vehicleMake, $vehicleModel, $contactPersonUserId, $range, $seatingCapacity);
		if ($error !== null) {
			return $error;
		}

		try {
			$vehicle = $this->vehicleService->createVehicle($name, $buildingId, $email, $vehicleType, $vehicleMake, $vehicleModel, $contactPersonUserId, $isElectric, $range, $seatingCapacity);
		} catch (DoesNotExistException) {
			return $this->error('The selected building does not exist', Http::STATUS_BAD_REQUEST);
		} catch (ServiceException $e) {
			return $this->serviceError('Could not create vehicle', $e);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not create vehicle', $e);
		}

		$this->resourceManager->update();

		return new JSONResponse($vehicle, Http::STATUS_CREATED);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/vehicles/{id}')]
	public function updateVehicle(int $id, string $name = '', ?int $buildingId = null, string $email = '', string $vehicleType = 'car', string $vehicleMake = '', string $vehicleModel = '', string $contactPersonUserId = '', bool $isElectric = false, ?int $range = null, ?int $seatingCapacity = null): JSONResponse {
		$error = $this->validateVehicle($name, $buildingId, $email, $vehicleType, $vehicleMake, $vehicleModel, $contactPersonUserId, $range, $seatingCapacity);
		if ($error !== null) {
			return $error;
		}

		try {
			$vehicle = $this->vehicleService->updateVehicle($id, $name, $buildingId, $email, $vehicleType, $vehicleMake, $vehicleModel, $contactPersonUserId, $isElectric, $range, $seatingCapacity);
		} catch (DoesNotExistException) {
			return $this->error('The vehicle does not exist', Http::STATUS_NOT_FOUND);
		} catch (ServiceException $e) {
			return $this->serviceError('Could not update vehicle', $e);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not update vehicle', $e);
		}

		$this->resourceManager->update();

		return new JSONResponse($vehicle);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/vehicles/{id}')]
	public function deleteVehicle(int $id): JSONResponse {
		try {
			$this->vehicleService->deleteVehicle($id);
		} catch (DoesNotExistException) {
			return $this->error('The vehicle does not exist', Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not delete vehicle', $e);
		}

		$this->resourceManager->update();

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	private function validateVehicle(string $name, ?int $buildingId, string $email, string $vehicleType, string $vehicleMake, string $vehicleModel, string $contactPersonUserId, ?int $range, ?int $seatingCapacity): ?JSONResponse {
		if (trim($name) === '') {
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error('A building has to be selected', Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error('A valid email address is required', Http::STATUS_BAD_REQUEST);
		}

		if (!in_array($vehicleType, ['bicycle', 'scooter', 'motorbike', 'car', 'plane', 'helicopter', 'other'], true)) {
			return $this->error('A valid vehicle type is required', Http::STATUS_BAD_REQUEST);
		}
		if (trim($vehicleMake) === '' || trim($vehicleModel) === '') {
			return $this->error('Vehicle make and model are required', Http::STATUS_BAD_REQUEST);
		}
		if (($range !== null && ($range < 0 || $range > 2147483647)) || ($seatingCapacity !== null && ($seatingCapacity < 0 || $seatingCapacity > 2147483647))) {
			return $this->error('Range and seating capacity must be between 0 and 2147483647', Http::STATUS_BAD_REQUEST);
		}
		if ($contactPersonUserId !== '' && !$this->userManager->userExists($contactPersonUserId)) {
			return $this->error('The selected contact person does not exist', Http::STATUS_BAD_REQUEST);
		}

		return null;
	}

	private function error(string $message, int $status): JSONResponse {
		return new JSONResponse(['error' => $message], $status);
	}

	/**
	 * Answer with the message of a service failure that names an http code, treat the rest
	 * as unexpected.
	 */
	private function serviceError(ServiceException $e): JSONResponse {
		$httpCode = $e->getHttpCode();
		if ($httpCode === null) {
			return $this->unexpectedError($e);
		}

		return $this->error($e->getMessage(), $httpCode);
	}

	/**
	 * Log an unexpected failure and report it without leaking internals to the client.
	 */
	private function unexpectedError(Throwable $e): JSONResponse {
		$this->logger->error('Unexpected failure in the admin interface', ['exception' => $e]);

		return new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
	}
}
