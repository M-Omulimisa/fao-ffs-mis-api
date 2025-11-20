<?php
use App\Models\Utils;

$_disableSidebar = false;
$_offcanvas = ' offcanvas-enabled ';
if (isset($disableSidebar)) {
    if ($disableSidebar) {
        $_disableSidebar = true;
        $_offcanvas = '';
    }
}

$un_paid_order_sum = 0;
if (Auth::user() != null) {
    $un_paid_order_sum = Utils::un_paid_order_sum(Auth::user());
}
?>
<!DOCTYPE html>
<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=utf-8" />

<head>
    <script src="js/jquery.js"></script>
    <script src="{{ url('vendor/laravel-admin/jquery-pjax/jquery.pjax.js') }}"></script>

    <base href="{{ url('') }}/">

    <meta charset="utf-8">
    <title>Cartzilla | Grocery - Single product</title>
    <!-- SEO Meta Tags-->
    <meta name="description" content="Cartzilla - Bootstrap E-commerce Template">
    <meta name="keywords"
        content="bootstrap, shop, e-commerce, market, modern, responsive,  business, mobile, bootstrap, html5, css3, js, gallery, slider, touch, creative, clean">
    <meta name="author" content="Createx Studio">
    <!-- Viewport-->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon and Touch Icons-->
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="mask-icon" color="#fe6a6a" href="safari-pinned-tab.svg">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">
    <!-- Vendor Styles including: Font Icons, Plugins, etc.-->
    <link rel="stylesheet" media="screen" href="vendor/simplebar/dist/simplebar.min.css" />
    <link rel="stylesheet" media="screen" href="vendor/tiny-slider/dist/tiny-slider.css" />
    <link rel="stylesheet" media="screen" href="vendor/drift-zoom/dist/drift-basic.min.css" />
    <link rel="stylesheet" media="screen" href="vendor/lightgallery.js/dist/css/lightgallery.min.css" />
    <!-- Main Theme Styles + Bootstrap-->
    <link rel="stylesheet" media="screen" href="css/theme.min.css">

</head>
<!-- Body-->

