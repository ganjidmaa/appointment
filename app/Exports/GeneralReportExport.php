<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\PaymentMethod;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GeneralReportExport implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $date_interval;
    protected $has_service_type;
    public $highest_column;
    private $cell_ranges = [];
    public $start_row = 3;
    protected $payment_methods;

    public function __construct(array $date_interval, $has_service_type)
    {
        $this->date_interval = $date_interval;
        $this->has_service_type = $has_service_type == 1 ? true : false;
        $this->payment_methods = PaymentMethod::where('active', 1)
            ->whereNotIn('slug', ['discount_card', 'discount', 'membership'])
            ->orderBy('id', 'asc')->get();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->mergeCells("B1:D1");
                $sheet->mergeCells("E1:G1");
                $sheet->setCellValue("B1", $this->date_interval[0].' - '. $this->date_interval[1]);
                $sheet->setCellValue("E1", 'Эмчилгээний дэлгэрэнгүй тайлан');

                foreach($this->cell_ranges as $cell_range) {
                    $sheet->mergeCells("B".$cell_range['first'].":B".$cell_range['last']);
                    $sheet->mergeCells("C".$cell_range['first'].":C".$cell_range['last']);
                    $comma_separate_col = 9;
                    //tolbor-s uldegdel hurtelh columnuudiig dooshoo merge hiine
                    for($comma_separate_col; $comma_separate_col <= $this->highest_column; $comma_separate_col++) {
                        $column_str = Coordinate::stringFromColumnIndex($comma_separate_col);
                        $sheet->mergeCells($column_str.$cell_range['first'].":".$column_str.$cell_range['last']);
                    }
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
        $start_date = date('Y-m-d 00:00:00', strtotime($this->date_interval[0]));
        $end_date = date('Y-m-d 23:59:59', strtotime($this->date_interval[1]));

        $events = Event::selectRaw('events.*, 
                appointments.id, appointments.status, appointments.treatment_state, appointments.event_date, diagnosis, conclusion')
            ->leftJoin('appointments', 'appointments.id', '=', 'events.appointment_id')
            ->whereBetween('events.start_time', [$start_date, $end_date])
            ->orderBy('appointments.event_date', 'asc')
            ->orderBy('events.customer_id', 'asc')
            ->orderBy('events.start_time', 'asc')
            ->get();

        $data = [];
        $datas = [];
        $grouped_appointment_id = 0;
        $key = 0;
        foreach($events as $event) {
            if($event->appointment->status != 'no_show' && $event->appointment->status != 'time_block') {
                // $payment = $event->service ? $event->service->price : 0;
                $key++;
                $data['index'] = ' '.($key).' ';
                $data['date'] = date('Y-m-d H:i', strtotime($event->start_time));
                $data['status'] = $event->appointment->statusName($event->appointment->status);
                $data['treatment_state'] = $event->appointment->treatmentStateName($event->appointment->treatment_state);
                $data['customer_name'] = $event->customer ? $event->customer->lastname. ' ' . $event->customer->firstname : '';
                $data['customer_age'] = $event->customer ? $event->customer->age() : '';
                $data['customer_gender'] = $event->customer ? $event->customer->gender() : '';
                $data['customer_phone'] = $event->customer ? $event->customer->phone : '';
                $data['customer_address'] = $event->customer && $event->customer->address ? $event->customer->addressName() : '';
                $data['customer_email'] = $event->customer ? $event->customer->email : '';
                $data['username'] = $event->user ? $event->user->firstname : '';
                // $data['duration'] = $event->duration;
                $data['service_name'] = $event->appointment->diagnosis;
                $this->has_service_type ? $data['service_code'] = $event->service ? $event->service->code : '' : null;

                $invoices = Invoice::selectRaw('invoices.id, cash_amount, qpay_amount, mobile_amount,
                            card_amount, barter_amount, coupon_amount, invoices.discount_amount,
                            SUM(paid) as total_paid, payment as total_payment')
                    ->leftJoin(DB::raw('(SELECT payments.invoice_id,
                            SUM(CASE WHEN payments.type = "cash" THEN payments.amount END) as cash_amount,
                            SUM(CASE WHEN payments.type = "qpay" THEN payments.amount END) as qpay_amount,
                            SUM(CASE WHEN payments.type = "mobile" THEN payments.amount END) as mobile_amount,
                            SUM(CASE WHEN payments.type = "card" THEN payments.amount END) as card_amount,
                            SUM(CASE WHEN payments.type = "barter" THEN payments.amount END) as barter_amount,
                            SUM(CASE WHEN payments.type = "coupon" THEN payments.amount END) as coupon_amount
                        FROM payments
                            GROUP BY payments.invoice_id) grouped_payments'), 'grouped_payments.invoice_id', 'invoices.id')
                    ->where('invoices.appointment_id', '=', $event->appointment_id)
                    ->where('invoices.state', '!=', 'voided')
                    ->first();

                $paid = $invoices && $invoices->total_paid ? $invoices->total_paid : 0;
                // $data['price'] = $event->price;
                $data['payment'] = $invoices ? $invoices->total_payment : 0;
                $data['discount'] = $invoices ? $invoices->discount_amount : 0;
                $data['total_paid'] = $paid;

                //songoson tolboriin helberuud deh data haruulna
                foreach($this->payment_methods as $payment_method) {
                    // $method_amount = $invoices[$payment_method->slug.'_amount'];
                    $method_amount = $invoices->{$payment_method->slug.'_amount'};
                    $data['total_'.$payment_method->slug] = $method_amount ? $method_amount : 0;
                }
                $data['total_left'] = $invoices ? ($invoices->total_payment - $invoices->discount_amount - $paid) : 0;

                if($grouped_appointment_id != $event->appointment_id && count($event->appointment->events) > 1) {
                    $group_events_number = count($event->appointment->events);
                    $row = $key + $this->start_row;
                    $this->cell_ranges[] = ['first' => $row, 'last' => $row+($group_events_number-1)];
                    $grouped_appointment_id = $event->appointment_id;
                }
                $this->highest_column = count($data);
                $datas[] = $data;
            }
        }

        return $datas;
    }

    public function headings(): array
    {
        //songoson tolboriin helberuudiig haruulna
        $method_names = $this->payment_methods->pluck('name')->toArray();
        if($this->has_service_type)
            return [' № ', 'Огноо', 'Төлөв', 'Үзлэгийн тов', 'Эмчлүүлэгчийн овог, нэр', 'Нас', 'Хүйс', 'Утас', 'Хаяг', 'Имэйл', 'Эмч', 'Онош', 'Эмчилгээний код', 'Төлбөр', 'Хөнгөлөлт', 'Нийт төлсөн - Үүнээс: ', ...$method_names, 'Үлдэгдэл'];
        else
            return [' № ', 'Огноо', 'Төлөв', 'Үзлэгийн тов', 'Эмчлүүлэгчийн овог, нэр', 'Нас', 'Хүйс', 'Утас', 'Хаяг', 'Имэйл', 'Эмч', 'Онош', 'Төлбөр', 'Хөнгөлөлт', 'Нийт төлсөн - Үүнээс: ', ...$method_names, 'Үлдэгдэл'];
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

    public function columnWidths(): array
    {
        return [
            'J' => 20,
        ];
    }

    public function columnFormats(): array
    {
        $comma_separate_col = 8;
        $formatting_cols = [];
        //tolbor-s uldegdel hurtelh columnuudiin mongon dvnd taslal oruulna
        for($comma_separate_col; $comma_separate_col <= $this->highest_column; $comma_separate_col++) {
            $column_str = Coordinate::stringFromColumnIndex($comma_separate_col);
            $formatting_cols[$column_str] = config('global.numberFormat');
        }
        return $formatting_cols;
    }
}
