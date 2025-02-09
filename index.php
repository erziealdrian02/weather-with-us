<?php
include './process/koneksi_api.php';

// if (!isset($_GET['desa'])) {
//     die("Provinsi tidak ditemukan.");
// }

$kode_desa = '32.01.07.2008';
// $kode_desa = $_GET['desa'];
$weatherDesa = json_decode(file_get_contents("https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4=" . $kode_desa), true);

// Inisialisasi array
$lokasiArray = [];
$lokasiTambahan = [];
$cuacaArray = [];
$chartData = [];
$allWeatherData = [];
$dailyWeather = [];

// Data utama lokasi
if (isset($weatherDesa['lokasi'])) {
    $lokasiArray = $weatherDesa['lokasi'];
}

// Proses data cuaca dan lokasi tambahan
if (isset($weatherDesa['data']) && is_array($weatherDesa['data'])) {
    foreach ($weatherDesa['data'] as $index => $data) {
        if (!empty($data['lokasi'])) {
            $lokasiTambahan[] = $data['lokasi'];
            
            // Simpan cuaca dengan key adm2
            if (!empty($data['cuaca']) && !empty($data['cuaca'][0])) {
                $adm2 = $data['lokasi']['adm2'] ?? $index;
                $cuacaArray[$adm2] = $data['cuaca'][0];

                foreach ($data['cuaca'][0] as $cuaca) {
                    $chartData[] = [
                        'time' => date('H:i', strtotime($cuaca['local_datetime'])),
                        'temp' => floatval($cuaca['t'])
                    ];
                }
            }
        }
    }
}

foreach ($weatherDesa['data'][0] as $data) {
    if (!empty($data['cuaca'])) {
        foreach ($data['cuaca'] as $cuacaList) { 
            foreach ($cuacaList as $cuaca) {
                // Simpan semua cuaca tanpa filter
                $allWeatherData[] = $cuaca;

                // Ambil tanggalnya dari local_datetime
                $cuacaDate = date('Y-m-d', strtotime($cuaca['local_datetime']));

                // Simpan ke array harian
                $dailyWeather[$cuacaDate][] = $cuaca;
            }
        }
    }
}

usort($chartData, function($a, $b) {
    return strtotime($a['time']) - strtotime($b['time']);
});

$chart_data_json = json_encode($chartData);

$provinsi_nama = isset($lokasiArray['provinsi']) ? $lokasiArray['provinsi'] : 'Tidak Diketahui';
$kabupaten_nama = isset($lokasiArray['kotkab']) ? $lokasiArray['kotkab'] : 'Tidak Diketahui';
$kecamatan_nama = isset($lokasiArray['kecamatan']) ? $lokasiArray['kecamatan'] : 'Tidak Diketahui';
$desa_nama = isset($lokasiArray['desa']) ? $lokasiArray['desa'] : 'Tidak Diketahui';

date_default_timezone_set('Asia/Jakarta');
$current_date = date('Y-m-d');
$current_day = date('l');
$current_time = date('H:i A');
$current_datetime = date('Y-m-d H:i'); // Format waktu sekarang

$nearest_weather = null;
$min_time_diff = PHP_INT_MAX; // Inisialisasi perbedaan waktu minimum

foreach ($cuacaArray as $adm2 => $cuaca) {
    foreach ($cuaca as $data) {
        if (!isset($data['local_datetime'])) continue; // Skip jika tidak ada local_datetime

        $weather_time = strtotime($data['local_datetime']);
        $current_time = strtotime($current_datetime);

        $time_diff = abs($weather_time - $current_time); // Selisih waktu absolut

        if ($time_diff < $min_time_diff) {
            $min_time_diff = $time_diff;
            $nearest_weather = $data;
        }
    }
}

// Cek apakah ada data cuaca yang sesuai
if ($nearest_weather) {
    $temperature = $nearest_weather['t'];
    $weather_desc = $nearest_weather['weather_desc'];
    $humidity = $nearest_weather['hu'];
    $weather_icon = $nearest_weather['image'];
} else {
    $temperature = 'N/A';
    $weather_desc = 'Data Tidak Tersedia';
    $humidity = 'N/A';
    $weather_icon = '';
}

function get_provinsi_icon($nama_provinsi) {  
    $nama_provinsi_lower = strtolower($nama_provinsi);  
    $nama_file = str_replace(' ', '-', $nama_provinsi_lower);  

    if ($nama_file === 'daerah-istimewa-yogyakarta') {  
        $nama_file = 'di-yogyakarta';
    }
    
    $nama_file .= '.png';  
    $imageUrl = 'https://www.bmkg.go.id/images/icon-provinsi/';  
    
    return $imageUrl . $nama_file;  
}

$icon_url = get_provinsi_icon($provinsi_nama);
?>

