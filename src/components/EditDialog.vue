<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'

const props = defineProps<{
	/** Heading of the dialog */
	name: string
	open: boolean
	canSave: boolean
	/** Whether the save request is running */
	loading?: boolean
}>()

const emit = defineEmits<{
	save: []
	close: []
}>()

const buttons = computed(() => [
	{
		label: t('calendar_resource_management', 'Cancel'),
		callback: (): void => emit('close'),
	},
	{
		label: t('calendar_resource_management', 'Save'),
		variant: 'primary' as const,
		disabled: !props.canSave || props.loading,
		callback: (): false => {
			emit('save')
			// The parent closes the dialog once the entity was saved
			return false
		},
	},
])

/**
 * Tell the parent when the dialog is dismissed.
 *
 * @param open Whether the dialog is open
 */
function onToggle(open: boolean): void {
	if (!open) {
		emit('close')
	}
}
</script>

<template>
	<NcDialog
		:buttons="buttons"
		:name="name"
		:open="open"
		size="normal"
		@update:open="onToggle">
		<div class="crm-edit">
			<slot />
		</div>
	</NcDialog>
</template>

<style lang="scss" scoped>
.crm-edit {
	:deep(.crm-field) {
		margin-block-end: calc(2 * var(--default-grid-baseline));
		width: 100%;
	}

	:deep(.crm-equipment) legend {
		font-weight: bold;
	}
}
</style>
