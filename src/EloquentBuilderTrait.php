<?php

namespace Ninhtqse\Bruno;

use DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait EloquentBuilderTrait
{
    /**
     * Apply resource options to a query builder
     * @param  Builder $queryBuilder
     * @param  array  $options
     * @return Builder
     */
    protected function applyResourceOptions(Builder $queryBuilder, array $options = [])
    {
        if (empty($options)) {
            return $queryBuilder;
        }

        extract($options);

        if (isset($includes)) {
            if (!is_array($includes)) {
                throw new InvalidArgumentException('Includes should be an array.');
            }

            $queryBuilder->with($includes);
        }

        if (isset($filter_groups)) {
            $filterJoins = $this->applyFilterGroups($queryBuilder, $filter_groups,[],$filter_or);
        }

        if($not_fields){
            $all_fields = \Schema::getColumnListing($queryBuilder->getModel()->getTable());
            $fields_new = array_diff($all_fields,$not_fields);
            $queryBuilder->select($fields_new);
        }else if($fields){
            $queryBuilder->select($fields);
        }

        if (isset($sort)) {
            if (!is_array($sort)) {
                throw new InvalidArgumentException('Sort should be an array.');
            }

            if (!isset($filterJoins)) {
                $filterJoins = [];
            }

            $sortingJoins = $this->applySorting($queryBuilder, $sort, $filterJoins);
        }

        if (isset($limit)) {
            $queryBuilder->limit($limit);
        }

        if (isset($page)) {
            if (! isset($limit)) {
                throw new InvalidArgumentException('A limit is required when using page.');
            }

            $queryBuilder->offset(($page - 1) * $limit);
        }

        if (isset($distinct)) {
            $queryBuilder->distinct();
        }

        if (isset($skip)) {
            $queryBuilder->skip($skip);
        }

        if (isset($take)) {
            $queryBuilder->take($take);
        }
        return $queryBuilder;
    }

    /**
     * @param Builder $queryBuilder
     * @param array $filterGroups
     * @param array $previouslyJoined
     * @return array
     */
     protected function applyFilterGroups(Builder $queryBuilder, array $filterGroups = [], array $previouslyJoined = [],array $filterOr = [])
    {
        // filter các quan hệ (sửa tạm)
        $joins = [];
        foreach ($filterGroups as $key => $group) {
            $or = $group['or'];
            $filters = $group['filters'];
            $first = current($filters);
            if(strrpos(@$first['key'], '.') !== false) {
                $tmp = explode('.', $first['key']);
                $first['key'] = $tmp[1];
                $whereHas = 'whereHas';
                if(@$filterOr[$key - 1]){
                    $whereHas = 'orWhereHas';
                }
                $queryBuilder->$whereHas($tmp[0], function ($query) use ($first, $or, &$joins,$filters) {
                    foreach($filters as $filter){
                        $exp = \explode('.',$filter['key']);
                        $filter['key'] = $exp[1];
                        $this->applyFilter($query, $filter, $or, $joins);
                    }
                    // $query->where($tmp[1], '=', $first['value']);
                });
            } else {
                $where = 'where';
                if(@$filterOr[$key - 1]){
                    $where = 'orWhere';
                }
                $queryBuilder->$where(function (Builder $query) use ($filters, $or, &$joins) {
                    foreach ($filters as $filter) {
                        $this->applyFilter($query, $filter, $or, $joins);
                    }
                });
            }
        }

        foreach(array_diff($joins, $previouslyJoined) as $join) {
            $this->joinRelatedModelIfExists($queryBuilder, $join);
        }

        return $joins;


        // $joins = [];
        // foreach ($filterGroups as $group) {
        //     $or = $group['or'];
        //     $filters = $group['filters'];

        //     $queryBuilder->where(function (Builder $query) use ($filters, $or, &$joins) {
        //         foreach ($filters as $filter) {
        //             $this->applyFilter($query, $filter, $or, $joins);
        //         }
        //     });
        // }

        // foreach(array_diff($joins, $previouslyJoined) as $join) {
        //     $this->joinRelatedModelIfExists($queryBuilder, $join);
        // }

        // return $joins;
    }

    /**
     * @param Builder $queryBuilder
     * @param array $filter
     * @param bool|false $or
     * @param array $joins
     */
    protected function applyFilter(Builder $queryBuilder, array $filter, $or, array &$joins)
    {
        //update version PHP 8.0
        if(!$or){
            $or = false;
        }
        // Destructure Shorthand Filtering Syntax if filter is Shorthand
        if (! array_key_exists('key', $filter) && count($filter) >= 3) {
            $filter = [
                'key'      => ($filter[0] ?: null),
                'operator' => ($filter[1] ?: null),
                'value'    => ($filter[2] ?: null),
                'not'      => (array_key_exists(3, $filter) ? $filter[3] : null),
            ];
        }

        // $value, $not, $key, $operator
        extract($filter);

        //convert string to boolean
        if ($not) {
            $not = filter_var($not, FILTER_VALIDATE_BOOLEAN);
        }
        
        $dbType = $queryBuilder->getConnection()->getDriverName();

        $table = $queryBuilder->getModel()->getTable();

        if ($value === 'null' || $value === '') {
            $method = $not ? 'WhereNotNull' : 'WhereNull';

            call_user_func([$queryBuilder, $method], sprintf('%s.%s', $table, $key));
        } else {
            $method = filter_var($or, FILTER_VALIDATE_BOOLEAN) ? 'orWhere' : 'where';
            $clauseOperator = null;
            $databaseField = null;

            switch($operator) {
                case 'ct':
                case 'sw':
                case 'ew':
                    $valueString = [
                        'ct' => '%'.$value.'%', // contains
                        'ew' => '%'.$value, // ends with
                        'sw' => $value.'%' // starts with
                    ];

                    $castToText = (($dbType === 'postgres') ? 'TEXT' : 'CHAR');
                    $databaseField = DB::raw(sprintf('CAST(%s.%s AS ' . $castToText . ')', $table, $key));
                    if($dbType === 'sqlsrv') {
                        $databaseField = $key;
                    }
                    $clauseOperator = ($not ? 'NOT':'') . (($dbType === 'postgres') ? 'ILIKE' : 'LIKE');
                    $value = $valueString[$operator];
                    break;
                case 'eq':
                default:
                    $clauseOperator = $not ? '!=' : '=';
                    break;
                case 'gt':
                    $clauseOperator = $not ? '<' : '>';
                    break;
                case 'gte':
                    $clauseOperator = $not ? '<' : '>=';
                    break;
                case 'lte':
                    $clauseOperator = $not ? '>' : '<=';
                    break;
                case 'lt':
                    $clauseOperator = $not ? '>' : '<';
                    break;
                case 'in':
                    if ($or === true) {
                        $method = $not === true ? 'orWhereNotIn' : 'orWhereIn';
                    } else {
                        $method = $not === true ? 'whereNotIn' : 'whereIn';
                    }
                    $clauseOperator = false;
                    break;
                case 'bt':
                    if ($or === true) {
                        $method = $not === true ? 'orWhereNotBetween' : 'orWhereBetween';
                    } else {
                        $method = $not === true ? 'whereNotBetween' : 'whereBetween';
                    }
                    $clauseOperator = false;
                    break;
                case 'eqd':
                    if ($or === true) {
                        $method = $not === true ? 'orWhereNotDate' : 'orWhereDate';
                    } else {
                        $method = $not === true ? 'whereDate' : 'whereDate';
                    }
                    $clauseOperator = $not ? '!=' : '=';
                    break;
                case 'eqy':
                    if ($or === true) {
                        $method = $not === true ? 'orWhereNotYear' : 'orWhereYear';
                    } else {
                        $method = $not === true ? 'whereYear' : 'whereYear';
                    }
                    $clauseOperator = $not ? '!=' : '=';
                    break;
                case 'eqm':
                    if ($or === true) {
                        $method = $not === true ? 'orWhereNotMonth' : 'orWhereMonth';
                    } else {
                        $method = $not === true ? 'whereMonth' : 'whereMonth';
                    }
                    $clauseOperator = $not ? '!=' : '=';
                    break;
            }

            // If we do not assign database field, the customer filter method
            // will fail when we execute it with parameters such as CAST(%s AS TEXT)
            // key needs to be reserved
            if (is_null($databaseField)) {
                $databaseField = sprintf('%s.%s', $table, $key);
            }

            $customFilterMethod = $this->hasCustomMethod('filter', $key);
            if ($customFilterMethod) {
                call_user_func_array([$this, $customFilterMethod], [
                    $queryBuilder,
                    $method,
                    $clauseOperator,
                    $value,
                    $clauseOperator // @deprecated. Here for backwards compatibility
                ]);

                // column to join.
                // trying to join within a nested where will get the join ignored.
                $joins[] = $key;
            } else {
                // In operations do not have an operator
                if (in_array($operator, ['in', 'bt'])) {
                    call_user_func_array([$queryBuilder, $method], [
                        $databaseField, \json_decode($value,true)
                    ]);
                } else {
                    call_user_func_array([$queryBuilder, $method], [
                        $databaseField, $clauseOperator, $value
                    ]);
                }
            }
        }
    }

    /**
     * @param Builder $queryBuilder
     * @param array $sorting
     * @param array $previouslyJoined
     * @return array
     */
    protected function applySorting(Builder $queryBuilder, array $sorting, array $previouslyJoined = [])
    {
        $joins = [];
        foreach($sorting as $sortRule) {
            if (is_array($sortRule)) {
                $key = $sortRule['key'];
                $direction = mb_strtolower($sortRule['direction']) === 'asc' ? 'ASC' : 'DESC';
            } else {
                $key = $sortRule;
                $direction = 'ASC';
            }

            $customSortMethod = $this->hasCustomMethod('sort', $key);
            if ($customSortMethod) {
                $joins[] = $key;

                call_user_func([$this, $customSortMethod], $queryBuilder, $direction);
            } else {
                $queryBuilder->orderBy($key, $direction);
            }
        }

        foreach(array_diff($joins, $previouslyJoined) as $join) {
            $this->joinRelatedModelIfExists($queryBuilder, $join);
        }

        return $joins;
    }

    /**
     * @param $type
     * @param $key
     * @return bool|string
     */
    private function hasCustomMethod($type, $key)
    {
        $methodName = sprintf('%s%s', $type, Str::studly($key));
        if (method_exists($this, $methodName)) {
            return $methodName;
        }

        return false;
    }

    /**
     * @param Builder $queryBuilder
     * @param $key
     */
    private function joinRelatedModelIfExists(Builder $queryBuilder, $key)
    {
        $model = $queryBuilder->getModel();
        // relationship exists, join to make special sort
        if (method_exists($model, $key)) {
            $relation = $model->$key();
            $type = 'inner';
            if ($relation instanceof BelongsTo) {
                $queryBuilder->join(
                    $relation->getRelated()->getTable(),
                    $relation->getQualifiedForeignKey(),
                    '=',
                    $relation->getQualifiedOwnerKeyName(),
                    $type
                );
            } elseif ($relation instanceof BelongsToMany) {
                $queryBuilder->join(
                    $relation->getTable(),
                    $relation->getQualifiedParentKeyName(),
                    '=',
                    $relation->getQualifiedForeignPivotKeyName(),
                    $type
                );
                $queryBuilder->join(
                    $relation->getRelated()->getTable(),
                    $relation->getRelated()->getTable().'.'.$relation->getRelated()->getKeyName(),
                    '=',
                    $relation->getQualifiedRelatedPivotKeyName(),
                    $type
                );
            } else {
                $queryBuilder->join(
                    $relation->getRelated()->getTable(),
                    $relation->getQualifiedParentKeyName(),
                    '=',
                    $relation->getQualifiedForeignPivotKeyName(),
                    $type
                );
            }
            $table = $model->getTable();
            $queryBuilder->select(sprintf('%s.*', $table));
        }
    }
}
