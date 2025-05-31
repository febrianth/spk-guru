<?php
require_once '../api/auth-check.php';
require_once '../config/database.php';

$conn = Database::getConnection(); // Inisialisasi koneksi

$query = "
    SELECT 
        a.*, 
        s.kehadiran, s.sikap_profesional, s.tanggung_jawab, s.orientasi_layanan
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

// Ambil bobot dari tabel kriteria
$weightsQuery = "SELECT code, weight FROM criterias";
$weightsStmt = $conn->prepare($weightsQuery);
$weightsStmt->execute();
$weightsData = $weightsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($data as $row) {
    $S = 1;
    $S *= pow($row['kehadiran'], $weightsData['C1']);
    $S *= pow($row['sikap_profesional'], $weightsData['C2']);
    $S *= pow($row['tanggung_jawab'], $weightsData['C3']);
    $S *= pow($row['orientasi_layanan'], $weightsData['C4']);

    $row['nilai_akhir'] = $S;
    $totalS += $S;
    $ranked[] = $row;
}

// 2. Hitung nilai normalisasi
foreach ($ranked as &$row) {
    $row['normalisasi'] = $row['nilai_akhir'] / $totalS;
}
unset($row);

// 3. Urutkan berdasarkan nilai akhir (descending)
usort($ranked, function ($a, $b) {
    return $b['nilai_akhir'] <=> $a['nilai_akhir'];
});

// 4. Bangun tbody
$tbody = '';
$tbody_no_aksi = '';
if (!empty($ranked)) {
    foreach ($ranked as $index => $v) {
        $action_buttons = '<button class="btn btn-warning m-2" title="edit data" onclick="handleEdit(' . $v['id'] . ')"><i class="fas fa-pen"></i></button>';
        $action_buttons .= '<button class="btn btn-error m-2" title="hapus data"><i class="fas fa-trash" onclick="handleDelete(' . $v['id'] . ')"></i></button>';

        $position = ($v['position'] == 'dosen') ? '<span>Dosen</span>' : '<span>Tenaga Pendidikan</span>';
        $type = ($v['type'] == 'struktural') ? '<span>Struktural</span>' : '<span>Non Struktural</span>';

        $tbody .= '
            <tr class="hover:bg-base-300">
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($v['name']) . '</td>
                <td>' . htmlspecialchars($v['nip']) . '</td>
                <td>' . $position . '</td>
                <td>' . $type . '</td>
                <td>' . $v['kehadiran'] . '</td>
                <td>' . $v['sikap_profesional'] . '</td>
                <td>' . $v['tanggung_jawab'] . '</td>
                <td>' . $v['orientasi_layanan'] . '</td>
                <td>' . round($v['normalisasi'], 4) . '</td>
                <td>' . round($v['nilai_akhir'], 4) . '</td>
                <td>' . $action_buttons . '</td>
            </tr>
        ';

        $tbody_no_aksi .= '
            <tr>
                <td style="border: 1px solid black;">' . ($index + 1) . '</td>
                <td style="border: 1px solid black;">' . htmlspecialchars($v['name']) . '</td>
                <td style="border: 1px solid black;">' . htmlspecialchars($v['nip']) . '</td>
                <td style="border: 1px solid black;">' . $position . '</td>
                <td style="border: 1px solid black;">' . $type . '</td>
                <td style="border: 1px solid black;">' . $v['kehadiran'] . '</td>
                <td style="border: 1px solid black;">' . $v['sikap_profesional'] . '</td>
                <td style="border: 1px solid black;">' . $v['tanggung_jawab'] . '</td>
                <td style="border: 1px solid black;">' . $v['orientasi_layanan'] . '</td>
                <td style="border: 1px solid black;">' . round($v['normalisasi'], 4) . '</td>
                <td style="border: 1px solid black;">' . round($v['nilai_akhir'], 4) . '</td>
            </tr>
        ';
    }
} else {
    $tbody = '
            <tr class="hover:bg-base-300">
                <td colspan="12" class="text-center">Tidak ada data tersedia</td>
            </tr>
        ';
}

$query = "
    SELECT 
        a.* 
    FROM alternatives AS a
    LEFT JOIN scores AS s 
           ON s.alternative_id = a.id
    WHERE a.deleted_at IS NULL
      AND s.alternative_id IS NULL
";
$stmt = $conn->prepare($query);
$stmt->execute();

// Simpan data ke variabel array
$alternatives = $stmt->fetchAll(PDO::FETCH_ASSOC);
$alternative_options = '';
if (!empty($alternatives)) {
    foreach ($alternatives as $value) {
        $position = ($value['position'] == 'dosen') ? 'Dosen' : 'Tenaga Pendidikan';
        $type = ($value['type'] == 'struktural') ? 'Struktural' : 'Non Struktural';
        $alternative_options .= '<option value="' . $value['id'] . '">' . $value['nip'] . ' - ' . $value['name'] . ' | ' . $position . ' - ' . $type . '</option>';
    }
}

