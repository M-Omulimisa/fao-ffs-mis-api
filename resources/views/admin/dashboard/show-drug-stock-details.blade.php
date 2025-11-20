<style>
    .details-page p {
        font-size: 2rem;
    }
</style>
<div class="details-page">
    <p><b>Drug name:</b> {{ $item->name }}</p>
    <p><b>Drug Original quantity:</b> {{ $item->original_quantity }}</p>
    <p><b>Drug S quantity:</b> {{ $item->current_quantity }}</p>
    <p><b>Drug Batch number:</b> {{ $item->batch_number }}</p>
    <p><b>Unit selling_price:</b> {{ $item->selling_price }} Units</p>
    <p><b>Drug Manufacturer:</b> {{ $item->manufacturer }}</p>
    <p><b>Drug Ingredients:</b> {{ $item->ingredients }}</p>
    <p><b>Manufacturer:</b> {{ $item->manufacturer }}</p>
    <p><b>Expiry date:</b> {{ $item->expiry_date }}</p>
    <p><b>Creationk date:</b> {{ $item->expiry_date }}</p>
    <p><b>Spirce froma:</b> {{ $item->source_id }}</p>
    <p><b>Belongs to:</b> {{ $item->administrator_id }}</p>
    <p><b>Details:</b> {{ $item->sub_county_id }}</p>
</div> 
