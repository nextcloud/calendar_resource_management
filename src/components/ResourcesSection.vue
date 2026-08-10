<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewResource, Resource } from '../services/adminService.ts'
import type { SelectOption } from '../utils/entities.ts'

import { mdiDelete, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { nameById, optionId, selectOptions } from '../utils/entities.ts'

const props = defineProps<{
	buildings: Building[]
	resources: Resource[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [resource: NewResource]
	delete: [id: number]
}>()

const name = ref('')
const email = ref('')
const resourceType = ref('default')
const building = ref<SelectOption | null>(null)

const buildingOptions = computed(() => selectOptions(props.buildings))

const canSubmit = computed(() => name.value.trim() !== '' && optionId(building.value) !== null)

/**
 * Clear the form. Called by the parent once the resource was created.
 */
function reset(): void {
	name.value = ''
	email.value = ''
	resourceType.value = 'default'
	building.value = null
}

defineExpose({ reset })

/**
 * Hand the entered resource over to the parent.
 */
function submit(): void {
	emit('create', {
		name: name.value.trim(),
		email: email.value.trim(),
		resourceType: resourceType.value.trim(),
		buildingId: optionId(building.value),
	})
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Resources are bookable in the calendar and belong to a building.')"
		:name="t('calendar_resource_management', 'Resources')">
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
							<NcButton
								:aria-label="t('calendar_resource_management', 'Delete resource {name}', { name: resource.name })"
								variant="tertiary"
								@click="emit('delete', resource.id)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiDelete" />
								</template>
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<NcTextField
			v-model="name"
			class="crm-field"
			:label="t('calendar_resource_management', 'Resource name')"
			:placeholder="t('calendar_resource_management', 'Required')" />

		<NcTextField
			v-model="email"
			class="crm-field"
			:label="t('calendar_resource_management', 'Email')"
			type="email" />

		<NcTextField
			v-model="resourceType"
			class="crm-field"
			:label="t('calendar_resource_management', 'Resource type')"
			:placeholder="t('calendar_resource_management', 'e.g. projector')" />

		<NcSelect
			v-model="building"
			class="crm-field"
			:inputLabel="t('calendar_resource_management', 'Building')"
			:options="buildingOptions"
			:placeholder="t('calendar_resource_management', 'Please select a building')" />

		<NcButton
			class="crm-field"
			:disabled="!canSubmit || loading"
			variant="primary"
			wide
			@click="submit">
			<template #icon>
				<NcLoadingIcon v-if="loading" />
				<NcIconSvgWrapper v-else :path="mdiPlus" />
			</template>
			{{ t('calendar_resource_management', 'Add resource') }}
		</NcButton>
	</NcSettingsSection>
</template>
