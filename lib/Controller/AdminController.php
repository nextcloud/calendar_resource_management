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
use OCA\CalendarResourceManagement\Settings\AdminSettings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Room\IManager as IRoomManager;
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

		return new JSONResponse($room, Http::STATUS_CREATED);
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
		return new JSONResponse($this->resourceService->listResources());
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

		return new JSONResponse($resource, Http::STATUS_CREATED);
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
