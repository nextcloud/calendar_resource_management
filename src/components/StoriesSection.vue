<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewStory, Story } from '../types/types.ts'

import { mdiDelete, mdiPencil, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import StoryDialog from './dialogs/StoryDialog.vue'
import { nameById } from '../utils/entities.ts'

defineProps<{
	buildings: Building[]
	stories: Story[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [story: NewStory]
	update: [id: number, story: NewStory]
	delete: [id: number]
}>()

const dialogOpen = ref(false)
const editing = ref<Story | null>(null)

/**
 * Open the dialog to create a floor, or to edit the given one.
 *
 * @param story The floor to edit
 */
function openDialog(story: Story | null = null): void {
	editing.value = story
	dialogOpen.value = true
}

/**
 * Close the dialog. Called by the parent once the floor was saved.
 */
function close(): void {
	dialogOpen.value = false
}

defineExpose({ close })

/**
 * Hand the entered floor over to the parent.
 *
 * @param story The entered floor
 */
function submit(story: NewStory): void {
	if (editing.value) {
		emit('update', editing.value.id, story)
	} else {
		emit('create', story)
	}
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Floors are the levels of a building.')"
		:name="t('calendar_resource_management', 'Floors')">
		<NcButton class="crm-add" @click="openDialog()">
			<template #icon>
				<NcIconSvgWrapper :path="mdiPlus" />
			</template>
			{{ t('calendar_resource_management', 'Add floor') }}
		</NcButton>

		<div v-if="stories.length" class="crm-table-wrapper">
			<table class="crm-table">
				<thead>
					<tr>
						<th>{{ t('calendar_resource_management', 'Name') }}</th>
						<th>{{ t('calendar_resource_management', 'Building') }}</th>
						<th>{{ t('calendar_resource_management', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="story in stories" :key="story.id">
						<td>{{ story.name }}</td>
						<td>{{ nameById(buildings, story.buildingId) }}</td>
						<td>
							<div class="crm-table__actions">
								<NcButton
									:aria-label="t('calendar_resource_management', 'Edit floor {name}', { name: story.name })"
									variant="tertiary"
									@click="openDialog(story)">
									<template #icon>
										<NcIconSvgWrapper :path="mdiPencil" />
									</template>
								</NcButton>
								<NcButton
									:aria-label="t('calendar_resource_management', 'Delete floor {name}', { name: story.name })"
									variant="tertiary"
									@click="emit('delete', story.id)">
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

		<StoryDialog
			v-model:open="dialogOpen"
			:buildings="buildings"
			:loading="loading"
			:story="editing"
			@submit="submit" />
	</NcSettingsSection>
</template>
