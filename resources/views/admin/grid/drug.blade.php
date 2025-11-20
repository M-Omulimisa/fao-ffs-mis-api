@php
use App\Models\Utils;
@endphp
<div class="box">
    @if (isset($title))
        <div class="box-header with-border">
            <h3 class="box-title"> {{ $title }}</h3>
        </div>
    @endif

    <div class="box-header with-border">
        <div class="pull-right">
            {!! $grid->renderExportButton() !!}
            {!! $grid->renderCreateButton() !!}
        </div>
        <span>
            {!! $grid->renderHeaderTools() !!}
        </span>
    </div>

    {!! $grid->renderFilter() !!}

    <div class="box-body">
        <ul class="mailbox-attachments clearfix">

            @foreach ($grid->rows() as $row)
                <?php
                $img = url('drug.webp');
                $link = admin_url('/drug-stock-batches/' . $row->column('id'));
                $link_buy = admin_url('/orders/create?id=' . $row->column('id'));
                ?>
                <li>

                    <span class="mailbox-attachment-icon has-img">
                        <img src="{!! $img !!}" alt="Image">
                    </span>
                    <div class="mailbox-attachment-info">
                        <h2 class="product-price" style="font-size: 22px!important">
                            UGX {!! $row->column('price') !!}
                        </h2>
                        <p class="product-title" style="color: black;">
                            AVAILABLE QTY: {!! number_format((int) $row->column('current_quantity')) !!} Units
                        </p>
                        <p class="product-title" style="color: black;">
                            {!! $row->column('name') !!}
                        </p>
                        <a class="btn btn-primary btn-block mt-2" href="{{ $link }}">READ MORE ABOUT THIS
                            DRUG</a>
                        <a class="btn btn-primary btn-block mt-2" href="{{ $link_buy }}">BUY NOW</a>
                </li>
            @endforeach

        </ul>
    </div>













</div>
<div class="box-footer
                clearfix">
    {!! $grid->paginator() !!}
</div>
<!-- /.box-body -->
</div>
