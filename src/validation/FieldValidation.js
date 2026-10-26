/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

const APP_ID = 'calendar_resource_management'

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export default class FieldValidation {
	errors = {}

	/**
	 * Store the field's first rule failure under its key.
	 *
	 * @param {object} field the schema's field descriptor
	 * @param {*} value the field's normalised value
	 */
	validate(field, value) {
		const label = field.label ?? ''

		if (field.required && this.#isBlank(value)) {
			this.errors[field.key] = t(APP_ID, '{label} is required', { label })
			return
		}
		if (field.type === 'email' && !this.#isBlank(value) && !EMAIL_REGEX.test(String(value))) {
			this.errors[field.key] = t(APP_ID, '{label} must be a valid email address', { label })
			return
		}
		if (field.range) {
			const message = this.#checkRange(label, field.range, value)
			if (message !== null) {
				this.errors[field.key] = message
			}
		}
	}

	/**
	 * The accumulated errors (not `errors()`: the field would shadow it).
	 *
	 * @return {?object} field key -> message, or null when nothing failed
	 */
	result() {
		return Object.keys(this.errors).length > 0 ? this.errors : null
	}

	/**
	 * Whether a value is null, undefined or blank.
	 *
	 * @param {*} value the value to test
	 * @return {boolean} true when the value is empty
	 */
	#isBlank(value) {
		return value === null || value === undefined || String(value).trim() === ''
	}

	/**
	 * Check a number against an inclusive range; blank passes.
	 *
	 * @param {string} label the field's display label
	 * @param {object} bounds the allowed range
	 * @param {?number} bounds.from inclusive lower bound (omit for none)
	 * @param {?number} bounds.to inclusive upper bound (omit for none)
	 * @param {*} value the field's value
	 * @return {?string} the error message, or null when the value passes
	 */
	#checkRange(label, { from = null, to = null }, value) {
		if (this.#isBlank(value)) {
			return null
		}
		const number = Number(value)
		if (Number.isNaN(number)) {
			return null
		}
		const hasFrom = from !== null && from !== undefined
		const hasTo = to !== null && to !== undefined
		if ((hasFrom && number < from) || (hasTo && number > to)) {
			if (hasFrom && hasTo) {
				return t(APP_ID, '{label} must be between {from} and {to}', { label, from, to })
			}
			return hasFrom
				? t(APP_ID, '{label} must be at least {from}', { label, from })
				: t(APP_ID, '{label} must be at most {to}', { label, to })
		}
		return null
	}
}
