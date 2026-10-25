<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { BookableType, BookingCalendar } from './types/types.ts'

import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { getLoggerBuilder } from '@nextcloud/logger'
import { computed, onMounted, ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import * as api from './services/personalService.ts'

interface CalendarGroup {
	key: string
	name: string
	calendars: BookingCalendar[]
}

const logger = getLoggerBuilder()
	.setApp('calendar_resource_management')
	.detectUser()
	.build()

const loading = ref(true)
const calendars = ref<BookingCalendar[]>([])
const autoSubscribeNew = ref(false)
const search = ref('')
// Keys of the calendars with a request in flight
const saving = ref(new Set<string>())

const typeLabels: Record<BookableType, string> = {
	room: t('calendar_resource_management', 'Room'),
	resource: t('calendar_resource_management', 'Resource'),
	vehicle: t('calendar_resource_management', 'Vehicle'),
}

const subscribedCount = computed(() => calendars.value.filter((calendar) => calendar.subscribed).length)

/**
 * The calendars matching the search, grouped by building and floor.
 */
const groups = computed<CalendarGroup[]>(() => {
	const query = search.value.trim().toLocaleLowerCase()
	const grouped = new Map<string, CalendarGroup>()

	for (const calendar of calendars.value) {
		const haystack = [calendar.name, calendar.buildingName, calendar.storyName].join(' ').toLocaleLowerCase()
		if (query !== '' && !haystack.includes(query)) {
			continue
		}

		const building = calendar.buildingName || t('calendar_resource_management', 'Unknown building')
		const name = calendar.storyName ? `${building} · ${calendar.storyName}` : building
		const group = grouped.get(name) ?? { key: name, name, calendars: [] }
		group.calendars.push(calendar)
		grouped.set(name, group)
	}

	return [...grouped.values()]
		.sort((a, b) => a.name.localeCompare(b.name))
		.map((group) => ({
			...group,
			calendars: group.calendars.sort((a, b) => a.name.localeCompare(b.name)),
		}))
})

/**
 * Identify a calendar across rooms, resources and vehicles.
 *
 * @param calendar The booking calendar
 * @return A key unique for all booking calendars
 */
function calendarKey(calendar: BookingCalendar): string {
	return `${calendar.type}-${calendar.id}`
}

/**
 * Describe a calendar below its name.
 *
 * @param calendar The booking calendar
 * @return Type, capacity and whether it is a default
 */
function calendarDetails(calendar: BookingCalendar): string {
	const details = [typeLabels[calendar.type]]
	if (calendar.capacity !== null) {
		details.push(t('calendar_resource_management', 'Capacity {capacity}', { capacity: calendar.capacity }))
	}
	if (calendar.isDefault) {
		details.push(t('calendar_resource_management', 'Added for your group'))
	}

	return details.join(' · ')
}

onMounted(async () => {
	try {
		const loaded = await api.fetchBookingCalendars()
		calendars.value = loaded.calendars
		autoSubscribeNew.value = loaded.autoSubscribeNew
	} catch (error) {
		logger.error('Could not load booking calendars', { error })
		showError(t('calendar_resource_management', 'Could not load booking calendars'))
	} finally {
		loading.value = false
	}
})

/**
 * Add the booking calendar to the calendars of the user or remove it.
 *
 * @param calendar The booking calendar
 * @param subscribed Whether the calendar should be shown
 */
async function onSubscribedChange(calendar: BookingCalendar, subscribed: boolean): Promise<void> {
	const key = calendarKey(calendar)
	saving.value.add(key)
	calendar.subscribed = subscribed
	try {
		await api.saveSubscribed(calendar.type, calendar.id, subscribed)
	} catch (error) {
		calendar.subscribed = !subscribed
		logger.error('Could not change booking calendar', { error })
		showError(subscribed
			? t('calendar_resource_management', 'Could not add the calendar of {name}', { name: calendar.name })
			: t('calendar_resource_management', 'Could not remove the calendar of {name}', { name: calendar.name }))
	} finally {
		saving.value.delete(key)
	}
}

/**
 * Save whether new rooms, resources and vehicles are added automatically.
 *
 * @param enabled Whether to add new booking calendars
 */
async function onAutoSubscribeNewChange(enabled: boolean): Promise<void> {
	autoSubscribeNew.value = enabled
	try {
		await api.saveAutoSubscribeNew(enabled)
	} catch (error) {
		autoSubscribeNew.value = !enabled
		logger.error('Could not save booking calendar preference', { error })
		showError(t('calendar_resource_management', 'Could not save the setting'))
	}
}
</script>

<template>
	<NcSettingsSection
		:description="t('calendar_resource_management', 'Pick the rooms, resources and vehicles whose bookings you want to see. Their calendars are added read-only to your calendars, in Nextcloud and in every connected calendar app.')"
		:name="t('calendar_resource_management', 'Resource booking calendars')">
		<NcLoadingIcon v-if="loading" />

		<template v-else>
			<NcCheckboxRadioSwitch
				:modelValue="autoSubscribeNew"
				type="switch"
				@update:modelValue="onAutoSubscribeNewChange">
				{{ t('calendar_resource_management', 'Automatically add new rooms, resources and vehicles') }}
			</NcCheckboxRadioSwitch>

			<NcEmptyContent
				v-if="calendars.length === 0"
				:name="t('calendar_resource_management', 'There are no booking calendars you can add')" />

			<template v-else>
				<NcTextField
					v-model="search"
					class="crm-search"
					:label="t('calendar_resource_management', 'Search by name, building or floor')"
					type="search" />

				<p class="crm-summary">
					{{ t('calendar_resource_management', '{count} of {total} calendars added', { count: subscribedCount, total: calendars.length }) }}
				</p>

				<p v-if="groups.length === 0" class="crm-summary">
					{{ t('calendar_resource_management', 'No calendar matches your search') }}
				</p>

				<section v-for="group in groups" :key="group.key" class="crm-group">
					<h3>{{ group.name }}</h3>
					<ul>
						<li v-for="calendar in group.calendars" :key="calendarKey(calendar)">
							<NcCheckboxRadioSwitch
								:description="calendarDetails(calendar)"
								:disabled="saving.has(calendarKey(calendar))"
								:loading="saving.has(calendarKey(calendar))"
								:modelValue="calendar.subscribed"
								@update:modelValue="onSubscribedChange(calendar, $event)">
								{{ calendar.name }}
							</NcCheckboxRadioSwitch>
						</li>
					</ul>
				</section>
			</template>
		</template>
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
.crm-search {
	margin-block: calc(2 * var(--default-grid-baseline));
	max-width: 500px;
}

.crm-summary {
	color: var(--color-text-maxcontrast);
}

.crm-group {
	margin-block-start: calc(3 * var(--default-grid-baseline));
	max-width: 700px;

	h3 {
		font-size: var(--default-font-size);
		font-weight: bold;
		margin-block-end: var(--default-grid-baseline);
	}
}
</style>
