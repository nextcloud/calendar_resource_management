/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const baseUrl = '/apps/calendar_resource_management/admin'

/**
 * Fetch all buildings
 *
 * @return {Promise<Array>} List of buildings
 */
export async function fetchBuildings() {
	const response = await axios.get(generateUrl(`${baseUrl}/buildings`))
	return response.data
}

/**
 * Create a new building
 *
 * @param {string} name Building name
 * @param {string} address Building address
 * @return {Promise<object>} Created building data
 */
export async function createBuilding(name, address) {
	const response = await axios.post(generateUrl(`${baseUrl}/buildings`), {
		name,
		address,
	})
	return response.data
}

/**
 * Delete a building
 *
 * @param {number} id Building ID
 * @return {Promise<object>} Response data
 */
export async function deleteBuilding(id) {
	const response = await axios.delete(generateUrl(`${baseUrl}/buildings/${id}`))
	return response.data
}

/**
 * Fetch all stories
 *
 * @return {Promise<Array>} List of stories
 */
export async function fetchStories() {
	const response = await axios.get(generateUrl(`${baseUrl}/stories`))
	return response.data
}

/**
 * Create a new story
 *
 * @param {string} name Story name
 * @param {number} buildingId Building ID
 * @return {Promise<object>} Created story data
 */
export async function createStory(name, buildingId) {
	const response = await axios.post(generateUrl(`${baseUrl}/stories`), {
		name,
		buildingId,
	})
	return response.data
}

/**
 * Delete a story
 *
 * @param {number} id Story ID
 * @return {Promise<object>} Response data
 */
export async function deleteStory(id) {
	const response = await axios.delete(generateUrl(`${baseUrl}/stories/${id}`))
	return response.data
}

/**
 * Fetch all rooms
 *
 * @return {Promise<Array>} List of rooms
 */
export async function fetchRooms() {
	const response = await axios.get(generateUrl(`${baseUrl}/rooms`))
	return response.data
}

/**
 * Create a new room
 *
 * @param {object} data Room data
 * @return {Promise<object>} Created room data
 */
export async function createRoom(data) {
	const response = await axios.post(generateUrl(`${baseUrl}/rooms`), data)
	return response.data
}

/**
 * Delete a room
 *
 * @param {number} id Room ID
 * @return {Promise<object>} Response data
 */
export async function deleteRoom(id) {
	const response = await axios.delete(generateUrl(`${baseUrl}/rooms/${id}`))
	return response.data
}

/**
 * Fetch all resources
 *
 * @return {Promise<Array>} List of resources
 */
export async function fetchResources() {
	const response = await axios.get(generateUrl(`${baseUrl}/resources`))
	return response.data
}

/**
 * Create a new resource
 *
 * @param {object} data Resource data
 * @return {Promise<object>} Created resource data
 */
export async function createResource(data) {
	const response = await axios.post(generateUrl(`${baseUrl}/resources`), data)
	return response.data
}

/**
 * Delete a resource
 *
 * @param {number} id Resource ID
 * @return {Promise<object>} Response data
 */
export async function deleteResource(id) {
	const response = await axios.delete(generateUrl(`${baseUrl}/resources/${id}`))
	return response.data
}
