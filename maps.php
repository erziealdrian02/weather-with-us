<?php
include("component/header.php");

// Mengambil data cuaca wilayah dari API
$sumber = "https://ibnux.github.io/BMKG-importer/cuaca/wilayah.json";
$konten = file_get_contents($sumber);
$data = json_decode($konten, true);

// Fungsi untuk mengambil data cuaca berdasarkan idWilayah
function getWeatherData($idWilayah)
{
    $url = "./json/" . $idWilayah . ".json";
    if (file_exists($url)) {
        $konten = file_get_contents($url);
        return json_decode($konten, true);
    }
    return [];
}


// Fungsi untuk mendapatkan data unik berdasarkan provinsi
function getUniqueProvinces($data)
{
    $seen = [];
    $uniqueData = [];
    foreach ($data as $row) {
        if (!in_array($row['propinsi'], $seen)) {
            $uniqueData[] = $row;
            $seen[] = $row['propinsi'];
        }
    }
    return $uniqueData;
}

// Mendapatkan data unik
$uniqueProvincesData = getUniqueProvinces($data);

// Fungsi untuk menyisipkan tanda "-" pada huruf besar di tengah kata
function insertDash($string)
{
    // Pengecualian khusus
    $exceptions = [
        "BangkaBelitung" => "kepulauan-bangka-belitung",
        "DKIJakarta" => "dki-jakarta",
        "DIYogyakarta" => "Di-yogyakarta"
    ];

    if (array_key_exists($string, $exceptions)) {
        return $exceptions[$string];
    }

    return preg_replace('/(?<!^)([A-Z])/', '-$1', $string);
}

// Fungsi untuk menambahkan spasi sebelum huruf besar
function addSpace($string)
{
    return preg_replace('/(?<!^)([A-Z])/', ' $1', $string);
}
?>

<body class="bg-white text-white dark:bg-gray-900">
    <!-- Navbar -->
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
        foreach ($data as $row) {
            $id = $row['id'];
            $lat = $row['lat'];
            $lon = $row['lon'];
            $provinsi_link = $row['propinsi'];
            $kota = htmlspecialchars($row['kota']);
            $provinsi = htmlspecialchars($row['propinsi']);

            // Ambil data cuaca untuk wilayah ini
            $weatherData = getWeatherData($id);
            $currentDate = date("Y-m-d");

            // Filter data cuaca untuk tanggal saat ini
            $filteredWeatherData = array_filter($weatherData, function ($cuaca) use ($currentDate) {
                return date("Y-m-d", strtotime($cuaca['jamCuaca'])) === $currentDate;
            });

            // Ambil cuaca pertama dari data yang difilter
            $cuacaHariIni = !empty($filteredWeatherData) ? reset($filteredWeatherData) : null;
            $deskripsiCuaca = $cuacaHariIni ? $cuacaHariIni['cuaca'] : 'Data tidak tersedia';
            $kodeCuaca = $cuacaHariIni ? $cuacaHariIni['kodeCuaca'] : '';

            // Tentukan apakah marker ini adalah ibukota provinsi
            $isIbukota = in_array($row, $uniqueProvincesData);
        ?>

            var marker = L.marker([<?php echo $lat; ?>, <?php echo $lon; ?>])
                .bindPopup(`
                    <div style='text-align: center;'>
                        <b><?php echo $id; ?></br><?php echo $kota; ?></b>
                        <br>
                        <a href='provinsi_detail.php?provinsi=<?php echo $provinsi_link ?>' style='color: black; text-decoration: none;'><?php echo $provinsi; ?></a>
                        <br>
                        <p><?php echo htmlspecialchars($deskripsiCuaca); ?></p>
                        <?php if ($kodeCuaca) { ?>
                            <img src='https://ibnux.github.io/BMKG-importer/icon/<?php echo $kodeCuaca; ?>.png' alt='Weather Icon' class='mx-auto w-20' />
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