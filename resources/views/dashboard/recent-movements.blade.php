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
            <a href="{{ admin_url('/movements') }}" class="btn btn-sm btn-primary mt-md-4 mt-4">
                View All
            </a>
        </div>
    </div>
    <div class="card-body py-2 py-md-3">
        @foreach ($items as $i)
            <div class="d-flex align-items-center mb-4 case-item">
                <div style="border-left: solid #6b3b00 5px;" class="flex-grow-1 pl-2 pl-md-3 ">
                    <a href="{{ admin_url('/movements/' . $i->id . '/edit') }}"
                        class="text-dark text-hover-primary">
                        <b>{{ Str::of($i->body)->limit(40) }}</b>
                    </a>
                    <span class="d-block text-dark">
                        <b class="text-primary">{{ Utils::my_date_time($i->created_at) }} : </b>
                        {{ $i->description }} Movement permit to {{ $i->destination }} - By {{ $i->owner->name }}
                    </span>
                </div>
                <a href="{{ admin_url('/movements/' . $i->id . '/edit') }}"
                    class="text-white py-1 px-1 rounded text-center"
                    style="background-color: #6b3b00; line-height: 1.6rem; font-weight: 600;" title="View all animal">
                    Review Permit </a>
            </div>
        @endforeach
    </div>
</div>
{{-- 
      "id" => 5
    "created_at" => "2023-01-26 08:02:40"
    "updated_at" => "2023-01-26 08:02:40"
    "administrator_id" => 777
    "vehicle" => "UBA 134"
    "reason" => "simple drug"
    "status" => "0"
    "trader_nin" => "121752168162561"
    "trader_name" => "Muhindo Mubaraka"
    "trader_phone" => "+256772721777"
    "transporter_name" => "Muhindo John"
    "transporter_nin" => "2399078900008"
    "transporter_Phone" => "0783204665"
    "district_from" => 14
    "sub_county_from" => 1001169
    "village_from" => "Kasindi"
    "district_to" => null
    "sub_county_to" => 1000009
    "village_to" => "kasese"
    "transportation_route" => "kamwenge - mbarara"
    "permit_Number" => null
    "valid_from_Date" => null
    "valid_to_Date" => null
    "status_comment" => null
    "destination" => "To slaughter"
    "destination_slaughter_house" => "1"
    "details" => "summer details"
    "destination_farm" => 182
    "is_paid" => null
    "paid_id" => "12066767ggGGF"
    "paid_method" => "Mobile money"
    "paid_amount" => 90000
    --}}
