<div class="navbar bg-white shadow-sm">
    <!-- START: Logo dan Menu Dropdown Mobile -->
    <div class="navbar-start">
        <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </div>
            <ul tabindex="0"
                class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="alternatives.php">Alternatif</a></li>
                <li><a href="criterias.php">Kriteria</a></li>
                <li><a href="rangkings.php">Peringkat</a></li>
                <li><a onclick="document.getElementById('my_modal_8').showModal()">Log Out</a></li>
            </ul>
        </div>
        <a href="#" class="btn btn-ghost text-l">SPK Dosen dan Tenaga Pendidikan</a>
    </div>

    <!-- CENTER: Menu Desktop -->
    <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal px-1">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="alternatives.php">Alternatif</a></li>
            <li><a href="criterias.php">Kriteria</a></li>
            <li><a href="rangkings.php">Peringkat</a></li>
        </ul>
    </div>

    <!-- END: Tombol Logout -->
    <div class="navbar-end hidden lg:flex">
        <button onclick="document.getElementById('my_modal_8').showModal()" class="btn btn-sm btn-outline">
            Log Out
        </button>
    </div>
</div>