<body class="bg-secondary">
    <!-- Google Tag Manager (noscript)-->
    <noscript>
        <iframe src="../external.html?link=http://www.googletagmanager.com/ns.html?id=GTM-WKV3GT5" height="0"
            width="0" style="display: none; visibility: hidden;"></iframe>
    </noscript>
    <!-- Sign in / sign up modal-->
    <div class="modal fade" id="signin-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link fw-medium active" href="#signin-tab"
                                data-bs-toggle="tab" role="tab" aria-selected="true"><i
                                    class="ci-unlocked me-2 mt-n1"></i>Sign in</a></li>
                        <li class="nav-item"><a class="nav-link fw-medium" href="#signup-tab" data-bs-toggle="tab"
                                role="tab" aria-selected="false"><i class="ci-user me-2 mt-n1"></i>Sign up</a></li>
                    </ul>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body tab-content py-4">
                    <form class="needs-validation tab-pane fade show active" autocomplete="off" novalidate
                        id="signin-tab">
                        <div class="mb-3">
                            <label class="form-label" for="si-email">Email address</label>
                            <input class="form-control" type="email" id="si-email" placeholder="johndoe@example.com"
                                required>
                            <div class="invalid-feedback">Please provide a valid email address.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="si-password">Password</label>
                            <div class="password-toggle">
                                <input class="form-control" type="password" id="si-password" required>
                                <label class="password-toggle-btn" aria-label="Show/hide password">
                                    <input class="password-toggle-check" type="checkbox"><span
                                        class="password-toggle-indicator"></span>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3 d-flex flex-wrap justify-content-between">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="si-remember">
                                <label class="form-check-label" for="si-remember">Remember me</label>
                            </div><a class="fs-sm" href="#">Forgot password?</a>
                        </div>
                        <button class="btn btn-primary btn-shadow d-block w-100" type="submit">Sign in</button>
                    </form>
                    <form class="needs-validation tab-pane fade" autocomplete="off" novalidate id="signup-tab">
                        <div class="mb-3">
                            <label class="form-label" for="su-name">Full name</label>
                            <input class="form-control" type="text" id="su-name" placeholder="John Doe"
                                required>
                            <div class="invalid-feedback">Please fill in your name.</div>
                        </div>
                        <div class="mb-3">
                            <label for="su-email">Email address</label>
                            <input class="form-control" type="email" id="su-email"
                                placeholder="johndoe@example.com" required>
                            <div class="invalid-feedback">Please provide a valid email address.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="su-password">Password</label>
                            <div class="password-toggle">
                                <input class="form-control" type="password" id="su-password" required>
                                <label class="password-toggle-btn" aria-label="Show/hide password">
                                    <input class="password-toggle-check" type="checkbox"><span
                                        class="password-toggle-indicator"></span>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="su-password-confirm">Confirm password</label>
                            <div class="password-toggle">
                                <input class="form-control" type="password" id="su-password-confirm" required>
                                <label class="password-toggle-btn" aria-label="Show/hide password">
                                    <input class="password-toggle-check" type="checkbox"><span
                                        class="password-toggle-indicator"></span>
                                </label>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-shadow d-block w-100" type="submit">Sign up</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar-->
    <header class="bg-light shadow-sm fixed-top" data-fixed-element>
        <div class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid"><a data-pjax
                    class="navbar-brand d-none d-sm-block me-3 me-xl-4 flex-shrink-0" href="{{ url('market') }}"><img
                        src="img/logo-dark.png" width="142" alt="Cartzilla"></a><a
                    class="navbar-brand d-sm-none me-2" href="{{ url('market') }}"><img src="img/logo-icon.png"
                        width="74" alt="Cartzilla"></a>
                <!-- Search-->

                <!-- Toolbar-->
                <div class="navbar-toolbar d-flex flex-shrink-0 align-items-center ms-xl-2">
                    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#sideNav"><span class="navbar-toggler-icon"></span></button><a
                        class="navbar-tool d-flex d-lg-none" href="#searchBox" data-bs-toggle="collapse"
                        role="button" aria-expanded="false" aria-controls="searchBox"><span
                            class="navbar-tool-tooltip">Search</span>
                        <div class="navbar-tool-icon-box"><i class="navbar-tool-icon ci-search"></i></div>
                    </a>
                    <a class="navbar-tool ms-1 ms-lg-0 me-n1 me-lg-2"
                        href="{{ Auth::user() != null ? route('account-orders') : route('m-register') }}">
                        <div class="navbar-tool-icon-box"><i class="navbar-tool-icon ci-user"></i></div>
                        <div class="navbar-tool-text ms-n3"><small>Hello,
                                {{ Auth::user() != null ? Auth::user()->last_name : 'Sign in' }}</small>My
                            Account</div>
                    </a>
                    <div class="navbar-tool dropdown ms-3"><a
                            class="navbar-tool-icon-box bg-secondary dropdown-toggle"
                            href="grocery-checkout.html"><span class="navbar-tool-label">3</span><i
                                class="navbar-tool-icon ci-cart"></i></a><a class="navbar-tool-text"
                            href="grocery-checkout.html"><small>My Orders</small>UGX
                            {{ number_format($un_paid_order_sum) }}</a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <div class1="widget widget-cart px-3 pt-2 pb-3" style="width: 20rem;">
                                <div style="height: 15rem;" data-simplebar data-simplebar-auto-hide="false">
                                    <div class="widget-cart-item pb-2 border-bottom">
                                        <button class="btn-close text-danger" type="button"
                                            aria-label="Remove"><span aria-hidden="true">&times;</span></button>
                                        <div class="d-flex align-items-center"><a class="d-block"
                                                href="grocery-single.html"><img src="img/grocery/cart/th01.jpg"
                                                    width="64" alt="Product"></a>
                                            <div class="ps-2">
                                                <h6 class="widget-product-title"><a href="grocery-single.html">Frozen
                                                        Oven-ready Poultry</a></h6>
                                                <div class="widget-product-meta"><span
                                                        class="text-accent me-2">$15.<small>00</small></span><span
                                                        class="text-muted">x 1</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="widget-cart-item py-2 border-bottom">
                                        <button class="btn-close text-danger" type="button"
                                            aria-label="Remove"><span aria-hidden="true">&times;</span></button>
                                        <div class="d-flex align-items-center"><a class="d-block"
                                                href="grocery-single.html"><img src="img/grocery/cart/th02.jpg"
                                                    width="64" alt="Product"></a>
                                            <div class="ps-2">
                                                <h6 class="widget-product-title"><a href="grocery-single.html">Nut
                                                        Chocolate Paste (750g)</a></h6>
                                                <div class="widget-product-meta"><span
                                                        class="text-accent me-2">$6.<small>50</small></span><span
                                                        class="text-muted">x 1</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="widget-cart-item py-2 border-bottom">
                                        <button class="btn-close text-danger" type="button"
                                            aria-label="Remove"><span aria-hidden="true">&times;</span></button>
                                        <div class="d-flex align-items-center"><a class="d-block"
                                                href="grocery-single.html"><img src="img/grocery/cart/th03.jpg"
                                                    width="64" alt="Product"></a>
                                            <div class="ps-2">
                                                <h6 class="widget-product-title"><a
                                                        href="grocery-single.html">Mozzarella Mini Cheese</a></h6>
                                                <div class="widget-product-meta"><span
                                                        class="text-accent me-2">$3.<small>50</small></span><span
                                                        class="text-muted">x 1</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap justify-content-between align-items-center pt-3">
                                    <div class="fs-sm me-2 py-2"><span class="text-muted">Total:</span><span
                                            class="text-accent fs-base ms-1">$25.<small>00</small></span></div><a
                                        class="btn btn-primary btn-sm" href="grocery-checkout.html"><i
                                            class="ci-card me-2 fs-base align-middle"></i>Checkout<i
                                            class="ci-arrow-right ms-1 me-n1"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Search collapse-->
        <div class="collapse" id="searchBox">
            <div class="card pt-2 pb-4 border-0 rounded-0">
                <div class="container">
                    <div class="input-group"><i
                            class="ci-search position-absolute top-50 start-0 translate-middle-y ms-3"></i>
                        <input class="form-control rounded-start" type="text" placeholder="Search for products">
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar menu-->
    @if (!$_disableSidebar)
        @include('layouts.sidebar-main')
    @endif
    <!-- Page-->
    <main class="{{ $_offcanvas }}  content-wrapper" id="pjax-container" style="padding-top: 5rem;">

        {{ Utils::display_alert_message() }}
