const state = {
  students: [],
  companies: [],
  users: [],
  myStudent: null,
  attendance: { today: null, history: [], stats: null },
  attendanceFilters: { startDate: "", endDate: "" },
  userFilters: { search: "" },
  auth: { loggedIn: false, id: null, username: null, role: null },
  filters: { search: "", status: "", major: "" },
  pendingDelete: null,
};

const els = {
  statsGrid: document.getElementById("statsGrid"),
  studentsPage: document.getElementById("studentsPage"),
  companiesPage: document.getElementById("companiesPage"),
  attendancePage: document.getElementById("attendancePage"),
  companyHistoryPage: document.getElementById("companyHistoryPage"),
  usersPage: document.getElementById("usersPage"),
  userViewPage: document.getElementById("userViewPage"),
  loginPage: document.getElementById("loginPage"),
  studentCards: document.getElementById("studentCards"),
  companyCards: document.getElementById("companyCards"),
  myStudentData: document.getElementById("myStudentData"),
  attendanceStatusText: document.getElementById("attendanceStatusText"),
  attendanceHistory: document.getElementById("attendanceHistory"),
  attendanceReportList: document.getElementById("attendanceReportList"),
  companyHistoryList: document.getElementById("companyHistoryList"),
  attendanceStartDate: document.getElementById("attendanceStartDate"),
  attendanceEndDate: document.getElementById("attendanceEndDate"),
  attendanceFilterBtn: document.getElementById("attendanceFilterBtn"),
  userSearchInput: document.getElementById("userSearchInput"),
  addUserBtn: document.getElementById("addUserBtn"),
  userList: document.getElementById("userList"),
  attendanceForm: document.getElementById("attendanceForm"),
  attendanceDateDisplay: document.getElementById("attendanceDateDisplay"),
  checkInBtn: document.getElementById("checkInBtn"),
  attPresent: document.getElementById("attPresent"),
  attSick: document.getElementById("attSick"),
  attPermission: document.getElementById("attPermission"),
  attAbsent: document.getElementById("attAbsent"),
  searchInput: document.getElementById("searchInput"),
  statusFilter: document.getElementById("statusFilter"),
  majorFilter: document.getElementById("majorFilter"),
  addStudentBtn: document.getElementById("addStudentBtn"),
  addCompanyBtn: document.getElementById("addCompanyBtn"),
  loginForm: document.getElementById("loginForm"),
  logoutBtn: document.getElementById("logoutBtn"),
  authStatusText: document.getElementById("authStatusText"),
  formModal: document.getElementById("formModal"),
  modalForm: document.getElementById("modalForm"),
  confirmModal: document.getElementById("confirmModal"),
  confirmText: document.getElementById("confirmText"),
  confirmDeleteBtn: document.getElementById("confirmDeleteBtn"),
  cancelDeleteBtn: document.getElementById("cancelDeleteBtn"),
  toastContainer: document.getElementById("toastContainer"),
  statTotal: document.getElementById("statTotal"),
  statActive: document.getElementById("statActive"),
  statCompleted: document.getElementById("statCompleted"),
  statSchools: document.getElementById("statSchools"),
};

const navButtons = Array.from(document.querySelectorAll(".nav-btn[data-page]"));
const sidebarLogoutBtn = document.getElementById("sidebarLogoutBtn");
const palette = ["#99f6e4", "#bfdbfe", "#c4b5fd", "#93c5fd", "#a7f3d0", "#ddd6fe"];

bootstrap();

async function bootstrap() {
  bindEvents();
  els.attendanceDateDisplay.value = todayYmd();
  try {
    await refreshAuth();
    await loadDataForRole();
  } catch (error) {
    toast(error.message || "Failed to initialize app", "error");
    lockToLoginView();
  }
}

