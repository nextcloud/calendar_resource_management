<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, UpdateBuilding } from '../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EditDialog from './EditDialog.vue'

const props = defineProps<{
	/** The building to edit, the dialog is closed without one */
	building: Building | null
	loading?: boolean
}>()

const emit = defineEmits<{
	save: [id: number, building: UpdateBuilding]
	close: []
}>()

const name = ref('')
const address = ref('')
const description = ref('')
const wheelchairAccessible = ref(false)

const canSubmit = computed(() => name.value.trim() !== '')

watch(() => props.building, (building) => {
	if (!building) {
		return
	}

	name.value = building.name
	address.value = building.address ?? ''
	description.value = building.description ?? ''
	wheelchairAccessible.value = building.isWheelchairAccessible
}, { immediate: true })

/**
 * Hand the changed building over to the parent.
 */
function submit(): void {
	if (!props.building) {
		return
	}

	emit('save', props.building.id, {
		name: name.value.trim(),
		address: address.value.trim(),
		description: description.value.trim(),
		wheelchairAccessible: wheelchairAccessible.value,
	})
}
</script>

<template>
	<EditDialog
		:canSave="canSubmit"
		:loading="loading"
		:name="t('calendar_resource_management', 'Edit building {name}', { name: building?.name ?? '' })"
		:open="building !== null"
		@close="emit('close')"
		@save="submit">
		<NcTextField
			v-model="name"
			class="crm-field"
			:label="t('calendar_resource_management', 'Building name')"
			:placeholder="t('calendar_resource_management', 'Required')" />

		<NcTextField
			v-model="address"
			class="crm-field"
			:label="t('calendar_resource_management', 'Address')"
			:placeholder="t('calendar_resource_management', 'e.g. Main Street 1, Berlin')" />

		<NcTextField
			v-model="description"
			class="crm-field"
			:label="t('calendar_resource_management', 'Description')" />

		<NcCheckboxRadioSwitch v-model="wheelchairAccessible" type="switch">
			{{ t('calendar_resource_management', 'Wheelchair accessible') }}
		</NcCheckboxRadioSwitch>
	</EditDialog>
</template>
