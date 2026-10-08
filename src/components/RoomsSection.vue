<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Building, NewRoom, Room, Story } from '../types/types.ts'

import { mdiDelete, mdiPencil, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import RoomDialog from './dialogs/RoomDialog.vue'
import { nameById } from '../utils/entities.ts'
import { equipmentSummary } from '../utils/equipment.ts'

const props = defineProps<{
	buildings: Building[]
	rooms: Room[]
	stories: Story[]
	loading?: boolean
}>()

const emit = defineEmits<{
	create: [room: NewRoom]
	update: [id: number, room: NewRoom]
	delete: [id: number]
}>()

const dialogOpen = ref(false)
const editing = ref<Room | null>(null)

/**
 * Name of the building a room is located in.
 *
 * @param storyId The story the room is located on
 * @return The building name
 */
function buildingName(storyId: number): string {
	const candidate = props.stories.find((entity) => entity.id === storyId)

	return nameById(props.buildings, candidate?.buildingId)
}

/**
 * Open the dialog to create a room, or to edit the given one.
 *
 * @param room The room to edit
 */
function openDialog(room: Room | null = null): void {
	editing.value = room
	dialogOpen.value = true
}

/**
 * Close the dialog. Called by the parent once the room was saved.
 */
function close(): void {
	dialogOpen.value = false
}

defineExpose({ close })

/**
 * Hand the entered room over to the parent.
 *
 * @param room The entered room
 */
function submit(room: NewRoom): void {
	if (editing.value) {
		emit('update', editing.value.id, room)
	} else {
		emit('create', room)
	}
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Rooms are bookable in the calendar and are located on a floor of a building.')"
		:name="t('calendar_resource_management', 'Rooms')">
		<NcButton class="crm-add" @click="openDialog()">
			<template #icon>
				<NcIconSvgWrapper :path="mdiPlus" />
			</template>
			{{ t('calendar_resource_management', 'Add room') }}
		</NcButton>

		<div v-if="rooms.length" class="crm-table-wrapper crm-table-wrapper--wide">
			<table class="crm-table">
				<thead>
					<tr>
						<th>{{ t('calendar_resource_management', 'Name') }}</th>
						<th>{{ t('calendar_resource_management', 'Email') }}</th>
						<th>{{ t('calendar_resource_management', 'Room type') }}</th>
						<th>{{ t('calendar_resource_management', 'Room number') }}</th>
						<th>{{ t('calendar_resource_management', 'Capacity') }}</th>
						<th>{{ t('calendar_resource_management', 'Equipment') }}</th>
						<th>{{ t('calendar_resource_management', 'Building') }}</th>
						<th>{{ t('calendar_resource_management', 'Floor') }}</th>
						<th>{{ t('calendar_resource_management', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="room in rooms" :key="room.id">
						<td>{{ room.name }}</td>
						<td>{{ room.email || '-' }}</td>
						<td>{{ room.roomType || '-' }}</td>
						<td>{{ room.roomNumber || '-' }}</td>
						<td>{{ room.capacity || '-' }}</td>
						<td>{{ equipmentSummary(room) }}</td>
						<td>{{ buildingName(room.storyId) }}</td>
						<td>{{ nameById(stories, room.storyId) }}</td>
						<td>
							<div class="crm-table__actions">
								<NcButton
									:aria-label="t('calendar_resource_management', 'Edit room {name}', { name: room.name })"
									variant="tertiary"
									@click="openDialog(room)">
									<template #icon>
										<NcIconSvgWrapper :path="mdiPencil" />
									</template>
								</NcButton>
								<NcButton
									:aria-label="t('calendar_resource_management', 'Delete room {name}', { name: room.name })"
									variant="tertiary"
									@click="emit('delete', room.id)">
									<template #icon>
										<NcIconSvgWrapper :path="mdiDelete" />
									</template>
								</NcButton>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<RoomDialog
			v-model:open="dialogOpen"
			:buildings="buildings"
			:loading="loading"
			:room="editing"
			:stories="stories"
			@submit="submit" />
	</NcSettingsSection>
</template>
