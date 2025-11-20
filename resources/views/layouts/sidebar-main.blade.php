<?php
$showAccountMenu = false;
if (str_contains($_SERVER['REQUEST_URI'], 'account-')) {
    $showAccountMenu = true;
}

?><aside class="offcanvas offcanvas-expand w-100 border-end zindex-lg-5 pt-lg-5" id="sideNav"
    style="max-width: 21.875rem;">
    <div class="pt-2 d-none d-lg-block"></div>
    <ul class="nav nav-tabs nav-justified mt-4 mt-lg-5 mb-0" role="tablist" style="min-height: 3rem;">
        <li class="nav-item"><a class="nav-link fw-medium active" href="#categories" data-bs-toggle="tab" role="tab">
                @if ($showAccountMenu)
                    <h3>Account</h3>
                @else
                    <h3>Categories</h3>
                @endif
            </a></li>
        <li class="nav-item d-lg-none"><a class="nav-link fs-sm" href="#" data-bs-dismiss="offcanvas"
                role="tab"><i class="ci-close fs-xs me-2"></i>Close</a></li>
    </ul>
    <div class="offcanvas-body px-0 pt-3 pb-0" data-simplebar>
        @if ($showAccountMenu)
            <a href="#" class="  px-2 px-md-3 py-1 py-md-2 d-block text-dark">
                <i class="ci-bread fs-lg me-2 text-dark"></i>
                <span>My Orders</span>
            </a>
            <a href="#" class="  px-2 px-md-3 py-1 py-md-2 d-block text-dark">
                <i class="ci-bread fs-lg me-2 text-dark"></i>
                <span>My Profile</span>
            </a>
            <a href="#" class="  px-2 px-md-3 py-1 py-md-2 d-block text-dark">
                <i class="ci-bread fs-lg me-2 text-dark"></i>
                <span>Change password</span>
            </a>
            <a href="{{ route('account-logout') }}" class="  px-2 px-md-3 py-1 py-md-2 d-block text-danger">
                <i class="ci-sign-out fs-lg me-2 text-dark"></i>
                <span>Log out</span>
            </a>
        @else
            <h3>Categories time</h3>
        @endif
    </div>
    <div class="offcanvas-footer d-block px-grid-gutter pt-4 pb-2 mb-0">
        <div class="d-flex mb-3"><i class="ci-support h4 mb-0 fw-normal text-primary mt-1 me-1"></i>
            <div class="ps-2">
                <div class="text-muted fs-sm">Sell on U-LITS</div>
            </div>
        </div>
        <div class="d-flex mb-2"><i class="ci-mail h5 mb-0 fw-normal text-primary mt-1 me-1"></i>
            <div class="ps-2">
                <div class="text-muted fs-sm">Contact us</div>
            </div>
        </div>
        <h6 class="pt-2 pb-1">Follow us</h6><a class="btn-social bs-outline bs-twitter me-2 mb-2" href="#"><i
                class="ci-twitter"></i></a><a class="btn-social bs-outline bs-facebook me-2 mb-2" href="#"><i
                class="ci-facebook"></i></a><a class="btn-social bs-outline bs-instagram me-2 mb-2" href="#"><i
                class="ci-instagram"></i></a><a class="btn-social bs-outline bs-youtube me-2 mb-2" href="#"><i
                class="ci-youtube"></i></a>
    </div>
</aside>
