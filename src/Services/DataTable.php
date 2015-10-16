<?php

namespace yajra\Datatables\Services;

use Illuminate\Contracts\View\Factory;
use yajra\Datatables\Contracts\DataTableButtonsContract;
use yajra\Datatables\Contracts\DataTableColumn;
use yajra\Datatables\Contracts\DataTableContract;
use yajra\Datatables\Contracts\DataTableEngineAttachment;
use yajra\Datatables\Contracts\DataTableFilter;
use yajra\Datatables\Contracts\DataTableScopeContract;
use yajra\Datatables\Datatables;

abstract class DataTable implements DataTableContract, DataTableButtonsContract
{
    /**
     * @var \yajra\Datatables\Datatables
     */
    protected $datatables;

    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $viewFactory;

    /**
     * Datatables print preview view.
     *
     * @var string
     */
    protected $printPreview;

    /**
     * List of columns to be exported.
     *
     * @var string|array
     */
    protected $exportColumns = '*';

    /**
     * Query scopes.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * DataTable Filters.
     *
     * @var array
     */
    protected $filters = [];

    /**
     * DataTable Columns.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * DataTable Columns for Query Scope
     *
     * @var array
     */
    protected $query_columns = ['*'];

    /**
     * DataTable Attachments.
     *
     * @var array
     */
    protected $attachments = [];

