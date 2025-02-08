<?php

/**
 * Contoh dibuat oleh @ibnux
 */

// Initialize a cURL session to get IP address
// Initialize a cURL session to get IP address
// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, "https://api.ipify.org?format=json");
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// $response = curl_exec($ch);
// curl_close($ch);

// $responseData = json_decode($response, true);
// $ip_address = $responseData['ip'];

// // Initialize a cURL session to get location data
// $cari_wilayah = "http://ip-api.com/json/" . $ip_address;
// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $cari_wilayah);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// $response = curl_exec($ch);
// curl_close($ch);

// $responseData = json_decode($response, true);
// $lat = $responseData['lat'];
// $lon = $responseData['lon'];

// Display the latitude and longitude
echo "Latitude: " . $lat . "<br>";
echo "Longitude: " . $lon . "<br>";

// Get the BMKG wilayah data
$wilayah = json_decode(file_get_contents("https://ibnux.github.io/BMKG-importer/cuaca/wilayah.json"), true);
$jml = count($wilayah);

// Calculate the distance
for ($n = 0; $n < $jml; $n++) {
    $wilayah[$n]['jarak'] = distance($lat, $lon, $wilayah[$n]['lat'], $wilayah[$n]['lon'], 'K');
}

// Sort by distance
usort($wilayah, 'urutkanJarak');

// Display the closest 5 regions
echo "<pre>";
echo "\n<h2>Urutkan dari yang terdekat<br>$lat,$lon</h2>\n";
echo "\n";
for ($n = 0; $n < 5; $n++) {
    print_r($wilayah[$n]);
    echo "\n";
}
echo "\n<h2>";
echo $wilayah[0]['propinsi'] . "," . $wilayah[0]['kota'] . "," . $wilayah[0]['kecamatan'] . "\n";
echo number_format($wilayah[0]['jarak'], 2, ",", ".") . " km</h2>\n";
echo "\n";

// Get weather data for the closest region
$json = json_decode(file_get_contents("https://ibnux.github.io/BMKG-importer/cuaca/" . $wilayah[0]['id'] . ".json"), true);
$time = time();
$n = 0;
echo '<table border="1"><tr>';
foreach ($json as $cuaca) {
    $timeCuaca = strtotime($cuaca['jamCuaca']);
    // Only display future weather data
    if ($timeCuaca > $time) {
        echo '<td>';
        echo '<img src="https://ibnux.github.io/BMKG-importer/icon/' . $cuaca['kodeCuaca'] . '.png" class="image">';
        echo '<p>' . $cuaca['cuaca'] . '</p>';
        echo "</td>\n";
    }
}
echo '</tr><table>';
echo "\n";

print_r($json);

function urutkanJarak($a, $b)
{
    return $a['jarak'] - $b['jarak'];
}

// Function to calculate distance
// https://www.geodatasource.com/developers/php
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
