<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'ocs' => [
		// Buildings
		['name' => 'Building#index', 'url' => '/api/v1/buildings', 'verb' => 'GET'],
		['name' => 'Building#buildingOptions', 'url' => '/api/v1/buildings/options', 'verb' => 'GET'],
		['name' => 'Building#create', 'url' => '/api/v1/buildings', 'verb' => 'POST'],
		['name' => 'Building#update', 'url' => '/api/v1/buildings/{id}', 'verb' => 'PUT'],
		['name' => 'Building#destroy', 'url' => '/api/v1/buildings/{id}', 'verb' => 'DELETE'],

		// Stories
		['name' => 'Story#index', 'url' => '/api/v1/stories', 'verb' => 'GET'],
		['name' => 'Story#storyOptions', 'url' => '/api/v1/stories/options', 'verb' => 'GET'],
		['name' => 'Story#create', 'url' => '/api/v1/stories', 'verb' => 'POST'],
		['name' => 'Story#update', 'url' => '/api/v1/stories/{id}', 'verb' => 'PUT'],
		['name' => 'Story#destroy', 'url' => '/api/v1/stories/{id}', 'verb' => 'DELETE'],

		// Rooms
		['name' => 'Room#index', 'url' => '/api/v1/rooms', 'verb' => 'GET'],
		['name' => 'Room#roomTypes', 'url' => '/api/v1/rooms/types', 'verb' => 'GET'],
		['name' => 'Room#create', 'url' => '/api/v1/rooms', 'verb' => 'POST'],
		['name' => 'Room#update', 'url' => '/api/v1/rooms/{id}', 'verb' => 'PUT'],
		['name' => 'Room#destroy', 'url' => '/api/v1/rooms/{id}', 'verb' => 'DELETE'],

		// Resources
		['name' => 'Resource#index', 'url' => '/api/v1/resources', 'verb' => 'GET'],
		['name' => 'Resource#resourceTypes', 'url' => '/api/v1/resources/types', 'verb' => 'GET'],
		['name' => 'Resource#create', 'url' => '/api/v1/resources', 'verb' => 'POST'],
		['name' => 'Resource#update', 'url' => '/api/v1/resources/{id}', 'verb' => 'PUT'],
		['name' => 'Resource#destroy', 'url' => '/api/v1/resources/{id}', 'verb' => 'DELETE'],

		// Vehicles
		['name' => 'Vehicle#index', 'url' => '/api/v1/vehicles', 'verb' => 'GET'],
		['name' => 'Vehicle#vehicleTypes', 'url' => '/api/v1/vehicles/types', 'verb' => 'GET'],
		['name' => 'Vehicle#create', 'url' => '/api/v1/vehicles', 'verb' => 'POST'],
		['name' => 'Vehicle#update', 'url' => '/api/v1/vehicles/{id}', 'verb' => 'PUT'],
		['name' => 'Vehicle#destroy', 'url' => '/api/v1/vehicles/{id}', 'verb' => 'DELETE'],
	],
];
