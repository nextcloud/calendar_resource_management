<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewVehicle, SelectOption, User, Vehicle } from '../../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EntityDialog from './EntityDialog.vue'
import { fetchUsers } from '../../services/adminService.ts'
import { optionId, selectOptions } from '../../utils/entities.ts'

const open = defineModel<boolean>('open', { required: true })
const props = defineProps<{
	vehicle: Vehicle | null
	buildings: Building[]
	loading?: boolean
}>()
const emit = defineEmits<{
	submit: [vehicle: NewVehicle]
}>()

const name = ref('')
const email = ref('')
const vehicleMake = ref('')
const vehicleModel = ref('')
const vehicleType = ref<{ id: string, label: string } | null>(null)
const isElectric = ref(false)
const range = ref('')
const seatingCapacity = ref('')
const building = ref<SelectOption | null>(null)
const contactPerson = ref<User | undefined>()
const users = ref<User[]>([])
const loadingUsers = ref(false)

const buildingOptions = computed(() => selectOptions(props.buildings))
const vehicleTypes = [
	{ id: 'bicycle', label: t('calendar_resource_management', 'Bicycle') },
	{ id: 'scooter', label: t('calendar_resource_management', 'Scooter') },
	{ id: 'motorbike', label: t('calendar_resource_management', 'Motorbike') },
	{ id: 'car', label: t('calendar_resource_management', 'Car') },
	{ id: 'plane', label: t('calendar_resource_management', 'Plane') },
	{ id: 'helicopter', label: t('calendar_resource_management', 'Helicopter') },
	{ id: 'other', label: t('calendar_resource_management', 'Other') },
]
const canSubmit = computed(() => name.value.trim() !== ''
	&& email.value.trim() !== ''
	&& vehicleMake.value.trim() !== ''
	&& vehicleModel.value.trim() !== ''
	&& optionId(vehicleType.value) !== null
	&& optionId(building.value) !== null
	&& [range.value, seatingCapacity.value].every((value) => value === ''
		|| (Number.isInteger(Number(value)) && Number(value) >= 0 && Number(value) <= 2147483647)))

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

watch(open, async (isOpen) => {
	if (!isOpen) {
		return
	}
	const vehicle = props.vehicle
	name.value = vehicle?.name ?? ''
	email.value = vehicle?.email ?? ''
	vehicleMake.value = vehicle?.vehicleMake ?? ''
	vehicleModel.value = vehicle?.vehicleModel ?? ''
	vehicleType.value = vehicleTypes.find((type) => type.id === (vehicle?.vehicleType ?? 'car')) ?? null
	isElectric.value = vehicle?.isElectric ?? false
	range.value = vehicle?.range?.toString() ?? ''
	seatingCapacity.value = vehicle?.seatingCapacity?.toString() ?? ''
	building.value = buildingOptions.value.find((option) => option.id === vehicle?.buildingId) ?? null
	const contactId = vehicle?.contactPersonUserId
	contactPerson.value = contactId ? { id: contactId, displayName: contactId } : undefined
	await searchUsers()
}, { immediate: true })

/**
 *
 */
function submit(): void {
	emit('submit', {
		name: name.value.trim(),
		email: email.value.trim(),
		buildingId: optionId(building.value),
		vehicleType: optionId(vehicleType.value) ?? '',
		vehicleMake: vehicleMake.value.trim(),
		vehicleModel: vehicleModel.value.trim(),
		contactPersonUserId: optionId(contactPerson.value) ?? '',
		isElectric: isElectric.value,
		range: range.value === '' ? null : Number(range.value),
		seatingCapacity: seatingCapacity.value === '' ? null : Number(seatingCapacity.value),
	})
}
</script>

<template>
	<EntityDialog
		v-model:open="open"
		:canSubmit="canSubmit"
		:loading="loading"
		:name="vehicle ? t('calendar_resource_management', 'Edit vehicle') : t('calendar_resource_management', 'Add vehicle')"
		size="normal"
		@submit="submit">
		<NcTextField
			v-model="name"
			:label="t('calendar_resource_management', 'Vehicle name')"
			required />

		<NcTextField
			v-model="email"
			:label="t('calendar_resource_management', 'Email')"
			required
			type="email" />

		<NcTextField
			v-model="vehicleMake"
			:label="t('calendar_resource_management', 'Make')"
			required />

		<NcTextField
			v-model="vehicleModel"
			:label="t('calendar_resource_management', 'Model')"
			required />

		<NcSelect
			v-model="vehicleType"
			:inputLabel="t('calendar_resource_management', 'Vehicle type')"
			:options="vehicleTypes" />

		<NcSelect
			v-model="building"
			:inputLabel="t('calendar_resource_management', 'Building')"
			:options="buildingOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building')" />

		<NcSelectUsers
			v-model="contactPerson"
			:inputLabel="t('calendar_resource_management', 'Contact person')"
			:loading="loadingUsers"
			:options="users"
			:placeholder="t('calendar_resource_management', 'Search for an account')"
			@search="searchUsers" />

		<NcCheckboxRadioSwitch v-model="isElectric" type="switch">
			{{ t('calendar_resource_management', 'Electric vehicle') }}
		</NcCheckboxRadioSwitch>

		<NcTextField
			v-model="range"
			:label="t('calendar_resource_management', 'Range')"
			min="0"
			max="2147483647"
			step="1"
			type="number" />

		<NcTextField
			v-model="seatingCapacity"
			:label="t('calendar_resource_management', 'Seating capacity')"
			min="0"
			max="2147483647"
			step="1"
			type="number" />
	</EntityDialog>
</template>
