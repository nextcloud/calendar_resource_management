<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\AppInfo;

use OCA\CalendarResourceManagement\Connector;
use OCA\CalendarResourceManagement\Listener\GroupDeletedListener;
use OCA\CalendarResourceManagement\Listener\UserDeletedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\User\Events\UserDeletedEvent;

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
