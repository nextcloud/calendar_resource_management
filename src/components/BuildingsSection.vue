<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

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
								@click="$emit('delete', building.id)">
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

<script>
import { mdiDelete, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'

export default {
	name: 'BuildingsSection',

	components: {
		NcButton,
		NcIconSvgWrapper,
		NcLoadingIcon,
		NcSettingsSection,
		NcTextField,
	},

	props: {
		buildings: {
			type: Array,
			required: true,
		},

		loading: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['create', 'delete'],

	data() {
		return {
			mdiDelete,
			mdiPlus,
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
