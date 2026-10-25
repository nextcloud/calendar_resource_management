<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, GroupOption, UpdateVehicle, Vehicle } from '../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import EditDialog from './EditDialog.vue'
import RestrictionFields from './RestrictionFields.vue'
import VehicleFields from './VehicleFields.vue'
import { editableVehicle, emptyVehicle, isCompleteVehicle, trimmedVehicle } from '../utils/vehicles.ts'

const props = defineProps<{
	/** The vehicle to edit, the dialog is closed without one */
	vehicle: Vehicle | null
	buildings: Building[]
	loading?: boolean
}>()

const emit = defineEmits<{
	save: [id: number, vehicle: UpdateVehicle]
	close: []
}>()

const fields = ref(emptyVehicle())
const restricted = ref(false)
const restrictionGroups = ref<GroupOption[]>([])

// Restricted without groups, the server has no booking calendar for it
const hidden = computed(() => props.vehicle !== null && props.vehicle.restricted && props.vehicle.restrictionGroups.length === 0)

const canSubmit = computed(() => isCompleteVehicle(fields.value)
	// Hiding deletes the booking calendar, only the occ command may do that
	&& (!restricted.value || restrictionGroups.value.length > 0 || hidden.value))

watch(() => props.vehicle, (vehicle) => {
	if (!vehicle) {
		return
	}

	fields.value = editableVehicle(vehicle)
	restricted.value = vehicle.restricted
	restrictionGroups.value = vehicle.restrictionGroups.map((group) => ({ id: group.id, label: group.displayName }))
}, { immediate: true })

/**
 * Hand the changed vehicle over to the parent.
 */
function submit(): void {
	if (!props.vehicle) {
		return
	}

	emit('save', props.vehicle.id, {
		...trimmedVehicle(fields.value),
		restricted: restricted.value,
		restrictionGroupIds: restrictionGroups.value.map((group) => group.id),
	})
}
</script>

<template>
	<EditDialog
		:canSave="canSubmit"
		:loading="loading"
		:name="t('calendar_resource_management', 'Edit vehicle {name}', { name: vehicle?.name ?? '' })"
		:open="vehicle !== null"
		@close="emit('close')"
		@save="submit">
		<VehicleFields v-model="fields" :buildings="buildings" />

		<RestrictionFields
			v-model:groups="restrictionGroups"
			v-model:restricted="restricted"
			:hidden="hidden" />
	</EditDialog>
</template>
