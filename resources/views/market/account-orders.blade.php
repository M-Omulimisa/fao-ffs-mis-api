<?php
$isPjax = isset($isPjax) ? $isPjax : false;
?>@if (!$isPjax)
    @include('layouts.header')
@endif

account-orders

@if (!$isPjax)
    @include('layouts.footer')
@endif
