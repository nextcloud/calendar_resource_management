/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

/** Anything with a name that can be shown in a table or a select */
interface NamedEntity {
	id: number
	name: string
}

export interface SelectOption {
	id: number
	label: string
}

/**
 * Resolve the id of an NcSelect value, which is either an option object or a raw id.
 *
 * @param value The current NcSelect value
 * @return The entity id or null if nothing is selected
 */
export function optionId<T extends string | number>(value: { id: T } | T | null | undefined): T | null {
	if (value === null || value === undefined) {
		return null
	}

	return typeof value === 'object' ? value.id : value
}

/**
 * Map entities to NcSelect options.
 *
 * @param entities Entities with an id and a name
 * @return NcSelect options
 */
export function selectOptions(entities: NamedEntity[]): SelectOption[] {
	return entities.map((entity) => ({
		id: entity.id,
		label: entity.name,
	}))
}

/**
 * Look up the name of an entity by its id.
 *
 * @param entities Entities with an id and a name
 * @param id The id to look up
 * @return The entity name or a placeholder if it is unknown
 */
export function nameById(entities: NamedEntity[], id: number | undefined): string {
	const entity = entities.find((candidate) => candidate.id === id)

	return entity ? entity.name : t('calendar_resource_management', 'Unknown')
}
