<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Antler - Hosting Provider & WHMCS Template</title>
    <meta name="description" content="">
    <link href="./assets/img/favicon.ico" rel="shortcut icon">
    <!-- Fonts -->
    <link href="./assets/fonts/cloudicon/cloudicon.css" rel="stylesheet" onload="if(media!='all')media='all'">
    <link href="./assets/fonts/fontawesome/css/all.min.css" rel="stylesheet" onload="if(media!='all')media='all'">
    <link href="./assets/fonts/evafeat/evafeat.css" rel="stylesheet" onload="if(media!='all')media='all'">
    <!-- RTL STYLES-->
    <link href="./assets/css/rtl/bootstrap-rtl.min.css" disabled="true" rel="stylesheet" class="rtl">
    <link href="./assets/css/rtl/style-rtl.min.css" disabled="true" rel="stylesheet" class="rtl">
    <!-- CSS Styles -->
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" class="ltr" onload="if(media!='all')media='all'">
    <link href="./assets/css/flickity.min.css" rel="stylesheet" onload="if(media!='all')media='all'">
    <link href="./assets/css/aos.min.css" rel="stylesheet" onload="if(media!='all')media='all'">
    <link href="./assets/css/swiper.min.css" rel="stylesheet" onload="if(media!='all')media='all'">
    <link href="./assets/css/animate.min.css" rel="stylesheet" onload="if(media!='all')media='all'">
    <link href="./assets/css/style.css" rel="stylesheet">
    <link href="./assets/css/custom.css" rel="stylesheet">
    <link href="./assets/css/gdpr-cookie.min.css" rel="stylesheet">
</head>

