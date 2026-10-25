/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type {
	BookableType,
	BookingCalendarSettings,
	Building,
	Group,
	NewResource,
	NewRoom,
	NewVehicle,
	Resource,
	Room,
	Story,
	UpdateBuilding,
	UpdateResource,
	UpdateRoom,
	UpdateStory,
	UpdateVehicle,
	User,
	Vehicle,
} from '../types/types.ts'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const baseUrl = '/apps/calendar_resource_management/admin'

/**
 * Fetch the accounts that can be picked as the contact person of a room
 *
 * @param search Filter for the display name
 * @return List of accounts
 */
export async function fetchUsers(search = ''): Promise<User[]> {
	const response = await axios.get<User[]>(generateUrl(`${baseUrl}/users`), {
		params: { search },
	})
	return response.data
}

/**
 * Fetch all buildings
 *
 * @return List of buildings
 */
export async function fetchBuildings(): Promise<Building[]> {
	const response = await axios.get<Building[]>(generateUrl(`${baseUrl}/buildings`))
	return response.data
}

/**
 * Create a new building
 *
 * @param name Building name
 * @param address Building address
 * @return Created building
 */
export async function createBuilding(name: string, address: string): Promise<Building> {
	const response = await axios.post(generateUrl(`${baseUrl}/buildings`), {
		name,
		address,
	})
	return response.data
}

/**
 * Change a building
 *
 * @param id Building ID
 * @param data Building data
 * @return Updated building
 */
export async function updateBuilding(id: number, data: UpdateBuilding): Promise<Building> {
	const response = await axios.put(generateUrl(`${baseUrl}/buildings/${id}`), data)
	return response.data
}

/**
 * Delete a building
 *
 * @param id Building ID
 */
export async function deleteBuilding(id: number): Promise<void> {
	await axios.delete(generateUrl(`${baseUrl}/buildings/${id}`))
}

/**
 * Fetch all stories
 *
 * @return List of stories
 */
export async function fetchStories(): Promise<Story[]> {
	const response = await axios.get<Story[]>(generateUrl(`${baseUrl}/stories`))
	return response.data
}

/**
 * Create a new story
 *
 * @param name Story name
 * @param buildingId Building ID
 * @return Created story
 */
export async function createStory(name: string, buildingId: number | null): Promise<Story> {
	const response = await axios.post(generateUrl(`${baseUrl}/stories`), {
		name,
		buildingId,
	})
	return response.data
}

/**
 * Change a story
 *
 * @param id Story ID
 * @param data Story data
 * @return Updated story
 */
export async function updateStory(id: number, data: UpdateStory): Promise<Story> {
	const response = await axios.put(generateUrl(`${baseUrl}/stories/${id}`), data)
	return response.data
}

/**
 * Delete a story
 *
 * @param id Story ID
 */
export async function deleteStory(id: number): Promise<void> {
	await axios.delete(generateUrl(`${baseUrl}/stories/${id}`))
}

/**
 * Fetch all rooms
 *
 * @return List of rooms
 */
export async function fetchRooms(): Promise<Room[]> {
	const response = await axios.get<Room[]>(generateUrl(`${baseUrl}/rooms`))
	return response.data
}

/**
 * Create a new room
 *
 * @param data Room data
 * @return Created room
 */
export async function createRoom(data: NewRoom): Promise<Room> {
	const response = await axios.post(generateUrl(`${baseUrl}/rooms`), data)
	return response.data
}

/**
 * Change a room and its restrictions
 *
 * @param id Room ID
 * @param data Room data
 * @return Updated room
 */
export async function updateRoom(id: number, data: UpdateRoom): Promise<Room> {
	const response = await axios.put(generateUrl(`${baseUrl}/rooms/${id}`), data)
	return response.data
}

/**
 * Delete a room
 *
 * @param id Room ID
 */
export async function deleteRoom(id: number): Promise<void> {
	await axios.delete(generateUrl(`${baseUrl}/rooms/${id}`))
}

/**
 * Fetch all resources
 *
 * @return List of resources
 */
export async function fetchResources(): Promise<Resource[]> {
	const response = await axios.get<Resource[]>(generateUrl(`${baseUrl}/resources`))
	return response.data
}

/**
 * Create a new resource
 *
 * @param data Resource data
 * @return Created resource
 */
export async function createResource(data: NewResource): Promise<Resource> {
	const response = await axios.post(generateUrl(`${baseUrl}/resources`), data)
	return response.data
}

/**
 * Change a resource and its restrictions
 *
 * @param id Resource ID
 * @param data Resource data
 * @return Updated resource
 */
export async function updateResource(id: number, data: UpdateResource): Promise<Resource> {
	const response = await axios.put(generateUrl(`${baseUrl}/resources/${id}`), data)
	return response.data
}

/**
 * Delete a resource
 *
 * @param id Resource ID
 */
export async function deleteResource(id: number): Promise<void> {
	await axios.delete(generateUrl(`${baseUrl}/resources/${id}`))
}

/**
 * Fetch the groups that can be picked as viewer or default groups
 *
 * @param search Filter for the group name
 * @return List of groups
 */
export async function fetchGroups(search = ''): Promise<Group[]> {
	const response = await axios.get<Group[]>(generateUrl(`${baseUrl}/groups`), {
		params: { search },
	})
	return response.data
}

/**
 * Fetch who sees the booking calendars
 *
 * @return Viewer groups and default groups
 */
export async function fetchBookingCalendarSettings(): Promise<BookingCalendarSettings> {
	const response = await axios.get<BookingCalendarSettings>(generateUrl(`${baseUrl}/booking-calendars`))
	return response.data
}

/**
 * Replace the groups whose members may add booking calendars
 *
 * @param groupIds Group IDs
 * @return The saved groups
 */
export async function saveViewerGroups(groupIds: string[]): Promise<Group[]> {
	const response = await axios.put<Group[]>(generateUrl(`${baseUrl}/booking-calendars/viewer-groups`), {
		groupIds,
	})
	return response.data
}

/**
 * Replace the groups that get a booking calendar without picking it
 *
 * @param entityType Type of the room, resource or vehicle
 * @param entityId ID of the room, resource or vehicle
 * @param groupIds Group IDs, all of them viewer groups
 */
export async function saveDefaultGroups(entityType: BookableType, entityId: number, groupIds: string[]): Promise<void> {
	await axios.put(generateUrl(`${baseUrl}/booking-calendars/${entityType}/${entityId}/default-groups`), {
		groupIds,
	})
}

/**
 * Fetch all vehicles
 *
 * @return List of vehicles
 */
export async function fetchVehicles(): Promise<Vehicle[]> {
	const response = await axios.get<Vehicle[]>(generateUrl(`${baseUrl}/vehicles`))
	return response.data
}

/**
 * Create a new vehicle
 *
 * @param data Vehicle data
 * @return Created vehicle
 */
export async function createVehicle(data: NewVehicle): Promise<Vehicle> {
	const response = await axios.post(generateUrl(`${baseUrl}/vehicles`), data)
	return response.data
}

/**
 * Change a vehicle and its restrictions
 *
 * @param id Vehicle ID
 * @param data Vehicle data
 * @return Updated vehicle
 */
export async function updateVehicle(id: number, data: UpdateVehicle): Promise<Vehicle> {
	const response = await axios.put(generateUrl(`${baseUrl}/vehicles/${id}`), data)
	return response.data
}

/**
 * Delete a vehicle
 *
 * @param id Vehicle ID
 */
export async function deleteVehicle(id: number): Promise<void> {
	await axios.delete(generateUrl(`${baseUrl}/vehicles/${id}`))
}
