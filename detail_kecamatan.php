<?php
include './process/koneksi_api.php';

if (!isset($_GET['kecamatan'])) {
    die("Provinsi tidak ditemukan.");
}

$kode_kecamatan = $_GET['kecamatan'];
$weatherKecamatan = json_decode(file_get_contents("https://api.bmkg.go.id/publik/prakiraan-cuaca?adm3=" . $kode_kecamatan), true);

// Inisialisasi array
$lokasiArray = [];
$lokasiTambahan = [];
$cuacaArray = [];

// Data utama lokasi
if (isset($weatherKecamatan['lokasi'])) {
    $lokasiArray = $weatherKecamatan['lokasi'];
}

// Proses data cuaca dan lokasi tambahan
if (isset($weatherKecamatan['data']) && is_array($weatherKecamatan['data'])) {
    foreach ($weatherKecamatan['data'] as $index => $data) {
        if (!empty($data['lokasi'])) {
            $lokasiTambahan[] = $data['lokasi'];
            
            // Simpan cuaca dengan key adm2
            if (!empty($data['cuaca']) && !empty($data['cuaca'][0])) {
                $adm2 = $data['lokasi']['adm2'] ?? $index;
                $cuacaArray[$adm2] = $data['cuaca'][0];
            }
        }
    }
}

$provinsi_nama = isset($lokasiArray['provinsi']) ? $lokasiArray['provinsi'] : 'Tidak Diketahui';
$kabupaten_nama = isset($lokasiArray['kotkab']) ? $lokasiArray['kotkab'] : 'Tidak Diketahui';
$kecamatan_nama = isset($lokasiArray['kecamatan']) ? $lokasiArray['kecamatan'] : 'Tidak Diketahui';

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

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuaca <?php echo htmlspecialchars($provinsi_nama); ?></title>
</head>
<body class="bg-white text-white dark:bg-gray-900">
    <?php include("component/header.php") ?>
    <?php include("component/navbar.php") ?>

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
        </div>  

        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-xl font-bold">Today's Overview</h2>
            </div>
            <div class="flex items-center">
                <span class="mr-4 font-bold">Provinsi Lain</span>
                <a href="provinsi.php" class="text-blue-500">See All</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="col-span-2">
               <?php foreach ($lokasiTambahan as $lokasiData) { 
                  $adm2 = $lokasiData['adm2'] ?? '';
                  $cuacaData = $cuacaArray[$adm2] ?? [];
               ?>
                  <div class="mb-4 p-4 bg-gray-800 rounded-lg shadow-lg">
                     <div class="flex items-center mb-4">
                        <a href="#" class="group inline-flex items-center hover:text-blue-500 transition-colors">  
                           <span class="font-bold text-xl mr-2">  
                              <?php echo htmlspecialchars($lokasiData['desa'] ?? 'Tidak Diketahui'); ?>  
                           </span>  
                           <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2 -rotate-45 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">  
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>  
                           </svg>  
                        </a>  
                        <span class="ml-auto text-lg">Kec. <?php echo htmlspecialchars($lokasiData['kecamatan']); ?> - Kelurahan / Desa. <?php echo htmlspecialchars($lokasiData['desa']); ?></span>
                     </div>

                     <?php if (!empty($cuacaData)) { ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                           <?php foreach ($cuacaData as $cuaca) { ?>
                                 <div class="text-center bg-gray-700 p-4 rounded-lg">
                                    <h3 class="font-bold"><?php echo date('H:i', strtotime($cuaca['local_datetime'])); ?></h3>
                                    <img src="<?php echo htmlspecialchars($cuaca['image']); ?>" 
                                          alt="Weather Icon" 
                                          class="mx-auto w-20 my-2" />
                                    <p class="text-sm mb-1"><?php echo htmlspecialchars($cuaca['weather_desc']); ?></p>
                                    <p class="text-sm mb-1">Suhu: <?php echo htmlspecialchars($cuaca['t']); ?>°C</p>
                                    <p class="text-sm">Kelembapan: <?php echo htmlspecialchars($cuaca['hu']); ?>%</p>
                                 </div>
                           <?php } ?>
                        </div>
                     <?php } ?>
                  </div>
               <?php } ?>
            </div>

            <!-- Sidebar Section -->
            <div>
                <div class="mb-4 p-4 bg-gray-800 rounded-lg shadow-lg">
                    <div class="flex items-center mb-4">
                        <span class="font-bold text-lg">Wilayah terdekat dari <?php echo htmlspecialchars($provinsi_nama); ?></span>
                    </div>
                    <div class="space-y-2">
                        <?php
                        // Contoh data wilayah terdekat (bisa diganti dengan data dinamis)
                        $nearbyRegions = [
                            ['name' => 'Banten', 'city' => 'Tangerang Selatan', 'district' => 'Serpong', 'distance' => 7.90],
                            ['name' => 'Jawa Barat', 'city' => 'Depok', 'district' => 'Depok', 'distance' => 12.02],
                            ['name' => 'DKI Jakarta', 'city' => 'Jakarta Selatan', 'district' => 'Jakarta Selatan', 'distance' => 18.34],
                            ['name' => 'Jawa Barat', 'city' => 'Kab. Bogor', 'district' => 'Cibinong', 'distance' => 12.02],
                            ['name' => 'DKI Jakarta', 'city' => 'Jakarta Barat', 'district' => 'Jakarta Barat', 'distance' => 12.02]
                        ];

                        foreach ($nearbyRegions as $region) { ?>
                            <a href="kota_detail.php" class="block mb-4">
                                <div class="flex items-center p-2 bg-gray-700 rounded-lg hover:bg-gray-600 transition duration-200">
                                    <img src="https://www.bmkg.go.id/asset/img/icon-prov/<?php echo str_replace(' ', '-', $region['name']); ?>.png" 
                                         alt="<?php echo htmlspecialchars($region['name']); ?>" 
                                         class="w-8 h-8 mr-2" />
                                    <span><?php echo htmlspecialchars("{$region['name']}, {$region['city']}, {$region['district']}"); ?></span>
                                    <span class="ml-auto"><?php echo number_format($region['distance'], 2, ",", "."); ?> km</span>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("component/footer.php") ?>
    <?php include("component/script.php") ?>
</body>
</html>