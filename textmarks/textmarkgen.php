<?php

$textmarks = array(
    'PROFILE',
    'EMAIL',
    'ICQ',
    'SKYPE',
    'YAHOO',
    'AIM',
    'MSN',
    'PHONE1',
    'PHONE2',
    'INSTITUTION',
    'DEPARTMENT',
    'ADDRESS',
    'CITY',
    'COUNTRY',
    'URL',
    'USERIMAGE'
);
$attributes = array(
    '',
    'email',
    'icq',
    'skype',
    'yahoo',
    'aim',
    'msn',
    'phone1',
    'phone2',
    'institution',
    'department',
    'address',
    'city',
    'country',
    'url',
    'userimage'
);
$formatters = array(
    '',
    'ucase',
    'lcase',
    'ucasefirst'
);

foreach ($textmarks as $tm) {
    foreach ($attributes as $attr) {
        foreach ($formatters as $fmt) {
            $str = $tm;
            if ($attr != '') {
                $str .=':' . $attr;
            }
            if ($fmt != '') {
                $str .=':' . $fmt;
            }
            echo "{$str}\n";
        }
    }
}

echo "------------------\n";

$file = fopen(__DIR__.'/test.csv', 'w');
foreach ($textmarks as $tm) {
    foreach ($attributes as $attr) {
        foreach ($formatters as $fmt) {
            $str = $tm;
            if ($attr != '') {
                $str .=':' . $attr;
            }
            if ($fmt != '') {
                $str .=':' . $fmt;
            }
            echo "'$str' => ['$tm' , '$attr', '$fmt', false],\n";
            fputcsv($file, [$str, $tm, $attr, $fmt, 'false']);
        }
    }
}
fclose($file);
