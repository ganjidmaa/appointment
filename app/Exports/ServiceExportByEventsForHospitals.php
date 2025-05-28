<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Illuminate\Support\Facades\Log;

class ServiceExportByEventsForHospitals implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $date_interval;
    public $highest_column;
    public $start_row = 3;
    public $cell_ranges = [];

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
                $sheet->setCellValue("E1", 'Үзлэгийн болон хүлээлгийн тайлан');

                foreach($this->cell_ranges as $cell_range) {
                    $sheet->mergeCells("B".$cell_range['first'].":B".$cell_range['last']);
                    $sheet->mergeCells("C".$cell_range['first'].":C".$cell_range['last']);
                    $sheet->mergeCells("D".$cell_range['first'].":D".$cell_range['last']);
                    $sheet->mergeCells("E".$cell_range['first'].":E".$cell_range['last']);
                    $sheet->mergeCells("F".$cell_range['first'].":F".$cell_range['last']);
                    $sheet->mergeCells("G".$cell_range['first'].":G".$cell_range['last']);
                    $sheet->mergeCells("H".$cell_range['first'].":H".$cell_range['last']);
                    $sheet->mergeCells("M".$cell_range['first'].":M".$cell_range['last']);
                    $sheet->mergeCells("R".$cell_range['first'].":R".$cell_range['last']);
                    $sheet->mergeCells("O".$cell_range['first'].":O".$cell_range['last']);
                    $sheet->mergeCells("P".$cell_range['first'].":P".$cell_range['last']);
                    $sheet->mergeCells("Q".$cell_range['first'].":Q".$cell_range['last']);
                    $sheet->mergeCells("A".$cell_range['first'].":A".$cell_range['last']);
                    $sheet->mergeCells("I".$cell_range['first'].":I".$cell_range['last']);
                }

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
        $start_date = date('Y-m-d', strtotime($this->date_interval[0]));
        $end_date = date('Y-m-d', strtotime($this->date_interval[1]));
        $datas = DB::select(DB::raw('SELECT
                customers.firstname AS cfn,
                customers.lastname AS cln,
                users.firstname AS ufn,
                appointments.id as id,
                appointments.desc as zowiur,
                appointments.conclusion as zuwlumj,
                card_number,
                surgery_card_number,
                events.start_time,
                customers.registerno,
                customers.phone,
                customers.phone2,
                service_sum.cname,
                service_sum.sname,
                treatment,
                appointments.status,
                appointments.treatment_state,
                gender
            FROM events
            LEFT JOIN users ON users.id = events.user_id
            LEFT JOIN customers ON customers.id = events.customer_id
            LEFT JOIN appointments ON appointments.id = events.appointment_id
            LEFT JOIN(
                SELECT
                    services.id,
                    services.name AS sname,
                    categories.name AS cname
                FROM
                    `services`
                LEFT JOIN(
                SELECT
                    *
                FROM
                    services
                ) AS categories
            ON
                services.category_id = categories.id
            WHERE
                services.is_category = 0
            ) AS service_sum
            ON
                service_sum.id = events.service_id
                WHERE NOT
            appointments.status = "cancelled" AND NOT appointments.status = "no_show" AND NOT appointments.status = "time_block" AND events.start_time
            BETWEEN "' . $start_date . '" and "' . $end_date . '"'
        )->getValue(DB::connection()->getQueryGrammar())
        );
        $currentYear = Carbon::now()->year;
        $columns = [];
        $current_appointment_id = 0;
        $row_start = 3;
        $row_end = 3;
        $index = 0;
        foreach ($datas as $key => $data) {
            $row_end++;
            if ($data->registerno) {
                $regno = trim($data->registerno);
                $birthYear = intval(mb_substr($regno, 4, 2)) + 1900;
                if ((int) mb_substr($regno, 6, 2) > 12) {
                    $birthYear += 100;
                }
                $data->age = $currentYear - $birthYear;
            } else {
                $data->age = '--';
            }
            if($current_appointment_id != $data->id) {
                $this->cell_ranges[] = [
                    'first' => $row_start, 'last' => $row_end - 1];
                $row_start = $row_end;
                $current_appointment_id = $data->id;
                $index++;
            }
            $col['index'] = $index;
            $col['card_num'] = $data->card_number;
            $col['surgery_num'] = $data->surgery_card_number;
            $col['date'] = $data->start_time;
            $col['lastname'] = $data->cln;
            $col['firstname'] = $data->cfn;
            $col['registerno'] = $data->registerno;
            $col['age'] = $data->age;
            if ($data->gender === 0) $col['sex'] = 'Эрэгтэй';
            elseif($data->gender === 1) $col['sex'] = 'Эмэгтэй';
            else $col['sex'] = '--';
            $col['category'] = $data->cname;
            $col['diagnos'] = $data->sname;
            $col['treatment'] = $data->treatment;
            $col['phone'] = $data->phone .', '.$data->phone2;
            $col['doc_name'] = $data->ufn;
            if ($data->status == 'completed' || $data->status == 'part_paid') $col['state'] = 'Үзлэг хийгдсэн';
            elseif($data->status == 'booked' || $data->status == 'showed' || $data->status == 'confirmed') $col['state'] = 'Хүлээгдэж байна.';
            elseif($data->status == 'no_show' || $data->status == 'cancelled') $col['state'] = 'Цуцлагдсан';
            else $col['state'] = $data->status;
            if($data->treatment_state == 0) $col['treatment_state'] = 'Хүлээлгэнд орсон';
            elseif($data->treatment_state == 1) $col['treatment_state'] = 'Зөвлөгөө өгсөн';
            elseif($data->treatment_state == 2) $col['treatment_state'] = 'Мэс засал товлосон';
            $col['zowiur'] = $data->zowiur;
            $col['zuwlumj'] = $data->zuwlumj;
            $columns[] = $col;
            }

        return $columns;
    }
    public function headings(): array
    {
            return ['Д/д','Картын дугаар','Мэс заслын картын дугаар','Огноо','Овог ','нэр','Регистрийн дугаар','Нас','Хүйс','Ангилал','Онош','Эмчилгээ','Утас','Эмчийн нэр','Төлөв','Одоогийн байдал','Зовиур','Зөвлөмж'];
    }

    public function startCell(): string
    {
        return 'A'.$this->start_row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            $this->start_row => ['font' => ['bold' => true]],

        ];
    }
}
