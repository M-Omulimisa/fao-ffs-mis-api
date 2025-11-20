@extends('layouts.main-layout')
@section('content')
    <!-- ***** SLIDER ***** -->
    <section class="main-container slider">
        <div class="silder-container">
            <div class="carousel header-main-slider" style="background-color: #573001">

                <!-- 2 Slider -->
                <div class="carousel-cell overlay">
                    <div class="slider-content">
                        <div class="container ">
                            <div class="row">
                                <div class="col-sm-12 col-md-8 px-0 pt-5">

                                    <h1 data-aos="fade-up" class="mt-4" data-aos-duration="800"
                                        style="font-size: 3rem; line-height: 3.2rem; letter-spacing: 1px">U-LITS is a system
                                        <br>that helps you <br>
                                        <span id="typed1"></span>
                                    </h1>
                                    <br>

                                    <br>
                                    <a href="{{ admin_url() }}" class="btn btn-default-yellow-fill me-2">Join Now <i
                                            class="fas fa-right-to-bracket ps-1 f-15"></i></a>
                                    <a href="{{ url('about') }}" class="btn btn-default-pink-fill">Learn more

                                        <i class="fas fa-circle-info ps-1 f-15"></i></a>
                                    </a>
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <img class="img img-fluid" src="assets/logo.png" alt="Ulits logo">
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="slider-video">
                        <div class="cover-wrapper">
                            <video class="cover-video" autoplay loop muted>
                                <source src="assets/videos/server.mp4" type="video/mp4">
                            </video>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- ***** PRICING TABLES ***** -->
    <section class="pricing special sec-bg2 bg-colorstyle specialposition">
        <div class="container">
            <div class="sec-up-slider nopadding">
                <div class="row">
                    <div class="col-md-12 col-lg-4">
                        <div class="wrapper first noshadow">
                            <div class="top-content bg-seccolorstyle topradius">
                                <img class="svg mb-3" src="./assets/fonts/svg/cloudfiber.svg" alt="">
                                <div class="title">Join U-LITS</div>
                                <div class="fromer seccolor">Get started with U-LITS system by
                                    taking simple steps as mentioned below.
                                </div>
                                <a href="hosting" class="btn btn-default-yellow-fill">All plans</a>
                            </div>
                            <ul class="list-info">
                                <li>
                                    <div class="row">
                                        <div class="col-2">
                                            <div style="width: 3rem; height: 3rem; font-size: 1.8rem;"
                                                class="text-white border border-1 text-center rounded-circle d-inline-block">
                                                1</div>
                                        </div>
                                        <div class="col-10 ">
                                            <span class="d-inline-block"
                                                style="color: rgb(195, 195, 195);">REGISTRATION</span><br> <span>Submit your
                                                account info</span>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="row">
                                        <div class="col-2">
                                            <div style="width: 3rem; height: 3rem; font-size: 1.8rem;"
                                                class="text-white border border-1 text-center rounded-circle d-inline-block">
                                                2</div>
                                        </div>
                                        <div class="col-10">
                                            <span class=" d-inline-block" style="color: rgb(195, 195, 195);">USER ROLE
                                                SELECTION</span><br> <span>Specify your role</span>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="row">
                                        <div class="col-2">
                                            <div style="width: 3rem; height: 3rem; font-size: 1.8rem;"
                                                class="text-white border border-1 text-center rounded-circle d-inline-block">
                                                3</div>
                                        </div>
                                        <div class="col-10">
                                            <span class="d-inline-block"
                                                style="color: rgb(195, 195, 195);">VERIFICATION</span><br> <span>
                                                Get verified by system admins
                                            </span>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="row">
                                        <div class="col-2">
                                            <div style="width: 3rem; height: 3rem; font-size: 1.8rem;"
                                                class="text-white border border-1 text-center rounded-circle d-inline-block">
                                                4</div>
                                        </div>
                                        <div class="col-10">
                                            <span class="d-inline-block" style="color: rgb(195, 195, 195);">SYSTEM
                                                MANAGEMENT</span><br> <span>Start using the system</span>
                                        </div>
                                    </div>
                                </li>


                            </ul>
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <div class="wrapper noshadow">
                            <div class="top-content bg-seccolorstyle topradius">
                                <img class="svg mb-3" src="./assets/fonts/svg/cloudfiber.svg" alt="">
                                <div class="title">Buy Cattle</div>
                                <div class="fromer seccolor">Buy or Sell your cattle on U-LITS by
                                    taking these simple steps.

                                    Otherwise you need to stop the tendency of ambushing someone into things like this.

                                    I have never been invol

                                    

                                </div>
                                <a href="hosting" class="btn btn-default-yellow-fill">Marketplace</a>
                            </div>
                            <ul class="list-info bg-purple">
                                <li>
                                    <div class="row">
                                        <div class="col-2">
                                            <div style="width: 3rem; height: 3rem; font-size: 1.8rem;"
                                                class="text-white border border-1 text-center rounded-circle d-inline-block">
                                                1</div>
                                        </div>
                                        <div class="col-10 ">
                                            <span class="d-inline-block"
                                                style="color: rgb(195, 195, 195);">REGISTRATION</span><br> <span>Submit your
                                                account info</span>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="row">
                                        <div class="col-2">
                                            <div style="width: 3rem; height: 3rem; font-size: 1.8rem;"
                                                class="text-white border border-1 text-center rounded-circle d-inline-block">
                                                2</div>
                                        </div>
                                        <div class="col-10">
                                            <span class=" d-inline-block" style="color: rgb(195, 195, 195);">USER ROLE
                                                SELECTION</span><br> <span>Specify your role</span>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="row">
                                        <div class="col-2">
                                            <div style="width: 3rem; height: 3rem; font-size: 1.8rem;"
                                                class="text-white border border-1 text-center rounded-circle d-inline-block">
                                                3</div>
                                        </div>
                                        <div class="col-10">
                                            <span class="d-inline-block"
                                                style="color: rgb(195, 195, 195);">VERIFICATION</span><br> <span>
                                                Get verified by system admins
                                            </span>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="row">
                                        <div class="col-2">
                                            <div style="width: 3rem; height: 3rem; font-size: 1.8rem;"
                                                class="text-white border border-1 text-center rounded-circle d-inline-block">
                                                4</div>
                                        </div>
                                        <div class="col-10">
                                            <span class="d-inline-block" style="color: rgb(195, 195, 195);">SYSTEM
                                                MANAGEMENT</span><br> <span>Start using the system</span>
                                        </div>
                                    </div>
                                </li>


                            </ul>
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <div class="wrapper third noshadow">
                            <div class="top-content bg-seccolorstyle topradius">
                                <img class="svg mb-3" src="./assets/fonts/svg/vps.svg" alt="">
                                <div class="title">Cloud VPS</div>
                                <div class="fromer seccolor">Starting at:</div>
                                <div class="price seccolor"><sup>$</sup>9.99 <span class="period">/month</span></div>
                                <a href="vps" class="btn btn-default-yellow-fill">All plans</a>
                            </div>
                            <ul class="list-info">
                                <li><i class="icon-cpu"></i> <span class="c-purple">CPU</span><br> <span>2 Cores</span>
                                </li>
                                <li><i class="icon-ram"></i> <span class="c-purple">RAM</span><br> <span>2GB Memory</span>
                                </li>
                                <li><i class="icon-drives"></i> <span class="c-purple">DISK</span><br> <span>20GB SSD
                                        Space</span></li>
                                <li><i class="icon-speed"></i> <span class="c-purple">DATA</span><br> <span>1TB
                                        Bandwidth</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>




    <!-- ***** CASE STUDY ***** -->
    <section class="casestudy sec-bg2 bg-colorstyle">
        <div class="container">
            <div class="sec-main sec-up bg-purple mb-0 nomargin">
                <img class="lazyload ltr-img d-block" src="./assets/img/casestudy.png"
                    data-src="./assets/img/casestudy.png" alt="Case Study">
                <img class="lazyload rtl-img d-none" src="./assets/img/casestudy-rtl.png"
                    data-src="./assets/img/casestudy-rtl.png" alt="Case Study">
                <div class="plans badge feat bg-dark">case study</div>
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-9">
                        <div class="slider-container slider-filter">
                            <div class="slider-wrap">
                                <div class="swiper-container main-slider" data-autoplay="4000" data-touch="1"
                                    data-mouse="0" data-slides-per-view="responsive" data-loop="1" data-speed="1200"
                                    data-mode="horizontal" data-xs-slides="1" data-sm-slides="1" data-md-slides="1"
                                    data-lg-slides="1">
                                    <div class="swiper-wrapper">
                                        <div class="swiper-slide">
                                            <h3 class="author">Everth Group</h3>
                                            <div class="content-info">
                                                <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem
                                                    accusantium doloremque laudantium, totamer rem aperiam, eaque ipsa quae
                                                    ab illo inventore veritatis et quasi.</p>
                                                <div class="mb-3"> Michael Jones - Executive Director</div>
                                                <a href="./assets/casestudy/casestudy-everthgroup.pdf"
                                                    class="btn btn-default-yellow-fill mb-2">Case Study Download</a>
                                            </div>
                                        </div>
                                        <div class="swiper-slide">
                                            <h3 class="author">Growtop Company</h3>
                                            <div class="content-info">
                                                <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem
                                                    accusantium doloremque laudantium, totamer rem aperiam, eaque ipsa quae
                                                    ab illo inventore.</p>
                                                <div class="mb-3"> Matt Radford - President &amp; Managing Director</div>
                                                <a href="./assets/casestudy/casestudy-growtop.pdf"
                                                    class="btn btn-default-yellow-fill mb-2">Case Study Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pagination vertical-mode pagination-index"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ***** HELP ***** -->
    <section class="services help sec-bg2 pt-4 pb-80 bg-colorstyle">
        <div class="container">
            <div class="service-wrap">
                <div class="row">
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="help-container bg-seccolorstyle noshadow">
                            <a href="javascript:void(Tawk_API.toggle())" class="help-item">
                                <div class="img">
                                    <img class="svg ico" src="./assets/fonts/svg/livechat.svg" height="65"
                                        alt="">
                                </div>
                                <div class="inform">
                                    <div class="title mergecolor">Live Chat</div>
                                    <div class="description seccolor">Lorem Ipsum is simply dummy text printing.</div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="help-container bg-seccolorstyle noshadow">
                            <a href="contact" class="help-item">
                                <div class="img">
                                    <img class="svg ico" src="./assets/fonts/svg/emailopen.svg" height="65"
                                        alt="">
                                </div>
                                <div class="inform">
                                    <div class="title mergecolor">Send Ticket</div>
                                    <div class="description seccolor">Lorem Ipsum is simply dummy text printing.</div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="help-container bg-seccolorstyle noshadow">
                            <a href="knowledgebase-list" class="help-item">
                                <div class="img">
                                    <img class="svg ico" src="./assets/fonts/svg/book.svg" height="65"
                                        alt="">
                                </div>
                                <div class="inform">
                                    <div class="title mergecolor">Knowledge base</div>
                                    <div class="description seccolor">Lorem Ipsum is simply dummy text printing.</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