function bindEvents() {
  navButtons.forEach((btn) => btn.addEventListener("click", () => switchPage(btn.dataset.page)));
  if (sidebarLogoutBtn) {
    sidebarLogoutBtn.addEventListener("click", handleLogoutClick);
  }
  els.searchInput.addEventListener("input", debounce((event) => {
    state.filters.search = event.target.value.trim();
    refreshStudents();
  }, 250));
  els.statusFilter.addEventListener("change", (event) => {
    state.filters.status = event.target.value;
    refreshStudents();
  });
  els.majorFilter.addEventListener("change", (event) => {
    state.filters.major = event.target.value;
    refreshStudents();
  });

  els.addStudentBtn.addEventListener("click", () => openStudentModal());
  els.addCompanyBtn.addEventListener("click", () => openCompanyModal());
  els.cancelDeleteBtn.addEventListener("click", () => els.confirmModal.close());
  els.confirmDeleteBtn.addEventListener("click", handleDeleteConfirmed);
  els.loginForm.addEventListener("submit", handleLoginSubmit);
  els.logoutBtn.addEventListener("click", handleLogoutClick);
  els.attendanceForm.addEventListener("submit", handleAttendanceSubmit);
  els.attendanceFilterBtn.addEventListener("click", () => {
    state.attendanceFilters.startDate = els.attendanceStartDate.value;
    state.attendanceFilters.endDate = els.attendanceEndDate.value;
    refreshAttendance();
  });
  els.userSearchInput.addEventListener("input", debounce((event) => {
    state.userFilters.search = event.target.value.trim();
    refreshUsers();
  }, 250));
  els.addUserBtn.addEventListener("click", () => openUserModal());
}

function switchPage(page) {
  if (!state.auth.loggedIn && page !== "login") return switchPage("login");
  if (state.auth.role === "user" && page !== "userView" && page !== "companyHistory" && page !== "login") return switchPage("userView");

  navButtons.forEach((btn) => btn.classList.toggle("active", btn.dataset.page === page));
  els.studentsPage.classList.toggle("hidden", page !== "students");
  els.companiesPage.classList.toggle("hidden", page !== "companies");
  els.attendancePage.classList.toggle("hidden", page !== "attendance");
  els.companyHistoryPage.classList.toggle("hidden", page !== "companyHistory");
  els.usersPage.classList.toggle("hidden", page !== "users");
  els.userViewPage.classList.toggle("hidden", page !== "userView");
  els.loginPage.classList.toggle("hidden", page !== "login");
}

function lockToLoginView() {
  document.body.classList.add("auth-only");
  navButtons.forEach((btn) => {
    const isLogin = btn.dataset.page === "login";
    btn.classList.toggle("hidden", !isLogin);
    btn.classList.toggle("active", isLogin);
  });
  if (sidebarLogoutBtn) {
    sidebarLogoutBtn.classList.add("hidden");
  }
  els.statsGrid.classList.add("hidden");
  switchPage("login");
  els.authStatusText.textContent = "Please login to continue.";
}

function applyRoleUi() {
  const { role, loggedIn } = state.auth;
  const isAdmin = role === "admin";
  const isUser = role === "user";

  navButtons.forEach((btn) => {
    const page = btn.dataset.page;
    let show = loggedIn;
    if (!loggedIn) show = page === "login";
    if (isAdmin) show = page === "students" || page === "companies" || page === "attendance" || page === "users" || page === "companyHistory" || page === "login";
    if (isUser) show = page === "userView" || page === "companyHistory" || page === "login";
    btn.classList.toggle("hidden", !show);
  });
  if (sidebarLogoutBtn) {
    sidebarLogoutBtn.classList.toggle("hidden", !loggedIn);
  }

  els.addStudentBtn.classList.toggle("hidden", !isAdmin);
  els.addCompanyBtn.classList.toggle("hidden", !isAdmin);
  els.addUserBtn.classList.toggle("hidden", !isAdmin);
  els.statsGrid.classList.toggle("hidden", !isAdmin);
  els.attendanceForm.classList.toggle("hidden", !isUser);
  els.attendanceFilterBtn.classList.toggle("hidden", !isAdmin);

  if (!loggedIn) {
    document.body.classList.add("auth-only");
    els.authStatusText.textContent = "Not logged in";
    return;
  }
  document.body.classList.remove("auth-only");
  els.authStatusText.textContent = `Logged in as ${state.auth.username} (${state.auth.role})`;
}

async function loadDataForRole() {
  applyRoleUi();
  if (!state.auth.loggedIn) return lockToLoginView();

  if (state.auth.role === "admin") {
    await Promise.all([refreshStudents(), refreshCompanies(), refreshUsers(), refreshStats(), refreshAttendance()]);
    switchPage("students");
    return;
  }

  await Promise.all([refreshMyStudent(), refreshAttendance(), refreshCompanies()]);
  switchPage("userView");
}

