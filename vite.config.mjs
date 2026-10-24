/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'node:path'

export default createAppConfig({
	'admin-settings': resolve(join('src', 'main.js')),
}, {
	inlineCSS: true,
	extractLicenseInformation: false,
})
