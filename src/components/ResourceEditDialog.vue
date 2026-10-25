<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, GroupOption, Resource, SelectOption, UpdateResource } from '../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import RestrictionFields from './RestrictionFields.vue'
import { optionId, selectOptions } from '../utils/entities.ts'

const props = defineProps<{
	/** The resource to edit, the dialog is closed without one */
	resource: Resource | null
	buildings: Building[]
	loading?: boolean
}>()

const emit = defineEmits<{
	save: [id: number, resource: UpdateResource]
	close: []
}>()

const name = ref('')
const email = ref('')
const resourceType = ref('')
const building = ref<SelectOption | null>(null)
const restricted = ref(false)
const restrictionGroups = ref<GroupOption[]>([])

const buildingOptions = computed(() => selectOptions(props.buildings))

// Restricted without groups, the server has no booking calendar for it
const hidden = computed(() => props.resource !== null && props.resource.restricted && props.resource.restrictionGroups.length === 0)

const canSubmit = computed(() => name.value.trim() !== '' && email.value.trim() !== '' && optionId(building.value) !== null
	// Hiding deletes the booking calendar, only the occ command may do that
	&& (!restricted.value || restrictionGroups.value.length > 0 || hidden.value))

const buttons = computed(() => [
	{
		label: t('calendar_resource_management', 'Cancel'),
		callback: (): void => emit('close'),
	},
	{
		label: t('calendar_resource_management', 'Save'),
		variant: 'primary' as const,
		disabled: !canSubmit.value || props.loading,
		callback: (): false => {
			submit()
			// The parent closes the dialog once the resource was saved
			return false
		},
	},
])

watch(() => props.resource, (resource) => {
	if (!resource) {
		return
	}

	const resourceBuilding = props.buildings.find((candidate) => candidate.id === resource.buildingId)
	name.value = resource.name
	email.value = resource.email
	resourceType.value = resource.resourceType
	building.value = resourceBuilding ? { id: resourceBuilding.id, label: resourceBuilding.name } : null
	restricted.value = resource.restricted
	restrictionGroups.value = resource.restrictionGroups.map((group) => ({ id: group.id, label: group.displayName }))
}, { immediate: true })

/**
 * Hand the changed resource over to the parent.
 */
function submit(): void {
	if (!props.resource) {
		return
	}

	emit('save', props.resource.id, {
		name: name.value.trim(),
		email: email.value.trim(),
		resourceType: resourceType.value.trim(),
		buildingId: optionId(building.value),
		restricted: restricted.value,
		restrictionGroupIds: restrictionGroups.value.map((group) => group.id),
	})
}

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
		:name="t('calendar_resource_management', 'Edit resource {name}', { name: resource?.name ?? '' })"
		:open="resource !== null"
		size="normal"
		@update:open="onToggle">
		<div class="crm-edit">
			<NcTextField
				v-model="name"
				class="crm-field"
				:label="t('calendar_resource_management', 'Resource name')"
				:placeholder="t('calendar_resource_management', 'Required')" />

			<NcTextField
				v-model="email"
				class="crm-field"
				:label="t('calendar_resource_management', 'Email')"
				:placeholder="t('calendar_resource_management', 'Required')"
				type="email" />

			<NcTextField
				v-model="resourceType"
				class="crm-field"
				:label="t('calendar_resource_management', 'Resource type')"
				:placeholder="t('calendar_resource_management', 'e.g. projector')" />

			<NcSelect
				v-model="building"
				class="crm-field"
				:clearable="false"
				:inputLabel="t('calendar_resource_management', 'Building')"
				:options="buildingOptions"
				:placeholder="t('calendar_resource_management', 'Please select a building')" />

			<RestrictionFields
				v-model:groups="restrictionGroups"
				v-model:restricted="restricted"
				:hidden="hidden" />
		</div>
	</NcDialog>
</template>

<style lang="scss" scoped>
.crm-edit :deep(.crm-field) {
	margin-block-end: calc(2 * var(--default-grid-baseline));
	width: 100%;
}
</style>
