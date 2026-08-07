/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Resolve the id of an NcSelect value, which is either an option object or a raw id.
 *
 * @param {object|number|null} value The current NcSelect value
 * @return {number|null} The entity id or null if nothing is selected
 */
export function optionId(value) {
	if (value === null || value === undefined) {
		return null
	}

	return typeof value === 'object' ? value.id : value
}

/**
 * Map entities to NcSelect options.
 *
 * @param {Array} entities Entities with an id and a name
 * @return {Array} NcSelect options
 */
export function selectOptions(entities) {
	return entities.map((entity) => ({
		id: entity.id,
		label: entity.name,
	}))
}

/**
 * Look up the name of an entity by its id.
 *
 * @param {Array} entities Entities with an id and a name
 * @param {number} id The id to look up
 * @return {string} The entity name or a placeholder if it is unknown
 */
export function nameById(entities, id) {
	const entity = entities.find((candidate) => candidate.id === id)

	return entity ? entity.name : t('calendar_resource_management', 'Unknown')
}
