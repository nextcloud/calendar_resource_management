<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, EquipmentKey, NewRoom, Room, Story, User } from '../services/adminService.ts'
import type { SelectOption } from '../utils/entities.ts'

import { mdiDelete, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed, onMounted, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { fetchUsers } from '../services/adminService.ts'
import { nameById, optionId, selectOptions } from '../utils/entities.ts'

const props = defineProps<{
	buildings: Building[]
	rooms: Room[]
	stories: Story[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [room: NewRoom]
	delete: [id: number]
}>()

/**
 * Equipment flags of a room. `payloadKey` is what the create endpoint expects,
 * `responseKey` what the listing returns.
 */
const EQUIPMENT_TYPES: Array<{
	payloadKey: EquipmentKey
	responseKey: keyof Room
	label: () => string
}> = [
	{
		payloadKey: 'hasPhone',
		responseKey: 'hasPhone',
		label: () => t('calendar_resource_management', 'Phone'),
	},
	{
		payloadKey: 'hasVideo',
		responseKey: 'hasVideoConferencing',
		label: () => t('calendar_resource_management', 'Video conferencing'),
	},
	{
		payloadKey: 'hasTv',
		responseKey: 'hasTv',
		label: () => t('calendar_resource_management', 'TV'),
	},
	{
		payloadKey: 'hasProjector',
		responseKey: 'hasProjector',
		label: () => t('calendar_resource_management', 'Projector'),
	},
	{
		payloadKey: 'hasWhiteboard',
		responseKey: 'hasWhiteboard',
		label: () => t('calendar_resource_management', 'Whiteboard'),
	},
	{
		payloadKey: 'wheelchairAccessible',
		responseKey: 'isWheelchairAccessible',
		label: () => t('calendar_resource_management', 'Wheelchair accessible'),
	},
]

/**
 * All equipment flags switched off.
 *
 * @return Equipment payload keys mapped to false
 */
function noEquipment(): Record<EquipmentKey, boolean> {
	return Object.fromEntries(EQUIPMENT_TYPES.map((item) => [item.payloadKey, false])) as Record<EquipmentKey, boolean>
}

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
const equipment = ref(noEquipment())

const buildingOptions = computed(() => selectOptions(props.buildings))

const storyOptions = computed(() => {
	const buildingId = optionId(building.value)
	if (buildingId === null) {
		return []
	}

	return selectOptions(props.stories.filter((candidate) => candidate.buildingId === buildingId))
})

const canSubmit = computed(() => name.value.trim() !== '' && optionId(story.value) !== null)

watch(building, () => {
	// The story belongs to the previously selected building
	story.value = null
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

onMounted(searchUsers)

/**
 * Name of the building a room is located in.
 *
 * @param storyId The story the room is located on
 * @return The building name
 */
function buildingName(storyId: number): string {
	const candidate = props.stories.find((entity) => entity.id === storyId)

	return nameById(props.buildings, candidate?.buildingId)
}

/**
 * Translated list of the equipment a room provides.
 *
 * @param room The room
 * @return Comma separated equipment names
 */
function equipmentSummary(room: Room): string {
	const available = EQUIPMENT_TYPES
		.filter((item) => room[item.responseKey])
		.map((item) => item.label())

	return available.length ? available.join(', ') : '-'
}

/**
 * Clear the form. Called by the parent once the room was created.
 */
function reset(): void {
	name.value = ''
	email.value = ''
	roomType.value = 'default'
	roomNumber.value = ''
	contactPerson.value = undefined
	capacity.value = ''
	building.value = null
	story.value = null
	equipment.value = noEquipment()
}

defineExpose({ reset })

/**
 * Hand the entered room over to the parent.
 */
function submit(): void {
	emit('create', {
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
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Rooms are bookable in the calendar and are located on a floor of a building.')"
		:name="t('calendar_resource_management', 'Rooms')">
		<div v-if="rooms.length" class="crm-table-wrapper crm-table-wrapper--wide">
			<table class="crm-table">
				<thead>
					<tr>
						<th>{{ t('calendar_resource_management', 'Name') }}</th>
						<th>{{ t('calendar_resource_management', 'Email') }}</th>
						<th>{{ t('calendar_resource_management', 'Room type') }}</th>
						<th>{{ t('calendar_resource_management', 'Room number') }}</th>
						<th>{{ t('calendar_resource_management', 'Capacity') }}</th>
						<th>{{ t('calendar_resource_management', 'Equipment') }}</th>
						<th>{{ t('calendar_resource_management', 'Building') }}</th>
						<th>{{ t('calendar_resource_management', 'Floor') }}</th>
						<th>{{ t('calendar_resource_management', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="room in rooms" :key="room.id">
						<td>{{ room.name }}</td>
						<td>{{ room.email || '-' }}</td>
						<td>{{ room.roomType || '-' }}</td>
						<td>{{ room.roomNumber || '-' }}</td>
						<td>{{ room.capacity || '-' }}</td>
						<td>{{ equipmentSummary(room) }}</td>
						<td>{{ buildingName(room.storyId) }}</td>
						<td>{{ nameById(stories, room.storyId) }}</td>
						<td>
							<NcButton
								:aria-label="t('calendar_resource_management', 'Delete room {name}', { name: room.name })"
								variant="tertiary"
								@click="emit('delete', room.id)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiDelete" />
								</template>
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<NcTextField
			v-model="name"
			class="crm-field"
			:label="t('calendar_resource_management', 'Room name')"
			:placeholder="t('calendar_resource_management', 'Required')" />

		<NcTextField
			v-model="email"
			class="crm-field"
			:label="t('calendar_resource_management', 'Email')"
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
			:inputLabel="t('calendar_resource_management', 'Building')"
			:options="buildingOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building')" />

		<NcSelect
			v-model="story"
			class="crm-field"
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

		<NcButton
			class="crm-field"
			:disabled="!canSubmit || loading"
			variant="primary"
			wide
			@click="submit">
			<template #icon>
				<NcLoadingIcon v-if="loading" />
				<NcIconSvgWrapper v-else :path="mdiPlus" />
			</template>
			{{ t('calendar_resource_management', 'Add room') }}
		</NcButton>
	</NcSettingsSection>
</template>
