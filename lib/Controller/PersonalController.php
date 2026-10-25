<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Controller;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCA\CalendarResourceManagement\Service\BookingCalendarSettings;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Lets users pick the booking calendars that show up in their calendar apps
 */
class PersonalController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private BookingCalendarShareService $shareService,
		private BookingCalendarSettings $settings,
		private BuildingMapper $buildingMapper,
		private StoryMapper $storyMapper,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/personal/booking-calendars')]
	public function getBookingCalendars(): JSONResponse {
		$userId = $this->getViewerId();
		if ($userId === null) {
			return $this->forbidden();
		}

		$buildingNames = [];
		foreach ($this->buildingMapper->findAll() as $building) {
			$buildingNames[$building->getId()] = $building->getDisplayName();
		}
		$stories = [];
		foreach ($this->storyMapper->findAll() as $story) {
			$stories[$story->getId()] = $story;
		}

		$visible = $this->shareService->getVisiblePrincipals($userId);
		$calendars = [];
		foreach ($this->shareService->listEntities() as [$type, $entity]) {
			if (!$this->shareService->canView($userId, $type, $entity)) {
				continue;
			}

			$story = $entity instanceof RoomModel ? ($stories[$entity->getStoryId()] ?? null) : null;
			$buildingId = $entity instanceof RoomModel ? $story?->getBuildingId() : $entity->getBuildingId();
			$calendars[] = [
				'type' => $type,
				'id' => $entity->getId(),
				'name' => $entity->getDisplayName(),
				'buildingName' => $buildingId === null ? '' : ($buildingNames[$buildingId] ?? ''),
				'storyName' => $story?->getDisplayName() ?? '',
				'capacity' => $this->getCapacity($entity),
				'subscribed' => isset($visible[$this->shareService->principalUri($type, $entity->getUid())]),
				'isDefault' => $this->shareService->isDefaultFor($userId, $type, $entity),
			];
		}

		return new JSONResponse([
			'autoSubscribeNew' => $this->settings->getAutoSubscribeNew($userId),
			'calendars' => $calendars,
		]);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/personal/booking-calendars/{entityType}/{entityId}')]
	public function setSubscribed(string $entityType, int $entityId, bool $subscribed): JSONResponse {
		$userId = $this->getViewerId();
		if ($userId === null) {
			return $this->forbidden();
		}

		try {
			$entity = $this->shareService->findEntity($entityType, $entityId);
		} catch (DoesNotExistException) {
			return $this->error('The calendar does not exist', Http::STATUS_NOT_FOUND);
		}
		if (!$this->shareService->canView($userId, $entityType, $entity)) {
			// Same answer as for a missing entity, restricted entities stay unknown
			return $this->error('The calendar does not exist', Http::STATUS_NOT_FOUND);
		}

		try {
			if (!$subscribed) {
				$this->shareService->unsubscribe($userId, $entityType, $entity);
			} elseif (!$this->shareService->subscribe($userId, $entityType, $entity)) {
				return $this->error('The calendar is not available yet, try again later', Http::STATUS_CONFLICT);
			}
		} catch (Throwable $e) {
			$this->logger->error('Could not change the booking calendar subscription', ['exception' => $e]);
			return $this->error('Could not change the booking calendar subscription', Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return new JSONResponse(['subscribed' => $subscribed]);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/personal/booking-calendars/preferences')]
	public function setPreferences(bool $autoSubscribeNew): JSONResponse {
		$userId = $this->getViewerId();
		if ($userId === null) {
			return $this->forbidden();
		}

		$this->settings->setAutoSubscribeNew($userId, $autoSubscribeNew);

		return new JSONResponse(['autoSubscribeNew' => $autoSubscribeNew]);
	}

	/**
	 * @return string|null The current user if they may see booking calendars
	 */
	private function getViewerId(): ?string {
		$userId = $this->userSession->getUser()?->getUID();
		if ($userId === null || !$this->shareService->isViewer($userId)) {
			return null;
		}

		return $userId;
	}

	private function getCapacity(RoomModel|ResourceModel $entity): ?int {
		$capacity = match (true) {
			$entity instanceof RoomModel => $entity->getCapacity(),
			$entity instanceof VehicleModel => $entity->getSeatingCapacity(),
			default => null,
		};

		// The commands store 0 when no capacity was given
		return $capacity ?: null;
	}

	private function forbidden(): JSONResponse {
		return $this->error('Booking calendars are not available for this account', Http::STATUS_FORBIDDEN);
	}

	private function error(string $message, int $status): JSONResponse {
		return new JSONResponse(['error' => $message], $status);
	}
}
