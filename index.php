<?php

$lat = "-6.4421888";
$lon = "106.9809664";

$wilayah = json_decode(file_get_contents("https://ibnux.github.io/BMKG-importer/cuaca/wilayah.json"), true);
$jml = count($wilayah);

for ($n = 0; $n < $jml; $n++) {
    $wilayah[$n]['jarak'] = distance($lat, $lon, $wilayah[$n]['lat'], $wilayah[$n]['lon'], 'K');
}

usort($wilayah, 'urutkanJarak');

$json = json_decode(file_get_contents("https://ibnux.github.io/BMKG-importer/cuaca/" . $wilayah[0]['id'] . ".json"), true);
$time = time();
$closest_weather = null;
foreach ($json as $cuaca) {
    $timeCuaca = strtotime($cuaca['jamCuaca']);
    if ($timeCuaca > $time) {
        $closest_weather = $cuaca;
        break;
    }
}
$current_date = date('Y-m-d');

date_default_timezone_set('Asia/Jakarta');
$current_day = date('l');
$current_time = date('H:i A');

function jarak($a, $b)
{
    return $a['jarak'] - $b['jarak'];
}

$today_weather = array_filter($json, function ($cuaca) use ($current_date) {
    return strpos($cuaca['jamCuaca'], $current_date) !== false;
});

function distance($lat1, $lon1, $lat2, $lon2, $unit)
{
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
    } else {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);
        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}

function urutkanJarak($a, $b)
{
    return $a['jarak'] - $b['jarak'];
}

function getTimeDescription($time)
{
    $hour = (int)date("H", strtotime($time));
    if ($hour >= 0 && $hour < 6) {
        return "Malam";
    } elseif ($hour >= 6 && $hour < 12) {
        return "Pagi";
    } elseif ($hour >= 12 && $hour < 18) {
        return "Siang";
    } else {
        return "Sore";
    }
}
$today_weather_json = json_encode(array_values($today_weather));
?>

<!DOCTYPE html>
<html lang="en">

<?php include("component/header.php"); ?>

<body class="bg-white text-white dark:bg-gray-900">
    <!-- Navba -->
    <?php include("component/navbar.php"); ?>
    <!-- Navbar -->

    <div class="p-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-4 flex justify-between font-bold">
                        <p><?php echo $current_day; ?></p>
                        <p><?php echo $current_time; ?></p>
                    </div>
                    <hr class="dark:border-gray-700" />
                    <div class="p-5 flex">
                        <div class="w-2/3">
                            <h5 class="mb-2 text-6xl font-bold tracking-tight text-gray-900 dark:text-white">
                                24°
                                <?php echo $closest_weather['tempC']; ?>°
                            </h5>
                            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
                                Cuaca
                                <strong class="dark:text-white"><?php echo $closest_weather['cuaca']; ?></strong>
                            </p>
                            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
                                Kelembapan
                                <strong class="dark:text-white"><?php echo $closest_weather['humidity']; ?>%</strong>
                            </p>
                        </div>
                        <!-- Right part for icon -->
                        <div class="w-1/3 flex justify-center items-center">
                            <div class="text-6xl">
                                <img src="https://ibnux.github.io/BMKG-importer/icon/<?php echo $closest_weather['kodeCuaca']; ?>.png" alt="Weather Icon">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Weekly Forecast -->
            <div class="flex space-x-4">
                <?php
                foreach ($today_weather as $cuaca) {
                    $timeCuaca = strtotime($cuaca['jamCuaca']);
                    $waktu = getTimeDescription($cuaca['jamCuaca']);
                ?>
                    <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                        <div class="p-4 text-center font-bold">
                            <p><?php echo $waktu; ?></p>
                        </div>
                        <hr class="dark:border-gray-700" />
                        <div class="p-5 flex">
                            <div class="text-center">
                                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                                    <?php echo $cuaca['tempC']; ?>°
                                </h5>
                                <div class="text-4xl">
                                    <div class="w-10"><img src="https://ibnux.github.io/BMKG-importer/icon/<?php echo $cuaca['kodeCuaca']; ?>.png" alt="Weather Icon"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <!-- Today's Forecast Graph -->
            <div class="flex items-center space-x-4">
                <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-4 text-center font-bold">
                        <p>Prediksi Hari ini</p>
                    </div>
                    <hr class="dark:border-gray-700" />
                    <div class="p-5 flex">
                        <canvas id="forecastChart" width="200" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="flex justify-between">
                <div>
                    <h2 class="text-2xl font-bold p-3">Today's Overview</h2>
                </div>
            </div>
            <div class="flex justify-between items-start">
                <div class="flex flex-col items-center space-y-4">
                    <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 m-1">
                        <div class="p-4 font-bold text-center">
                            <p>Kelembapan</p>
                        </div>
                        <hr class="dark:border-gray-700" />
                        <div class="p-5 flex flex-col items-center">
                            <div class="text-8xl mb-3">💧</div>
                            <p class="mt-3 top-4 font-normal text-gray-700 dark:text-gray-400">
                                <strong class="top-5 dark:text-white"><?php echo $closest_weather['humidity']; ?>%</strong>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap justify-center max-w-6xl">
                    <?php
                    foreach ($json as $cuaca) {
                    ?>
                        <div class="w-full sm:w-1/2 lg:w-1/4 max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 m-1">
                            <div class="p-4 text-center">
                                <p class="font-bold"><?php echo $cuaca['jamCuaca']; ?></p>
                            </div>
                            <hr class="dark:border-gray-700" />
                            <div class="p-5 flex">
                                <div class="w-2/3">
                                    <h5 class="mb-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                                        <?php echo $cuaca['tempC']; ?>°
                                    </h5>
                                    <p class="mb-3 font-light text-gray-700 dark:text-gray-400">
                                        Cuaca
                                        <strong class="dark:text-white"><?php echo $cuaca['cuaca']; ?></strong>
                                    </p>
                                    <p class="mb-3 font-light text-gray-700 dark:text-gray-400">
                                        Kelembapan
                                        <strong class="dark:text-white"><?php echo $cuaca['humidity']; ?>%</strong>
                                    </p>
                                </div>
                                <!-- Bagian kanan untuk ikon -->
                                <div class="w-1/3 flex justify-center items-center">
                                    <div class="text-5xl"><img src="https://ibnux.github.io/BMKG-importer/icon/<?php echo $cuaca['kodeCuaca']; ?>.png" alt="Weather Icon"></div>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php include("component/footer.php") ?>
    <?php include("component/script.php") ?>
</body>

</html>