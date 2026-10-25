<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, GroupOption, Room, SelectOption, Story, UpdateRoom, User } from '../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EditDialog from './EditDialog.vue'
import RestrictionFields from './RestrictionFields.vue'
import { fetchUsers } from '../services/adminService.ts'
import { optionId, selectOptions } from '../utils/entities.ts'
import { EQUIPMENT_TYPES, equipmentOf, noEquipment } from '../utils/equipment.ts'

const props = defineProps<{
	/** The room to edit, the dialog is closed without one */
	room: Room | null
	buildings: Building[]
	stories: Story[]
	loading?: boolean
}>()

const emit = defineEmits<{
	save: [id: number, room: UpdateRoom]
	close: []
}>()

const name = ref('')
const email = ref('')
const roomType = ref('')
const roomNumber = ref('')
// NcSelectUsers clears to undefined, it does not accept null
const contactPerson = ref<User | undefined>()
const users = ref<User[]>([])
const loadingUsers = ref(false)
const capacity = ref('')
const building = ref<SelectOption | null>(null)
const story = ref<SelectOption | null>(null)
const equipment = ref(noEquipment())
const restricted = ref(false)
const restrictionGroups = ref<GroupOption[]>([])

const buildingOptions = computed(() => selectOptions(props.buildings))

const storyOptions = computed(() => {
	const buildingId = optionId(building.value)
	if (buildingId === null) {
		return []
	}

	return selectOptions(props.stories.filter((candidate) => candidate.buildingId === buildingId))
})

// Restricted without groups, the server has no booking calendar for it
const hidden = computed(() => props.room !== null && props.room.restricted && props.room.restrictionGroups.length === 0)

const canSubmit = computed(() => name.value.trim() !== '' && email.value.trim() !== '' && optionId(story.value) !== null
	// Hiding deletes the booking calendar, only the occ command may do that
	&& (!restricted.value || restrictionGroups.value.length > 0 || hidden.value))

/**
 * Fill the form with the room to edit.
 *
 * @param room The room
 */
function fill(room: Room): void {
	const roomStory = props.stories.find((candidate) => candidate.id === room.storyId)
	const roomBuilding = props.buildings.find((candidate) => candidate.id === roomStory?.buildingId)

	name.value = room.name
	email.value = room.email
	roomType.value = room.roomType
	roomNumber.value = room.roomNumber
	contactPerson.value = room.contactPersonUserId
		? { id: room.contactPersonUserId, displayName: room.contactPersonUserId }
		: undefined
	capacity.value = room.capacity ? String(room.capacity) : ''
	building.value = roomBuilding ? { id: roomBuilding.id, label: roomBuilding.name } : null
	story.value = roomStory ? { id: roomStory.id, label: roomStory.name } : null
	equipment.value = equipmentOf(room)
	restricted.value = room.restricted
	restrictionGroups.value = room.restrictionGroups.map((group) => ({ id: group.id, label: group.displayName }))
}

watch(() => props.room, (room) => {
	if (room) {
		fill(room)
	}
}, { immediate: true })

watch(building, () => {
	// A floor of the previously selected building
	if (!storyOptions.value.some((option) => option.id === optionId(story.value))) {
		story.value = null
	}
})

/**
 * Load the accounts to pick the contact person from. The endpoint limits the
 * result, so filtering happens server side instead of in the dropdown.
 *
 * @param search Filter for the display name
 */
async function searchUsers(search = ''): Promise<void> {
	loadingUsers.value = true
	try {
		users.value = await fetchUsers(search)
	} catch {
		// Without accounts the dropdown stays empty, there is nothing to pick
		users.value = []
	} finally {
		loadingUsers.value = false
	}
}

/**
 * Hand the changed room over to the parent.
 */
function submit(): void {
	if (!props.room) {
		return
	}

	emit('save', props.room.id, {
		name: name.value.trim(),
		email: email.value.trim(),
		roomType: roomType.value.trim(),
		roomNumber: roomNumber.value.trim(),
		contactPersonUserId: optionId(contactPerson.value) ?? '',
		capacity: capacity.value === '' ? null : Number.parseInt(capacity.value, 10),
		storyId: optionId(story.value),
		...equipment.value,
		restricted: restricted.value,
		restrictionGroupIds: restrictionGroups.value.map((group) => group.id),
	})
}

</script>

<template>
	<EditDialog
		:canSave="canSubmit"
		:loading="loading"
		:name="t('calendar_resource_management', 'Edit room {name}', { name: room?.name ?? '' })"
		:open="room !== null"
		@close="emit('close')"
		@save="submit">
		<NcTextField
			v-model="name"
			class="crm-field"
			:label="t('calendar_resource_management', 'Room name')"
			:placeholder="t('calendar_resource_management', 'Required')" />

		<NcTextField
			v-model="email"
			class="crm-field"
			:label="t('calendar_resource_management', 'Email')"
			:placeholder="t('calendar_resource_management', 'Required')"
			type="email" />

		<NcTextField
			v-model="roomType"
			class="crm-field"
			:label="t('calendar_resource_management', 'Room type')"
			:placeholder="t('calendar_resource_management', 'e.g. meeting-room')" />

		<NcTextField
			v-model="roomNumber"
			class="crm-field"
			:label="t('calendar_resource_management', 'Room number')"
			:placeholder="t('calendar_resource_management', 'e.g. 1.23')" />

		<NcSelectUsers
			v-model="contactPerson"
			class="crm-field"
			:inputLabel="t('calendar_resource_management', 'Contact person')"
			:loading="loadingUsers"
			:options="users"
			:placeholder="t('calendar_resource_management', 'Search for an account')"
			@search="searchUsers" />

		<NcTextField
			v-model="capacity"
			class="crm-field"
			:label="t('calendar_resource_management', 'Capacity')"
			min="0"
			:placeholder="t('calendar_resource_management', 'e.g. 10')"
			type="number" />

		<NcSelect
			v-model="building"
			class="crm-field"
			:clearable="false"
			:inputLabel="t('calendar_resource_management', 'Building')"
			:options="buildingOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building')" />

		<NcSelect
			v-model="story"
			class="crm-field"
			:disabled="storyOptions.length === 0"
			:inputLabel="t('calendar_resource_management', 'Floor')"
			:options="storyOptions"
			:placeholder="t('calendar_resource_management', 'Please select a floor')" />

		<fieldset class="crm-equipment">
			<legend>{{ t('calendar_resource_management', 'Equipment') }}</legend>
			<NcCheckboxRadioSwitch
				v-for="item in EQUIPMENT_TYPES"
				:key="item.payloadKey"
				v-model="equipment[item.payloadKey]"
				type="switch">
				{{ item.label() }}
			</NcCheckboxRadioSwitch>
		</fieldset>

		<RestrictionFields
			v-model:groups="restrictionGroups"
			v-model:restricted="restricted"
			:hidden="hidden" />
	</EditDialog>
</template>
