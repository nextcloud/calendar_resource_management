/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type {
	Building,
	NewResource,
	NewRoom,
	Resource,
	Room,
	Story,
	User,
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
 * Delete a resource
 *
 * @param id Resource ID
 */
export async function deleteResource(id: number): Promise<void> {
	await axios.delete(generateUrl(`${baseUrl}/resources/${id}`))
}
