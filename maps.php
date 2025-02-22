<?php

include './process/koneksi_api.php';

$rows = explode("\n", trim($wilayah_baru));

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

function insert_Dash($string) {
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

// Fungsi untuk mendapatkan data unik berdasarkan provinsi
function getUniqueProvinces($weatherData) {
    $seen = [];
    $uniqueData = [];
    foreach ($weatherData as $data) {
        $provinsi = $data['lokasi']['provinsi'];
        if (!in_array($provinsi, $seen)) {
            $uniqueData[] = $data;
            $seen[] = $provinsi;
        }
    }
    return $uniqueData;
}

// Get unique provinces
$uniqueProvincesData = getUniqueProvinces($weatherData);

function getMajorCities($weatherData) {
    $majorCities = [];
    foreach ($weatherData as $data) {
        if (strpos(strtoupper($data['lokasi']['kotkab']), 'KOTA') === 0) {
            $majorCities[] = $data;
        }
    }
    return $majorCities;
}

$majorCitiesData = getMajorCities($weatherData);

// Function to format province names for URLs
function formatProvinceUrl($string) {
    $exceptions = [
        "KEPULAUAN BANGKA BELITUNG" => "kepulauan-bangka-belitung",
        "DKI JAKARTA" => "dki-jakarta",
        "DI YOGYAKARTA" => "di-yogyakarta"
    ];
    
    $normalized = strtolower($string);
    if (array_key_exists(strtoupper($string), $exceptions)) {
        return $exceptions[strtoupper($string)];
    }
    
    return str_replace(' ', '-', $normalized);
}

// Function to format display names
function formatDisplayName($string) {
    return ucwords(strtolower($string));
}
?>

<body class="bg-white text-white dark:bg-gray-900">
    <!-- Navbar -->
    <?php include("component/header.php"); ?>
    <?php include("component/navbar.php") ?>

    <div class="container mx-auto p-4">
        <div class="flex justify-center mb-5">
            <img
                src="https://www.bmkg.go.id/asset/img/id.png"
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
        var map = L.map("map", {
            center: [-2.548926, 118.0148634],
            zoom: 5,
            minZoom: 5,
            maxBounds: [
                [-11.0, 94.0],
                [6.0, 141.0],
            ],
        });

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18,
        }).addTo(map);

        var kotaMarkers = [];
        var semuaMarkers = [];

        // First, create markers for major cities
        <?php
        foreach ($majorCitiesData as $data) {
            $lokasi = $data['lokasi'];
            $cuaca = $data['cuaca'];
            
            $lat = $lokasi['lat'];
            $lon = $lokasi['lon'];
            $kota = htmlspecialchars($lokasi['kotkab']);
            $provinsi = htmlspecialchars($lokasi['provinsi']);
            $provinsi_link = formatProvinceUrl($provinsi);
            
            $kodeCuaca = $cuaca['kodeCuaca'] ?? '';
            $deskripsiCuaca = $cuaca['cuaca'] ?? 'Data tidak tersedia';
        ?>
            var marker = L.marker([<?php echo $lat; ?>, <?php echo $lon; ?>])
                .bindPopup(`
                    <div style='text-align: center;'>
                        <b><?php echo $kota; ?></b>
                        <br>
                        <a href='detail_provinsi.php?provinsi=<?php echo $provinsi_link ?>' style='color: black; text-decoration: none;'>
                            <?php echo formatDisplayName($provinsi); ?>
                        </a>
                        <br>
                        <p><?php echo htmlspecialchars($deskripsiCuaca); ?></p>
                        <?php if ($kodeCuaca) { ?>
                            <img src='https://ibnux.github.io/BMKG-importer/icon/<?php echo $kodeCuaca; ?>.png' alt='Weather Icon' class='mx-auto w-20' />
                        <?php } ?>
                    </div>
                `);
            kotaMarkers.push(marker);
        <?php } ?>

        // Then, create markers for all locations
        <?php
        foreach ($weatherData as $data) {
            $lokasi = $data['lokasi'];
            $cuaca = $data['cuaca'];
            
            $lat = $lokasi['lat'];
            $lon = $lokasi['lon'];
            $kota = htmlspecialchars($lokasi['kotkab']);
            $provinsi = htmlspecialchars($lokasi['provinsi']);
            $provinsi_link = formatProvinceUrl($provinsi);
            
            $kodeCuaca = $cuaca['kodeCuaca'] ?? '';
            $deskripsiCuaca = $cuaca['cuaca'] ?? 'Data tidak tersedia';
        ?>
            var marker = L.marker([<?php echo $lat; ?>, <?php echo $lon; ?>])
                .bindPopup(`
                    <div style='text-align: center;'>
                        <b><?php echo $kota; ?></b>
                        <br>
                        <a href='detail_provinsi.php?provinsi=<?php echo $provinsi_link ?>' style='color: black; text-decoration: none;'>
                            <?php echo formatDisplayName($provinsi); ?>
                        </a>
                        <br>
                        <p><?php echo htmlspecialchars($deskripsiCuaca); ?></p>
                        <?php if ($kodeCuaca) { ?>
                            <img src='https://ibnux.github.io/BMKG-importer/icon/<?php echo $kodeCuaca; ?>.png' alt='Weather Icon' class='mx-auto w-20' />
                        <?php } ?>
                    </div>
                `);
            semuaMarkers.push(marker);
        <?php } ?>

        // Function to show only major cities
        function tampilkanKotaMarkers() {
            // Remove all markers first
            semuaMarkers.forEach(function(marker) {
                map.removeLayer(marker);
            });
            // Add only major city markers
            kotaMarkers.forEach(function(marker) {
                marker.addTo(map);
            });
        }

        // Function to show all markers
        function tampilkanSemuaMarkers() {
            semuaMarkers.forEach(function(marker) {
                marker.addTo(map);
            });
        }

        // Zoom event handler
        map.on('zoomend', function() {
            if (map.getZoom() < 7) {
                // At lower zoom levels, show only major cities
                semuaMarkers.forEach(function(marker) {
                    map.removeLayer(marker);
                });
                tampilkanKotaMarkers();
            } else {
                // At higher zoom levels, show all locations
                tampilkanSemuaMarkers();
            }
        });

        // Initially show only major cities
        tampilkanKotaMarkers();
    </script>

        
    <?php include("component/footer.php") ?>
    <?php include("component/script.php") ?>
</body>

</html>