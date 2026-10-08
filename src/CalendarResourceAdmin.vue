<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Ref } from 'vue'
import type { Building, NamedEntity, NewBuilding, NewResource, NewRoom, NewStory, Resource, Room, Story } from './types/types.ts'

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

type TabId = 'buildings' | 'stories' | 'rooms' | 'resources'

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

// Blocks the dialog submit buttons while a create or update request is running
const saving = ref({
	building: false,
	story: false,
	room: false,
	resource: false,
})

const pendingDelete = ref<PendingDelete | null>(null)

const tabs: { id: TabId, label: string }[] = [
	{ id: 'buildings', label: t('calendar_resource_management', 'Buildings') },
	{ id: 'stories', label: t('calendar_resource_management', 'Floors') },
	{ id: 'rooms', label: t('calendar_resource_management', 'Rooms') },
	{ id: 'resources', label: t('calendar_resource_management', 'Resources') },
]

const activeTab = ref<TabId>('buildings')
const tabButtons = useTemplateRef<HTMLButtonElement[]>('tabButtons')

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
 * Add a created entity to a list, keeping the name order the endpoints list in.
 *
 * @param list The list to add the entity to
 * @param entity The created entity
 */
function addSorted<T extends NamedEntity>(list: Ref<T[]>, entity: T): void {
	list.value = [...list.value, entity].sort((a, b) => a.name.localeCompare(b.name))
}

/**
 * Replace an updated entity in a list, keeping the name order the endpoints list in.
 *
 * @param list The list holding the entity
 * @param entity The updated entity
 */
function replaceSorted<T extends NamedEntity>(list: Ref<T[]>, entity: T): void {
	list.value = list.value
		.map((candidate) => candidate.id === entity.id ? entity : candidate)
		.sort((a, b) => a.name.localeCompare(b.name))
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
	saving.value.building = true
	try {
		const created = await request(
			() => api.createBuilding(building.name, building.address),
			t('calendar_resource_management', 'Could not create building'),
		)
		if (created !== null) {
			buildingsSection.value?.close()
			addSorted(buildings, created)
		}
	} finally {
		saving.value.building = false
	}
}

/**
 * Create the floor entered in the floors section.
 *
 * @param story The floor to create
 */
async function createStory(story: NewStory): Promise<void> {
	saving.value.story = true
	try {
		const created = await request(
			() => api.createStory(story.name, story.buildingId),
			t('calendar_resource_management', 'Could not create floor'),
		)
		if (created !== null) {
			storiesSection.value?.close()
			addSorted(stories, created)
		}
	} finally {
		saving.value.story = false
	}
}

/**
 * Create the room entered in the rooms section.
 *
 * @param room The room to create
 */
async function createRoom(room: NewRoom): Promise<void> {
	saving.value.room = true
	try {
		const created = await request(
			() => api.createRoom(room),
			t('calendar_resource_management', 'Could not create room'),
		)
		if (created !== null) {
			roomsSection.value?.close()
			addSorted(rooms, created)
		}
	} finally {
		saving.value.room = false
	}
}

/**
 * Create the resource entered in the resources section.
 *
 * @param resource The resource to create
 */
async function createResource(resource: NewResource): Promise<void> {
	saving.value.resource = true
	try {
		const created = await request(
			() => api.createResource(resource),
			t('calendar_resource_management', 'Could not create resource'),
		)
		if (created !== null) {
			resourcesSection.value?.close()
			addSorted(resources, created)
		}
	} finally {
		saving.value.resource = false
	}
}

/**
 * Save the changes to a building edited in the buildings section.
 *
 * @param id The building to update
 * @param building The entered building
 */
async function updateBuilding(id: number, building: NewBuilding): Promise<void> {
	saving.value.building = true
	try {
		const updated = await request(
			() => api.updateBuilding(id, building.name, building.address),
			t('calendar_resource_management', 'Could not update building'),
		)
		if (updated !== null) {
			buildingsSection.value?.close()
			replaceSorted(buildings, updated)
		}
	} finally {
		saving.value.building = false
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
 * Save the changes to a floor edited in the floors section.
 *
 * @param id The floor to update
 * @param story The entered floor
 */
async function updateStory(id: number, story: NewStory): Promise<void> {
	saving.value.story = true
	try {
		const updated = await request(
			() => api.updateStory(id, story.name, story.buildingId),
			t('calendar_resource_management', 'Could not update floor'),
		)
		if (updated !== null) {
			storiesSection.value?.close()
			replaceSorted(stories, updated)
		}
	} finally {
		saving.value.story = false
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
 * Save the changes to a room edited in the rooms section.
 *
 * @param id The room to update
 * @param room The entered room
 */
async function updateRoom(id: number, room: NewRoom): Promise<void> {
	saving.value.room = true
	try {
		const updated = await request(
			() => api.updateRoom(id, room),
			t('calendar_resource_management', 'Could not update room'),
		)
		if (updated !== null) {
			roomsSection.value?.close()
			replaceSorted(rooms, updated)
		}
	} finally {
		saving.value.room = false
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
				// Nothing references a room, so drop it locally instead of reloading
				rooms.value = rooms.value.filter((room) => room.id !== id)
			}
		},
	}
}

/**
 * Save the changes to a resource edited in the resources section.
 *
 * @param id The resource to update
 * @param resource The entered resource
 */
async function updateResource(id: number, resource: NewResource): Promise<void> {
	saving.value.resource = true
	try {
		const updated = await request(
			() => api.updateResource(id, resource),
			t('calendar_resource_management', 'Could not update resource'),
		)
		if (updated !== null) {
			resourcesSection.value?.close()
			replaceSorted(resources, updated)
		}
	} finally {
		saving.value.resource = false
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
				// Nothing references a resource, so drop it locally instead of reloading
				resources.value = resources.value.filter((resource) => resource.id !== id)
			}
		},
	}
}

