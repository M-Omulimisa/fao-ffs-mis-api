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
</style>
<div class="card  mb-4 mb-md-5 border-0 rounded">
    <div class="card-body p-0 rounded">
        <div class="bg-primary p-3 "
            style="
        border-top-left-radius: .8rem;
        border-top-right-radius: .8rem;
        ">
            <h2 class="py-0 my-0 mb-2 mt-2 ml-2 fw-700">My livestock summary</h2>
            <div class="d-flex ">
                <div class="p-2 flex-fill ">
                    <div class="bg-white card ">
                        <div class="card-body">
                            <h3 class="text-dark fw-800 my-0">Cattle</h3>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 mt-5 "
                                style="line-height: 1">
                                <span>Bulls</span>
                                <span>{{ number_format($countCattleMale) }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1">
                                <span>Cows</span>
                                <span>{{ number_format($countCattleFemale) }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1;
                            border-top: dashed 1px black;">
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1;
                            border-top: dashed 1px black;">
                                <span>Total</span>
                                <span>{{ number_format($countCattle) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-2 flex-fill ">
                    <div class="bg-white card ">
                        <div class="card-body">
                            <h3 class="text-dark fw-800 my-0">Goats</h3>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 mt-5"
                                style="line-height: 1">
                                <span>Male</span>
                                <span>{{ number_format($countGoatMale) }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1">
                                <span>Female</span>
                                <span>{{ number_format($countGoatFemale) }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1;
                            border-top: dashed 1px black;">
                                <span>Total</span>
                                <span>{{ number_format($countGoat) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>


            <div class="d-flex ">
                <div class="p-2 flex-fill ">
                    <div class="bg-white card ">
                        <div class="card-body">
                            <h3 class="text-dark fw-800 my-0">Sheep</h3>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 mt-5 "
                                style="line-height: 1">
                                <span>Male</span>
                                <span>{{ $countSheepMale }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1">
                                <span>Female</span>
                                <span>{{ number_format($countSheepFemale) }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1;
                            border-top: dashed 1px black;">
                                <span>Total</span>
                                <span>{{ number_format($countSheep) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-2 flex-fill ">
                    <div class="bg-white card ">
                        <div class="card-body">
                            <h3 class="text-dark fw-800 my-0">Livestock</h3>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 mt-3 "
                                style="line-height: 1">
                                <span>Cattle</span>
                                <span>{{ number_format($countCattle) }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1">
                                <span>Sheep</span>
                                <span>{{ number_format($countGoat) }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1">
                                <span>Goats</span>
                                <span>{{ number_format($countSheep) }}</span>
                            </p>
                            <p class="d-flex justify-content-between fc-gray fs-18 fw-600 my-0 p-0 "
                                style="line-height: 1;
                            border-top: dashed 1px black;">
                                <span>Total</span>
                                <span>{{ number_format($count) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="p-2 p-3 mt-2">

            <h2 class="py-0 my-0 mb-2 mt-0 ml-2 mb-2  fw-700">Livestock aquisition</h2>
            <canvas id="line-stacked" style="width: 100%;"></canvas>
            <script>
                $(function() {

                    function randomScalingFactor() {
                        return Math.floor(Math.random() * 100)
                    }

                    window.chartColors = {
                        red: 'rgb(255, 99, 132)',
                        orange: 'rgb(255, 159, 64)',
                        yellow: 'rgb(255, 205, 86)',
                        green: 'rgb(75, 192, 192)',
                        blue: 'rgb(54, 162, 235)',
                        purple: 'rgb(153, 102, 255)',
                        grey: 'rgb(201, 203, 207)'
                    };

                    var config = {
                        type: 'line',
                        data: {
                            labels: JSON.parse('<?php echo json_encode($labels); ?>'),
                            datasets: [{
                                    label: 'Cattle',
                                    borderColor: window.chartColors.red,
                                    backgroundColor: window.chartColors.red,
                                    data: JSON.parse('<?php echo json_encode($cattle); ?>'),
                                }, {
                                    label: 'Goats',
                                    borderColor: window.chartColors.blue,
                                    backgroundColor: window.chartColors.blue,
                                    data: JSON.parse('<?php echo json_encode($goat); ?>'),
                                }, {
                                    label: 'Sheep',
                                    borderColor: window.chartColors.green,
                                    backgroundColor: window.chartColors.green,
                                    data: JSON.parse('<?php echo json_encode($sheep); ?>'),
                                },
                                {
                                    label: 'All animals',
                                    borderColor: window.chartColors.yellow,
                                    backgroundColor: window.chartColors.yellow,
                                    data: JSON.parse('<?php echo json_encode($allAnimals); ?>'),
                                },
                            ]
                        },
                        options: {
                            responsive: true,
                            title: {
                                display: true,
                                text: 'Chart.js Line Chart - Stacked Area'
                            },
                            tooltips: {
                                mode: 'index',
                            },
                            hover: {
                                mode: 'index'
                            },
                            scales: {
                                xAxes: [{
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Month'
                                    }
                                }],
                                yAxes: [{
                                    stacked: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Value'
                                    }
                                }]
                            }
                        }
                    };

                    var ctx = document.getElementById('line-stacked').getContext('2d');
                    new Chart(ctx, config);
                });
            </script>


        </div>

    </div>
    <!--begin::Body-->
</div>
