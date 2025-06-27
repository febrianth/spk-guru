<?php
require_once '../api/auth-check.php';
require_once '../config/database.php';

$conn = Database::getConnection(); // Inisialisasi koneksi
$query = "
    SELECT 
        a.*, 
        s.C1, s.C2, s.C3, s.C4
    FROM alternatives AS a
    INNER JOIN scores AS s 
            ON s.alternative_id = a.id
    WHERE a.deleted_at IS NULL
";
$stmt = $conn->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 1. Hitung nilai WP (S)
$ranked = [];
$totalS = 0;

// Ambil bobot dan TIPE kriteria dari tabel
$criteriaQuery = "SELECT code, weight, attribute, name FROM criterias";
$criteriaStmt = $conn->prepare($criteriaQuery);
$criteriaStmt->execute();
$allCriteria = $criteriaStmt->fetchAll(PDO::FETCH_ASSOC);

$criteriaData = [];
foreach ($allCriteria as $v) {
    $criteriaData[$v['code']] = [
        'weight' => $v['weight'],
        'attribute' => $v['attribute'],
        'name' => $v['name']
    ];
}

// Proses Perhitungan Nilai S
foreach ($data as $row) {
    $S = 1;

    // Definisikan pemetaan kolom skor ke kode kriteria
    $criteriaMapping = [
        'C1',
        'C2',
        'C3',
        'C4'
    ];

    foreach ($criteriaMapping as $code) {
        // Ambil data kriteria saat ini
        $criterion = $criteriaData[$code];
        $weight = $criterion['weight'];
        $attribute = $criterion['attribute'];
        $score = $row[$code];

        // **KONDISI UTAMA: Cek tipe kriteria (cost atau benefit)**
        if ($attribute == 'cost') {
            // Jika 'cost', gunakan bobot negatif
            $S *= pow($score, -$weight);
        } else {
            // Jika 'benefit', gunakan bobot positif (standar)
            $S *= pow($score, $weight);
        }
    }

    $row['nilai_akhir'] = $S;
    $totalS += $S;
    $ranked[] = $row;
}

// 2. Hitung nilai normalisasi (Vektor V)
foreach ($ranked as &$row) {
    // Hindari pembagian dengan nol jika tidak ada data
    $row['normalisasi'] = ($totalS > 0) ? ($row['nilai_akhir'] / $totalS) : 0;
}
unset($row);

// 3. Urutkan berdasarkan nilai akhir (descending)
usort($ranked, function ($a, $b) {
    return $b['nilai_akhir'] <=> $a['nilai_akhir'];
});

//alert if weight less than 1
$sum_weight = $conn->query("SELECT SUM(weight) FROM criterias")->fetch(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
</head>

<body>
    <?php include('templates/header.php'); ?>
    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Dashboard</h1>
        </div>
    </header>
    <main>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if ($sum_weight != 1): ?>
                    <!-- Warning Card jika total bobot tidak sama dengan 1 -->
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl shadow-sm p-6 transition-all duration-200 hover:shadow-md md:col-span-2">
                        <h2 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
                            Peringatan Bobot Kriteria
                        </h2>
                        <div class="flex flex-col sm:flex-row gap-4 items-start bg-white border border-red-100 rounded-lg p-4">
                            <div class="text-3xl text-red-400">⚠️</div>
                            <p class="text-sm sm:text-base text-gray-800 leading-relaxed">
                                Total bobot kriteria saat ini <strong>belum berjumlah 1</strong>. Hal ini dapat menyebabkan hasil peringkat menjadi tidak akurat.
                                Silakan sesuaikan kembali nilai bobot di menu <span class="font-medium text-red-600">"Kriteria"</span> agar perhitungan sistem pendukung keputusan dapat berjalan dengan optimal.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Welcome Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center gap-5">
                        <div class="text-5xl bg-primary-50 text-primary-500 p-4 rounded-lg">
                            👋
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Selamat Datang, Admin!</h2>
                            <p class="text-gray-600 mt-1">Semoga harimu menyenangkan dan produktif ✨</p>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Teachers Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <span class="mr-2">🏆</span> Top 5 Dosen Terbaik Saat Ini
                    </h2>
                    <ul class="space-y-2">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <li class="flex items-center text-gray-700 py-1 px-3 rounded-lg hover:bg-gray-50">
                                <span class="font-medium text-primary-600 mr-2"><?= $i + 1; ?>.</span>
                                <?php if (isset($ranked[$i]['name'], $ranked[$i]['nilai_akhir'])): ?>
                                    <?= $ranked[$i]['name'] . ' (' . $ranked[$i]['nilai_akhir'] . ')'; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>

                <!-- Decision Support System Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md md:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <span class="mr-2">📊</span> Sistem Pendukung Keputusan
                    </h2>
                    <p class="text-gray-700 mb-3">
                        Sistem ini menggunakan metode <span class="font-medium text-primary-600">Weighted Product (WP)</span> untuk menentukan peringkat Dosen atau tenaga kependidikan terbaik berdasarkan 4 kriteria:
                    </p>
                    <?php if (!empty($allCriteria)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                        <?php foreach ($allCriteria as $v): ?>
                            <div class="bg-primary-50 rounded-lg p-4 text-center">
                                <div class="font-medium text-primary-700"><?= $v['name']; ?></div>
                                <div class="text-lg font-bold text-primary-800 mt-1"><?= isset($v['code']) ? $v['weight'] : '-'; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Usage Tips Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md md:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <span class="mr-2">💡</span> Tips Penggunaan
                    </h2>
                    <div class="flex flex-col sm:flex-row gap-4 items-center bg-yellow-50 rounded-lg p-4">
                        <div class="text-3xl">🔍</div>
                        <p class="text-gray-700">
                            Gunakan menu <span class="font-medium text-primary-600">"Alternatif"</span> untuk mengisi data Dosen, lalu lihat dan isi penilaian di menu <span class="font-medium text-primary-600">"Penilaian"</span>. Pastikan data lengkap agar hasil akurat.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    </div>
    <?php include('templates/logoutModal.php'); ?>
</body>

</html>