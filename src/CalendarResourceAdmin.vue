<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="crm-admin">
		<BuildingsSection
			ref="buildings"
			:buildings="buildings"
			@create="createBuilding"
			@delete="confirmDeleteBuilding" />

		<StoriesSection
			ref="stories"
			:buildings="buildings"
			:stories="stories"
			@create="createStory"
			@delete="confirmDeleteStory" />

		<RoomsSection
			ref="rooms"
			:buildings="buildings"
			:rooms="rooms"
			:stories="stories"
			@create="createRoom"
			@delete="confirmDeleteRoom" />

		<ResourcesSection
			ref="resources"
			:buildings="buildings"
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
				const data = await call()
				if (data?.success === false) {
					showError(data.error ? `${errorMessage}: ${data.error}` : errorMessage)
					return null
				}

				return data
			} catch (error) {
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
				t('calendar_resource_management', 'Could not load stories'),
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
			const created = await this.request(
				() => api.createBuilding(building.name, building.address),
				t('calendar_resource_management', 'Could not create building'),
			)
			if (created !== null) {
				this.$refs.buildings.reset()
				await this.loadBuildings()
			}
		},

		async createStory(story) {
			const created = await this.request(
				() => api.createStory(story.name, story.buildingId),
				t('calendar_resource_management', 'Could not create story'),
			)
			if (created !== null) {
				this.$refs.stories.reset()
				await this.loadStories()
			}
		},

		async createRoom(room) {
			const created = await this.request(
				() => api.createRoom(room),
				t('calendar_resource_management', 'Could not create room'),
			)
			if (created !== null) {
				this.$refs.rooms.reset()
				await this.loadRooms()
			}
		},

		async createResource(resource) {
			const created = await this.request(
				() => api.createResource(resource),
				t('calendar_resource_management', 'Could not create resource'),
			)
			if (created !== null) {
				this.$refs.resources.reset()
				await this.loadResources()
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
				message: t('calendar_resource_management', 'Do you really want to delete this story?'),
				action: async () => {
					const deleted = await this.request(
						() => api.deleteStory(id),
						t('calendar_resource_management', 'Could not delete story'),
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
	// Form fields and tables of all sections
	:deep(.crm-field) {
		margin-block-end: calc(2 * var(--default-grid-baseline));
		max-width: 500px;
	}

	:deep(.crm-equipment) {
		margin-block: calc(2 * var(--default-grid-baseline));
		max-width: 500px;

		legend {
			font-weight: bold;
		}
	}

	:deep(.crm-table) {
		border-collapse: collapse;
		margin-block-start: calc(3 * var(--default-grid-baseline));
		max-width: 100%;
		width: 100%;

		th,
		td {
			border-block-end: 1px solid var(--color-border);
			padding: calc(2 * var(--default-grid-baseline));
			text-align: start;
		}

		th {
			color: var(--color-text-maxcontrast);
			font-weight: normal;
		}

		tbody tr:hover {
			background-color: var(--color-background-hover);
		}
	}

	:deep(.crm-table-wrapper) {
		overflow-x: auto;
	}
}
</style>
