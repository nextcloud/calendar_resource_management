<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Only the parts of sabre/dav the app uses, the server ships the library

namespace Sabre\DAV;

class PropPatch {
	/**
	 * @param array<string, mixed> $mutations
	 */
	public function __construct(array $mutations) {
	}

	public function commit(): bool {
	}
}
