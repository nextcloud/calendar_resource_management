<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Service\EmailGenerationService;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class EmailGenerationServiceTest extends TestCase {
	private EmailGenerationService $service;

	protected function setUp(): void {
		parent::setUp();

		$random = $this->createMock(ISecureRandom::class);
		$random->method('generate')->willReturn('a7f3');

		$this->service = new EmailGenerationService($random);
	}

	/**
	 * @dataProvider defaultEmailProvider
	 */
	public function testDefaultEmail(string $displayName, string $expected): void {
		$this->assertSame($expected, $this->service->defaultEmail($displayName, Constants::ROOM));
	}

	/**
	 * Only names with nothing transliterable fall back to the entity type.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function defaultEmailProvider(): array {
		return [
			'plain name' => ['Kitchen', 'kitchen-a7f3@room.local'],
			'several words' => ['Big meeting room', 'big-meeting-room-a7f3@room.local'],
			'accents folded' => ['Große Küche', 'grosse-kuche-a7f3@room.local'],
			'punctuation collapsed' => ['Room #3 — "Annex"', 'room-3-annex-a7f3@room.local'],
			'already a slug' => ['kitchen', 'kitchen-a7f3@room.local'],
			'cjk romanised' => ['会議室', 'hui-yi-shi-a7f3@room.local'],
			'cyrillic romanised' => ['кухня', 'kuhna-a7f3@room.local'],
			'nothing transliterable' => ['🎉', 'room-a7f3@room.local'],
			'only separators' => ['---', 'room-a7f3@room.local'],
			'blank' => ['', 'room-a7f3@room.local'],
		];
	}

	public function testDefaultEmailUsesTheEntityTypeAsTheDomain(): void {
		$this->assertSame('beamer-a7f3@resource.local', $this->service->defaultEmail('Beamer', Constants::RESOURCE));
		$this->assertSame('van-a7f3@vehicle.local', $this->service->defaultEmail('Van', Constants::VEHICLE));
	}

	/**
	 * @dataProvider defaultEmailProvider
	 */
	public function testDefaultEmailIsAlwaysADeliverableAddress(string $displayName): void {
		$email = $this->service->defaultEmail($displayName, Constants::ROOM);

		$this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
	}

	/**
	 * A long name keeps the local part within the 64-character limit.
	 */
	public function testDefaultEmailTruncatesALongName(): void {
		$email = $this->service->defaultEmail(str_repeat('meeting room ', 20), Constants::ROOM);
		$localPart = substr($email, 0, (int)strpos($email, '@'));

		$this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
		$this->assertSame(64, strlen($localPart));
		$this->assertStringEndsWith('-a7f3@room.local', $email);
		$this->assertLessThanOrEqual(255, strlen($email));
	}

	/**
	 * A separator on the cut may double, but the address stays valid.
	 */
	public function testDefaultEmailKeepsASeparatorLeftOnTheCut(): void {
		$email = $this->service->defaultEmail(str_repeat('a', 58) . ' annex', Constants::ROOM);
		$localPart = substr($email, 0, (int)strpos($email, '@'));

		$this->assertSame(str_repeat('a', 58) . '--a7f3@room.local', $email);
		$this->assertSame(64, strlen($localPart));
		$this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
	}

	/**
	 * @dataProvider blankEmailProvider
	 */
	public function testResolveEmailGeneratesOneWhenBlank(?string $given): void {
		$this->assertSame(
			'kitchen-a7f3@room.local',
			$this->service->resolveEmail($given, 'Kitchen', Constants::ROOM),
		);
	}

	/**
	 * @return array<string, array{string|null}>
	 */
	public static function blankEmailProvider(): array {
		return [
			'missing' => [null],
			'empty' => [''],
			'whitespace' => ['   '],
		];
	}

	public function testResolveEmailKeepsAndTrimsAGivenAddress(): void {
		$this->assertSame(
			'kitchen@example.org',
			$this->service->resolveEmail('  kitchen@example.org  ', 'Kitchen', Constants::ROOM),
		);
	}
}
