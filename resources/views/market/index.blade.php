@if (!$isPjax)
    @include('layouts.header')
@endif


<section class="ps-lg-4 pe-lg-3 pt-4">
    <div class="px-3 pt-2">

        <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
            <h1 class="h2 mb-3 mb-md-0 me-2">Livestock market</h1>
            <div class="d-flex align-items-center">
                <div class="d-none d-sm-block py-2 fs-sm text-muted text-nowrap me-2">Sort by:</div>
                <ul class="nav nav-tabs fs-sm mb-0">
                    <li class="nav-item"><a class="nav-link active" href="#">Popular</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Cheap</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Expensive</a></li>
                </ul>
            </div>
        </section>
        <!-- Product grid-->
        <div class="row g-0 mx-n2">

            @foreach ($products as $pro)
                <!-- Product-->
                <div class="col-6 col-xl-3 col-lg-6 col-md-4 col-sm-6 px-2 mb-3">
                    <div class="card product-card card-static pb-3">
                        <button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip"
                            data-bs-placement="left" title="Add to wishlist"><i class="ci-heart"></i></button><a
                            class="card-img-top d-block overflow-hidden" data-pjax href="/{{ $pro->slug }}"><img
                                src="storage/images/{{ $pro->thumbnail }}" alt="Product"></a>
                        <div class="card-body py-2"><a class="product-meta d-block fs-xs pb-1" href="#">Fruits and
                                Vegetables</a>
                            <h3 class="product-title fs-sm text-truncate"><a href="grocery-single.html">Coconut,
                                    Indonesia
                                    (piece)
                                </a></h3>
                            <div class="product-price"><span class="text-accent">$2.<small>99</small></span></div>
                        </div>
                        <div class="product-floating-btn">
                            <button class="btn btn-primary btn-shadow btn-sm" type="button">+<i
                                    class="ci-cart fs-base ms-1"></i></button>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
        <div class="py-4 pb-md-5 mb-4">
            <!-- Pagination-->
            <nav class="d-flex justify-content-between pt-2" aria-label="Page navigation">
                <ul class="pagination">
                    <li class="page-item"><a class="page-link" href="#"><i class="ci-arrow-left me-2"></i>Prev</a>
                    </li>
                </ul>
                <ul class="pagination">
                    <li class="page-item d-sm-none"><span class="page-link page-link-static">1 / 5</span></li>
                    <li class="page-item active d-none d-sm-block" aria-current="page"><span class="page-link">1<span
                                class="visually-hidden">(current)</span></span></li>
                    <li class="page-item d-none d-sm-block"><a class="page-link" href="#">2</a></li>
                    <li class="page-item d-none d-sm-block"><a class="page-link" href="#">3</a></li>
                    <li class="page-item d-none d-sm-block"><a class="page-link" href="#">4</a></li>
                    <li class="page-item d-none d-sm-block"><a class="page-link" href="#">5</a></li>
                </ul>
                <ul class="pagination">
                    <li class="page-item"><a class="page-link" href="#" aria-label="Next">Next<i
                                class="ci-arrow-right ms-2"></i></a></li>
                </ul>
            </nav>
        </div>
    </div>
</section>

@if (!$isPjax)
    @include('layouts.footer')
@endif
