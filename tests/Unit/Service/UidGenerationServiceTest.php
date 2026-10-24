<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Service\UidGenerationService;
use OCA\CalendarResourceManagement\Service\UidValidationService;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class UidGenerationServiceTest extends TestCase {
	/** Everything the DAV cache can hold: `oc_calendar_*.resource_id` is VARCHAR(64). */
	private const MAX_UID_LENGTH = 64;

	public function testGenerateUidAsksForSixteenLowercaseAlphanumerics(): void {
		$random = $this->createMock(ISecureRandom::class);
		$random->expects($this->once())
			->method('generate')
			->with(16, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS)
			->willReturn('k3f9a2xq7m1p4wzb');

		$this->assertSame('k3f9a2xq7m1p4wzb', (new UidGenerationService($random))->generateUid());
	}

	/**
	 * The uid fits the DAV cache and passes the app's validator.
	 */
	public function testGeneratedUidIsValidAndFitsTheDavCache(): void {
		$random = $this->createMock(ISecureRandom::class);
		$random->method('generate')->willReturnCallback(
			static function (int $length, string $characters): string {
				$out = '';
				for ($i = 0; $i < $length; $i++) {
					$out .= $characters[random_int(0, strlen($characters) - 1)];
				}
				return $out;
			},
		);

		$service = new UidGenerationService($random);
		$uidValidation = new UidValidationService();

		for ($i = 0; $i < 50; $i++) {
			$uid = $service->generateUid();

			$this->assertMatchesRegularExpression('/^[a-z0-9]{16}$/', $uid);
			$this->assertLessThanOrEqual(self::MAX_UID_LENGTH, strlen($uid));
			$this->assertTrue($uidValidation->validateUid($uid));
		}
	}

	/**
	 * The uid does not depend on the display name.
	 */
	public function testGenerateUidTakesNoArguments(): void {
		$method = new \ReflectionMethod(UidGenerationService::class, 'generateUid');

		$this->assertSame(0, $method->getNumberOfParameters());
	}
}
