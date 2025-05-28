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
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AttendanceReportByServicesExport implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $date_interval;
    protected $has_service_type;
    protected $last_column;

    public function __construct(array $date_interval, bool $has_service_type)
    {
        $this->date_interval = $date_interval;
        $this->has_service_type = $has_service_type;
        $this->last_column = $has_service_type == 1 ? 'F' : 'E';
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

                $cellRange = "A3:".$this->last_column . $sheet->getHighestRow(); // All headers
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

        $appointment_statuses = DB::table('events')
            ->selectRaw('events.service_id, COUNT(events.id) as total_number, services.name as service_name, 
                categories.name, services.category_id, services.code, services.price')
            ->leftJoin('services', 'services.id', 'events.service_id')  
            ->leftJoin('services as categories', 'categories.id', 'services.category_id')  
            ->leftJoin('appointments', 'appointments.id', 'events.appointment_id')  
            ->whereRaw('appointments.status NOT IN ("no_show", "cancelled", "time_block")')
            ->whereBetween('events.start_time', [$start_date, $end_date])
            ->groupBy('events.service_id')
            ->orderBy('services.category_id', 'desc')
            ->get();

        $data = [];
        $datas = [];
        foreach($appointment_statuses as $key => $appointment) {
            $data['index'] =  ' '.($key+1).' ';
            $data['category_name'] = $appointment->name;
            $data['service_name'] = $appointment->service_name;
            if($this->has_service_type)
                $data['service_code'] = $appointment->code;
            $data['total_num'] = $appointment->total_number;
            $data['service_price'] = $appointment->price;

            $datas[] = $data;
        }

        return $datas;
    }

    public function headings(): array
    {
        if($this->has_service_type)
            return [' № ','Ангилал','Онош','Код', 'Эмчилгээний тоо', 'Нэгжийн үнэ'];
        else 
            return [' № ','Ангилал', 'Онош','Эмчилгээний тоо', 'Нэгжийн үнэ'];
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
            'B' => 20,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => config('global.numberFormat'),
        ];
    }
}
