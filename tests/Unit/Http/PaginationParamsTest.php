<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Http;

use OCA\CalendarResourceManagement\Http\PaginationParams;
use Test\TestCase;

class PaginationParamsTest extends TestCase {
	/**
	 * @dataProvider clampingData
	 */
	public function testItClampsThePageTheRequestAskedFor(
		?int $page,
		int $total,
		int $expectedPage,
		int $expectedPages,
		int $expectedOffset,
	): void {
		$pagination = new PaginationParams($page, $total);

		$this->assertSame($expectedPage, $pagination->page);
		$this->assertSame($expectedPages, $pagination->pages);
		$this->assertSame($expectedOffset, $pagination->offset());
		$this->assertSame($total < 0 ? 0 : $total, $pagination->total);
	}

	/**
	 * Every page value a query string can carry; out-of-range pages are clamped.
	 *
	 * @return array<string, array{?int, int, int, int, int}>
	 */
	public static function clampingData(): array {
		return [
			// page, total, page, pages, offset
			'nothing asked for' => [null, 132, 1, 7, 0],
			'page zero' => [0, 132, 1, 7, 0],
			'negative page' => [-5, 132, 1, 7, 0],
			'second page' => [2, 132, 2, 7, 20],
			'last page' => [7, 132, 7, 7, 120],
			'past the last page' => [8, 132, 7, 7, 120],
			'the largest page there is' => [PHP_INT_MAX, 132, 7, 7, 120],
			'exact multiple' => [2, 40, 2, 2, 20],
			'one row into a new page' => [3, 41, 3, 3, 40],
			'a single row' => [1, 1, 1, 1, 0],
			'no rows' => [1, 0, 1, 1, 0],
			'no rows, page asked for anyway' => [4, 0, 1, 1, 0],
			'a negative total' => [1, -4, 1, 1, 0],
		];
	}

	/**
	 * A page past the end (e.g. after a delete) is pinned to the last page.
	 */
	public function testItPinsAnOverrunPageToTheLastOne(): void {
		$pagination = new PaginationParams(999, 25);

		$this->assertSame(2, $pagination->pages);
		$this->assertSame(2, $pagination->page);
		$this->assertSame(20, $pagination->offset());
	}

	/**
	 * An empty result set still has one page.
	 */
	public function testItGivesAnEmptyResultSetOnePage(): void {
		$pagination = new PaginationParams(null, 0);

		$this->assertSame(1, $pagination->pages);
		$this->assertSame(1, $pagination->page);
		$this->assertSame(0, $pagination->total);
		$this->assertSame(0, $pagination->offset());
	}

	public function testItFloorsANegativeTotal(): void {
		$pagination = new PaginationParams(1, -4);

		$this->assertSame(0, $pagination->total);
		$this->assertSame(1, $pagination->pages);
	}

	/**
	 * Every envelope number is readable, and `pages` is derived.
	 */
	public function testEveryNumberTheEnvelopeIsBuiltFromIsReadable(): void {
		$pagination = new PaginationParams(2, 132);

		$this->assertSame(132, $pagination->total);
		$this->assertSame(2, $pagination->page);
		$this->assertSame(7, $pagination->pages);
	}

	/**
	 * The public constructor is the only way to build one, and it normalises.
	 */
	public function testItIsBuiltThroughItsConstructorAndNormalisesThere(): void {
		$class = new \ReflectionClass(PaginationParams::class);

		$this->assertTrue($class->getConstructor()->isPublic());
		$this->assertCount(2, $class->getConstructor()->getParameters());
		$this->assertSame(
			[],
			array_filter(
				$class->getMethods(\ReflectionMethod::IS_STATIC),
				static fn (\ReflectionMethod $m): bool => $m->getDeclaringClass()->getName() === PaginationParams::class
			),
			'No static factory should remain alongside a public constructor'
		);
		// Two arguments in, three checked properties out — neither as given.
		$pagination = new PaginationParams(999, 132);

		$this->assertSame(7, $pagination->page);
		$this->assertSame(7, $pagination->pages);
		$this->assertSame(132, $pagination->total);
	}

	public function testItDoesNotFormatResponses(): void {
		$this->assertFalse(
			method_exists(PaginationParams::class, 'toArray'),
			'The envelope belongs to the controllers, not to this value object'
		);
	}

	/**
	 * The page size is a fixed server constant, not a request parameter or property.
	 */
	public function testThePageSizeIsFixedAtTheAdvertisedOne(): void {
		$this->assertSame(20, PaginationParams::DEFAULT_LIMIT);
		$this->assertFalse(
			property_exists(PaginationParams::class, 'limit'),
			'The page size is a constant, not something an instance carries'
		);
		// It still decides the arithmetic: 41 rows is three pages of 20.
		$this->assertSame(3, (new PaginationParams(3, 41))->pages);
		$this->assertSame(40, (new PaginationParams(3, 41))->offset());

		$this->assertFalse(
			defined(PaginationParams::class . '::MAX_LIMIT'),
			'A page-size cap only makes sense alongside a negotiable page size'
		);
	}
}
