<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Schema\Entities;

use OCA\CalendarResourceManagement\Db\Relation;
use OCA\CalendarResourceManagement\Schema\EntityDefinition;
use OCA\CalendarResourceManagement\Schema\FieldDefinition;
use OCA\CalendarResourceManagement\Schema\RelationDefinition;
use OCP\IL10N;

class Rooms extends EntityDefinition {
	public function __construct(IL10N $l10n) {
		parent::__construct(
			key: 'rooms',
			table: 'calresources_rooms',
			path: '/rooms',
			label: $l10n->t('Rooms'),
			singular: $l10n->t('room'),
			cascades: false,
			fields: [
				// Virtual field: not in the entities's table, value retrieved via relation
				new FieldDefinition(
					name: 'buildingId',
					label: $l10n->t('Building'),
					type: FieldDefinition::TYPE_INT,
					form: FieldDefinition::FORM_SELECT,
					required: true,
					filter: FieldDefinition::FILTER_SELECT,
					relation: new RelationDefinition(
						through: [
							new Relation(
								foreignKey: 'story_id',
								table: 'calresources_stories',
								primaryKey: 'id',
							),
						],
						column: 'building_id',
					),
					cascade: 'storyId',
					options: '/buildings/options',
				),
				new FieldDefinition(
					name: 'storyId',
					label: $l10n->t('Story'),
					type: FieldDefinition::TYPE_INT,
					form: FieldDefinition::FORM_SELECT,
					column: 'story_id',
					required: true,
					filter: FieldDefinition::FILTER_SELECT,
					options: '/stories/options',
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
				new FieldDefinition(
					name: 'email',
					label: $l10n->t('Email'),
					form: FieldDefinition::FORM_EMAIL,
					column: 'email',
					inTable: true,
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'roomType',
					label: $l10n->t('Room type'),
					form: FieldDefinition::FORM_COMBOBOX,
					column: 'room_type',
					inTable: true,
					filter: FieldDefinition::FILTER_SELECT,
					options: '/rooms/types',
				),
				new FieldDefinition(
					name: 'capacity',
					label: $l10n->t('Capacity'),
					form: FieldDefinition::FORM_NUMBER,
					type: FieldDefinition::TYPE_INT,
					column: 'capacity',
					inTable: true,
					min: 0,
					filter: FieldDefinition::FILTER_RANGE,
				),
				new FieldDefinition(
					name: 'contactPersonUserId',
					label: $l10n->t('Contact person'),
					form: FieldDefinition::FORM_USER,
					column: 'contact_person_user_id',
					filter: FieldDefinition::FILTER_USER,
				),
				new FieldDefinition(
					name: 'roomNumber',
					label: $l10n->t('Room number'),
					form: FieldDefinition::FORM_TEXT,
					column: 'room_number',
					filter: FieldDefinition::FILTER_TEXT,
				),
				new FieldDefinition(
					name: 'hasPhone',
					label: $l10n->t('Has phone'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'has_phone',
					filter: FieldDefinition::FILTER_BOOL,
				),
				new FieldDefinition(
					name: 'hasVideoConferencing',
					label: $l10n->t('Has video conferencing'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'has_video_conferencing',
					filter: FieldDefinition::FILTER_BOOL,
				),
				new FieldDefinition(
					name: 'hasTv',
					label: $l10n->t('Has TV'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'has_tv',
					filter: FieldDefinition::FILTER_BOOL,
				),
				new FieldDefinition(
					name: 'hasProjector',
					label: $l10n->t('Has projector'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'has_projector',
					filter: FieldDefinition::FILTER_BOOL,
				),
				new FieldDefinition(
					name: 'hasWhiteboard',
					label: $l10n->t('Has whiteboard'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'has_whiteboard',
					filter: FieldDefinition::FILTER_BOOL,
				),
				new FieldDefinition(
					name: 'isWheelchairAccessible',
					label: $l10n->t('Wheelchair accessible'),
					form: FieldDefinition::FORM_BOOL,
					type: FieldDefinition::TYPE_BOOL,
					column: 'is_wheelchair_accessible',
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
