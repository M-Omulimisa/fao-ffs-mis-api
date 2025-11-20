@foreach($css as $c)
    <link rel="stylesheet" href="{{ admin_asset("$c") }}">
@endforeach

<?php

$primt_color = '#05179F'; 
?><style> 
    .sidebar {
        background-color: #FFFFFF;
    }

    .content-header {
        background-color: #F9F9F9;
    }

    .sidebar-menu .active {
        border-left: solid 5px {{ $primt_color }} !important;
        ;
        color: {{ $primt_color }} !important;
        ;
    }


    .navbar,
    .logo,
    .sidebar-toggle,
    .user-header,
    .btn-dropbox,
    .btn-twitter,
    .btn-instagram,
    .btn-primary,
    .navbar-static-top {
        background-color: {{ $primt_color }} !important;
    }

    .dropdown-menu {
        border: none !important;
    }

    .box-success {
        border-top: {{ $primt_color }} .5rem solid !important;
    }

    :root {
        --primary: {{ $primt_color }};
    }
    
    /* Simple design - no gradients, square corners */
    .card,
    .box,
    .info-box,
    .small-box,
    .panel,
    .modal-content,
    .form-control,
    .btn,
    .input-group-addon,
    .dropdown-menu,
    .nav-tabs-custom {
        border-radius: 0 !important;
        box-shadow: none !important;
        background-image: none !important;
    }
    
    .card,
    .box {
        border: 1px solid #e0e0e0 !important;
    }
    
    .small-box {
        border: none !important;
        background: {{ $primt_color }} !important;
    }
    
    .info-box {
        border: 1px solid #e0e0e0 !important;
        background: #ffffff !important;
    }
    
    .info-box-icon {
        background: {{ $primt_color }} !important;
        border-radius: 0 !important;
    }
    
    .btn-primary {
        background: {{ $primt_color }} !important;
        border: none !important;
        border-radius: 0 !important;
    }
    
    .btn {
        border-radius: 0 !important;
    }
    
    .form-control {
        border-radius: 0 !important;
        border: 1px solid #d2d6de !important;
    }
    
    .nav-tabs-custom {
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    
    .nav-tabs-custom > .nav-tabs > li.active {
        border-top-color: {{ $primt_color }} !important;
    }
</style> 
