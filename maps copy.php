<?php

include './process/koneksi_api.php';

$rows = explode("\n", trim($wilayah_baru));

// Mengambil hanya data provinsi
$provinces = [];
foreach ($rows as $row) {
    $cols = str_getcsv($row);
    if (count($cols) == 2 && strpos($cols[0], '.') === false) {
        $nama_proper = ucwords(strtolower($cols[1])); // Ubah hanya huruf pertama yang besar
        $nama_gambar = strtolower(str_replace(' ', '-', $cols[1])); // Ubah menjadi lowercase dan ganti spasi dengan '-'
        
        // Penanganan khusus untuk Daerah Istimewa Yogyakarta
        if ($nama_gambar === 'daerah-istimewa-yogyakarta') {
            $nama_gambar = 'di-yogyakarta';
        }
        
        $provinces[] = [
            'kode' => $cols[0],
            'kode_provinsi' => substr($cols[0], 0, 2), // Mengambil kode provinsi
            'nama' => $nama_proper,
            'nama_gambar' => $nama_gambar
        ];
    }
}

function insertDash($string) {
    return strtolower(str_replace(' ', '-', $string));
}

$weatherData = [];
foreach ($provinces as $province) {
    $kodeProvinsi = $province['kode_provinsi'];
    $url = "https://api.bmkg.go.id/publik/prakiraan-cuaca?adm1={$kodeProvinsi}";

    // Inisialisasi cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Eksekusi permintaan dan dapatkan respons
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode respons JSON
    $data = json_decode($response, true);

    if (isset($data['data'])) {
        foreach ($data['data'] as $location) {
            if (isset($location['lokasi']['adm2'])) {
                $adm2 = $location['lokasi']['adm2'];
                $provinsi = $location['lokasi']['provinsi'];
                $kotkab = $location['lokasi']['kotkab'];
                $lon = $location['lokasi']['lon'];
                $lat = $location['lokasi']['lat'];
                $timezone = $location['lokasi']['timezone'];

                // Ambil data cuaca terdekat dengan waktu saat ini
                $nearestWeather = null;
                $currentTime = new DateTime('now', new DateTimeZone($timezone));
                foreach ($location['cuaca'] as $weatherPeriod) {
                    foreach ($weatherPeriod as $weather) {
                        $localDatetime = new DateTime($weather['local_datetime'], new DateTimeZone($timezone));
                        if ($localDatetime >= $currentTime) {
                            $nearestWeather = $weather;
                            break 2;
                        }
                    }
                }

                if ($nearestWeather) {
                    $weatherData[] = [
                        'lokasi' => [
                            'adm1' => $kodeProvinsi,
                            'adm2' => $adm2,
                            'provinsi' => $provinsi,
                            'kotkab' => $kotkab,
                            'lon' => $lon,
                            'lat' => $lat,
                            'timezone' => $timezone,
                        ],
                        'cuaca' => $nearestWeather,
                    ];
                }
            }
        }
    }
}

// echo "<pre>";
// print_r($weatherData);
// echo "</pre>";
?>

