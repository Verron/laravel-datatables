<?php
/**
 * Copyright (c) DevelMe LLC 2015
 * User: verronknowles
 * Date: 10/16/15
 * Time: 1:10 PM
 */

namespace yajra\Datatables\Services;


use yajra\Datatables\Contracts\DataTableFilter;
use yajra\Datatables\Engines\BaseEngine;

abstract class Filter implements DataTableFilter
{
    public function getSearchText($column_name, BaseEngine $datatable)
    {
        $column = $this->getSearchColumn($column_name, $datatable);

        if (!is_null($column)) {
            return "%".$column['search']['value']."%";

        }

        return '';
    }

    /**
     * Get the column information from the ajax request
     *
     * @param $column_name
     * @param BaseEngine $datatable
     * @return null
     */
    public function getSearchColumn($column_name, BaseEngine $datatable)
    {
        $columns = $datatable->request->get('columns');

        foreach ($columns as $column) {
            if (array_key_exists("name", $column) && $column['name'] == $column_name) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Get order orientation
     * 
     * @param BaseEngine $datatable
     * @return mixed
     */
    public function getOrderOrientation(BaseEngine $datatable)
    {
        return $datatable->request->get('order')[0]['dir'];
    }
}