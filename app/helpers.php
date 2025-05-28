<?php

function symbolLabel() {
    $colors = ['warning', 'primary', 'success', 'danger', 'info', 'dark', 'warning', 'warning', 'success', 'danger', 'info', 'dark'];
    $index = random_int(0, count($colors) - 1);
    return $colors[$index];
}

function checkHoliday() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,'http://mohsms.ubisol.mn/sms/check_holiday');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function sendSmsApi($phone, $msg) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://mohsms.ubisol.mn/sms/send?mobile=' .$phone. '&desc=' . urlencode($msg));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result);
}

function converCyrToLat($str) {
    $cyr = [
        'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
        'р','с','т','у','ф','х','ц','ч','ш','ө','ү','ы','ь','э','ю','я',
        'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
        'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Ө','Ү','Ы','Ь','Э','Ю','Я'
    ];
    $lat = [
        'a','b','v','g','d','e','yo','j','z','i','i','k','l','m','n','o','p',
        'r','s','t','u','f','h','ts','ch','sh','u','u','i','y','e','yu','ya',
        'A','B','V','G','D','E','Yo','J','Z','I','Y','K','L','M','N','O','P',
        'R','S','T','U','F','H','Ts','Ch','Sh','U','U','I','Y','E','Yu','Ya'
    ];

     return str_replace($cyr, $lat, $str);
}