async function refreshAuth() {
  state.auth = await fetchJson("api.php?entity=auth");
}

async function refreshStudents() {
  const query = new URLSearchParams({
    entity: "students",
    search: state.filters.search,
    status: state.filters.status,
    major: state.filters.major,
  });
  state.students = await fetchJson(`api.php?${query.toString()}`);
  renderMajorFilterOptions(state.students, els.majorFilter, state.filters.major);
  renderStudents();
}

async function refreshCompanies() {
  state.companies = await fetchJson("api.php?entity=companies");
  renderCompanies();
  renderCompanyHistory();
}

async function refreshMyStudent() {
  const payload = await fetchJson("api.php?entity=myStudent");
  state.myStudent = payload.student || null;
  renderMyStudent();
}

async function refreshUsers() {
  const query = new URLSearchParams({ entity: "users", search: state.userFilters.search });
  state.users = await fetchJson(`api.php?${query.toString()}`);
  renderUsers();
}

async function refreshStats() {
  const stats = await fetchJson("api.php?entity=stats");
  els.statTotal.textContent = stats.total;
  els.statActive.textContent = stats.active;
  els.statCompleted.textContent = stats.completed;
  els.statSchools.textContent = stats.schools;
}

async function refreshAttendance() {
  const query = new URLSearchParams({ entity: "attendance" });
  if (state.attendanceFilters.startDate) query.set("start_date", state.attendanceFilters.startDate);
  if (state.attendanceFilters.endDate) query.set("end_date", state.attendanceFilters.endDate);
  state.attendance = await fetchJson(`api.php?${query.toString()}`);
  renderAttendance();
  if (state.auth.role === "admin" && state.students.length > 0) {
    renderStudents();
  }
  if (state.auth.role === "user" && state.myStudent) {
    renderMyStudent();
  }
}

function renderMyStudent() {
  if (!state.myStudent) {
    els.myStudentData.innerHTML = `
      <article class="user-progress-card">
        <div class="user-progress-head">
          <div class="user-photo">?</div>
          <div>
            <h3>My Internship Progress</h3>
            <p>Profile not linked yet</p>
          </div>
        </div>
        <div class="user-progress-strip">
          <span>D-0</span>
          <span>Waiting</span>
        </div>
        <div class="progress-wrap">
          <div class="progress-head">
            <span>Start date → End date</span>
            <span>0%</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" style="width:0%"></div>
          </div>
        </div>
        <div class="countdown-grid">
          <div class="count-box"><b>0</b><small>Months</small></div>
          <div class="count-box"><b>0</b><small>Days</small></div>
          <div class="count-box"><b>0%</b><small>Remaining</small></div>
          <div class="count-box"><b>Waiting</b><small>Status</small></div>
        </div>
      </article>
      <p style="margin-top:10px;">No student profile linked to your account yet. Ask admin to assign your email in student data.</p>
    `;
    return;
  }
  els.myStudentData.innerHTML = buildUserDashboard(state.myStudent);
}

function renderAttendance() {
  if (state.auth.role === "admin") {
    const stats = state.attendance.stats || { Present: 0, Sick: 0, Permission: 0, Absent: 0 };
    els.attPresent.textContent = stats.Present;
    els.attSick.textContent = stats.Sick;
    els.attPermission.textContent = stats.Permission;
    els.attAbsent.textContent = stats.Absent;

    if (!state.attendance.history.length) {
      els.attendanceReportList.innerHTML = "<p>No attendance data in selected period.</p>";
      return;
    }
    els.attendanceReportList.innerHTML = state.attendance.history.map((row) => `
      <div class="detail-list">
        <div><span>${escapeHtml(row.attendance_date)}</span><strong>${escapeHtml(row.username)}</strong></div>
        <div><span>Status</span><strong>${escapeHtml(row.status)}</strong></div>
        <div><span>Check In</span><strong>${new Date(row.check_in_at.replace(" ", "T")).toLocaleString()}</strong></div>
      </div>
    `).join("");
    return;
  }

  els.attendanceReportList.innerHTML = "";
  els.attendanceStatusText.textContent = state.attendance.today
    ? `Already submitted today (${state.attendance.today.status})`
    : "You have not submitted attendance today.";
  els.checkInBtn.disabled = Boolean(state.attendance.today);
  els.checkInBtn.textContent = state.attendance.today ? "Already Submitted" : "Submit Attendance";

  if (!state.attendance.history.length) {
    els.attendanceHistory.innerHTML = "<p>No attendance history yet.</p>";
    return;
  }
  els.attendanceHistory.innerHTML = state.attendance.history.map((row) => `
    <div class="detail-list">
      <div><span>${escapeHtml(row.attendance_date)}</span><strong>${escapeHtml(row.status)}</strong></div>
      <div><span>Check In</span><strong>${new Date(row.check_in_at.replace(" ", "T")).toLocaleString()}</strong></div>
      <div><span>Note</span><strong>${escapeHtml(row.note || "-")}</strong></div>
    </div>
  `).join("");
}