<body class="bg-white text-white dark:bg-gray-900">
    <?php include("component/header.php"); ?>
    <?php include("component/navbar.php"); ?>
    
    <div class="container mx-auto p-4">
        <div class="flex items-center justify-center mb-5 space-x-2">  
            <img src="<?php echo $icon_url; ?>" alt="Logo Provinsi" class="w-8" />  
            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($provinsi_nama); ?></h1>  
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">  
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>  
            </svg>  
            <h1 class="text-xl font-bold"><?php echo htmlspecialchars($kabupaten_nama); ?></h1>  
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">  
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>  
            </svg>  
            <h1 class="text-xl font-bold"><?php echo htmlspecialchars($kecamatan_nama); ?></h1>  
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">  
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>  
            </svg>  
            <h1 class="text-xl font-bold"><?php echo htmlspecialchars($desa_nama); ?></h1>  
        </div>  
    </div>

    <div class="p-4">
        <div class="flex justify-between items-center">
            <!-- Current Weather Display -->
            <div class="flex items-center space-x-2">
                <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-4 flex justify-between font-bold">
                        <p><?php echo $current_day; ?></p>
                        <p><?php echo date('H:i', strtotime($nearest_weather['local_datetime'])); ?></p>
                    </div>
                    <hr class="dark:border-gray-700" />
                    <div class="p-5 flex">
                        <div class="w-2/3">
                            <h5 class="mb-2 text-6xl font-bold tracking-tight text-gray-900 dark:text-white">
                                <?php echo $temperature; ?>°
                            </h5>
                            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
                                Cuaca: <strong class="dark:text-white"><?php echo $weather_desc; ?></strong>
                            </p>
                            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
                                Kelembapan: <strong class="dark:text-white"><?php echo $humidity; ?>%</strong>
                            </p>
                        </div>
                        <div class="w-1/3 flex justify-center items-center">
                            <img src="<?php echo $weather_icon; ?>" alt="Weather Icon" class="w-16">
                        </div>
                    </div>
                </div>
            </div>

            <?php 
                
            ?>

            <div class="flex space-x-4">
                <?php foreach ($lokasiTambahan as $lokasiData) { 
                    $adm2 = $lokasiData['adm2'] ?? '';
                    $cuacaData = $cuacaArray[$adm2] ?? [];
                ?>
                    <?php if (!empty($cuacaData)) { ?>
                        <?php foreach ($cuacaData as $cuaca) { ?>
                            <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                                <div class="p-4 text-center font-bold">
                                    <p><?php echo date('H:i', strtotime($cuaca['local_datetime'])); ?></p>
                                </div>
                                <hr class="dark:border-gray-700" />
                                <div class="p-5 flex">
                                    <div class="text-center">
                                        <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($cuaca['t']); ?>°
                                        </h5>
                                        <div class="text-4xl">
                                            <div class="w-10"><img src="<?php echo htmlspecialchars($cuaca['image']); ?>" alt="Weather Icon"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
            </div>
            <!-- Menampilkan Data cuaca minggu ini  -->

            <!-- Forecast Chart -->
            <div class="mt-8">
                <div class="bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-4 text-center font-bold">
                        <p>Prediksi Suhu 24 Jam</p>
                    </div>
                    <hr class="dark:border-gray-700" />
                    <div class="p-5">
                    <canvas id="forecastChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menamampilakan cuaca minggu ini -->
        <div class="mt-8">
            <h2 class="text-2xl font-bold mb-4">Prakiraan Cuaca Mingguan</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <?php foreach ($lokasiTambahan as $lokasiData) { 
                    $adm2 = $lokasiData['adm2'] ?? '';
                    $cuacaData = $cuacaArray[$adm2] ?? [];
                ?>
                    <?php if (!empty($cuacaData)) { ?>
                        <?php foreach ($cuacaData as $cuaca) { ?>
                        <div class="bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                            <div class="p-4 text-center">
                                <p class="font-bold"><?php echo date('d M Y H:i', strtotime($cuaca['local_datetime'])); ?></p>
                            </div>
                            <hr class="dark:border-gray-700" />
                            <div class="p-5">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h5 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                            <?php echo $cuaca['t']; ?>°
                                        </h5>
                                        <p class="text-gray-700 dark:text-gray-400">
                                            <?php echo $cuaca['weather_desc']; ?>
                                        </p>
                                        <p class="text-gray-700 dark:text-gray-400 mt-2">
                                            Kelembapan: <?php echo $cuaca['hu']; ?>%
                                        </p>
                                    </div>
                                    <img src="<?php echo htmlspecialchars($cuaca['image']); ?>" alt="Weather Icon" class="w-16 h-16">
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>  
            </div>
        </div>

        <!-- Additional weather information -->
        <script>
            // Initialize chart with all available data
            document.addEventListener('DOMContentLoaded', function() {
                var ctx = document.getElementById('forecastChart').getContext('2d');
                var chartData = <?php echo $chart_data_json; ?>;
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(data => data.time),
                        datasets: [{
                            label: 'Suhu (°C)',
                            data: chartData.map(data => data.temp),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                grid: {
                                    color: 'rgba(156, 163, 175, 0.1)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value + '°C';
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(156, 163, 175, 0.1)'
                                }
                            }
                        }
                    }
                });
            });
        </script>
    </div>
    
    <?php include("component/footer.php"); ?>
    <?php include("component/script.php"); ?>
</body>
</html>