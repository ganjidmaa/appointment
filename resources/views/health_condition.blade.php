<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Эрүүл мэндийн асуумж</title>
    <style type="text/css">
        body {
            width: 21cm;
            height: 29.7cm;
            margin: 30mm 45mm 30mm 45mm;
        }

        .tg {
            border-collapse: collapse;
            border-spacing: 0;
        }

        .tg td {
            border-color: black;
            border-style: solid;
            border-width: 1px;
            font-family: Arial, sans-serif;
            font-size: 12px;
            overflow: hidden;
            padding: 8px 4px;
            word-break: normal;
        }

        .tg .tg-0lax {
            text-align: left;
            vertical-align: top
        }

        .tg .tg-nrix {
            text-align: center;
            vertical-align: middle
        }
    </style>
</head>

<body>
    <div style="margin-left: 44%">
        <img height="80px" src="{{ storage_path('app/public/' . $settings->logo) }}" />
    </div>
    <div style="margin-left: 36%;">
        <h4 style="margin:0%; padding:0%;">Нүдний эмчийн үзлэг</h4>
    </div>
    <table class="tg" style="table-layout: fixed; width: 700px">
        <colgroup>
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
            <col style="width: 27px">
        </colgroup>
        <tbody>
            <tr>
                <td class="tg-0lax" colspan="12"><span style="font-weight:bold">Овог нэр: </span>
                    {{ $customer->lastname }}-ийн {{ $customer->firstname }}</td>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">Нас:</span> {{ $age }}</td>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">РД:</span> {{ $customer->registerno }}
                </td>
            </tr>

            <tr>
                <td class="tg-0lax" colspan="12"><span style="font-weight:bold">Хаяг:</span></td>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">Утас:</span> {{ $customer->phone }},
                    {{ $customer->phone2 }}</td>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">Огноо:</span>
                    {{ $appointment->event_date }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="24"><span style="font-weight:bold">Зовиур: </span>
                    {{ $appointment->desc }}</td>
            </tr>
            <tr>
                <td class="tg-nrix" colspan="2" rowspan="3"><span
                        style="font-weight:bold">ХОЛЫН<br />ХАРАА:</span>
                </td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOD=</span>{{ optional($data->farsightedness)->VOD }}</td>
                <td class="tg-nrix" colspan="2" rowspan="3"><span style="font-weight:bold">Ph:</span></td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOD=</span>{{ optional($data->Ph)->VOD }}</td>
                <td class="tg-nrix" colspan="2" rowspan="3"><span style="font-weight:bold">ШИЛТЭЙ:</span></td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOD=</span>{{ optional($data->with_glasses)->VOD }}</td>
                <td class="tg-nrix" colspan="2" rowspan="3"><span
                        style="font-weight:bold">ОЙРЫН<br />ХАРАА:</span>
                </td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOD=</span>{{ optional($data->nearsightedness)->VOD }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOS=</span>{{ optional($data->farsightedness)->VOS }}</td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOS=</span>{{ optional($data->Ph)->VOS }}</td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOS=</span>{{ optional($data->with_glasses)->VOS }}</td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOS=</span>{{ optional($data->nearsightedness)->VOS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOU=</span>{{ optional($data->farsightedness)->VOU }}</td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOU=</span>{{ optional($data->Ph)->VOU }}</td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOU=</span>{{ optional($data->with_glasses)->VOU }}</td>
                <td class="tg-0lax" colspan="4"><span
                        style="font-weight:bold">VOU=</span>{{ optional($data->nearsightedness)->VOU }}</td>
            </tr>
            <tr>
                <td class="tg-nrix" colspan="4" rowspan="2"><span style="font-weight:bold">Нүдний даралт</span><br><span style="font-weight:bold">Air tonometer</span></td>
                <td class="tg-0lax" colspan="4"><span style="font-weight:bold">TOD=</span>{{ optional($data->air_tonometer)->TOD }}</td>
                <td class="tg-nrix" colspan="4" rowspan="2"><span style="font-weight:bold">CCT:</span></td>
                <td class="tg-0lax" colspan="4"><span style="font-weight:bold">TOD=</span>{{ optional($data->CCT)->TOD }}</td>
                <td class="tg-nrix" colspan="4" rowspan="2"><span style="font-weight:bold">ГОНИОСКОПИ</span></td>
                <td class="tg-0lax" colspan="4"><span style="font-weight:bold">OD=</span>{{ optional($data->go_scope)->OD }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="4"><span style="font-weight:bold">TOS=</span>{{ optional($data->air_tonometer)->TOS }}</td>
                <td class="tg-0lax" colspan="4"><span style="font-weight:bold">TOS=</span>{{ optional($data->CCT)->TOS }}</td>
                <td class="tg-0lax" colspan="4"><span style="font-weight:bold">OS=</span>{{ optional($data->go_scope)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"></td>
                <td class="tg-nrix" colspan="9"><span style="font-weight:bold">OD</span></td>
                <td class="tg-nrix" colspan="9"><span style="font-weight:bold">OS</span></td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">НҮДНИЙ ХӨДӨЛГӨӨН:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->eye_movement)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->eye_movement)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">РЕФРАКЦ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->fraction)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->fraction)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ХЯЛАРЫН ӨНЦӨГ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->cranial_angle)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->cranial_angle)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ӨНГӨ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->color)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->color)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ЭМГЭГ ЯЛГАДАС:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->pathological_discharge)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->pathological_discharge)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-nrix" colspan="24"><span style="font-weight:bold">НҮДНИЙ ӨМНӨД ХЭСЭГ</span></td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">НУЛИМСНЫ ЗАМ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->tear_path)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->tear_path)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">УХАРХАЙ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->eye_recesses)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->eye_recesses)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ЗОВХИ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->eyelids)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->eyelids)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">САЛСТ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->mucus)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->mucus)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">СКЛЕР:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->sclera)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->sclera)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ЭВЭРЛЭГ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->cornea)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->cornea)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ӨМНӨД КАМЕР:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->sought_camera)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->sought_camera)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">СОЛОНГОН БҮРХЭВЧ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->rainbow_cover)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->rainbow_cover)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ХҮҮХЭН ХАРАА:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->pupil)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->pupil)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">RAPD:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->RAPD)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->RAPD)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">БОЛОР:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->crystal)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->crystal)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-nrix" colspan="24"><span style="font-weight:bold">НҮДНИЙ УГ</span></td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ШИЛЭНЦЭР:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->glass)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->glass)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ХАРААНЫ МЭДРЭЛИЙН ДИСК:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->eye_disk)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->eye_disk)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">CDR:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->CDR)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->CDR)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-nrix" colspan="3" rowspan="4"><span style="font-weight:bold">СУДСУУД:</span>
                </td>
                <td class="tg-0lax" colspan="3"><span style="font-weight:bold">A:V</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->A_V)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->A_V)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="3"><span style="font-weight:bold">S-H</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->S_H)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->S_H)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="3"><span style="font-weight:bold">K-W</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->K_W)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->K_W)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="3"><span style="font-weight:bold">S-S</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->S_S)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->S_S)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ТОРЛОГ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->reticulated)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->reticulated)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ШАР ТОЛБО:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->yallow_dot)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->yallow_dot)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="6"><span style="font-weight:bold">ЗАХ ХЭСЭГ:</span></td>
                <td class="tg-0lax" colspan="9">{{ optional($data->outside)->OD }}</td>
                <td class="tg-0lax" colspan="9">{{ optional($data->outside)->OS }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="24"><span style="font-weight:bold">ОНОШ: </span>
                    @foreach ($events as $event)
                    {{ optional($event->service)->name }}, 
                    @endforeach</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="24"><span style="font-weight:bold">ЭМЧИЛГЭЭ: </span>
                    @foreach ($events as $event)
                    {{ $event->treatment }}, 
                    @endforeach</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="24"><span style="font-weight:bold">ЗӨВЛӨМЖ: </span>
                    {{ $appointment->conclusion }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="8"><span style="font-weight:bold">ШИЛНИЙ РЕЦЕФТ</span></td>
                <td class="tg-nrix" colspan="4"><span style="font-weight:bold">SPH</span></td>
                <td class="tg-nrix" colspan="4"><span style="font-weight:bold">CYL</span></td>
                <td class="tg-nrix" colspan="4"><span style="font-weight:bold">AXIS</span></td>
                <td class="tg-nrix" colspan="4"><span style="font-weight:bold">VISION</span></td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="5" rowspan="2"><span style="font-weight:bold">DISTANCE
                        (ХОЛ)</span></td>
                <td class="tg-nrix" colspan="3"><span style="font-weight:bold">R</span></td>
                <td class="tg-nrix" colspan="4">{{ optional($data->distance_R)->SPH }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->distance_R)->CYL }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->distance_R)->AXIS }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->distance_R)->VISION }}</td>
            </tr>
            <tr>
                <td class="tg-nrix" colspan="3"><span style="font-weight:bold">L</span></td>
                <td class="tg-nrix" colspan="4">{{ optional($data->distance_L)->SPH }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->distance_L)->CYL }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->distance_L)->AXIS }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->distance_L)->VISION }}</td>
            </tr>
            <tr>
                <td class="tg-0lax" colspan="5" rowspan="2"><span style="font-weight:bold">NEAR (ОЙР)</span>
                </td>
                <td class="tg-nrix" colspan="3"><span style="font-weight:bold">R</span></td>
                <td class="tg-nrix" colspan="4">{{ optional($data->near_R)->SPH }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->near_R)->CYL }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->near_R)->AXIS }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->near_R)->VISION }}</td>
            </tr>
            <tr>
                <td class="tg-nrix" colspan="3"><span style="font-weight:bold">L</span></td>
                <td class="tg-nrix" colspan="4">{{ optional($data->near_L)->SPH }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->near_L)->CYL }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->near_L)->AXIS }}</td>
                <td class="tg-nrix" colspan="4">{{ optional($data->near_L)->VISION }}</td>
            </tr>
        </tbody>
    </table>
    <span></span>
</body>

</html>
