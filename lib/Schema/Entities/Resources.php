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
 * The resources entity: general bookable resources, referencing their building
 * directly.
 */
class Resources extends EntityDefinition {
	public function __construct(IL10N $l10n) {
		parent::__construct(
			key: 'resources',
			table: 'calresources_resources',
			path: '/resources',
			label: $l10n->t('Resources'),
			singular: $l10n->t('resource'),
			cascades: false,
			fields: [
				new FieldDefinition(
					name: 'buildingId',
					label: $l10n->t('Building'),
					type: FieldDefinition::TYPE_INT,
					form: FieldDefinition::FORM_SELECT,
					column: 'building_id',
					required: true,
					filter: FieldDefinition::FILTER_SELECT,
					options: '/buildings/options',
				),
				new FieldDefinition(
					name: 'name',
					label: $l10n->t('Name'),
					form: FieldDefinition::FORM_TEXT,
					column: 'display_name',
					inTable: true,
					required: true,
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'email',
					label: $l10n->t('Email'),
					form: FieldDefinition::FORM_EMAIL,
					column: 'email',
					inTable: true,
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'resourceType',
					label: $l10n->t('Resource type'),
					form: FieldDefinition::FORM_COMBOBOX,
					column: 'resource_type',
					inTable: true,
					filter: FieldDefinition::FILTER_SELECT,
					options: '/resources/types',
				),
				new FieldDefinition(
					name: 'contactPersonUserId',
					label: $l10n->t('Contact person'),
					form: FieldDefinition::FORM_USER,
					column: 'contact_person_user_id',
					filter: FieldDefinition::FILTER_USER,
				),
				new FieldDefinition(
					name: 'restricted',
					label: $l10n->t('Restricted'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'restricted',
					filter: FieldDefinition::FILTER_BOOL,
				),
			],
		);
	}
}
