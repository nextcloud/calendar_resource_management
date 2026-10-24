<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Schema;

use OCA\CalendarResourceManagement\Db\Criterion;
use OCA\CalendarResourceManagement\Db\Relation;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Persisted (has `$column`) or virtual (no column, reached via `$relation`).
 * The frontend gets its subset through {@see EntityDefinition::frontendDefinition()}.
 */
final class FieldDefinition {
	public const TYPE_STRING = 'string';
	public const TYPE_INT = 'int';
	public const TYPE_BOOL = 'bool';

	public const FORM_TEXT = 'text';
	public const FORM_NUMBER = 'number';
	public const FORM_BOOL = 'bool';
	public const FORM_EMAIL = 'email';
	public const FORM_COMBOBOX = 'combobox';
	public const FORM_USER = 'user';
	public const FORM_TEXTAREA = 'textarea';
	public const FORM_SELECT = 'select';

	public const FILTER_TEXT = 'text';
	public const FILTER_NUMBER = 'number';
	public const FILTER_RANGE = 'range';
	public const FILTER_SELECT = 'select';
	public const FILTER_BOOL = 'bool';
	public const FILTER_USER = 'user';

	/**
	 * @param string $name the API/JSON key, camelCase
	 * @param string $label the translated, user-visible label
	 * @param string $type one of the `TYPE_*` constants
	 * @param string|null $form one of the `FORM_*` constants, or null when not in the form
	 * @param string|null $column the DB column under the entity it belongs to, or null when the field is virtual
	 * @param bool $inTable whether the list table shows the field as a column
	 * @param bool $required whether the form must have a value for it
	 * @param int|null $min inclusive lower bound for numeric input
	 * @param int|null $max inclusive upper bound for numeric input
	 * @param string|null $filter one of the `FILTER_*` constants, or null when not filterable
	 * @param RelationDefinition|null $relation the path to the field's value, for a field with no column
	 * @param string|null $cascade the field this one populates in the form
	 * @param string|list<array{id: mixed, label: string}|string>|null $options a select's options: an OCS API path, or the list itself
	 * @throws \LogicException on an invalid declaration
	 */
	public function __construct(
		public readonly string $name,
		public readonly string $label,
		public readonly string $type = self::TYPE_STRING,
		public readonly ?string $form = null,
		public readonly ?string $column = null,
		public readonly bool $inTable = false,
		public readonly bool $required = false,
		public readonly ?int $min = null,
		public readonly ?int $max = null,
		public readonly ?string $filter = null,
		public readonly ?RelationDefinition $relation = null,
		public readonly ?string $cascade = null,
		public readonly string|array|null $options = null,
	) {
		if ($column === null && $relation === null) {
			throw new \LogicException('Field ' . $name . ' has neither a column nor a relation, so it can be neither persisted nor resolved');
		}
		if ($column !== null && $relation !== null) {
			throw new \LogicException('Field ' . $name . ' has a column of its own, so it is not reached through a relation');
		}
		if ($relation !== null && $relation->through === []) {
			throw new \LogicException('Relation field ' . $name . ' must name the tables it is reached through');
		}
		if ($inTable && $column === null) {
			throw new \LogicException('Field ' . $name . ' has no column for the table to show');
		}
		if (($form === self::FORM_SELECT || $filter === self::FILTER_SELECT) && $options === null) {
			throw new \LogicException('Select field ' . $name . ' must name its options');
		}
		if ($filter === self::FILTER_BOOL && $type !== self::TYPE_BOOL) {
			throw new \LogicException('Field ' . $name . ' is not a boolean, so it cannot be filtered as one');
		}
	}

	/**
	 * Whether the field can be narrowed by.
	 *
	 * @return bool true when a filter control and operator are declared
	 */
	public function isFilterable(): bool {
		return $this->filter !== null;
	}

	/**
	 * Whether the create/edit form offers the field.
	 *
	 * @return bool true when a form control is declared
	 */
	public function isInForm(): bool {
		return $this->form !== null;
	}

	/**
	 * The query builder parameter type matching the field's data type.
	 *
	 * @return int|string an `IQueryBuilder::PARAM_*` constant
	 */
	public function paramType(): int|string {
		return match ($this->type) {
			self::TYPE_INT => IQueryBuilder::PARAM_INT,
			self::TYPE_BOOL => IQueryBuilder::PARAM_BOOL,
			default => IQueryBuilder::PARAM_STR,
		};
	}

	/**
	 * The comparison the field's filter control implies.
	 *
	 * Ranges are split into `OP_GTE`/`OP_LTE` by {@see EntityDefinition::criteria()}.
	 *
	 * @return string one of the `Criterion::OP_*` constants
	 * @throws \LogicException when the filter kind has no single operator
	 */
	public function operator(): string {
		return match ($this->filter) {
			self::FILTER_TEXT => Criterion::OP_LIKE,
			self::FILTER_NUMBER, self::FILTER_SELECT, self::FILTER_BOOL, self::FILTER_USER => Criterion::OP_EQ,
			default => throw new \LogicException('Filter kind ' . $this->filter . ' has no single operator'),
		};
	}

	/**
	 * The column target column and joins that reach it.
	 *
	 * @return array{0: string, 1: Relation[]} the column and the hops to reach it
	 */
	public function target(): array {
		if ($this->column !== null) {
			return [$this->column, []];
		}

		return [$this->relation->column, $this->relation->through];
	}

	/**
	 * A request value, with blank meaning absent.
	 *
	 * The controllers' typed parameters already cast it to the declared type.
	 *
	 * @param mixed $value the request value
	 * @return mixed the value, or null when it is absent or blank
	 */
	public function coerce(mixed $value): mixed {
		return $value === '' ? null : $value;
	}

	/**
	 * The field as the admin UI's table column descriptor.
	 *
	 * @return array{key: string, label: string}
	 */
	public function toColumnArray(): array {
		return [
			'key' => $this->name,
			'label' => $this->label,
		];
	}

	/**
	 * The field as the admin UI's form field descriptor.
	 *
	 * `type` is the form control, not the data type.
	 *
	 * @return array<string, mixed>
	 */
	public function toFormArray(): array {
		$field = [
			'key' => $this->name,
			'label' => $this->label,
			'type' => $this->form,
			'required' => $this->required,
		];

		if ($this->options !== null) {
			$field['options'] = $this->options;
		}
		if ($this->min !== null || $this->max !== null) {
			// Drives the client-side bounds check in FieldValidation.js.
			$field['range'] = array_filter(
				['from' => $this->min, 'to' => $this->max],
				static fn ($bound) => $bound !== null,
			);
		}
		if ($this->column === null) {
			// Virtual: not persisted, so it must stay out of the request payload.
			$field['transient'] = true;
		}
		if ($this->cascade !== null) {
			$field['cascade'] = $this->cascade;
		}

		return $field;
	}

	/**
	 * The field as the admin UI's filter descriptor.
	 *
	 * `type` is the filter kind; the frontend supplies `bool` options.
	 *
	 * @return array{key: string, label: string, type: string, options?: string|list<mixed>}
	 */
	public function toFilterArray(): array {
		$filter = [
			'key' => $this->name,
			'label' => $this->label,
			'type' => $this->filter,
		];

		if ($this->options !== null) {
			$filter['options'] = $this->options;
		}

		return $filter;
	}

}