function renderMajorFilterOptions(students, selectEl, selectedValue) {
  const majors = Array.from(new Set(students.map((s) => s.major))).sort();
  selectEl.innerHTML = '<option value="">All Majors</option>';
  majors.forEach((major) => {
    const option = document.createElement("option");
    option.value = major;
    option.textContent = major;
    if (major === selectedValue) option.selected = true;
    selectEl.appendChild(option);
  });
}

function renderStudents() {
  if (!state.students.length) {
    els.studentCards.innerHTML = "<p>No students found.</p>";
    return;
  }
  els.studentCards.innerHTML = state.students.map((student) => buildStudentCard(student, false)).join("");
}

function buildStudentCard(student, readOnly) {
  const color = colorByText(student.name);
  const initials = getInitials(student.name);
  const progress = internshipProgress(student.internship_start_date, student.internship_end_date);
  const attendancePct = getAttendancePercentagesByUsername(student.user_username, state.attendance.history);
  const effectiveStatus = progress.percent >= 100 ? "Completed" : student.status;
  const dischargeTag = effectiveStatus === "Completed"
    ? `<span class="discharge-tag">Discharge • ${formatDate(student.internship_end_date)}</span>` : "";
  const statusClass = effectiveStatus === "Active" ? "active" : "completed";
  const progressClass = progress.percent >= 95 ? "danger" : progress.percent >= 80 ? "warning" : "";
  const actions = readOnly ? "" : `
    <div class="card-actions">
      <button class="small-btn" onclick="openStudentModal(${student.id})">Edit</button>
      <button class="small-btn delete" onclick="requestDelete('student', ${student.id}, '${escapeJs(student.name)}')">Delete</button>
    </div>
  `;

  return `
    <article class="student-card admin-student-card">
      <div class="card-banner">${dischargeTag}</div>
      <div class="card-content">
        <div class="identity">
          <div class="avatar" style="background:${color}">${initials}</div>
          <div>
            <h3>${escapeHtml(student.name)}</h3>
            <p>NIS ${escapeHtml(student.nis)}</p>
          </div>
          <span class="status-badge ${statusClass}">${escapeHtml(effectiveStatus)}</span>
        </div>
        <div class="admin-progress-strip">
          <span>D-${progress.remainingDays}</span>
          <span>${progress.phase}</span>
        </div>
        <div class="detail-list">
          <div><span>Phone</span><strong>${escapeHtml(student.phone)}</strong></div>
          <div><span>School</span><strong>${escapeHtml(student.school_origin)}</strong></div>
          <div><span>Major</span><strong>${escapeHtml(student.major)}</strong></div>
          <div><span>Company</span><strong>${escapeHtml(student.company_name || "-")}</strong></div>
        </div>
        <div class="progress-wrap">
          <div class="progress-head">
            <span>${formatDate(student.internship_start_date)} → ${formatDate(student.internship_end_date)}</span>
            <span>${progress.percent}%</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill ${progressClass}" style="width:${progress.percent}%"></div>
          </div>
        </div>
        <div class="countdown-grid">
          <div class="count-box"><b>${attendancePct.present}%</b><small>Attendance Percentage</small></div>
          <div class="count-box"><b>${attendancePct.sick}%</b><small>Sick Percentage</small></div>
          <div class="count-box"><b>${attendancePct.permission}%</b><small>Permission Percentage</small></div>
          <div class="count-box"><b>${attendancePct.absent}%</b><small>Absent Percentage</small></div>
        </div>
        ${actions}
      </div>
    </article>
  `;
}

