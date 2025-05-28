<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Invoice;
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

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AttendanceReportByUsersExport implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents
{
    protected $date_interval;

    public function __construct(array $date_interval)
    {
        $this->date_interval = $date_interval;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {              
                $sheet = $event->sheet;
                $sheet->mergeCells("B1:D1");
                $sheet->mergeCells("E1:G1");
                $sheet->setCellValue("B1", $this->date_interval[0].' - '. $this->date_interval[1]);
                $sheet->setCellValue("E1", 'Эмчилгээний тоо');

                $styleArray = [
                    "borders" => [
                        "allBorders" => [
                            "borderStyle" =>
                                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            "color" => ["argb" => "000000"],
                        ],
                    ],
                ];

                $cellRange = "A3:".$sheet->getHighestColumn().$sheet->getHighestRow(); // All headers
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

        $appointment_statuses = DB::select(DB::raw("SELECT COUNT(no_show_events.id) as no_showed_number, 
            COUNT(cancelled_events_1.id) as cancelled_events_1_number, COUNT(cancelled_events_2.id) as cancelled_events_2_number, 
            COUNT(booked_events.id) as booked_number, COUNT(show_events.id) as showed_number, users.firstname FROM events
            LEFT JOIN (SELECT events.id from events 
                    LEFT JOIN appointments on appointments.id = events.appointment_id 
                    WHERE appointments.status = 'no_show') as no_show_events ON no_show_events.id = events.id
            LEFT JOIN (SELECT events.id from events 
                    LEFT JOIN appointments on appointments.id = events.appointment_id 
                    WHERE appointments.status = 'cancelled' and cancellation_type = 'user_request') as cancelled_events_1 ON cancelled_events_1.id = events.id
            LEFT JOIN (SELECT events.id from events 
                    LEFT JOIN appointments on appointments.id = events.appointment_id 
                    WHERE appointments.status = 'cancelled' and cancellation_type = 'mistake') as cancelled_events_2 ON cancelled_events_2.id = events.id
            LEFT JOIN (SELECT events.id from events 
                    LEFT JOIN appointments on appointments.id = events.appointment_id 
                    WHERE appointments.status = 'booked') as booked_events ON booked_events.id = events.id
            LEFT JOIN (SELECT events.id from events 
                    LEFT JOIN appointments on appointments.id = events.appointment_id 
                    WHERE appointments.status NOT IN ('booked', 'no_show', 'cancelled', 'time_block')) as show_events ON show_events.id = events.id
            LEFT JOIN users ON users.id = events.user_id
            WHERE events.start_time BETWEEN '".$start_date."' and '".$end_date."'
                GROUP BY events.user_id")->getValue(DB::connection()->getQueryGrammar()));

        $data = [];
        $datas = [];
        foreach($appointment_statuses as $key => $appointment) {
            $total_number = $appointment->booked_number + $appointment->showed_number + $appointment->no_showed_number + $appointment->cancelled_events_1_number + $appointment->cancelled_events_2_number;
            $data['index'] =  ' '.($key+1).' ';
            $data['username'] = $appointment->firstname;
            $data['total'] = $total_number;
            $data['booked'] = $appointment->booked_number;
            $data['showed'] = $appointment->showed_number;
            $data['no_showed'] = $appointment->no_showed_number;
            $data['cancelled_one'] = $appointment->cancelled_events_1_number;
            $data['cancelled_two'] = $appointment->cancelled_events_2_number;

            $datas[] = $data;
        }

        return $datas;
    }

    public function headings(): array
    {
        return [' № ', 'Эмч', 'Нийт эмчилгээ - Үүнээс: ', 'Захиалсан', 'Ирсэн', 'Ирээгүй', 'Эмчлүүлэгч цуцалсан', 'Эмч цуцалсан'];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            3    => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'C' => 20,
        ];
    }
}
