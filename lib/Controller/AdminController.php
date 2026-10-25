<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Controller;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
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
use OCP\IGroup;
use OCP\IGroupManager;
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
		private IRoomManager $roomManager,
		private IResourceManager $resourceManager,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private BookingCalendarShareService $shareService,
		private IGroupManager $groupManager,
		private VehicleService $vehicleService,
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
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}

		try {
			$building = $this->buildingService->createBuilding($name, $address);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not create building', $e);
		}

		return new JSONResponse($building, Http::STATUS_CREATED);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/buildings/{id}')]
	public function updateBuilding(int $id, string $name = '', string $address = '', string $description = '', bool $wheelchairAccessible = false): JSONResponse {
		if (trim($name) === '') {
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}

		try {
			$building = $this->buildingService->updateBuilding($id, $name, $address, $description, $wheelchairAccessible);
		} catch (DoesNotExistException) {
			return $this->error('The building does not exist', Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not update building', $e);
		}

		// Rooms and resources carry the name and address of their building
		$this->roomManager->update();
		$this->resourceManager->update();

		return new JSONResponse($building);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/buildings/{id}')]
	public function deleteBuilding(int $id): JSONResponse {
		try {
			$this->buildingService->deleteBuilding($id);
		} catch (DoesNotExistException) {
			return $this->error('The building does not exist', Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not delete building', $e);
		}

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
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error('A building has to be selected', Http::STATUS_BAD_REQUEST);
		}

		try {
			$story = $this->storyService->createStory($name, $buildingId);
		} catch (DoesNotExistException) {
			return $this->error('The selected building does not exist', Http::STATUS_BAD_REQUEST);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not create story', $e);
		}

		return new JSONResponse($story, Http::STATUS_CREATED);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/stories/{id}')]
	public function updateStory(int $id, string $name = '', ?int $buildingId = null): JSONResponse {
		if (trim($name) === '') {
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error('A building has to be selected', Http::STATUS_BAD_REQUEST);
		}

		try {
			$story = $this->storyService->updateStory($id, $name, $buildingId);
		} catch (DoesNotExistException) {
			return $this->error('The story or the selected building does not exist', Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not update story', $e);
		}

		// Rooms carry the name of their story and building
		$this->roomManager->update();

		return new JSONResponse($story);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/stories/{id}')]
	public function deleteStory(int $id): JSONResponse {
		try {
			$this->storyService->deleteStory($id);
		} catch (DoesNotExistException) {
			return $this->error('The story does not exist', Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not delete story', $e);
		}

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/rooms')]
	public function getRooms(): JSONResponse {
		return new JSONResponse($this->withRestrictionGroups(
			$this->roomService->listRooms(),
			$this->roomService->listRestrictionGroupIds(),
		));
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
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}
		if ($storyId === null) {
			return $this->error('A story has to be selected', Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error('A valid email address is required', Http::STATUS_BAD_REQUEST);
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
		} catch (DoesNotExistException) {
			return $this->error('The selected story does not exist', Http::STATUS_BAD_REQUEST);
		} catch (ServiceException $e) {
			return $this->serviceError('Could not create room', $e);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not create room', $e);
		}

		$this->roomManager->update();
		$this->shareCreated(Constants::ROOM, $room);

		return new JSONResponse($room, Http::STATUS_CREATED);
	}

	/**
	 * @param string[] $restrictionGroupIds
	 */
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
		bool $restricted = false,
		array $restrictionGroupIds = [],
	): JSONResponse {
		if (trim($name) === '') {
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}
		if ($storyId === null) {
			return $this->error('A story has to be selected', Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error('A valid email address is required', Http::STATUS_BAD_REQUEST);
		}
		$restrictionGroupIds = $this->validGroupIds($restrictionGroupIds);
		if ($restrictionGroupIds === null) {
			return $this->error('A selected group does not exist', Http::STATUS_BAD_REQUEST);
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
				$restricted,
				$restrictionGroupIds,
			);
		} catch (DoesNotExistException) {
			return $this->error('The room or the selected story does not exist', Http::STATUS_NOT_FOUND);
		} catch (ServiceException $e) {
			return $this->serviceError('Could not update room', $e);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not update room', $e);
		}

		// Name, email and visibility of the room are cached by the server
		$this->roomManager->update();
		$this->syncShares(Constants::ROOM, $id);

		return new JSONResponse($this->withRestrictionGroups([$room], [$id => $room->isRestricted() ? $restrictionGroupIds : []])[0]);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/rooms/{id}')]
	public function deleteRoom(int $id): JSONResponse {
		try {
			$this->roomService->deleteRoom($id);
		} catch (DoesNotExistException) {
			return $this->error('The room does not exist', Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not delete room', $e);
		}

		$this->roomManager->update();

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/resources')]
	public function getResources(): JSONResponse {
		return new JSONResponse($this->withRestrictionGroups(
			$this->resourceService->listResources(),
			$this->resourceService->listRestrictionGroupIds(),
		));
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'POST', url: '/admin/resources')]
	public function createResource(string $name = '', ?int $buildingId = null, string $email = '', string $resourceType = 'default'): JSONResponse {
		if (trim($name) === '') {
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error('A building has to be selected', Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error('A valid email address is required', Http::STATUS_BAD_REQUEST);
		}

		try {
			$resource = $this->resourceService->createResource($name, $buildingId, $email, $resourceType);
		} catch (DoesNotExistException) {
			return $this->error('The selected building does not exist', Http::STATUS_BAD_REQUEST);
		} catch (ServiceException $e) {
			return $this->serviceError('Could not create resource', $e);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not create resource', $e);
		}

		$this->resourceManager->update();
		$this->shareCreated(Constants::RESOURCE, $resource);

		return new JSONResponse($resource, Http::STATUS_CREATED);
	}

	/**
	 * @param string[] $restrictionGroupIds
	 */
	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/resources/{id}')]
	public function updateResource(
		int $id,
		string $name = '',
		?int $buildingId = null,
		string $email = '',
		string $resourceType = 'default',
		bool $restricted = false,
		array $restrictionGroupIds = [],
	): JSONResponse {
		if (trim($name) === '') {
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error('A building has to be selected', Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error('A valid email address is required', Http::STATUS_BAD_REQUEST);
		}
		$restrictionGroupIds = $this->validGroupIds($restrictionGroupIds);
		if ($restrictionGroupIds === null) {
			return $this->error('A selected group does not exist', Http::STATUS_BAD_REQUEST);
		}

		try {
			$resource = $this->resourceService->updateResource($id, $name, $buildingId, $email, $resourceType, $restricted, $restrictionGroupIds);
		} catch (DoesNotExistException) {
			return $this->error('The resource or the selected building does not exist', Http::STATUS_NOT_FOUND);
		} catch (ServiceException $e) {
			return $this->serviceError('Could not update resource', $e);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not update resource', $e);
		}

		$this->resourceManager->update();
		$this->syncShares(Constants::RESOURCE, $id);

		return new JSONResponse($this->withRestrictionGroups([$resource], [$id => $resource->isRestricted() ? $restrictionGroupIds : []])[0]);
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'DELETE', url: '/admin/resources/{id}')]
	public function deleteResource(int $id): JSONResponse {
		try {
			$this->resourceService->deleteResource($id);
		} catch (DoesNotExistException) {
			return $this->error('The resource does not exist', Http::STATUS_NOT_FOUND);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not delete resource', $e);
		}

		$this->resourceManager->update();

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}

	/**
	 * Share the booking calendar of a new entity, the entity exists even if this fails and the
	 * background job shares it later
	 */
	private function shareCreated(string $type, RoomModel|ResourceModel $entity): void {
		try {
			$this->shareService->handleCreated($type, $entity);
		} catch (Throwable $e) {
			$this->logger->error('Could not share the booking calendar of ' . $type . ' ' . $entity->getId(), ['exception' => $e]);
		}
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'GET', url: '/admin/vehicles')]
	public function getVehicles(): JSONResponse {
		return new JSONResponse($this->withRestrictionGroups(
			$this->vehicleService->listVehicles(),
			$this->vehicleService->listRestrictionGroupIds(),
		));
	}

	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'POST', url: '/admin/vehicles')]
	public function createVehicle(
		string $name = '',
		?int $buildingId = null,
		string $email = '',
		string $vehicleType = '',
		string $vehicleMake = '',
		string $vehicleModel = '',
		bool $isElectric = false,
		?int $range = null,
		?int $seatingCapacity = null,
		string $contactPersonUserId = '',
	): JSONResponse {
		$invalid = $this->validateVehicle($name, $buildingId, $email);
		if ($invalid !== null) {
			return $invalid;
		}

		try {
			$vehicle = $this->vehicleService->createVehicle($name, (int)$buildingId, $email, $vehicleType, $vehicleMake, $vehicleModel, $isElectric, $range, $seatingCapacity, $contactPersonUserId);
		} catch (DoesNotExistException) {
			return $this->error('The selected building does not exist', Http::STATUS_BAD_REQUEST);
		} catch (ServiceException $e) {
			return $this->serviceError('Could not create vehicle', $e);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not create vehicle', $e);
		}

		$this->resourceManager->update();
		$this->shareCreated(Constants::VEHICLE, $vehicle);

		return new JSONResponse($this->withRestrictionGroups([$vehicle], [])[0], Http::STATUS_CREATED);
	}

	/**
	 * @param string[] $restrictionGroupIds
	 */
	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	#[FrontpageRoute(verb: 'PUT', url: '/admin/vehicles/{id}')]
	public function updateVehicle(
		int $id,
		string $name = '',
		?int $buildingId = null,
		string $email = '',
		string $vehicleType = '',
		string $vehicleMake = '',
		string $vehicleModel = '',
		bool $isElectric = false,
		?int $range = null,
		?int $seatingCapacity = null,
		string $contactPersonUserId = '',
		bool $restricted = false,
		array $restrictionGroupIds = [],
	): JSONResponse {
		$invalid = $this->validateVehicle($name, $buildingId, $email);
		if ($invalid !== null) {
			return $invalid;
		}
		$restrictionGroupIds = $this->validGroupIds($restrictionGroupIds);
		if ($restrictionGroupIds === null) {
			return $this->error('A selected group does not exist', Http::STATUS_BAD_REQUEST);
		}

		try {
			$vehicle = $this->vehicleService->updateVehicle($id, $name, (int)$buildingId, $email, $vehicleType, $vehicleMake, $vehicleModel, $isElectric, $range, $seatingCapacity, $contactPersonUserId, $restricted, $restrictionGroupIds);
		} catch (DoesNotExistException) {
			return $this->error('The vehicle or the selected building does not exist', Http::STATUS_NOT_FOUND);
		} catch (ServiceException $e) {
			return $this->serviceError('Could not update vehicle', $e);
		} catch (Throwable $e) {
			return $this->unexpectedError('Could not update vehicle', $e);
		}

		$this->resourceManager->update();
		$this->syncShares(Constants::VEHICLE, $id);

		return new JSONResponse($this->withRestrictionGroups([$vehicle], [$id => $vehicle->isRestricted() ? $restrictionGroupIds : []])[0]);
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

	/**
	 * @return JSONResponse|null The error, null if the vehicle is valid
	 */
	private function validateVehicle(string $name, ?int $buildingId, string $email): ?JSONResponse {
		if (trim($name) === '') {
			return $this->error('A name is required', Http::STATUS_BAD_REQUEST);
		}
		if ($buildingId === null) {
			return $this->error('A building has to be selected', Http::STATUS_BAD_REQUEST);
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return $this->error('A valid email address is required', Http::STATUS_BAD_REQUEST);
		}

		return null;
	}

	/**
	 * A changed name or restriction affects the booking calendar and who may see it
	 */
	private function syncShares(string $type, int $id): void {
		try {
			$this->shareService->syncEntityById($type, $id);
		} catch (Throwable $e) {
			$this->logger->error('Could not sync the booking calendar of ' . $type . ' ' . $id, ['exception' => $e]);
		}
	}

	/**
	 * @param mixed[] $groupIds
	 * @return string[]|null The group ids, null if one of them does not exist
	 */
	private function validGroupIds(array $groupIds): ?array {
		$groupIds = array_values(array_unique(array_map('strval', $groupIds)));
		foreach ($groupIds as $groupId) {
			if (!$this->groupManager->groupExists($groupId)) {
				return null;
			}
		}

		return $groupIds;
	}

	/**
	 * Add the restriction groups to serialized rooms or resources
	 *
	 * @param list<RoomModel|ResourceModel> $entities
	 * @param array<int, string[]> $restrictionGroupIds Group ids per entity id
	 * @return list<array<string, mixed>>
	 */
	private function withRestrictionGroups(array $entities, array $restrictionGroupIds): array {
		return array_map(fn (RoomModel|ResourceModel $entity): array => [
			...$entity->jsonSerialize(),
			'restrictionGroups' => array_map(function (string $groupId): array {
				$group = $this->groupManager->get($groupId);
				// A group of a vanished user backend still shows up, so it can be removed
				return [
					'id' => $groupId,
					'displayName' => $group instanceof IGroup ? $group->getDisplayName() : $groupId,
				];
			}, $restrictionGroupIds[$entity->getId()] ?? []),
		], $entities);
	}

	private function error(string $message, int $status): JSONResponse {
		return new JSONResponse(['error' => $message], $status);
	}

	/**
	 * Answer with the message of a service failure that names an http code, treat the rest
	 * as unexpected.
	 */
	private function serviceError(string $message, ServiceException $e): JSONResponse {
		$httpCode = $e->getHttpCode();
		if ($httpCode === null) {
			return $this->unexpectedError($message, $e);
		}

		return $this->error($e->getMessage(), $httpCode);
	}

	/**
	 * Log an unexpected failure and report it without leaking internals to the client.
	 */
	private function unexpectedError(string $message, Throwable $e): JSONResponse {
		$this->logger->error($message, ['exception' => $e]);

		return $this->error($message, Http::STATUS_INTERNAL_SERVER_ERROR);
	}
}
