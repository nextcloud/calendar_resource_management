<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="crm-table-wrapper">
		<NcLoadingIcon
			v-if="loading"
			class="crm-table-wrapper__loading"
			:size="32"
			:name="t('calendar_resource_management', 'Loading …')" />

		<NcEmptyContent
			v-else-if="loadError"
			:name="t('calendar_resource_management', 'Could not load entries')"
			:description="loadError">
			<template #icon>
				<AlertCircleIcon :size="20" />
			</template>
		</NcEmptyContent>

		<NcEmptyContent
			v-else-if="records.length === 0"
			:name="emptyName">
			<template #icon>
				<TableIcon :size="20" />
			</template>
		</NcEmptyContent>

		<table v-else class="crm-table">
			<thead>
				<tr>
					<th v-for="col in entity.columns" :key="col.key">
						{{ col.label }}
					</th>
					<th class="crm-table__actions-head">
						{{ t('calendar_resource_management', 'Actions') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="row in formattedRows" :key="row.id">
					<td v-for="cell in row.cells" :key="cell.key">
						<span class="crm-table__cell-text" :title="cell.text">
							{{ cell.text }}
						</span>
					</td>
					<td class="crm-table__actions">
						<NcButton
							variant="secondary"
							@click="$emit('edit', row.record)">
							{{ t('calendar_resource_management', 'Edit') }}
						</NcButton>
						<NcButton
							variant="error"
							@click="$emit('delete', row.record)">
							{{ t('calendar_resource_management', 'Delete') }}
						</NcButton>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'
import TableIcon from 'vue-material-design-icons/Table.vue'

export default {
	name: 'EntityTable',

	components: {
		AlertCircleIcon,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		TableIcon,
	},

	props: {
		entity: {
			type: Object,
			required: true,
		},

		records: {
			type: Array,
			default: () => [],
		},

		loading: {
			type: Boolean,
			default: false,
		},

		loadError: {
			type: String,
			default: null,
		},

		/** Whether filters are narrowing the list, which changes the empty-state. */
		filtered: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['edit', 'delete'],

	computed: {
		emptyName() {
			return this.filtered
				? t('calendar_resource_management', 'No entries match the current filters.')
				: t('calendar_resource_management', 'No entries yet.')
		},

		/** Rows with every cell pre-formatted, cached per records. */
		formattedRows() {
			const columns = this.entity.columns ?? []
			return this.records.map((record) => ({
				id: record.id,
				record,
				cells: columns.map((col) => ({
					key: col.key,
					text: this.formatCell(record[col.key]),
				})),
			}))
		},
	},

	methods: {
		t,

		/**
		 * Render a value for display in a table cell.
		 *
		 * @param {*} value the raw value
		 * @return {string} a human-readable representation
		 */
		formatCell(value) {
			if (value === undefined || value === null || value === '') {
				return ''
			}
			if (typeof value === 'boolean') {
				return value ? t('calendar_resource_management', 'Yes') : t('calendar_resource_management', 'No')
			}
			return String(value)
		},
	},
}
</script>

<style scoped lang="scss">
.crm-table-wrapper {
	margin-top: 12px;
	overflow-x: auto;

	&__loading {
		margin: 24px 0;
	}
}

.crm-table {
	width: 100%;
	border-collapse: collapse;

	tr {
		border-bottom: 1px solid var(--color-border);
	}

	th,
	td {
		padding: 8px 12px;
		text-align: start;
		color: var(--color-main-text);
		white-space: nowrap;
	}

	th {
		font-weight: bold;
		color: var(--color-text-maxcontrast);
	}

	&__cell-text {
		display: -webkit-box;
		max-width: 400px;
		overflow: hidden;
		overflow-wrap: anywhere;
		white-space: normal;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 3;
		line-clamp: 3;
	}

	&__actions-head {
		text-align: end;
	}

	&__actions {
		display: flex;
		gap: 8px;
		justify-content: flex-end;
	}
}
</style>