function renderCompanies() {
  if (!state.companies.length) {
    els.companyCards.innerHTML = "<p>No companies found.</p>";
    return;
  }
  els.companyCards.innerHTML = state.companies.map((company) => {
    const color = colorByText(company.name);
    const initials = getInitials(company.name);
    return `
      <article class="company-card">
        <div class="card-content">
          <div class="identity">
            <div class="avatar" style="background:${color}">${initials}</div>
            <div>
              <h3>${escapeHtml(company.name)}</h3>
              <p>Supervisor ${escapeHtml(company.supervisor_name)}</p>
            </div>
          </div>
          <div class="detail-list">
            <div><span>Address</span><strong>${escapeHtml(company.address)}</strong></div>
            <div><span>Assigned Students</span><strong>${escapeHtml(company.student_count || 0)}</strong></div>
          </div>
          <div class="card-actions">
            <button class="small-btn" onclick="openCompanyModal(${company.id})">Edit</button>
            <button class="small-btn delete" onclick="requestDelete('school', ${company.id}, '${escapeJs(company.name)}')">Delete</button>
          </div>
        </div>
      </article>
    `;
  }).join("");
}

function renderCompanyHistory() {
  if (!state.companies.length) {
    els.companyHistoryList.innerHTML = "<p>No companies history found.</p>";
    return;
  }

  els.companyHistoryList.innerHTML = state.companies.map((company) => `
    <article class="history-item">
      <h4>${escapeHtml(company.name)}</h4>
      <p><strong>Address:</strong> ${escapeHtml(company.address)}</p>
      <p><strong>Supervisor:</strong> ${escapeHtml(company.supervisor_name)}</p>
      <p><strong>Assigned Students:</strong> ${escapeHtml(company.student_count || 0)}</p>
      <p><strong>Added:</strong> ${formatDateTime(company.created_at)}</p>
    </article>
  `).join("");
}

function renderUsers() {
  if (!state.users.length) {
    els.userList.innerHTML = "<p>No user accounts found.</p>";
    return;
  }
  els.userList.innerHTML = state.users.map((user) => `
    <div class="detail-list">
      <div><span>Email</span><strong>${escapeHtml(user.username)}</strong></div>
      <div><span>Role</span><strong>${escapeHtml(user.role)}</strong></div>
      <div><span>Linked Students</span><strong>${escapeHtml(user.linked_students)}</strong></div>
      <div><span>Actions</span><strong>
        <button class="small-btn" onclick="openUserModal(${user.id})">Edit</button>
        <button class="small-btn delete" onclick="requestDelete('user', ${user.id}, '${escapeJs(user.username)}')">Delete</button>
      </strong></div>
    </div>
  `).join("");
}

function buildUserDashboard(student) {
  const progress = internshipProgress(student.internship_start_date, student.internship_end_date);
  const progressClass = progress.percent >= 95 ? "danger" : progress.percent >= 80 ? "warning" : "";
  const attendancePct = getAttendancePercentages(state.attendance.history);
  return `
    <article class="user-progress-card">
      <div class="user-progress-head">
        <div class="user-photo">${getInitials(student.name)}</div>
        <div>
          <h3>${escapeHtml(student.name)}</h3>
          <p>${escapeHtml(student.major)} • ${escapeHtml(student.school_origin)}</p>
          <p>NIS ${escapeHtml(student.nis)}</p>
        </div>
      </div>
      <div class="user-progress-strip">
        <span>D-${progress.remainingDays}</span>
        <span>${progress.phase}</span>
      </div>
      <div class="progress-wrap">
        <div class="progress-head">
          <span>${formatDate(student.internship_start_date)} → ${formatDate(student.internship_end_date)}</span>
          <span>${progress.percent}%</span>
        </div>
        <div class="progress-track">
          <div class="progress-fill ${progressClass}" style="width:${progress.percent}%"></div>
        </div>
      </div>
      <div class="countdown-grid">
        <div class="count-box"><b>${attendancePct.present}%</b><small>Attendance Percentage</small></div>
        <div class="count-box"><b>${attendancePct.sick}%</b><small>Sick Percentage</small></div>
        <div class="count-box"><b>${attendancePct.permission}%</b><small>Permission Percentage</small></div>
        <div class="count-box"><b>${attendancePct.absent}%</b><small>Absent Percentage</small></div>
      </div>
    </article>
  `;
}

