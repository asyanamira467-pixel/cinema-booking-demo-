<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF: untuk semua aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? null;
  if (!csrf_verify(is_string($token) ? $token : null)) {
    http_response_code(419);
    echo 'CSRF failed';
    exit;
  }
}

// FIX #1: logout harus POST agar terlindungi CSRF
if ($action === 'logout') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=home');
    exit;
  }
  (new User())->logout();
  header('Location: index.php?page=home');
  exit;
}

$user = new User();
$transaksi = new Transaksi();
$filmModel = new Film();
$jadwalModel = new Jadwal();
$studioModel = new Studio();

try {
  switch ($action) {

    // ===================== AUTH =====================
    case 'login':
      $username = trim((string) ($_POST['username'] ?? ''));
      $password = (string) ($_POST['password'] ?? '');
      if ($username === '' || $password === '') {
        header('Location: index.php?page=home&err=login_empty');
        exit;
      }
      if ($user->login($username, $password)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
          echo json_encode(['ok' => true]);
          exit;
        }
        header('Location: index.php?page=dashboard');
        exit;
      }
      // gagal:
      if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['ok' => false, 'msg' => 'Username atau password salah.']);
        exit;
      }
      header('Location: index.php?page=home&err=login_wrong');
      exit;

    case 'register':
      $nama = trim((string) ($_POST['nama'] ?? ''));
      $username = trim((string) ($_POST['username'] ?? ''));
      $email = trim((string) ($_POST['email'] ?? ''));
      $phone = trim((string) ($_POST['phone'] ?? ''));
      $password = (string) ($_POST['password'] ?? '');
      if ($nama === '' || $username === '' || $email === '' || $password === '') {
        header('Location: index.php?page=home&err=register_empty');
        exit;
      }
      if ($user->register($nama, $username, $email, $phone, $password)) {
        header('Location: index.php?page=dashboard');
        exit;
      }
      header('Location: index.php?page=home&err=register_dup');
      exit;

    // ===================== USER BOOKING =====================
    case 'booking_create':
      Helper::require_roles(['user', 'operator', 'admin']);
      $jadwal_id = (string) ($_POST['jadwal_id'] ?? '');
      $metode = (string) ($_POST['metode'] ?? 'QRIS');
      $user_id = (int) $_SESSION['user_id'];
      $role = $_SESSION['role'] ?? 'user';

      $rawKursi = $_POST['kursi'] ?? [];
      $kursiList = is_array($rawKursi)
        ? array_values(array_filter(array_map('trim', $rawKursi)))
        : [];
      $jumlah = count($kursiList);

      if ($role === 'user') {
        $pelanggan_id = (string) ($_SESSION['pelanggan_id'] ?? '');
        $catatan = 'Booking online';
      } else {
        // operator & admin: pakai P000 (walk-in), nama disimpan di catatan
        $pelanggan_id = 'P000';
        $nama_walkin = trim((string) ($_POST['nama_pelanggan'] ?? 'Walk-in'));
        $catatan = 'Walk-in: ' . $nama_walkin;
      }

      if ($jadwal_id === '' || $jumlah < 1 || $jumlah > 10 || $pelanggan_id === '') {
        header('Location: index.php?page=schedule&err=invalid');
        exit;
      }

      if ($transaksi->createBooking($user_id, $pelanggan_id, $jadwal_id, $jumlah, $metode, $kursiList, $catatan)) {
        header('Location: index.php?page=dashboard&ok=booked');
      } else {
        header('Location: index.php?page=booking&jadwal_id=' . urlencode($jadwal_id) . '&err=seat_taken');
      }
      exit;

    case 'booking_cancel':
      Helper::require_roles(['user']);
      $transaksi_id = (string) ($_POST['transaksi_id'] ?? '');
      $user_id = (int) $_SESSION['user_id'];
      if ($transaksi_id !== '') {
        $transaksi->cancelPending($user_id, $transaksi_id);
      }
      header('Location: index.php?page=dashboard&ok=cancelled');
      exit;

    // ===================== OPERATOR =====================
    case 'operator_confirm':
      Helper::require_roles(['operator']);
      $transaksi_id = (string) ($_POST['transaksi_id'] ?? '');
      if ($transaksi_id !== '') {
        $transaksi->operatorOfflineConfirm($transaksi_id);
      }
      header('Location: index.php?page=dashboard&ok=confirmed');
      exit;

    // FIX #5: operator bisa cancel transaksi pending
    case 'operator_cancel':
      Helper::require_roles(['operator']);
      $transaksi_id = (string) ($_POST['transaksi_id'] ?? '');
      if ($transaksi_id !== '') {
        $transaksi->adminCancel($transaksi_id);
      }
      header('Location: index.php?page=dashboard&ok=cancelled');
      exit;

    // ===================== ADMIN — TRANSAKSI =====================
    case 'admin_confirm':
      Helper::require_roles(['admin']);
      $transaksi_id = (string) ($_POST['transaksi_id'] ?? '');
      if ($transaksi_id !== '') {
        $transaksi->adminConfirm($transaksi_id);
      }
      header('Location: index.php?page=dashboard&tab=transaksi&ok=confirmed');
      exit;

    case 'admin_cancel':
      Helper::require_roles(['admin']);
      $transaksi_id = (string) ($_POST['transaksi_id'] ?? '');
      if ($transaksi_id !== '') {
        $transaksi->adminCancel($transaksi_id);
      }
      header('Location: index.php?page=dashboard&tab=transaksi&ok=cancelled');
      exit;

    // ===================== ADMIN — FILM =====================
    case 'admin_film_create':
      Helper::require_roles(['admin']);
      $data = [
        'judul' => trim((string) ($_POST['judul'] ?? '')),
        'genre' => trim((string) ($_POST['genre'] ?? '')),
        'durasi_menit' => (int) ($_POST['durasi_menit'] ?? 0),
        'rating_usia' => trim((string) ($_POST['rating_usia'] ?? '')),
        'sinopsis' => trim((string) ($_POST['sinopsis'] ?? '')),
        'poster' => trim((string) ($_POST['poster'] ?? 'default.jpg')),
      ];
      if ($data['judul'] !== '' && $data['genre'] !== '' && $data['durasi_menit'] > 0) {
        $filmModel->create($data);
      }
      header('Location: index.php?page=dashboard&tab=film&ok=film_created');
      exit;

    case 'admin_film_update':
      Helper::require_roles(['admin']);
      $film_id = (string) ($_POST['film_id'] ?? '');
      $data = [
        'judul' => trim((string) ($_POST['judul'] ?? '')),
        'genre' => trim((string) ($_POST['genre'] ?? '')),
        'durasi_menit' => (int) ($_POST['durasi_menit'] ?? 0),
        'rating_usia' => trim((string) ($_POST['rating_usia'] ?? '')),
        'sinopsis' => trim((string) ($_POST['sinopsis'] ?? '')),
        'poster' => trim((string) ($_POST['poster'] ?? 'default.jpg')),
      ];
      if ($film_id !== '' && $data['judul'] !== '') {
        $filmModel->update($film_id, $data);
      }
      header('Location: index.php?page=dashboard&tab=film&ok=film_updated');
      exit;

    case 'admin_film_delete':
      Helper::require_roles(['admin']);
      $film_id = (string) ($_POST['film_id'] ?? '');
      if ($film_id !== '') {
        $filmModel->delete($film_id);
      }
      header('Location: index.php?page=dashboard&tab=film&ok=film_deleted');
      exit;

    // ===================== ADMIN — STUDIO =====================
    case 'admin_studio_create':
      Helper::require_roles(['admin']);
      $data = [
        'nama' => trim((string) ($_POST['nama'] ?? '')),
        'kapasitas' => (int) ($_POST['kapasitas'] ?? 0),
      ];
      if ($data['nama'] !== '' && $data['kapasitas'] > 0) {
        $studioModel->create($data);
      }
      header('Location: index.php?page=dashboard&tab=studio&ok=studio_created');
      exit;

    case 'admin_studio_update':
      Helper::require_roles(['admin']);
      $studio_id = (string) ($_POST['studio_id'] ?? '');
      $data = [
        'nama' => trim((string) ($_POST['nama'] ?? '')),
        'kapasitas' => (int) ($_POST['kapasitas'] ?? 0),
      ];
      if ($studio_id !== '' && $data['nama'] !== '') {
        $studioModel->update($studio_id, $data);
      }
      header('Location: index.php?page=dashboard&tab=studio&ok=studio_updated');
      exit;

    case 'admin_studio_delete':
      Helper::require_roles(['admin']);
      $studio_id = (string) ($_POST['studio_id'] ?? '');
      if ($studio_id !== '') {
        $studioModel->delete($studio_id);
      }
      header('Location: index.php?page=dashboard&tab=studio&ok=studio_deleted');
      exit;

    // ===================== ADMIN — JADWAL =====================
    case 'admin_jadwal_create':
      Helper::require_roles(['admin']);
      $data = [
        'film_id' => (string) ($_POST['film_id'] ?? ''),
        'studio_id' => (string) ($_POST['studio_id'] ?? ''),
        'tanggal' => (string) ($_POST['tanggal'] ?? ''),
        'jam' => (string) ($_POST['jam'] ?? ''),
        'harga' => (int) ($_POST['harga'] ?? 0),
      ];
      // FIX #6: validasi tanggal tidak boleh di masa lalu
      if ($data['tanggal'] !== '' && $data['tanggal'] < date('Y-m-d')) {
        header('Location: index.php?page=dashboard&tab=jadwal&err=past_date');
        exit;
      }
      if ($data['film_id'] !== '' && $data['studio_id'] !== '' && $data['tanggal'] !== '' && $data['harga'] > 0) {
        $jadwalModel->create($data);
      }
      header('Location: index.php?page=dashboard&tab=jadwal&ok=jadwal_created');
      exit;

    case 'admin_jadwal_update':
      Helper::require_roles(['admin']);
      $jadwal_id = (string) ($_POST['jadwal_id'] ?? '');
      $data = [
        'film_id' => (string) ($_POST['film_id'] ?? ''),
        'studio_id' => (string) ($_POST['studio_id'] ?? ''),
        'tanggal' => (string) ($_POST['tanggal'] ?? ''),
        'jam' => (string) ($_POST['jam'] ?? ''),
        'harga' => (int) ($_POST['harga'] ?? 0),
      ];
      if ($jadwal_id !== '' && $data['film_id'] !== '') {
        $jadwalModel->update($jadwal_id, $data);
      }
      header('Location: index.php?page=dashboard&tab=jadwal&ok=jadwal_updated');
      exit;

    case 'admin_jadwal_delete':
      Helper::require_roles(['admin']);
      $jadwal_id = (string) ($_POST['jadwal_id'] ?? '');
      if ($jadwal_id !== '') {
        $jadwalModel->delete($jadwal_id);
      }
      header('Location: index.php?page=dashboard&tab=jadwal&ok=jadwal_deleted');
      exit;

    // ===================== ADMIN — USER =====================
    case 'admin_user_detail':
      Helper::require_roles(['admin']);
      $user_id = (int) ($_POST['user_id'] ?? 0);
      if ($user_id > 0) {
        $userModel = new User();
        $result = $userModel->adminDetail($user_id);
        header('Content-Type: application/json');
        echo json_encode($result);
      }
      exit;

    case 'admin_user_create':
      Helper::require_roles(['admin']);
      $nama = trim((string) ($_POST['nama'] ?? ''));
      $username = trim((string) ($_POST['username'] ?? ''));
      $email = trim((string) ($_POST['email'] ?? ''));
      $phone = trim((string) ($_POST['phone'] ?? ''));
      $password = (string) ($_POST['password'] ?? '');
      $role = (string) ($_POST['role'] ?? 'user');
      
      if ($nama !== '' && $username !== '' && $password !== '' && in_array($role, ['user', 'operator', 'admin'], true)) {
        $user->adminCreate($nama, $username, $email, $phone, $password, $role);
      }
      header('Location: index.php?page=dashboard&tab=users&ok=user_created');
      exit;

    case 'admin_user_update':
      Helper::require_roles(['admin']);
      $upd_uid = (int) ($_POST['upd_user_id'] ?? 0);
      $self_uid = (int) ($_SESSION['user_id'] ?? 0);
      if ($upd_uid > 0 && $upd_uid !== $self_uid) {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'user');
        
        if (in_array($role, ['user', 'operator', 'admin'], true)) {
          $user->adminUpdate($upd_uid, $nama, $email, $phone, $role, $password);
        }
      }
      header('Location: index.php?page=dashboard&tab=users&ok=user_updated');
      exit;

    case 'admin_user_delete':
      Helper::require_roles(['admin']);
      $del_uid = (int) ($_POST['del_user_id'] ?? 0);
      $self_uid = (int) ($_SESSION['user_id'] ?? 0);
      if ($del_uid > 0 && $del_uid !== $self_uid) {
        $user->adminDelete($del_uid);
      }
      header('Location: index.php?page=dashboard&tab=users&ok=user_deleted');
      exit;

    default:
      header('Location: index.php?page=home');
      exit;
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Error: ' . htmlspecialchars($e->getMessage());
}
