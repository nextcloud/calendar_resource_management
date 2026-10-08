/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { EquipmentKey, Room } from '../types/types.ts'

import { translate as t } from '@nextcloud/l10n'

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
 * Translated list of the equipment a room provides.
 *
 * @param room The room
 * @return Comma separated equipment names
 */
export function equipmentSummary(room: Room): string {
	const available = EQUIPMENT_TYPES
		.filter((item) => room[item.responseKey])
		.map((item) => item.label())

	return available.length ? available.join(', ') : '-'
}
