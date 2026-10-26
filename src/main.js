/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import App from './App.vue'

const app = createApp(App, {
	initialType: new URLSearchParams(window.location.search).get('type'),
})

app.mount('#calendar-resource-admin')
