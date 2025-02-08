<?php
include("component/header.php");

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
   // Pengecualian khusus
   $exceptions = [
      "BangkaBelitung" => "Kepulauan Bangka Belitung",
      "DKIJakarta" => "DKI Jakarta",
      "DIYogyakarta" => "DI Yogyakarta"
   ];

   if (array_key_exists($string, $exceptions)) {
      return $exceptions[$string];
   }

   return preg_replace('/(?<!^)([A-Z])/', ' $1', $string);
}

// Fungsi untuk menghitung jarak antara dua titik geografis
function distance($lat1, $lon1, $lat2, $lon2, $unit)
{
   if (($lat1 == $lat2) && ($lon1 == $lon2)) {
      return 0;
   } else {
      $theta = $lon1 - $lon2;
      $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
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

// Fungsi untuk mengurutkan wilayah berdasarkan jarak
function urutkanJarak($a, $b)
{
   return $a['jarak'] - $b['jarak'];
}

// Tangkap parameter provinsi dari URL
$provinsi = isset($_GET['provinsi']) ? $_GET['provinsi'] : '';

// Atur URL gambar dan nama tampilan
$imageUrl = "https://www.bmkg.go.id/asset/img/icon-prov/" . insertDash($provinsi) . ".png";
$displayName = addSpace($provinsi);

// Ambil data wilayah
$sumberWilayah = "https://ibnux.github.io/BMKG-importer/cuaca/wilayah.json";
$kontenWilayah = file_get_contents($sumberWilayah);
$dataWilayah = json_decode($kontenWilayah, true);

// Filter data wilayah berdasarkan provinsi
$filteredWilayah = array_filter($dataWilayah, function ($row) use ($provinsi) {
   return $row['propinsi'] === $provinsi;
});

// Fungsi untuk mengambil data cuaca berdasarkan idWilayah
function getWeatherData($idWilayah)
{
   // $url = "https://ibnux.github.io/BMKG-importer/cuaca/" . $idWilayah . ".json";
   $url = "./json/" . $idWilayah . ".json";
   $konten = file_get_contents($url);
   return json_decode($konten, true);
}

// Fungsi untuk mengonversi waktu ke deskripsi waktu
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

// Fungsi untuk memfilter cuaca berdasarkan tanggal saat ini
function filterWeatherDataByDate($weatherData, $date)
{
   return array_filter($weatherData, function ($cuaca) use ($date) {
      return date("Y-m-d", strtotime($cuaca['jamCuaca'])) === $date;
   });
}

// Ambil lat dan lon dari wilayah tertentu (misal, yang pertama dari $filteredWilayah)
$lat = isset($filteredWilayah[0]['lat']) ? $filteredWilayah[0]['lat'] : 0;
$lon = isset($filteredWilayah[0]['lon']) ? $filteredWilayah[0]['lon'] : 0;

// Hitung jarak untuk setiap wilayah
foreach ($dataWilayah as &$wilayah) {
   $wilayah['jarak'] = distance($lat, $lon, $wilayah['lat'], $wilayah['lon'], 'K');
}

// Urutkan wilayah berdasarkan jarak
usort($dataWilayah, 'urutkanJarak');

// Ambil 5 wilayah terdekat dan filter out yang memiliki ID 0
$nearestWilayah = array_filter(array_slice($dataWilayah, 0, 5), function ($wilayah) {
   return $wilayah['id'] !== 0;
});

$currentDate = date("Y-m-d");
?>

<body class="bg-white text-white dark:bg-gray-900">
   <!-- Navbar -->
   <?php include("component/navbar.php") ?>
   <!-- Navbar -->

   <div class="container mx-auto p-4">
      <div class="flex justify-center mb-5">
         <img src="<?php echo $imageUrl; ?>" alt="Logo" class="w-12 mr-2" />
         <h1 class="text-4xl font-bold"><?php echo htmlspecialchars($displayName); ?></h1>
      </div>
      <!-- Header Section -->
      <div class="flex justify-between items-center mb-4">
         <div>
            <h2 class="text-xl font-bold">Today's Overview</h2>
         </div>
         <div class="flex items-center">
            <span class="mr-4 font-bold">Provinsi Lain</span>
            <a href="provinsi.php" class="text-blue-500">See All</a>
         </div>
      </div>

      <!-- Main Content Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
         <!-- Weather Forecast Section -->
         <div class="col-span-2">
            <!-- Single Weather Row -->
            <?php foreach ($filteredWilayah as $wilayah) {
               $weatherData = getWeatherData($wilayah['id']);
               $filteredWeatherData = filterWeatherDataByDate($weatherData, $currentDate);
            ?>
               <div class="mb-4 p-4 bg-gray-800 rounded-lg shadow-lg">
                  <div class="flex items-center mb-4">
                     <span class="font-bold text-xl"><?php echo htmlspecialchars($displayName); ?></span>
                     <!-- <br> <?php echo htmlspecialchars($wilayah['id']); ?> -->
                     <span class="ml-auto text-lg"><?php echo htmlspecialchars($wilayah['kota']); ?> - Kec. <?php echo htmlspecialchars($wilayah['kecamatan']); ?></span>
                  </div>
                  <div class="grid grid-cols-4 gap-4 mb-4">
                     <?php foreach ($filteredWeatherData as $cuaca) { ?>
                        <div class="text-center">
                           <h3 class="font-bold"><?php echo getTimeDescription($cuaca['jamCuaca']); ?></h3>
                           <img src="https://ibnux.github.io/BMKG-importer/icon/<?php echo $cuaca['kodeCuaca']; ?>.png" alt="Weather Icon" class="mx-auto w-20" />
                           <p><?php echo htmlspecialchars($cuaca['cuaca']); ?></p>
                           <p>Suhu: <?php echo htmlspecialchars($cuaca['tempC']); ?>°C</p>
                           <p>Kelembapan: <?php echo htmlspecialchars($cuaca['humidity']); ?>%</p>
                        </div>
                     <?php } ?>
                  </div>
               </div>
            <?php } ?>
         </div>

         <!-- Sidebar Section -->
         <div>
            <div class="mb-4 p-4 bg-gray-800 rounded-lg shadow-lg">
               <div class="flex items-center mb-4">
                  <span class="font-bold text-lg">Wilayah terdekat dari <?php echo htmlspecialchars($displayName); ?></span>
               </div>
               <div class="space-y-2">
                  <!-- <?php foreach ($nearestWilayah as $wilayah) { ?>
                     <a href="kota_detail.php?id=<?php echo $wilayah['id']; ?>" class="block mb-10">
                        <div class="flex mb-3 justify-center items-center p-2 bg-gray-700 rounded-lg hover:bg-gray-600 transition duration-200 ease-in-out">
                           <img src="https://www.bmkg.go.id/asset/img/icon-prov/<?php echo insertDash($wilayah['propinsi']); ?>.png" alt="<?php echo htmlspecialchars($wilayah['propinsi']); ?>" class="w-8 h-8 mr-2" />
                           <span><?php echo htmlspecialchars($wilayah['propinsi']); ?> - <?php echo htmlspecialchars($wilayah['kota']); ?> - <?php echo htmlspecialchars($wilayah['kecamatan']); ?></span>
                           <span class="ml-auto"><?php echo number_format($wilayah['jarak'], 2, ",", "."); ?> km</span>
                        </div>
                     </a>
                  <?php } ?> -->
                  <a href="kota_detail.php" class="block mb-10">
                     <div class="flex mb-3 justify-center items-center p-2 bg-gray-700 rounded-lg hover:bg-gray-600 transition duration-200 ease-in-out">
                        <img src="https://www.bmkg.go.id/asset/img/icon-prov/Banten.png" alt="Banten" class="w-8 h-8 mr-2" />
                        <span>Banten, Kota Tangerang Selatan, Serpong
                        </span>
                        <span class="ml-auto">7.90 km</span>
                     </div>
                  </a>
                  <a href="kota_detail.php" class="block mb-10">
                     <div class="flex mb-3 justify-center items-center p-2 bg-gray-700 rounded-lg hover:bg-gray-600 transition duration-200 ease-in-out">
                        <img src="https://www.bmkg.go.id/asset/img/icon-prov/Jawa-Barat.png" alt="Banten" class="w-8 h-8 mr-2" />
                        <span>JawaBarat, Kota Depok, Depok
                        </span>
                        <span class="ml-auto">12.02 km</span>
                     </div>
                  </a>
                  <a href="kota_detail.php" class="block mb-10">
                     <div class="flex mb-3 justify-center items-center p-2 bg-gray-700 rounded-lg hover:bg-gray-600 transition duration-200 ease-in-out">
                        <img src="https://www.bmkg.go.id/asset/img/icon-prov/DKI-Jakarta.png" alt="Banten" class="w-8 h-8 mr-2" />
                        <span>DKIJakarta, Kota Jakarta Selatan, Jakarta Selatan
                        </span>
                        <span class="ml-auto">18.34 km</span>
                     </div>
                  </a>
                  <a href="kota_detail.php" class="block mb-10">
                     <div class="flex mb-3 justify-center items-center p-2 bg-gray-700 rounded-lg hover:bg-gray-600 transition duration-200 ease-in-out">
                        <img src="https://www.bmkg.go.id/asset/img/icon-prov/Jawa-Barat.png" alt="Banten" class="w-8 h-8 mr-2" />
                        <span>JawaBarat, Kab. Bogor, Cibinong
                        </span>
                        <span class="ml-auto">12.02 km</span>
                     </div>
                  </a>
                  <a href="kota_detail.php" class="block mb-10">
                     <div class="flex mb-3 justify-center items-center p-2 bg-gray-700 rounded-lg hover:bg-gray-600 transition duration-200 ease-in-out">
                        <img src="https://www.bmkg.go.id/asset/img/icon-prov/DKI-Jakarta.png" alt="Banten" class="w-8 h-8 mr-2" />
                        <span>DKIJakarta, Kota Jakarta Barat, Jakarta Barat
                        </span>
                        <span class="ml-auto">12.02 km</span>
                     </div>
                  </a>
               </div>
            </div>
         </div>
      </div>
   </div>

   <?php include("component/footer.php") ?>
   <?php include("component/script.php") ?>
</body>

</html>