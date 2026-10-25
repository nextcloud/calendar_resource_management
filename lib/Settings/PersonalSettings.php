<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Settings;

use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;

class PersonalSettings implements ISettings {
	public function __construct(
		private BookingCalendarShareService $shareService,
		private IUserSession $userSession,
	) {
	}

	public function getForm(): TemplateResponse {
		$userId = $this->userSession->getUser()?->getUID();
		// Settings can not hide themselves, accounts without access get an empty form
		if ($userId === null || !$this->shareService->isViewer($userId)) {
			return new TemplateResponse('calendar_resource_management', 'personal', ['enabled' => false]);
		}

		// The CSS is injected by the JavaScript bundle
		Util::addScript('calendar_resource_management', 'calendar_resource_management-personal-settings');

		return new TemplateResponse('calendar_resource_management', 'personal', ['enabled' => true]);
	}

	public function getSection(): string {
		// Next to the calendar settings of the server
		return 'calendar';
	}

	public function getPriority(): int {
		return 50;
	}
}
