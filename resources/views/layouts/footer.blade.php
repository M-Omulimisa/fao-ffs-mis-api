<?php
$_disableSidebar = false;
$_offcanvas = ' offcanvas-enabled ';
if (isset($disableSidebar)) {
    if ($disableSidebar) {
        $_disableSidebar = true;
        $_offcanvas = '';
    }
}
?></main>



<div class="{{$_offcanvas}} content-wrapper">
    <!-- Back to top button -->
    <a href="#top" class="btn-scroll-top" data-scroll>
        <span class="btn-scroll-top-tooltip text-muted fs-sm me-2">Top</span>
        <i class="btn-scroll-top-icon bx bx-chevron-up"></i>
    </a>
    <footer class="footer bg-dark pt-5">
        <div class="px-lg-3 pt-2 pb-4">
            <div class="mx-auto px-3" style="max-width: 80rem;">
                <div class="row">
                    <div class="col-xl-2 col-lg-3 col-sm-4 pb-2 mb-4">
                        <div class="mt-n1"><a class="d-inline-block align-middle" href="#"><img
                                    class="d-block mb-4" src="img/footer-logo-light.png" width="117"
                                    alt="Cartzilla"></a></div>
                        <div class="btn-group dropdown disable-autohide">
                            <button class="btn btn-outline-light border-light btn-sm dropdown-toggle px-2"
                                type="button" data-bs-toggle="dropdown"><img class="me-2" src="img/flags/en.png"
                                    width="20" alt="English">Eng / $</button>
                            <ul class="dropdown-menu my-1">
                                <li class="dropdown-item">
                                    <select class="form-select form-select-sm">
                                        <option value="usd">$ USD</option>
                                        <option value="eur">€ EUR</option>
                                        <option value="ukp">£ UKP</option>
                                        <option value="jpy">¥ JPY</option>
                                    </select>
                                </li>
                                <li><a class="dropdown-item pb-1" href="#"><img class="me-2"
                                            src="img/flags/fr.png" width="20" alt="Français">Français</a>
                                </li>
                                <li><a class="dropdown-item pb-1" href="#"><img class="me-2"
                                            src="img/flags/de.png" width="20" alt="Deutsch">Deutsch</a>
                                </li>
                                <li><a class="dropdown-item" href="#"><img class="me-2" src="img/flags/it.png"
                                            width="20" alt="Italiano">Italiano</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-sm-4">
                        <div class="widget widget-links widget-light pb-2 mb-4">
                            <h3 class="widget-title text-light">Product catalog</h3>
                            <ul class="widget-list">
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Special
                                        offers</a>
                                </li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Bakery</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Fruits
                                        and Vegetables</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Dairy
                                        and Eggs</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Meat
                                        and Poultry</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Fish
                                        and Seafood</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Sauces
                                        and Spices</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Canned
                                        Food and Oil</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Alcoholic
                                        Beverages</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Soft
                                        Drinks and Juice</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Packets, Cereals
                                        and
                                        Poultry</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Frozen</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Personal
                                        hygiene</a>
                                </li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Kitchenware</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-sm-4">
                        <div class="widget widget-links widget-light pb-2 mb-4">
                            <h3 class="widget-title text-light">Company</h3>
                            <ul class="widget-list">
                                <li class="widget-list-item"><a class="widget-list-link" href="#">About
                                        us</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Store
                                        locator</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Careers at
                                        Cartzilla</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Contacts</a>
                                </li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Help
                                        center</a></li>
                                <li class="widget-list-item"><a class="widget-list-link" href="#">Actions and
                                        News</a></li>
                            </ul>
                        </div>
                        <div class="widget widget-light pb-2 mb-4">
                            <h3 class="widget-title text-light">Follow us</h3><a
                                class="btn-social bs-light bs-twitter me-2 mb-2" href="#"><i
                                    class="ci-twitter"></i></a><a class="btn-social bs-light bs-facebook me-2 mb-2"
                                href="#"><i class="ci-facebook"></i></a><a
                                class="btn-social bs-light bs-instagram me-2 mb-2" href="#"><i
                                    class="ci-instagram"></i></a><a class="btn-social bs-light bs-youtube me-2 mb-2"
                                href="#"><i class="ci-youtube"></i></a>
                        </div>
                    </div>
                    <div class="col-xl-4 col-sm-8">
                        <div class="widget pb-2 mb-4">
                            <h3 class="widget-title text-light pb-1">Stay informed</h3>
                            <form class="subscription-form validate"
                                action="../external.html?link=https://studio.us12.list-manage.com/subscribe/post?u=c7103e2c981361a6639545bd5&amp;amp;id=29ca296126"
                                method="post" name="mc-embedded-subscribe-form" target="_blank" novalidate>
                                <div class="input-group flex-nowrap"><i
                                        class="ci-mail position-absolute top-50 translate-middle-y text-muted fs-base ms-3"></i>
                                    <input class="form-control rounded-start" type="email" name="EMAIL"
                                        placeholder="Your email" required>
                                    <button class="btn btn-primary" type="submit"
                                        name="subscribe">Subscribe*</button>
                                </div>
                                <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                                <div style="position: absolute; left: -5000px;" aria-hidden="true">
                                    <input class="subscription-form-antispam" type="text"
                                        name="b_c7103e2c981361a6639545bd5_29ca296126" tabindex="-1">
                                </div>
                                <div class="form-text text-light opacity-50">*Subscribe to our newsletter to
                                    receive early discount offers, updates and new products info.</div>
                                <div class="subscription-status"></div>
                            </form>
                        </div>
                        <div class="widget pb-2 mb-4">
                            <h3 class="widget-title text-light pb-1">Download our app</h3>
                            <div class="d-flex flex-wrap">
                                <div class="me-2 mb-2"><a class="btn-market btn-apple" href="#"
                                        role="button"><span class="btn-market-subtitle">Download on
                                            the</span><span class="btn-market-title">App Store</span></a></div>
                                <div class="mb-2"><a class="btn-market btn-google" href="#"
                                        role="button"><span class="btn-market-subtitle">Download on
                                            the</span><span class="btn-market-title">Google Play</span></a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-darker px-lg-3 py-3">
            <div class="d-sm-flex justify-content-between align-items-center mx-auto px-3" style="max-width: 80rem;">
                <div class="fs-xs text-light opacity-50 text-center text-sm-start py-3">© All rights reserved.
                    Made by <a class="text-light" href="../external.html?link=https://createx.studio/"
                        target="_blank" rel="noopener">Createx Studio</a></div>
                <div class="py-3"><img class="d-block mx-auto mx-sm-start" src="img/cards-alt.png" width="187"
                        alt="Payment methods"></div>
            </div>
        </div>
    </footer>
    <!-- Back To Top Button--><a class="btn-scroll-top" href="#top" data-scroll data-fixed-element><span
            class="btn-scroll-top-tooltip text-muted fs-sm me-2">Top</span><i class="btn-scroll-top-icon ci-arrow-up">
        </i></a>

</div>
<!-- Vendor scrits: js libraries and plugins-->
<script src="vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="vendor/simplebar/dist/simplebar.min.js"></script>
<script src="vendor/tiny-slider/dist/min/tiny-slider.js"></script>
<script src="vendor/smooth-scroll/dist/smooth-scroll.polyfills.min.js"></script>
<script src="vendor/drift-zoom/dist/Drift.min.js"></script>
<script src="vendor/lightgallery.js/dist/js/lightgallery.min.js"></script>
<!-- Main theme script-->

<script src="js/theme.min.js"></script>

<script>
    $(document).pjax('[data-pjax] a, a[data-pjax]', {
        container: '#pjax-container'
    });
</script>

</body>

</html>


</html>
