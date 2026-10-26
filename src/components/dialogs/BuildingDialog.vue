<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewBuilding } from '../../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EntityDialog from './EntityDialog.vue'

const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
	/** The building to edit, or null to create a new one */
	building: Building | null
	loading?: boolean
}>()

const emit = defineEmits<{
	submit: [building: NewBuilding]
}>()

const name = ref('')
const address = ref('')

const canSubmit = computed(() => name.value.trim() !== '')

watch(open, (isOpen) => {
	if (isOpen) {
		name.value = props.building?.name ?? ''
		address.value = props.building?.address ?? ''
	}
}, { immediate: true })

/**
 * Hand the entered building over to the parent.
 */
function submit(): void {
	emit('submit', {
		name: name.value.trim(),
		address: address.value.trim(),
	})
}
</script>

<template>
	<EntityDialog
		v-model:open="open"
		:canSubmit="canSubmit"
		:loading="loading"
		:name="building ? t('calendar_resource_management', 'Edit building') : t('calendar_resource_management', 'Add building')"
		@submit="submit">
		<NcTextField
			v-model="name"
			:label="t('calendar_resource_management', 'Building name')"
			:placeholder="t('calendar_resource_management', 'Required')"
			required />

		<NcTextField
			v-model="address"
			:label="t('calendar_resource_management', 'Address')" />
	</EntityDialog>
</template>