//alert if weight less than 1
$sum_weight = $conn->query("SELECT SUM(weight) FROM criterias")->fetch(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.datatables.net/2.3.1/js/dataTables.js"></script>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Tom Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    <!-- Tom Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
</head>
<style>
    .border {
        border: 1px solid black
    }
</style>

<body>
    <?php include('templates/header.php'); ?>
    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Peringkat</h1>
        </div>
    </header>
    <main>
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <?php if ($sum_weight != 1): ?>
                <!-- Warning Card jika total bobot tidak sama dengan 1 -->
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl shadow-sm p-6 transition-all duration-200 hover:shadow-md md:col-span-2 mb-4">
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
            <button class="btn btn-neutral mb-4" onclick="handleAdd()" title="Tambah Data Penilaian"><i class="fas fa-plus"></i>Tambah Data</button>
            <button class="btn btn-neutral mb-4" onclick="handlePrintPreview()" title="Cetak"><i class="fas fa-print"></i>Cetak</button>
            <div class="overflow-x-auto">
                <table class="table" id="dataTables">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Peringkat</th>
                            <th style="text-align: left;">Nama</th>
                            <th style="text-align: left;">NIP</th>
                            <th style="text-align: left;">Jabatan</th>
                            <th style="text-align: left;">Tipe</th>
                            <th style="text-align: left;">Kehadiran</th>
                            <th style="text-align: left;">Sikap Profesional</th>
                            <th style="text-align: left;">Tanggung Jawab</th>
                            <th style="text-align: left;">Orientasi Layanan</th>
                            <th style="text-align: left;">Normalisasi</th>
                            <th style="text-align: left;">Nilai Akhir</th>
                            <th style="text-align: left;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= $tbody; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <dialog id="modalCetak" class="modal modal-middle">
        <div class="modal-box w-full max-w-full">
            <h3 class="text-xl font-bold text-center mb-4">Laporan SPK Dosen</h3>

            <div id="printContent" class="overflow-auto p-4 bg-white">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black;">Peringkat</th>
                            <th style="border: 1px solid black;">Nama</th>
                            <th style="border: 1px solid black;">NIP</th>
                            <th style="border: 1px solid black;">Jabatan</th>
                            <th style="border: 1px solid black;">Tipe</th>
                            <th style="border: 1px solid black;">Kehadiran</th>
                            <th style="border: 1px solid black;">Sikap Profesional</th>
                            <th style="border: 1px solid black;">Tanggung Jawab</th>
                            <th style="border: 1px solid black;">Orientasi Layanan</th>
                            <th style="border: 1px solid black;">Normalisasi</th>
                            <th style="border: 1px solid black;">Nilai Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= $tbody_no_aksi; ?>
                    </tbody>
                </table>
            </div>

            <div class="modal-action mt-4">
                <button type="button" class="btn btn-primary" onclick="handlePrint()">Cetak</button>
                <form method="dialog">
                    <button class="btn">Tutup</button>
                </form>
            </div>
        </div>
    </dialog>

    <dialog id="modalTambahData" class="modal modal-middle">
        <div class="modal-box w-11/12 max-w-3xl">
            <h3 class="text-lg font-bold mb-4" id="modalTitle">Tambah Penilaian</h3>
            <form id="formScores">
                <input type="hidden" name="id" id="id">

                <div id="alternative_field">
                    <div>
                        <label class="label">Pilih Alternatif (Dosen)</label>
                        <select name="alternative_id" id="alternative_id" class="w-full" required>
                            <option value="">Pilih Dosen...</option>
                            <?= $alternative_options; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Kehadiran</label>
                        <select name="kehadiran" id="kehadiran" class="select select-bordered w-full" required>
                            <option value="">Pilih Level...</option>
                            <option value="1.00">Level 1</option>
                            <option value="2.00">Level 2</option>
                            <option value="3.00">Level 3</option>
                            <option value="4.00">Level 4</option>
                            <option value="5.00">Level 5</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Sikap Profesional</label>
                        <select name="sikap_profesional" id="sikap_profesional" class="select select-bordered w-full" required>
                            <option value="">Pilih Level...</option>
                            <option value="1.00">Level 1</option>
                            <option value="2.00">Level 2</option>
                            <option value="3.00">Level 3</option>
                            <option value="4.00">Level 4</option>
                            <option value="5.00">Level 5</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tanggung Jawab</label>
                        <select name="tanggung_jawab" id="tanggung_jawab" class="select select-bordered w-full" required>
                            <option value="">Pilih Level...</option>
                            <option value="1.00">Level 1</option>
                            <option value="2.00">Level 2</option>
                            <option value="3.00">Level 3</option>
                            <option value="4.00">Level 4</option>
                            <option value="5.00">Level 5</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Orientasi Layanan Pelanggan</label>
                        <select name="orientasi_layanan" id="orientasi_layanan" class="select select-bordered w-full" required>
                            <option value="">Pilih Level...</option>
                            <option value="1.00">Level 1</option>
                            <option value="2.00">Level 2</option>
                            <option value="3.00">Level 3</option>
                            <option value="4.00">Level 4</option>
                            <option value="5.00">Level 5</option>
                        </select>
                    </div>
                </div>

                <div class="modal-action mt-6">
                    <button type="button" class="btn btn-primary" onclick="handleSubmit()">Simpan</button>
                </div>
            </form>

            <form method="dialog" class="mt-2 text-right">
                <button class="btn">Batal</button>
            </form>
        </div>
    </dialog>

    <span id="loading-overlay"
        class="hidden fixed inset-0 bg-gray-100/50 flex items-center justify-center z-50 pointer-events-auto">
        <span class="loading loading-dots loading-lg text-black"></span>
    </span>

    </div>
    <?php include('templates/logoutModal.php'); ?>
</body>
<script>
    new TomSelect('#alternative_id', {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });

    let table = new DataTable('#dataTables');

    function showLoading() {
        document.getElementById('loading-overlay').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loading-overlay').classList.add('hidden');
    }

    function handleAdd() {
        document.getElementById('formScores').reset();
        document.querySelector('#alternative_field').style.display = 'block';
        document.getElementById('id').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Data Penilaian';
        document.getElementById('modalTambahData').showModal();
    }

    function handlePrintPreview() {
        document.getElementById('modalCetak').showModal();
    }

    function handlePrint() {
        const printContent = document.getElementById('printContent');
        const printWindow = window.open('', '', 'width=900,height=650');

        printWindow.document.write(`
            <html>
            <head>
                <title>Laporan SPK Dosen dan Tenaga Pendidikan</title>
                <style>
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th, td {
                        border: 1px solid black;
                        padding: 6px;
                        text-align: left;
                    }
                    h3 {
                        text-align: center;
                    }
                    body {
                        font-family: sans-serif;
                        padding: 20px;
                    }
                </style>
            </head>
            <body>
                <h3>Laporan SPK Dosen dan Tenaga Pendidikan</h3>
                ${printContent.innerHTML}
            </body>
            </html>
        `);

        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }


    function handleEdit(id) {
        showLoading();
        fetch(`../api/get-score-by-id.php?id=${id}`)
            .then(response => {
                if (!response.ok) throw new Error('Gagal fetch data');
                return response.json();
            })
            .then(res => {
                if (res.success) {
                    const data = res.data;
                    document.getElementById('id').value = data.alternative_id || '';
                    document.querySelector('#alternative_field').style.display = 'none';

                    // Isi nilai masing-masing kriteria berdasarkan id
                    document.getElementById('kehadiran').value = data.kehadiran || '';
                    document.getElementById('orientasi_layanan').value = data.orientasi_layanan || '';
                    document.getElementById('sikap_profesional').value = data.sikap_profesional || '';
                    document.getElementById('tanggung_jawab').value = data.tanggung_jawab || '';

                    document.getElementById('modalTitle').textContent = 'Edit Penilaian';
                    document.getElementById('modalTambahData').showModal();
                } else {
                    alert(res.message || 'Data tidak ditemukan');
                }
            })
            .catch(error => {
                console.error(error);
                alert('Gagal mengambil data');
            })
            .finally(() => {
                hideLoading();
            });
    }

    function handleSubmit() {
        const form = document.getElementById('formScores');
        const formData = new FormData(form);

        showLoading();
        fetch('../api/add-score.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    form.reset();
                    document.getElementById('modalTambahData').close();
                    location.reload();
                } else {
                    alert(res.message || 'Gagal menyimpan data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan data.');
            })
            .finally(() => {
                hideLoading();
            });
    }

    function handleDelete(id) {
        if (!confirm('Apakah kamu yakin ingin menghapus data ini?')) return;

        showLoading();
        const formData = new FormData();
        formData.append('id', id);

        fetch('../api/delete-score-by-id.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Gagal menghapus data');
                }
            })
            .catch(error => {
                console.error(error);
                alert('Terjadi kesalahan saat menghapus data.');
            })
            .finally(() => {
                hideLoading();
            });
    }
</script>

</html>