<style>
    .timeline-item {
        border-left: .4rem solid #6a3a00;
        margin-left: 4rem;
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
    }

    .timeline-item .child {
        margin-left: -2.7rem;
        font-size: 2rem;
    }

    .timeline-item .child img {
        width: 5rem;
    }
</style>

@foreach ($items as $r)
    @php
        $description = $r->record_type;
        $img = 'approve.png';
        if ($r->record_type == 'transfer' || $r->record_type == 'received_drugs') {
            $img = 'transaction.png';
            $description = 'Transfered 200grams of this drugs to John Doe';
        } elseif ($r->record_type == 'offline_sales') {
            $img = 'sell.png';
            $description = 'Sold 500grams of this drugs to Bwambale muhidin';
        } elseif ($r->record_type == 'other') {
            $img = 'other.png';
            $description = $r->description;
        } elseif ($r->record_type == 'animal_event') {
            $img = 'animal.png';
            $description = 'Applied this drug to animal.';
        }
    @endphp
    <div class="timeline-item">
        <div class="child"> <img src="{{ url('assets/images/' . $img) }}">
            <b>{{ $r->get_created_date() }} </b>
            {{ $r->get_details() }}
        </div>
    </div>
@endforeach
