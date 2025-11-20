<?php
use App\Models\Utils;
?>
<div class="card  mb-4 mb-md-5 border-1 border-primary" style="border-radius: 0px;">
    <!--begin::Header-->
    <div class="d-flex justify-content-between pt-2 pb-3 px-md-4 border-bottom border-secondary border-2">
        <h3>
            <b>Events by categories</b>
        </h3>
    </div>
    <div class="card-body py-2 py-md-3">
        <canvas id="graph_animals" style="width: 100%;"></canvas>
    </div>
</div>




<script>
    $(function() {
        var config = {
            type: 'pie',
            data: {
                datasets: [{
                    data: [370, 57, 101, 210, 259, 712, 100],
                    backgroundColor: [
                        '#8EFCDF',
                        '#F43DE3',
                        '#F6DE5C',
                        '#7D57F8',
                        '#431B02',
                        '#23A2E9',
                        '#34F1B7',
                        '#868686',
                        '#C71C5D',
                        '#D0B1FD',
                    ],
                    label: 'Dataset 1'
                }],
                labels: [
                    'Treament',
                    'Temperature check',
                    'Stolen',
                    'Death',
                    'Borth',
                    'Movement',
                    'Milk'
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'left',
                    display: true,
                },
                title: {
                    display: false,
                    text: 'Persons with Disabilities by Categories'
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        };

        var ctx = document.getElementById('graph_animals').getContext('2d');
        new Chart(ctx, config);
    });
</script>
