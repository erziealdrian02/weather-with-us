<?php
include './process/koneksi.php';
?>

<!DOCTYPE html>
<html lang="en">


<?php include("component/header.php") ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@0.7.7"></script>

<body class="bg-white text-white dark:bg-gray-900">
   <!-- Navbar -->
   <?php include("component/navbar.php") ?>
   <!-- Navbar -->


   <div class="container mx-auto p-4">
      <div class="flex justify-center mb-5">
         <img
            src="https://www.bmkg.go.id/asset/img/id.png"
            alt="Logo"
            class="w-12 mr-2" />
         <h1 class="text-4xl font-bold">Statistika Cuaca di Indonesia</h1>
      </div>
      <!-- Header Section -->
      <div class="flex justify-between items-center mb-4">
         <div>
            <h2 class="text-xl font-bold">Today's Overview</h2>
         </div>
         <div class="flex items-center">
            <span class="mr-4 font-bold">Peta Lainnya</span>
            <a href="maps.php" class="text-blue-500">See All</a>
         </div>
      </div>


      <!-- Main Content Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
         <!-- Weather Forecast Section -->
         <?php
         include './process/koneksi.php';

         // Query untuk mengambil data wilayah
         $select = mysqli_query($connect, "SELECT * FROM wilayah");

         while ($data = mysqli_fetch_array($select)) {
            $id_wilayah = $data['id_wilayah'];
            $queryCuaca = mysqli_query($connect, "SELECT Tanggal, Tavg, RR, RH_avg FROM cuaca WHERE id_wilayah = '$id_wilayah'");

            $labels = [];
            $temperaturData = [];
            $hujanData = [];
            $kelembapanData = [];

            $count = 0;
            $interval = 21; // 21 hari (3 minggu)

            while ($cuaca = mysqli_fetch_array($queryCuaca)) {
               if ($count % $interval == 0) { // Ambil setiap 21 data
                  $labels[] = $cuaca['Tanggal'];
                  $temperaturData[] = $cuaca['Tavg'];
                  $hujanData[] = $cuaca['RR'];
                  $kelembapanData[] = $cuaca['RH_avg'];
               }
               $count++;
            }

            $labelsJson = json_encode($labels);
            $temperaturJson = json_encode($temperaturData);
            $hujanJson = json_encode($hujanData);
            $kelembapanJson = json_encode($kelembapanData);
         ?>
            <div class="col-span-2">
               <!-- Single Weather Row -->


               <div class="mb-4 p-4 bg-gray-800 rounded-lg shadow-lg">
                  <div class="flex items-center mb-4">
                     <span class="font-bold text-xl"><?php echo $data['propinsi']; ?></span>
                     <span class="font-bold text-xl"><?php echo $data['id_wilayah']; ?></span>
                     <span class="ml-auto text-lg"><?php echo $data['kota']; ?></span>
                  </div>
                  <div class="flex items-center">
                     <div class="w-full bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-600 dark:border-white">
                        <div class="p-4 text-center ">
                           <p class="text-black">Statistika Temperatur, Curah Hujan, dan Kelembapan Juni 2023 - Juni 2024</p>
                        </div>
                        <hr class="dark:border-white" />
                        <div class="p-5 flex">
                           <canvas id="forecastChart_<?php echo $id_wilayah; ?>"></canvas>
                        </div>
                     </div>
                  </div>
               </div>

               <script>
                  const ctx_<?php echo $id_wilayah; ?> = document.getElementById("forecastChart_<?php echo $id_wilayah; ?>").getContext("2d");
                  const forecastChart_<?php echo $id_wilayah; ?> = new Chart(ctx_<?php echo $id_wilayah; ?>, {
                     type: "line",
                     data: {
                        labels: <?php echo $labelsJson; ?>,
                        datasets: [{
                              label: "Temperatur",
                              data: <?php echo $temperaturJson; ?>,
                              backgroundColor: "#66DEF5",
                              borderColor: "#66DEF5",
                              borderWidth: 1,
                           },
                           {
                              label: "Hujan",
                              data: <?php echo $hujanJson; ?>,
                              backgroundColor: "#5a03d5",
                              borderColor: "#5a03d5",
                              borderWidth: 1,
                           },
                           {
                              label: "Kelembapan",
                              data: <?php echo $kelembapanJson; ?>,
                              backgroundColor: "#a22e4f",
                              borderColor: "#a22e4f",
                              borderWidth: 1,
                           }
                        ],
                     },
                     options: {
                        scales: {
                           y: {
                              beginAtZero: true,
                           },
                        },
                        plugins: {
                           zoom: {
                              pan: {
                                 enabled: true,
                                 mode: 'xy',
                              },
                              zoom: {
                                 enabled: true,
                                 mode: 'xy',
                              }
                           }
                        }
                     },
                  });
               </script>
            </div>

            <!-- Sidebar Section -->
            <!-- Sidebar Section -->
            <div>
               <div class="mb-4 p-4 bg-gray-800 rounded-lg shadow-lg">
                  <div class="flex items-center mb-4">
                     <span class="font-bold text-lg">Peta Ibu Kota <?php echo $data['propinsi']; ?></span>
                  </div>
                  <div class="flex items-center">
                     <div
                        class="w-full h-1/2 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-600 dark:border-white">
                        <div class="p-1 flex rounded-lg">
                           <div id="map_wilayah_<?php echo $id_wilayah; ?>" style="height: 470px; width: 100%;" class="rounded-lg"></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>

            <script>
               var map_wilayah_<?php echo $id_wilayah; ?> = L.map("map_wilayah_<?php echo $id_wilayah; ?>", {
                  center: [<?php echo $data['lat']; ?>, <?php echo $data['lon']; ?>], // Use data from the database
                  zoom: 10, // You can adjust the zoom level if needed
                  minZoom: 5,
                  maxBounds: [
                     [-11.0, 94.0], // South-West boundary of Indonesia
                     [6.0, 141.0], // North-East boundary of Indonesia
                  ],
               });

               L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                  maxZoom: 18,
               }).addTo(map_wilayah_<?php echo $id_wilayah; ?>);

               // Add a marker using data from the database
               var markerWilayah_<?php echo $id_wilayah; ?> = L.marker([<?php echo $data['lat']; ?>, <?php echo $data['lon']; ?>])
                  .addTo(map_wilayah_<?php echo $id_wilayah; ?>)
                  .bindPopup("<b><?php echo $data['kota']; ?></b><br> <?php echo $data['propinsi']; ?>");
            </script>
         <?php
         }
         ?>
      </div>
   </div>



   <script>
      var map_wilayah = L.map("map_wilayah", {
         center: [-2.548926, 118.0148634], // Center of Indonesia
         zoom: 5,
         minZoom: 5,
         maxBounds: [
            [-11.0, 94.0], // South-West boundary of Indonesia
            [6.0, 141.0], // North-East boundary of Indonesia
         ],
      });

      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
         attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
         maxZoom: 18,
      }).addTo(map_wilayah);

      // Example markers
      var markerBandung = L.marker([-6.90992, 107.64691])
         .addTo(map_wilayah)
         .bindPopup("<b>Bandung</b><br>Kota di Jawa Barat");

      var markerJakarta = L.marker([-6.176396, 106.826591])
         .addTo(map_wilayah)
         .bindPopup("<b>Jakarta</b><br>Ibukota Indonesia");
   </script>
   <?php include("component/footer.php") ?>
   <?php include("component/script.php") ?>
</body>

</html>