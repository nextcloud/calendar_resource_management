/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* Shared helpers for the admin UI. */

/**
 * Whether a value is set.
 *
 * @param {string|number|boolean|null|undefined} value the value to test
 * @return {boolean} true when the value should be sent to the API
 */
export function isSet(value) {
	return value !== null && value !== undefined && value !== ''
}
