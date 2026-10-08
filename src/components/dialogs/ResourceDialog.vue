<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewResource, Resource, SelectOption } from '../../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EntityDialog from './EntityDialog.vue'
import { optionId, selectOptions } from '../../utils/entities.ts'

const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
	/** The resource to edit, or null to create a new one */
	resource: Resource | null
	buildings: Building[]
	loading?: boolean
}>()

const emit = defineEmits<{
	submit: [resource: NewResource]
}>()

const name = ref('')
const email = ref('')
const resourceType = ref('default')
const building = ref<SelectOption | null>(null)

const buildingOptions = computed(() => selectOptions(props.buildings))

const canSubmit = computed(() => name.value.trim() !== '' && email.value.trim() !== '' && optionId(building.value) !== null)

watch(open, (isOpen) => {
	if (isOpen) {
		name.value = props.resource?.name ?? ''
		email.value = props.resource?.email ?? ''
		resourceType.value = props.resource?.resourceType ?? 'default'
		building.value = buildingOptions.value.find((option) => option.id === props.resource?.buildingId) ?? null
	}
}, { immediate: true })

/**
 * Hand the entered resource over to the parent.
 */
function submit(): void {
	emit('submit', {
		name: name.value.trim(),
		email: email.value.trim(),
		resourceType: resourceType.value.trim(),
		buildingId: optionId(building.value),
	})
}
</script>

<template>
	<EntityDialog
		v-model:open="open"
		:canSubmit="canSubmit"
		:loading="loading"
		:name="resource ? t('calendar_resource_management', 'Edit resource') : t('calendar_resource_management', 'Add resource')"
		@submit="submit">
		<NcTextField
			v-model="name"
			:label="t('calendar_resource_management', 'Resource name')"
			:placeholder="t('calendar_resource_management', 'Required')"
			required />

		<NcTextField
			v-model="email"
			:label="t('calendar_resource_management', 'Email')"
			:placeholder="t('calendar_resource_management', 'Required')"
			required
			type="email" />

		<NcTextField
			v-model="resourceType"
			:label="t('calendar_resource_management', 'Resource type')"
			:placeholder="t('calendar_resource_management', 'e.g. projector')" />

		<NcSelect
			v-model="building"
			:inputLabel="t('calendar_resource_management', 'Building')"
			:options="buildingOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building')" />
	</EntityDialog>
</template>
