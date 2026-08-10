/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/** An account that can be picked as the contact person of a room */
export interface User {
	id: string
	displayName: string
}

export interface Building {
	id: number
	name: string
	address: string
}

export interface Story {
	id: number
	name: string
	buildingId: number
}

export interface Room {
	id: number
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
}

export interface Resource {
	id: number
	name: string
	email: string
	resourceType: string
	buildingId: number
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
export async function createBuilding(name: string, address: string): Promise<Pick<Building, 'id' | 'name'>> {
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
 * @return Empty response data
 */
export async function deleteBuilding(id: number): Promise<unknown> {
	const response = await axios.delete(generateUrl(`${baseUrl}/buildings/${id}`))
	return response.data
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
export async function createStory(name: string, buildingId: number | null): Promise<Pick<Story, 'id' | 'name'>> {
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
 * @return Empty response data
 */
export async function deleteStory(id: number): Promise<unknown> {
	const response = await axios.delete(generateUrl(`${baseUrl}/stories/${id}`))
	return response.data
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
export async function createRoom(data: NewRoom): Promise<Pick<Room, 'id' | 'name'>> {
	const response = await axios.post(generateUrl(`${baseUrl}/rooms`), data)
	return response.data
}

/**
 * Delete a room
 *
 * @param id Room ID
 * @return Empty response data
 */
export async function deleteRoom(id: number): Promise<unknown> {
	const response = await axios.delete(generateUrl(`${baseUrl}/rooms/${id}`))
	return response.data
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
export async function createResource(data: NewResource): Promise<Pick<Resource, 'id' | 'name'>> {
	const response = await axios.post(generateUrl(`${baseUrl}/resources`), data)
	return response.data
}

/**
 * Delete a resource
 *
 * @param id Resource ID
 * @return Empty response data
 */
export async function deleteResource(id: number): Promise<unknown> {
	const response = await axios.delete(generateUrl(`${baseUrl}/resources/${id}`))
	return response.data
}
