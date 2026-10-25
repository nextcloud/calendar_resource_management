<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Ref } from 'vue'
import type { Building, NamedEntity, NewBuilding, NewResource, NewRoom, NewStory, NewVehicle, Resource, Room, Story, UpdateBuilding, UpdateResource, UpdateRoom, UpdateStory, UpdateVehicle, Vehicle } from './types/types.ts'

import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { getLoggerBuilder } from '@nextcloud/logger'
import { computed, onMounted, ref, useTemplateRef } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import BookingCalendarsSection from './components/BookingCalendarsSection.vue'
import BuildingEditDialog from './components/BuildingEditDialog.vue'
import BuildingsSection from './components/BuildingsSection.vue'
import ResourceEditDialog from './components/ResourceEditDialog.vue'
import ResourcesSection from './components/ResourcesSection.vue'
import RoomEditDialog from './components/RoomEditDialog.vue'
import RoomsSection from './components/RoomsSection.vue'
import StoriesSection from './components/StoriesSection.vue'
import StoryEditDialog from './components/StoryEditDialog.vue'
import VehicleEditDialog from './components/VehicleEditDialog.vue'
import VehiclesSection from './components/VehiclesSection.vue'
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
const vehicles = ref<Vehicle[]>([])

// Blocks the submit buttons while a create request is running
const creating = ref({
	building: false,
	story: false,
	room: false,
	resource: false,
	vehicle: false,
})

const pendingDelete = ref<PendingDelete | null>(null)

// The entity shown in the edit dialog and whether its update request is running
const editingBuilding = ref<Building | null>(null)
const editingStory = ref<Story | null>(null)
const editingRoom = ref<Room | null>(null)
const editingResource = ref<Resource | null>(null)
const editingVehicle = ref<Vehicle | null>(null)
const saving = ref(false)

const buildingsSection = useTemplateRef<InstanceType<typeof BuildingsSection>>('buildings')
const storiesSection = useTemplateRef<InstanceType<typeof StoriesSection>>('stories')
const roomsSection = useTemplateRef<InstanceType<typeof RoomsSection>>('rooms')
const resourcesSection = useTemplateRef<InstanceType<typeof ResourcesSection>>('resources')
const vehiclesSection = useTemplateRef<InstanceType<typeof VehiclesSection>>('vehicles')

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

/**
 * Reload the vehicles.
 */
async function loadVehicles(): Promise<void> {
	const loaded = await request(
		api.fetchVehicles,
		t('calendar_resource_management', 'Could not load vehicles'),
	)
	if (loaded !== null) {
		vehicles.value = loaded
	}
}

