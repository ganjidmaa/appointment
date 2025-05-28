<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use App\Models\CouponCode;


class CouponCodeExport implements FromArray, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents, WithColumnFormatting
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
                $sheet->setCellValue("B1", $this->date_interval[0] . ' - ' . $this->date_interval[1]);

                $styleArray = [
                    "borders" => [
                        "allBorders" => [
                            "borderStyle" => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            "color" => ["argb" => "000000"],
                        ],
                    ],
                ];

                $cellRange = "A3:L" . $sheet->getHighestRow();
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

        $coupons = CouponCode::selectRaw('coupon_codes.id, coupon_codes.value, coupons.title, limit_number, sell_limit,
                coupon_codes.status as status, coupon_codes.redeemed, coupon_codes.code, usage_count,
                coupon_codes.type, coupon_codes.start_date, coupon_codes.end_date, coupon_codes.created_at')
            ->leftJoin('coupons', 'coupons.id', 'coupon_codes.coupon_id')
            ->get();

        $data = [];
        foreach ($coupons as $key => $coupon) {
            $possible_limit = 1;
            if ($coupon->type == 'mass') {
                $possible_limit = $coupon->limit_number ? $coupon->limit_number : '';
            } elseif ($coupon->type == 'personal' && $coupon->sell_limit == 1) {
                $possible_limit = 1;
            } else {
                $possible_limit = 'Хязгааргүй';
            }

            $data[] = [
                'index' => $key + 1,
                'title' => $coupon->title,
                'code' => $coupon->code,
                'type' => $coupon->type == 'mass' ? 'Олон удаагийн' : 'Нэг удаагийн',
                'value' => $coupon->value,
                'status' => $coupon->status == 'valid' ? 'Хүчинтэй' : ($coupon->status == 'redeemed' ? 'Ашиглагдсан' : 'Хүчингүй'),
                'possible_count' => $possible_limit,
                'usage_count' => $coupon->usage_count,
                'redeemed' => $coupon->redeemed,
                'start_date' => $coupon->start_date,
                'end_date' => $coupon->end_date,
                'created_at' => $coupon->created_at
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return ['№', 'Ангилал', 'Купон код', 'Төрөл', 'Хэрэглэх хязгаар', 'Төлөв', 'Боломжит тоо', 'Ашигласан тоо', 'Ашигласан дүн', 'Эхлэх огноо', 'Дуусах огноо', 'Үүсгэсэн огноо'];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            3 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'B' => 20,
            'C' => 15,
            'D' => 20,
            'E' => 20,
            'F' => 15,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER,
        ];
    }
}