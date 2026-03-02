<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Data Dashboard</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-head">
                <h1>Internship Hub</h1>
                <div class="sidebar-user">
                    <div class="sidebar-avatar">IH</div>
                    <div>
                        <p>Dashboard</p>
                        <small>Internship manager</small>
                    </div>
                </div>
            </div>

            <p class="nav-section-title">MENU</p>
            <button class="nav-btn active" data-page="students"><span class="nav-ico">S</span><span class="nav-label">Students</span></button>
            <button class="nav-btn" data-page="companies"><span class="nav-ico">H</span><span class="nav-label">School</span></button>
            <button class="nav-btn" data-page="attendance"><span class="nav-ico">A</span><span class="nav-label">Attendance</span></button>
            <button class="nav-btn" data-page="users"><span class="nav-ico">U</span><span class="nav-label">Users</span></button>
            <button class="nav-btn" data-page="companyHistory"><span class="nav-ico">C</span><span class="nav-label">Companies History</span></button>

            <p class="nav-section-title">GENERAL</p>
            <button class="nav-btn" data-page="userView"><span class="nav-ico">P</span><span class="nav-label">Daily Attendance</span></button>
            <button class="nav-btn" data-page="login"><span class="nav-ico">L</span><span class="nav-label">Login</span></button>
            <button class="nav-btn" id="sidebarLogoutBtn" type="button"><span class="nav-ico">O</span><span class="nav-label">Logout</span></button>
        </aside>

        <main class="main-content">
            <section class="stats-grid" id="statsGrid">
                <article class="stat-card">
                    <p>Total Students</p>
                    <h2 id="statTotal">0</h2>
                </article>
                <article class="stat-card">
                    <p>Active</p>
                    <h2 id="statActive">0</h2>
                </article>
                <article class="stat-card">
                    <p>Completed</p>
                    <h2 id="statCompleted">0</h2>
                </article>
                <article class="stat-card">
                    <p>Schools</p>
                    <h2 id="statSchools">0</h2>
                </article>
            </section>

            <section id="studentsPage">
                <div class="toolbar">
                    <input id="searchInput" type="text" placeholder="Search name, NIS, school, major...">
                    <select id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                    </select>
                    <select id="majorFilter">
                        <option value="">All Majors</option>
                    </select>
                    <button class="primary-btn" id="addStudentBtn">+ Add Student</button>
                </div>
                <div class="card-grid" id="studentCards"></div>
            </section>

            <section id="companiesPage" class="hidden">
                <div class="toolbar">
                    <div></div>
                    <button class="primary-btn" id="addCompanyBtn">+ Add School</button>
                </div>
                <div class="card-grid company-grid" id="companyCards"></div>
            </section>

            <section id="attendancePage" class="hidden">
                <article class="stat-card">
                    <h2>Attendance Reports</h2>
                    <div class="toolbar">
                        <input id="attendanceStartDate" type="date">
                        <input id="attendanceEndDate" type="date">
                        <div></div>
                        <button class="primary-btn" id="attendanceFilterBtn">Apply Filter</button>
                    </div>
                    <div class="stats-grid" id="attendanceStatsGrid">
                        <article class="stat-card"><p>Present</p><h2 id="attPresent">0</h2></article>
                        <article class="stat-card"><p>Sick</p><h2 id="attSick">0</h2></article>
                        <article class="stat-card"><p>Permission</p><h2 id="attPermission">0</h2></article>
                        <article class="stat-card"><p>Absent</p><h2 id="attAbsent">0</h2></article>
                    </div>
                    <div id="attendanceReportList"></div>
                </article>
            </section>

            <section id="companyHistoryPage" class="hidden">
                <article class="stat-card">
                    <h2>Companies History</h2>
                    <p style="margin-top: 4px; color: var(--muted);">Historical information about internship companies.</p>
                    <article class="history-item history-featured">
                        <h4>Sejarah RSUD R.T. Notopuro Sidoarjo</h4>
                        <p>RSUD R.T. Notopuro Sidoarjo awalnya didirikan pada 17 Agustus 1956 dengan nama Rumah Sakit Umum Daerah Kabupaten Dati II Sidoarjo. Pada awal berdirinya, rumah sakit ini berlokasi di Jalan Dr. Soetomo dengan fasilitas yang masih sangat sederhana.</p>
                        <p>Seiring meningkatnya jumlah pasien dan kebutuhan pelayanan kesehatan, pada tahun 1972 rumah sakit dipindahkan ke lokasi baru di Jalan Mojopahit No. 667 Sidoarjo. Setelah pindah, rumah sakit mengalami perkembangan pesat. Pada tahun 1978, statusnya meningkat dari Tipe D menjadi Tipe C, dengan penambahan layanan dokter spesialis dan fasilitas pendukung. Pada tahun 1980, kapasitas tempat tidur rawat inap telah mencapai sekitar 221 tempat tidur.</p>
                        <p><strong>Perkembangan penting:</strong></p>
                        <p>- 1997: ditetapkan sebagai Rumah Sakit Tipe B Non-Pendidikan.</p>
                        <p>- 1999: berubah menjadi Unit Swadana.</p>
                        <p>- 2008: resmi menjadi Badan Layanan Umum Daerah (BLUD).</p>
                        <p>- 2013: meningkat menjadi Rumah Sakit Tipe B Pendidikan.</p>
                        <p>Rumah sakit ini juga berhasil meraih berbagai akreditasi, hingga akhirnya memperoleh Akreditasi Paripurna dan meningkat menjadi Rumah Sakit Tipe A, yang merupakan kelas tertinggi untuk rumah sakit pemerintah di Indonesia.</p>
                        <p>Pada 8 Maret 2024, RSUD Sidoarjo resmi berganti nama menjadi RSUD R.T. Notopuro Sidoarjo. Nama tersebut diambil dari Raden Tumenggung Notopuro, Bupati pertama Sidoarjo, sebagai bentuk penghormatan atas jasa dan sejarah beliau bagi Kabupaten Sidoarjo.</p>
                        <p>Saat ini, RSUD R.T. Notopuro Sidoarjo berstatus sebagai Rumah Sakit Tipe A Pendidikan dengan sistem BLUD, serta menjadi salah satu rumah sakit rujukan utama di wilayah Sidoarjo dan sekitarnya, dengan pelayanan medis lengkap dan layanan darurat 24 jam.</p>
                    </article>
                    <div id="companyHistoryList" class="history-grid"></div>
                </article>
            </section>

            <section id="usersPage" class="hidden">
                <article class="stat-card">
                    <div class="toolbar">
                        <input id="userSearchInput" type="text" placeholder="Search email or role...">
                        <div></div>
                        <div></div>
                        <button class="primary-btn" id="addUserBtn">+ Add User</button>
                    </div>
                    <div id="userList"></div>
                </article>
            </section>

            <section id="userViewPage" class="hidden">
                <div class="user-mobile-shell">
                    <div class="user-top-grid">
                        <div id="myStudentData"></div>
                        <article class="stat-card">
                            <div class="attendance-panel">
                                <div class="attendance-head">
                                    <h2>Daily Attendance</h2>
                                    <p id="attendanceStatusText">You have not submitted attendance today.</p>
                                </div>
                                <div class="attendance-steps">
                                    <span class="active">Basic Information</span>
                                </div>
                                <form id="attendanceForm" class="attendance-form">
                                    <div class="attendance-grid">
                                        <label>
                                            <span>Date</span>
                                            <input type="date" id="attendanceDateDisplay" disabled>
                                        </label>
                                        <label>
                                            <span>Notes</span>
                                            <input name="note" placeholder="Optional note">
                                        </label>
                                    </div>

                                    <div class="attendance-status-group">
                                        <span>Attendance Status</span>
                                        <div class="status-options">
                                            <label><input type="radio" name="status" value="Present" checked> Present</label>
                                            <label><input type="radio" name="status" value="Sick"> Sick</label>
                                            <label><input type="radio" name="status" value="Permission"> Permission</label>
                                            <label><input type="radio" name="status" value="Absent"> Absent</label>
                                        </div>
                                    </div>

                                    <div class="attendance-actions">
                                        <button class="ghost-btn" type="button" onclick="this.form.reset()">Cancel</button>
                                        <button class="primary-btn" type="submit" id="checkInBtn">Submit Attendance</button>
                                    </div>
                                </form>
                            </div>
                        </article>
                    </div>
                    <article class="stat-card">
                        <h2>Attendance History</h2>
                        <div id="attendanceHistory"></div>
                    </article>
                </div>
            </section>

            <section id="loginPage" class="hidden">
                <div class="login-shell">
                    <div class="login-topbar">
                        <span class="brand">Internship Hub</span>
                        <nav>
                            <span>Home</span>
                            <span>About</span>
                            <span>Services</span>
                        </nav>
                    </div>
                    <div class="login-layout">
                        <div class="login-art">
                            <div class="art-phone">
                                <div class="art-avatar">🔒</div>
                                <div class="art-line"></div>
                                <div class="art-line short"></div>
                                <div class="art-dot"></div>
                            </div>
                        </div>
                        <article class="login-card">
                            <p class="back-link">← Back to accounts</p>
                            <h3>User Login</h3>
                            <p class="login-subtitle">Sign in to continue to Internship Hub</p>
                            <p id="authStatusText">Not logged in</p>

                            <form id="loginForm" class="login-form">
                                <label for="loginUsername">Username</label>
                                <input id="loginUsername" name="username" placeholder="ayusabrina@pkl.com" value="ayusabrina@pkl.com" required>

                                <label for="loginPassword">Password</label>
                                <input id="loginPassword" name="password" type="password" placeholder="Enter your password" required>

                                <div class="login-meta">
                                    <label><input type="checkbox"> Remember</label>
                                    <span>Forgot password?</span>
                                </div>

                                <button class="primary-btn login-submit" type="submit">Login</button>
                                <button class="ghost-btn login-logout" type="button" id="logoutBtn">Logout</button>
                            </form>

                            <p class="login-hint">Default: <strong>ayusabrina@pkl.com/user123</strong></p>
                        </article>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <dialog id="formModal" class="modal">
        <form method="dialog" id="modalForm" class="modal-body"></form>
    </dialog>

    <dialog id="confirmModal" class="modal">
        <div class="modal-body">
            <h3>Confirm Deletion</h3>
            <p id="confirmText">Are you sure?</p>
            <div class="modal-actions">
                <button id="cancelDeleteBtn" type="button" class="ghost-btn">Cancel</button>
                <button id="confirmDeleteBtn" type="button" class="danger-btn">Delete</button>
            </div>
        </div>
    </dialog>

    <div id="toastContainer" class="toast-container"></div>
    <script src="assets/app.js"></script>
</body>
</html>
