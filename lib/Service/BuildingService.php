<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <georg-nextcloud@ehrke.email>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
