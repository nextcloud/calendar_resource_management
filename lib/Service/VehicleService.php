<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\VehicleMapper;

class VehicleService {
	/** @var VehicleMapper */
	private $vehicleMapper;

	/** @var RestrictionMapper */
	private $restrictionMapper;

	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
		// ...
	];

	/**
	 * ResourceService constructor.
	 * @param VehicleMapper $vehicleMapper
	 * @param RestrictionMapper $restrictionMapper
	 */
	public function __construct(VehicleMapper $vehicleMapper,
		RestrictionMapper $restrictionMapper) {
		$this->vehicleMapper = $vehicleMapper;
		$this->restrictionMapper = $restrictionMapper;
	}
}
