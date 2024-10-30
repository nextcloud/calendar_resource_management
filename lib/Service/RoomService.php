<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RoomMapper;

class RoomService {
	/** @var RoomMapper */
	private $roomMapper;

	/** @var RestrictionMapper */
	private $restrictionMapper;

	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
		// ...
	];

	/**
	 * ResourceService constructor.
	 * @param RoomMapper $roomMapper
	 * @param RestrictionMapper $restrictionMapper
	 */
	public function __construct(RoomMapper $roomMapper,
		RestrictionMapper $restrictionMapper) {
		$this->roomMapper = $roomMapper;
		$this->restrictionMapper = $restrictionMapper;
	}
}
