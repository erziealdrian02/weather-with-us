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
?>

<!DOCTYPE html>
<html lang="en">

<?php include("component/header.php") ?>

<body class="bg-white text-white dark:bg-gray-900">
    <!-- Navba -->
    <?php include("component/navbar.php") ?>
    <!-- Navbar -->

    <div class="container mx-auto p-4 w-full">
        <h2 class="text-center text-3xl font-bold mb-4">Provinsi Lainnya</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-4">
            <!-- Start of Province Item -->
            <?php
            foreach ($uniqueProvincesData as $row) {
                if ($propinsi = 'Bangka-Belitung') {
                    $propinsi = 'kepulauan-bangka-belitung';
                } elseif ($propinsi = 'D-K-I-Jakarta') {
                    $propinsi = 'dki-jakarta';
                } elseif ($propinsi = 'd-i-yogyakarta') {
                    $propinsi = 'Di-yogyakarta';
                }
                $imageUrl = "https://www.bmkg.go.id/asset/img/icon-prov/" . insertDash($row['propinsi']) . ".png";
                $displayName = addSpace($row['propinsi']);
            ?>
                <div class="rounded-lg shadow transition transform hover:scale-105 hover:shadow-lg">
                    <a href="provinsi_detail.php?provinsi=<?php echo urlencode($row['propinsi']); ?>" class="block">
                        <div class="flex justify-start p-4 hover:bg-gray-700 hover:rounded-lg">
                            <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($row['propinsi']); ?>" class="w-8 h-8 mr-2" />
                            <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($displayName); ?></span>
                        </div>
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php include("component/footer.php") ?>
    <?php include("component/script.php") ?>
</body>

</html>