<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\Branch;
use App\Models\Settings;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMappedCells;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Carbon\Carbon;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AttendanceReportByRushHours implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, 
    WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $date_interval;
    protected $branches;
    protected $users;

    public function __construct(array $date_interval)
    {
        $this->date_interval = $date_interval;
        $this->users = User::where('role_id', 3)->orderBy('branch_id', 'asc')->get();
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
                $sheet->setCellValue("E1", 'Үйлчилгээний оргил цагийн ачааллын тайлан');

                //branch names
                $branches = Branch::orderBy('id', 'asc')->get();
                foreach($branches as $key => $branch) {
                    $col_length = $col_length + count($branch->beauticians);
                    $column_str_start = Coordinate::stringFromColumnIndex($start_col+1);
                    $column_str_end = Coordinate::stringFromColumnIndex($col_length);
                    $sheet->mergeCells($column_str_start."3:".$column_str_end."3");
                    $sheet->setCellValue($column_str_start."3", $branch->name);
                    $start_col = $col_length;
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

                $cellRange = "A3:".$sheet->getHighestColumn() .''. $sheet->getHighestRow(); // All headers
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
        $settings = Settings::find(1);
        $user_hour_datas = [];

        foreach($this->users as $user) {
            $rush_hour_datas = DB::select(DB::raw('SELECT SUM(grouped_events.event_num) as event_number, DATE_FORMAT(grouped_events.start_time,"%H") as s_time FROM appointments
                LEFT JOIN (
                    SELECT MIN(start_time) as start_time, COUNT(events.id) as event_num, appointment_id, user_id FROM events GROUP BY events.appointment_id
                ) AS grouped_events ON grouped_events.appointment_id = appointments.id 
                LEFT JOIN users ON users.id = grouped_events.user_id
                WHERE grouped_events.user_id = '.$user->id.' AND appointments.status NOT IN ("no_show", "cancelled", "time_block")
                    AND appointments.event_date BETWEEN "'.$start_date.'" and "'.$end_date.'"
                GROUP BY HOUR(grouped_events.start_time)
                ORDER BY HOUR(grouped_events.start_time) ASC')->getValue(DB::connection()->getQueryGrammar()));

            $user_hour_datas[] = $rush_hour_datas;
        }
        
        $hours = [];
        $hours = Event::selectRaw('DATE_FORMAT(events.start_time,"%H") as s_time')
                ->groupByRaw("HOUR(events.start_time)")
                ->orderByRaw("HOUR(events.start_time) ASC")
                ->pluck('s_time')->toArray();

        foreach($hours as $hour) {
            $data['index'] = $hour.':00';
            
            foreach($user_hour_datas as $key => $user_hour_data) {
                $event_number = '0';

                foreach($user_hour_data as $hour_attendance) {
                    if($hour_attendance->s_time == $hour) {
                        $event_number = $hour_attendance->event_number;
                    }
                }
                $data[$key] = $event_number;
            }
            
            $datas[] = $data;
        }

        return $datas;
    }

    public function headings(): array
    {
        $user_names = $this->users->pluck('firstname')->toArray();
        return [['Цаг', ...$user_names]];
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
        return [];
    }
}
