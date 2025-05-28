<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomerReportMultiSheet implements WithMultipleSheets
{
    use Exportable;

    protected $date_interval;
    protected $has_service_type;
    protected $has_branch;
    
    public function __construct(array $date_interval, $has_service_type, $has_branch)
    {
        $this->date_interval = $date_interval;
        $this->has_service_type = $has_service_type == 1 ? true : false;
        $this->has_branch = $has_branch == 1 ? true : false;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets[] = new CustomerReportByDailyFreqSheet($this->date_interval, $this->has_service_type, $this->has_branch);
        $sheets[] = new CustomerReportByFreqSheet($this->date_interval, $this->has_service_type, $this->has_branch);

        return $sheets;
    }
}