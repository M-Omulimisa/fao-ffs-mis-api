<?php 
    use App\Models\District;
    use App\Models\Animal;
    $types = [
        'Cattle',
        'Goat',
        'Sheep',
    ];
    $data = [];
    $label = [];
    foreach ($types as $key => $d) {
        $data[] = Animal::where('type', $d)->count();
        if($d == "Goat"){
            $label[] = "Goats";
        }else{
            $label[] = $d;
        }
    }

?><canvas id="myChart" style="width: 100%;"></canvas>
<script>
$(function () {
    var ctx = document.getElementById("myChart").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($label); ?>,
            datasets: [{
                label: 'Animals Species',
                data: <?= json_encode($data); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132)',
                    'rgba(54, 162, 235)',
                    'rgba(255, 206, 86)',
                    'rgba(75, 192, 192)',
                    'rgba(153, 102, 255)',
                    'rgba(255, 159, 64)'
                ],
                borderColor: [
                    'rgba(255,99,132,1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true
                    }
                }]
            }
        }
    });
});
</script>