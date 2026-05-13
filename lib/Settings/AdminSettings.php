<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IInitialStateService;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

class AdminSettings implements IDelegatedSettings {

	private IInitialStateService $initialStateService;
	private IURLGenerator $urlGenerator;

	public function __construct(IInitialStateService $initialStateService, IURLGenerator $urlGenerator) {
		$this->initialStateService = $initialStateService;
		$this->urlGenerator = $urlGenerator;
	}

	public function getForm() {
		// Load JavaScript (CSS is injected by the JS bundle)
		Util::addScript('calendar_resource_management', 'calendar_resource_management-admin-settings');

		// Provide initial state for the API URL
		$this->initialStateService->provideInitialState('calendar_resource_management', 'calendar-resource-admin', [
			'apiUrl' => $this->urlGenerator->linkToRouteAbsolute('calendar_resource_management.admin.getresources'),
		]);

		// Return the template
		return new TemplateResponse('calendar_resource_management', 'admin', []);
	}

	public function getSection() {
		return 'calendar_resource_management';
	}

	public function getPriority() {
		return 50;
	}

	public function getName(): ?string {
		return null; // Use the section name
	}

	public function getAuthorizedAppConfig(): array {
		return []; // No app config keys to authorize for delegated admins
	}
}
