<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Extension;

use Cake\ORM\Table;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Action\CrudAction;

/**
 * Shared sort field validation for sort extensions.
 *
 * Uses the `sortableFields` config key of the extension.
 * Actions without a table (not a CrudAction) accept only `sortableFields` entries.
 */
trait SortableTrait
{
    /**
     * Returns a safe sort field, or null if the field is not allowed.
     *
     * When `sortableFields` is set, only listed fields are allowed. Otherwise only
     * columns of the action table that the entity does not hide are allowed. Columns of
     * the action table are prefixed with the table alias.
     *
     * @param \CakeDC\Api\Service\Action\Action|null $action Action instance.
     * @param mixed $field Requested sort field.
     * @return string|null
     */
    protected function _sortField(?Action $action, $field): ?string
    {
        if (!is_string($field) || $field === '') {
            return null;
        }
        $table = $action instanceof CrudAction ? $action->getTable() : null;
        $alias = $table instanceof Table ? $table->getAlias() : null;

        $sortableFields = (array)$this->getConfig('sortableFields');
        if ($sortableFields !== []) {
            $candidates = [$field];
            if ($alias !== null) {
                if (strpos($field, '.') === false) {
                    $candidates[] = $alias . '.' . $field;
                } elseif (strpos($field, $alias . '.') === 0) {
                    $candidates[] = substr($field, strlen($alias) + 1);
                }
            }
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $sortableFields, true)) {
                    return $this->_qualifyField($table, $candidate);
                }
            }

            return null;
        }

        if (!$table instanceof Table) {
            return null;
        }
        $column = $field;
        if (strpos($field, '.') !== false) {
            [$prefix, $column] = explode('.', $field, 2);
            if ($prefix !== $alias) {
                return null;
            }
        }
        if (!$table->getSchema()->hasColumn($column)) {
            return null;
        }
        // a column the entity hides from output is not sortable by default: the row order
        // would reveal its values; sortableFields can still allow it explicitly
        if (in_array($column, $table->newEmptyEntity()->getHidden(), true)) {
            return null;
        }

        return $alias . '.' . $column;
    }

    /**
     * Prefixes an unqualified column of the table with the table alias.
     * Other fields, like computed select aliases, are returned as is.
     *
     * @param \Cake\ORM\Table|null $table Table instance.
     * @param string $field Field name.
     * @return string
     */
    protected function _qualifyField(?Table $table, string $field): string
    {
        if ($table instanceof Table && strpos($field, '.') === false && $table->getSchema()->hasColumn($field)) {
            return $table->getAlias() . '.' . $field;
        }

        return $field;
    }

    /**
     * Returns a normalized sort direction. Invalid values fall back to `asc`.
     *
     * @param mixed $direction Requested direction.
     * @return string
     */
    protected function _sortDirection($direction): string
    {
        if (is_string($direction) && in_array(strtolower($direction), ['asc', 'desc'], true)) {
            return strtolower($direction);
        }

        return 'asc';
    }
}
