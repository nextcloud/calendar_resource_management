<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Exception;

use Exception;
use Throwable;

/**
 * A failure of a service operation. Subclasses that carry an http code also carry a
 * message that is safe to show to the client.
 */
class ServiceException extends Exception {
	public function __construct(
		string $message = '',
		int $code = 0,
		?Throwable $previous = null,
		private ?int $httpCode = null,
	) {
		parent::__construct($message, $code, $previous);
	}

	public function getHttpCode(): ?int {
		return $this->httpCode;
	}
}
