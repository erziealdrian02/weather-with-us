<?php

include './process/koneksi_api.php';

// Mengambil data dari sumber yang diberikan
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

?>

<!DOCTYPE html>
<html lang="en">

<?php include("component/header.php") ?>

<body class="bg-white text-white dark:bg-gray-900">
    <!-- Navbar -->
    <?php include("component/navbar.php") ?>
    <!-- Navbar -->

    <div class="container mx-auto p-4 w-full">
        <h2 class="text-center text-3xl font-bold mb-4">Daftar Provinsi</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-4">
            <?php foreach ($provinces as $provinsi) { ?>
                <div class="rounded-lg shadow transition transform hover:scale-105 hover:shadow-lg">
                    <a href="detail_provinsi.php?provinsi=<?php echo urlencode($provinsi['kode_provinsi']); ?>" class="block">
                        <div class="flex justify-start p-4 hover:bg-gray-700 hover:rounded-lg">
                            <img src="<?php echo $imageUrl . insertDash($provinsi['nama_gambar']) . ".png"; ?>" 
                                alt="<?php echo htmlspecialchars($provinsi['nama_gambar']); ?>" 
                                class="w-8 h-8 mr-2" />
                            <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($provinsi['nama']); ?></span>
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
