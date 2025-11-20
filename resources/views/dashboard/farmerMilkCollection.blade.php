<?php
use App\Models\Utils;
?><style>
    .ext-icon {
        color: rgba(0, 0, 0, 0.5);
        margin-left: 10px;
    }

    .installed {
        color: #00a65a;
        margin-right: 10px;
    }

    .card {
        border-radius: 5px;
    }

    .case-item:hover {
        background-color: rgb(254, 254, 254);
    }
</style>
<div class="card  mb-4 mb-md-5 border-0">
    <!--begin::Header-->
    <div class="d-flex justify-content-between px-3 px-md-4 ">
        <h3>
            <b>Milk collection - {{ count($labels) }} days ago</b>
        </h3>
        <div>
            <a href="{{ url('/events') }}" class="btn btn-sm btn-primary mt-md-4 mt-4">
                View All
            </a>
        </div>
    </div>

    <div class="card-body py-2 py-md-3">


        <canvas id="farmerMilkCollection" style="width: 100%;"></canvas>

        <div class="border-top bord-dark mt-3 mt-md-3">
            <h3>
                <b>Daily milk collection summary</b>
            </h3>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered rounded">
                        <thead class="bg-primary rounded">
                            <tr>
                                <th>Day</th>
                                <th class="text-center">Animals Milked</th>
                                <th>Quantity (in Ltrs)</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $my_counter = 1;
                            @endphp
                            @foreach ($records as $red)
                                @php
                                    $my_counter++;
                                    if ($my_counter > 16) {
                                        break;
                                    }
                                @endphp
                                <tr>
                                    <th>{{ $red['day'] }}</th>
                                    <td class="text-center"
                                        style="
                                    font-size: 2.5rem;
                                    ">
                                        {{ $red['animals'] }}</td>
                                    <td class="text-center"
                                        style="
                                    font-size: 2rem;
                                    ">
                                        <b>{{ $red['milk'] }} </b>
                                    </td>


                                    @if ($red['progress'] < 0)
                                        <td class="text-center text-white pt-3"
                                            style="font-size: 3rem;
                                           background-color: rgb(174, 7, 7);
                                         font-weight: 800!important;
                                        ">
                                            <span class="fa fa-angle-double-down"></span>
                                        @elseif ($red['progress'] > 0)
                                        <td class="text-center text-white"
                                            style="font-size: 3rem;
                                           background-color: rgb(6, 98, 6);
                                         font-weight: 800!important;">
                                            <span class="fa fa-angle-double-up"></span>
                                        @else
                                        <td class="text-center text-white"
                                            style="font-size: 3rem;
                                           background-color: rgb(48, 48, 48);
                                         font-weight: 800!important;">
                                            <span class="fa fa-minus"></span>
                                    @endif

                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <div class="col-md-6">
                    <table class="table table-bordered rounded">
                        <thead class="bg-primary rounded">
                            <tr>
                                <th>Day</th>
                                <th class="text-center">Animals Milked</th>
                                <th>Quantity (in Ltrs)</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $my_counter = 0;
                            @endphp
                            @foreach ($records as $red)
                                @php
                                    $my_counter++;
                                    if ($my_counter < 16) {
                                        continue;
                                    }
                                @endphp
                                <tr>
                                    <th>{{ $red['day'] }}</th>
                                    <td class="text-center"
                                        style="
                                    font-size: 2.5rem;
                                    ">
                                        {{ $red['animals'] }}</td>
                                    <td class="text-center"
                                        style="
                                    font-size: 2rem;
                                    ">
                                        <b>{{ $red['milk'] }} </b>
                                    </td>


                                    @if ($red['progress'] < 0)
                                        <td class="text-center text-white pt-3"
                                            style="font-size: 3rem;
                                           background-color: rgb(174, 7, 7);
                                         font-weight: 800!important;
                                        ">
                                            <span class="fa fa-angle-double-down"></span>
                                        @elseif ($red['progress'] > 0)
                                        <td class="text-center text-white"
                                            style="font-size: 3rem;
                                           background-color: rgb(6, 98, 6);
                                         font-weight: 800!important;">
                                            <span class="fa fa-angle-double-up"></span>
                                        @else
                                        <td class="text-center text-white"
                                            style="font-size: 3rem;
                                           background-color: rgb(48, 48, 48);
                                         font-weight: 800!important;">
                                            <span class="fa fa-minus"></span>
                                    @endif

                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>


<div class="card  mb-4 mb-md-5 border-0">
    <div class="card-body">
        <h3>
            <b>Income Vs Expense - {{ count($labels) }} days ago</b>
        </h3>

        <canvas id="farmerFinance" style="width: 100%;"></canvas>
    </div>

</div>



<script>
    $(function() {


        window.chartColors = {
            red: 'rgb(255, 99, 132)',
            orange: 'rgb(255, 159, 64)',
            yellow: 'rgb(255, 205, 86)',
            green: '#277C61',
            blue: 'rgb(54, 162, 235)',
            purple: 'rgb(153, 102, 255)',
            grey: 'rgb(201, 203, 207)'
        };

        var chartData = {
            labels: JSON.parse('<?php echo json_encode($labels); ?>'),
            datasets: [{
                    type: 'bar',
                    label: 'Inome',
                    backgroundColor: window.chartColors.green,
                    data: {{ json_encode($income) }}
                },
                {
                    borderColor: window.chartColors.red,
                    backgroundColor: window.chartColors.red,
                    type: 'bar',
                    label: 'Expence',
                    data: {{ json_encode($expence) }}
                },

            ]

        };

        var ctx = document.getElementById('farmerFinance').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Chart.js Combo Bar Line Chart'
                },
                tooltips: {
                    mode: 'index',
                    intersect: true
                }
            }
        });
    });
</script>







<script>
    $(function() {


        window.chartColors = {
            red: 'rgb(255, 99, 132)',
            orange: 'rgb(255, 159, 64)',
            yellow: 'rgb(255, 205, 86)',
            green: '#277C61',
            blue: 'rgb(54, 162, 235)',
            purple: 'rgb(153, 102, 255)',
            grey: 'rgb(201, 203, 207)'
        };

        var chartData = {
            labels: JSON.parse('<?php echo json_encode($labels); ?>'),
            datasets: [

                {
                    type: 'line',
                    label: 'Animals milked',
                    backgroundColor: window.chartColors.red,
                    borderColor: window.chartColors.red,
                    data: {{ json_encode($count) }}
                }, {
                    type: 'bar',
                    label: 'Milk quantity',
                    backgroundColor: window.chartColors.green,
                    data: {{ json_encode($data) }}
                },

            ]

        };

        var ctx = document.getElementById('farmerMilkCollection').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Chart.js Combo Bar Line Chart'
                },
                tooltips: {
                    mode: 'index',
                    intersect: true
                }
            }
        });
    });
</script>
