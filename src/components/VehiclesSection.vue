<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewVehicle, Vehicle } from '../types/types.ts'

import { mdiDelete, mdiPencil, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import VehicleDialog from './dialogs/VehicleDialog.vue'
import { nameById } from '../utils/entities.ts'

defineProps<{
	buildings: Building[]
	vehicles: Vehicle[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [vehicle: NewVehicle]
	update: [id: number, vehicle: NewVehicle]
	delete: [id: number]
}>()

const dialogOpen = ref(false)
const editing = ref<Vehicle | null>(null)

/**
 * Open the dialog to create a vehicle, or to edit the given one.
 *
 * @param vehicle The vehicle to edit
 */
function openDialog(vehicle: Vehicle | null = null): void {
	editing.value = vehicle
	dialogOpen.value = true
}

/**
 * Close the dialog. Called by the parent once the vehicle was saved.
 */
function close(): void {
	dialogOpen.value = false
}

defineExpose({ close })

/**
 * Hand the entered vehicle over to the parent.
 *
 * @param vehicle The entered vehicle
 */
function submit(vehicle: NewVehicle): void {
	if (editing.value) {
		emit('update', editing.value.id, vehicle)
	} else {
		emit('create', vehicle)
	}
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Vehicles are bookable in the calendar and belong to a building.')"
		:name="t('calendar_resource_management', 'Vehicles')">
		<NcButton class="crm-add" @click="openDialog()">
			<template #icon>
				<NcIconSvgWrapper :path="mdiPlus" />
			</template>
			{{ t('calendar_resource_management', 'Add vehicle') }}
		</NcButton>

		<div v-if="vehicles.length" class="crm-table-wrapper">
			<table class="crm-table">
				<thead>
					<tr>
						<th>{{ t('calendar_resource_management', 'Name') }}</th>
						<th>{{ t('calendar_resource_management', 'Email') }}</th>
						<th>{{ t('calendar_resource_management', 'Vehicle type') }}</th>
						<th>{{ t('calendar_resource_management', 'Building') }}</th>
						<th>{{ t('calendar_resource_management', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="vehicle in vehicles" :key="vehicle.id">
						<td>{{ vehicle.name }}</td>
						<td>{{ vehicle.email || '-' }}</td>
						<td>{{ vehicle.vehicleType || '-' }}</td>
						<td>{{ nameById(buildings, vehicle.buildingId) }}</td>
						<td>
							<div class="crm-table__actions">
								<NcButton
									:aria-label="t('calendar_resource_management', 'Edit vehicle {name}', { name: vehicle.name })"
									variant="tertiary"
									@click="openDialog(vehicle)">
									<template #icon>
										<NcIconSvgWrapper :path="mdiPencil" />
									</template>
								</NcButton>
								<NcButton
									:aria-label="t('calendar_resource_management', 'Delete vehicle {name}', { name: vehicle.name })"
									variant="tertiary"
									@click="emit('delete', vehicle.id)">
									<template #icon>
										<NcIconSvgWrapper :path="mdiDelete" />
									</template>
								</NcButton>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<VehicleDialog
			v-model:open="dialogOpen"
			:buildings="buildings"
			:loading="loading"
			:vehicle="editing"
			@submit="submit" />
	</NcSettingsSection>
</template>
