/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { BookableType, PersonalBookingCalendars } from '../types/types.ts'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const baseUrl = '/apps/calendar_resource_management/personal/booking-calendars'

/**
 * Fetch the booking calendars the current user may add
 *
 * @return The calendars and the preferences of the user
 */
export async function fetchBookingCalendars(): Promise<PersonalBookingCalendars> {
	const response = await axios.get<PersonalBookingCalendars>(generateUrl(baseUrl))
	return response.data
}

/**
 * Add a booking calendar to the calendars of the current user or remove it
 *
 * @param type Type of the room, resource or vehicle
 * @param id ID of the room, resource or vehicle
 * @param subscribed Whether the calendar should be shown
 */
export async function saveSubscribed(type: BookableType, id: number, subscribed: boolean): Promise<void> {
	await axios.put(generateUrl(`${baseUrl}/${type}/${id}`), { subscribed })
}

/**
 * Save whether new rooms, resources and vehicles are added automatically
 *
 * @param autoSubscribeNew Whether to add new booking calendars
 */
export async function saveAutoSubscribeNew(autoSubscribeNew: boolean): Promise<void> {
	await axios.put(generateUrl(`${baseUrl}/preferences`), { autoSubscribeNew })
}
