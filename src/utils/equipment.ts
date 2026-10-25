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
 * All equipment flags switched off.
 *
 * @return Equipment payload keys mapped to false
 */
export function noEquipment(): Record<EquipmentKey, boolean> {
	return Object.fromEntries(EQUIPMENT_TYPES.map((item) => [item.payloadKey, false])) as Record<EquipmentKey, boolean>
}

/**
 * The equipment of a room as the update endpoint expects it.
 *
 * @param room The room as the listing returns it
 * @return Equipment payload keys mapped to whether the room has it
 */
export function equipmentOf(room: Room): Record<EquipmentKey, boolean> {
	return Object.fromEntries(EQUIPMENT_TYPES.map((item) => [item.payloadKey, Boolean(room[item.responseKey])])) as Record<EquipmentKey, boolean>
}
