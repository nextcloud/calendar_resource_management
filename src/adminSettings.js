/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import CalendarResourceAdmin from './CalendarResourceAdmin.vue'

// Wrap t and n to auto-inject app name
const appId = 'calendar_resource_management'
const wrapT = (text, vars) => t(appId, text, vars)
const wrapN = (textSingular, textPlural, count, vars) => n(appId, textSingular, textPlural, count, vars)

const app = createApp(CalendarResourceAdmin)
app.mixin({ methods: { t: wrapT, n: wrapN } })
app.mount('#calendar-resource-admin')