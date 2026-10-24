/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'

const APP_ID = 'calendar_resource_management'

const schema = loadState(APP_ID, 'entitySchema', { tabOrder: [], entities: {}, pageSize: 20 })

/** Ordered list of tabs. */
export const TAB_ORDER = schema.tabOrder ?? []

/** Entity configuration. */
export const ENTITIES = schema.entities ?? {}

/** Rows per page. */
export const PAGE_SIZE = schema.pageSize ?? 20
