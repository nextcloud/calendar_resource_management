<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;

class ResourceService {
	/** @var ResourceMapper */
	private $resourceMapper;

	/** @var RestrictionMapper */
	private $restrictionMapper;

	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
		// ...
	];

	/**
	 * ResourceService constructor.
	 * @param ResourceMapper $resourceMapper
	 * @param RestrictionMapper $restrictionMapper
	 */
	public function __construct(ResourceMapper $resourceMapper,
		RestrictionMapper $restrictionMapper) {
		$this->resourceMapper = $resourceMapper;
		$this->restrictionMapper = $restrictionMapper;
	}
}
