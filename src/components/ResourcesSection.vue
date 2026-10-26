<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewResource, Resource } from '../types/types.ts'

import { mdiDelete, mdiPencil, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import ResourceDialog from './dialogs/ResourceDialog.vue'
import { nameById } from '../utils/entities.ts'

defineProps<{
	buildings: Building[]
	resources: Resource[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [resource: NewResource]
	update: [id: number, resource: NewResource]
	delete: [id: number]
}>()

const dialogOpen = ref(false)
const editing = ref<Resource | null>(null)

/**
 * Open the dialog to create a resource, or to edit the given one.
 *
 * @param resource The resource to edit
 */
function openDialog(resource: Resource | null = null): void {
	editing.value = resource
	dialogOpen.value = true
}

/**
 * Close the dialog. Called by the parent once the resource was saved.
 */
function close(): void {
	dialogOpen.value = false
}

defineExpose({ close })

/**
 * Hand the entered resource over to the parent.
 *
 * @param resource The entered resource
 */
function submit(resource: NewResource): void {
	if (editing.value) {
		emit('update', editing.value.id, resource)
	} else {
		emit('create', resource)
	}
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Resources are bookable in the calendar and belong to a building.')"
		:name="t('calendar_resource_management', 'Resources')">
		<NcButton class="crm-add" @click="openDialog()">
			<template #icon>
				<NcIconSvgWrapper :path="mdiPlus" />
			</template>
			{{ t('calendar_resource_management', 'Add resource') }}
		</NcButton>

		<div v-if="resources.length" class="crm-table-wrapper">
			<table class="crm-table">
				<thead>
					<tr>
						<th>{{ t('calendar_resource_management', 'Name') }}</th>
						<th>{{ t('calendar_resource_management', 'Email') }}</th>
						<th>{{ t('calendar_resource_management', 'Resource type') }}</th>
						<th>{{ t('calendar_resource_management', 'Building') }}</th>
						<th>{{ t('calendar_resource_management', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="resource in resources" :key="resource.id">
						<td>{{ resource.name }}</td>
						<td>{{ resource.email || '-' }}</td>
						<td>{{ resource.resourceType || '-' }}</td>
						<td>{{ nameById(buildings, resource.buildingId) }}</td>
						<td>
							<div class="crm-table__actions">
								<NcButton
									:aria-label="t('calendar_resource_management', 'Edit resource {name}', { name: resource.name })"
									variant="tertiary"
									@click="openDialog(resource)">
									<template #icon>
										<NcIconSvgWrapper :path="mdiPencil" />
									</template>
								</NcButton>
								<NcButton
									:aria-label="t('calendar_resource_management', 'Delete resource {name}', { name: resource.name })"
									variant="tertiary"
									@click="emit('delete', resource.id)">
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

		<ResourceDialog
			v-model:open="dialogOpen"
			:buildings="buildings"
			:loading="loading"
			:resource="editing"
			@submit="submit" />
	</NcSettingsSection>
</template>
