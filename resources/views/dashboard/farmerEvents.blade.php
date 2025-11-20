<?php
use App\Models\Utils;
?><style>
    /*
    .timeline-item {
        border-left: .4rem solid #6a3a00;
        margin-left: 2rem;
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
        display: inline;
    }

    .timeline-item {
        border-left: .4rem solid #6a3a00;
        margin-left: 2rem;
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
        display: inline;
    }

    .timeline-item .child {
        margin-left: -10px;
        font-size: 2rem;
        padding-left: 2rem;
    }
 */

    .timeline-container {
        border-left: .3rem solid #aab5c2;
        margin-left: 1rem;

    }

    .dot {
        height: 15px !important;
        width: 15px !important;
        border-radius: 7.5px;
        margin-left: 6px;
    }
</style>
<div class="card  mb-4 mb-md-5 border-0 rounded">
    <div class="card-body rounded">
        <div class="d-flex justify-content-between">
            <h3 class="fw-700 p-0 m-0 mb-3">Recent Events</h3>
            <a href="{{ admin_url('events') }}" class="b-700 fs-16 mt-1">View All Events</a>
        </div>


        <div class="timeline-container">
            @foreach ($events as $r)
                <div class="row mb-3 mb-md-4">
                    <div class="dot bg-{{ $r->status }}"></div>
                    <div class="col pl-2 pt-0" style="margin-top: -2px;">
                        <b>{{ Utils::my_date_time($r->created_at) }}</b> -
                        {{ $r->description }}
                    </div>
                </div>
                {{-- <div class="dot"></div> 
                <div class="child">
                    <b>{{ $r->created_at }} </b>
                    {{ $r->description }}
                </div> --}}
            @endforeach
        </div>


    </div>
    <!--begin::Body-->
</div>
