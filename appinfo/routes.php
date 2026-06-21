<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		// API endpoints for rooms and resources
		['name' => 'admin#getrooms', 'url' => '/admin/rooms', 'verb' => 'GET'],
		['name' => 'admin#createroom', 'url' => '/admin/rooms', 'verb' => 'POST'],
		['name' => 'admin#deleteroom', 'url' => '/admin/rooms/{id}', 'verb' => 'DELETE'],
		['name' => 'admin#getresources', 'url' => '/admin/resources', 'verb' => 'GET'],
		['name' => 'admin#createresource', 'url' => '/admin/resources', 'verb' => 'POST'],
		['name' => 'admin#deleteresource', 'url' => '/admin/resources/{id}', 'verb' => 'DELETE'],
		['name' => 'admin#getstories', 'url' => '/admin/stories', 'verb' => 'GET'],
		['name' => 'admin#createbuilding', 'url' => '/admin/buildings', 'verb' => 'POST'],
		['name' => 'admin#createstory', 'url' => '/admin/stories', 'verb' => 'POST'],
		['name' => 'admin#getbuildings', 'url' => '/admin/buildings', 'verb' => 'GET'],
		['name' => 'admin#deletebuilding', 'url' => '/admin/buildings/{id}', 'verb' => 'DELETE'],
		['name' => 'admin#deletestory', 'url' => '/admin/stories/{id}', 'verb' => 'DELETE'],
	]
];
