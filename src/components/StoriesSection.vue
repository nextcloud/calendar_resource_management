<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Floors are the levels of a building.')"
		:name="t('calendar_resource_management', 'Floors')">
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
							<NcButton
								:aria-label="t('calendar_resource_management', 'Delete floor {name}', { name: story.name })"
								variant="tertiary"
								@click="$emit('delete', story.id)">
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
			:label="t('calendar_resource_management', 'Floor name')"
			:placeholder="t('calendar_resource_management', 'Required')" />

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
			{{ t('calendar_resource_management', 'Add floor') }}
		</NcButton>
	</NcSettingsSection>
</template>

<script>
import { mdiDelete, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { nameById, optionId, selectOptions } from '../utils/entities.js'

export default {
	name: 'StoriesSection',

	components: {
		NcButton,
		NcSelect,
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

		stories: {
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
				buildingId: optionId(this.building),
			})
		},

		/**
		 * Clear the form. Called by the parent once the story was created.
		 */
		reset() {
			this.name = ''
			this.building = null
		},
	},
}
</script>
