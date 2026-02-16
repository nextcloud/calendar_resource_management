<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\AppInfo;

require_once __DIR__ . '/../../vendor/autoload.php';

use OCA\CalendarResourceManagement\Connector;
use OCA\CalendarResourceManagement\Listener\GroupDeletedListener;
use OCA\CalendarResourceManagement\Listener\UserDeletedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\IInitialStateService;
use OCP\IURLGenerator;

use OCA\CalendarResourceManagement\Service\RoomService;
use OCA\CalendarResourceManagement\Service\ResourceService;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Controller\AdminController;
use OCA\CalendarResourceManagement\Settings\AdminSettings;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\Calendar\Resource\IManager as IResourceManager;

class Application extends App implements IBootstrap {
	/**
	 * @var string
	 */
	public const APP_ID = 'calendar_resource_management';

	/**
	 * Application constructor.
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();

		// RoomService
		$container->registerService(RoomService::class, function($c) {
			return new RoomService(
				$c->query(RoomMapper::class),
				$c->query(RestrictionMapper::class)
			);
		});

		// ResourceService
		$container->registerService(ResourceService::class, function($c) {
			return new ResourceService(
				$c->query(ResourceMapper::class),
				$c->query(RestrictionMapper::class)
			);
		});

		// BuildingMapper
		$container->registerService(BuildingMapper::class, function($c) {
			return new BuildingMapper($c->query('ServerContainer')->getDatabaseConnection());
		});

		// StoryMapper
		$container->registerService(StoryMapper::class, function($c) {
			return new StoryMapper($c->query('ServerContainer')->getDatabaseConnection());
		});

		// AdminController
		$container->registerService(AdminController::class, function($c) {
			$server = $c->query('ServerContainer');
			
			// Try to get managers, but make them optional
			$roomManager = null;
			$resourceManager = null;
			try {
				$roomManager = $server->get(IRoomManager::class);
			} catch (\Exception $e) {
				// Room manager not available - cache invalidation won't work
			}
			try {
				$resourceManager = $server->get(IResourceManager::class);
			} catch (\Exception $e) {
				// Resource manager not available - cache invalidation won't work
			}
			
			return new AdminController(
				self::APP_ID,
				$server->getRequest(),
				$c->query(RoomService::class),
				$c->query(ResourceService::class),
				$c->query(BuildingMapper::class),
				$c->query(StoryMapper::class),
				$roomManager,
				$resourceManager
			);
		});

		// AdminSettings
		$container->registerService(AdminSettings::class, function($c) {
			return new AdminSettings(
				$c->query(IInitialStateService::class),
				$c->query(IURLGenerator::class)
			);
		});
	}

	/**
	 * @inheritDoc
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerCalendarResourceBackend(Connector\Resource\Backend::class);
		$context->registerCalendarRoomBackend(Connector\Room\Backend::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
	}

	/**
	 * @inheritDoc
	 */
	public function boot(IBootContext $context): void {
	}
}
