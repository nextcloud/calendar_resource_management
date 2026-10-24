<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="crm-select-field" :class="{ 'crm-select-field--error': error }">
		<NcSelect
			:inputId="inputId"
			:inputLabel="required ? label + ' *' : label"
			:labelOutside="labelOutside"
			:modelValue="selected"
			:options="optionList"
			:loading="loading"
			:disabled="disabled"
			:required="required"
			:taggable="taggable"
			:createOption="createOption"
			:clearable="!required"
			:placeholder="placeholder ?? defaultPlaceholder"
			label="label"
			@open="loadOptions"
			@update:modelValue="onSelect" />
		<FieldError v-if="error" :message="error" />
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { useId } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import FieldError from './FieldError.vue'
import { fetchOptions } from '../../services/api.js'
import { isSet } from '../../utils.js'

/**
 * @param {object|string|number|boolean} entry one raw entry
 * @return {{id: string|number|boolean, label: string}} the option
 */
function toOption(entry) {
	if (entry !== null && typeof entry === 'object') {
		return { id: entry.id, label: entry.label ?? entry.displayName ?? ('#' + entry.id) }
	}

	return { id: entry, label: String(entry) }
}

export default {
	name: 'SelectField',

	components: {
		FieldError,
		NcSelect,
	},

	props: {
		/**
		 * The chosen id, flag or tag text, or null.
		 * `String` must precede `Boolean`, or Vue casts '' to `true`.
		 */
		modelValue: {
			// eslint-disable-next-line vue/prefer-prop-type-boolean-first
			type: [String, Number, Boolean],
			default: null,
		},

		/** A path to fetch the options from, or the list itself. */
		options: {
			type: [String, Array],
			default: null,
		},

		label: {
			type: String,
			required: true,
		},

		required: {
			type: Boolean,
			default: false,
		},

		taggable: {
			type: Boolean,
			default: false,
		},

		disabled: {
			type: Boolean,
			default: false,
		},

		/** Whether the caller draws the label itself. */
		labelOutside: {
			type: Boolean,
			default: false,
		},

		inputId: {
			type: String,
			default: () => useId(),
		},

		/** Shown while nothing is chosen; the generic prompt when left out. */
		placeholder: {
			type: String,
			default: null,
		},

		/** A validation message shown under the field; none when empty. */
		error: {
			type: String,
			default: '',
		},
	},

	emits: ['update:modelValue'],

	data() {
		return {
			fetched: null,
			loading: false,
		}
	},

	computed: {
		defaultPlaceholder() {
			return this.taggable
				? t('calendar_resource_management', 'Type or pick a value …')
				: t('calendar_resource_management', '— choose —')
		},

		fetchesOptions() {
			return typeof this.options === 'string'
		},

		optionList() {
			const entries = (this.fetchesOptions ? this.fetched : this.options) ?? []

			return entries.map(toOption)
		},

		selected() {
			if (!isSet(this.modelValue)) {
				return null
			}

			const option = this.optionList.find((entry) => entry.id === this.modelValue)

			return option ?? (this.taggable ? toOption(this.modelValue) : null)
		},
	},

	watch: {
		options() {
			this.fetched = null
			this.loading = false
			if (!this.taggable && isSet(this.modelValue)) {
				this.loadOptions()
			}
		},

		modelValue(value) {
			if (!this.taggable && isSet(value)) {
				this.loadOptions()
			}
		},
	},

	mounted() {
		if (!this.taggable && isSet(this.modelValue)) {
			this.loadOptions()
		}
	},

	methods: {
		t,

		/**
		 * @return {Promise<void>}
		 */
		async loadOptions() {
			if (!this.fetchesOptions || this.fetched !== null || this.loading) {
				return
			}

			const source = this.options
			this.loading = true
			try {
				const entries = await fetchOptions(source)
				if (source === this.options) {
					this.fetched = entries
				}
			} catch (error) {
				showError(error.message)
			} finally {
				if (source === this.options) {
					this.loading = false
				}
			}
		},

		/**
		 * @param {string} value the typed value
		 * @return {{id: string, label: string}} the option
		 */
		createOption(value) {
			return { id: value, label: value }
		},

		/**
		 *
		 * @param {object|null} option the chosen option, or null when cleared
		 */
		onSelect(option) {
			if (!option) {
				this.$emit('update:modelValue', null)
				return
			}

			this.$emit('update:modelValue', option.id ?? null)
		},
	},
}
</script>

<style scoped lang="scss">
.crm-select-field {
	:deep(.v-select.select) {
		display: block;
		width: 100%;
	}

	&--error :deep(.v-select) {
		border-radius: var(--border-radius-large);
		box-shadow: 0 0 0 2px var(--color-error);
	}
}
</style>
