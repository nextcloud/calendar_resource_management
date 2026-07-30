<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Resources are bookable in the calendar and belong to a building.')"
		:name="t('calendar_resource_management', 'Resources')">
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
			:disabled="!canSubmit"
			variant="primary"
			@click="submit">
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
							<NcButton
								:aria-label="t('calendar_resource_management', 'Delete resource {name}', { name: resource.name })"
								variant="error"
								@click="$emit('delete', resource.id)">
								{{ t('calendar_resource_management', 'Delete') }}
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</NcSettingsSection>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { nameById, optionId, selectOptions } from '../utils/entities.js'

export default {
	name: 'ResourcesSection',

	components: {
		NcButton,
		NcSelect,
		NcSettingsSection,
		NcTextField,
	},

	props: {
		buildings: {
			type: Array,
			required: true,
		},

		resources: {
			type: Array,
			required: true,
		},
	},

	emits: ['create', 'delete'],

	data() {
		return {
			name: '',
			email: '',
			resourceType: 'default',
			building: null,
		}
	},

	computed: {
		buildingOptions() {
			return selectOptions(this.buildings)
		},

		canSubmit() {
			return this.name.trim() !== '' && optionId(this.building) !== null
		},
	},

	methods: {
		t,
		nameById,

		submit() {
			this.$emit('create', {
				name: this.name.trim(),
				email: this.email.trim(),
				resourceType: this.resourceType.trim(),
				buildingId: optionId(this.building),
			})
		},

		/**
		 * Clear the form. Called by the parent once the resource was created.
		 */
		reset() {
			this.name = ''
			this.email = ''
			this.resourceType = 'default'
			this.building = null
		},
	},
}
</script>
