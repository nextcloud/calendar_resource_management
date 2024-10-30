<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;

class BuildingService {
	/** @var BuildingMapper */
	private $buildingMapper;

	/** @var StoryMapper */
	private $storyMapper;

	/** @var int */
	private const LIMIT_STORIES_PER_BUILDING = 1000;

	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
	];

	/**
	 * BuildingService constructor.
	 * @param BuildingMapper $buildingMapper
	 * @param StoryMapper $storyMapper
	 */
	public function __construct(BuildingMapper $buildingMapper,
		StoryMapper $storyMapper) {
		$this->buildingMapper = $buildingMapper;
		$this->storyMapper = $storyMapper;
	}
}