<body class="bg-white text-white dark:bg-gray-900">
    <!-- Navbar -->
    <?php include("component/header.php"); ?>
    <?php include("component/navbar.php"); ?>

    <div class="container mx-auto p-4">
        <div class="flex justify-center mb-5">
            <img
                src="https://www.bmkg.go.id/images/id.png"
                alt="Logo"
                class="w-12 mr-2" />
            <h1 class="text-4xl font-bold">
                Peta Indonesia dengan Titik Persebaran Cuaca
            </h1>
        </div>
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-xl font-bold">Today's Overview</h2>
            </div>
        </div>

        <!-- Main Content Section -->
        <div class="grid grid-cols-1 gap-4">
            <!-- Weather Forecast Section -->
            <div>
                <!-- Single Weather Row -->
                <div class="mb-4 p-4 bg-gray-800 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div
                            class="w-full bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-600 dark:border-white">
                            <div class="p-1 flex rounded-lg">
                                <div id="map" class="rounded-lg"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inisialisasi peta
        var map = L.map("map", {
            center: [-2.548926, 118.0148634], // Koordinat tengah Indonesia
            zoom: 5,
            minZoom: 5, // Set minZoom untuk mencegah zoom out lebih jauh
            maxBounds: [
                [-11.0, 94.0], // Batas Selatan-Barat Indonesia
                [6.0, 141.0], // Batas Utara-Timur Indonesia
            ],
        });

        // Tambahkan layer peta dari OpenStreetMap
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: ' &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18,
        }).addTo(map);

        // Marker untuk ibukota provinsi
        var ibukotaMarkers = [];
        var semuaMarkers = [];

        <?php
        foreach ($weatherData as $weather) {
            $id = $weather['lokasi']['adm2'];
            $lat = $weather['lokasi']['lon'];
            $lon = $weather['lokasi']['lat'];
            $provinsi_link = $weather['lokasi']['provinsi'];
            $kota = htmlspecialchars($weather['cuaca']['kotkab']);
            $provinsi = htmlspecialchars($weather['lokasi']['provinsi']);

            // Ambil data cuaca untuk wilayah ini
            $weatherData = getWeatherData($id);
            $currentDate = date("Y-m-d");

            // Filter data cuaca untuk tanggal saat ini
            $filteredWeatherData = array_filter($weatherData, function ($weather) use ($currentDate) {
                return date("Y-m-d", strtotime($weather['lokasi']['local_datetime'])) === $currentDate;
            });

            // Ambil cuaca pertama dari data yang difilter
            $cuacaHariIni = !empty($filteredWeatherData) ? reset($filteredWeatherData) : null;
            $deskripsiCuaca = $cuacaHariIni ? $cuacaHariIni['lokasi']['weather_desc'] : 'Data tidak tersedia';
            $kodeCuaca = $cuacaHariIni ? $cuacaHariIni['lokasi']['image'] : '';

            // Tentukan apakah marker ini adalah ibukota provinsi
            $isIbukota = in_array($row, $uniqueProvincesData);
        ?>

            var marker = L.marker([<?php echo $lat; ?>, <?php echo $lon; ?>])
                .bindPopup(`
                    <div style='text-align: center;'>
                        <b><?php echo $id; ?></br><?php echo $kota; ?></b>
                        <br>
                        <a href='detail_provinsi.php?provinsi=<?php echo $provinsi_link ?>' style='color: black; text-decoration: none;'><?php echo $provinsi; ?></a>
                        <br>
                        <p><?php echo htmlspecialchars($deskripsiCuaca); ?></p>
                        <?php if ($kodeCuaca) { ?>
                            <img src='<?php echo $kodeCuaca; ?>' alt='Weather Icon' class='mx-auto w-20' />
                        <?php } ?>
                    </div>
                `);
            semuaMarkers.push(marker);

            if (<?php echo $isIbukota ? 'true' : 'false'; ?>) {
                ibukotaMarkers.push(marker); // Tambahkan ke array ibukotaMarkers jika marker adalah ibukota
                marker.addTo(map); // Tambahkan hanya marker ibukota ke peta secara default
            }

        <?php } ?>

        // Fungsi untuk menampilkan semua marker
        function tampilkanSemuaMarkers() {
            semuaMarkers.forEach(function(marker) {
                marker.addTo(map);
            });
        }

        // Fungsi untuk hanya menampilkan marker ibukota
        function tampilkanIbukotaMarkers() {
            ibukotaMarkers.forEach(function(marker) {
                marker.addTo(map);
            });
        }

        // Event listener untuk zoom
        map.on('zoomend', function() {
            if (map.getZoom() < 7) { // Pada zoom level rendah, tampilkan hanya ibukota
                semuaMarkers.forEach(function(marker) {
                    map.removeLayer(marker);
                });
                tampilkanIbukotaMarkers();
            } else { // Pada zoom level tinggi, tampilkan semua marker
                tampilkanSemuaMarkers();
            }
        });

        // Atur tampilan awal (zoom level rendah)
        tampilkanIbukotaMarkers();
    </script>

    <?php include("component/footer.php") ?>
    <?php include("component/script.php") ?>
</body>

</html>