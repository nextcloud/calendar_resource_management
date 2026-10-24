<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Schema;

use OCA\CalendarResourceManagement\Db\Criterion;

abstract class EntityDefinition {
	/**
	 * @param string $key the schema key, which is also the admin UI's tab id
	 * @param string $table the DB table, never sent to the frontend
	 * @param string $path the entity's path under the app's OCS API base
	 * @param string $label the translated plural label
	 * @param string $singular the translated singular label, for "Add {name}"
	 * @param bool $cascades whether deleting a record deletes child records
	 * @param FieldDefinition[] $fields the entity's fields, in display order
	 */
	public function __construct(
		public readonly string $key,
		public readonly string $table,
		public readonly string $path,
		public readonly string $label,
		public readonly string $singular,
		public readonly bool $cascades,
		public readonly array $fields,
	) {
	}

	/**
	 * The fields that can be narrowed by.
	 *
	 * @return FieldDefinition[]
	 */
	public function filterableFields(): array {
		return array_values(array_filter($this->fields, static fn (FieldDefinition $field) => $field->isFilterable()));
	}

	/**
	 * Turn request-supplied filter values into query criteria.
	 *
	 * @param array<string, mixed> $filters request values keyed by parameter name
	 * @return Criterion[] the conditions to hand to the mapper
	 */
	public function criteria(array $filters): array {
		$criteria = [];

		foreach ($this->filterableFields() as $field) {
			if ($field->filter === FieldDefinition::FILTER_RANGE) {
				$bounds = [];
				foreach ([Criterion::OP_GTE => 'Min', Criterion::OP_LTE => 'Max'] as $op => $suffix) {
					$bound = $field->coerce($filters[$field->name . $suffix] ?? null);
					if ($bound !== null) {
						$bounds[$op] = $bound;
					}
				}
				if ($bounds === []) {
					continue;
				}

				[$column, $joins] = $field->target();
				foreach ($bounds as $op => $bound) {
					$criteria[] = new Criterion($column, $op, $bound, $field->paramType(), $joins);
				}
				continue;
			}

			$value = $field->coerce($filters[$field->name] ?? null);
			if ($value === null) {
				continue;
			}

			[$column, $joins] = $field->target();
			$criteria[] = new Criterion($column, $field->operator(), $value, $field->paramType(), $joins);
		}

		return $criteria;
	}

	/**
	 * One of this entity's fields, by name.
	 *
	 * @param string $name the field's name, as declared in its entity
	 * @return FieldDefinition the field
	 * @throws \LogicException when this entity declares no such field
	 */
	public function field(string $name): FieldDefinition {
		foreach ($this->fields as $field) {
			if ($field->name === $name) {
				return $field;
			}
		}

		throw new \LogicException('Entity ' . $this->key . ' has no field ' . $name);
	}

	/**
	 * Entity schema for frontend.
	 *
	 * @return array<string, mixed>
	 */
	public function frontendDefinition(): array {
		$columns = [];
		$fields = [];
		$filters = [];

		foreach ($this->fields as $field) {
			if ($field->inTable) {
				$columns[] = $field->toColumnArray();
			}
			if ($field->isInForm()) {
				$fields[] = $field->toFormArray();
			}
			if ($field->isFilterable()) {
				$filters[] = $field->toFilterArray();
			}
		}

		return [
			'path' => $this->path,
			'label' => $this->label,
			'singular' => $this->singular,
			'cascades' => $this->cascades,
			'columns' => $columns,
			'fields' => $fields,
			'filters' => $filters,
		];
	}
}