<body>


    <!-- ***** NEWS ***** -->
    <div class="sec-bg3 infonews">
        <div class="container-fluid">
            <div class="row">
                <div class="col-6 col-md-6 news">
                    <span class="badge bg-purple me-2">news</span>
                    <span>SSD Storage with increased flexibility.</span>
                    <span> <a class="c-yellow" href="vps">Cloud Overview <i
                                class="fas fa-arrow-circle-right"></i></a></span>
                </div>
                <div class="col-6 col-md-6 link">
                    <div class="infonews-nav float-end">
                        <a href="blog-details" title="Blog" class="iconews"><i
                                class="ico-message-content f-18 w-icon"></i></a>
                        <a href="contact" title="Contact Us" class="iconews"><i class="ico-bell f-18 w-icon"></i></a>
                        <a href="{{ url('/client/index.php?rp=/login') }}" title="Login" class="iconews"><i
                                class="ico-shopping-cart f-18 w-icon"></i></a>
                        <a href="tel:1300-656-1046" class="iconews tabphone">+ (256) 783-204-665</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ***** NAV MENU DESKTOP ****** -->
    <div class="menu-wrap">
        <div class="nav-menu">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-2 col-md-2">
                        <a href="#">
                            <img class="svg logo-menu d-block" style="width: 130%;" src="./assets/img/logo.png"
                                alt="logo 8tech">
                            <img class="svg logo-menu d-none" src="./assets/img/logo.png" alt="logo 8tech">
                        </a>
                    </div>
                    <nav id="menu" class="col-10 col-md-10">
                        <div class="navigation float-end">
                            <button class="menu-toggle">
                                <span class="icon"></span>
                                <span class="icon"></span>
                                <span class="icon"></span>
                            </button>
                            <ul class="main-menu nav navbar-nav navbar-right">
                                <li class="menu-item menu-item-has-children me-2">
                                    <a class="m-0 pe-1" href="javascript:;" data-i18n="[html]header.home"> </a>
                                </li>
                                <li class="menu-item menu-item-has-children me-2">
                                    <a class="m-0 pe-1 v-stroke" href="javascript:;"
                                        data-i18n="[html]header.services">
                                    </a>
                                    <div class="badge bg-pink me-4">PRO</div>
                                    <div class="sub-menu menu-large bg-colorstyle">
                                        <div class="service-list">
                                            <div class="service">
                                                <img class="svg" src="./assets/fonts/svg/cloudfiber.svg"
                                                    alt="Shared Hosting">
                                                <div class="media-body">
                                                    <a class="menu-item mergecolor" href="javascrip:;"
                                                        data-i18n="[html]submenu.hosting"> </a>
                                                    <p class="seccolor">Blazing fast hosting on performance server</p>
                                                </div>
                                            </div>
                                            <div class="service">
                                                <img class="svg" src="./assets/fonts/svg/ceo.svg"
                                                    alt="Corporate Hosting">
                                                <div class="media-body">
                                                    <a class="menu-item mergecolor" href="javascrip:;"> Corporate
                                                        Hosting</a>
                                                    <p class="seccolor">Lorem ipsum dolor sit amet, consectetur</p>
                                                </div>
                                            </div>
                                            <div class="service">
                                                <img class="svg" src="./assets/fonts/svg/wordpress.svg"
                                                    alt="WordPress Hosting">
                                                <div class="media-body">
                                                    <a class="menu-item mergecolor" href="javascrip:;"
                                                        data-i18n="[html]submenu.wordpress"> </a>
                                                    <p class="seccolor">On the other hand, we denounce with</p>
                                                </div>
                                            </div>
                                            <div class="service">
                                                <img class="svg" src="./assets/fonts/svg/windows.svg"
                                                    alt="windows">
                                                <div class="media-body">
                                                    <a class="menu-item mergecolor" href="javascrip:;">Windows
                                                        hosting</a>
                                                    <p class="seccolor">Best windows hosting in Uganda</p>
                                                </div>
                                            </div>
                                            <div class="service">
                                                <img class="svg" src="./assets/fonts/svg/code.svg"
                                                    alt="Developer Hosting">
                                                <div class="media-body">
                                                    <a class="menu-item mergecolor" href="javascrip:;"
                                                        data-i18n="[html]submenu.developer"> </a>
                                                    <p class="seccolor">Deidcated servers fro Developers</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="menu-item menu-item-has-children">
                                    <a class="v-stroke" href="javascript:;">Our Services</a>
                                    <div class="sub-menu megamenu-list">
                                        <div class="container">
                                            <div class="row">
                                                <div class="service-list col-md-9 bg-colorstyle">
                                                    <div class="row">
                                                        <div class="col-4 service">
                                                            <div class="media-body">
                                                                <div class="top-head">
                                                                    <img class="svg"
                                                                        src="./assets/fonts/svg/favorite.svg"
                                                                        alt="Services">
                                                                    <div class="menu-item c-grey mergecolor"
                                                                        data-i18n="[html]submenu.services"> </div>
                                                                </div>
                                                                <hr>
                                                                <ul>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.emailsecurity">
                                                                        </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.ssl"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="email"
                                                                            data-i18n="[html]submenu.email"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.magento"> </a>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.gsuite"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.iptv"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.gaming"> </a>
                                                                        <div class="badge inside bg-pink">NEW</div>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.radiostream"> </a>
                                                                        <div class="badge inside bg-pink">NEW</div>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 service">
                                                            <div class="media-body">
                                                                <div class="top-head">
                                                                    <img class="svg"
                                                                        src="./assets/fonts/svg/infrastructure.svg"
                                                                        alt="Infrastructure">
                                                                    <div class="menu-item c-grey mergecolor"
                                                                        data-i18n="[html]submenu.infrastructure">
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <ul>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.ddos"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="datacenter"
                                                                            data-i18n="[html]submenu.datacenter"> </a>
                                                                        <div class="badge inside bg-grey">TOP</div>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.about"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.database"> </a>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.vpnsolutions"></a>
                                                                        <div class="badge inside bg-pink">NEW</div>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.voip"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.voice"> </a>
                                                                        <div class="badge inside bg-pink">NEW</div>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.seo"> </a>
                                                                        <div class="badge inside bg-pink">NEW</div>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 service">
                                                            <div class="media-body">
                                                                <div class="top-head">
                                                                    <img class="svg"
                                                                        src="./assets/fonts/svg/global.svg"
                                                                        alt="Global">
                                                                    <div class="menu-item c-grey mergecolor"
                                                                        data-i18n="[html]submenu.others"> </div>
                                                                </div>
                                                                <hr>
                                                                <ul>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.payments"> </a>
                                                                        <div class="badge inside bg-pink">NEW</div>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.config"> </a>
                                                                        <div class="badge inside bg-grey">HOT</div>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.cart"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.bloggrid"> </a>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.checkout"> </a>
                                                                    </li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.soon"> </a></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.promos"> </a> <i
                                                                            class="fas fa-tags c-pink ms-2"></i></li>
                                                                    <li class="menu-item"><a class="mergecolor"
                                                                            href="javascript:;"
                                                                            data-i18n="[html]submenu.blackfriday"> </a>
                                                                        <div class="badge inside bg-pink">HOT</div>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="start-offer col-md-3">
                                                    <div class="inner">
                                                        <h4 class="title my-3">Cloud VPS SSD <br>Get 50% Discount</h4>
                                                        <div class="inner-content mb-4">Enjoy increased flexibility and
                                                            get the performance you need with SSD Storage.</div>
                                                        <span class="m-0">Before <del class="c-pink">$20.00
                                                                /mo</del></span><br>
                                                        <h4 class="m-0"><b>Now</b> <b class="c-pink">$9.99 /mo</b>
                                                        </h4>
                                                        <a href="javascript:;"
                                                            class="btn btn-default-pink-fill mt-4">See
                                                            Plans</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="menu-item menu-item-has-children menu-last">
                                    <a class="v-stroke" href="javascript:;" data-i18n="[html]header.support"></a>
                                    <div class="sub-menu megamenu">
                                        <div class="container">
                                            <div class="row">
                                                <div class="service-list col-md-9 bg-colorstyle">
                                                    <div class="row">
                                                        <div class="col-4 service">
                                                            <div class="media-left">
                                                                <img class="svg"
                                                                    src="./assets/fonts/svg/bookmark.svg"
                                                                    alt="Knowledgebase">
                                                            </div>
                                                            <div class="media-body">
                                                                <a class="menu-item mergecolor"
                                                                    href="knowledgebase-list"
                                                                    data-i18n="[html]submenu.knowlist"> </a>
                                                                <p class="seccolor">Lorem ipsum dolor sit amet,
                                                                    consectetur adipiscing</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 service">
                                                            <div class="media-left">
                                                                <img class="svg" src="./assets/fonts/svg/book.svg"
                                                                    alt="Knowledgebase">
                                                            </div>
                                                            <div class="media-body">
                                                                <a class="menu-item mergecolor"
                                                                    href="knowledgebase-article"
                                                                    data-i18n="[html]submenu.knowarticle"> </a>
                                                                <p class="seccolor">Eaque ipsa quae ab illo inventore
                                                                    veritatis et quasi</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 service">
                                                            <div class="media-left">
                                                                <img class="svg"
                                                                    src="./assets/fonts/svg/emailopen.svg"
                                                                    alt="Knowledgebase">
                                                            </div>
                                                            <div class="media-body">
                                                                <a class="menu-item mergecolor" href="contact"
                                                                    data-i18n="[html]submenu.contact"> </a>
                                                                <p class="seccolor">Lorem ipsum dolor sit amet,
                                                                    consectetur adipiscing</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 service">
                                                            <div class="media-left">
                                                                <img class="svg"
                                                                    src="./assets/fonts/svg/compare.svg"
                                                                    alt="Knowledgebase">
                                                            </div>
                                                            <div class="media-body">
                                                                <a class="menu-item mergecolor" href="legal"
                                                                    data-i18n="[html]submenu.legal"> </a>
                                                                <div class="badge inside bg-grey ms-1">NEW</div>
                                                                <p class="seccolor">Eaque ipsa quae ab illo inventore
                                                                    veritatis et quasi</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 service">
                                                            <div class="media-left">
                                                                <img class="svg" src="./assets/fonts/svg/blog.svg"
                                                                    alt="Knowledgebase">
                                                            </div>
                                                            <div class="media-body">
                                                                <div>
                                                                    <a class="menu-item mergecolor"
                                                                        href="blog-details"
                                                                        data-i18n="[html]submenu.blogdetails"> </a>
                                                                    <div class="badge inside bg-pink ms-1">HOT</div>
                                                                </div>
                                                                <p class="seccolor">Lorem ipsum dolor sit amet,
                                                                    consectetur adipiscing</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 service">
                                                            <div class="media-left">
                                                                <img class="svg"
                                                                    src="./assets/fonts/svg/question.svg"
                                                                    alt="Knowledgebase">
                                                            </div>
                                                            <div class="media-body">
                                                                <a class="menu-item mergecolor" href="faq"
                                                                    data-i18n="[html]submenu.faq"> </a>
                                                                <p class="seccolor">Eaque ipsa quae ab illo inventore
                                                                    veritatis et quasi</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="start-offer col-md-3">
                                                    <div class="inner">
                                                        <h4 class="title my-3">Support Premium</h4>
                                                        <div class="inner-content"><span>Call us:</span> <b>+ (256)
                                                                783-204-665</b> HeadQuarters - No.01 - 399-0 Lorem
                                                            Ntinda Kisaasi</div>
                                                        <a href="contact"
                                                            class="btn btn-default-yellow-fill mt-4">Contact Us</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <div class="tech-box">
                                    <img class="svg" src="./assets/img/menu.svg" alt=""
                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBackdrop"
                                        aria-controls="offcanvasWithBackdrop">
                                </div>
                                <li class="menu-item">
                                    <a class="pe-0 me-0" href="{{ url('/client/index.php?rp=/login') }}">
                                        <div class="btn btn-default-yellow-fill question"><span
                                                data-i18n="[html]header.login"></span> <i
                                                class="fas fa-lock ps-1"></i> </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- ***** NAV MENU MOBILE ****** -->
    <div class="menu-wrap mobile">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-6">
                    <a href="{{ url('') }}"><img class="svg logo-menu d-block" src="./assets/img/logo.png"
                            alt="logo Antler"></a>
                    <a href="{{ url('') }}"><img class="svg logo-menu d-none" src="./assets/img/logo.png"
                            alt="logo Antler"></a>
                </div>
                <div class="col-6">
                    <nav class="nav-menu float-end d-flex">
                        <div class="tech-box">
                            <img class="svg" src="./assets/img/menu.svg" alt=""
                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBackdrop"
                                aria-controls="offcanvasWithBackdrop">
                        </div>
                        <button id="nav-toggle" class="menu-toggle">
                            <span class="icon"></span>
                            <span class="icon"></span>
                            <span class="icon"></span>
                        </button>
                        <div class="main-menu bg-seccolorstyle">
                            <div class="menu-item">
                                <a class="mergecolor" href="javascript:;" data-bs-toggle="dropdown">Home <div
                                        class="badge bg-purple">NEW</div></a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item menu-item" href="#">Home Default</a>
                                    <a class="dropdown-item menu-item" href="homevideo">Home Video</a>
                                    <a class="dropdown-item menu-item" href="homeimage">Home Image</a>
                                    <a class="dropdown-item menu-item" href="homegaming">Home Gaming <div
                                            class="badge inside bg-purple ms-2">NEW</div></a>
                                    <a class="dropdown-item menu-item" href="home3d">Home 3D <div
                                            class="badge inside bg-purple ms-2">NEW</div></a>
                                    <a class="dropdown-item menu-item"
                                        href="http://inebur.com/whmcs/?systpl=antler-rtl&language=arabic"
                                        target="_blank">WHMCS (RTL)
                                        <div class="badge inside bg-pink ms-2">HOT</div>
                                    </a>
                                </div>
                            </div>
                            <div class="menu-item">
                                <a class="mergecolor" href="javascript:;" data-bs-toggle="dropdown">Hosting <div
                                        class="badge bg-purple">PRO</div></a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item menu-item" href="hosting">Shared Hosting</a>
                                    <a class="dropdown-item menu-item" href="reseller">Cloud Reseller</a>
                                    <a class="dropdown-item menu-item" href="dedicated">Dedicated Server</a>
                                    <a class="dropdown-item menu-item" href="vps">Cloud VPS</a>
                                    <a class="dropdown-item menu-item" href="wordpress">WordPress Hosting</a>
                                    <a class="dropdown-item menu-item" href="domains">Domain Names</a>
                                    <a class="dropdown-item menu-item" href="developer">Developer Hosting</a>
                                </div>
                            </div>
                            <div class="menu-item">
                                <a class="mergecolor" href="javascript:;" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item menu-item" href="javascript:;">Email Security</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">SSL Certificates</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Enterprise Email</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Magento Pro</a>
                                    <a class="dropdown-item menu-item" href="javascript:;"> Suite - Google</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">IPTV System</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Gaming Server</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Radio Stream <div
                                            class="badge inside bg-pink ms-2">NEW</div></a>
                                    <a class="dropdown-item menu-item" href="javascript:;">DDoS Protection</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Datacenter <div
                                            class="badge inside bg-grey ms-2">TOP</div></a>
                                    <a class="dropdown-item menu-item" href="javascript:;">About Us</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Database-as-a-Service</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">VPN Solutions <div
                                            class="badge inside bg-pink ms-2">NEW</div></a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Virtual Numbers</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Voice Server <div
                                            class="badge inside bg-pink ms-2">NEW</div></a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Managed SEO Services <div
                                            class="badge inside bg-pink ms-2">NEW</div></a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Payment Methods <div
                                            class="badge inside bg-pink ms-2">NEW</div></a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Configurator <div
                                            class="badge inside bg-grey ms-2">HOT</div></a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Cart</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Checkout</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Coming Soon</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Promotions</a>
                                    <a class="dropdown-item menu-item" href="javascript:;">Blackfriday <div
                                            class="badge inside bg-pink ms-2">HOT</div></a>
                                </div>
                            </div>
                            <div class="menu-item">
                                <a class="mergecolor" href="javascript:;" data-bs-toggle="dropdown">Features</a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item menu-item" href="http://inebur.com/whmcs/?systpl=antler"
                                        target="_blank">WHMCS Template <div class="badge inside bg-pink">HOT</div></a>
                                    <a class="dropdown-item menu-item"
                                        href="http://inebur.com/whmcs/cart.php?carttpl=antler&systpl=antler"
                                        target="_blank">Order Form Template <div class="badge inside bg-grey">TOP
                                        </div></a>
                                    <a class="dropdown-item menu-item" href="http://inebur.com/antler/email/"
                                        target="_blank">HMCS Email Template <div class="badge inside bg-grey">TOP
                                        </div></a>
                                    <a class="dropdown-item menu-item" href="http://inebur.com/antler/newsletter/"
                                        target="_blank">WHMCS Newsletter Template <div class="badge inside bg-grey">
                                            TOP</div></a>
                                    <a class="dropdown-item menu-item" href="pricing">Pricing Options</a>
                                    <a class="dropdown-item menu-item" href="sliders">Content Sliders</a>
                                    <a class="dropdown-item menu-item" href="configurator">Configurator</a>
                                    <a class="dropdown-item menu-item" href="404">404 Error</a>
                                    <a class="dropdown-item menu-item"
                                        href="{{ url('/client/index.php?rp=/login') }}">Register</a>
                                    <a class="dropdown-item menu-item"
                                        href="{{ url('/client/index.php?rp=/login') }}">Client Area</a>
                                    <a class="dropdown-item menu-item" href="elements">Elements</a>
                                    <a class="dropdown-item menu-item" href="sections">Sections</a>
                                </div>
                            </div>
                            <div class="menu-item menu-last">
                                <a class="mergecolor" href="javascript:;" data-bs-toggle="dropdown">Support</a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item menu-item" href="knowledgebase-list">Knowlege List</a>
                                    <a class="dropdown-item menu-item" href="knowledgebase-article">Knowlege
                                        Article</a>
                                    <a class="dropdown-item menu-item" href="contact">Contact Us</a>
                                    <a class="dropdown-item menu-item" href="legal">Legal</a>
                                    <a class="dropdown-item menu-item" href="blog-details">Blog</a>
                                    <a class="dropdown-item menu-item" href="faq">Faq</a>
                                </div>
                            </div>
                            <div class="float-start w-100 mt-3">
                                <p class="c-grey-light seccolor"> <small> Phone: + (256) 783-204-665</small> </p>
                                <p class="c-grey-light seccolor"><small>Email: antler@mail.com</small> </p>
                            </div>
                            <div>
                                <a href="{{ url('/client/index.php?rp=/login') }}">
                                    <div class="btn btn-default-yellow-fill mt-3">CLIENT AREA</div>
                                </a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- ***** Sidebar ****** -->
    <div class="offcanvas offcanvas-start offcanvas-box bg-colorstyle" tabindex="-1" id="offcanvasWithBackdrop"
        aria-labelledby="offcanvasWithBackdropLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title mergecolor" id="offcanvasWithBackdropLabel">Special Deals</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="row mb-4">
                <h6 class="mergecolor">Free Trials</h6>
                <div class="col">
                    <a href="hosting">
                        <div class="card mb-4 b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                            <div class="plans badge feat bg-purple">free migration</div>
                            <img src="./assets/img/topbanner05.jpg" class="card-img-top" alt="Shared Hosting">
                            <div class="card-body">
                                <h6 class="card-title text-dark mergecolor">Shared Hosting</h6>
                                <p class="card-text text-dark seccolor"><small>Blazing fast & stable hosting
                                        infrastructure</small></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a href="emailsecurity">
                        <div class="card mb-4 b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                            <div class="plans badge feat bg-purple">free test</div>
                            <img src="./assets/img/topbanner03.jpg" class="card-img-top" alt="Email Security">
                            <div class="card-body">
                                <h6 class="card-title text-dark mergecolor">Email Security</h6>
                                <p class="card-text text-dark seccolor"><small>Powerful protection for emails with
                                        intelligent cluster</small></p>
                            </div>
                        </div>
                </div>
                </a>
            </div>
            <div class="row mb-4">
                <h6 class="mergecolor">Special Promotions</h6>
                <div class="col-md-6">
                    <a href="hosting">
                        <div class="card mb-4 b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                            <div class="plans badge feat bg-grey">50%</div>
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img class="svg img-fluid rounded-start" src="./assets/fonts/svg/cloudfiber.svg"
                                        alt="Shared Hosting">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark mergecolor">Hosting</h6>
                                        <p class="card-text text-dark seccolor"><small>Storage SSD, CloudLinux,
                                                cPanel..</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="domains">
                        <div class="card mb-4 b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                            <div class="plans badge feat bg-grey">$0.77</div>
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img class="svg img-fluid rounded-start" src="./assets/fonts/svg/domains.svg"
                                        alt="Email Security">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark mergecolor">Domains</h6>
                                        <p class="card-text text-dark seccolor"><small>More than 900 domains
                                                extensions..</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="gsuite">
                        <div class="card mb-4 b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                            <div class="plans badge feat bg-purple">55%</div>
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img class="svg img-fluid rounded-start" src="./assets/fonts/svg/docbox.svg"
                                        alt="G Suite Google">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark mergecolor">G Suite</h6>
                                        <p class="card-text text-dark seccolor"><small>Email, Chat, Apps, Cloud
                                                Storage..</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="ssl">
                        <div class="card mb-4 b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                            <div class="plans badge feat bg-purple">35%</div>
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img class="svg img-fluid rounded-start" src="./assets/fonts/svg/privacy.svg"
                                        alt="Wilcard SSL">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark mergecolor">Wilcard</h6>
                                        <p class="card-text text-dark seccolor"><small>Security, credibility and trust
                                                visitors..</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row mb-4">
                <h6 class="mergecolor">Flexible Operating Systems</h6>
                <p class="seccolor"><small>Install over +300 scripts and apps instantly with our auto
                        installer.</small></p>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/centos.svg" alt="Centos">
                        <p class="mb-0 seccolor">Centos</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/debian.svg" alt="Centos">
                        <p class="mb-0 seccolor">Debian</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/docker.svg" alt="Centos">
                        <p class="mb-0 seccolor">Docker</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/drupal.svg" alt="Centos">
                        <p class="mb-0 seccolor">Drupal</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/cpanel.svg" alt="Centos">
                        <p class="mb-0 seccolor">cPanel</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/fedora.svg" alt="Centos">
                        <p class="mb-0 seccolor">Fedora</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/gitlab.svg" alt="Centos">
                        <p class="mb-0 seccolor">Gitlab</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/joomla.svg" alt="Centos">
                        <p class="mb-0 seccolor">Joomla</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/joomla.svg" alt="Centos">
                        <p class="mb-0 seccolor">Lamp</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/magento.svg" alt="Centos">
                        <p class="mb-0 seccolor">Magento</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/openvpn.svg" alt="Centos">
                        <p class="mb-0 seccolor">VPN</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/owncloud.svg" alt="Centos">
                        <p class="mb-0 seccolor">OWN</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/prestashop.svg" alt="Centos">
                        <p class="mb-0 seccolor">Presta</p>
                    </a>
                </div>
                <div class="os b-radius15 upping cursor-p noshadow bg-seccolorstyle">
                    <a href="javascript:;">
                        <img class="svg" src="./assets/apps/windows.svg" alt="Centos">
                        <p class="mb-0 seccolor">Windows</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- ***** TRANSLATION ****** -->
    <section id="drop-lng" class="btn-group btn-group-toggle toplang">
        <label data-lng="en-US" for="option1" class="btn btn-secondary me-2">
            <input type="radio" name="options" id="option1" checked> EN
        </label>
        <label data-lng="pt-PT" for="option2" class="btn btn-secondary">
            <input type="radio" name="options" id="option2"> PT
        </label>
    </section>
    <!-- Javascript -->
    <script>
        $("#nav-toggle").click(function() {
            $(".menu-wrap.mobile, .menu-toggle").toggleClass("active");
        });
    </script>


    <div class="box-container limit-width">
        <!-- ***** SETTINGS ****** -->
        <section id="settings"> </section>
        <!-- ***** LOADING PAGE ****** -->
        <div id="spinner-area">
            <div class="spinner">
                <div class="double-bounce1"></div>
                <div class="double-bounce2"></div>
                <div class="spinner-txt">Antler...</div>
            </div>
        </div>
        <!-- ***** FRAME MODE ****** -->
        <div class="body-borders" data-border="20">
            <div class="top-border bg-white"></div>
            <div class="right-border bg-white"></div>
            <div class="bottom-border bg-white"></div>
            <div class="left-border bg-white"></div>
        </div>
        <!-- ***** UPLOADED MENU FROM HEADER.HTML ***** -->
        <header id="header"> </header>
