/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createAppConfig } from '@nextcloud/vite-config'
import path from 'path'

export default createAppConfig({
	'admin-settings': path.join(__dirname, 'src', 'adminSettings.js'),
}, {
	inlineCSS: true,
	extractLicenseInformation: false,
})
