<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCP\Security\ISecureRandom;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Generates a readable default email, e.g. "Kitchen" -> kitchen-a7f3@room.local.
 */
class EmailGenerationService {
	/** Reserved TLD, so a generated address can never route mail off the instance. */
	private const EMAIL_TLD = '.local';

	/** Length of the random suffix appended to every generated local part. */
	private const SUFFIX_LENGTH = 4;

	/** RFC 5321 local-part limit, enforced by {@see FieldValidationService::assertEmail()}. */
	private const MAX_LOCAL_PART_LENGTH = 64;

	private SluggerInterface $slugger;

	public function __construct(
		private ISecureRandom $random,
	) {
		// Fixed locale so the slug doesn't depend on who creates the entity.
		$this->slugger = new AsciiSlugger('en');
	}

	/**
	 * The address an entity gets when no email was given.
	 *
	 * @param string $displayName the entity's name, used for the readable part
	 * @param string $entityType one of `Constants::ROOM|RESOURCE|VEHICLE`; the domain and fallback slug
	 * @return string the generated email address
	 */
	public function defaultEmail(string $displayName, string $entityType): string {
		$slug = $this->slug($displayName);
		if ($slug === '') {
			$slug = $this->slug($entityType);
		}

		$slug = substr($slug, 0, self::MAX_LOCAL_PART_LENGTH - self::SUFFIX_LENGTH - 1);

		$suffix = $this->random->generate(
			self::SUFFIX_LENGTH,
			ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS,
		);

		return $slug . '-' . $suffix . '@' . $entityType . self::EMAIL_TLD;
	}

	/**
	 * The email to persist: the given one, or a generated default when null or blank.
	 *
	 * @param string|null $email the admin-supplied address, if any
	 * @param string $displayName the entity's name, used when generating
	 * @param string $entityType one of the `Constants::ROOM|RESOURCE|VEHICLE` values
	 * @return string the address to store
	 */
	public function resolveEmail(?string $email, string $displayName, string $entityType): string {
		$email = trim($email ?? '');

		return $email === '' ? $this->defaultEmail($displayName, $entityType) : $email;
	}

	/**
	 * The readable part of an address: the name reduced to lowercase ASCII words.
	 *
	 * @param string $displayName the name to reduce
	 * @return string the slug, or '' when the name holds nothing transliterable
	 */
	private function slug(string $displayName): string {
		return (string)$this->slugger->slug($displayName)->lower();
	}
}
