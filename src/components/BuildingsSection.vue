<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewBuilding } from '../types/types.ts'

import { mdiDelete, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'

defineProps<{
	buildings: Building[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [building: NewBuilding]
	delete: [id: number]
}>()

const name = ref('')
const address = ref('')

const canSubmit = computed(() => name.value.trim() !== '')

/**
 * Clear the form. Called by the parent once the building was created.
 */
function reset(): void {
	name.value = ''
	address.value = ''
}

defineExpose({ reset })

/**
 * Hand the entered building over to the parent.
 */
function submit(): void {
	emit('create', {
		name: name.value.trim(),
		address: address.value.trim(),
	})
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Buildings group the floors that rooms are located on.')"
		:name="t('calendar_resource_management', 'Buildings')">
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
							<NcButton
								:aria-label="t('calendar_resource_management', 'Delete building {name}', { name: building.name })"
								variant="tertiary"
								@click="emit('delete', building.id)">
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
			:label="t('calendar_resource_management', 'Building name')"
			:placeholder="t('calendar_resource_management', 'Required')" />

		<NcTextField
			v-model="address"
			class="crm-field"
			:label="t('calendar_resource_management', 'Address')" />

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
			{{ t('calendar_resource_management', 'Add building') }}
		</NcButton>
	</NcSettingsSection>
</template>
