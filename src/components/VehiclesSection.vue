<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewVehicle, Vehicle } from '../types/types.ts'

import { mdiDelete, mdiPencil, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import VehicleFields from './VehicleFields.vue'
import { nameById, restrictionSummary } from '../utils/entities.ts'
import { emptyVehicle, isCompleteVehicle, trimmedVehicle } from '../utils/vehicles.ts'

defineProps<{
	buildings: Building[]
	vehicles: Vehicle[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [vehicle: NewVehicle]
	edit: [vehicle: Vehicle]
	delete: [id: number]
}>()

const vehicle = ref(emptyVehicle())

const canSubmit = computed(() => isCompleteVehicle(vehicle.value))

/**
 * Describe a vehicle by make, model and type.
 *
 * @param entry The vehicle
 * @return The details that are set
 */
function details(entry: Vehicle): string {
	const parts = [entry.vehicleMake, entry.vehicleModel, entry.vehicleType].filter(Boolean)
	if (entry.isElectric) {
		parts.push(t('calendar_resource_management', 'electric'))
	}

	return parts.length ? parts.join(' · ') : '-'
}

/**
 * Clear the form. Called by the parent once the vehicle was created.
 */
function reset(): void {
	vehicle.value = emptyVehicle()
}

defineExpose({ reset })
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Vehicles are bookable in the calendar and belong to a building.')"
		:name="t('calendar_resource_management', 'Vehicles')">
		<div v-if="vehicles.length" class="crm-table-wrapper crm-table-wrapper--wide">
			<table class="crm-table">
				<thead>
					<tr>
						<th>{{ t('calendar_resource_management', 'Name') }}</th>
						<th>{{ t('calendar_resource_management', 'Email') }}</th>
						<th>{{ t('calendar_resource_management', 'Vehicle') }}</th>
						<th>{{ t('calendar_resource_management', 'Seats') }}</th>
						<th>{{ t('calendar_resource_management', 'Building') }}</th>
						<th>{{ t('calendar_resource_management', 'Restricted to') }}</th>
						<th>{{ t('calendar_resource_management', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="entry in vehicles" :key="entry.id">
						<td>{{ entry.name }}</td>
						<td>{{ entry.email || '-' }}</td>
						<td>{{ details(entry) }}</td>
						<td>{{ entry.seatingCapacity || '-' }}</td>
						<td>{{ nameById(buildings, entry.buildingId) }}</td>
						<td>{{ restrictionSummary(entry) }}</td>
						<td class="crm-actions">
							<NcButton
								:aria-label="t('calendar_resource_management', 'Edit vehicle {name}', { name: entry.name })"
								variant="tertiary"
								@click="emit('edit', entry)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiPencil" />
								</template>
							</NcButton>
							<NcButton
								:aria-label="t('calendar_resource_management', 'Delete vehicle {name}', { name: entry.name })"
								variant="tertiary"
								@click="emit('delete', entry.id)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiDelete" />
								</template>
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<VehicleFields v-model="vehicle" :buildings="buildings" />

		<NcButton
			class="crm-field"
			:disabled="!canSubmit || loading"
			variant="primary"
			wide
			@click="emit('create', trimmedVehicle(vehicle))">
			<template #icon>
				<NcLoadingIcon v-if="loading" />
				<NcIconSvgWrapper v-else :path="mdiPlus" />
			</template>
			{{ t('calendar_resource_management', 'Add vehicle') }}
		</NcButton>
	</NcSettingsSection>
</template>
