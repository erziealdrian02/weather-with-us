<?php


$sumber = "https://ibnux.github.io/BMKG-importer/cuaca/wilayah.json";
$konten = file_get_contents($sumber);
$data = json_decode($konten, true);

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
