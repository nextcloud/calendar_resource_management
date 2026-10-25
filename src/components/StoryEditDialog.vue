<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, SelectOption, Story, UpdateStory } from '../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EditDialog from './EditDialog.vue'
import { optionId, selectOptions } from '../utils/entities.ts'

const props = defineProps<{
	/** The floor to edit, the dialog is closed without one */
	story: Story | null
	buildings: Building[]
	loading?: boolean
}>()

const emit = defineEmits<{
	save: [id: number, story: UpdateStory]
	close: []
}>()

const name = ref('')
const building = ref<SelectOption | null>(null)

const buildingOptions = computed(() => selectOptions(props.buildings))

const canSubmit = computed(() => name.value.trim() !== '' && optionId(building.value) !== null)

watch(() => props.story, (story) => {
	if (!story) {
		return
	}

	const storyBuilding = props.buildings.find((candidate) => candidate.id === story.buildingId)
	name.value = story.name
	building.value = storyBuilding ? { id: storyBuilding.id, label: storyBuilding.name } : null
}, { immediate: true })

/**
 * Hand the changed floor over to the parent.
 */
function submit(): void {
	if (!props.story) {
		return
	}

	emit('save', props.story.id, {
		name: name.value.trim(),
		buildingId: optionId(building.value),
	})
}
</script>

<template>
	<EditDialog
		:canSave="canSubmit"
		:loading="loading"
		:name="t('calendar_resource_management', 'Edit floor {name}', { name: story?.name ?? '' })"
		:open="story !== null"
		@close="emit('close')"
		@save="submit">
		<NcTextField
			v-model="name"
			class="crm-field"
			:label="t('calendar_resource_management', 'Floor name')"
			:placeholder="t('calendar_resource_management', 'Required')" />

		<NcSelect
			v-model="building"
			class="crm-field"
			:clearable="false"
			:inputLabel="t('calendar_resource_management', 'Building')"
			:options="buildingOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building')" />

		<p v-if="story && optionId(building) !== story.buildingId" class="crm-hint">
			{{ t('calendar_resource_management', 'The rooms on this floor move to the other building as well.') }}
		</p>
	</EditDialog>
</template>

<style lang="scss" scoped>
.crm-hint {
	color: var(--color-text-maxcontrast);
}
</style>
