<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewVehicle, SelectOption, User } from '../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { fetchUsers } from '../services/adminService.ts'
import { optionId, selectOptions } from '../utils/entities.ts'

/** The vehicle as the endpoints expect it, the fields write into it */
const vehicle = defineModel<NewVehicle>({ required: true })

const props = defineProps<{
	buildings: Building[]
}>()

const users = ref<User[]>([])
const loadingUsers = ref(false)

const buildingOptions = computed(() => selectOptions(props.buildings))

const building = computed<SelectOption | null>({
	get: () => buildingOptions.value.find((option) => option.id === vehicle.value.buildingId) ?? null,
	set: (selected) => {
		vehicle.value.buildingId = optionId(selected)
	},
})

// NcSelectUsers clears to undefined, it does not accept null
const contactPerson = computed<User | undefined>({
	get: () => vehicle.value.contactPersonUserId
		? users.value.find((user) => user.id === vehicle.value.contactPersonUserId)
		?? { id: vehicle.value.contactPersonUserId, displayName: vehicle.value.contactPersonUserId }
		: undefined,
	set: (selected) => {
		vehicle.value.contactPersonUserId = optionId(selected) ?? ''
	},
})

/**
 * A number field as text, empty for no value.
 *
 * @param key The numeric field of the vehicle
 * @return A writable text value
 */
function numberField(key: 'range' | 'seatingCapacity') {
	return computed<string>({
		get: () => vehicle.value[key] === null ? '' : String(vehicle.value[key]),
		set: (value) => {
			vehicle.value[key] = value === '' ? null : Number.parseInt(value, 10)
		},
	})
}

const range = numberField('range')
const seatingCapacity = numberField('seatingCapacity')

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

watch(() => vehicle.value.contactPersonUserId, (userId) => {
	// Show the display name of a contact person that is not loaded yet
	if (userId && !users.value.some((user) => user.id === userId)) {
		searchUsers(userId)
	}
}, { immediate: true })
</script>

<template>
	<NcTextField
		v-model="vehicle.name"
		class="crm-field"
		:label="t('calendar_resource_management', 'Vehicle name')"
		:placeholder="t('calendar_resource_management', 'Required')" />

	<NcTextField
		v-model="vehicle.email"
		class="crm-field"
		:label="t('calendar_resource_management', 'Email')"
		:placeholder="t('calendar_resource_management', 'Required')"
		type="email" />

	<NcTextField
		v-model="vehicle.vehicleType"
		class="crm-field"
		:label="t('calendar_resource_management', 'Vehicle type')"
		:placeholder="t('calendar_resource_management', 'e.g. car')" />

	<NcTextField
		v-model="vehicle.vehicleMake"
		class="crm-field"
		:label="t('calendar_resource_management', 'Make')"
		:placeholder="t('calendar_resource_management', 'e.g. Volkswagen')" />

	<NcTextField
		v-model="vehicle.vehicleModel"
		class="crm-field"
		:label="t('calendar_resource_management', 'Model')"
		:placeholder="t('calendar_resource_management', 'e.g. ID.3')" />

	<NcTextField
		v-model="seatingCapacity"
		class="crm-field"
		:label="t('calendar_resource_management', 'Seats')"
		min="0"
		type="number" />

	<NcTextField
		v-model="range"
		class="crm-field"
		:label="t('calendar_resource_management', 'Range in km')"
		min="0"
		type="number" />

	<NcCheckboxRadioSwitch v-model="vehicle.isElectric" class="crm-field" type="switch">
		{{ t('calendar_resource_management', 'Electric') }}
	</NcCheckboxRadioSwitch>

	<NcSelectUsers
		v-model="contactPerson"
		class="crm-field"
		:inputLabel="t('calendar_resource_management', 'Contact person')"
		:loading="loadingUsers"
		:options="users"
		:placeholder="t('calendar_resource_management', 'Search for an account')"
		@search="searchUsers" />

	<NcSelect
		v-model="building"
		class="crm-field"
		:inputLabel="t('calendar_resource_management', 'Building')"
		:options="buildingOptions"
		:placeholder="t('calendar_resource_management', 'Please select a building')" />
</template>
