@if (!$isPjax)
    @include('layouts.header')
@endif
<?php
$user = Auth::user();
?>

<section class="ps-lg-4 pe-lg-3 pt-4 pb-4 pb-md-5">
    <div class="row">
        <div class="col-md-8">
            <form class="bg-light p-3 p-md-4 rounded-4" method="POST"{{--  action="{{ route('m-register-post') }}" --}}>

                <h1 class="h2 mb-3 mb-md-0 me-2">Checkout</h1>
                <div
                    class="d-flex mt-2 mt-md-4 flex-wrap justify-content-between align-items-center rounded-3 border py-2 px-3 mb-4">
                    <div class="d-flex align-items-center me-3 py-2"><img class="rounded"
                            src="storage/images/{{ $product->thumbnail }}" width="120" alt="{{ $product->name }}">
                        <div class="ps-3">
                            <div class="fs-md fw-medium text-heading">{{ $product->name }}</div>
                            <div class="fs-ms text-muted"><span>SEX:</span> <span
                                    class="text-heading">{{ $product->sex }}</span></div>
                            <div class="fs-ms text-muted"><span>SPECIES:</span> <span
                                    class="text-heading">{{ $product->type }}</span></div>
                            <div class="fs-ms text-muted"><span>WEIGHT:</span> <span
                                    class="text-heading">{{ $product->weight }}</span></div>
                        </div>
                    </div>
                    <div class="py-2"><a class="btn btn-light btn-sm btn-shadow mt-3 mt-sm-0"
                            href="{{ route('market') }}"><i class="ci-edit me-2"></i>Change item</a></div>
                </div>

                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="row gx-4 gy-3">
                    <div class="col-sm-6">
                        @include('components.input-text', [
                            'name' => 'first_name',
                            'label' => 'First name',
                            'attributes' => [
                                'value' => $user->first_name,
                                'required' => 'required',
                            ],
                        ])
                    </div>
                    <div class="col-sm-6">
                        @include('components.input-text', [
                            'name' => 'last_name',
                            'label' => 'Last name',
                            'attributes' => [
                                'value' => $user->last_name,
                                'required' => 'required',
                            ],
                        ])
                    </div>

                    <div class="col-sm-6">
                        @include('components.input-text', [
                            'name' => 'phone_number',
                            'label' => 'Phone number',
                            'type' => 'tel',
                            'attributes' => [
                                'value' => $user->phone_number,
                                'required' => 'required',
                            ],
                        ])
                    </div>

                    <div class="col-sm-6">
                        @include('components.input-text', [
                            'name' => 'phone_number_2',
                            'label' => 'Phone number 2',
                            'type' => 'tel',
                            'attributes' => [
                                'value' => $user->phone_number_2,
                            ],
                        ])
                    </div>

                    <div class="col-sm-12">
                        @include('components.input-text', [
                            'name' => 'address',
                            'label' => 'Delivery address',
                            'attributes' => [
                                'value' => $user->address,
                                'required' => 'required',
                            ],
                        ])
                    </div>


                    <div class="col-sm-12">
                        @include('components.input-text', [
                            'name' => 'order_note',
                            'label' => 'Order note',
                            'type' => 'textarea',
                        ])
                    </div>



                    <div class="col-12 text-end">
                        <button class="btn btn-primary btn-block" type="submit"><i
                                class="ci-cart me-2 ms-n1"></i>SUBMIT
                            ORDER</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

@if (!$isPjax)
    @include('layouts.footer')
@endif
