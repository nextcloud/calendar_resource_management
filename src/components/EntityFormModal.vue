<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal
		size="normal"
		:name="title"
		@close="$emit('close')">
		<form class="crm-form" @submit.prevent="onSubmit">
			<h3 class="crm-form__title">
				{{ title }}
			</h3>

			<template v-for="field in entity.fields" :key="field.key">
				<NcCheckboxRadioSwitch
					v-if="field.type === 'bool'"
					:modelValue="values[field.key]"
					@update:modelValue="setValue(field, $event)">
					{{ field.label }}
				</NcCheckboxRadioSwitch>

				<NcTextArea
					v-else-if="field.type === 'textarea'"
					:modelValue="values[field.key]"
					:label="labelFor(field)"
					:error="hasError(field.key)"
					:helperText="helperText(field.key)"
					@update:modelValue="setValue(field, $event)" />

				<UserPickerField
					v-else-if="field.type === 'user'"
					:modelValue="values[field.key]"
					:label="field.label"
					:required="field.required"
					:error="helperText(field.key)"
					@update:modelValue="setValue(field, $event)" />

				<SelectField
					v-else-if="isSelect(field)"
					:modelValue="values[field.key]"
					:options="optionsFor(field)"
					:label="field.label"
					:required="field.required"
					:taggable="field.type === 'combobox'"
					:disabled="isWaiting(field)"
					:error="helperText(field.key)"
					@update:modelValue="onSelect(field, $event)" />

				<NcTextField
					v-else
					:modelValue="values[field.key]"
					:type="field.type === 'number' || field.type === 'email' ? field.type : 'text'"
					:label="labelFor(field)"
					:error="hasError(field.key)"
					:helperText="helperText(field.key) || (field.type === 'email' ? autoEmailHint : '')"
					@update:modelValue="setValue(field, $event)" />

				<GroupPickerField
					v-if="field.key === 'restricted' && values.restricted"
					:modelValue="values.groupRestrictions"
					:label="t('calendar_resource_management', 'Restricted to groups')"
					@update:modelValue="values.groupRestrictions = $event ?? []" />
			</template>

			<div class="crm-form__actions">
				<NcButton
					variant="tertiary"
					:disabled="submitting"
					@click="$emit('close')">
					{{ t('calendar_resource_management', 'Cancel') }}
				</NcButton>
				<NcButton
					variant="primary"
					type="submit"
					:disabled="submitting">
					{{ t('calendar_resource_management', 'Save') }}
				</NcButton>
			</div>
		</form>
	</NcModal>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import GroupPickerField from './fields/GroupPickerField.vue'
import SelectField from './fields/SelectField.vue'
import UserPickerField from './fields/UserPickerField.vue'
import { fetchOptions } from '../services/api.js'
import { isSet } from '../utils.js'
import FieldValidation from '../validation/FieldValidation.js'

