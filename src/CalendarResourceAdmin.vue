<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewBuilding, NewResource, NewRoom, NewStory, Resource, Room, Story } from './services/adminService.ts'

import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { getLoggerBuilder } from '@nextcloud/logger'
import { computed, onMounted, ref, useTemplateRef } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import BuildingsSection from './components/BuildingsSection.vue'
import ResourcesSection from './components/ResourcesSection.vue'
import RoomsSection from './components/RoomsSection.vue'
import StoriesSection from './components/StoriesSection.vue'
import * as api from './services/adminService.ts'

interface PendingDelete {
	message: string
	action: () => Promise<void>
}

const logger = getLoggerBuilder()
	.setApp('calendar_resource_management')
	.detectUser()
	.build()

const buildings = ref<Building[]>([])
const stories = ref<Story[]>([])
const rooms = ref<Room[]>([])
const resources = ref<Resource[]>([])

// Blocks the submit buttons while a create request is running
const creating = ref({
	building: false,
	story: false,
	room: false,
	resource: false,
})

const pendingDelete = ref<PendingDelete | null>(null)

const buildingsSection = useTemplateRef<InstanceType<typeof BuildingsSection>>('buildings')
const storiesSection = useTemplateRef<InstanceType<typeof StoriesSection>>('stories')
const roomsSection = useTemplateRef<InstanceType<typeof RoomsSection>>('rooms')
const resourcesSection = useTemplateRef<InstanceType<typeof ResourcesSection>>('resources')

const deleteDialogButtons = computed(() => [
	{
		label: t('calendar_resource_management', 'Cancel'),
		callback: () => {
			pendingDelete.value = null
		},
	},
	{
		label: t('calendar_resource_management', 'Delete'),
		variant: 'error' as const,
		callback: () => {
			const pending = pendingDelete.value
			pendingDelete.value = null
			pending?.action()
		},
	},
])

/**
 * Run an API call and report failures to the user instead of throwing.
 *
 * @param call The API call to run
 * @param errorMessage Message shown to the user if the call fails
 * @return The response data, or null if the call failed
 */
async function request<T>(call: () => Promise<T>, errorMessage: string): Promise<T | null> {
	try {
		return await call()
	} catch (error) {
		// The endpoints answer with an untranslated message, so only log it
		logger.error(errorMessage, { error })
		showError(errorMessage)
		return null
	}
}

/**
 * Reload the buildings.
 */
async function loadBuildings(): Promise<void> {
	const loaded = await request(
		api.fetchBuildings,
		t('calendar_resource_management', 'Could not load buildings'),
	)
	if (loaded !== null) {
		buildings.value = loaded
	}
}

/**
 * Reload the floors.
 */
async function loadStories(): Promise<void> {
	const loaded = await request(
		api.fetchStories,
		t('calendar_resource_management', 'Could not load floors'),
	)
	if (loaded !== null) {
		stories.value = loaded
	}
}

/**
 * Reload the rooms.
 */
async function loadRooms(): Promise<void> {
	const loaded = await request(
		api.fetchRooms,
		t('calendar_resource_management', 'Could not load rooms'),
	)
	if (loaded !== null) {
		rooms.value = loaded
	}
}

/**
 * Reload the resources.
 */
async function loadResources(): Promise<void> {
	const loaded = await request(
		api.fetchResources,
		t('calendar_resource_management', 'Could not load resources'),
	)
	if (loaded !== null) {
		resources.value = loaded
	}
}

onMounted(async () => {
	await Promise.all([
		loadBuildings(),
		loadStories(),
		loadRooms(),
		loadResources(),
	])
})

/**
 * Create the building entered in the buildings section.
 *
 * @param building The building to create
 */
async function createBuilding(building: NewBuilding): Promise<void> {
	creating.value.building = true
	try {
		const created = await request(
			() => api.createBuilding(building.name, building.address),
			t('calendar_resource_management', 'Could not create building'),
		)
		if (created !== null) {
			buildingsSection.value?.reset()
			await loadBuildings()
		}
	} finally {
		creating.value.building = false
	}
}

/**
 * Create the floor entered in the floors section.
 *
 * @param story The floor to create
 */
async function createStory(story: NewStory): Promise<void> {
	creating.value.story = true
	try {
		const created = await request(
			() => api.createStory(story.name, story.buildingId),
			t('calendar_resource_management', 'Could not create floor'),
		)
		if (created !== null) {
			storiesSection.value?.reset()
			await loadStories()
		}
	} finally {
		creating.value.story = false
	}
}

/**
 * Create the room entered in the rooms section.
 *
 * @param room The room to create
 */
async function createRoom(room: NewRoom): Promise<void> {
	creating.value.room = true
	try {
		const created = await request(
			() => api.createRoom(room),
			t('calendar_resource_management', 'Could not create room'),
		)
		if (created !== null) {
			roomsSection.value?.reset()
			await loadRooms()
		}
	} finally {
		creating.value.room = false
	}
}

/**
 * Create the resource entered in the resources section.
 *
 * @param resource The resource to create
 */
async function createResource(resource: NewResource): Promise<void> {
	creating.value.resource = true
	try {
		const created = await request(
			() => api.createResource(resource),
			t('calendar_resource_management', 'Could not create resource'),
		)
		if (created !== null) {
			resourcesSection.value?.reset()
			await loadResources()
		}
	} finally {
		creating.value.resource = false
	}
}

