<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1, maximum-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<div style="display: inline-block; font-size: 14px; width: 70%;">
   	<h3 style="text-align:center; line-height: 32px; padding-top: 40px; word-break: break-word;"> Таньд шинэ захиалга бүртгэгдлээ.</h3> 	
    
    <div style="padding-top: 10px; display:block;">
        <h3 style="font-weight: 500; margin: 0">Хэзээ</h4>
    </div>
    <table width="100%" style="border: 1px solid #ccc;">
        <tr>
            <td valign="top" width="25%">
                Огноо:
            </td>
            <td valign="top" width="40%">
                {{ $data['event_date'] }}
            </td>
        </tr>
        <tr>
            <td valign="top" width="25%">
                Цаг:
            </td>
            <td valign="top" width="40%">
                {{ $data['event_time'] }}
            </td>
        </tr>
        @if ($data['branch_name'])
            <tr>
                <td valign="top" width="25%">
                    Салбар:
                </td>
                <td valign="top" width="40%">
                    {{ $data['branch_name'] }}
                </td>
            </tr>
        @endif
    </table>

    <div style="padding-top: 10px; display:block;">
        <h3 style="font-weight: 500; margin: 0">Үйлчлүүлэгч</h4>
    </div>
    <table width="100%" style="border: 1px solid #ccc;">
        <tr>
            <td valign="top" width="25%">
                Овог Нэр:
            </td>
            <td valign="top" width="40%">
                {{ $data['customer_name'] }}
            </td>
        </tr>
        <tr>
            <td valign="top" width="25%">
                Утас:
            </td>
            <td valign="top" width="40%">
                {{ $data['customer_phone'] }}
            </td>
        </tr>
    </table>

    <div style="padding-top: 10px; display:block;">
        <h3 style="font-weight: 500; margin: 0">Үйлчилгээ</h4>
    </div>
    <table width="100%" style="border: 1px solid #aaa;">
        @foreach($data['events'] as $event)
            <tr>
                <td valign="top" width="25%">
                    Үйлчилгээ:
                </td>
                <td valign="top" width="40%">
                    {{ $event['service_name'] }}
                </td>
            </tr>
            <tr>
                <td valign="top" width="25%" style="border-bottom: 1px solid #ddd;">
                    Хугацаа:
                </td>
                <td valign="top" width="40%" style="border-bottom: 1px solid #ddd;">
                    {{ $event['service_time'] }}
                </td>
            </tr>
        @endforeach
    </table>

    <br/>
    <div style="padding: 10px; display:block;">
        <h4 style="font-weight: 500; margin: 0">Баярлалаа. Таньд амжилт хүсэе.</h4>
    </div>
    
</div>

</body>
</html>
