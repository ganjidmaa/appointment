<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IncomeReportByDaysExport implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $date_interval;
    protected $payment_methods;
    public $highest_column;

    public function __construct(array $date_interval)
    {
        $this->date_interval = $date_interval;
        $this->payment_methods = PaymentMethod::where('active', 1)
            ->whereNotIn('slug', ['discount_card', 'discount', 'membership'])
            ->orderBy('id', 'asc')->get();
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
                $cellRange = "A3:".$sheet->getHighestColumn().''.$sheet->getHighestRow(); // All headers
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

        $invoices = DB::select('SELECT COUNT(appointments.id) as app_number, appointments.event_date,sum(evv.event_number) as event_number,
                                SUM(total_payment) as total_payment, SUM(total_payable) as total_payable, SUM(total_paid) as total_paid, SUM(total_discount) as total_discount,
                                SUM(total_cash_amount) as total_cash_amount, SUM(total_qpay_amount) as total_qpay_amount, SUM(total_mobile_amount) as total_mobile_amount,
                                SUM(total_card_amount) as total_card_amount, SUM(total_barter_amount) as total_barter_amount, SUM(total_coupon_amount) as total_coupon_amount
                        FROM
                            appointments
                        left join (select appointment_id, count(events.id) as event_number from events group by events.appointment_id ) as evv on evv.appointment_id = appointments.id
                        LEFT JOIN(
                            SELECT invoices.appointment_id, (payment) as total_payment, (payable) as total_payable, (paid) as total_paid, 	(discount_amount) as total_discount,
                        (cash_amount) as total_cash_amount,
                        (qpay_amount) as total_qpay_amount,
                        (mobile_amount) as total_mobile_amount,
                        (card_amount) as total_card_amount,
                        (barter_amount) as total_barter_amount,
                        (coupon_amount) as total_coupon_amount
                            FROM
                                invoices
                            LEFT JOIN(
                                SELECT
                                    payments.invoice_id,
                                SUM(CASE WHEN payments.type = "cash" THEN payments.amount END) as cash_amount,
                                                        SUM(CASE WHEN payments.type = "qpay" THEN payments.amount END) as qpay_amount,
                                                        SUM(CASE WHEN payments.type = "mobile" THEN payments.amount END) as mobile_amount,
                                                        SUM(CASE WHEN payments.type = "card" THEN payments.amount END) as card_amount,
                                                        SUM(CASE WHEN payments.type = "barter" THEN payments.amount END) as barter_amount,
                                                        SUM(CASE WHEN payments.type = "coupon" THEN payments.amount END) as coupon_amount
                                FROM
                                    payments
                                group by payments.invoice_id
                            ) AS payments
                        ON  payments.invoice_id = invoices.id) AS invoices
                        ON invoices.appointment_id = appointments.id
                        WHERE
                            status in ("completed","part_paid")
                            and event_date between "'.$start_date.'" and "'.$end_date.'"
                            group by appointments.event_date
                            order by appointments.event_date');

        $data = [];
        $datas = [];
        foreach($invoices as $key => $invoice) {
            $data['index'] =  ' '.($key+1).' ';
            $data['created_date'] = $invoice->event_date ? date('Y-m-d', strtotime($invoice->event_date)) : '';
            $data['app_number'] = $invoice->app_number ? $invoice->app_number : 0;
            $data['event_number'] = $invoice->event_number ? $invoice->event_number : 0;
            $data['total_payment'] = $invoice->total_payment ? $invoice->total_payment : 0;
            $data['total_discount'] = $invoice->total_discount ? $invoice->total_discount : 0;
            $data['total_paid'] = $invoice->total_paid ? $invoice->total_paid : 0;
            foreach($this->payment_methods as $payment_method) {
                $method_amount = $invoice['total_'.$payment_method->slug.'_amount'];
                $data['total_'.$payment_method->slug] = $method_amount ? $method_amount : 0;
            }
            $total_left = ($invoice->total_payment - $invoice->total_discount - $invoice->total_paid);
            $data['total_left'] = $invoice->total_payment ? $total_left : 0;

            $datas[] = $data;
        }
        $this->highest_column = count($data);

        return $datas;
    }

    public function headings(): array
    {
        $method_names = $this->payment_methods->pluck('name')->toArray();
        return [' № ', ' Огноо ', 'Захиалгын тоо', 'Эмчилгээний тоо', 'Төлбөр', 'Хөнгөлөлт', 'Нийт төлсөн - Үүнээс: ', ...$method_names, 'Үлдэгдэл'];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            3   => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'B' => 15,
            'E' => 20,
        ];
    }

    public function columnFormats(): array
    {
        $comma_separate_col = 5;
        $formatting_cols = [];

        for($comma_separate_col; $comma_separate_col <= $this->highest_column; $comma_separate_col++) {
            $column_str = Coordinate::stringFromColumnIndex($comma_separate_col);
            $formatting_cols[$column_str] = config('global.numberFormat');
        }
        return $formatting_cols;
    }
}
