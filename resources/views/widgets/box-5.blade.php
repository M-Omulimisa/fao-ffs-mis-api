<?php

$title = isset($title) ? $title : 'Title';
$number = isset($number) ? $number : '0.00';
$sub_title = isset($sub_title) ? $sub_title : 'Sub-titles';
$link = isset($link) ? $link : 'javascript:;';

if (!isset($is_dark)) {
    $is_dark = true;
}
$is_dark = ((bool) $is_dark);

$bg = '';
$text = 'text-primary';
$text2 = 'text-dark';
if ($is_dark) {
    $bg = 'bg-primary';
    $text = 'text-white';
    $text2 = 'text-white';
}
?><a href="{{ $link }}" class="card {{ $bg }} border-primary mb-4 mb-md-5 "
    style="border-radius: 10px; border: 5px red solid; box-shadow: rgba(0, 0, 0, 0.3) 0px 19px 38px, rgba(0, 0, 0, 0.22) 0px 15px 12px;">
    <div class="card-body py-2 px-2">
        <p class="text-bold mb-2 mb-md-3 {{ $text }} " style="font-weight: 700; font-size: 2.5rem;">
            {{ $title }}</p>
        <p class="display-3  m-0 text-right {{ $text2 }}" style="line-height: 3.2rem">{{ $number }}</p>
        <p class="mt-4 {{ $text2 }}">{{ $sub_title }}</p>
    </div>
</a>
