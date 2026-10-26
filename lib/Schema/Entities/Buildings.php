<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Schema\Entities;

use OCA\CalendarResourceManagement\Schema\EntityDefinition;
use OCA\CalendarResourceManagement\Schema\FieldDefinition;
use OCP\IL10N;

/**
 * The buildings entity: the root of the hierarchy, referenced by stories,
 * resources and vehicles.
 */
class Buildings extends EntityDefinition {
	public function __construct(IL10N $l10n) {
		parent::__construct(
			key: 'buildings',
			table: 'calresources_buildings',
			path: '/buildings',
			label: $l10n->t('Buildings'),
			singular: $l10n->t('building'),
			cascades: true,
			fields: [
				new FieldDefinition(
					name: 'displayName',
					label: $l10n->t('Name'),
					form: FieldDefinition::FORM_TEXT,
					column: 'display_name',
					inTable: true,
					required: true,
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'address',
					label: $l10n->t('Address'),
					form: FieldDefinition::FORM_TEXT,
					column: 'address',
					inTable: true,
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'description',
					label: $l10n->t('Description'),
					form: FieldDefinition::FORM_TEXTAREA,
					column: 'description',
					inTable: true,
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'isWheelchairAccessible',
					label: $l10n->t('Wheelchair accessible'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'is_wheelchair_accessible',
					inTable: true,
					filter: FieldDefinition::FILTER_BOOL,
				),
			],
		);
	}
}
