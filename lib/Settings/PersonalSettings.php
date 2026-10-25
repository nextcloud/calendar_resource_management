<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Settings;

use OCA\CalendarResourceManagement\Service\BookingCalendarSettings;
use OCA\CalendarResourceManagement\Service\BookingCalendarShareService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;

class PersonalSettings implements ISettings {
	public function __construct(
		private BookingCalendarShareService $shareService,
		private BookingCalendarSettings $settings,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getForm(): TemplateResponse {
		$userId = $this->userSession->getUser()?->getUID();
		if ($userId !== null && $this->shareService->isViewer($userId)) {
			// The CSS is injected by the JavaScript bundle
			Util::addScript('calendar_resource_management', 'calendar_resource_management-personal-settings');

			return new TemplateResponse('calendar_resource_management', 'personal', ['state' => 'viewer']);
		}

		// Settings can not hide themselves, so explain why there is nothing to pick
		$featureEnabled = $this->settings->getViewerGroups() !== [];
		if ($userId !== null && $this->groupManager->isAdmin($userId)) {
			return new TemplateResponse('calendar_resource_management', 'personal', [
				'state' => $featureEnabled ? 'admin-not-viewer' : 'admin-disabled',
				'adminUrl' => $this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'calendar_resource_management']),
			]);
		}

		// Without viewer groups the feature is off and not worth a note for everybody
		return new TemplateResponse('calendar_resource_management', 'personal', ['state' => $featureEnabled ? 'not-viewer' : 'disabled']);
	}

	public function getSection(): string {
		// Next to the calendar settings of the server
		return 'calendar';
	}

	public function getPriority(): int {
		return 50;
	}
}
