<div class="card   ">
    <!--begin::Body-->
    <div class="card-body d-flex flex-column flex-center">
        <!--begin::Heading-->
        <div class="mb-2 text-center">
            <!--begin::Title-->
            <h2 class="fw-bold text-gray-800 text-center lh-lg">
                Only <b class="text-primary">{{ $step }} steps</b> remaining for you to get started!
            </h2>
            <img width="80%" class="text-center my-3 my-md-5 " src="{{ url('public/assets/svg/2.svg') }}">
        </div>
        <div class="text-center mb-1">
            <!--begin::Link-->
            <a class="btn btn-block btn-primary btn-lg" href="{{ $link }}">
                {{ $text }}
            </a>
        </div>
        <!--end::Links-->
    </div>
    <!--end::Body-->
</div>
