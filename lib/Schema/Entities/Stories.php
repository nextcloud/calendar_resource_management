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
class Stories extends EntityDefinition {
	public function __construct(IL10N $l10n) {
		parent::__construct(
			key: 'stories',
			table: 'calresources_stories',
			path: '/stories',
			label: $l10n->t('Stories'),
			singular: $l10n->t('story'),
			cascades: true,
			fields: [
				new FieldDefinition(
					name: 'buildingId',
					label: $l10n->t('Building'),
					type: FieldDefinition::TYPE_INT,
					form: FieldDefinition::FORM_SELECT,
					column: 'building_id',
					inTable: false,
					required: true,
					filter: FieldDefinition::FILTER_SELECT,
					options: '/buildings/options',
				),
				new FieldDefinition(
					name: 'displayName',
					label: $l10n->t('Name'),
					form: FieldDefinition::FORM_TEXT,
					column: 'display_name',
					inTable: true,
					required: true,
					filter: FieldDefinition::FILTER_TEXT,
				),
			],
		);
	}
}
