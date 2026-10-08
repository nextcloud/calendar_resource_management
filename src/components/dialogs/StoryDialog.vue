<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewStory, SelectOption, Story } from '../../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EntityDialog from './EntityDialog.vue'
import { optionId, selectOptions } from '../../utils/entities.ts'

const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
	/** The floor to edit, or null to create a new one */
	story: Story | null
	buildings: Building[]
	loading?: boolean
}>()

const emit = defineEmits<{
	submit: [story: NewStory]
}>()

const name = ref('')
const building = ref<SelectOption | null>(null)

const buildingOptions = computed(() => selectOptions(props.buildings))

const canSubmit = computed(() => name.value.trim() !== '' && optionId(building.value) !== null)

watch(open, (isOpen) => {
	if (isOpen) {
		name.value = props.story?.name ?? ''
		building.value = buildingOptions.value.find((option) => option.id === props.story?.buildingId) ?? null
	}
}, { immediate: true })

/**
 * Hand the entered floor over to the parent.
 */
function submit(): void {
	emit('submit', {
		name: name.value.trim(),
		buildingId: optionId(building.value),
	})
}
</script>

<template>
	<EntityDialog
		v-model:open="open"
		:canSubmit="canSubmit"
		:loading="loading"
		:name="story ? t('calendar_resource_management', 'Edit floor') : t('calendar_resource_management', 'Add floor')"
		@submit="submit">
		<NcTextField
			v-model="name"
			:label="t('calendar_resource_management', 'Floor name')"
			:placeholder="t('calendar_resource_management', 'Required')"
			required />

		<NcSelect
			v-model="building"
			:inputLabel="t('calendar_resource_management', 'Building')"
			:options="buildingOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building')" />
	</EntityDialog>
</template>
