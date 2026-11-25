/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { EquipmentKey, Room } from '../types/types.ts'

import { getLanguage, translate as t } from '@nextcloud/l10n'

/**
 * Equipment flags of a room. `payloadKey` is what the create and update
 * endpoints expect, `responseKey` what the listing returns.
 */
export const EQUIPMENT_TYPES: Array<{
	payloadKey: EquipmentKey
	responseKey: keyof Room
	label: () => string
}> = [
	{
		payloadKey: 'hasPhone',
		responseKey: 'hasPhone',
		label: () => t('calendar_resource_management', 'Phone'),
	},
	{
		payloadKey: 'hasVideo',
		responseKey: 'hasVideoConferencing',
		label: () => t('calendar_resource_management', 'Video conferencing'),
	},
	{
		payloadKey: 'hasTv',
		responseKey: 'hasTv',
		label: () => t('calendar_resource_management', 'TV'),
	},
	{
		payloadKey: 'hasProjector',
		responseKey: 'hasProjector',
		label: () => t('calendar_resource_management', 'Projector'),
	},
	{
		payloadKey: 'hasWhiteboard',
		responseKey: 'hasWhiteboard',
		label: () => t('calendar_resource_management', 'Whiteboard'),
	},
	{
		payloadKey: 'wheelchairAccessible',
		responseKey: 'isWheelchairAccessible',
		label: () => t('calendar_resource_management', 'Wheelchair accessible'),
	},
]

/**
 * Equipment flags of a room as the endpoints expect them.
 *
 * @param room The room to read the flags from, or null for no equipment
 * @return Equipment payload keys mapped to whether the room provides them
 */
export function roomEquipment(room: Room | null): Record<EquipmentKey, boolean> {
	return Object.fromEntries(EQUIPMENT_TYPES.map((item) => [item.payloadKey, room !== null && Boolean(room[item.responseKey])])) as Record<EquipmentKey, boolean>
}

/**
 * Create a formatter that lists items the way the user's language does.
 * Themes can add languages whose codes are no valid BCP 47 tags, those fall
 * back to the browser language.
 *
 * @return The list formatter
 */
function createListFormat(): Intl.ListFormat {
	const options: Intl.ListFormatOptions = { type: 'conjunction', style: 'narrow' }
	try {
		return new Intl.ListFormat(getLanguage(), options)
	} catch {
		return new Intl.ListFormat(undefined, options)
	}
}

const listFormat = createListFormat()

/**
 * Translated list of the equipment a room provides.
 *
 * @param room The room
 * @return Equipment names, listed as the user's language lists items
 */
export function equipmentSummary(room: Room): string {
	const available = EQUIPMENT_TYPES
		.filter((item) => room[item.responseKey])
		.map((item) => item.label())

	return available.length ? listFormat.format(available) : '-'
}
