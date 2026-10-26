<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Settings;

use OCA\CalendarResourceManagement\Http\PaginationParams;
use OCA\CalendarResourceManagement\Schema\Entities\Buildings;
use OCA\CalendarResourceManagement\Schema\Entities\Resources;
use OCA\CalendarResourceManagement\Schema\Entities\Rooms;
use OCA\CalendarResourceManagement\Schema\Entities\Stories;
use OCA\CalendarResourceManagement\Schema\Entities\Vehicles;
use OCA\CalendarResourceManagement\Settings\Admin;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use Test\TestCase;

class AdminTest extends TestCase {
	/**
	 * The UI gets every entity, keyed by schema key, in tab order.
	 */
	public function testTheFormPublishesEveryEntityInTabOrder(): void {
		$payload = $this->publishedSchema();

		$this->assertSame(
			['buildings', 'stories', 'rooms', 'resources', 'vehicles'],
			$payload['tabOrder']
		);
		$this->assertSame(array_keys($payload['entities']), $payload['tabOrder']);
		$this->assertSame('/rooms', $payload['entities']['rooms']['path']);
	}

	/**
	 * The UI is handed the server's own page size.
	 */
	public function testTheFormPublishesThePageSize(): void {
		$payload = $this->publishedSchema();

		$this->assertArrayHasKey('pageSize', $payload);
		$this->assertSame(PaginationParams::DEFAULT_LIMIT, $payload['pageSize']);
		$this->assertSame(20, $payload['pageSize']);
	}

	/**
	 * Render the admin form and return what it provided as `entitySchema`.
	 *
	 * @return array<string, mixed>
	 */
	private function publishedSchema(): array {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$published = null;
		$initialState = $this->createMock(IInitialState::class);
		$initialState->expects(self::once())
			->method('provideInitialState')
			->with('entitySchema', self::anything())
			->willReturnCallback(static function (string $key, mixed $data) use (&$published): void {
				$published = $data;
			});

		$admin = new Admin(
			$initialState,
			new Buildings($l10n),
			new Stories($l10n),
			new Rooms($l10n),
			new Resources($l10n),
			new Vehicles($l10n),
		);
		$admin->getForm();

		return $published;
	}
}
