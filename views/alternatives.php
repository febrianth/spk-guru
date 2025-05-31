<?php
require_once '../api/auth-check.php';
require_once '../config/database.php';

$conn = Database::getConnection(); // Inisialisasi koneksi

$query = "SELECT * FROM alternatives WHERE deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute();

// Simpan data ke variabel array
$alternatives = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tbody = '';
$action_buttons = '';
if (!empty($alternatives)) {
    foreach ($alternatives as $index => $v) {
        $action_buttons = '<button class="btn btn-warning mr-2" title="edit data" onclick="handleEdit(' . $v['id'] . ')"><i class="fas fa-pen"></i></button>';
        $action_buttons .= '<button class="btn btn-error" title="hapus data"><i class="fas fa-trash" onclick="handleDelete(' . $v['id'] . ')"></i></button>';
        // Mapping
        $position = ($v['position'] == 'dosen') ? '<span>Dosen</span>' : '<span>Tenaga Pendidikan</span>';
        $type = ($v['type'] == 'struktural') ? '<span>Struktural</span>' : '<span>Non Struktural</span>';
        $tbody .= '
            <tr class="hover:bg-base-300">
                <td>' . $index + 1 . '</td>
                <td>' . htmlspecialchars($v['nip']) . '</td>
                <td>' . htmlspecialchars($v['name']) . '</td>
                <td>' . $position . '</td>
                <td>' . $type . '</td>
                <td>' . $action_buttons . '</td>
            </tr>
        ';
    }
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
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.css" />
    <script src="https://cdn.datatables.net/2.3.1/js/dataTables.js"></script>
</head>

<body>
    <?php include('templates/header.php'); ?>
    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Alternatif</h1>
        </div>
    </header>
    <main>
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <button class="btn btn-neutral mb-4" onclick="handleAdd()" title="Tambah Data Alternatif"><i class="fas fa-plus"></i>Tambah Data</button>
            <div class="overflow-x-auto">
                <table class="table" id="dataTables">
                    <thead>
                        <tr>
                            <th style="text-align:left;">No</th>
                            <th style="text-align:left;">NIP</th>
                            <th style="text-align:left;">Nama</th>
                            <th style="text-align:left;">Posisi</th>
                            <th style="text-align:left;">Tipe</th>
                            <th style="text-align:left;">Aksi</th>
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
            <h3 class="text-lg font-bold mb-4" id="modalTitle">Tambah Data Alternatif</h3>
            <form id="formAlternative">
                <input type="hidden" name="id" id="id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Nama</label>
                        <input type="text" name="name" id="name" class="input input-bordered w-full" required>
                    </div>
                    <div>
                        <label class="label">NIP</label>
                        <input type="text" name="nip" id="nip" class="input input-bordered w-full" required>
                    </div>
                    <div>
                        <label class="label">Jabatan (Position)</label>
                        <select name="position" id="position" class="select select-bordered w-full" required>
                            <option value="">-- Pilih --</option>
                            <option value="dosen">Dosen</option>
                            <option value="tenaga_pendidikan">Tenaga Pendidikan</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tipe</label>
                        <select name="type" id="type" class="select select-bordered w-full" required>
                            <option value="">-- Pilih --</option>
                            <option value="struktural">Struktural</option>
                            <option value="nonstruktural">Non Struktural</option>
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
    let table = new DataTable('#dataTables');

    function handleAdd() {
        document.getElementById('formAlternative').reset();
        document.getElementById('id').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Data Alternatif';
        document.getElementById('modalTambahData').showModal();
    }

    function showLoading() {
        document.getElementById('loading-overlay').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loading-overlay').classList.add('hidden');
    }

    function handleEdit(id) {
        showLoading();

        fetch(`../api/get-alternative-by-id.php?id=${id}`)
            .then(response => {
                if (!response.ok) throw new Error('Gagal fetch data');
                return response.json();
            })
            .then(res => {
                if (res.success) {
                    hideLoading();
                    const data = res.data;

                    document.getElementById('id').value = data.id;
                    document.getElementById('name').value = data.name || '';
                    document.getElementById('nip').value = data.nip || '';
                    document.getElementById('position').value = data.position || '';
                    document.getElementById('type').value = data.type || '';

                    document.getElementById('modalTitle').textContent = 'Edit Data Alternatif';
                    document.getElementById('modalTambahData').showModal();
                } else {
                    alert(res.message || 'Data tidak ditemukan');
                }
            })
            .catch(error => {
                alert('Gagal mengambil data alternatif');
                console.error(error);
            })
            .finally(() => {
                loadingOverlay.style.display = 'none';
            });
    }

    function handleSubmit() {
        const form = document.getElementById('formAlternative');
        const formData = new FormData(form);

        showLoading();
        fetch('../api/add-alternative.php', {
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

    function handleDelete(id) {
        if (!confirm('Apakah kamu yakin ingin menghapus data ini?')) return;

        showLoading();

        const formData = new FormData();
        formData.append('id', id);

        fetch('../api/delete-alternative-by-id.php', {
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