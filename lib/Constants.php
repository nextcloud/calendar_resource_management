<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement;

final class Constants {
	public const RESOURCE = 'resource';
	public const ROOM = 'room';
	public const VEHICLE = 'vehicle';

	public const RESTRICTABLE_TYPES = [
		self::RESOURCE,
		self::ROOM,
		self::VEHICLE,
	];

}
