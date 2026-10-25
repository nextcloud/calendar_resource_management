<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { BookableEntity, BookableType, DefaultGroup, Group, GroupOption, Resource, Room } from '../types/types.ts'

import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { onMounted, ref, watch } from 'vue'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { fetchBookingCalendarSettings, fetchGroups, saveDefaultGroups, saveViewerGroups } from '../services/adminService.ts'

const props = defineProps<{
	rooms: Room[]
	resources: Resource[]
}>()

const viewerGroups = ref<GroupOption[]>([])
const entities = ref<BookableEntity[]>([])
const defaultGroups = ref<DefaultGroup[]>([])
const groupOptions = ref<GroupOption[]>([])
const loadingGroups = ref(false)
const savingViewerGroups = ref(false)
// The shares follow a viewer group change in the background
const viewerGroupsChanged = ref(false)

const typeLabels: Record<BookableType, string> = {
	room: t('calendar_resource_management', 'Room'),
	resource: t('calendar_resource_management', 'Resource'),
	vehicle: t('calendar_resource_management', 'Vehicle'),
}

/**
 * Map groups to NcSelect options.
 *
 * @param groups Groups as the endpoints return them
 * @return NcSelect options
 */
function groupSelectOptions(groups: Group[]): GroupOption[] {
	return groups.map((group) => ({ id: group.id, label: group.displayName }))
}

/**
 * Load the groups to pick from. The endpoint limits the result, so filtering
 * happens server side instead of in the dropdown.
 *
 * @param search Filter for the group name
 */
async function searchGroups(search = ''): Promise<void> {
	loadingGroups.value = true
	try {
		groupOptions.value = groupSelectOptions(await fetchGroups(search))
	} catch {
		// Without groups the dropdown stays empty, there is nothing to pick
		groupOptions.value = []
	} finally {
		loadingGroups.value = false
	}
}

/**
 * Load the viewer groups, the bookable entities and their default groups.
 */
async function loadSettings(): Promise<void> {
	try {
		const settings = await fetchBookingCalendarSettings()
		viewerGroups.value = groupSelectOptions(settings.viewerGroups)
		entities.value = settings.entities
		defaultGroups.value = settings.defaultGroups
	} catch {
		showError(t('calendar_resource_management', 'Could not load the booking calendar settings'))
	}
}

onMounted(async () => {
	await Promise.all([searchGroups(), loadSettings()])
})

// Rooms and resources created or deleted in the other sections
watch(() => [props.rooms, props.resources], loadSettings)

/**
 * Save the viewer groups. They decide which default groups are possible,
 * so the entities are reloaded afterwards.
 *
 * @param selected The selected groups
 */
async function onViewerGroupsChange(selected: GroupOption[]): Promise<void> {
	const previous = viewerGroups.value
	viewerGroups.value = selected
	savingViewerGroups.value = true
	try {
		viewerGroups.value = groupSelectOptions(await saveViewerGroups(selected.map((group) => group.id)))
		viewerGroupsChanged.value = true
		await loadSettings()
	} catch {
		viewerGroups.value = previous
		showError(t('calendar_resource_management', 'Could not save the viewer groups'))
	} finally {
		savingViewerGroups.value = false
	}
}

/**
 * The viewer groups that can be default groups of an entity.
 *
 * @param entity The room, resource or vehicle
 * @return The options
 */
function allowedOptionsOf(entity: BookableEntity): GroupOption[] {
	return viewerGroups.value.filter((option) => entity.allowedGroupIds.includes(option.id))
}

/**
 * The default groups of an entity as NcSelect values. Saved defaults the
 * server ignores, e.g. of restricted entities, are left out.
 *
 * @param entity The room, resource or vehicle
 * @return The selected options
 */
function defaultGroupsOf(entity: BookableEntity): GroupOption[] {
	const allowed = allowedOptionsOf(entity)
	return defaultGroups.value
		.filter((group) => group.entityType === entity.type && group.entityId === entity.id)
		.map((group) => allowed.find((option) => option.id === group.groupId))
		.filter((option): option is GroupOption => option !== undefined)
}

/**
 * Save the default groups of an entity.
 *
 * @param entity The room, resource or vehicle
 * @param selected The selected groups
 */
async function onDefaultGroupsChange(entity: BookableEntity, selected: GroupOption[]): Promise<void> {
	const previous = defaultGroups.value
	const others = previous.filter((group) => group.entityType !== entity.type || group.entityId !== entity.id)
	defaultGroups.value = [
		...others,
		...selected.map((group) => ({ entityType: entity.type, entityId: entity.id, groupId: group.id })),
	]
	try {
		await saveDefaultGroups(entity.type, entity.id, selected.map((group) => group.id))
	} catch {
		defaultGroups.value = previous
		showError(t('calendar_resource_management', 'Could not save the default groups of {name}', { name: entity.name }))
	}
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Members of the viewer groups can add the booking calendars of rooms, resources and vehicles to their calendars in their personal settings. The calendars are read-only and also show up in other CalDAV apps. Private events are hidden, confidential events only show as busy.')"
		:name="t('calendar_resource_management', 'Booking calendars')">
		<NcSelect
			class="crm-field"
			:disabled="savingViewerGroups"
			:filterable="false"
			:inputLabel="t('calendar_resource_management', 'Viewer groups')"
			:loading="loadingGroups"
			:modelValue="viewerGroups"
			multiple
			:options="groupOptions"
			:placeholder="t('calendar_resource_management', 'Nobody sees booking calendars')"
			@search="searchGroups"
			@update:modelValue="onViewerGroupsChange" />

		<NcNoteCard v-if="viewerGroupsChanged" class="crm-field" type="info">
			{{ t('calendar_resource_management', 'The calendars of users who are no longer allowed to see them are removed by a background job within the next minutes.') }}
		</NcNoteCard>

		<p v-if="viewerGroups.length && entities.length" class="crm-hint">
			{{ t('calendar_resource_management', 'Members of default groups get the booking calendar without picking it. They can still remove it. Restricted rooms, resources and vehicles can only be defaults of viewer groups they are restricted to.') }}
		</p>

		<div v-if="viewerGroups.length && entities.length" class="crm-table-wrapper">
			<table class="crm-table">
				<thead>
					<tr>
						<th>{{ t('calendar_resource_management', 'Name') }}</th>
						<th>{{ t('calendar_resource_management', 'Type') }}</th>
						<th>{{ t('calendar_resource_management', 'Default for') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="entity in entities" :key="`${entity.type}-${entity.id}`">
						<td>{{ entity.name }}</td>
						<td>{{ typeLabels[entity.type] }}</td>
						<td>
							<NcSelect
								:aria-label-combobox="t('calendar_resource_management', 'Default groups of {name}', { name: entity.name })"
								:disabled="entity.allowedGroupIds.length === 0"
								:modelValue="defaultGroupsOf(entity)"
								multiple
								:options="allowedOptionsOf(entity)"
								:placeholder="entity.allowedGroupIds.length === 0
									? t('calendar_resource_management', 'No viewer group may see it')
									: t('calendar_resource_management', 'No default groups')"
								@update:modelValue="onDefaultGroupsChange(entity, $event)" />
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
.crm-hint {
	color: var(--color-text-maxcontrast);
	margin-block-end: calc(2 * var(--default-grid-baseline));
	max-width: var(--crm-content-max-width);
}
</style>
