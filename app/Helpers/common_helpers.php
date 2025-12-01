<?php

use Illuminate\Support\Facades\Log;

function common_helper($data = '')
{
    $text = 'Common Helper';
    Log::critical(json_encode($text));
    dd($text);
}