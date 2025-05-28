<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\Appointment;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IncomeReportByUsersExport implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $date_interval;
    public $start_row = 3;

    public function __construct(array $date_interval)
    {
        $this->date_interval = $date_interval;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                //date header
                $sheet->mergeCells("B1:D1");
                $sheet->mergeCells("E1:G1");
                $sheet->setCellValue("B1", $this->date_interval[0].' - '. $this->date_interval[1]);
                $sheet->setCellValue("E1", 'Орлогын тайлан');


                $styleArray = [
                    "borders" => [
                        "allBorders" => [
                            "borderStyle" =>
                                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            "color" => ["argb" => "000000"],
                        ],
                    ],
                ];

                $cellRange = "A".$this->start_row.":".$sheet->getHighestColumn().$sheet->getHighestRow(); // All headers
                $event->sheet
                    ->getDelegate()
                    ->getStyle($cellRange)
                    ->applyFromArray($styleArray);
            },
        ];
    }

    public function array(): array
    {
        $start_date = date('Y-m-d 00:00:00', strtotime($this->date_interval[0]));
        $end_date = date('Y-m-d 23:59:59', strtotime($this->date_interval[1]));

        $query_ex2 = DB::select('select  users.firstname,
                        SUM(CASE
	                        WHEN payable >= potential_price THEN events.price + (payable - potential_price) / event_number
                            WHEN payable < potential_price THEN events.price - (potential_price - payable) / event_number
                            ELSE 0
                        END) as total_payable,
                        SUM(CASE
                            WHEN appointments.status = "completed" AND paid    >= potential_price THEN events.price + (paid - potential_price) / event_number
                            WHEN appointments.status = "completed" AND paid     < potential_price THEN events.price - (potential_price - paid) / event_number
                            WHEN appointments.status = "part_paid" AND payable >= potential_price THEN (events.price + (payable - potential_price) / event_number) * (paid/payable)
                            WHEN appointments.status = "part_paid" AND payable  < potential_price THEN (events.price - (potential_price - payable) / event_number) * (paid/payable)
                            ELSE 0
                        END) as total_paid from appointments
                        left join events on events.appointment_id = appointments.id
						left join (SELECT COUNT(id) as event_number, appointment_id, SUM(price) as potential_price FROM events
                    GROUP BY appointment_id) as  grouped_events on  grouped_events.appointment_id=appointments.id
                     left join users on users.id = events.user_id
               		left join (SELECT * FROM invoices WHERE invoices.state NOT IN ("voided","unpaid") ) as 	new_invoices on new_invoices.appointment_id = appointments.id
                where appointments.status IN ("completed", "part_paid")
                and appointments.event_date BETWEEN "'.$start_date.'" AND "'.$end_date.'"
                group by events.user_id');

        $data = [];
        $datas = [];
        foreach($query_ex2 as $key => $invoice) {
            $data['index'] =  ' '.($key+1).' ';
            $data['username'] = $invoice->firstname;
            $data['total_payable'] = $invoice->total_payable;
            $data['total_paid'] = $invoice->total_paid;

            $total_left = ($invoice->total_payable - $invoice->total_paid);
            $data['total_left'] = $invoice->total_payable ? $total_left : 0;

            $datas[] = $data;
        }

        return $datas;
    }

    public function headings(): array
    {
        return [' № ', 'Ажилтан', 'Төлбөр', 'Нийт орлого', 'Үлдэгдэл'];
    }

    public function startCell(): string
    {
        return 'A'.$this->start_row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            $this->start_row  => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'D' => 20,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => config('global.numberFormat'),
            'D' => config('global.numberFormat'),
            'E' => config('global.numberFormat'),
        ];
    }
}
