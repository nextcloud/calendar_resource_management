<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="crm__panel" role="tabpanel">
		<div class="crm__panel-header">
			<h3 class="crm__panel-title">
				{{ entity.label }}
			</h3>
			<NcButton variant="primary" @click="openCreate">
				<template #icon>
					<PlusIcon :size="20" />
				</template>
				{{ t('calendar_resource_management', 'Add {name}', { name: entity.singular }) }}
			</NcButton>
		</div>

		<EntityFilterPanel
			:entity="entity"
			:filters="filters"
			:activeFilterCount="activeFilterCount"
			:loading="loading"
			@update:filters="setFilters"
			@search="applyFilters"
			@clear="clearFilters" />

		<EntityTable
			:entity="entity"
			:records="records"
			:loading="loading"
			:loadError="loadError"
			:filtered="activeFilterCount > 0"
			@edit="openEdit"
			@delete="askDelete" />

		<EntityPager
			:page="page"
			:pages="pages"
			:total="total"
			:limit="limit"
			:loading="loading"
			@update:page="goToPage" />

		<EntityFormModal
			v-if="showForm"
			ref="form"
			:entity="entity"
			:record="editingRecord"
			@close="closeForm"
			@saved="onSaved" />

		<NcDialog
			v-model:open="deleteDialogOpen"
			:name="t('calendar_resource_management', 'Confirm deletion')"
			:message="deleteMessage"
			:buttons="deleteButtons" />
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import EntityFilterPanel from './EntityFilterPanel.vue'
import EntityFormModal from './EntityFormModal.vue'
import EntityPager from './EntityPager.vue'
import EntityTable from './EntityTable.vue'
import { create, destroy, fetchPage, update } from '../services/api.js'
import { ENTITIES, PAGE_SIZE } from '../services/schema.js'
import { isSet } from '../utils.js'