function getAttendancePercentages(history) {
  if (!Array.isArray(history) || history.length === 0) {
    return { present: 0, sick: 0, permission: 0, absent: 0 };
  }

  let present = 0;
  let sick = 0;
  let permission = 0;
  let absent = 0;

  history.forEach((row) => {
    const status = String(row.status || "");
    if (status === "Present") present += 1;
    if (status === "Sick") sick += 1;
    if (status === "Permission") permission += 1;
    if (status === "Absent") absent += 1;
  });

  const total = Math.max(history.length, 1);
  return {
    present: Math.round((present / total) * 100),
    sick: Math.round((sick / total) * 100),
    permission: Math.round((permission / total) * 100),
    absent: Math.round((absent / total) * 100),
  };
}

function getAttendancePercentagesByUsername(username, history) {
  if (!username || !Array.isArray(history) || history.length === 0) {
    return { present: 0, sick: 0, permission: 0, absent: 0 };
  }

  const scoped = history.filter((row) => String(row.username || "").toLowerCase() === String(username).toLowerCase());
  if (scoped.length === 0) {
    return { present: 0, sick: 0, permission: 0, absent: 0 };
  }
  return getAttendancePercentages(scoped);
}

async function handleAttendanceSubmit(event) {
  event.preventDefault();
  const payload = Object.fromEntries(new FormData(els.attendanceForm).entries());
  payload.action = "markAttendance";
  try {
    await fetchJson("api.php", { method: "POST", body: JSON.stringify(payload) });
    toast("Attendance submitted");
    els.attendanceForm.reset();
    els.attendanceDateDisplay.value = todayYmd();
    await refreshAttendance();
  } catch (error) {
    toast(error.message, "error");
  }
}

function openStudentModal(studentId = null) {
  if (state.auth.role !== "admin") return toast("Admin access required", "error");
  const student = studentId ? state.students.find((item) => item.id === studentId) : null;
  const companyOptions = state.companies.map((company) => {
    const selected = student && Number(student.company_id) === Number(company.id) ? "selected" : "";
    return `<option value="${company.id}" ${selected}>${escapeHtml(company.name)}</option>`;
  }).join("");

  els.modalForm.innerHTML = `
    <h3>${student ? "Edit Student" : "Add Student"}</h3>
    <div class="form-grid">
      <input name="name" placeholder="Name" value="${escapeAttr(student?.name || "")}" required>
      <input name="nis" placeholder="Student ID (NIS)" value="${escapeAttr(student?.nis || "")}" required>
      <input name="phone" placeholder="Phone Number" value="${escapeAttr(student?.phone || "")}" required>
      <input name="school_origin" placeholder="School Origin" value="${escapeAttr(student?.school_origin || "")}" required>
      <input name="major" placeholder="Major" value="${escapeAttr(student?.major || "")}" required>
      <input name="user_username" placeholder="Student Account Email (optional)" value="${escapeAttr(student?.user_username || "")}">
      <select name="status" required>
        <option value="Active" ${student?.status === "Active" ? "selected" : ""}>Active</option>
        <option value="Completed" ${student?.status === "Completed" ? "selected" : ""}>Completed</option>
      </select>
      <label>Start Date
        <input type="date" name="internship_start_date" value="${escapeAttr(student?.internship_start_date || "")}" required>
      </label>
      <label>End Date
        <input type="date" name="internship_end_date" value="${escapeAttr(student?.internship_end_date || "")}" required>
      </label>
      <select name="company_id" class="full">
        <option value="">Select Company (Optional)</option>
        ${companyOptions}
      </select>
    </div>
    <div class="modal-actions">
      <button type="button" class="ghost-btn" onclick="closeFormModal()">Cancel</button>
      <button type="submit" class="primary-btn">${student ? "Update" : "Save"}</button>
    </div>
  `;

  els.modalForm.onsubmit = async (event) => {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(els.modalForm).entries());
    payload.action = "saveStudent";
    payload.id = student ? student.id : null;
    payload.company_id = payload.company_id || null;
    try {
      await fetchJson("api.php", { method: "POST", body: JSON.stringify(payload) });
      closeFormModal();
      toast(student ? "Student updated" : "Student created");
      await Promise.all([refreshStudents(), refreshStats()]);
    } catch (error) {
      toast(error.message, "error");
    }
  };
  els.formModal.showModal();
}

