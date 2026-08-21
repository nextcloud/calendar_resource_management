<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Exception;

use OCP\AppFramework\Http;

/**
 * Thrown when the email address of an entity is already taken by another entity of the
 * same type, which the unique index on the email column forbids.
 */
class EmailAlreadyUsedException extends ServiceException {
	public function __construct(string $message) {
		parent::__construct($message, httpCode: Http::STATUS_CONFLICT);
	}
}
