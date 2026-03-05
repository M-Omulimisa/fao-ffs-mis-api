<?php
use App\Models\Utils;

?>
<!-- Main Header -->
<header class="main-header">

    <!-- Logo -->
    <a href="{{ admin_url('/') }}" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini">{!! env('APP_NAME') !!}</span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg">{!! env('APP_NAME') !!}</span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top d-block p-0" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <ul class="nav navbar-nav hidden-sm visible-lg-block">
            {!! Admin::getNavbar()->render('left') !!}
        </ul>

        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav ">

                {!! Admin::getNavbar()->render() !!}

                <!-- User Account Menu -->
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <img src="{{ Admin::user()->avatar }}" class="user-image" alt="User Image" style="cursor:pointer;">
                        <span class="hidden-xs">{{ Admin::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="user-header">
                            <a href="{{ admin_url('auth/setting') }}">
                                <img src="{{ Admin::user()->avatar }}" class="img-circle" alt="User Image" style="cursor:pointer;border:3px solid rgba(255,255,255,0.5);">
                            </a>
                            <p>
                                {{ Admin::user()->name }}
                                <small>Member since {{ Admin::user()->created_at }}</small>
                            </p>
                        </li>
                        <li>
                            <div style="padding:10px 15px;">
                                <a href="{{ admin_url('auth/setting') }}" class="btn btn-default btn-block btn-flat" style="margin-bottom:6px;">
                                    <i class="fa fa-user"></i>&nbsp; My Profile
                                </a>
                                @if(Admin::user()->isAdministrator())
                                <a href="{{ admin_url('implementing-partners') }}" class="btn btn-default btn-block btn-flat" style="margin-bottom:6px;">
                                    <i class="fa fa-building"></i>&nbsp; Implementing Partners
                                </a>
                                <a href="{{ admin_url('users') }}" class="btn btn-default btn-block btn-flat" style="margin-bottom:6px;">
                                    <i class="fa fa-users"></i>&nbsp; System Users
                                </a>
                                <a href="{{ admin_url('system-configurations') }}" class="btn btn-default btn-block btn-flat" style="margin-bottom:6px;">
                                    <i class="fa fa-cogs"></i>&nbsp; System Config
                                </a>
                                <a href="{{ admin_url('import-tasks') }}" class="btn btn-default btn-block btn-flat" style="margin-bottom:6px;">
                                    <i class="fa fa-upload"></i>&nbsp; Data Import
                                </a>
                                <a href="{{ admin_url('pesapal-payments') }}" class="btn btn-default btn-block btn-flat" style="margin-bottom:6px;">
                                    <i class="fa fa-credit-card"></i>&nbsp; Payment Records
                                </a>
                                @endif
                                <a href="{{ admin_url('auth/logout') }}" class="btn btn-danger btn-block btn-flat" style="margin-top:8px;">
                                    <i class="fa fa-sign-out"></i>&nbsp; {{ trans('admin.logout') }}
                                </a>
                            </div>
                        </li>
                    </ul>
                </li>
                <!-- Control Sidebar Toggle Button -->
                {{-- <li> --}}
                {{-- <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a> --}}
                {{-- </li> --}}
            </ul>
        </div>
    </nav>
</header>
