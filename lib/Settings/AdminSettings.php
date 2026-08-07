<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

class AdminSettings implements IDelegatedSettings {
	public function getForm(): TemplateResponse {
		// The CSS is injected by the JavaScript bundle
		Util::addScript('calendar_resource_management', 'calendar_resource_management-admin-settings');

		return new TemplateResponse('calendar_resource_management', 'admin', []);
	}

	public function getSection(): string {
		return 'calendar_resource_management';
	}

	public function getPriority(): int {
		return 50;
	}

	public function getName(): ?string {
		// Only the section name is shown
		return null;
	}

	public function getAuthorizedAppConfig(): array {
		// The settings do not write any app config values
		return [];
	}
}
