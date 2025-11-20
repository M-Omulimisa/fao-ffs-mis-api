<footer class="footer">
    <img class="logo-bg logo-footer" src="./assets/img/symbol.svg" alt="logo">
    <div class="container">
        <div class="footer-top">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="heading">Hosting</div>
                    <ul class="footer-menu">
                        <li class="menu-item"><a href="hosting">Shared Hosting</a></li>
                        <li class="menu-item"><a href="dedicated">Dedicated Server</a></li>
                        <li class="menu-item"><a href="vps">Cloud Virtual (VPS)</a></li>
                        <li class="menu-item"><a href="domains">Domain Names</a></li>
                    </ul>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="heading">Support</div>
                    <ul class="footer-menu">
                        <li class="menu-item"><a href="login">myAntler</a></li>
                        <li class="menu-item"><a href="knowledgebase-list">Knowledge Base</a></li>
                        <li class="menu-item"><a href="contact">Contact Us</a></li>
                        <li class="menu-item"><a href="faq">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="heading">Company</div>
                    <ul class="footer-menu">
                        <li class="menu-item"><a href="about">About Us</a> </li>
                        <li class="menu-item"><a href="elements">Features</a></li>
                        <li class="menu-item"><a href="blog-details">Blog</a></li>
                        <li class="menu-item"><a href="legal">Legal</a></li>
                    </ul>
                </div>
                <div class="col-sm-6 col-md-3">
                    <a><img class="svg logo-footer d-block" src="./assets/img/logo.png" alt="logo"></a>
                    <a><img class="svg logo-footer d-none" src="./assets/img/logo-light.svg" alt="logo"></a>
                    <div class="copyright">©2022 Antler - All rights reserved</div>
                    <div class="soc-icons">
                        <a href=""><i class="fab fa-facebook-f withborder noshadow"></i></a>
                        <a href=""><i class="fab fa-youtube withborder noshadow"></i></a>
                        <a href=""><i class="fab fa-twitter withborder noshadow"></i></a>
                        <a href=""><i class="fab fa-instagram withborder noshadow"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="subcribe news">
        <div class="container">
            <div class="row">
                <form action="#" class="w-100">
                    <div class="col-md-6 offset-md-3">
                        <div class="general-input">
                            <input class="fill-input" type="email" name="email"
                                data-i18n="[placeholder]header.login">
                            <input type="submit" value="SUBSCRIBE"
                                class="btn btn-default-yellow-fill initial-transform">
                        </div>
                    </div>
                    <div class="col-md-6 offset-md-3 text-center pt-4">
                        <p>Subscribe to our newsletter to receive news and updates</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <ul class="footer-menu">
                        <li id="drop-lng" class="btn-group btn-group-toggle">
                            <label data-lng="en-US" for="option1" class="btn btn-secondary">
                                <input type="radio" name="options" id="option1" checked> EN
                            </label>
                            <label data-lng="pt-PT" for="option2" class="btn btn-secondary">
                                <input type="radio" name="options" id="option2"> PT
                            </label>
                        </li>
                        <li class="menu-item by">Designed With <span class="c-pink">♥</span> by
                            <a href="javascript:;">M. Muhindo</a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="payment-list">
                        <li>
                            <p>Payments We Accept</p>
                        </li>
                        <li><i class="fab fa-cc-paypal"></i></li>
                        <li><i class="fab fa-cc-visa"></i></li>
                        <li><i class="fab fa-cc-mastercard"></i></li>
                        <li><i class="fab fa-cc-apple-pay"></i></li>
                        <li><i class="fab fa-cc-discover"></i></li>
                        <li><i class="fab fa-cc-amazon-pay"></i></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>
<script src="assets/js/gdpr-cookie.min.js"></script>
<script>
    $.gdprcookie.init({});
    $(document.body)
        .on("gdpr:show", function() {
            console.log("Cookie dialog is shown");
        })
        .on("gdpr:accept", function() {
            var preferences = $.gdprcookie.preference();
            console.log("Preferences saved:", preferences);
        })
        .on("gdpr:advanced", function() {
            console.log("Advanced button was pressed");
        });
    if ($.gdprcookie.preference("marketing") === true) {
        console.log("This should run because marketing is accepted.");
    }
</script>
</div>
<!-- ***** BUTTON GO TOP ***** -->
<a href="#0" class="cd-top"> <i class="fas fa-angle-up"></i> </a>
<!-- Javascript -->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/typed.js"></script>
<script defer src="assets/js/popper.min.js"></script>
<script defer src="assets/js/bootstrap.min.js"></script>
<script defer src="assets/js/jquery.countdown.js"></script>
<script defer src="assets/js/jquery.magnific-popup.min.js"></script>
<script defer src="assets/js/slick.min.js"></script>
<script defer src="assets/js/flickity.pkgd.min.js"></script>
<script defer src="assets/js/flickity-fade.min.js"></script>
<script defer src="assets/js/aos.min.js"></script>
<script defer src="assets/js/isotope.min.js"></script>
<script defer src="assets/js/jquery.scrollme.min.js"></script>
<script defer src="assets/js/swiper.min.js"></script>
<script async src="assets/js/lazysizes.min.js"></script>
<script src="assets/js/wow.min.js"></script>
<script>
    new WOW().init();
</script>
<script defer src="assets/js/scripts.min.js"></script>
<script defer src="assets/js/settings-init.js"></script>
<script>
    var typed1 = new Typed('#typed1', {
        strings: [
            'track and trace your livestock',
            "manage your livestock events",
            "livestock movement permits",
            "likestock drugs registration",
            "sell your likestock",
            "buy likestock drugs"
        ],
        typeSpeed: 50,
        backSpeed: 20,
        smartBackspace: true,
        loop: true
    });
</script>
</body>

</html>
