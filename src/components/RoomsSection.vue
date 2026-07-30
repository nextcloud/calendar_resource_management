<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Rooms are bookable in the calendar and are located on a story of a building.')"
		:name="t('calendar_resource_management', 'Rooms')">
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

		<NcTextField
			v-model="contactPerson"
			class="crm-field"
			:label="t('calendar_resource_management', 'Contact person (user ID)')"
			:placeholder="t('calendar_resource_management', 'e.g. admin')" />

		<NcTextField
			v-model="capacity"
			class="crm-field"
			:label="t('calendar_resource_management', 'Capacity (persons)')"
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
			:inputLabel="t('calendar_resource_management', 'Story')"
			:options="storyOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building first')" />

		<fieldset class="crm-equipment">
			<legend>{{ t('calendar_resource_management', 'Equipment') }}</legend>
			<NcCheckboxRadioSwitch
				v-for="item in equipmentTypes"
				:key="item.payloadKey"
				v-model="equipment[item.payloadKey]"
				type="switch">
				{{ item.label() }}
			</NcCheckboxRadioSwitch>
		</fieldset>

		<NcButton
			:disabled="!canSubmit"
			variant="primary"
			@click="submit">
			{{ t('calendar_resource_management', 'Add room') }}
		</NcButton>

		<div v-if="rooms.length" class="crm-table-wrapper">
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
						<th>{{ t('calendar_resource_management', 'Story') }}</th>
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
								variant="error"
								@click="$emit('delete', room.id)">
								{{ t('calendar_resource_management', 'Delete') }}
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</NcSettingsSection>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { nameById, optionId, selectOptions } from '../utils/entities.js'

/**
 * Equipment flags of a room. `payloadKey` is what the create endpoint expects,
 * `responseKey` what the listing returns.
 */
const EQUIPMENT_TYPES = [
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
 * @return {object} Equipment payload keys mapped to false
 */
function noEquipment() {
	return Object.fromEntries(EQUIPMENT_TYPES.map((item) => [item.payloadKey, false]))
}

export default {
	name: 'RoomsSection',

	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcSelect,
		NcSettingsSection,
		NcTextField,
	},

	props: {
		buildings: {
			type: Array,
			required: true,
		},

		rooms: {
			type: Array,
			required: true,
		},

		stories: {
			type: Array,
			required: true,
		},
	},

	emits: ['create', 'delete'],

	data() {
		return {
			name: '',
			email: '',
			roomType: 'default',
			roomNumber: '',
			contactPerson: '',
			capacity: '',
			building: null,
			story: null,
			equipment: noEquipment(),
		}
	},

	computed: {
		equipmentTypes() {
			return EQUIPMENT_TYPES
		},

		buildingOptions() {
			return selectOptions(this.buildings)
		},

		storyOptions() {
			const buildingId = optionId(this.building)
			if (buildingId === null) {
				return []
			}

			return selectOptions(this.stories.filter((story) => story.buildingId === buildingId))
		},

		canSubmit() {
			return this.name.trim() !== '' && optionId(this.story) !== null
		},
	},

	watch: {
		building() {
			// The story belongs to the previously selected building
			this.story = null
		},
	},

	methods: {
		t,
		nameById,

		/**
		 * Name of the building a room is located in.
		 *
		 * @param {number} storyId The story the room is located on
		 * @return {string} The building name
		 */
		buildingName(storyId) {
			const story = this.stories.find((candidate) => candidate.id === storyId)

			return nameById(this.buildings, story?.buildingId)
		},

		/**
		 * Translated list of the equipment a room provides.
		 *
		 * @param {object} room The room
		 * @return {string} Comma separated equipment names
		 */
		equipmentSummary(room) {
			const available = EQUIPMENT_TYPES
				.filter((item) => room[item.responseKey])
				.map((item) => item.label())

			return available.length ? available.join(', ') : '-'
		},

		submit() {
			this.$emit('create', {
				name: this.name.trim(),
				email: this.email.trim(),
				roomType: this.roomType.trim(),
				roomNumber: this.roomNumber.trim(),
				contactPersonUserId: this.contactPerson.trim(),
				capacity: this.capacity === '' ? null : Number.parseInt(this.capacity, 10),
				storyId: optionId(this.story),
				...this.equipment,
			})
		},

		/**
		 * Clear the form. Called by the parent once the room was created.
		 */
		reset() {
			this.name = ''
			this.email = ''
			this.roomType = 'default'
			this.roomNumber = ''
			this.contactPerson = ''
			this.capacity = ''
			this.building = null
			this.story = null
			this.equipment = noEquipment()
		},
	},
}
</script>
