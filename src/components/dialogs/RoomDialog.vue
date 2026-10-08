<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewRoom, Room, SelectOption, Story, User } from '../../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EntityDialog from './EntityDialog.vue'
import { fetchUsers } from '../../services/adminService.ts'
import { optionId, selectOptions } from '../../utils/entities.ts'
import { EQUIPMENT_TYPES, roomEquipment } from '../../utils/equipment.ts'

const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
	/** The room to edit, or null to create a new one */
	room: Room | null
	buildings: Building[]
	stories: Story[]
	loading?: boolean
}>()

const emit = defineEmits<{
	submit: [room: NewRoom]
}>()

const name = ref('')
const email = ref('')
const roomType = ref('default')
const roomNumber = ref('')
// NcSelectUsers clears to undefined, it does not accept null
const contactPerson = ref<User | undefined>()
const users = ref<User[]>([])
const loadingUsers = ref(false)
const capacity = ref('')
const building = ref<SelectOption | null>(null)
const story = ref<SelectOption | null>(null)
const equipment = ref(roomEquipment(null))

const buildingOptions = computed(() => selectOptions(props.buildings))

const storyOptions = computed(() => {
	const buildingId = optionId(building.value)
	if (buildingId === null) {
		return []
	}

	return selectOptions(props.stories.filter((candidate) => candidate.buildingId === buildingId))
})

const canSubmit = computed(() => name.value.trim() !== '' && email.value.trim() !== '' && optionId(story.value) !== null)

/**
 * Select a building. The floor belongs to the previously selected building,
 * so it is cleared.
 *
 * @param value The selected building
 */
function selectBuilding(value: SelectOption | null): void {
	building.value = value
	story.value = null
}

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
 * Fill the form with the room to edit, or with defaults for a new room.
 */
async function fillForm(): Promise<void> {
	const room = props.room
	const roomStory = props.stories.find((candidate) => candidate.id === room?.storyId)

	name.value = room?.name ?? ''
	email.value = room?.email ?? ''
	roomType.value = room?.roomType ?? 'default'
	roomNumber.value = room?.roomNumber ?? ''
	capacity.value = room?.capacity?.toString() ?? ''
	building.value = buildingOptions.value.find((option) => option.id === roomStory?.buildingId) ?? null
	story.value = storyOptions.value.find((option) => option.id === roomStory?.id) ?? null
	equipment.value = roomEquipment(room)
	contactPerson.value = undefined

	await searchUsers()
	if (props.room !== room) {
		return
	}

	const contactPersonUserId = room?.contactPersonUserId
	if (contactPersonUserId) {
		// The listing only knows the account id, so it stands in for an account outside the first search page
		contactPerson.value = users.value.find((user) => user.id === contactPersonUserId)
			?? { id: contactPersonUserId, displayName: contactPersonUserId }
	}
}

watch(open, (isOpen) => {
	if (isOpen) {
		fillForm()
	}
}, { immediate: true })

/**
 * Hand the entered room over to the parent.
 */
function submit(): void {
	emit('submit', {
		name: name.value.trim(),
		email: email.value.trim(),
		roomType: roomType.value.trim(),
		roomNumber: roomNumber.value.trim(),
		contactPersonUserId: optionId(contactPerson.value) ?? '',
		capacity: capacity.value === '' ? null : Number.parseInt(capacity.value, 10),
		storyId: optionId(story.value),
		...equipment.value,
	})
}
</script>

<template>
	<EntityDialog
		v-model:open="open"
		:canSubmit="canSubmit"
		:loading="loading"
		:name="room ? t('calendar_resource_management', 'Edit room') : t('calendar_resource_management', 'Add room')"
		size="normal"
		@submit="submit">
		<NcTextField
			v-model="name"
			:label="t('calendar_resource_management', 'Room name')"
			:placeholder="t('calendar_resource_management', 'Required')"
			required />

		<NcTextField
			v-model="email"
			:label="t('calendar_resource_management', 'Email')"
			:placeholder="t('calendar_resource_management', 'Required')"
			required
			type="email" />

		<NcTextField
			v-model="roomType"
			:label="t('calendar_resource_management', 'Room type')"
			:placeholder="t('calendar_resource_management', 'e.g. meeting-room')" />

		<NcTextField
			v-model="roomNumber"
			:label="t('calendar_resource_management', 'Room number')"
			:placeholder="t('calendar_resource_management', 'e.g. 1.23')" />

		<NcSelectUsers
			v-model="contactPerson"
			:inputLabel="t('calendar_resource_management', 'Contact person')"
			:loading="loadingUsers"
			:options="users"
			:placeholder="t('calendar_resource_management', 'Search for an account')"
			@search="searchUsers" />

		<NcTextField
			v-model="capacity"
			:label="t('calendar_resource_management', 'Capacity')"
			min="0"
			:placeholder="t('calendar_resource_management', 'e.g. 10')"
			type="number" />

		<NcSelect
			:inputLabel="t('calendar_resource_management', 'Building')"
			:modelValue="building"
			:options="buildingOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building')"
			@update:modelValue="selectBuilding" />

		<NcSelect
			v-model="story"
			:disabled="storyOptions.length === 0"
			:inputLabel="t('calendar_resource_management', 'Floor')"
			:options="storyOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building first')" />

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
	</EntityDialog>
</template>

<style lang="scss" scoped>
.crm-equipment legend {
	font-weight: bold;
}
</style>
