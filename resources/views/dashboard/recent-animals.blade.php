<?php
use App\Models\Utils;
?><style>
    .ext-icon {
        color: rgba(0, 0, 0, 0.5);
        margin-left: 10px;
    }

    .installed {
        color: #6b3b00;
        margin-right: 10px;
    }

    .card {
        border-radius: 5px;
    }

    .case-item:hover {
        background-color: rgb(254, 254, 254);
    }
</style>
<div class="card  mb-4 mb-md-5 border-1 border-primary" style="border-radius: 0px;">
    <!--begin::Header-->
    <div class="d-flex justify-content-between pt-2 pb-3 px-md-4 border-bottom border-secondary border-2">
        <h3>
            <b>{{ $title }}</b>
        </h3>
        <div>
            <a href="{{ admin_url('/events') }}" class="btn btn-sm btn-primary mt-md-4 mt-4">
                View All
            </a>
        </div>
    </div>
    <div class="card-body py-2 py-md-3">
        @foreach ($items as $i)
            <div class="d-flex align-items-center mb-4 case-item">
                <div style="border-left: solid #6b3b00 5px;" class="flex-grow-1 pl-2 pl-md-3 ">
                    <a href="{{ admin_url('/events?animal_id=' . $i->animal_id) }}" class="text-dark text-hover-primary">
                        <b>{{ Str::of($i->body)->limit(40) }}</b>
                    </a>
                    <span class="d-block text-dark">
                        <b class="text-primary">{{ Utils::my_date_time($i->created_at) }} : </b>
                        {{ $i->description }}
                    </span>
                </div>
                <a href="{{ admin_url('/events?animal_id=' . $i->animal_id) }}" class="badge "
                    style="background-color: #6b3b00;" title="View all animal"> <i class="fa fa-chevron-right"></i> </a>
            </div>
        @endforeach
    </div>
</div>
