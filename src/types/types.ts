/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** Anything with a name that can be shown in a table or a select */
export interface NamedEntity {
	id: number
	name: string
}

export interface SelectOption {
	id: number
	label: string
}

/** An account that can be picked as the contact person of a room */
export interface User {
	id: string
	displayName: string
}

export interface Building {
	id: number
	name: string
	description: string
	address: string
	isWheelchairAccessible: boolean
}

export interface Story {
	id: number
	name: string
	buildingId: number
}

export interface Room {
	id: number
	uid: string
	name: string
	email: string
	roomType: string
	storyId: number
	roomNumber: string
	contactPersonUserId: string
	capacity: number | null
	hasPhone: boolean
	hasVideoConferencing: boolean
	hasTv: boolean
	hasProjector: boolean
	hasWhiteboard: boolean
	isWheelchairAccessible: boolean
	restricted: boolean
}

export interface Resource {
	id: number
	uid: string
	name: string
	email: string
	resourceType: string
	buildingId: number
	contactPersonUserId: string
	restricted: boolean
}

export interface NewBuilding {
	name: string
	address: string
}

export interface NewStory {
	name: string
	buildingId: number | null
}

/** Equipment flags as the create endpoint expects them, they differ from the listing */
export type EquipmentKey = 'hasPhone' | 'hasVideo' | 'hasTv' | 'hasProjector' | 'hasWhiteboard' | 'wheelchairAccessible'

export type NewRoom = Record<EquipmentKey, boolean> & {
	name: string
	email: string
	roomType: string
	roomNumber: string
	contactPersonUserId: string
	capacity: number | null
	storyId: number | null
}

export interface NewResource {
	name: string
	email: string
	resourceType: string
	buildingId: number | null
}

/** Rooms, resources and vehicles have a booking calendar */
export type BookableType = 'room' | 'resource' | 'vehicle'

export interface Group {
	id: string
	displayName: string
}

export interface GroupOption {
	id: string
	label: string
}

export interface DefaultGroup {
	entityType: BookableType
	entityId: number
	groupId: string
}

/** A room, resource or vehicle as the booking calendar settings list it */
export interface BookableEntity {
	type: BookableType
	id: number
	name: string
	/** Viewer groups that may see the entity, only these can be default groups */
	allowedGroupIds: string[]
}

export interface BookingCalendarSettings {
	viewerGroups: Group[]
	entities: BookableEntity[]
	defaultGroups: DefaultGroup[]
}

/** A booking calendar the current user may add to their calendars */
export interface BookingCalendar {
	type: BookableType
	id: number
	name: string
	buildingName: string
	storyName: string
	capacity: number | null
	subscribed: boolean
	isDefault: boolean
}

export interface PersonalBookingCalendars {
	autoSubscribeNew: boolean
	calendars: BookingCalendar[]
}