export default {
	name: 'EntitySection',

	components: {
		EntityFilterPanel,
		EntityFormModal,
		EntityPager,
		EntityTable,
		NcButton,
		NcDialog,
		PlusIcon,
	},

	props: {
		entityKey: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			records: [],
			loading: false,
			// Pager state, as reported by the server.
			page: 1,
			pages: 1,
			total: 0,
			limit: PAGE_SIZE,
			// The list-load error message
			loadError: null,
			// Create/edit modal state.
			showForm: false,
			editingRecord: null,
			// Panel filter values by key (`{ min, max }` for ranges); sent only on search.
			filters: {},
			// The filters the loaded list reflects.
			appliedFilters: {},
			// The record awaiting delete confirmation, or null.
			pendingDelete: null,
			deleteDialogOpen: false,
		}
	},

	computed: {
		/** The active entity's configuration (from the backend schema). */
		entity() {
			return ENTITIES[this.entityKey]
		},

		filterParams() {
			const params = {}
			for (const filter of this.entity.filters ?? []) {
				const value = this.appliedFilters[filter.key]

				if (filter.type === 'range') {
					const { min, max } = value ?? {}
					if (isSet(min)) {
						params[filter.key + 'Min'] = min
					}
					if (isSet(max)) {
						params[filter.key + 'Max'] = max
					}
					continue
				}

				params[filter.key] = value
			}
			return params
		},

		activeFilterCount() {
			let count = 0
			for (const filter of this.entity.filters ?? []) {
				const value = this.appliedFilters[filter.key]

				if (filter.type === 'range') {
					const { min, max } = value ?? {}
					if (isSet(min) || isSet(max)) {
						count++
					}
					continue
				}

				if (isSet(value)) {
					count++
				}
			}
			return count
		},

		deleteMessage() {
			if (!this.pendingDelete) {
				return ''
			}
			const name = this.pendingDelete.displayName || ('#' + this.pendingDelete.id)
			let message = t('calendar_resource_management', 'Delete "{name}"?', { name })
			if (this.entity.cascades) {
				message += '\n\n' + t('calendar_resource_management', 'This will also delete all child entries.')
			}
			return message
		},

		deleteButtons() {
			return [
				{
					label: t('calendar_resource_management', 'Cancel'),
				},
				{
					label: t('calendar_resource_management', 'Delete'),
					variant: 'error',
					callback: () => this.confirmDelete(),
				},
			]
		},
	},

	created() {
		// guard against out-of-order responses.
		this.requestSeq = 0
	},

	async mounted() {
		await this.refresh()
	},

	methods: {
		t,

		/**
		 * Load the record list (on mount and after a change).
		 *
		 * @return {Promise<void>}
		 */
		async refresh() {
			await this.reload()
		},

		/**
		 * @return {Promise<void>}
		 */
		async reload() {
			this.loading = true
			this.loadError = null
			const seq = ++this.requestSeq

			try {
				const result = await fetchPage(this.entity.path, this.filterParams, { page: this.page })
				if (seq !== this.requestSeq) {
					return
				}
				this.records = result.items
				this.total = result.total
				this.pages = result.pages
				this.limit = result.limit
				this.page = result.page
			} catch (error) {
				if (seq !== this.requestSeq) {
					return
				}
				this.loadError = error.message
				showError(error.message)
			} finally {
				if (seq === this.requestSeq) {
					this.loading = false
				}
			}
		},

		/**
		 * Replace the panel's filter state without reloading.
		 *
		 * @param {object} filters the new filter values
		 */
		setFilters(filters) {
			this.filters = filters
		},

		/**
		 * Apply the panel's filters and reload from page 1.
		 *
		 * @return {Promise<void>}
		 */
		applyFilters() {
			this.appliedFilters = { ...this.filters }
			this.page = 1
			return this.reload()
		},

		/**
		 * Drop every filter and reload the full list.
		 *
		 * @return {Promise<void>}
		 */
		clearFilters() {
			this.filters = {}
			this.appliedFilters = {}
			this.page = 1
			return this.reload()
		},

		/**
		 * Move to another page of the current result set.
		 *
		 * @param {number} page the 1-based page to load
		 * @return {Promise<void>}
		 */
		goToPage(page) {
			this.page = page
			return this.reload()
		},

		openCreate() {
			this.editingRecord = null
			this.showForm = true
		},

		/**
		 * Open the edit modal for a record.
		 *
		 * @param {object} record the record to edit
		 */
		openEdit(record) {
			this.editingRecord = record
			this.showForm = true
		},

		closeForm() {
			this.showForm = false
			this.editingRecord = null
		},

		/**
		 * Persist a create/update emitted by the form modal.
		 *
		 * @param {object} event the payload event
		 * @param {boolean} event.isEdit whether this is an update
		 * @param {number|null} event.id the record id (for updates)
		 * @param {object} event.payload the request body
		 */
		async onSaved({ isEdit, id, payload }) {
			try {
				if (isEdit) {
					await update(this.entity.path, id, payload)
					showSuccess(t('calendar_resource_management', 'Updated {name}', { name: this.entity.singular }))
				} else {
					await create(this.entity.path, payload)
					showSuccess(t('calendar_resource_management', 'Created {name}', { name: this.entity.singular }))
				}
				this.closeForm()
				await this.refresh()
			} catch (error) {
				// Prefer the backend's OCS validation message.
				showError(error.response?.data?.ocs?.meta?.message || error.message)
				// Re-enable the modal's submit button so the user can retry.
				this.$refs.form?.reset()
			}
		},

		/**
		 * Open the confirmation dialog for a record (from the table's delete).
		 *
		 * @param {object} record the record the user asked to delete
		 */
		askDelete(record) {
			this.pendingDelete = record
			this.deleteDialogOpen = true
		},

		/**
		 * Delete the pending record; cleared first so it can't be deleted twice.
		 *
		 * @return {Promise<void>}
		 */
		async confirmDelete() {
			const record = this.pendingDelete
			this.deleteDialogOpen = false
			this.pendingDelete = null
			if (!record) {
				return
			}

			try {
				await destroy(this.entity.path, record.id)
				showSuccess(t('calendar_resource_management', 'Deleted {name}', { name: this.entity.singular }))
				await this.refresh()

				// Step back if the current page is now empty.
				if (this.records.length === 0 && this.page > 1) {
					await this.goToPage(this.page - 1)
				}
			} catch (error) {
				showError(error.message)
			}
		},
	},
}
</script>

<style scoped lang="scss">
.crm__panel-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 8px;
}

.crm__panel-title {
	margin: 0;
}
</style>
