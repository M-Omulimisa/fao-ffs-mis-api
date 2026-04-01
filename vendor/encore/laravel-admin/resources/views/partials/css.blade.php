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

    /* ── Admin header user-menu dropdown fix ─────────────────────────── */
    /* Prevent avatar images from blowing out the dropdown layout        */

    /* Navbar toggle avatar — fixed circle */
    .navbar-nav > .user-menu > .dropdown-toggle > .user-image {
        width: 25px;
        height: 25px;
        object-fit: cover;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* Dropdown panel itself — fixed width, no overflow */
    .navbar-nav > .user-menu > .dropdown-menu {
        width: 280px;
        min-width: 280px;
        max-width: 280px;
        border-radius: 0 !important;
        padding: 0;
        overflow: hidden;
    }

    /* User-header section — constrain height, center content */
    .navbar-nav > .user-menu > .dropdown-menu > .user-header {
        height: auto;
        min-height: 120px;
        padding: 20px 15px;
        text-align: center;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    /* Header avatar — fixed 70px circle, never stretch */
    .navbar-nav > .user-menu > .dropdown-menu > .user-header img {
        width: 70px;
        height: 70px;
        max-width: 70px;
        max-height: 70px;
        min-width: 70px;
        min-height: 70px;
        object-fit: cover;
        border-radius: 50% !important;
        display: block;
        margin: 0 auto 8px;
        flex-shrink: 0;
    }

    /* Header text — prevent long names from expanding dropdown */
    .navbar-nav > .user-menu > .dropdown-menu > .user-header p {
        color: #fff;
        margin: 0;
        font-size: 14px;
        line-height: 1.4;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }

    .navbar-nav > .user-menu > .dropdown-menu > .user-header p small {
        color: rgba(255,255,255,0.6);
        display: block;
        font-size: 11px;
    }
</style> 
