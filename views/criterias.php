<?php
require_once '../api/auth-check.php';
require_once '../config/database.php';

$conn = Database::getConnection(); // Inisialisasi koneksi

$query = "SELECT * FROM criterias";
$stmt = $conn->prepare($query);
$stmt->execute();

// Simpan data ke variabel array
$criterias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tbody = '';
$action_buttons = '';
if (!empty($criterias)) {
    foreach ($criterias as $index => $v) {
        $action_buttons = '<button class="btn btn-warning mr-2" title="edit data" onclick="handleEdit(' . $v['id'] . ')"><i class="fas fa-pen"></i></button>';
        // $action_buttons .= '<button class="btn btn-error" title="hapus data"><i class="fas fa-trash" onclick="handleDelete(' . $v['id'] . ')"></i></button>';
        $tbody .= '
            <tr class="hover:bg-base-300">
                <td>' . $index + 1 . '</td>
                <td>' . htmlspecialchars($v['code']) . '</td>
                <td>' . htmlspecialchars($v['name']) . '</td>
                <td>' . htmlspecialchars($v['weight']) . '</td>
                <td>' . htmlspecialchars($v['attribute']) . '</td>
                <td>' . $action_buttons . '</td>
            </tr>
        ';
    }
} else {
    $tbody = '
            <tr class="hover:bg-base-300">
                <td colspan="6" class="text-center">Tidak ada data tersedia</td>
            </tr>
        ';
}

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
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Kriteria</h1>
        </div>
    </header>
    <main>
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Info Card untuk Tambah Kriteria -->
            <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl shadow-sm p-6 transition-all duration-200 hover:shadow-md mb-3">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    Informasi Penting
                </h2>
                <div class="flex flex-col sm:flex-row gap-4 items-start bg-white border border-blue-100 rounded-lg p-4">
                    <div class="text-3xl text-blue-400">ℹ️</div>
                    <p class="text-sm sm:text-base text-gray-800 leading-relaxed">
                        Total bobot seluruh kriteria <strong>tidak boleh melebihi 1.00</strong>.
                        Jika jumlah bobot masih kurang dari 1.00, sistem tetap dapat melakukan perhitungan,
                        namun <span class="font-medium text-blue-600">hasil peringkat bisa jadi tidak akurat</span>.
                        Pastikan Anda menyesuaikan bobot secara proporsional untuk mendapatkan hasil terbaik.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-left">No</th>
                            <th class="text-left">Kode</th>
                            <th class="text-left">Nama</th>
                            <th class="text-left">Bobot</th>
                            <th class="text-left">Atribut</th>
                            <th class="text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= $tbody; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <dialog id="modalTambahData" class="modal modal-middle">
        <div class="modal-box w-11/12 max-w-3xl">
            <h3 class="text-lg font-bold mb-4" id="modalTitle">Tambah Data Kriteria</h3>
            <form id="formCriterias">
                <input type="hidden" name="id" id="id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Kode</label>
                        <input type="text" name="code" id="code" class="input input-bordered w-full" disabled>
                    </div>
                    <div>
                        <label class="label">Nama</label>
                        <input type="text" name="name" id="name" class="input input-bordered w-full" disabled>
                    </div>
                    <div>
                        <label class="label">Bobot</label>
                        <input type="number" name="weight" id="weight" class="input input-bordered w-full" required>
                    </div>
                    <div>
                        <label class="label">Atribut</label>
                        <select name="attribute" id="attribute" class="select select-bordered w-full" disabled>
                            <option value="">-- Pilih --</option>
                            <option value="benefit">Benefit</option>
                            <option value="cost">Cost</option>
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

    <?php include('templates/logoutModal.php'); ?>
</body>
<script>
    function showLoading() {
        document.getElementById('loading-overlay').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loading-overlay').classList.add('hidden');
    }

    function handleEdit(id) {
        showLoading();
        fetch(`../api/get-criteria-by-id.php?id=${id}`)
            .then(response => {
                if (!response.ok) throw new Error('Gagal fetch data');
                return response.json();
            })
            .then(res => {
                if (res.success) {
                    hideLoading();
                    const data = res.data;

                    document.getElementById('id').value = data.id;
                    document.getElementById('code').value = data.code || '';
                    document.getElementById('name').value = data.name || '';
                    document.getElementById('weight').value = data.weight || '';
                    document.getElementById('attribute').value = data.attribute || '';

                    document.getElementById('modalTitle').textContent = 'Edit Data Kriteria';
                    document.getElementById('modalTambahData').showModal();
                } else {
                    alert(res.message || 'Data tidak ditemukan');
                }
            })
            .catch(error => {
                alert('Gagal mengambil data');
                console.error(error);
            })
            .finally(() => {
                hideLoading();
            });
    }

    function handleSubmit() {
        const form = document.getElementById('formCriterias');
        const formData = new FormData(form);

        showLoading();
        fetch('../api/add-criteria.php', {
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
            });
    };
</script>

</html>