/**
 * Move between tabs with the arrow, Home and End keys.
 *
 * @param event The keyboard event on the tab list
 */
function onTabKeydown(event: KeyboardEvent): void {
	const current = tabs.findIndex((tab) => tab.id === activeTab.value)
	let next: number
	switch (event.key) {
		case 'ArrowRight':
			next = (current + 1) % tabs.length
			break
		case 'ArrowLeft':
			next = (current - 1 + tabs.length) % tabs.length
			break
		case 'Home':
			next = 0
			break
		case 'End':
			next = tabs.length - 1
			break
		default:
			return
	}
	event.preventDefault()
	activeTab.value = tabs[next].id
	tabButtons.value?.[next]?.focus()
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
		<div
			class="crm-tabs"
			role="tablist"
			:aria-label="t('calendar_resource_management', 'Calendar resources')"
			@keydown="onTabKeydown">
			<button
				v-for="tab in tabs"
				:id="`crm-tab-${tab.id}`"
				ref="tabButtons"
				:key="tab.id"
				:aria-controls="`crm-panel-${tab.id}`"
				:aria-selected="activeTab === tab.id"
				class="crm-tabs__tab"
				:class="{ 'crm-tabs__tab--active': activeTab === tab.id }"
				role="tab"
				:tabindex="activeTab === tab.id ? 0 : -1"
				type="button"
				@click="activeTab = tab.id">
				{{ tab.label }}
			</button>
		</div>

		<div
			v-show="activeTab === 'buildings'"
			id="crm-panel-buildings"
			aria-labelledby="crm-tab-buildings"
			role="tabpanel">
			<BuildingsSection
				ref="buildings"
				:buildings="buildings"
				:loading="saving.building"
				@create="createBuilding"
				@update="updateBuilding"
				@delete="confirmDeleteBuilding" />
		</div>

		<div
			v-show="activeTab === 'stories'"
			id="crm-panel-stories"
			aria-labelledby="crm-tab-stories"
			role="tabpanel">
			<StoriesSection
				ref="stories"
				:buildings="buildings"
				:loading="saving.story"
				:stories="stories"
				@create="createStory"
				@update="updateStory"
				@delete="confirmDeleteStory" />
		</div>

		<div
			v-show="activeTab === 'rooms'"
			id="crm-panel-rooms"
			aria-labelledby="crm-tab-rooms"
			role="tabpanel">
			<RoomsSection
				ref="rooms"
				:buildings="buildings"
				:loading="saving.room"
				:rooms="rooms"
				:stories="stories"
				@create="createRoom"
				@update="updateRoom"
				@delete="confirmDeleteRoom" />
		</div>

		<div
			v-show="activeTab === 'resources'"
			id="crm-panel-resources"
			aria-labelledby="crm-tab-resources"
			role="tabpanel">
			<ResourcesSection
				ref="resources"
				:buildings="buildings"
				:loading="saving.resource"
				:resources="resources"
				@create="createResource"
				@update="updateResource"
				@delete="confirmDeleteResource" />
		</div>

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
	// Every table of all sections shares this width
	--crm-content-max-width: 700px;

	.crm-tabs {
		border-block-end: 1px solid var(--color-border);
		display: flex;
		flex-wrap: wrap;
		gap: var(--default-grid-baseline);
		// Lines the tabs up with the settings sections below
		margin-inline: calc(7 * var(--default-grid-baseline));
		margin-block-start: calc(4 * var(--default-grid-baseline));

		&__tab {
			background: none;
			border: none;
			border-block-end: 2px solid transparent;
			border-radius: var(--border-radius-element) var(--border-radius-element) 0 0;
			color: var(--color-text-maxcontrast);
			cursor: pointer;
			margin: 0;
			// Overlaps the list border so the active underline replaces it
			margin-block-end: -1px;
			min-height: var(--default-clickable-area);
			padding: 0 calc(4 * var(--default-grid-baseline));

			&:hover {
				background-color: var(--color-background-hover);
				color: var(--color-main-text);
			}

			&:focus-visible {
				outline: 2px solid var(--color-main-text);
				outline-offset: -2px;
			}

			&--active {
				border-block-end-color: var(--color-primary-element);
				color: var(--color-main-text);
				font-weight: bold;
			}
		}
	}

	:deep(.crm-add) {
		margin-block-end: calc(4 * var(--default-grid-baseline));
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

		.crm-table__actions {
			display: flex;
			gap: var(--default-grid-baseline);
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
