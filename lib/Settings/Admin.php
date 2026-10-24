<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Settings;

use OCA\CalendarResourceManagement\Http\PaginationParams;
use OCA\CalendarResourceManagement\Schema\Entities\Buildings;
use OCA\CalendarResourceManagement\Schema\Entities\Resources;
use OCA\CalendarResourceManagement\Schema\Entities\Rooms;
use OCA\CalendarResourceManagement\Schema\Entities\Stories;
use OCA\CalendarResourceManagement\Schema\Entities\Vehicles;
use OCA\CalendarResourceManagement\Schema\EntityDefinition;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;

class Admin implements ISettings
{
	/** @var EntityDefinition[] in tab order */
	private array $entities;

	public function __construct(
		private IInitialState $initialState,
		Buildings $buildings,
		Stories $stories,
		Rooms $rooms,
		Resources $resources,
		Vehicles $vehicles,
	) {
		/**
		 * The order the entities are listed in here is the admin UI's tab order.
		 */
		$this->entities = [$buildings, $stories, $rooms, $resources, $vehicles];
	}

	public function getForm(): TemplateResponse
	{
		$this->initialState->provideInitialState('entitySchema', $this->frontendSchema());

		return new TemplateResponse('calendar_resource_management', 'settings/admin', []);
	}

	public function getSection(): string
	{
		return 'calendar_resource_management';
	}

	public function getPriority(): int
	{
		return 10;
	}

	/**
	 * Every entity as the admin UI consumes it.
	 *
	 * `pageSize` is sent so the pager matches the server's page size.
	 *
	 * @return array{tabOrder: string[], pageSize: int, entities: array<string, array<string, mixed>>}
	 */
	private function frontendSchema(): array
	{
		$entities = [];
		foreach ($this->entities as $entity) {
			$entities[$entity->key] = $entity->frontendDefinition();
		}

		return [
			'tabOrder' => array_keys($entities),
			'pageSize' => PaginationParams::DEFAULT_LIMIT,
			'entities' => $entities,
		];
	}
}
