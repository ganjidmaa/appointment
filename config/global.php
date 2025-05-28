<?php
 
return [
    'roles' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'subscriber' => 'Subscriber',
        'client' => 'Client',
    ],
    'statuses' => [
        ['value' => ['booked'], 'name' => 'Цаг захиалсан'],
        ['value' => ['confirmed'], 'name' => 'Баталгаажсан'],
        ['value' => ['showed'], 'name' => 'Ирсэн'],
        ['value' => ['started'], 'name' => 'Эхлэсэн'],
        ['value' => ['no_show'], 'name' => 'Ирээгүй'],
        ['value' => ['cancelled'], 'name' => 'Цуцалсан'],
        ['value' => ['completed','part_paid','unpaid'], 'name' => 'Дууссан'],
    ],
    'treatment_states' => [
        ['value' => [0], 'name' => 'Хүлээлгэд орсон'],
        ['value' => [1], 'name' => 'Зөвлөгөө өгсөн'],
        ['value' => [2], 'name' => 'Мэс засал товлосон'],
    ],
    'numberFormat' => '#,##'
    
];