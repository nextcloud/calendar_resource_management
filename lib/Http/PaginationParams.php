<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Http;

final class PaginationParams {
	/**
	 * Rows per page, also sent to the admin UI as `pageSize`.
	 */
	public const DEFAULT_LIMIT = 20;

	public readonly int $page;
	public readonly int $pages;
	public readonly int $total;

	/**
	 * @param int|null $page the requested 1-based page, or null for the first
	 * @param int $total the number of rows matching the filters
	 */
	public function __construct(?int $page, int $total) {
		$this->total = max(0, $total);
		$this->pages = max(1, (int)ceil($this->total / self::DEFAULT_LIMIT));
		$this->page = min(max(1, $page ?? 1), $this->pages);
	}

	/**
	 * @return int the query's `OFFSET`
	 */
	public function offset(): int {
		return ($this->page - 1) * self::DEFAULT_LIMIT;
	}
}
