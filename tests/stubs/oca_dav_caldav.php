<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Only the parts of the internal DAV classes the app uses, the dav app is no composer package

namespace OCA\DAV\DAV\Sharing {
	interface IShareable {
		public function getResourceId(): int;

		public function getOwner(): ?string;
	}
}

namespace OCA\DAV\CalDAV {
	use OCA\DAV\DAV\Sharing\IShareable;
	use OCP\IConfig;
	use OCP\IL10N;
	use Psr\Log\LoggerInterface;
	use Sabre\DAV\PropPatch;

	class CalDavBackend {
		public const PERSONAL_CALENDAR_URI = 'personal';
		public const PERSONAL_CALENDAR_NAME = 'Personal';
		public const RESOURCE_BOOKING_CALENDAR_URI = 'calendar';
		public const RESOURCE_BOOKING_CALENDAR_NAME = 'Calendar';

		/**
		 * @param string $principalUri
		 * @return list<array<string, mixed>>
		 */
		public function getCalendarsForUser($principalUri) {
		}

		/**
		 * @param string $principal
		 * @param string $uri
		 * @return array<string, mixed>|null
		 */
		public function getCalendarByUri($principal, $uri) {
		}

		/**
		 * @return array<string, mixed>|null
		 */
		public function getCalendarById(int $calendarId): ?array {
		}

		/**
		 * @param int $calendarId
		 * @return void
		 */
		public function updateCalendar($calendarId, PropPatch $propPatch) {
		}

		/**
		 * @param list<array{href: string, commonName: string, readOnly: bool}> $add
		 * @param list<string> $remove
		 */
		public function updateShares(IShareable $shareable, array $add, array $remove): void {
		}

		/**
		 * @return list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}>
		 */
		public function getShares(int $resourceId): array {
		}

		public function unshare(IShareable $shareable, string $principal): void {
		}
	}

	class Calendar implements IShareable {
		/**
		 * @param array<string, mixed> $calendarInfo
		 */
		public function __construct(CalDavBackend $caldavBackend, array $calendarInfo, IL10N $l10n, IConfig $config, LoggerInterface $logger) {
		}

		public function getResourceId(): int {
		}

		public function getOwner(): ?string {
		}

		/**
		 * @param string[] $requestedProperties
		 * @return array<string, mixed>
		 */
		public function getProperties($requestedProperties) {
		}
	}
}
