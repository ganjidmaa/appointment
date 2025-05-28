<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

use Illuminate\Support\Facades\DB;

class CustomerReportByDailyFreqSheet implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, WithTitle,
    WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $date_interval;
    protected $branches;
    protected $has_service_type;
    protected $has_branch;
    public $highest_column;

    public function __construct(array $date_interval, $has_service_type, $has_branch)
    {
        $this->date_interval = $date_interval;
        $this->has_service_type = $has_service_type == 1 ? true : false;
        $this->has_branch = $has_branch == 1 ? true : false;
    }

    public function title(): string
    {
        return 'Өдөр тутмын нэгдсэн';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {              
                $sheet = $event->sheet;
                $start_col = 1;
                $col_length = 1;

                //date headers
                $sheet->mergeCells("B1:D1");
                $sheet->mergeCells("E1:G1");
                $sheet->setCellValue("B1", $this->date_interval[0].' - '. $this->date_interval[1]);
                $sheet->setCellValue("E1", 'Хэрэглэгчийн үйлчилгээний тайлан');

                $styleArray = [
                    "borders" => [
                        "allBorders" => [
                            "borderStyle" =>
                                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            "color" => ["argb" => "000000"],
                        ],
                    ],
                ];

                $cellRange = "A4:".$sheet->getHighestColumn() .''. $sheet->getHighestRow(); // All headers
                $event->sheet
                    ->getDelegate()
                    ->getStyle($cellRange)
                    ->applyFromArray($styleArray);
            },
        ];
    }


    public function array(): array
    {
        $start_date = date('Y-m-d', strtotime($this->date_interval[0]));
        $end_date = date('Y-m-d', strtotime($this->date_interval[1]));

        $datas = [];

        $grouped_customers = DB::select(DB::raw('SELECT COUNT(grouped_appointments.customer_id) as customers_count, appointments.event_date, grouped_appointments.type_name,
                            COUNT(IF(customers.frequency_number = 1, 1, NULL)) AS first_time_count, 
                            (COUNT(DISTINCT(appointments.customer_id)) - COUNT(IF(customers.frequency_number = 1, 1, NULL))) AS multiple_time_count,
                            COUNT(IF(type_name = "YVES ROCHER", 1, NULL)) AS yves_rocher_count,
                            COUNT(IF(type_name = "THANN", 1, NULL)) AS thann_count,
                            COUNT(IF(type_name IS NULL, 1, NULL)) AS null_service_count
                    FROM appointments 
                    LEFT JOIN (
                            SELECT COUNT(appointments.id) as frequency_number, customers.id FROM customers
                            LEFT JOIN appointments ON appointments.customer_id = customers.id
                            WHERE appointments.status NOT IN ("no_show", "cancelled", "time_block")
                            GROUP BY appointments.customer_id
                        ) as customers on customers.id = appointments.customer_id
                    RIGHT JOIN (
                        SELECT appointments.customer_id, appointments.event_date as a_event_date, appointments.id as aid, events.type_name
                        FROM appointments
                        LEFT JOIN (
                            SELECT events.appointment_id, events.id as event_id, events.customer_id, services.* FROM events
                            LEFT JOIN (
                                SELECT service_types.name as type_name, services.id as s_id FROM services
                                LEFT JOIN service_types on service_types.id = services.type
                            ) as services on services.s_id = events.service_id
                            GROUP BY events.appointment_id
                        ) as events on events.appointment_id = appointments.id
                        WHERE event_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" AND appointments.status NOT IN ("no_show", "cancelled", "time_block")
                        GROUP BY appointments.customer_id, appointments.event_date
                    ) as grouped_appointments ON grouped_appointments.aid = appointments.id
                    WHERE event_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" AND appointments.status NOT IN ("no_show", "cancelled", "time_block")
                    GROUP BY appointments.event_date
                    ORDER BY appointments.event_date')->getValue(DB::connection()->getQueryGrammar()));

        foreach($grouped_customers as $key => $customer) {
            $data['index'] =  ' '.($key+1).' ';
            $data['event_date'] = $customer->event_date;
            $data['customers_count'] = $customer->customers_count;
            $data['first_time_count'] = $customer->first_time_count;
            $data['multiple_time_count'] = $customer->multiple_time_count;
            if($this->has_service_type) {
                $data['yves_rocher'] = $customer->yves_rocher_count ?? 0;
                $data['thann'] = $customer->thann_count ?? 0;
            }
            $data['null_service_count'] = $customer->null_service_count ?? 0;
            $datas[] = $data;
        }
        $this->highest_column = count($data);

        return $datas;
    }

    public function headings(): array
    {
        if($this->has_service_type)
            return [' № ', 'Огноо', 'Нийт үйлчлүүлэгчдийн тоо', 'Анх ирсэн', 'Давтан ирсэн', 'Нийт Yves Rocher', 'Нийт Thann', 'Үйлчилгээ сонгоогүй'];
        else
            return [' № ', 'Огноо', 'Нийт үйлчлүүлэгчдийн тоо', 'Анх ирсэн', 'Давтан ирсэн', 'Үйлчилгээ сонгоогүй'];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4    => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [];
    }

    public function columnFormats(): array
    {
        $comma_separate_col = 5;
        $formatting_cols = [];

        for($comma_separate_col; $comma_separate_col <= $this->highest_column-1; $comma_separate_col++) {
            $column_str = Coordinate::stringFromColumnIndex($comma_separate_col);
            $formatting_cols[$column_str] = config('global.numberFormat');
        }
        return $formatting_cols;
    }
}