function openCompanyModal(companyId = null) {
  if (state.auth.role !== "admin") return toast("Admin access required", "error");
  const company = companyId ? state.companies.find((item) => item.id === companyId) : null;
  els.modalForm.innerHTML = `
    <h3>${company ? "Edit School" : "Add School"}</h3>
    <div class="form-grid">
      <input class="full" name="name" placeholder="School Name" value="${escapeAttr(company?.name || "")}" required>
      <input class="full" name="address" placeholder="Address" value="${escapeAttr(company?.address || "")}" required>
      <input class="full" name="supervisor_name" placeholder="Supervisor Name" value="${escapeAttr(company?.supervisor_name || "")}" required>
    </div>
    <div class="modal-actions">
      <button type="button" class="ghost-btn" onclick="closeFormModal()">Cancel</button>
      <button type="submit" class="primary-btn">${company ? "Update" : "Save"}</button>
    </div>
  `;
  els.modalForm.onsubmit = async (event) => {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(els.modalForm).entries());
    payload.action = "saveCompany";
    payload.id = company ? company.id : null;
    try {
      await fetchJson("api.php", { method: "POST", body: JSON.stringify(payload) });
      closeFormModal();
      toast(company ? "School updated" : "School created");
      await Promise.all([refreshCompanies(), refreshStudents()]);
    } catch (error) {
      toast(error.message, "error");
    }
  };
  els.formModal.showModal();
}

function openUserModal(userId = null) {
  if (state.auth.role !== "admin") return toast("Admin access required", "error");
  const user = userId ? state.users.find((item) => Number(item.id) === Number(userId)) : null;
  els.modalForm.innerHTML = `
    <h3>${user ? "Edit User" : "Add User"}</h3>
    <div class="form-grid">
      <input class="full" name="username" type="text" placeholder="Username or email" value="${escapeAttr(user?.username || "")}" required>
      <select class="full" name="role" required>
        <option value="user" ${user?.role === "user" ? "selected" : ""}>User</option>
        <option value="admin" ${user?.role === "admin" ? "selected" : ""}>Admin</option>
      </select>
      <input class="full" name="password" type="password" placeholder="${user ? "Leave blank to keep current password" : "Password (min 6 chars)"}" ${user ? "" : "required"}>
    </div>
    <div class="modal-actions">
      <button type="button" class="ghost-btn" onclick="closeFormModal()">Cancel</button>
      <button type="submit" class="primary-btn">${user ? "Update" : "Save"}</button>
    </div>
  `;
  els.modalForm.onsubmit = async (event) => {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(els.modalForm).entries());
    payload.action = "saveUser";
    payload.id = user ? user.id : null;
    try {
      await fetchJson("api.php", { method: "POST", body: JSON.stringify(payload) });
      closeFormModal();
      toast(user ? "User updated" : "User created");
      await refreshUsers();
    } catch (error) {
      toast(error.message, "error");
    }
  };
  els.formModal.showModal();
}

function closeFormModal() {
  els.formModal.close();
}

window.openStudentModal = openStudentModal;
window.openCompanyModal = openCompanyModal;
window.openUserModal = openUserModal;
window.closeFormModal = closeFormModal;

function requestDelete(type, id, label) {
  if (state.auth.role !== "admin") return toast("Admin access required", "error");
  state.pendingDelete = { type, id };
  els.confirmText.textContent = `Delete ${type} "${label}"? This cannot be undone.`;
  els.confirmModal.showModal();
}

window.requestDelete = requestDelete;

