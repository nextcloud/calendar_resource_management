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

class Vehicles extends EntityDefinition {
	public function __construct(IL10N $l10n) {
		parent::__construct(
			key: 'vehicles',
			table: 'calresources_vehicles',
			path: '/vehicles',
			label: $l10n->t('Vehicles'),
			singular: $l10n->t('vehicle'),
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
					name: 'vehicleType',
					label: $l10n->t('Vehicle type'),
					form: FieldDefinition::FORM_COMBOBOX,
					column: 'vehicle_type',
					inTable: true,
					filter: FieldDefinition::FILTER_SELECT,
					options: '/vehicles/types',
				),
				new FieldDefinition(
					name: 'seatingCapacity',
					label: $l10n->t('Seating capacity'),
					form: FieldDefinition::FORM_NUMBER,
					type: FieldDefinition::TYPE_INT,
					column: 'seating_capacity',
					inTable: true,
					min: 0,
					filter: FieldDefinition::FILTER_RANGE,
				),
				new FieldDefinition(
					name: 'vehicleMake',
					label: $l10n->t('Make'),
					form: FieldDefinition::FORM_TEXT,
					column: 'vehicle_make',
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'vehicleModel',
					label: $l10n->t('Model'),
					form: FieldDefinition::FORM_TEXT,
					column: 'vehicle_model',
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'contactPersonUserId',
					label: $l10n->t('Contact person'),
					form: FieldDefinition::FORM_USER,
					column: 'contact_person_user_id',
					filter: FieldDefinition::FILTER_USER,
				),
				new FieldDefinition(
					name: 'range',
					label: $l10n->t('Range'),
					form: FieldDefinition::FORM_NUMBER,
					type: FieldDefinition::TYPE_INT,
					column: 'range',
					min: 0,
					filter: FieldDefinition::FILTER_RANGE,
				),
				new FieldDefinition(
					name: 'isElectric',
					label: $l10n->t('Electric'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'is_electric',
					filter: FieldDefinition::FILTER_BOOL,
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
