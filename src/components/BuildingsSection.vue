<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewBuilding } from '../types/types.ts'

import { mdiDelete, mdiPencil, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import BuildingDialog from './dialogs/BuildingDialog.vue'

defineProps<{
	buildings: Building[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [building: NewBuilding]
	update: [id: number, building: NewBuilding]
	delete: [id: number]
}>()

const dialogOpen = ref(false)
const editing = ref<Building | null>(null)

/**
 * Open the dialog to create a building, or to edit the given one.
 *
 * @param building The building to edit
 */
function openDialog(building: Building | null = null): void {
	editing.value = building
	dialogOpen.value = true
}

/**
 * Close the dialog. Called by the parent once the building was saved.
 */
function close(): void {
	dialogOpen.value = false
}

defineExpose({ close })

/**
 * Hand the entered building over to the parent.
 *
 * @param building The entered building
 */
function submit(building: NewBuilding): void {
	if (editing.value) {
		emit('update', editing.value.id, building)
	} else {
		emit('create', building)
	}
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Buildings group the floors that rooms are located on.')"
		:name="t('calendar_resource_management', 'Buildings')">
		<NcButton class="crm-add" @click="openDialog()">
			<template #icon>
				<NcIconSvgWrapper :path="mdiPlus" />
			</template>
			{{ t('calendar_resource_management', 'Add building') }}
		</NcButton>

		<div v-if="buildings.length" class="crm-table-wrapper">
			<table class="crm-table">
				<thead>
					<tr>
						<th>{{ t('calendar_resource_management', 'Name') }}</th>
						<th>{{ t('calendar_resource_management', 'Address') }}</th>
						<th>{{ t('calendar_resource_management', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="building in buildings" :key="building.id">
						<td>{{ building.name }}</td>
						<td>{{ building.address || '-' }}</td>
						<td>
							<div class="crm-table__actions">
								<NcButton
									:aria-label="t('calendar_resource_management', 'Edit building {name}', { name: building.name })"
									variant="tertiary"
									@click="openDialog(building)">
									<template #icon>
										<NcIconSvgWrapper :path="mdiPencil" />
									</template>
								</NcButton>
								<NcButton
									:aria-label="t('calendar_resource_management', 'Delete building {name}', { name: building.name })"
									variant="tertiary"
									@click="emit('delete', building.id)">
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

		<BuildingDialog
			v-model:open="dialogOpen"
			:building="editing"
			:loading="loading"
			@submit="submit" />
	</NcSettingsSection>
</template>
