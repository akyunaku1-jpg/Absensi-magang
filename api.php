<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    ensureDefaultUserAccounts();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $entity = $_GET['entity'] ?? '';

    if ($method === 'GET') {
        if ($entity === 'students') {
            requireLogin();
            respond(listStudents());
        }
        if ($entity === 'companies') {
            requireLogin();
            respond(listCompanies());
        }
        if ($entity === 'stats') {
            requireLogin();
            respond(fetchStatistics());
        }
        if ($entity === 'auth') {
            respond(currentAuthState());
        }
        if ($entity === 'attendance') {
            respond(listAttendance());
        }
        if ($entity === 'myStudent') {
            respond(fetchMyStudentData());
        }
        if ($entity === 'users') {
            requireAdmin();
            respond(listUsers());
        }
        throw new RuntimeException('Unknown GET endpoint.');
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($payload)) {
            throw new RuntimeException('Invalid JSON payload.');
        }

        $action = (string)($payload['action'] ?? '');

        if ($action === 'login') {
            respond(login($payload));
        }
        if ($action === 'logout') {
            logout();
            respond(['success' => true]);
        }
        if ($action === 'markAttendance') {
            respond(markAttendance($payload));
        }

        if ($action === 'saveStudent') {
            requireAdmin();
            respond(saveStudent($payload));
        }
        if ($action === 'deleteStudent') {
            requireAdmin();
            deleteStudent((int)($payload['id'] ?? 0));
            respond(['success' => true]);
        }
        if ($action === 'saveCompany') {
            requireAdmin();
            respond(saveCompany($payload));
        }
        if ($action === 'deleteCompany') {
            requireAdmin();
            deleteCompany((int)($payload['id'] ?? 0));
            respond(['success' => true]);
        }
        if ($action === 'saveUser') {
            requireAdmin();
            respond(saveUser($payload));
        }
        if ($action === 'deleteUser') {
            requireAdmin();
            deleteUser((int)($payload['id'] ?? 0));
            respond(['success' => true]);
        }

        throw new RuntimeException('Unknown action.');
    }

    throw new RuntimeException('Unsupported HTTP method.');
} catch (Throwable $e) {
    http_response_code(400);
    respond(['error' => $e->getMessage()]);
}

function respond($payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function currentAuthState(): array
{
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user)) {
        return [
            'loggedIn' => false,
            'id' => null,
            'username' => null,
            'role' => null
        ];
    }

    return [
        'loggedIn' => true,
        'id' => (int)$user['id'],
        'username' => (string)$user['username'],
        'role' => (string)$user['role']
    ];
}

function login(array $payload): array
{
    $username = strtolower(trim((string)($payload['username'] ?? '')));
    $password = (string)($payload['password'] ?? '');

    if ($username === '' || $password === '') {
        throw new RuntimeException('Username and password are required.');
    }

    $rows = supabaseRequest('GET', 'users', [
        'select' => 'id,username,password_hash,role',
        'username' => 'eq.' . $username,
        'limit' => '1'
    ]);
    $user = $rows[0] ?? null;

    if (!is_array($user) || !password_verify($password, (string)$user['password_hash'])) {
        throw new RuntimeException('Invalid username or password.');
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => (string)$user['username'],
        'role' => (string)$user['role']
    ];

    return currentAuthState();
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function requireAdmin(): void
{
    $role = (string)(($_SESSION['user']['role'] ?? ''));
    if ($role !== 'admin') {
        throw new RuntimeException('Admin access required.');
    }
}

function requireLogin(): array
{
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user)) {
        throw new RuntimeException('Please login first.');
    }
    return $user;
}

