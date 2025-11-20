@if (!$isPjax)
    @include('layouts.header')
@endif

<section class="ps-lg-4 pe-lg-3 pt-0 pt-md-4">
    <div class="px-3 pt-2">
        <!-- Page title + breadcrumb-->
        <nav class="mb-4 d-none d-md-block" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a class="text-nowrap" href="{{ config('app.market_url') }}"><i
                            class="ci-home"></i>Home</a></li>
                <li class="breadcrumb-item text-nowrap"><a
                        href="{{ config('app.market_url') }}/{{ strtolower($product->type) }}">
                        {{ $product->type }}</a>
                </li>
                <li class="breadcrumb-item text-nowrap active" aria-current="page">
                    {{ $product->name }}
                </li>
            </ol>
        </nav>

        <!-- Content thumbnail  956000011635915-(m).JPG -->
        <!-- Product Gallery + description-->
        <section class="row g-0 mx-n2 pb-5 mb-xl-3 ">
            <div class="col-xl-7 px-0 px-md-2 mb-0">
                <div class="h-100 bg-light rounded-3 p-0 p-md-4">
                    <div class="product-gallery">
                        <div class="product-gallery-preview order-sm-2">

                            <?php
                            $is_first_thumb = true;
                            ?>
                            @foreach ($product->animal->photos as $img)
                                <?php
                                $thumb_is_active = '';
                                if ($is_first_thumb) {
                                    $thumb_is_active = ' active ';
                                }
                                $is_first_thumb = false;
                                ?>

                                <div class="product-gallery-preview-item {{ $thumb_is_active }}"
                                    id="product-{{ $img->id }}"><img class="image-zoom"
                                        src="storage/images/{{ $img->src }}"
                                        data-zoom="storage/images/{{ $img->src }}" alt="{{ $product->name }}">
                                    <div class="image-zoom-pane"></div>
                                </div>
                            @endforeach

                        </div>
                        <div class="product-gallery-thumblist order-sm-1">

                            @php
                                $is_first_thumb = true;
                            @endphp
                            @foreach ($product->animal->photos as $img)
                                @php
                                    $thumb_is_active = '';
                                    if ($is_first_thumb) {
                                        $thumb_is_active = ' active ';
                                    }
                                    $is_first_thumb = false;
                                @endphp
                                <a class="product-gallery-thumblist-item {{ $thumb_is_active }}"
                                    href="#product-{{ $img->id }}"><img src="storage/images/{{ $img->src }}"
                                        alt="{{ $product->name }}"></a>
                            @endforeach

                            <a class="product-gallery-thumblist-item video-item"
                                href="https://www.youtube.com/watch?v=KjmuBo8xoCU">
                                <div class="product-gallery-thumblist-item-text">
                                    <i class="ci-video"></i>Video
                                </div>
                            </a>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5 px-0 px-md-2 mb-0">
                <div class="h-100 bg-light rounded-3 py-2 py-md-5 px-2 px-md-4 "><a
                        class="product-meta d-block fs-sm pb-2"
                        href="{{ config('app.market_url') }}/{{ strtolower($product->type) }}">{{ $product->type }},
                        {{ $product->breed }}</a>
                    <h1 class="h2">{{ $product->name }}</h1>
                    <div class="h2 fw-normal text-primary bg-secondary ps-3 pe-2 pt-1 pb-1"
                        style="font-weight: 800!important">

                        UGX {{ number_format($product->price) }}</div>

                    <h6 class="mt-3 mt-md-4 h4">Description</h6>
                    <ul class="list-unstyled fs-sm pt-0 mb-0">
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">Species:</strong>
                            <span>{{ $product->type }}</span>
                        </li>
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">Sex:</strong>
                            <span>{{ $product->sex }}</span>
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">Stage:</strong>
                            <span>{{ $product->stage }}</span>
                        </li>
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">breed:</strong>
                            <span>{{ $product->breed }}</span>
                        </li>
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">weight:</strong>
                            <span>{{ $product->animal->weight_text }}</span>
                        </li>
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">Birth date:</strong>
                            <span>{{ $product->animal->dob }}</span>
                        </li>
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">Last fmd vaccine date:</strong>
                            <span>{{ $product->animal->fmd }}</span>
                        </li>
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">Average milk production:</strong>
                            <span>{{ $product->animal->average_milk }} litters per milking</span>
                        </li>
                        <li><i class="ci-check-circle text-success me-2"></i>
                            <strong class="text-uppercase">Best for:</strong>
                            <span>{{ $product->best_for }}</span>
                        </li>
                    </ul>


                    <div class="d-flex flex-wrap align-items-center pt-4 pb-2 mb-3">
                        <a href="{{ route('buy-now', $product->id) }}"
                            class="btn btn-primary btn-shadow d-block w-100 mb-3 rounded-0" type="submit"><i
                                class="ci-cart fs-lg me-2"></i>BUY NOW</a>
                    </div>

                </div>
            </div>

            <div class="col-12">
                <div class="bg-light rounded-3 p-2 mt-3 ms-2">
                    <h6 class="mt-2 mt-md-2 h4">More details</h6>
                    <p>{{ $product->details }}</p>
                </div>
            </div>

        </section>
        <!-- Related products-->
        <section class="pb-5 mb-2 mb-xl-4">
            <h2 class="h3 pb-2 mb-grid-gutter text-center">You may also like</h2>
            <div class="tns-carousel tns-controls-static tns-controls-outside tns-nav-enabled">
                <div class="tns-carousel-inner"
                    data-carousel-options="{&quot;items&quot;: 2, &quot;gutter&quot;: 16, &quot;controls&quot;: true, &quot;responsive&quot;: {&quot;0&quot;:{&quot;items&quot;:1}, &quot;480&quot;:{&quot;items&quot;:2}, &quot;720&quot;:{&quot;items&quot;:3}, &quot;991&quot;:{&quot;items&quot;:2}, &quot;1140&quot;:{&quot;items&quot;:3}, &quot;1300&quot;:{&quot;items&quot;:4}, &quot;1500&quot;:{&quot;items&quot;:5}}}">
                    <!-- Product-->
                    <div>
                        <div class="card product-card card-static pb-3">
                            <button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip"
                                data-bs-placement="left" title="Add to wishlist"><i class="ci-heart"></i></button><a
                                class="card-img-top d-block overflow-hidden" href="grocery-single.html"><img
                                    src="img/grocery/catalog/08.jpg" alt="Product"></a>
                            <div class="card-body py-2"><a class="product-meta d-block fs-xs pb-1" href="#">Dairy
                                    and Eggs</a>
                                <h3 class="product-title fs-sm"><a href="grocery-single.html">Mozzarella Cheese
                                        (125g)</a></h3>
                                <div class="product-price"><span class="text-accent">$4.<small>30</small></span></div>
                            </div>
                            <div class="product-floating-btn">
                                <button class="btn btn-primary btn-shadow btn-sm" type="button">+<i
                                        class="ci-cart fs-base ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- Product-->
                    <div>
                        <div class="card product-card card-static pb-3">
                            <button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip"
                                data-bs-placement="left" title="Add to wishlist"><i class="ci-heart"></i></button><a
                                class="card-img-top d-block overflow-hidden" href="grocery-single.html"><img
                                    src="img/grocery/catalog/09.jpg" alt="Product"></a>
                            <div class="card-body py-2"><a class="product-meta d-block fs-xs pb-1"
                                    href="#">Personal hygiene</a>
                                <h3 class="product-title fs-sm text-truncate"><a href="grocery-single.html">Men’s
                                        Shampoo (400ml)</a></h3>
                                <div class="product-price"><span class="text-accent">$5.<small>99</small></span></div>
                            </div>
                            <div class="product-floating-btn">
                                <button class="btn btn-primary btn-shadow btn-sm" type="button">+<i
                                        class="ci-cart fs-base ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- Product-->
                    <div>
                        <div class="card product-card card-static pb-3">
                            <button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip"
                                data-bs-placement="left" title="Add to wishlist"><i class="ci-heart"></i></button><a
                                class="card-img-top d-block overflow-hidden" href="grocery-single.html"><img
                                    src="img/grocery/catalog/10.jpg" alt="Product"></a>
                            <div class="card-body py-2"><a class="product-meta d-block fs-xs pb-1"
                                    href="#">Meat and Poultry</a>
                                <h3 class="product-title fs-sm text-truncate"><a href="grocery-single.html">Frozen
                                        Oven-ready Poultry</a></h3>
                                <div class="product-price"><span class="text-accent">$12.<small>00</small></span>
                                </div>
                            </div>
                            <div class="product-floating-btn">
                                <button class="btn btn-primary btn-shadow btn-sm" type="button">+<i
                                        class="ci-cart fs-base ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- Product-->
                    <div>
                        <div class="card product-card card-static pb-3">
                            <button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip"
                                data-bs-placement="left" title="Add to wishlist"><i class="ci-heart"></i></button><a
                                class="card-img-top d-block overflow-hidden" href="grocery-single.html"><img
                                    src="img/grocery/catalog/11.jpg" alt="Product"></a>
                            <div class="card-body py-2"><a class="product-meta d-block fs-xs pb-1"
                                    href="#">Snacks, Sweets and Chips</a>
                                <h3 class="product-title fs-sm text-truncate"><a href="grocery-single.html">Dark
                                        Chocolate with Nuts</a></h3>
                                <div class="product-price"><span class="text-accent">$2.<small>50</small></span></div>
                            </div>
                            <div class="product-floating-btn">
                                <button class="btn btn-primary btn-shadow btn-sm" type="button">+<i
                                        class="ci-cart fs-base ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- Product-->
                    <div>
                        <div class="card product-card card-static pb-3">
                            <button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip"
                                data-bs-placement="left" title="Add to wishlist"><i class="ci-heart"></i></button><a
                                class="card-img-top d-block overflow-hidden" href="grocery-single.html"><img
                                    src="img/grocery/catalog/12.jpg" alt="Product"></a>
                            <div class="card-body py-2"><a class="product-meta d-block fs-xs pb-1"
                                    href="#">Canned Food and Oil</a>
                                <h3 class="product-title fs-sm text-truncate"><a href="grocery-single.html">Corn Oil
                                        Bottle (500ml)</a></h3>
                                <div class="product-price"><span class="text-accent">$3.<small>10</small></span></div>
                            </div>
                            <div class="product-floating-btn">
                                <button class="btn btn-primary btn-shadow btn-sm" type="button">+<i
                                        class="ci-cart fs-base ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- Product-->
                    <div>
                        <div class="card product-card card-static pb-3">
                            <button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip"
                                data-bs-placement="left" title="Add to wishlist"><i class="ci-heart"></i></button><a
                                class="card-img-top d-block overflow-hidden" href="grocery-single.html"><img
                                    src="img/grocery/catalog/13.jpg" alt="Product"></a>
                            <div class="card-body py-2"><a class="product-meta d-block fs-xs pb-1"
                                    href="#">Fish and Seafood</a>
                                <h3 class="product-title fs-sm text-truncate"><a href="grocery-single.html">Steak
                                        Salmon Fillet (1kg)</a></h3>
                                <div class="product-price"><span class="text-accent">$17.<small>99</small></span>
                                </div>
                            </div>
                            <div class="product-floating-btn">
                                <button class="btn btn-primary btn-shadow btn-sm" type="button">+<i
                                        class="ci-cart fs-base ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- Product-->
                    <div>
                        <div class="card product-card card-static pb-3">
                            <button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip"
                                data-bs-placement="left" title="Add to wishlist"><i class="ci-heart"></i></button><a
                                class="card-img-top d-block overflow-hidden" href="grocery-single.html"><img
                                    src="img/grocery/catalog/14.jpg" alt="Product"></a>
                            <div class="card-body py-2"><a class="product-meta d-block fs-xs pb-1"
                                    href="#">Canned Food and Oil</a>
                                <h3 class="product-title fs-sm text-truncate"><a href="grocery-single.html">Sardine in
                                        Tomato Sauce (105g)</a></h3>
                                <div class="product-price"><span class="text-accent">$3.<small>25</small></span></div>
                            </div>
                            <div class="product-floating-btn">
                                <button class="btn btn-primary btn-shadow btn-sm" type="button">+<i
                                        class="ci-cart fs-base ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>

@if (!$isPjax)
    @include('layouts.footer')
@endif
