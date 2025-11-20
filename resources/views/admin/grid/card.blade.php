<?php
use App\Models\Utils;
use App\Models\Animal;
use App\Models\Product;
use Carbon\Carbon;
?>
<div class="box">
    @if (isset($title))
        <div class="box-header with-border">
            <h3 class="box-title"> {{ $title }}</h3>
        </div>
    @endif

    <div class="box-header with-border">
        <div class="pull-right">


            <a href="{{ admin_url('my-products/create') }}" class="btn btn-sm btn-success" title="New">
                <i class="fa fa-plus"></i><span class="hidden-xs">&nbsp;&nbsp;SELL NOW</span>
            </a>


        </div>
        <span>
            {!! $grid->renderHeaderTools() !!}
        </span>
    </div>

    {!! $grid->renderFilter() !!}

    <!-- /.box-header -->
    <div class="box-body ">
        @foreach ($grid->rows() as $row)
            <?php
            
            $pro = Product::find($row->column('id'));
            
            if ($pro == null) {
                continue;
            }
            
            $img = $pro->get_thumnail();
            
            $link = admin_url('/products/' . $row->column('id'));
            $link_buy = admin_url('/orders/create?id=' . $row->column('id'));
            
            $animal = new Animal();
            
            if ($row->column('type') == 'Livestock') {
                $animal = Animal::where([
                    'id' => $row->column('animal_id'),
                ])->first();
            
                if ($animal == null) {
                    continue;
                    $animal = new Animal();
                }
            }
            ?>

            <div class="col-xs-6 col-sm-6 col-md-4 col-lg-3 product-c  ">
                <div class="product">

                    <img style="width: 100%; " src="{!! $img !!}" alt="Image">

                    @if ($row->column('type') == 'Livestock')
                    @endif

                    <div class="desc">
                        <p class="title">{{ $row->column('quantity') }}KGs {{ $animal->type }}</p>
                        <p class="price"><sup class="#6A3A00">UGX</sup>{{ number_format((int) $row->column('price')) }}
                        </p>
                        <a class="button-27" href="{{ $link_buy }}" role="button">BUY NOW</a>
                    </div>
                    <div class="detail">
                        <div>
                            <p class="title">POSTED</p>
                            <p class="desc">{!! Carbon::parse($row->column('created_at'))->diffForHumans() !!}</p>
                        </div>

                        <div class="desc-item">
                            <p class="title">SPECIES</p>
                            <p class="desc">{!! $animal->type !!}</p>
                        </div>

                        <div class="desc-item">
                            <p class="title">WEIGHT</p>
                            <p class="desc">{!! $row->column('quantity') !!} KGs</p>
                        </div>

                        <div class="desc-item">
                            <p class="title">DATE OF BIRTH</p>
                            <p class="desc">{!! $animal->dob !!}</p>
                        </div>

                        <div class="desc-item">
                            <p class="title">LHC</p>
                            <p class="desc">{!! $animal->lhc !!}</p>
                        </div>


                    </div>


                </div>

            </div>
        @endforeach
    </div>
    <div class="box-footer clearfix">
        {!! $grid->paginator() !!}
    </div>
</div>
