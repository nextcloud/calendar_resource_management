<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use InvalidArgumentException;

class UidValidationService {
	/**
	 * Validate whether the given uid only contains valid characters.
	 */
	public function validateUid(string $uid): bool {
		// Taken from \OC\User\Manager::validateUserId
		return !preg_match('/[^a-zA-Z0-9 _.@\-\']/', $uid);
	}

	/**
	 * Validate whether the given uid only contains valid characters and throw otherwise.
	 *
	 * @throws InvalidArgumentException If the given uid is invalid.
	 */
	public function validateUidAndThrow(string $uid): void {
		if (!$this->validateUid($uid)) {
			throw new InvalidArgumentException(
				'Only the following characters are allowed in a uid: "a-z", "A-Z", "0-9", spaces and "_.@-\'"'
			);
		}
	}
}