    /**
     * @param \yajra\Datatables\Datatables $datatables
     * @param \Illuminate\Contracts\View\Factory $viewFactory
     */
    public function __construct(Datatables $datatables, Factory $viewFactory)
    {
        $this->datatables  = $datatables;
        $this->viewFactory = $viewFactory;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajax()
    {
        return $this->applyAttachments($this->datatables->of($this->query()))->make(true);
    }

    /**
     * Render view.
     *
     * @param $view
     * @param array $data
     * @param array $mergeData
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function render($view, $data = [], $mergeData = [])
    {
        if ($this->datatables->getRequest()->ajax()) {
            return $this->ajax();
        }

        switch ($this->datatables->getRequest()->get('action')) {
            case 'excel':
                return $this->excel();

            case 'csv':
                return $this->csv();

            case 'pdf':
                return $this->pdf();

            case 'print':
                return $this->printPreview();

            default:
                return $this->viewFactory->make($view, $data, $mergeData)->with('dataTable', $this->html());
        }
    }

    /**
     * Export results to Excel file.
     *
     * @return mixed
     */
    public function excel()
    {
        return $this->buildExcelFile()->download('xls');
    }

    /**
     * Build excel file and prepare for export.
     *
     * @return mixed
     */
    protected function buildExcelFile()
    {
        return app('excel')->create($this->filename(), function ($excel) {
            $excel->sheet('exported-data', function ($sheet) {
                $sheet->fromArray($this->getDataForExport());
            });
        });
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'export_' . time();
    }

    /**
     * Get mapped columns versus final decorated output.
     *
     * @return array
     */
    protected function getDataForExport()
    {
        $decoratedData = $this->getAjaxResponseData();

        return array_map(function ($row) {
            if (is_array($this->exportColumns)) {
                return array_only($row, $this->exportColumns);
            }

            return $row;
        }, $decoratedData);
    }

    /**
     * Get decorated data as defined in datatables ajax response.
     *
     * @return mixed
     */
    protected function getAjaxResponseData()
    {
        $this->datatables->getRequest()->merge(['length' => -1]);

        $response = $this->ajax();
        $data     = $response->getData(true);

        return $data['data'];
    }

    /**
     * Export results to CSV file.
     *
     * @return mixed
     */
    public function csv()
    {
        return $this->buildExcelFile()->download('csv');
    }

    /**
     * Export results to PDF file.
     *
     * @return mixed
     */
    public function pdf()
    {
        return $this->buildExcelFile()->download('pdf');
    }

    /**
     * Display printable view of datatables.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function printPreview()
    {
        $data = $this->getAjaxResponseData();
        $view = $this->printPreview ?: 'datatables::print';

        return $this->viewFactory->make($view, compact('data'));
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return mixed
     */
    public function html()
    {
        return $this->builder();
    }

    /**
     * Get Datatables Html Builder instance.
     *
     * @return \yajra\Datatables\Html\Builder
     */
    public function builder()
    {
        return $this->datatables->getHtmlBuilder();
    }

    /**
     * Get Datatables Request instance.
     *
     * @return \yajra\Datatables\Request
     */
    public function request()
    {
        return $this->datatables->getRequest();
    }

    /**
     * Add basic array query scopes.
     *
     * @param \yajra\Datatables\Contracts\DataTableScopeContract $scope
     * @return $this
     */
    public function addScope(DataTableScopeContract $scope)
    {
        $this->scopes[] = $scope;

        return $this;
    }

    /**
     * Add basic array query scopes.
     *
     * @param DataTableFilter $filter
     * @return $this
     */
    public function addFilter(DataTableFilter $filter)
    {
        // Nothing Special about a filter just yet.
        // Just ensure it gets attached to
        return $this->addAttachment($filter);
    }

    /**
     * Add basic array query scopes.
     *
     * @param DataTableColumn $column
     * @return $this
     */
    public function addColumn(DataTableColumn $column)
    {
        if ($column->isAttachable()) {
            $this->injectColumn($column);
        }

        return $this->addAttachment($column);
    }

    /**
     * Add Engine Attachment
     *
     * @param DataTableEngineAttachment $attachment
     * @return $this
     */
    public function addAttachment(DataTableEngineAttachment $attachment)
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Apply datatable attachments.
     *
     * @param \yajra\Datatables\Contracts\DataTableEngineContract $datatable
     * @return \yajra\Datatables\Contracts\DataTableEngineContract $datatable
     */
    public function applyAttachments($datatable)
    {
        foreach ($this->attachments as $attachment) {
            $datatable = $attachment->attach($datatable);
        }

        return $datatable;
    }

    /**
     * Apply query scopes.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @return mixed
     */
    public function applyScopes($query)
    {
        foreach ($this->scopes as $scope) {
            $scope->apply($query);
        }

        return $query;
    }


    /**
     * Get columns for query
     *
     */
    public function getQueryColumns()
    {
        return $this->query_columns;
    }

    /**
     * Set columns for Query
     * @param array $columns
     *
     * @return $this
     */
    public function setQueryColumns(Array $columns)
    {
        $this->query_columns = $columns;

        return $this;
    }

    /**
     * Get columns for datatables
     *
     */
    public function getColumns()
    {
        return $this->columns->toArray();
    }

    /**
     * Set columns for datatables
     *
     */
    public function setColumns(Array $columns)
    {
        $this->columns = collect($columns);

        return $this;
    }

    /**
     * Append Columns for datatables
     *
     * @param Column $column
     *
     */
    protected function appendColumn(Column $column)
    {
        $this->columns->put($column->getName(), $this->fetchColumnDetails($column));
    }

    /**
     * Inject column into current column html build
     *
     * @param DataTableColumn $column
     */
    public function injectColumn(DataTableColumn $column)
    {
        if ($column->getSort() == -1) {
            $this->appendColumn($column);
        } else {
            $this->insertColumn($column);
        }
    }

    /**
     * Insert column at it's desire sort
     *
     * @param $column
     */
    protected function insertColumn($column)
    {
        $sort = $column->getSort();
        $spot = 0;
        $newCollection = collect();
        $inserted = false;

        foreach($this->columns->toArray() as $pos => $col) {
            if (!$inserted && $sort == $spot) {
                $newCollection->put($column->getName(), $this->addColumnDetail($this->fetchColumnDetails($column)));
                $inserted = true;
            }

            $newCollection->put($pos, $col);
            $spot++;
        }

        $this->columns = $newCollection;
    }

    /**
     * Build datatables structure for column
     *
     * @param DataTableColumn $column
     * @return array
     */
    private function fetchColumnDetails(DataTableColumn $column)
    {
        return array_merge([
            'name' => $column->getName(),
            'data' => $column->getData(),
            'title' => $column->getTitle(),
            'orderable' => $column->isOrderable(),
            'searchable' => $column->isSearchable()
        ], $column->additionalDetails());
    }

    /**
     * Add an additional searchable class if it is not defined
     *
     * @param array $column
     * @return array
     */
    protected function addColumnDetail(Array $column)
    {
        if ((array_key_exists('searchable', $column) && ($column['searchable'] == true)) || !array_key_exists('searchable', $column)) {
            if (!array_key_exists('className', $column)) {
                $column['className'] = 'searchable input';
            }
        }

        return $column;
    }

    /**
     * Add datatables array if a string is given for a column
     *
     * @param $column
     * @return array
     */
    protected function addFullColumnDetail($column) {
        return [
            'name' => $column,
            'data' => $column,
            'title' => ucwords(strtolower(str_replace("_", " ", snake_case($column)))),
            'className' => 'searchable input',
            'searchable' => true,
            'orderable' => true
        ];
    }
}
