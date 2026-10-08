<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiCheck } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

const open = defineModel<boolean>('open', { required: true })

defineProps<{
	name: string
	canSubmit: boolean
	loading?: boolean
	size?: 'small' | 'normal'
}>()

const emit = defineEmits<{
	submit: []
}>()

</script>

<template>
	<NcDialog
		v-model:open="open"
		contentClasses="crm-dialog"
		isForm
		:name="name"
		:size="size ?? 'small'"
		@submit="canSubmit && !loading && emit('submit')">
		<slot />

		<template #actions>
			<NcButton :disabled="loading" @click="open = false">
				{{ t('calendar_resource_management', 'Cancel') }}
			</NcButton>
			<NcButton
				:disabled="!canSubmit || loading"
				type="submit"
				variant="primary">
				<template #icon>
					<NcLoadingIcon v-if="loading" />
					<NcIconSvgWrapper v-else :path="mdiCheck" />
				</template>
				{{ t('calendar_resource_management', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style lang="scss">
// Not scoped: the dialog is mounted outside of this component
.crm-dialog {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
}
</style>
