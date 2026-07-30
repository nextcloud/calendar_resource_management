<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

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
			:message="pendingDelete ? pendingDelete.message : ''"
			:name="t('calendar_resource_management', 'Confirm deletion')"
			:open="pendingDelete !== null"
			@update:open="onDeleteDialogToggle" />
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { getLoggerBuilder } from '@nextcloud/logger'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import BuildingsSection from './components/BuildingsSection.vue'
import ResourcesSection from './components/ResourcesSection.vue'
import RoomsSection from './components/RoomsSection.vue'
import StoriesSection from './components/StoriesSection.vue'
import * as api from './services/adminService.js'

const logger = getLoggerBuilder()
	.setApp('calendar_resource_management')
	.detectUser()
	.build()

export default {
	name: 'CalendarResourceAdmin',

	components: {
		BuildingsSection,
		NcDialog,
		ResourcesSection,
		RoomsSection,
		StoriesSection,
	},

	data() {
		return {
			buildings: [],
			stories: [],
			rooms: [],
			resources: [],
			// Blocks the submit buttons while a create request is running
			creating: {
				building: false,
				story: false,
				room: false,
				resource: false,
			},

			pendingDelete: null,
		}
	},

	computed: {
		deleteDialogButtons() {
			return [
				{
					label: t('calendar_resource_management', 'Cancel'),
					callback: () => {
						this.pendingDelete = null
					},
				},
				{
					label: t('calendar_resource_management', 'Delete'),
					variant: 'error',
					callback: () => {
						const pending = this.pendingDelete
						this.pendingDelete = null
						pending?.action()
					},
				},
			]
		},
	},

	async mounted() {
		await Promise.all([
			this.loadBuildings(),
			this.loadStories(),
			this.loadRooms(),
			this.loadResources(),
		])
	},

	methods: {
		t,

		/**
		 * Run an API call and report failures to the user instead of throwing.
		 *
		 * @param {() => Promise<object|Array>} call The API call to run
		 * @param {string} errorMessage Message shown to the user if the call fails
		 * @return {Promise<object|Array|null>} The response data, or null if the call failed
		 */
		async request(call, errorMessage) {
			try {
				return await call()
			} catch (error) {
				// The endpoints answer with an untranslated message, so only log it
				logger.error(errorMessage, { error })
				showError(errorMessage)
				return null
			}
		},

		async loadBuildings() {
			const buildings = await this.request(
				api.fetchBuildings,
				t('calendar_resource_management', 'Could not load buildings'),
			)
			if (buildings !== null) {
				this.buildings = buildings
			}
		},

		async loadStories() {
			const stories = await this.request(
				api.fetchStories,
				t('calendar_resource_management', 'Could not load floors'),
			)
			if (stories !== null) {
				this.stories = stories
			}
		},

		async loadRooms() {
			const rooms = await this.request(
				api.fetchRooms,
				t('calendar_resource_management', 'Could not load rooms'),
			)
			if (rooms !== null) {
				this.rooms = rooms
			}
		},

		async loadResources() {
			const resources = await this.request(
				api.fetchResources,
				t('calendar_resource_management', 'Could not load resources'),
			)
			if (resources !== null) {
				this.resources = resources
			}
		},

		async createBuilding(building) {
			this.creating.building = true
			try {
				const created = await this.request(
					() => api.createBuilding(building.name, building.address),
					t('calendar_resource_management', 'Could not create building'),
				)
				if (created !== null) {
					this.$refs.buildings.reset()
					await this.loadBuildings()
				}
			} finally {
				this.creating.building = false
			}
		},

		async createStory(story) {
			this.creating.story = true
			try {
				const created = await this.request(
					() => api.createStory(story.name, story.buildingId),
					t('calendar_resource_management', 'Could not create floor'),
				)
				if (created !== null) {
					this.$refs.stories.reset()
					await this.loadStories()
				}
			} finally {
				this.creating.story = false
			}
		},

		async createRoom(room) {
			this.creating.room = true
			try {
				const created = await this.request(
					() => api.createRoom(room),
					t('calendar_resource_management', 'Could not create room'),
				)
				if (created !== null) {
					this.$refs.rooms.reset()
					await this.loadRooms()
				}
			} finally {
				this.creating.room = false
			}
		},

		async createResource(resource) {
			this.creating.resource = true
			try {
				const created = await this.request(
					() => api.createResource(resource),
					t('calendar_resource_management', 'Could not create resource'),
				)
				if (created !== null) {
					this.$refs.resources.reset()
					await this.loadResources()
				}
			} finally {
				this.creating.resource = false
			}
		},

		confirmDeleteBuilding(id) {
			this.pendingDelete = {
				message: t('calendar_resource_management', 'Do you really want to delete this building?'),
				action: async () => {
					const deleted = await this.request(
						() => api.deleteBuilding(id),
						t('calendar_resource_management', 'Could not delete building'),
					)
					if (deleted !== null) {
						// Stories, rooms and resources reference the building
						await Promise.all([
							this.loadBuildings(),
							this.loadStories(),
							this.loadRooms(),
							this.loadResources(),
						])
					}
				},
			}
		},

		confirmDeleteStory(id) {
			this.pendingDelete = {
				message: t('calendar_resource_management', 'Do you really want to delete this floor?'),
				action: async () => {
					const deleted = await this.request(
						() => api.deleteStory(id),
						t('calendar_resource_management', 'Could not delete floor'),
					)
					if (deleted !== null) {
						// Rooms reference the story
						await Promise.all([
							this.loadStories(),
							this.loadRooms(),
						])
					}
				},
			}
		},

		confirmDeleteRoom(id) {
			this.pendingDelete = {
				message: t('calendar_resource_management', 'Do you really want to delete this room?'),
				action: async () => {
					const deleted = await this.request(
						() => api.deleteRoom(id),
						t('calendar_resource_management', 'Could not delete room'),
					)
					if (deleted !== null) {
						await this.loadRooms()
					}
				},
			}
		},

		confirmDeleteResource(id) {
			this.pendingDelete = {
				message: t('calendar_resource_management', 'Do you really want to delete this resource?'),
				action: async () => {
					const deleted = await this.request(
						() => api.deleteResource(id),
						t('calendar_resource_management', 'Could not delete resource'),
					)
					if (deleted !== null) {
						await this.loadResources()
					}
				},
			}
		},

		onDeleteDialogToggle(open) {
			if (!open) {
				this.pendingDelete = null
			}
		},
	},
}
</script>

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
