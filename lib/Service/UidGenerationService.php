<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCP\Security\ISecureRandom;

/**
 * Generates `uid` (the Calendar principal id) for new entities.
 */
class UidGenerationService {
	/**
	 * Must fit the `VARCHAR(64)` DAV cache `resource_id` that
	 * {@see \OC\Calendar\ResourcesRoomsUpdater} copies the uid into.
	 */
	private const UID_LENGTH = 16;

	public function __construct(
		private ISecureRandom $random,
	) {
	}

	/**
	 * A fresh `[a-z0-9]` uid, valid for {@see UidValidationService::validateUid()} and DAV URIs.
	 *
	 * @return string the generated uid
	 */
	public function generateUid(): string {
		return $this->random->generate(
			self::UID_LENGTH,
			ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS,
		);
	}
}