/**
 * Ask for confirmation before deleting a building.
 *
 * @param id The building to delete
 */
function confirmDeleteBuilding(id: number): void {
	pendingDelete.value = {
		message: t('calendar_resource_management', 'Do you really want to delete this building?'),
		action: async () => {
			const deleted = await request(
				() => api.deleteBuilding(id),
				t('calendar_resource_management', 'Could not delete building'),
			)
			if (deleted !== null) {
				// Stories, rooms and resources reference the building
				await Promise.all([
					loadBuildings(),
					loadStories(),
					loadRooms(),
					loadResources(),
				])
			}
		},
	}
}

/**
 * Ask for confirmation before deleting a floor.
 *
 * @param id The floor to delete
 */
function confirmDeleteStory(id: number): void {
	pendingDelete.value = {
		message: t('calendar_resource_management', 'Do you really want to delete this floor?'),
		action: async () => {
			const deleted = await request(
				() => api.deleteStory(id),
				t('calendar_resource_management', 'Could not delete floor'),
			)
			if (deleted !== null) {
				// Rooms reference the story
				await Promise.all([
					loadStories(),
					loadRooms(),
				])
			}
		},
	}
}

/**
 * Ask for confirmation before deleting a room.
 *
 * @param id The room to delete
 */
function confirmDeleteRoom(id: number): void {
	pendingDelete.value = {
		message: t('calendar_resource_management', 'Do you really want to delete this room?'),
		action: async () => {
			const deleted = await request(
				() => api.deleteRoom(id),
				t('calendar_resource_management', 'Could not delete room'),
			)
			if (deleted !== null) {
				await loadRooms()
			}
		},
	}
}

/**
 * Ask for confirmation before deleting a resource.
 *
 * @param id The resource to delete
 */
function confirmDeleteResource(id: number): void {
	pendingDelete.value = {
		message: t('calendar_resource_management', 'Do you really want to delete this resource?'),
		action: async () => {
			const deleted = await request(
				() => api.deleteResource(id),
				t('calendar_resource_management', 'Could not delete resource'),
			)
			if (deleted !== null) {
				await loadResources()
			}
		},
	}
}

/**
 * Forget the pending deletion when the dialog is dismissed.
 *
 * @param open Whether the dialog is open
 */
function onDeleteDialogToggle(open: boolean): void {
	if (!open) {
		pendingDelete.value = null
	}
}
</script>

<template>
	<div class="crm-admin">
		<BuildingsSection
			ref="buildings"
			:buildings="buildings"
			:loading="creating.building"
			@create="createBuilding"
			@delete="confirmDeleteBuilding" />

		<StoriesSection
			ref="stories"
			:buildings="buildings"
			:loading="creating.story"
			:stories="stories"
			@create="createStory"
			@delete="confirmDeleteStory" />

		<RoomsSection
			ref="rooms"
			:buildings="buildings"
			:loading="creating.room"
			:rooms="rooms"
			:stories="stories"
			@create="createRoom"
			@delete="confirmDeleteRoom" />

		<ResourcesSection
			ref="resources"
			:buildings="buildings"
			:loading="creating.resource"
			:resources="resources"
			@create="createResource"
			@delete="confirmDeleteResource" />

		<NcDialog
			:buttons="deleteDialogButtons"
			:message="pendingDelete?.message ?? ''"
			:name="t('calendar_resource_management', 'Confirm deletion')"
			:open="pendingDelete !== null"
			@update:open="onDeleteDialogToggle" />
	</div>
</template>

<style lang="scss" scoped>
.crm-admin {
	// Every form field and table of all sections shares this width
	--crm-content-max-width: 700px;

	// Text fields, selects and the submit button all line up on the same edges
	:deep(.crm-field) {
		margin-block-end: calc(2 * var(--default-grid-baseline));
		max-width: var(--crm-content-max-width);
		min-width: 0;
		width: 100%;
	}

	:deep(.crm-equipment) {
		margin-block: calc(2 * var(--default-grid-baseline));
		max-width: var(--crm-content-max-width);

		legend {
			font-weight: bold;
		}
	}

	:deep(.crm-table) {
		border-collapse: collapse;
		max-width: 100%;
		width: 100%;

		th,
		td {
			border-block-end: 1px solid var(--color-border);
			// Leaves 4px above and below the delete buttons
			padding: var(--default-grid-baseline) calc(2 * var(--default-grid-baseline));
			text-align: start;
		}

		th {
			color: var(--color-text-maxcontrast);
			font-weight: normal;
		}

		// The wrapper already draws the outer border
		tbody tr:last-child td {
			border-block-end: none;
		}

		tbody tr:hover {
			background-color: var(--color-background-hover);
		}
	}

	:deep(.crm-table-wrapper) {
		border: 1px solid var(--color-border-dark);
		// Clips the rows to the rounded corners, together with the overflow
		border-radius: var(--border-radius-element);
		margin-block-end: calc(4 * var(--default-grid-baseline));
		max-width: var(--crm-content-max-width);
		overflow-x: auto;
	}

	// The room table has too many columns to fit the shared width
	:deep(.crm-table-wrapper--wide) {
		max-width: 100%;
	}
}
</style>