function listAttendance(): array
{
    $auth = requireLogin();
    $startDate = trim((string)($_GET['start_date'] ?? ''));
    $endDate = trim((string)($_GET['end_date'] ?? ''));

    if ((string)$auth['role'] === 'admin') {
        $query = ['select' => 'id,user_id,attendance_date,status,check_in_at,note', 'order' => 'attendance_date.desc'];
        if ($startDate !== '') {
            $query['attendance_date'] = 'gte.' . $startDate;
        }
        if ($endDate !== '') {
            $query['attendance_date'] = ($query['attendance_date'] ?? '') !== ''
                ? ($query['attendance_date'] . '&attendance_date=lte.' . $endDate)
                : 'lte.' . $endDate;
        }

        $rows = supabaseRequest('GET', 'attendances', ['select' => 'id,user_id,attendance_date,status,check_in_at,note,created_at']);
        $rows = applyDateRange($rows, $startDate, $endDate);

        $userIds = array_values(array_unique(array_map(static fn($r) => (int)($r['user_id'] ?? 0), $rows)));
        $userMap = fetchUsersByIds($userIds);

        $stats = [
            'Present' => 0,
            'Sick' => 0,
            'Permission' => 0,
            'Absent' => 0
        ];
        $history = [];
        foreach ($rows as $row) {
            $key = (string)($row['status'] ?? '');
            if (isset($stats[$key])) {
                $stats[$key] += 1;
            }

            $uid = (int)($row['user_id'] ?? 0);
            $history[] = [
                'attendance_date' => (string)($row['attendance_date'] ?? ''),
                'status' => (string)($row['status'] ?? ''),
                'check_in_at' => (string)($row['check_in_at'] ?? ''),
                'note' => $row['note'] ?? null,
                'username' => (string)($userMap[$uid] ?? '-')
            ];
        }
        usort($history, static fn($a, $b) => strcmp((string)$b['attendance_date'], (string)$a['attendance_date']));
        $history = array_slice($history, 0, 50);

        return [
            'today' => null,
            'history' => $history,
            'stats' => $stats
        ];
    }

    $userId = (int)$auth['id'];
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');
    $rows = supabaseRequest('GET', 'attendances', [
        'select' => 'attendance_date,status,check_in_at,note',
        'user_id' => 'eq.' . $userId,
        'order' => 'attendance_date.desc'
    ]);

    $todayRow = null;
    foreach ($rows as $row) {
        if ((string)($row['attendance_date'] ?? '') === $today) {
            $todayRow = $row;
            break;
        }
    }
    $history = array_slice($rows, 0, 30);

    return [
        'today' => $todayRow,
        'history' => $history,
        'stats' => null
    ];
}

function markAttendance(array $payload): array
{
    $auth = requireLogin();
    if ((string)$auth['role'] !== 'user') {
        throw new RuntimeException('Attendance check-in is only for user accounts.');
    }

    $userId = (int)$auth['id'];
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');
    $checkInAt = nowIso();
    $status = trim((string)($payload['status'] ?? 'Present'));
    $note = trim((string)($payload['note'] ?? ''));
    $noteOrNull = $note === '' ? null : $note;
    $allowedStatuses = ['Present', 'Sick', 'Permission', 'Absent'];
    if (!in_array($status, $allowedStatuses, true)) {
        throw new RuntimeException('Invalid attendance status.');
    }

    try {
        supabaseRequest('POST', 'attendances', [], [[
            'user_id' => $userId,
            'attendance_date' => $today,
            'status' => $status,
            'check_in_at' => $checkInAt,
            'note' => $noteOrNull,
            'created_at' => nowIso()
        ]]);
    } catch (Throwable $e) {
        throw new RuntimeException('You already checked in today.');
    }

    return ['success' => true];
}

