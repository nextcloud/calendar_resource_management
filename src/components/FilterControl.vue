<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="crm-filter-control">
		<label class="crm-filter-control__label" :for="inputId">
			{{ filter.label }}
		</label>

		<div v-if="filter.type === 'range'" class="crm-filter-control__range">
			<NcTextField
				:id="inputId"
				:modelValue="rangeBound('min')"
				type="number"
				labelOutside
				:placeholder="t('calendar_resource_management', 'from')"
				:aria-label="t('calendar_resource_management', '{field} from', { field: filter.label })"
				@update:modelValue="setBound('min', $event)" />
			<NcTextField
				:modelValue="rangeBound('max')"
				type="number"
				labelOutside
				:placeholder="t('calendar_resource_management', 'to')"
				:aria-label="t('calendar_resource_management', '{field} to', { field: filter.label })"
				@update:modelValue="setBound('max', $event)" />
		</div>

		<UserPickerField
			v-else-if="filter.type === 'user'"
			:inputId="inputId"
			:modelValue="textValue"
			:label="filter.label"
			labelOutside
			@update:modelValue="$emit('update:modelValue', $event)" />

		<SelectField
			v-else-if="isSelect"
			:inputId="inputId"
			:modelValue="modelValue"
			:options="selectOptions"
			:label="filter.label"
			:placeholder="t('calendar_resource_management', 'Any')"
			labelOutside
			@update:modelValue="$emit('update:modelValue', $event)" />

		<NcTextField
			v-else
			:id="inputId"
			:modelValue="textValue"
			:type="filter.type === 'number' ? 'number' : 'text'"
			labelOutside
			@update:modelValue="$emit('update:modelValue', $event)" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { useId } from 'vue'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import SelectField from './fields/SelectField.vue'
import UserPickerField from './fields/UserPickerField.vue'

export default {
	name: 'FilterControl',

	components: {
		NcTextField,
		SelectField,
		UserPickerField,
	},

	props: {
		/** The filter descriptor (from the entity's `filters` config). */
		filter: {
			type: Object,
			required: true,
		},

		/**
		 * `{ min, max }` for a range filter, else a scalar, or null.
		 * `String` must precede `Boolean`, or Vue casts '' to `true`.
		 */
		modelValue: {
			// eslint-disable-next-line vue/prefer-prop-type-boolean-first
			type: [String, Boolean, Number, Object],
			default: null,
		},

	},

	emits: ['update:modelValue'],

	setup() {
		return {
			inputId: useId(),
		}
	},

	computed: {
		isSelect() {
			return this.filter.type === 'select' || this.isBool
		},

		/** Whether this filter collects a boolean, which supplies its own options. */
		isBool() {
			return this.filter.type === 'bool'
		},

		/** The select's options; a boolean filter supplies Yes/No itself. */
		selectOptions() {
			if (this.isBool) {
				return [
					{ id: true, label: t('calendar_resource_management', 'Yes') },
					{ id: false, label: t('calendar_resource_management', 'No') },
				]
			}

			return this.filter.options
		},

		/** The value bound to a free-text input, which cannot be null. */
		textValue() {
			return this.modelValue ?? ''
		},

	},

	methods: {
		t,

		/**
		 * Read one bound of a range filter.
		 *
		 * @param {string} bound either 'min' or 'max'
		 * @return {string} the bound's value, or an empty string when unset
		 */
		rangeBound(bound) {
			return this.modelValue?.[bound] ?? ''
		},

		/**
		 * Update one bound of a range filter, leaving the other untouched.
		 *
		 * @param {string} bound either 'min' or 'max'
		 * @param {string} value the new bound
		 */
		setBound(bound, value) {
			this.$emit('update:modelValue', { ...this.modelValue, [bound]: value })
		},
	},
}
</script>

<style scoped lang="scss">
.crm-filter-control {
	&__label {
		display: block;
		margin-bottom: 2px;
	}

	&__range {
		display: flex;
		gap: 8px;

		> * {
			flex: 1 1 0;
			min-width: 0;
		}
	}
}
</style>
