<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<nav
		class="crm-pager"
		:aria-label="t('calendar_resource_management', 'Pagination')">
		<p class="crm-pager__counter">
			{{ counter }}
		</p>

		<div class="crm-pager__pages">
			<NcButton
				variant="tertiary"
				:disabled="loading || page <= 1"
				:ariaLabel="t('calendar_resource_management', 'Previous page')"
				@click="go(page - 1)">
				<template #icon>
					<ChevronLeftIcon :size="20" />
				</template>
			</NcButton>

			<template v-for="item in items" :key="item.key">
				<span v-if="item.page === null" class="crm-pager__gap" aria-hidden="true">
					…
				</span>
				<NcButton
					v-else
					class="crm-pager__page"
					:variant="item.page === page ? 'primary' : 'tertiary'"
					:aria-current="item.page === page ? 'page' : undefined"
					:ariaLabel="t('calendar_resource_management', 'Page {page}', { page: item.page })"
					@click="go(item.page)">
					{{ item.page }}
				</NcButton>
			</template>

			<NcButton
				variant="tertiary"
				:disabled="loading || page >= pages"
				:ariaLabel="t('calendar_resource_management', 'Next page')"
				@click="go(page + 1)">
				<template #icon>
					<ChevronRightIcon :size="20" />
				</template>
			</NcButton>
		</div>
	</nav>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import ChevronLeftIcon from 'vue-material-design-icons/ChevronLeft.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'

// Consecutive page numbers shown around the current page.
const WINDOW_SIZE = 4

export default {
	name: 'EntityPager',

	components: {
		ChevronLeftIcon,
		ChevronRightIcon,
		NcButton,
	},

	props: {
		page: {
			type: Number,
			default: 1,
		},

		/** How many pages the current result set has. */
		pages: {
			type: Number,
			default: 1,
		},

		/** How many records the current result set holds in total. */
		total: {
			type: Number,
			default: 0,
		},

		/** How many rows a page holds. */
		limit: {
			type: Number,
			default: 20,
		},

		loading: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['update:page'],

	computed: {
		firstRow() {
			return (this.page - 1) * this.limit + 1
		},

		lastRow() {
			return Math.min(this.page * this.limit, this.total)
		},

		counter() {
			return t('calendar_resource_management', 'Rows {start}–{end} of {total}', {
				start: this.firstRow,
				end: this.lastRow,
				total: this.total,
			})
		},

		/**
		 * Pager entries in order
		 *
		 * @return {Array<{key: string, page: ?number}>} the pager entries
		 */
		items() {
			const start = Math.min(
				Math.max(this.page - 1, 1),
				Math.max(this.pages - WINDOW_SIZE + 1, 1),
			)
			const end = Math.min(start + WINDOW_SIZE - 1, this.pages)

			const wanted = new Set([1])
			for (let number = start; number <= end; number++) {
				wanted.add(number)
			}
			wanted.add(this.pages)

			const items = []
			let previous = 0
			for (const number of [...wanted]) {
				const gap = number - previous
				if (gap === 2) {
					items.push({ key: 'page-' + (previous + 1), page: previous + 1 })
				} else if (gap > 2) {
					items.push({ key: 'gap-' + number, page: null })
				}
				items.push({ key: 'page-' + number, page: number })
				previous = number
			}
			return items
		},
	},

	methods: {
		t,

		/**
		 * @param {number} page target page
		 */
		go(page) {
			if (page < 1 || page > this.pages || page === this.page) {
				return
			}
			this.$emit('update:page', page)
		},
	},
}
</script>

<style scoped lang="scss">
.crm-pager {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	margin-top: 12px;

	&__counter {
		margin: 0;
		color: var(--color-text-maxcontrast);
	}

	&__pages {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 4px;
	}

	&__page {
		min-width: 44px;
	}

	&__gap {
		padding: 0 4px;
		color: var(--color-text-maxcontrast);
	}
}
</style>
