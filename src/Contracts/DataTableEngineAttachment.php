<?php

namespace yajra\Datatables\Contracts;

use yajra\Datatables\Engines\BaseEngine;

interface DataTableEngineAttachment
{

    /**
     * Apply additions to datatables.
     *
     * @param BaseEngine $datatable
     * @return BaseEngine $datatable
     */
    public function attach(BaseEngine $datatable);

}