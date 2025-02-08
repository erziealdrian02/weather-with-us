<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Get the PHP data passed to JavaScript
    const todaysWeather = <?php echo $today_weather_json; ?>;

    // Extract the data for the chart
    const labels = todaysWeather.map(weather => new Date(weather.jamCuaca).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    }));
    const temperatures = todaysWeather.map(weather => weather.tempC);

    // Create the chart
    const ctx = document.getElementById("forecastChart").getContext("2d");
    const forecastChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: "Suhu (°C)",
                data: temperatures,
                backgroundColor: "rgba(4, 196, 224, 0.2)",
                borderColor: "rgba(4, 196, 224, 1)",
                borderWidth: 1,
            }],
        },
        options: {
            scales: {
                y: {
                    beginAtZero: false,
                },
            },
        },
    });
</script>
<!-- <script>
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
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18,
        id: "mapbox/streets-v11",
        accessToken: "SLKDF2J0H0UIlaDI6VVJ9FGKeR-SAQUdphCbtUMEb2Y",
    }).addTo(map);

    // Tambahkan marker untuk Bandung
    var markerBandung = L.marker([-6.90992, 107.64691])
        .addTo(map)
        .bindPopup("<b>Bandung</b><br>Kota di Jawa Barat")
        .openPopup();

    // Tambahkan marker untuk Jakarta
    var markerJakarta = L.marker([-6.176396, 106.826591])
        .addTo(map)
        .bindPopup("<b>Jakarta</b><br>Ibukota Indonesia")
        .openPopup();
</script> -->