function listStudents(): array
{
    $auth = requireLogin();
    $search = trim((string)($_GET['search'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $major = trim((string)($_GET['major'] ?? ''));

    $query = ['select' => '*', 'order' => 'created_at.desc'];
    if ((string)$auth['role'] === 'user') {
        $query['user_username'] = 'eq.' . (string)$auth['username'];
    }
    if ($status !== '') {
        $query['status'] = 'eq.' . $status;
    }
    if ($major !== '') {
        $query['major'] = 'eq.' . $major;
    }

    $rows = supabaseRequest('GET', 'students', $query);
    if ($search !== '') {
        $searchLower = mb_strtolower($search);
        $rows = array_values(array_filter($rows, static function ($row) use ($searchLower): bool {
            $text = mb_strtolower(
                (string)($row['name'] ?? '') . ' ' .
                (string)($row['nis'] ?? '') . ' ' .
                (string)($row['school_origin'] ?? '') . ' ' .
                (string)($row['major'] ?? '')
            );
            return mb_strpos($text, $searchLower) !== false;
        }));
    }

    $companyIds = array_values(array_unique(array_filter(array_map(static fn($r) => (int)($r['company_id'] ?? 0), $rows))));
    $companyMap = fetchCompaniesByIds($companyIds);
    foreach ($rows as &$row) {
        $cid = (int)($row['company_id'] ?? 0);
        $row['company_name'] = $cid > 0 ? (string)($companyMap[$cid] ?? '-') : '-';
    }
    unset($row);

    return $rows;
}

function listCompanies(): array
{
    requireLogin();
    $companies = supabaseRequest('GET', 'companies', ['select' => '*', 'order' => 'created_at.desc']);
    $students = supabaseRequest('GET', 'students', ['select' => 'company_id']);
    $counts = [];
    foreach ($students as $student) {
        $cid = (int)($student['company_id'] ?? 0);
        if ($cid > 0) {
            $counts[$cid] = ($counts[$cid] ?? 0) + 1;
        }
    }
    foreach ($companies as &$company) {
        $cid = (int)($company['id'] ?? 0);
        $company['student_count'] = (int)($counts[$cid] ?? 0);
    }
    unset($company);
    return $companies;
}

function fetchMyStudentData(): array
{
    $auth = requireLogin();
    if ((string)$auth['role'] !== 'user') {
        return ['student' => null];
    }

    $rows = supabaseRequest('GET', 'students', [
        'select' => '*',
        'user_username' => 'eq.' . (string)$auth['username'],
        'order' => 'created_at.desc',
        'limit' => '1'
    ]);
    $student = $rows[0] ?? null;
    if (!is_array($student)) {
        return ['student' => null];
    }

    $cid = (int)($student['company_id'] ?? 0);
    if ($cid > 0) {
        $map = fetchCompaniesByIds([$cid]);
        $student['company_name'] = (string)($map[$cid] ?? '-');
    } else {
        $student['company_name'] = '-';
    }
    return ['student' => $student];
}

function fetchStatistics(): array
{
    $students = supabaseRequest('GET', 'students', ['select' => 'status,school_origin']);
    $total = count($students);
    $active = 0;
    $completed = 0;
    $schoolSet = [];
    foreach ($students as $student) {
        $status = (string)($student['status'] ?? '');
        if ($status === 'Active') {
            $active++;
        } elseif ($status === 'Completed') {
            $completed++;
        }
        $school = trim((string)($student['school_origin'] ?? ''));
        if ($school !== '') {
            $schoolSet[$school] = true;
        }
    }
    $schools = count($schoolSet);

    return [
        'total' => $total,
        'active' => $active,
        'completed' => $completed,
        'schools' => $schools
    ];
}

function saveStudent(array $payload): array
{
    $id = (int)($payload['id'] ?? 0);

    $name = trim((string)($payload['name'] ?? ''));
    $nis = trim((string)($payload['nis'] ?? ''));
    $phone = trim((string)($payload['phone'] ?? ''));
    $school = trim((string)($payload['school_origin'] ?? ''));
    $major = trim((string)($payload['major'] ?? ''));
    $status = trim((string)($payload['status'] ?? ''));
    $startDate = trim((string)($payload['internship_start_date'] ?? ''));
    $endDate = trim((string)($payload['internship_end_date'] ?? ''));
    $companyId = (int)($payload['company_id'] ?? 0);
    $userUsername = trim((string)($payload['user_username'] ?? ''));

    if (
        $name === '' || $nis === '' || $phone === '' || $school === '' || $major === '' ||
        $status === '' || $startDate === '' || $endDate === ''
    ) {
        throw new RuntimeException('All student fields are required.');
    }
    if (!in_array($status, ['Active', 'Completed'], true)) {
        throw new RuntimeException('Invalid student status.');
    }
    if ($startDate > $endDate) {
        throw new RuntimeException('Internship start date must be before end date.');
    }

    $companyIdOrNull = $companyId > 0 ? $companyId : null;
    $userUsernameOrNull = $userUsername === '' ? null : $userUsername;
    if ($userUsernameOrNull !== null) {
        $match = supabaseRequest('GET', 'users', [
            'select' => 'id',
            'username' => 'eq.' . $userUsernameOrNull,
            'role' => 'eq.user',
            'limit' => '1'
        ]);
        if ($match === []) {
            throw new RuntimeException('Assigned user email is not registered as student account.');
        }
    }

    $data = [
        'name' => $name,
        'nis' => $nis,
        'phone' => $phone,
        'school_origin' => $school,
        'major' => $major,
        'user_username' => $userUsernameOrNull,
        'status' => $status,
        'internship_start_date' => $startDate,
        'internship_end_date' => $endDate,
        'company_id' => $companyIdOrNull,
        'updated_at' => nowIso()
    ];

    if ($id > 0) {
        supabaseRequest('PATCH', 'students', ['id' => 'eq.' . $id], $data);
        return ['success' => true, 'id' => $id];
    }

    $data['created_at'] = nowIso();
    $created = supabaseRequest('POST', 'students', ['select' => 'id'], [$data]);
    $newId = (int)($created[0]['id'] ?? 0);
    return ['success' => true, 'id' => $newId];
}

function deleteStudent(int $id): void
{
    if ($id < 1) {
        throw new RuntimeException('Invalid student ID.');
    }
    supabaseRequest('DELETE', 'students', ['id' => 'eq.' . $id], null);
}

function saveCompany(array $payload): array
{
    $id = (int)($payload['id'] ?? 0);

    $name = trim((string)($payload['name'] ?? ''));
    $address = trim((string)($payload['address'] ?? ''));
    $supervisor = trim((string)($payload['supervisor_name'] ?? ''));

    if ($name === '' || $address === '' || $supervisor === '') {
        throw new RuntimeException('All company fields are required.');
    }

    $data = [
        'name' => $name,
        'address' => $address,
        'supervisor_name' => $supervisor,
        'updated_at' => nowIso()
    ];

    if ($id > 0) {
        supabaseRequest('PATCH', 'companies', ['id' => 'eq.' . $id], $data);
        return ['success' => true, 'id' => $id];
    }

    $data['created_at'] = nowIso();
    $created = supabaseRequest('POST', 'companies', ['select' => 'id'], [$data]);
    return ['success' => true, 'id' => (int)($created[0]['id'] ?? 0)];
}

function deleteCompany(int $id): void
{
    if ($id < 1) {
        throw new RuntimeException('Invalid company ID.');
    }
    supabaseRequest('DELETE', 'companies', ['id' => 'eq.' . $id], null);
}

function listUsers(): array
{
    $search = trim((string)($_GET['search'] ?? ''));
    $users = supabaseRequest('GET', 'users', ['select' => 'id,username,role,created_at', 'order' => 'created_at.desc']);
    if ($search !== '') {
        $s = mb_strtolower($search);
        $users = array_values(array_filter($users, static function ($u) use ($s): bool {
            return mb_strpos(mb_strtolower((string)($u['username'] ?? '')), $s) !== false
                || mb_strpos(mb_strtolower((string)($u['role'] ?? '')), $s) !== false;
        }));
    }

    $students = supabaseRequest('GET', 'students', ['select' => 'user_username']);
    $linked = [];
    foreach ($students as $student) {
        $uname = (string)($student['user_username'] ?? '');
        if ($uname !== '') {
            $linked[$uname] = ($linked[$uname] ?? 0) + 1;
        }
    }
    foreach ($users as &$user) {
        $username = (string)($user['username'] ?? '');
        $user['linked_students'] = (int)($linked[$username] ?? 0);
    }
    unset($user);
    return $users;
}

function saveUser(array $payload): array
{
    $id = (int)($payload['id'] ?? 0);
    $username = strtolower(trim((string)($payload['username'] ?? '')));
    $password = (string)($payload['password'] ?? '');
    $role = trim((string)($payload['role'] ?? 'user'));

    if ($username === '' || $role === '') {
        throw new RuntimeException('Username and role are required.');
    }
    if (!in_array($role, ['admin', 'user'], true)) {
        throw new RuntimeException('Invalid role.');
    }
    if ($id > 0) {
        $current = supabaseRequest('GET', 'users', [
            'select' => 'id,username',
            'id' => 'eq.' . $id,
            'limit' => '1'
        ]);
        if ($current === []) {
            throw new RuntimeException('User not found.');
        }
        $oldUsername = (string)$current[0]['username'];
        $data = [
            'username' => $username,
            'role' => $role
        ];
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        supabaseRequest('PATCH', 'users', ['id' => 'eq.' . $id], $data);
        if ($oldUsername !== $username) {
            supabaseRequest('PATCH', 'students', ['user_username' => 'eq.' . $oldUsername], ['user_username' => $username]);
        }
        return ['success' => true, 'id' => $id];
    }

    if (strlen($password) < 6) {
        throw new RuntimeException('Password must be at least 6 characters.');
    }
    $created = supabaseRequest('POST', 'users', ['select' => 'id'], [[
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'created_at' => nowIso()
    ]]);
    return ['success' => true, 'id' => (int)($created[0]['id'] ?? 0)];
}

function deleteUser(int $id): void
{
    if ($id < 1) {
        throw new RuntimeException('Invalid user ID.');
    }
    $selfId = (int)($_SESSION['user']['id'] ?? 0);
    if ($id === $selfId) {
        throw new RuntimeException('You cannot delete your own account.');
    }

    $rows = supabaseRequest('GET', 'users', [
        'select' => 'id,username',
        'id' => 'eq.' . $id,
        'limit' => '1'
    ]);
    if ($rows === []) {
        throw new RuntimeException('User not found.');
    }
    $username = (string)$rows[0]['username'];

    supabaseRequest('PATCH', 'students', ['user_username' => 'eq.' . $username], ['user_username' => null]);
    supabaseRequest('DELETE', 'users', ['id' => 'eq.' . $id], null);
}

function fetchCompaniesByIds(array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $idList = implode(',', array_map('intval', $ids));
    $rows = supabaseRequest('GET', 'companies', [
        'select' => 'id,name',
        'id' => 'in.(' . $idList . ')'
    ]);
    $map = [];
    foreach ($rows as $row) {
        $map[(int)($row['id'] ?? 0)] = (string)($row['name'] ?? '');
    }
    return $map;
}

function fetchUsersByIds(array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $idList = implode(',', array_map('intval', $ids));
    $rows = supabaseRequest('GET', 'users', [
        'select' => 'id,username',
        'id' => 'in.(' . $idList . ')'
    ]);
    $map = [];
    foreach ($rows as $row) {
        $map[(int)($row['id'] ?? 0)] = (string)($row['username'] ?? '');
    }
    return $map;
}

function applyDateRange(array $rows, string $startDate, string $endDate): array
{
    return array_values(array_filter($rows, static function ($row) use ($startDate, $endDate): bool {
        $date = (string)($row['attendance_date'] ?? '');
        if ($date === '') {
            return false;
        }
        if ($startDate !== '' && $date < $startDate) {
            return false;
        }
        if ($endDate !== '' && $date > $endDate) {
            return false;
        }
        return true;
    }));
}

