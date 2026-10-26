<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="crm-user-picker" :class="{ 'crm-user-picker--error': error }">
		<NcSelectUsers
			:inputId="inputId"
			:inputLabel="required ? label + ' *' : label"
			:labelOutside="labelOutside"
			:modelValue="selected"
			:options="options"
			:loading="loading"
			:required="required"
			:placeholder="t('calendar_resource_management', 'Search for a user …')"
			@search="onSearch"
			@search:blur="onBlur"
			@update:modelValue="onSelect" />
		<FieldError v-if="error" :message="error" />
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import debounce from 'debounce'
import { useId } from 'vue'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import FieldError from './FieldError.vue'
import { searchUsers } from '../../services/api.js'

/**
 * Shape a raw user record into an NcSelectUsers option.
 *
 * @param {object} user the raw user (id + optional displayname/email)
 * @return {object} the option
 */
function toOption(user) {
	const id = String(user.id ?? '')
	return {
		id,
		user: id,
		displayName: user.displayname || id,
		subname: user.email || '',
		isNoUser: false,
	}
}

export default {
	name: 'UserPickerField',

	components: {
		FieldError,
		NcSelectUsers,
	},

	props: {
		modelValue: {
			type: String,
			default: '',
		},

		label: {
			type: String,
			required: true,
		},

		required: {
			type: Boolean,
			default: false,
		},

		labelOutside: {
			type: Boolean,
			default: false,
		},

		inputId: {
			type: String,
			default: () => useId(),
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
			options: [],
			loading: false,
			searchError: null,
			selected: null,
		}
	},

	watch: {
		modelValue: {
			immediate: true,
			handler(value) {
				if (!value) {
					this.selected = null
					return
				}
				if (!this.selected || this.selected.id !== value) {
					this.selected = toOption({ id: value })
				}
			},
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
			if (!term) {
				this.options = []
				return
			}
			this.loading = true
			try {
				const users = await searchUsers(term)
				this.options = users.map(toOption)
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
		 * Map the chosen option to its id string and emit it.
		 *
		 * @param {object|null} option the chosen option
		 */
		onSelect(option) {
			this.selected = option
			this.$emit('update:modelValue', option ? option.id : '')
		},
	},
}
</script>

<style scoped lang="scss">
.crm-user-picker {
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