export default {
	name: 'EntityFormModal',

	components: {
		GroupPickerField,
		NcButton,
		NcCheckboxRadioSwitch,
		NcModal,
		NcTextArea,
		NcTextField,
		SelectField,
		UserPickerField,
	},

	props: {
		/** The entity's schema; its `fields` is a list in display order. */
		entity: {
			type: Object,
			required: true,
		},

		/** The record being edited, or null to create a new one. */
		record: {
			type: Object,
			default: null,
		},
	},

	emits: ['close', 'saved'],

	data() {
		return {
			values: {},
			errors: {},
			submitting: false,
		}
	},

	computed: {
		isEdit() {
			return this.record !== null
		},

		title() {
			return this.isEdit
				? t('calendar_resource_management', 'Edit {name}', { name: this.entity.singular })
				: t('calendar_resource_management', 'Add {name}', { name: this.entity.singular })
		},

		/** Field key -> the parent field that cascades into it. */
		parents() {
			const parents = {}
			for (const field of this.entity.fields) {
				if (field.cascade) {
					parents[field.cascade] = field
				}
			}
			return parents
		},

		restrictable() {
			return this.fieldNamed('restricted')
		},

		autoEmailHint() {
			return t('calendar_resource_management', 'Leave empty to generate one automatically')
		},
	},

	created() {
		this.initValues()
		if (this.isEdit) {
			this.prefill()
		}
	},

	methods: {
		t,

		/**
		 * A field's empty value: null for selects, false for checkboxes, '' otherwise.
		 *
		 * @param {object} field the field descriptor
		 * @return {string|boolean|null} the empty value
		 */
		emptyValue(field) {
			if (field.type === 'bool') {
				return false
			}
			if (field.type === 'select') {
				return null
			}
			return ''
		},

		/** Seed form values from the record or with empty values. */
		initValues() {
			const values = {}
			for (const field of this.entity.fields) {
				values[field.key] = this.record?.[field.key] ?? this.emptyValue(field)
			}
			if (this.restrictable) {
				values.groupRestrictions = [...(this.record?.groupRestrictions ?? [])]
			}
			this.values = values
		},

		/**
		 * Resolve transient cascading fields (e.g. a room's building) from the dependent field's option.
		 *
		 * @return {Promise<void>}
		 */
		async prefill() {
			for (const field of this.entity.fields) {
				if (!field.transient || !field.cascade) {
					continue
				}
				const child = this.fieldNamed(field.cascade)
				const childValue = this.values[child.key]
				if (!isSet(childValue)) {
					continue
				}
				try {
					const options = await fetchOptions(child.options, { id: childValue })
					const option = options.find((item) => item.id === childValue)
					if (option) {
						this.values[field.key] = option[field.key] ?? this.emptyValue(field)
					}
				} catch (error) {
					showError(error.message)
				}
			}
		},

		/**
		 * One of the entity's form fields, by key.
		 *
		 * @param {string} key the field key
		 * @return {object|undefined} the field descriptor
		 */
		fieldNamed(key) {
			return this.entity.fields.find((field) => field.key === key)
		},

		/**
		 * Whether a field is a select or combobox.
		 *
		 * @param {object} field the field descriptor
		 * @return {boolean} true for a select or combobox
		 */
		isSelect(field) {
			return ['select', 'combobox'].includes(field.type)
		},

		/**
		 * A select's options source, narrowed by its cascade parent if any.
		 *
		 * @param {object} field the field descriptor
		 * @return {string|Array|null} the path or list, or null for none
		 */
		optionsFor(field) {
			const parent = this.parents[field.key]
			if (!parent) {
				return field.options ?? null
			}
			const value = this.values[parent.key]
			if (!isSet(value)) {
				return null
			}
			return field.options + '?' + parent.key + '=' + encodeURIComponent(value)
		},

		/**
		 * Whether a field waits on a parent field that has no value yet.
		 *
		 * @param {object} field the field descriptor
		 * @return {boolean} true while the field it depends on is unset
		 */
		isWaiting(field) {
			const parent = this.parents[field.key]
			return parent !== undefined && !isSet(this.values[parent.key])
		},

		/**
		 * The label with a required marker, for inputs without their own marker.
		 *
		 * @param {object} field the field descriptor
		 * @return {string} the display label
		 */
		labelFor(field) {
			return field.required ? field.label + ' *' : field.label
		},

		/**
		 * Whether a field currently has a validation error.
		 *
		 * @param {string} key the field key
		 * @return {boolean} true when the field is flagged as invalid
		 */
		hasError(key) {
			return Boolean(this.errors[key])
		},

		/**
		 * The helper text under a field: its validation message while invalid.
		 *
		 * @param {string} key the field key
		 * @return {string} the helper text, or '' when valid
		 */
		helperText(key) {
			return this.errors[key] || ''
		},

		/**
		 * Update a field value (null becomes its empty value) and clear its error.
		 *
		 * @param {object} field the field descriptor
		 * @param {*} value the new value
		 */
		setValue(field, value) {
			this.values[field.key] = value ?? this.emptyValue(field)
			if (this.errors[field.key]) {
				delete this.errors[field.key]
			}
		},

		/**
		 * Store a select's choice and clear its cascade dependent.
		 *
		 * @param {object} field the field descriptor
		 * @param {*} value the chosen value
		 */
		onSelect(field, value) {
			this.setValue(field, value)
			if (field.cascade) {
				this.setValue(this.fieldNamed(field.cascade), null)
			}
		},

		/** Validate, build the payload and emit it if valid. */
		onSubmit() {
			const validator = new FieldValidation()
			const payload = {}

			for (const field of this.entity.fields) {
				if (field.transient) {
					continue
				}

				// A blank number is sent as null, not 0.
				let raw = this.values[field.key]
				if (typeof raw === 'string') {
					raw = raw.trim()
				}
				if (field.type === 'number') {
					raw = raw === '' ? null : Number(raw)
				}

				validator.validate(field, raw)

				payload[field.key] = raw
			}

			// Unrestricted entities send no groups (the API refuses them).
			if (this.restrictable) {
				payload.groupRestrictions = payload.restricted ? this.values.groupRestrictions : []
			}

			const errors = validator.result()
			if (errors !== null) {
				this.errors = errors
				return
			}

			this.errors = {}
			this.submitting = true
			this.$emit('saved', {
				isEdit: this.isEdit,
				id: this.isEdit ? this.record.id : null,
				payload,
			})
		},

		/** Re-enable the submit button, e.g. when the parent's save failed. */
		reset() {
			this.submitting = false
		},
	},
}
</script>

<style scoped lang="scss">
.crm-form {
	display: flex;
	flex-direction: column;
	gap: 16px;
	padding: 20px;

	&__title {
		margin: 0;
	}

	&__actions {
		display: flex;
		justify-content: flex-end;
		gap: 8px;
		margin-top: 8px;
	}
}
</style>
