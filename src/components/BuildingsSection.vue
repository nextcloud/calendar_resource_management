<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Buildings group the stories that rooms are located on.')"
		:name="t('calendar_resource_management', 'Buildings')">
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
			:disabled="!canSubmit"
			variant="primary"
			@click="submit">
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
							<NcButton
								:aria-label="t('calendar_resource_management', 'Delete building {name}', { name: building.name })"
								variant="error"
								@click="$emit('delete', building.id)">
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
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'

export default {
	name: 'BuildingsSection',

	components: {
		NcButton,
		NcSettingsSection,
		NcTextField,
	},

	props: {
		buildings: {
			type: Array,
			required: true,
		},
	},

	emits: ['create', 'delete'],

	data() {
		return {
			name: '',
			address: '',
		}
	},

	computed: {
		canSubmit() {
			return this.name.trim() !== ''
		},
	},

	methods: {
		t,

		submit() {
			this.$emit('create', {
				name: this.name.trim(),
				address: this.address.trim(),
			})
		},

		/**
		 * Clear the form. Called by the parent once the building was created.
		 */
		reset() {
			this.name = ''
			this.address = ''
		},
	},
}
</script>
