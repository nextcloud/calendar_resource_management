<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="crm-group-picker">
		<NcSelect
			:inputId="inputId"
			:inputLabel="label"
			:modelValue="selected"
			:options="options"
			:loading="loading"
			:filterable="false"
			:placeholder="t('calendar_resource_management', 'Search for a group …')"
			label="label"
			multiple
			keepOpen
			@open="onSearch('')"
			@search="onSearch"
			@search:blur="onBlur"
			@update:modelValue="onSelect" />
		<p class="crm-group-picker__hint">
			{{ hint }}
		</p>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import debounce from 'debounce'
import { useId } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { searchGroups } from '../../services/api.js'

/**
 * Shape a raw group record into an NcSelect option.
 *
 * @param {object} group the raw group (id + optional displayname)
 * @return {{id: string, label: string}} the option
 */
function toOption(group) {
	return {
		id: group.id,
		label: group.displayname,
	}
}

export default {
	name: 'GroupPickerField',

	components: {
		NcSelect,
	},

	props: {
		modelValue: {
			type: Array,
			default: () => [],
		},

		label: {
			type: String,
			required: true,
		},

		inputId: {
			type: String,
			default: () => useId(),
		},
	},

	emits: ['update:modelValue'],

	data() {
		return {
			options: [],
			loading: false,
			searchError: null,
			labels: {},
		}
	},

	computed: {
		selected() {
			return this.modelValue.map((id) => ({ id, label: this.labels[id] ?? id }))
		},

		hint() {
			return this.modelValue.length > 0
				? t('calendar_resource_management', 'Only members of these groups can see and book it.')
				: t('calendar_resource_management', 'Nobody can see or book it until a group is added.')
		},
	},

	methods: {
		t,

		/**
		 * Search populating the option list.
		 *
		 * @param {string} term the current search term
		 */
		onSearch: debounce(async function(term) {
			this.loading = true
			try {
				const options = (await searchGroups(term)).map(toOption)
				for (const option of options) {
					this.labels[option.id] = option.label
				}
				this.options = options
				this.searchError = null
			} catch (error) {
				const message = error.message
				this.options = []
				if (this.searchError !== message) {
					showError(message)
				}
				this.searchError = message
			} finally {
				this.loading = false
			}
		}, 300),

		onBlur() {
			this.searchError = null
		},

		/**
		 * Map the chosen options to their ids and emit them.
		 *
		 * @param {Array<{id: string}>|null} options the chosen options
		 */
		onSelect(options) {
			this.$emit('update:modelValue', (options ?? []).map((option) => option.id))
		},
	},
}
</script>

<style scoped lang="scss">
.crm-group-picker {
	:deep(.v-select.select) {
		display: block;
		width: 100%;
		min-width: 0;
	}

	&__hint {
		padding-block: 4px;
		padding-inline: var(--border-radius-element);
		color: var(--color-text-maxcontrast);
	}
}
</style>