onMounted(async () => {
	await Promise.all([
		loadBuildings(),
		loadStories(),
		loadRooms(),
		loadResources(),
		loadVehicles(),
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
			addSorted(buildings, created)
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
			addSorted(stories, created)
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
			addSorted(rooms, created)
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
			addSorted(resources, created)
		}
	} finally {
		creating.value.resource = false
	}
}

/**
 * Save an entity changed in an edit dialog, replace it in its list and close
 * the dialog. The dialog stays open if saving fails.
 *
 * @param list The list the entity is shown in
 * @param editing The entity of the open edit dialog
 * @param call The update request
 * @param errorMessage Message shown to the user if the request fails
 * @return Whether the entity was saved
 */
async function saveEdit<T extends NamedEntity>(list: Ref<T[]>, editing: Ref<T | null>, call: () => Promise<T>, errorMessage: string): Promise<boolean> {
	saving.value = true
	try {
		const updated = await request(call, errorMessage)
		if (updated === null) {
			return false
		}
		list.value = list.value
			.map((candidate) => candidate.id === updated.id ? updated : candidate)
			.sort((a, b) => a.name.localeCompare(b.name))
		editing.value = null
		return true
	} finally {
		saving.value = false
	}
}

/**
 * Save the building changed in the edit dialog.
 *
 * @param id The building
 * @param building The changed building
 */
async function updateBuilding(id: number, building: UpdateBuilding): Promise<void> {
	await saveEdit(buildings, editingBuilding, () => api.updateBuilding(id, building), t('calendar_resource_management', 'Could not update building'))
}

/**
 * Save the floor changed in the edit dialog.
 *
 * @param id The floor
 * @param story The changed floor
 */
async function updateStory(id: number, story: UpdateStory): Promise<void> {
	await saveEdit(stories, editingStory, () => api.updateStory(id, story), t('calendar_resource_management', 'Could not update floor'))
}

/**
 * Save the room changed in the edit dialog.
 *
 * @param id The room
 * @param room The changed room
 */
async function updateRoom(id: number, room: UpdateRoom): Promise<void> {
	await saveEdit(rooms, editingRoom, () => api.updateRoom(id, room), t('calendar_resource_management', 'Could not update room'))
}

/**
 * Save the resource changed in the edit dialog.
 *
 * @param id The resource
 * @param resource The changed resource
 */
async function updateResource(id: number, resource: UpdateResource): Promise<void> {
	await saveEdit(resources, editingResource, () => api.updateResource(id, resource), t('calendar_resource_management', 'Could not update resource'))
}

/**
 * Save the vehicle changed in the edit dialog.
 *
 * @param id The vehicle
 * @param vehicle The changed vehicle
 */
async function updateVehicle(id: number, vehicle: UpdateVehicle): Promise<void> {
	await saveEdit(vehicles, editingVehicle, () => api.updateVehicle(id, vehicle), t('calendar_resource_management', 'Could not update vehicle'))
}

/**
 * Create the vehicle entered in the vehicles section.
 *
 * @param vehicle The vehicle to create
 */
async function createVehicle(vehicle: NewVehicle): Promise<void> {
	creating.value.vehicle = true
	try {
		const created = await request(
			() => api.createVehicle(vehicle),
			t('calendar_resource_management', 'Could not create vehicle'),
		)
		if (created !== null) {
			vehiclesSection.value?.reset()
			addSorted(vehicles, created)
		}
	} finally {
		creating.value.vehicle = false
	}
}

/**
 * Ask for confirmation before deleting a vehicle.
 *
 * @param id The vehicle to delete
 */
function confirmDeleteVehicle(id: number): void {
	pendingDelete.value = {
		message: t('calendar_resource_management', 'Do you really want to delete this vehicle? Its bookings are deleted as well.'),
		action: async () => {
			const deleted = await request(
				() => api.deleteVehicle(id),
				t('calendar_resource_management', 'Could not delete vehicle'),
			)
			if (deleted !== null) {
				vehicles.value = vehicles.value.filter((vehicle) => vehicle.id !== id)
			}
		},
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
				// Stories, rooms, resources and vehicles reference the building
				await Promise.all([
					loadBuildings(),
					loadStories(),
					loadRooms(),
					loadResources(),
					loadVehicles(),
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
				// Nothing references a room, so drop it locally instead of reloading
				rooms.value = rooms.value.filter((room) => room.id !== id)
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
				// Nothing references a resource, so drop it locally instead of reloading
				resources.value = resources.value.filter((resource) => resource.id !== id)
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
			@delete="confirmDeleteBuilding"
			@edit="editingBuilding = $event" />

		<StoriesSection
			ref="stories"
			:buildings="buildings"
			:loading="creating.story"
			:stories="stories"
			@create="createStory"
			@delete="confirmDeleteStory"
			@edit="editingStory = $event" />

		<RoomsSection
			ref="rooms"
			:buildings="buildings"
			:loading="creating.room"
			:rooms="rooms"
			:stories="stories"
			@create="createRoom"
			@delete="confirmDeleteRoom"
			@edit="editingRoom = $event" />

		<ResourcesSection
			ref="resources"
			:buildings="buildings"
			:loading="creating.resource"
			:resources="resources"
			@create="createResource"
			@delete="confirmDeleteResource"
			@edit="editingResource = $event" />

		<VehiclesSection
			ref="vehicles"
			:buildings="buildings"
			:loading="creating.vehicle"
			:vehicles="vehicles"
			@create="createVehicle"
			@delete="confirmDeleteVehicle"
			@edit="editingVehicle = $event" />

		<BookingCalendarsSection
			:resources="resources"
			:rooms="rooms"
			:vehicles="vehicles" />

		<BuildingEditDialog
			:building="editingBuilding"
			:loading="saving"
			@close="editingBuilding = null"
			@save="updateBuilding" />

		<StoryEditDialog
			:buildings="buildings"
			:loading="saving"
			:story="editingStory"
			@close="editingStory = null"
			@save="updateStory" />

		<RoomEditDialog
			:buildings="buildings"
			:loading="saving"
			:room="editingRoom"
			:stories="stories"
			@close="editingRoom = null"
			@save="updateRoom" />

		<ResourceEditDialog
			:buildings="buildings"
			:loading="saving"
			:resource="editingResource"
			@close="editingResource = null"
			@save="updateResource" />

		<VehicleEditDialog
			:buildings="buildings"
			:loading="saving"
			:vehicle="editingVehicle"
			@close="editingVehicle = null"
			@save="updateVehicle" />

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

	// Edit and delete side by side
	:deep(.crm-actions) {
		white-space: nowrap;
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
