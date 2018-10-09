<?php

$textmarks = array(
    'COURSENAME',
);

$attributes = array();

$formatters = array(
    'ucase',
    'lcase',
    'ucasefirst'
);

// --------------  do not modify

$attributes[] = '';
$formatters[] = '';

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
fputcsv($file, [ 'textmark', 'name', 'attribute', 'formatter', 'expected']);
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
