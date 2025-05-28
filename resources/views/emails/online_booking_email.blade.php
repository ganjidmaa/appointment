<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            /* background-color: #f8f9fa; */
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
        }
        .header {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            padding: 10px 0;
        }
        .content {
            background: white;
            padding: 20px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border: 1px solid #ddd;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .total-row {
            font-weight: bold;
            background-color: #f1f1f1;
        }
        .separator {
            border-bottom: 1px dashed #ddd;
            margin: 15px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="header">Манайхыг сонгон үйлчлүүлсэнд баярлалаа.</h2>
    <div class="separator"></div>

    <div class="content">
        {{-- <p>Сайн байна уу <strong>{{$appointment->customer->firstname}}</strong>,</p>
        <p>Таны төлбөрийг хүлээн авлаа. Таны цаг захиалга баталгаажлаа.</p>

        <h3>({{$appointment->branch_id ? $appointment->branch->name : ''}} - {{date('Y/m/d', strtotime($appointment->event_date))}})</h3>
        <table>
            <thead>
                <tr style="background-color: #f1f1f1;">
                    <th>Үзлэг, оношилгоо</th>
                    <th>Цаг</th>
                    <th>Хугацаа</th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointment->events as $event)
                    <tr>
                        <td>{{$event->service->name}}</td>
                        <td>{{date('Y/m/d H:i', strtotime($event->start_time))}}</td>
                        <td>{{$event->service->duration}} мин</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f1f1; font-weight: bold;">
                    <td colspan="2">Нийт үнийн дүн:</td>
                    <td>{{number_format($appointment->invoice->payment)}}₮</td>
                </tr>
                @if($appointment->invoice->discount_amount > 0)
                    <tr style="background-color: #f1f1f1;">
                        <td colspan="2">Хөнгөлөлт:</td>
                        <td>{{number_format($appointment->invoice->discount_amount)}}₮</td>
                    </tr>
                @endif
                <tr style="background-color: #f1f1f1; font-weight: bold;">
                    <td colspan="2">Нийт төлсөн дүн:</td>
                    <td>{{number_format($appointment->invoice->paid)}}₮</td>
                </tr>
            </tfoot>
        </table> --}}
        <p>{!! $email_info !!}</p>
    </div>
</div>

</body>
</html>
