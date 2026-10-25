/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createAppConfig } from '@nextcloud/vite-config'
import path from 'path'

export default createAppConfig({
	'admin-settings': path.join(__dirname, 'src', 'adminSettings.ts'),
	'personal-settings': path.join(__dirname, 'src', 'personalSettings.ts'),
}, {
	// Each settings page loads only its own entry, so each entry injects its own styles
	inlineCSS: { relativeCSSInjection: true },
	extractLicenseInformation: false,
})
