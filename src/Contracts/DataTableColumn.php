<?php

namespace yajra\Datatables\Contracts;

interface DataTableColumn extends DataTableEngineAttachment
{
    /**
     * Get the column name
     *
     * @return string
     */
    public function getName();

    /**
     * Get the column title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Get the column data
     *
     * @return string
     */
    public function getData();

    /**
     * Can the column be searched
     *
     * @return bool
     */
    public function isSearchable();

    /**
     * Can the column be ordered
     *
     * @return bool
     */
    public function isOrderable();

    /**
     * Should attach to select list
     *
     * @return bool
     */
    public function isAttachable();

    /**
     * Should be exported to other formats
     *
     * @return bool
     */
    public function isExportable();

    /**
     * Get the sort position
     *
     * @return int
     */
    public function getSort();
}