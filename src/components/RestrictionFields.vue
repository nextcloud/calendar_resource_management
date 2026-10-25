<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { GroupOption } from '../types/types.ts'

import { translate as t } from '@nextcloud/l10n'
import { onMounted, ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { fetchGroups } from '../services/adminService.ts'

const restricted = defineModel<boolean>('restricted', { required: true })

const groups = defineModel<GroupOption[]>('groups', { required: true })

defineProps<{
	/** Already restricted without groups, its booking calendar is gone */
	hidden?: boolean
}>()

const groupOptions = ref<GroupOption[]>([])
const loadingGroups = ref(false)

/**
 * Load the groups to pick from. The endpoint limits the result, so filtering
 * happens server side instead of in the dropdown.
 *
 * @param search Filter for the group name
 */
async function searchGroups(search = ''): Promise<void> {
	loadingGroups.value = true
	try {
		groupOptions.value = (await fetchGroups(search)).map((group) => ({ id: group.id, label: group.displayName }))
	} catch {
		// Without groups the dropdown stays empty, there is nothing to pick
		groupOptions.value = []
	} finally {
		loadingGroups.value = false
	}
}

onMounted(searchGroups)
</script>

<template>
	<fieldset class="crm-restriction">
		<legend>{{ t('calendar_resource_management', 'Access') }}</legend>
		<NcCheckboxRadioSwitch v-model="restricted" type="switch">
			{{ t('calendar_resource_management', 'Only members of selected groups can book it') }}
		</NcCheckboxRadioSwitch>

		<template v-if="restricted">
			<NcSelect
				v-model="groups"
				class="crm-field"
				:filterable="false"
				:inputLabel="t('calendar_resource_management', 'Groups')"
				:loading="loadingGroups"
				multiple
				:options="groupOptions"
				:placeholder="t('calendar_resource_management', 'Search for a group')"
				@search="searchGroups" />

			<NcNoteCard v-if="groups.length === 0 && hidden" class="crm-field" type="warning">
				{{ t('calendar_resource_management', 'Without groups nobody can book it, and it is hidden in the calendar.') }}
			</NcNoteCard>
			<NcNoteCard v-else-if="groups.length === 0" class="crm-field" type="error">
				{{ t('calendar_resource_management', 'Select at least one group. Without groups it would be hidden, and the server deletes its booking calendar with all bookings.') }}
			</NcNoteCard>
		</template>
	</fieldset>
</template>

<style lang="scss" scoped>
.crm-restriction {
	margin-block: calc(2 * var(--default-grid-baseline));

	legend {
		font-weight: bold;
	}
}
</style>
