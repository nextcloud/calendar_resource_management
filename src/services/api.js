/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { isSet } from '../utils.js'

const APP_ID = 'calendar_resource_management'
const API_BASE = 'apps/calendar_resource_management/api/v1'

/**
 * Build a full OCS URL for a path relative to the app's API base.
 *
 * @param {string} path the path relative to API_BASE (e.g. '/buildings')
 * @return {string} the absolute OCS URL
 */
function apiUrl(path) {
	return generateOcsUrl(API_BASE + path)
}

/**
 * Unwrap the OCS envelope from an axios response
 *
 * @param {object} response the axios response
 * @return {*} the unwrapped `ocs.data`
 * @throws {Error} when the OCS meta status is not 'ok', carrying the meta message
 */
function unwrap(response) {
	const ocs = response?.data?.ocs ?? null
	const meta = ocs?.meta ?? null
	if (!meta || meta.status !== 'ok') {
		const message = meta?.message
			? meta.message
			: t(APP_ID, 'Request failed') + ' (' + (meta?.statuscode ?? response?.status) + ')'
		throw new Error(message)
	}
	return ocs.data
}

/**
 * Drop blank values from a filter map
 *
 * @param {object} params the raw filter values
 * @return {object} the query parameters worth sending
 */
function queryParams(params) {
	const query = {}
	for (const [key, value] of Object.entries(params)) {
		if (isSet(value)) {
			query[key] = value
		}
	}
	return query
}

/**
 * Fetch one page of records for an entity path 
 *
 * @param {string} path the entity path (e.g. '/buildings')
 * @param {object} [params] filter query parameters; empty values are dropped
 * @param {object} [pagination] the page to fetch
 * @param {number} [pagination.page] the 1-based page number
 * @return {Promise<{items: Array, total: number, page: number, limit: number, pages: number}>} the page envelope
 */
export async function fetchPage(path, params = {}, { page = 1 } = {}) {
	const query = queryParams(params)
	query.page = page

	const response = await axios.get(apiUrl(path), {
		params: query,
		headers: { Accept: 'application/json' },
	})

	const data = unwrap(response)

	return {
		items: Array.isArray(data?.items) ? data.items : [],
		total: data?.total ?? 0,
		page: data?.page ?? 0,
		limit: data?.limit ?? 0,
		pages: data?.pages ?? 0,
	}
}

/**
 * Fetch a complete, unpaged option list; a query string in the path is used when no params are passed.
 *
 * @param {string} path the whole path under the app's API base, e.g. `/stories/options?buildingId=4`
 * @param {object} [params] query parameters, as for {@link fetchPage}; override the path's query string
 * @return {Promise<Array>} the list; `[]` for a non-array payload
 */
export async function fetchOptions(path, params = {}) {
	const [url, query] = path.split('?')
	const inlineParams = query !== undefined && Object.keys(params).length === 0
		? Object.fromEntries(new URLSearchParams(query))
		: null

	const response = await axios.get(apiUrl(url), {
		params: queryParams(inlineParams ?? params),
		headers: { Accept: 'application/json' },
	})
	const data = unwrap(response)

	return Array.isArray(data) ? data : []
}

/**
 * Create a record.
 *
 * @param {string} path the entity path
 * @param {object} body the payload
 * @return {Promise<object>} the created record
 */
export async function create(path, body) {
	const response = await axios.post(apiUrl(path), body, { headers: { Accept: 'application/json' } })
	return unwrap(response)
}

/**
 * Update a record.
 *
 * @param {string} path the entity path
 * @param {number|string} id the record id
 * @param {object} body the payload
 * @return {Promise<object>} the updated record
 */
export async function update(path, id, body) {
	const response = await axios.put(apiUrl(path + '/' + encodeURIComponent(id)), body, { headers: { Accept: 'application/json' } })
	return unwrap(response)
}

/**
 * Delete a record.
 *
 * @param {string} path the entity path
 * @param {number|string} id the record id
 * @return {Promise<*>} the OCS data payload
 */
export async function destroy(path, id) {
	const response = await axios.delete(apiUrl(path + '/' + encodeURIComponent(id)), { headers: { Accept: 'application/json' } })
	return unwrap(response)
}

/**
 * Fetch all stories of a building
 *
 * @param {number|string} buildingId the parent building id
 * @return {Promise<Array>} the story records
 */
export async function storiesByBuilding(buildingId) {
	return await fetchOptions('/stories/options', { buildingId })
}

/**
 * Search users 
 *
 * @param {string} term the search term
 * @return {Promise<Array>} the matched users
 * @throws {Error} when the request fails or the OCS meta status is not 'ok'
 */
export async function searchUsers(term) {
	const params = {
		search: term ?? '',
	}
	const response = await axios.get(generateOcsUrl('/cloud/users/details'), {
		params,
		headers: {
			'OCS-APIREQUEST': 'true',
			Accept: 'application/json',
		},
	})
	const data = unwrap(response)
	const users = data.users
	if (!users || typeof users !== 'object') {
		return []
	}
	return Object.values(users)
}

/**
 * Search groups 
 *
 * @param {string} term the search term
 * @param {number} [limit] the most groups to return
 * @return {Promise<Array<{id: string, displayname: string}>>} the matched groups
 * @throws {Error} when the request fails or the OCS meta status is not 'ok'
 */
export async function searchGroups(term, limit = 25) {
	const response = await axios.get(generateOcsUrl('/cloud/groups/details'), {
		params: {
			search: term ?? '',
			limit,
		},
		headers: {
			'OCS-APIREQUEST': 'true',
			Accept: 'application/json',
		},
	})
	const data = unwrap(response)
	return Array.isArray(data?.groups) ? data.groups : []
}
