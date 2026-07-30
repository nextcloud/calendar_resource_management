<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private IURLGenerator $url,
		private IL10N $l10n,
	) {
	}

	public function getID(): string {
		return 'calendar_resource_management';
	}

	public function getName(): string {
		return $this->l10n->t('Calendar Resource Management');
	}

	public function getPriority(): int {
		return 50;
	}

	public function getIcon(): string {
		return $this->url->imagePath('calendar_resource_management', 'app.svg');
	}
}
