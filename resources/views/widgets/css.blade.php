<?php
$primt_color = '#6B3B00';
?><style>
    .sidebar {
        background-color: #FFFFFF;
    }

    .content-header {
        background-color: #F9F9F9;
    }

    .sidebar-menu .active {
        border-left: solid 6px {{ $primt_color }} !important;
        color: {{ $primt_color }} !important;
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

    p,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    label,
    input,
    .form-control,
    .control-label,
    .sidebar-menu,
    .sidebar-menu,
    .main-sidebar .user-panel,
    .sidebar-menu>li.header,
    div,
    .row,
    .col,
    body,
    * {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        font-size: 1.8rem;
     }
</style>
