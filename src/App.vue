<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		:name="t('calendar_resource_management', 'Calendar resources')">
		<div class="crm__tabs" role="tablist">
			<button
				v-for="tab in tabOrder"
				:key="tab"
				role="tab"
				class="crm__tab"
				:class="{ 'crm__tab--active': tab === activeType }"
				:aria-selected="tab === activeType ? 'true' : 'false'"
				@click="switchTab(tab)">
				{{ entities[tab].label }}
			</button>
		</div>

		<EntitySection :key="activeType" :entityKey="activeType" />
	</NcSettingsSection>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import EntitySection from './components/EntitySection.vue'
import { ENTITIES, TAB_ORDER } from './services/schema.js'

export default {
	name: 'App',

	components: {
		EntitySection,
		NcSettingsSection,
	},

	props: {
		initialType: {
			type: String,
			default: 'buildings',
		},
	},

	data() {
		return {
			entities: ENTITIES,
			tabOrder: TAB_ORDER,
			activeType: TAB_ORDER.includes(this.initialType) ? this.initialType : 'buildings',
		}
	},

	methods: {
		t,

		/**
		 * @param {string} key the entity key
		 */
		switchTab(key) {
			if (key === this.activeType) {
				return
			}
			this.activeType = key
			const url = new URL(window.location.href)
			url.searchParams.set('type', key)
			window.history.replaceState({}, '', url)
		},
	},
}
</script>

<style scoped lang="scss">

#calendar-resource-admin .settings-section {
	width: max(400px,100% - var(--default-grid-baseline) * 7 * 2);
}

.crm {
	&__tabs {
		display: flex;
		flex-wrap: wrap;
		gap: 4px;
		border-bottom: 1px solid var(--color-border);
		margin-bottom: 16px;
	}

	&__tab {
		background: transparent;
		border: none;
		border-bottom: 2px solid transparent;
		border-radius: 0;
		padding: 8px 16px;
		color: var(--color-text-maxcontrast);
		cursor: pointer;

		&:hover,
		&:focus-visible {
			color: var(--color-main-text);
			background-color: var(--color-background-hover);
		}

		&--active {
			color: var(--color-main-text);
			border-bottom-color: var(--color-primary-element);
			font-weight: bold;
		}
	}
}
</style>
