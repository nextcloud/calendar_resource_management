<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="crm-filters">
		<div class="crm-filters__header">
			<NcButton
				variant="tertiary"
				:aria-expanded="expanded ? 'true' : 'false'"
				@click="expanded = !expanded">
				<template #icon>
					<ChevronDownIcon v-if="expanded" :size="20" />
					<ChevronRightIcon v-else :size="20" />
				</template>
				{{ toggleLabel }}
			</NcButton>
		</div>

		<div v-if="expanded">
			<div class="crm-filters__grid" @keyup.enter="$emit('search')">
				<FilterControl
					v-for="filter in entity.filters"
					:key="filter.key"
					:class="{ 'crm-filters__cell--wide': filter.type === 'range' }"
					:filter="filter"
					:modelValue="filters[filter.key] ?? null"
					@update:modelValue="setFilter(filter.key, $event)" />
			</div>

			<div class="crm-filters__actions">
				<NcButton
					variant="tertiary"
					:disabled="loading"
					@click="$emit('clear')">
					{{ t('calendar_resource_management', 'Clear all') }}
				</NcButton>

				<NcButton
					variant="primary"
					:disabled="loading"
					@click="$emit('search')">
					<template #icon>
						<MagnifyIcon :size="20" />
					</template>
					{{ t('calendar_resource_management', 'Search') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import MagnifyIcon from 'vue-material-design-icons/Magnify.vue'
import FilterControl from './FilterControl.vue'

export default {
	name: 'EntityFilterPanel',

	components: {
		ChevronDownIcon,
		ChevronRightIcon,
		FilterControl,
		MagnifyIcon,
		NcButton,
	},

	props: {
		/** The active entity's configuration (from the backend schema). */
		entity: {
			type: Object,
			required: true,
		},

		/** The current filter values, keyed by filter key. */
		filters: {
			type: Object,
			default: () => ({}),
		},

		/** How many filters are currently narrowing the list. */
		activeFilterCount: {
			type: Number,
			default: 0,
		},

		loading: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['update:filters', 'search', 'clear'],

	data() {
		return {
			expanded: false,
		}
	},

	computed: {
		toggleLabel() {
			if (this.activeFilterCount === 0) {
				return t('calendar_resource_management', 'Filters')
			}
			return t('calendar_resource_management', 'Filters ({count})', { count: this.activeFilterCount })
		},
	},

	methods: {
		t,

		/**
		 * Emit the filter state with one filter changed.
		 *
		 * @param {string} key the filter key
		 * @param {*} value the new value
		 */
		setFilter(key, value) {
			this.$emit('update:filters', { ...this.filters, [key]: value })
		},
	},
}
</script>

<style scoped lang="scss">
.crm-filters {
	border-bottom: 1px solid var(--color-border);
	padding-bottom: 8px;

	&__header {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	&__actions {
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 12px;
		margin-bottom: 4px;
	}

	&__grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
		gap: 12px;
		margin: 8px 0 4px;
		align-items: start;
	}

	// Ranges need two columns.
	&__cell--wide {
		grid-column: span 2;
	}

	@media (max-width: 800px) {
		&__cell--wide {
			grid-column: span 1;
		}
	}
}
</style>
