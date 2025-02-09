<?php

$wilayah = "https://ibnux.github.io/BMKG-importer/cuaca/wilayah.json";

$wilayah_lama = json_decode(file_get_contents("https://ibnux.github.io/BMKG-importer/cuaca/wilayah.json"), true);

$wilayah_baru = file_get_contents("https://raw.githubusercontent.com/kodewilayah/permendagri-72-2019/main/dist/base.csv");

$imageUrl = "https://www.bmkg.go.id/images/icon-provinsi/";