async function handleDeleteConfirmed() {
  if (!state.pendingDelete) return;
  const { type, id } = state.pendingDelete;
  try {
    if (type === "student") {
      await fetchJson("api.php", { method: "POST", body: JSON.stringify({ action: "deleteStudent", id }) });
      await Promise.all([refreshStudents(), refreshStats()]);
      toast("Student deleted");
    } else if (type === "school") {
      await fetchJson("api.php", { method: "POST", body: JSON.stringify({ action: "deleteCompany", id }) });
      await Promise.all([refreshCompanies(), refreshStudents()]);
      toast("School deleted");
    } else {
      await fetchJson("api.php", { method: "POST", body: JSON.stringify({ action: "deleteUser", id }) });
      await refreshUsers();
      toast("User deleted");
    }
  } catch (error) {
    toast(error.message, "error");
  } finally {
    state.pendingDelete = null;
    els.confirmModal.close();
  }
}

async function handleLoginSubmit(event) {
  event.preventDefault();
  const payload = Object.fromEntries(new FormData(els.loginForm).entries());
  payload.action = "login";
  try {
    state.auth = await fetchJson("api.php", { method: "POST", body: JSON.stringify(payload) });
    toast("Login successful");
    await loadDataForRole();
  } catch (error) {
    toast(error.message, "error");
  }
}

async function handleLogoutClick() {
  try {
    await fetchJson("api.php", { method: "POST", body: JSON.stringify({ action: "logout" }) });
    state.auth = { loggedIn: false, id: null, username: null, role: null };
    state.students = [];
    state.companies = [];
    state.myStudent = null;
    state.attendance = { today: null, history: [], stats: null };
    els.studentCards.innerHTML = "";
    els.companyCards.innerHTML = "";
    els.myStudentData.innerHTML = "";
    els.attendanceHistory.innerHTML = "";
    els.attendanceReportList.innerHTML = "";
    toast("Logged out");
    lockToLoginView();
  } catch (error) {
    toast(error.message, "error");
  }
}

async function fetchJson(url, options = {}) {
  const config = { headers: { "Content-Type": "application/json" }, ...options };
  const response = await fetch(url, config);
  const payload = await response.json();
  if (!response.ok || payload.error) throw new Error(payload.error || "Request failed");
  return payload;
}

function internshipProgress(startDateInput, endDateInput) {
  const now = new Date();
  const startDate = new Date(`${startDateInput}T00:00:00`);
  const endDate = new Date(`${endDateInput}T00:00:00`);
  const totalMs = Math.max(endDate - startDate, 1);
  const elapsedMs = Math.min(Math.max(now - startDate, 0), totalMs);
  const remainingMs = Math.max(endDate - now, 0);
  const percent = Math.max(0, Math.min(100, Math.round((elapsedMs / totalMs) * 100)));
  const remainingPercent = 100 - percent;
  const remainingDays = Math.max(0, Math.ceil(remainingMs / (1000 * 60 * 60 * 24)));
  const remainingMonths = Math.max(0, Math.floor(remainingDays / 30));
  let phase = "Ongoing";
  if (percent >= 80 && percent < 100) phase = "Nearly Completed";
  if (percent >= 100) phase = "Completed";
  return { percent, remainingPercent, remainingDays, remainingMonths, phase };
}

function todayYmd() {
  return new Date().toISOString().slice(0, 10);
}

function getInitials(text) {
  const parts = text.trim().split(/\s+/).slice(0, 2);
  return parts.map((part) => part[0]?.toUpperCase() || "").join("") || "?";
}

function colorByText(text) {
  let hash = 0;
  for (let i = 0; i < text.length; i += 1) hash = text.charCodeAt(i) + ((hash << 5) - hash);
  return palette[Math.abs(hash) % palette.length];
}

function formatDate(dateInput) {
  const date = new Date(`${dateInput}T00:00:00`);
  return date.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}

function formatDateTime(input) {
  if (!input) return "-";
  const safe = String(input).replace(" ", "T");
  const date = new Date(safe);
  if (Number.isNaN(date.getTime())) return String(input);
  return date.toLocaleString("en-GB");
}

function toast(message, type = "success") {
  const node = document.createElement("div");
  node.className = `toast ${type === "error" ? "error" : ""}`;
  node.textContent = message;
  els.toastContainer.appendChild(node);
  setTimeout(() => node.remove(), 2500);
}

function debounce(fn, wait) {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), wait);
  };
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function escapeAttr(value) {
  return escapeHtml(value).replaceAll("`", "");
}

function escapeJs(value) {
  return String(value).replaceAll("\\", "\\\\").replaceAll("'", "\\'");
}
