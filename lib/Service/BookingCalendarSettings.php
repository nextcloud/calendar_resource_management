<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\IAppConfig;

/**
 * Who may see booking calendars and which users want new ones automatically
 */
class BookingCalendarSettings {
	private const VIEWER_GROUPS = 'viewer_groups';
	private const AUTO_SUBSCRIBE_NEW = 'auto_subscribe_new';

	public function __construct(
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
	) {
	}

	/**
	 * Members of these groups may add booking calendars, none means the feature is off
	 *
	 * @return string[]
	 */
	public function getViewerGroups(): array {
		return array_values(array_map('strval', $this->appConfig->getValueArray(Application::APP_ID, self::VIEWER_GROUPS)));
	}

	/**
	 * @param string[] $groupIds
	 */
	public function setViewerGroups(array $groupIds): void {
		$this->appConfig->setValueArray(Application::APP_ID, self::VIEWER_GROUPS, array_values(array_unique($groupIds)));
	}

	public function getAutoSubscribeNew(string $userId): bool {
		return $this->userConfig->getValueBool($userId, Application::APP_ID, self::AUTO_SUBSCRIBE_NEW);
	}

	public function setAutoSubscribeNew(string $userId, bool $enabled): void {
		$this->userConfig->setValueBool($userId, Application::APP_ID, self::AUTO_SUBSCRIBE_NEW, $enabled);
	}

	/**
	 * @return string[] The users that get new rooms, resources and vehicles automatically
	 */
	public function getAutoSubscribeUsers(): array {
		return iterator_to_array($this->userConfig->searchUsersByValueBool(Application::APP_ID, self::AUTO_SUBSCRIBE_NEW, true), false);
	}
}
