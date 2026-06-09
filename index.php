<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes.php';

$page = $_GET['page'] ?? 'home';
$userRole = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? null;
$searchQuery = $_GET['q'] ?? '';

$film = new Film();
$jadwal = new Jadwal();
$transaksi = new Transaksi();

$nowShowing = $film->todayNowShowing(8);
$todaySchedule = $jadwal->listToday();
$comingSoon = $jadwal->listComing(5);
$popular = $film->popular(8);
$searchResults = [];
$movieDetail = null;

if ($page === 'search' && !empty($searchQuery)) {
  $searchResults = $film->search(trim($searchQuery));
}

if ($page === 'movie') {
  $filmId = $_GET['film_id'] ?? '';
  if (!empty($filmId)) {
    $movieDetail = $film->find($filmId);
  }
}

$myTransactions = [];
$pendingForOperator = [];
$adminTransactions = [];
$adminStats = [];
$adminFilms = [];
$adminStudios = [];
$adminJadwals = [];
$adminUsers = [];
$activeTab = $_GET['tab'] ?? 'transaksi';

$studio = new Studio();

if (!empty($_SESSION['user_id'])) {
  $uid = (int) $_SESSION['user_id'];
  if ($userRole === 'user') {
    $myTransactions = $transaksi->listMy($uid);
  } elseif ($userRole === 'operator') {
    $pendingForOperator = $transaksi->listPendingForOperator();
  } elseif ($userRole === 'admin' && $page === 'dashboard') {
    $adminStats     = $transaksi->adminStats();
    $adminTransactions = $transaksi->adminAll();
    $adminFilms     = $film->allAdmin();
    $adminStudios   = $studio->all();
    $adminJadwals   = $jadwal->allAdmin();
    $userModel      = new User();
    $adminUsers     = $userModel->adminAll();
  }
}

function route_link(string $page, array $params = []): string
{
  return 'index.php?' . http_build_query(array_merge(['page' => $page], $params));
}

/* ---- Render card film/jadwal ---- */
function render_card_movie(array $m, bool $isBooking = true): void
{
  global $loggedIn;
  $poster = htmlspecialchars($m['poster'] ?? '', ENT_QUOTES, 'UTF-8');
  $judul = htmlspecialchars((string) ($m['judul'] ?? ''));
  $genre = htmlspecialchars((string) ($m['genre'] ?? ''));
  $durasi = isset($m['durasi_menit']) ? (int) $m['durasi_menit'] . ' mnt' : '';
  $rating = htmlspecialchars((string) ($m['rating_usia'] ?? ''));
  $jadwalId = $m['jadwal_id'] ?? '';
  $filmId  = $m['film_id'] ?? '';
  $bookUrl = ($isBooking && $jadwalId)
    ? route_link('booking', ['jadwal_id' => $jadwalId])
    : ($filmId ? route_link('movie', ['film_id' => $filmId]) : route_link('schedule'));

  echo '<div class="card-movie">';
  echo '  <div class="movie-poster">';
  echo '    <img src="assets/images/' . $poster . '" alt="' . $judul . '" onerror="this.style.background=\'#1a1a2e\';this.removeAttribute(\'src\')">';
  echo '    <div class="movie-overlay"></div>';
  echo '  </div>';
  echo '  <div class="movie-body">';
  echo '    <h3 class="movie-title">' . $judul . '</h3>';
  echo '    <div class="movie-meta">';
  if ($rating)
    echo '<span class="chip chip-red">' . $rating . '</span>';
  echo '      <span class="muted">' . $genre . '</span>';
  if ($durasi)
    echo '<span class="muted">&bull; ' . $durasi . '</span>';
  echo '    </div>';
  if (!empty($m['jam']) && !empty($m['tanggal'])) {
    echo '    <div class="movie-sub">' . htmlspecialchars((string) $m['tanggal']) . ' &bull; ' . htmlspecialchars((string) $m['jam']) . '</div>';
  }
  // Tombol booking: kalau belum login → buka modal login dulu
  if ($isBooking && $jadwalId && !$loggedIn) {
    $btn = '<button class="btn btn-primary btn-sm" data-modal-open="loginModal">Pesan Tiket</button>';
  } else {
    $btn = '<a class="btn btn-primary btn-sm" href="' . $bookUrl . '">Pesan Tiket</a>';
  }
  echo '    <div class="movie-actions">';
  echo '      ' . $btn;
  echo '    </div>';
  echo '  </div>';
  echo '</div>';
}

$heroSlides = ['assets/images/inception.jpg', 'assets/images/interstellar.jpg', 'assets/images/batman.jpg', 'assets/images/dune.jpg'];
$loggedIn = !empty($_SESSION['user_id']);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Bioskopp &ndash; Cinema Premium</title>
  <link rel="stylesheet" href="style.css" />
  <script defer src="script.js"></script>
</head>

<body data-page="<?= htmlspecialchars($page) ?>">
  <div class="bg-grid"></div>

  <header class="navbar">
    <div class="nav-inner">
      <a class="brand" href="<?= route_link('home') ?>">
        <span class="brand-mark">B</span>
        <span class="brand-text">Bioskopp</span>
      </a>

      <nav class="nav-menu">
        <a href="<?= route_link('home') ?>" class="nav-link <?= $page === 'home' ? 'is-active' : '' ?>">Home</a>
        <a href="<?= route_link('movies') ?>" class="nav-link <?= $page === 'movies' ? 'is-active' : '' ?>">Movies</a>
        <a href="<?= route_link('schedule') ?>" class="nav-link <?= $page === 'schedule' ? 'is-active' : '' ?>">Schedule</a>
        <a href="<?= route_link('promotions') ?>" class="nav-link <?= $page === 'promotions' ? 'is-active' : '' ?>">Promos</a>
        <?php if ($userRole === 'operator' || $userRole === 'admin'): ?>
          <a href="<?= route_link('dashboard') ?>" class="nav-link <?= $page === 'dashboard' ? 'is-active' : '' ?>">
            <?= $userRole === 'admin' ? 'Admin Panel' : 'Operator Panel' ?>
          </a>
        <?php endif; ?>
      </nav>

      <form class="nav-search" method="GET" action="index.php" style="display: flex; gap: 8px; align-items: center; position: relative;">
        <input type="hidden" name="page" value="search">
        <input type="text" id="searchInput" name="q" placeholder="Cari film..." value="<?= htmlspecialchars($searchQuery) ?>" class="nav-search-input" autocomplete="off" style="width: 160px; padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.05); color: var(--text); font-size: .9rem;" />
        <div id="searchDropdown" class="search-dropdown" style="display: none;"></div>
        <button type="submit" class="btn btn-sm" style="padding: 6px 12px;">Cari</button>
      </form>

      <div class="nav-auth">
        <?php if (!$loggedIn): ?>
          <button class="btn btn-ghost" data-modal-open="loginModal">Login</button>
          <button class="btn btn-primary" data-modal-open="registerModal">Sign Up</button>
        <?php else: ?>
          <div class="user-pill">
            <?php
            $roleClass = match ($userRole) { 'admin' => 'role-admin', 'operator' => 'role-operator', default => 'role-user'};
            ?>
            <span class="nav-role-pill <?= $roleClass ?>"><?= htmlspecialchars((string) $userRole) ?></span>
            <span class="user-dot"></span>
            <span class="user-name">@<?= htmlspecialchars((string) $username) ?></span>
            <?php if ($userRole === 'user'): ?>
              <a class="btn btn-ghost btn-sm" href="<?= route_link('dashboard') ?>">Tiket Saya</a>
            <?php endif; ?>
            <form method="post" action="process.php" style="display:inline"><input type="hidden" name="action" value="logout"/><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/><button class="btn btn-ghost btn-sm" type="submit">Logout</button></form>
          </div>
        <?php endif; ?>
      </div>

      <!-- Hamburger (mobile) -->
      <button class="nav-hamburger" id="navHamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>

    <!-- Mobile drawer -->
    <nav class="nav-drawer" id="navDrawer">
      <a href="<?= route_link('home') ?>" class="nav-link <?= $page==='home'?'is-active':'' ?>">Home</a>
      <a href="<?= route_link('movies') ?>" class="nav-link <?= $page==='movies'?'is-active':'' ?>">Movies</a>
      <a href="<?= route_link('schedule') ?>" class="nav-link <?= $page==='schedule'?'is-active':'' ?>">Schedule</a>
      <a href="<?= route_link('promotions') ?>" class="nav-link <?= $page==='promotions'?'is-active':'' ?>">Promos</a>
      <?php if($userRole==='operator'||$userRole==='admin'): ?>
        <a href="<?= route_link('dashboard') ?>" class="nav-link <?= $page==='dashboard'?'is-active':'' ?>">
          <?= $userRole==='admin'?'Admin Panel':'Operator Panel' ?>
        </a>
      <?php endif; ?>
      <?php if(!$loggedIn): ?>
        <button class="btn btn-ghost" data-modal-open="loginModal" style="margin-top:6px">Login</button>
        <button class="btn btn-primary" data-modal-open="registerModal">Sign Up</button>
      <?php else: ?>
        <form method="post" action="process.php" style="display:inline;margin-top:6px"><input type="hidden" name="action" value="logout"/><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/><button class="btn btn-ghost btn-sm" type="submit">Logout</button></form>
      <?php endif; ?>
    </nav>
  </header>

  <main class="container">

    <?php if ($page === 'home'): ?>

      <section class="hero" aria-label="Hero">
        <div class="hero-slides" id="heroSlides">
          <?php foreach ($heroSlides as $i => $src): ?>
            <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>"
              style="background-image:url('<?= htmlspecialchars($src) ?>')"></div>
          <?php endforeach; ?>
        </div>
        <div class="hero-overlay">
          <div class="hero-content">
            <div class="hero-badge">CINEMA PREMIUM &bull; DARK MODE</div>
            <h1 class="hero-title">Tonton Film Favoritmu, Sekarang Juga.</h1>
            <p class="hero-desc">Booking cepat, tampilan elegan, jadwal realtime.</p>
            <div class="hero-actions">
              <button class="btn btn-ghost" type="button" id="btnTrailer">Watch Trailer</button>
              <a class="btn btn-primary" href="<?= route_link('schedule') ?>">Pesan Tiket</a>
            </div>
          </div>
        </div>
      </section>

      <!-- NOW SHOWING -->
      <hr class="section-divider">
      <section class="section">
        <div class="section-head">
          <h2>Now Showing</h2>
          <span class="muted">Sedang tayang hari ini</span>
        </div>
        <div class="grid grid-4">
          <?php if (!empty($nowShowing)): ?>
            <?php foreach ($nowShowing as $f): ?>
              <?php
              // nowShowing hanya film data (tanpa jadwal_id); arahkan ke schedule
              $f['jadwal_id'] = '';
              render_card_movie($f, false);
              ?>
            <?php endforeach; ?>
          <?php else: ?>
            <?php foreach ($popular as $f):
              render_card_movie($f, false); endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- TODAY SCHEDULE (dengan tombol booking langsung) -->
      <hr class="section-divider">
      <section class="section">
        <div class="section-head">
          <h2>Today Schedule</h2>
          <span class="muted">Pilih studio &amp; jam</span>
        </div>
        <div class="grid grid-4">
          <?php if (!empty($todaySchedule)): ?>
            <?php $cnt = 0;
            foreach ($todaySchedule as $j):
              if ($cnt++ >= 8)
                break;
              render_card_movie($j, false); endforeach; ?>
          <?php else: ?>
            <?php $cnt = 0;
            foreach ($jadwal->listComing(7) as $j):
              if ($cnt++ >= 8)
                break;
              render_card_movie($j, false); endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- COMING SOON -->
      <hr class="section-divider">
      <section class="section">
        <div class="section-head">
          <h2>Coming Soon</h2>
          <span class="muted">Beberapa hari ke depan</span>
        </div>
        <div class="grid grid-4">
          <?php $cnt = 0;
          foreach ($comingSoon as $j):
            if ($cnt++ >= 8)
              break;
            render_card_movie($j, false); endforeach; ?>
          <?php if (empty($comingSoon)):
            echo '<p class="muted">Jadwal mendatang belum tersedia.</p>'; endif; ?>
        </div>
      </section>

      <!-- POPULAR -->
      <hr class="section-divider">
      <section class="section">
        <div class="section-head">
          <h2>Popular Movies</h2>
          <span class="muted">Pilihan teratas</span>
        </div>
        <div class="grid grid-4">
          <?php foreach ($popular as $f):
            render_card_movie($f, false); endforeach; ?>
        </div>
      </section>

    <?php elseif ($page === 'movie'): ?>
      <?php if ($movieDetail):
        $movieSchedules = $jadwal->listByFilm((string)$movieDetail['film_id']);
      ?>
        <section class="section">
          <div style="display: flex; gap: 24px; margin-bottom: 32px;">
            <div style="flex-shrink: 0;">
              <img src="assets/images/<?= htmlspecialchars($movieDetail['poster']) ?>" alt="<?= htmlspecialchars($movieDetail['judul']) ?>" style="width: 180px; height: 280px; border-radius: 16px; object-fit: cover; border: 1px solid var(--line);" onerror="this.style.background='#1a1a2e'">
            </div>
            <div style="flex: 1;">
              <h1 style="font-size: 2rem; font-weight: 950; margin-bottom: 8px;"><?= htmlspecialchars($movieDetail['judul']) ?></h1>
              <div style="display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                <?php if (!empty($movieDetail['rating_usia'])): ?>
                  <span class="chip chip-red"><?= htmlspecialchars($movieDetail['rating_usia']) ?></span>
                <?php endif; ?>
                <?php if (!empty($movieDetail['genre'])): ?>
                  <span class="muted"><?= htmlspecialchars($movieDetail['genre']) ?></span>
                <?php endif; ?>
                <?php if (!empty($movieDetail['durasi_menit'])): ?>
                  <span class="muted">&bull; <?= (int)$movieDetail['durasi_menit'] ?> menit</span>
                <?php endif; ?>
              </div>
              <?php if (!empty($movieDetail['sinopsis'])): ?>
                <p class="muted" style="line-height: 1.6; margin-bottom: 20px;"><?= htmlspecialchars($movieDetail['sinopsis']) ?></p>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($movieSchedules)): ?>
            <div class="section-head"><h2>Jadwal Tayang</h2></div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:12px;">
              <?php foreach ($movieSchedules as $js): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:14px 18px;border:1px solid var(--line);border-radius:16px;background:rgba(255,255,255,.02);">
                  <div style="display:flex;gap:24px;flex-wrap:wrap;font-size:.92rem;">
                    <span><b><?= htmlspecialchars((string)$js['tanggal']) ?></b> &bull; <?= htmlspecialchars(substr((string)$js['jam'],0,5)) ?></span>
                    <span class="muted"><?= htmlspecialchars((string)$js['studio_nama']) ?></span>
                    <span style="color:var(--red);font-weight:800;"><?= Helper::money((int)$js['harga']) ?></span>
                  </div>
                  <?php if ($loggedIn): ?>
                    <a class="btn btn-primary btn-sm" href="<?= route_link('booking', ['jadwal_id' => $js['jadwal_id']]) ?>">Pesan Tiket</a>
                  <?php else: ?>
                    <button class="btn btn-primary btn-sm" data-modal-open="loginModal" 
                      onclick="document.querySelector('#loginModal input[name=redirect]').value = window.location.href">
                      Pesan Tiket
                    </button>
                  <?php endif; ?>
                  </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="muted" style="margin-top:16px;">Belum ada jadwal tayang untuk film ini.</p>
          <?php endif; ?>
        </section>
      <?php else: ?>
        <section class="section">
          <p class="muted" style="text-align: center; padding: 40px 0;">Film tidak ditemukan.</p>
        </section>
      <?php endif; ?>

    <?php elseif ($page === 'movies'): ?>
      <section class="section">
        <div class="section-head">
          <h2>Movies</h2>
          <span class="muted">Semua koleksi film</span>
        </div>
        <div class="grid grid-4">
          <?php foreach ($film->all() as $f):
            render_card_movie($f, false); endforeach; ?>
        </div>
      </section>

    <?php elseif ($page === 'search'): ?>
      <section class="section">
        <div class="section-head">
          <h2>Hasil Pencarian</h2>
          <span class="muted"><?php if (!empty($searchQuery)) echo 'Mencari: ' . htmlspecialchars($searchQuery); ?></span>
        </div>
        <div class="grid grid-4">
          <?php if (!empty($searchResults)): ?>
            <?php foreach ($searchResults as $f):
              render_card_movie($f, false); endforeach; ?>
          <?php else: ?>
            <p class="muted" style="grid-column: 1 / -1; padding: 20px 0; text-align: center;">
              <?php if (!empty($searchQuery)): ?>
                Tidak ada film yang cocok dengan pencarian "<?= htmlspecialchars($searchQuery) ?>"
              <?php else: ?>
                Masukkan kata kunci untuk mencari film
              <?php endif; ?>
            </p>
          <?php endif; ?>
        </div>
      </section>

    <?php elseif ($page === 'schedule'): ?>
      <?php
      $allSchedule = $jadwal->listComing(7);
      // Kelompokkan per tanggal
      $byDate = [];
      foreach ($allSchedule as $j) {
        $byDate[$j['tanggal']][] = $j;
      }
      $dates = array_keys($byDate);
      ?>
      <section class="section schedule-page">
        <div class="section-head">
          <h2>Schedule</h2>
          <span class="muted">Pilih tanggal &amp; pesan tiket</span>
        </div>
        <div class="schedule-layout">

          <!-- Sidebar tanggal -->
          <aside class="schedule-sidebar">
            <div class="sched-sidebar-title">Tanggal</div>
            <?php if (empty($dates)): ?>
              <p class="muted" style="font-size:.85rem;padding:8px">Tidak ada jadwal</p>
            <?php else: ?>
              <?php foreach ($dates as $i => $tgl): ?>
                <?php
                $ts = strtotime($tgl);
                $isToday = ($tgl === date('Y-m-d'));
                $label = $isToday ? 'Hari ini' : date('d M', $ts);
                $day = date('D', $ts);
                $dayId = 'sched-' . str_replace('-', '', $tgl);
                ?>
                <a href="#<?= $dayId ?>" class="sched-date-btn <?= $i === 0 ? 'is-active' : '' ?>" data-target="<?= $dayId ?>">
                  <span class="sched-day"><?= $day ?></span>
                  <span class="sched-label"><?= $label ?></span>
                  <span class="sched-count"><?= count($byDate[$tgl]) ?> film</span>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </aside>

          <!-- Konten jadwal per tanggal -->
          <div class="schedule-content">
            <?php if (empty($byDate)): ?>
              <p class="muted">Jadwal tidak tersedia saat ini.</p>
            <?php else: ?>
              <?php foreach ($byDate as $tgl => $items): ?>
                <?php
                $ts = strtotime($tgl);
                $isToday = ($tgl === date('Y-m-d'));
                $dayId = 'sched-' . str_replace('-', '', $tgl);
                $headLabel = $isToday
                  ? 'Hari ini — ' . date('l, d F Y', $ts)
                  : date('l, d F Y', $ts);
                ?>
                <div class="sched-group" id="<?= $dayId ?>">
                  <div class="sched-group-head">
                    <?php if ($isToday): ?><span class="sched-today-badge">TODAY</span><?php endif; ?>
                    <?= htmlspecialchars($headLabel) ?>
                  </div>
                  <div class="grid grid-3">
                    <?php foreach ($items as $j):
                      render_card_movie($j, false); endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      </section>

    <?php elseif ($page === 'promotions'): ?>
      <section class="section">
        <div class="section-head">
          <h2>Promotions</h2><span class="muted">Diskon premium</span>
        </div>
        <div class="promo-grid">
          <div class="promo-card">
            <div class="promo-title">Buy 2 Get 1</div>
            <div class="promo-desc">Transaksi online min. 2 tiket.</div>
            <div class="promo-badge">-33%</div>
          </div>
          <div class="promo-card">
            <div class="promo-title">Red Ticket</div>
            <div class="promo-desc">Diskon khusus jam 10.00&ndash;13.00.</div>
            <div class="promo-badge">HOT</div>
          </div>
          <div class="promo-card">
            <div class="promo-title">Student Night</div>
            <div class="promo-desc">Harga spesial untuk pelajar &amp; mahasiswa.</div>
            <div class="promo-badge">NEW</div>
          </div>
        </div>
      </section>

    <?php elseif ($page === 'booking'): ?>
      <?php
      if (!$loggedIn) {
        header('Location: index.php?page=home&err=login_empty');
        exit;
      }
      $jadwal_id = (string)($_GET['jadwal_id'] ?? '');
      $jd = $jadwal->find($jadwal_id);

      // Ambil data studio untuk layout kursi
      $studioData = null;
      $kursiTerpesan = [];
      $kapasitas = 100;
      if ($jd) {
        $studioData = $studio->find((string)$jd['studio_id']);
        $kapasitas  = (int)($studioData['kapasitas'] ?? 100);
        $kursiTerpesan = Kursi::getTerpesan($jadwal_id);
        $layout = Kursi::layout($kapasitas);
      }

      if (!$jd): ?>
        <div class="section">
          <div class="notice notice-warn">Jadwal tidak ditemukan. <a href="<?= route_link('schedule') ?>">Kembali ke Schedule</a></div>
        </div>
      <?php else: ?>
        <section class="section">
          <div class="section-head">
            <h2>Pesan Tiket</h2>
            <span class="muted"><?= htmlspecialchars((string)$jd['judul']) ?></span>
          </div>

          <?php if(isset($_GET['err']) && $_GET['err']==='seat_taken'): ?>
            <div class="notice notice-warn" style="margin-bottom:16px">
              &#9888; Kursi yang kamu pilih sudah dipesan orang lain. Silakan pilih kursi lain.
            </div>
          <?php endif; ?>

          <!-- Info film -->
          <div class="booking-card" style="margin-bottom:18px">
            <div class="booking-poster">
              <img src="assets/images/<?= htmlspecialchars((string)$jd['poster']) ?>"
                   alt="<?= htmlspecialchars((string)$jd['judul']) ?>">
            </div>
            <div class="booking-info">
              <div class="booking-title"><?= htmlspecialchars((string)$jd['judul']) ?></div>
              <div class="booking-sub muted">
                Studio: <?= htmlspecialchars((string)$jd['studio_nama']) ?><br>
                Tanggal: <?= htmlspecialchars((string)$jd['tanggal']) ?> &bull;
                Jam: <?= htmlspecialchars(substr((string)$jd['jam'],0,5)) ?><br>
                Kapasitas: <?= $kapasitas ?> kursi &bull;
                Sisa: <?= $kapasitas - count($kursiTerpesan) ?> kursi
              </div>
              <div class="booking-price">
                Harga/tiket: <span class="price-strong"><?= Helper::money((int)$jd['harga']) ?></span>
              </div>
              <div class="muted" style="margin-top:6px">
                Rating: <?= htmlspecialchars((string)($jd['rating_usia']??'-')) ?> &bull;
                <?= (int)($jd['durasi_menit']??0) ?> mnt &bull;
                <?= htmlspecialchars((string)($jd['genre']??'')) ?>
              </div>
            </div>
          </div>

          <?php if(!$loggedIn): ?>
            <div class="booking-form-card">
              <div class="notice notice-warn">Login terlebih dahulu untuk memesan tiket.</div>
              <div class="form-actions">
                <button class="btn btn-primary" data-modal-open="loginModal">Login</button>
                <a class="btn btn-ghost" href="<?= route_link('schedule') ?>">Kembali</a>
              </div>
            </div>
          <?php else: ?>
            <form class="form" method="post" action="process.php" id="bookingForm">
              <input type="hidden" name="action" value="booking_create"/>
              <input type="hidden" name="jadwal_id" value="<?= htmlspecialchars($jadwal_id) ?>"/>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"/>
              <!-- kursi dipilih akan di-append via JS sebagai hidden inputs -->

              <div style="display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:flex-start">

                <!-- Kolom kiri: Seat picker -->
                <div class="booking-form-card">
                  <h3 style="font-weight:900;font-size:1rem;margin-bottom:4px">Pilih Kursi</h3>
                  <p class="muted" style="font-size:.85rem;margin-bottom:16px">
                    Maks. 10 kursi per transaksi. Klik kursi untuk memilih/batal.
                  </p>

                  <!-- Grid kursi -->
                  <div class="seat-grid-wrap">
                    <div style="display:flex;align-items:center;">
                      <div class="seat-row-label" style="visibility:hidden">A</div>
                      <div class="seat-screen" style="flex:1;margin-bottom:20px;">
                        <div class="seat-screen-bar"></div>
                        <div class="seat-screen-label">Layar / Screen</div>
                      </div>
                    </div>
                    <div class="seat-grid" id="seatGrid">
                      <?php
                      $cols = $layout['cols'];
                      $rows = $layout['rows'];
                      $terpesan = array_flip($kursiTerpesan); // O(1) lookup

                      for ($r = 0; $r < $rows; $r++):
                        $rowLabel = chr(65 + $r);
                      ?>
                        <div class="seat-row">
                          <div class="seat-row-label"><?= $rowLabel ?></div>
                          <?php
                          // Gap di tengah setelah kolom ke-$gapAt
                          $gapAt = (int)floor($cols / 2);
                          for ($c = 1; $c <= $cols; $c++):
                            // Seat number: A1, A2, ...
                            $nomor = $rowLabel . $c;
                            $taken = isset($terpesan[$nomor]);
                            $cls   = $taken ? 'is-taken' : '';
                            $title = $taken ? 'Kursi sudah terpesan' : $nomor;
                          ?>
                            <?php if($c === $gapAt + 1): ?>
                              <div class="seat-btn is-gap"></div>
                            <?php endif; ?>
                            <button type="button"
                              class="seat-btn <?= $cls ?>"
                              data-seat="<?= $nomor ?>"
                              title="<?= $title ?>"
                              <?= $taken ? 'disabled' : '' ?>
                            ></button>
                          <?php endfor; ?>
                        </div>
                      <?php endfor; ?>
                    </div>
                  </div>

                  <!-- Legenda -->
                  <div class="seat-legend">
                    <div class="seat-legend-item">
                      <div class="legend-box legend-available"></div>
                      <span>Tersedia</span>
                    </div>
                    <div class="seat-legend-item">
                      <div class="legend-box legend-selected"></div>
                      <span>Dipilih</span>
                    </div>
                    <div class="seat-legend-item">
                      <div class="legend-box legend-taken"></div>
                      <span>Terpesan</span>
                    </div>
                  </div>
                </div>

                <!-- Kolom kanan: ringkasan & metode bayar -->
                <div style="display:flex;flex-direction:column;gap:14px;position:sticky;top:82px">
                  <div class="booking-form-card">
                    <h3 style="font-weight:900;font-size:1rem;margin-bottom:14px">Ringkasan</h3>

                    <div class="seat-summary" id="seatSummary">
                      <span class="muted">Belum ada kursi dipilih.</span>
                    </div>

                    <div style="margin-top:16px;padding:14px;border:1px solid rgba(255,255,255,.07);border-radius:14px;background:rgba(255,255,255,.02)">
                      <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.9rem">
                        <span class="muted">Harga per tiket</span>
                        <span style="font-weight:700"><?= Helper::money((int)$jd['harga']) ?></span>
                      </div>
                      <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.9rem">
                        <span class="muted">Jumlah kursi</span>
                        <span style="font-weight:700" id="countDisplay">0</span>
                      </div>
                      <div style="border-top:1px solid rgba(255,255,255,.07);padding-top:10px;display:flex;justify-content:space-between">
                        <span style="font-weight:800">Total</span>
                        <span style="font-weight:900;color:var(--red)" id="totalDisplay">Rp 0</span>
                      </div>
                    </div>

                    <label class="label" style="margin-top:16px">Metode Pembayaran</label>
                    <?php if($userRole === 'operator' || $userRole === 'admin'): ?>
  <label class="label" style="margin-top:16px">Nama Pelanggan</label>
  <input class="input" type="text" name="nama_pelanggan" 
    placeholder="Nama pelanggan walk-in" required
    style="margin-bottom:12px"/>
<?php endif; ?>
                    <div class="radio-grid">
                      <?php foreach(['Tunai','QRIS','Transfer Bank','Kartu Debit','Kartu Kredit'] as $mt): ?>
                        <label class="radio-pill">
                          <input type="radio" name="metode" value="<?= htmlspecialchars($mt) ?>" <?= $mt==='QRIS'?'checked':'' ?>>
                          <span><?= htmlspecialchars($mt) ?></span>
                        </label>
                      <?php endforeach; ?>
                    </div>

                    <div class="form-actions" style="margin-top:18px">
                      <button class="btn btn-primary" type="submit" id="btnSubmitBooking" disabled style="width:100%;opacity:.5">
                        Pesan Tiket
                      </button>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="<?= route_link('schedule') ?>"
                       style="display:block;text-align:center;margin-top:8px">&#8592; Kembali ke Jadwal</a>
                  </div>
                </div>

              </div><!-- /grid -->
            </form>

            <script>
            (function(){
              var harga = <?= (int)$jd['harga'] ?>;
              var selected = [];
              var MAX = 10;

              var grid    = document.getElementById('seatGrid');
              var summary = document.getElementById('seatSummary');
              var countEl = document.getElementById('countDisplay');
              var totalEl = document.getElementById('totalDisplay');
              var submitBtn = document.getElementById('btnSubmitBooking');
              var form    = document.getElementById('bookingForm');

              function formatRp(n){
                return 'Rp ' + n.toLocaleString('id-ID');
              }

              function updateUI(){
                var n = selected.length;
                countEl.textContent = n;
                totalEl.textContent = formatRp(n * harga);
                if(n === 0){
                  summary.innerHTML = '<span class="muted">Belum ada kursi dipilih.</span>';
                  submitBtn.disabled = true;
                  submitBtn.style.opacity = '.5';
                } else {
                  summary.innerHTML = '<strong>' + n + ' kursi dipilih:</strong> ' + selected.join(', ');
                  submitBtn.disabled = false;
                  submitBtn.style.opacity = '1';
                  submitBtn.textContent = 'Pesan ' + n + ' Tiket — ' + formatRp(n * harga);
                }
              }

              grid.addEventListener('click', function(e){
                var btn = e.target.closest('.seat-btn');
                if(!btn || btn.classList.contains('is-taken') || btn.classList.contains('is-gap')) return;
                var seat = btn.dataset.seat;
                var idx = selected.indexOf(seat);
                if(idx === -1){
                  if(selected.length >= MAX){
                    showToast('Maksimal ' + MAX + ' kursi per transaksi.');
                    return;
                  }
                  selected.push(seat);
                  btn.classList.add('is-selected');
                } else {
                  selected.splice(idx, 1);
                  btn.classList.remove('is-selected');
                }
                updateUI();
              });

              // Sebelum submit: inject hidden inputs kursi[]
              form.addEventListener('submit', function(e){
                if(selected.length === 0){ e.preventDefault(); return; }
                // Hapus hidden inputs lama
                form.querySelectorAll('input[name="kursi[]"]').forEach(function(el){ el.remove(); });
                selected.forEach(function(seat){
                  var inp = document.createElement('input');
                  inp.type = 'hidden';
                  inp.name = 'kursi[]';
                  inp.value = seat;
                  form.appendChild(inp);
                });
              });

              updateUI();
            })();
            </script>
          <?php endif; ?>
        </section>
      <?php endif; ?>

    <?php elseif ($page === 'dashboard'): ?>
      <?php if (!$loggedIn): ?>
        <div class="section">
          <div class="notice notice-warn">Silakan <button class="btn btn-primary btn-sm" data-modal-open="loginModal">login</button> terlebih dahulu.</div>
        </div>
      <?php else: ?>
        <section class="section dashboard">
          <div class="dash-layout">

            <!-- Sidebar -->
            <aside class="dash-sidebar">
              <div class="dash-user">
                <div class="avatar"></div>
                <div>
                  <div class="dash-username">@<?= htmlspecialchars((string)$username) ?></div>
                  <?php $roleClass = match($userRole){ 'admin'=>'role-admin','operator'=>'role-operator',default=>'role-user'}; ?>
                  <div class="dash-role">Role: <span class="chip <?= $roleClass ?>"><?= htmlspecialchars((string)$userRole) ?></span></div>
                </div>
              </div>
              <div class="dash-links">
                <?php if ($userRole === 'admin'): ?>
                  <a class="dash-link <?= $activeTab==='transaksi'?'is-active':'' ?>" href="<?= route_link('dashboard',['tab'=>'transaksi']) ?>">&#9632; Transaksi</a>
                  <a class="dash-link <?= $activeTab==='film'?'is-active':'' ?>" href="<?= route_link('dashboard',['tab'=>'film']) ?>">&#127916; Kelola Film</a>
                  <a class="dash-link <?= $activeTab==='studio'?'is-active':'' ?>" href="<?= route_link('dashboard',['tab'=>'studio']) ?>">&#127970; Kelola Studio</a>
                  <a class="dash-link <?= $activeTab==='jadwal'?'is-active':'' ?>" href="<?= route_link('dashboard',['tab'=>'jadwal']) ?>">&#128197; Kelola Jadwal</a>
                  <a class="dash-link <?= $activeTab==='users'?'is-active':'' ?>" href="<?= route_link('dashboard',['tab'=>'users']) ?>">&#128101; Data User</a>
                <?php elseif ($userRole === 'operator'): ?>
                  <a class="dash-link is-active" href="<?= route_link('dashboard') ?>">Operator Queue</a>
                <?php else: ?>
                  <a class="dash-link is-active" href="<?= route_link('dashboard') ?>">Tiket Saya</a>
                  <a class="dash-link" href="<?= route_link('schedule') ?>">Pesan Tiket</a>
                <?php endif; ?>
              </div>
              <div class="dash-footer">
                <form method="post" action="process.php" style="display:inline"><input type="hidden" name="action" value="logout"/><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/><button class="dash-link" type="submit" style="background:none;border:none;cursor:pointer;font:inherit;padding:0;color:inherit;width:100%;text-align:left">Logout</button></form>
              </div>
            </aside>

            <!-- Main content -->
            <div class="dash-main">
<?php if (isset($_GET['ok'])): ?>
<div class="notice notice-ok" style="margin-bottom:14px;padding:10px 16px;border-radius:8px;background:rgba(34,197,94,.12);color:#22c55e;border:1px solid rgba(34,197,94,.25);">
  &#10003; Operasi berhasil.
</div>
<?php endif; ?>

              <?php if ($userRole === 'user'): ?>
                <!-- ===== USER DASHBOARD ===== -->
                <div class="stat-grid">
                  <div class="stat-card"><div class="stat-title">Total Pesanan</div><div class="stat-value"><?= count($myTransactions) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Pending</div><div class="stat-value"><?= count(array_filter($myTransactions,fn($t)=>$t['status']==='pending')) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Confirmed</div><div class="stat-value"><?= count(array_filter($myTransactions,fn($t)=>$t['status']==='confirmed')) ?></div></div>
                </div>
                <div class="table-card">
                  <div class="table-head">Riwayat Tiket &nbsp;<a class="btn btn-primary btn-sm" href="<?= route_link('schedule') ?>" style="float:right">+ Pesan Tiket Baru</a></div>
                  <div class="dash-search-bar">
                    <input class="input dash-search-input" type="search" placeholder="&#128269; Cari film, studio, metode..." data-search-target="tblUser" autocomplete="off"/>
                    <select class="input dash-filter-select" data-filter-col="7" data-filter-target="tblUser">
                      <option value="">Semua Status</option>
                      <option value="pending">Pending</option>
                      <option value="confirmed">Confirmed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>
                  <div class="table-wrap">
                    <table class="tbl" id="tblUser">
                      <thead><tr><th>ID</th><th>Film</th><th>Studio</th><th>Tanggal</th><th>Jam</th><th>Tiket</th><th>Metode</th><th>Status</th><th>Total</th><th>Aksi</th></tr></thead>
                      <tbody>
                        <?php if (empty($myTransactions)): ?>
                          <tr><td colspan="10" style="text-align:center;padding:24px" class="muted">Belum ada pemesanan. <a href="<?= route_link('schedule') ?>">Pesan tiket sekarang</a></td></tr>
                        <?php endif; ?>
                        <?php foreach($myTransactions as $t): ?>
                          <?php $st=$t['status']; $cls=$st==='confirmed'?'tag tag-ok':($st==='cancelled'?'tag tag-cancel':'tag tag-warn'); ?>
                          <tr>
                            <td><?= e((string)$t['transaksi_id']) ?></td>
                            <td><?= e((string)$t['judul']) ?></td>
                            <td><?= e((string)$t['studio_nama']) ?></td>
                            <td><?= e((string)$t['tanggal']) ?></td>
                            <td><?= e((string)$t['jam']) ?></td>
                            <td style="text-align:center"><?= (int)$t['jumlah_tiket'] ?></td>
                            <td><?= e((string)$t['metode_pembayaran']) ?></td>
                            <td><span class="<?= $cls ?>"><?= e($st) ?></span></td>
                            <td><?= Helper::money((int)$t['total_harga']) ?></td>
                            <td>
                              <?php if($st==='pending'): ?>
                                <form method="post" action="process.php" style="display:inline">
                                  <input type="hidden" name="action" value="booking_cancel"/>
                                  <input type="hidden" name="transaksi_id" value="<?= e((string)$t['transaksi_id']) ?>"/>
                                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                  <button class="btn btn-ghost btn-sm" onclick="return confirm('Batalkan pesanan ini?')">Batalkan</button>
                                </form>
                              <?php else: ?><span class="muted">&mdash;</span><?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

              <?php elseif ($userRole === 'operator'): ?>
                <!-- ===== OPERATOR DASHBOARD ===== -->
                <div class="stat-grid">
                  <div class="stat-card"><div class="stat-title">Antrian Pending</div><div class="stat-value"><?= count($pendingForOperator) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Mode</div><div class="stat-value" style="font-size:1rem">Offline Confirm</div></div>
                </div>
                <div class="table-card">
                  <div class="table-head">Antrian Konfirmasi Operator</div>
                  <div class="dash-search-bar">
                    <input class="input dash-search-input" type="search" placeholder="&#128269; Cari pelanggan, film, studio, metode..." data-search-target="tblOperator" autocomplete="off"/>
                  </div>
                  <div class="table-wrap">
                    <table class="tbl" id="tblOperator">
                      <thead><tr><th>ID</th><th>Pelanggan</th><th>Film</th><th>Studio</th><th>Tanggal</th><th>Jam</th><th>Tiket</th><th>Metode</th><th>Total</th><th>Aksi</th></tr></thead>
                      <tbody>
                        <?php if(empty($pendingForOperator)): ?>
                          <tr><td colspan="10" style="text-align:center;padding:24px" class="muted">Tidak ada antrian pending.</td></tr>
                        <?php endif; ?>
                        <?php foreach($pendingForOperator as $t): ?>
                          <tr>
                            <td><?= e((string)$t['transaksi_id']) ?></td>
                            <?php
                            $namaDisplay = $t['pelanggan_id'] === 'P000'
                              ? explode(' | ', str_replace('Walk-in: ', '', (string)$t['catatan']))[0]
                              : (string)$t['pelanggan_nama'];
                            ?>
                            <td><?= e($namaDisplay) ?></td>
                            <td><?= e((string)$t['judul']) ?></td>
                            <td><?= e((string)$t['studio_nama']) ?></td>
                            <td><?= e((string)$t['tanggal']) ?></td>
                            <td><?= e((string)$t['jam']) ?></td>
                            <td style="text-align:center"><?= (int)$t['jumlah_tiket'] ?></td>
                            <td><?= e((string)$t['metode_pembayaran']) ?></td>
                            <td><?= Helper::money((int)$t['total_harga']) ?></td>
                            <td>
                              <form method="post" action="process.php" style="display:inline">
                                <input type="hidden" name="action" value="operator_confirm"/>
                                <input type="hidden" name="transaksi_id" value="<?= e((string)$t['transaksi_id']) ?>"/>
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                <button class="btn btn-primary btn-sm">Konfirmasi</button>
                              </form>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

              <?php else: /* ADMIN */ ?>
                <!-- ===== ADMIN STATS ===== -->
                <div class="stat-grid">
                  <div class="stat-card"><div class="stat-title">Total Transaksi</div><div class="stat-value"><?= (int)($adminStats['total']??0) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Pending</div><div class="stat-value" style="color:#eab308"><?= (int)($adminStats['pending']??0) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Confirmed</div><div class="stat-value" style="color:#22c55e"><?= (int)($adminStats['confirmed']??0) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Cancelled</div><div class="stat-value" style="color:#ef4444"><?= (int)($adminStats['cancelled']??0) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Revenue</div><div class="stat-value" style="font-size:1rem;color:#a78bfa"><?= Helper::money((int)($adminStats['revenue']??0)) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Total Film</div><div class="stat-value"><?= count($adminFilms) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Total Studio</div><div class="stat-value"><?= count($adminStudios) ?></div></div>
                  <div class="stat-card"><div class="stat-title">Total Jadwal</div><div class="stat-value"><?= count($adminJadwals) ?></div></div>
                </div>

                <!-- ===== TAB: TRANSAKSI ===== -->
                <?php if($activeTab==='transaksi'): ?>
                <div class="table-card">
                  <div class="table-head">Semua Transaksi</div>
                  <div class="dash-search-bar">
                    <input class="input dash-search-input" type="search" placeholder="&#128269; Cari ID, pelanggan, film, studio, metode..." data-search-target="tblAdminTrx" autocomplete="off"/>
                    <select class="input dash-filter-select" data-filter-col="7" data-filter-target="tblAdminTrx">
                      <option value="">Semua Status</option>
                      <option value="pending">Pending</option>
                      <option value="confirmed">Confirmed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>
                  <div class="table-wrap">
                    <table class="tbl" id="tblAdminTrx">
                      <thead><tr><th>ID</th><th>Pelanggan</th><th>Film</th><th>Studio</th><th>Tanggal</th><th>Tiket</th><th>Metode</th><th>Status</th><th>Total</th><th>Aksi</th></tr></thead>
                      <tbody>
                        <?php if(empty($adminTransactions)): ?>
                          <tr><td colspan="10" style="text-align:center;padding:24px" class="muted">Belum ada transaksi.</td></tr>
                        <?php endif; ?>
                        <?php foreach($adminTransactions as $t): ?>
                          <?php $st=$t['status']; $cls=$st==='confirmed'?'tag tag-ok':($st==='cancelled'?'tag tag-cancel':'tag tag-warn'); ?>
                          <tr>
                            <td><?= e((string)$t['transaksi_id']) ?></td>
                            <?php
                            $namaDisplay = $t['pelanggan_id'] === 'P000'
                              ? explode(' | ', str_replace('Walk-in: ', '', (string)$t['catatan']))[0]
                              : (string)$t['pelanggan_nama'];
                            ?>
                            <td><?= e($namaDisplay) ?></td>
                            <td><?= e((string)$t['judul']) ?></td>
                            <td><?= e((string)$t['studio_nama']) ?></td>
                            <td><?= e((string)$t['tanggal']) ?></td>
                            <td style="text-align:center"><?= (int)$t['jumlah_tiket'] ?></td>
                            <td><?= e((string)$t['metode_pembayaran']) ?></td>
                            <td><span class="<?= $cls ?>"><?= e($st) ?></span></td>
                            <td><?= Helper::money((int)$t['total_harga']) ?></td>
                            <td>
                              <div class="btn-row">
                                <?php if($st==='pending'): ?>
                                  <form method="post" action="process.php" style="display:inline">
                                    <input type="hidden" name="action" value="admin_confirm"/>
                                    <input type="hidden" name="transaksi_id" value="<?= e((string)$t['transaksi_id']) ?>"/>
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                    <button class="btn btn-primary btn-sm">Konfirmasi</button>
                                  </form>
                                  <form method="post" action="process.php" style="display:inline">
                                    <input type="hidden" name="action" value="admin_cancel"/>
                                    <input type="hidden" name="transaksi_id" value="<?= e((string)$t['transaksi_id']) ?>"/>
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                    <button class="btn btn-ghost btn-sm" onclick="return confirm('Batalkan transaksi ini?')">Batalkan</button>
                                  </form>
                                <?php elseif($st==='confirmed'): ?>
                                  <form method="post" action="process.php" style="display:inline">
                                    <input type="hidden" name="action" value="admin_cancel"/>
                                    <input type="hidden" name="transaksi_id" value="<?= e((string)$t['transaksi_id']) ?>"/>
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                    <button class="btn btn-ghost btn-sm" style="color:#ef4444" onclick="return confirm('Batalkan transaksi yang sudah confirmed?')">Cancel</button>
                                  </form>
                                <?php else: ?><span class="muted">&mdash;</span><?php endif; ?>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- ===== TAB: FILM ===== -->
                <?php elseif($activeTab==='film'): ?>
                <div class="table-card">
                  <div class="table-head">Kelola Film
                    <button class="btn btn-primary btn-sm" style="float:right" data-modal-open="modalFilmCreate">+ Tambah Film</button>
                  </div>
                  <div class="dash-search-bar">
                    <input class="input dash-search-input" type="search" placeholder="&#128269; Cari judul, genre, rating..." data-search-target="tblAdminFilm" autocomplete="off"/>
                  </div>
                  <div class="table-wrap">
                    <table class="tbl" id="tblAdminFilm">
                      <thead><tr><th>ID</th><th>Judul</th><th>Genre</th><th>Durasi</th><th>Rating</th><th>Poster</th><th>Aksi</th></tr></thead>
                      <tbody>
                        <?php if(empty($adminFilms)): ?>
                          <tr><td colspan="7" style="text-align:center;padding:24px" class="muted">Belum ada film.</td></tr>
                        <?php endif; ?>
                        <?php foreach($adminFilms as $f): ?>
                          <tr>
                            <td><?= e((string)$f['film_id']) ?></td>
                            <td><?= e((string)$f['judul']) ?></td>
                            <td><?= e((string)$f['genre']) ?></td>
                            <td><?= (int)$f['durasi_menit'] ?> mnt</td>
                            <td><?= e((string)$f['rating_usia']) ?></td>
                            <td style="font-size:.8rem;color:var(--muted)"><?= e((string)$f['poster']) ?></td>
                            <td>
                              <div class="btn-row">
                                <button class="btn btn-ghost btn-sm"
                                  onclick="openEditFilm(<?= htmlspecialchars(json_encode($f),ENT_QUOTES) ?>)">Edit</button>
                                <form method="post" action="process.php" style="display:inline">
                                  <input type="hidden" name="action" value="admin_film_delete"/>
                                  <input type="hidden" name="film_id" value="<?= e((string)$f['film_id']) ?>"/>
                                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                  <button class="btn btn-ghost btn-sm" style="color:#ef4444" onclick="return confirm('Hapus film ini?')">Hapus</button>
                                </form>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- ===== TAB: STUDIO ===== -->
                <?php elseif($activeTab==='studio'): ?>
                <div class="table-card">
                  <div class="table-head">Kelola Studio
                    <button class="btn btn-primary btn-sm" style="float:right" data-modal-open="modalStudioCreate">+ Tambah Studio</button>
                  </div>
                  <div class="dash-search-bar">
                    <input class="input dash-search-input" type="search" placeholder="&#128269; Cari nama studio..." data-search-target="tblAdminStudio" autocomplete="off"/>
                  </div>
                  <div class="table-wrap">
                    <table class="tbl" id="tblAdminStudio">
                      <thead><tr><th>ID</th><th>Nama Studio</th><th>Kapasitas</th><th>Aksi</th></tr></thead>
                      <tbody>
                        <?php if(empty($adminStudios)): ?>
                          <tr><td colspan="4" style="text-align:center;padding:24px" class="muted">Belum ada studio.</td></tr>
                        <?php endif; ?>
                        <?php foreach($adminStudios as $s): ?>
                          <tr>
                            <td><?= e((string)$s['studio_id']) ?></td>
                            <td><?= e((string)$s['nama']) ?></td>
                            <td><?= (int)$s['kapasitas'] ?> kursi</td>
                            <td>
                              <div class="btn-row">
                                <button class="btn btn-ghost btn-sm"
                                  onclick="openEditStudio(<?= htmlspecialchars(json_encode($s),ENT_QUOTES) ?>)">Edit</button>
                                <form method="post" action="process.php" style="display:inline">
                                  <input type="hidden" name="action" value="admin_studio_delete"/>
                                  <input type="hidden" name="studio_id" value="<?= e((string)$s['studio_id']) ?>"/>
                                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                  <button class="btn btn-ghost btn-sm" style="color:#ef4444" onclick="return confirm('Hapus studio ini?')">Hapus</button>
                                </form>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- ===== TAB: JADWAL ===== -->
                <?php elseif($activeTab==='jadwal'): ?>
                <div class="table-card">
                  <div class="table-head">Kelola Jadwal Tayang
                    <button class="btn btn-primary btn-sm" style="float:right" data-modal-open="modalJadwalCreate">+ Tambah Jadwal</button>
                  </div>
                  <div class="dash-search-bar">
                    <input class="input dash-search-input" type="search" placeholder="&#128269; Cari film, studio, tanggal..." data-search-target="tblAdminJadwal" autocomplete="off"/>
                  </div>
                  <div class="table-wrap">
                    <table class="tbl" id="tblAdminJadwal">
                      <thead><tr><th>ID</th><th>Film</th><th>Studio</th><th>Tanggal</th><th>Jam</th><th>Harga</th><th>Aksi</th></tr></thead>
                      <tbody>
                        <?php if(empty($adminJadwals)): ?>
                          <tr><td colspan="7" style="text-align:center;padding:24px" class="muted">Belum ada jadwal.</td></tr>
                        <?php endif; ?>
                        <?php foreach($adminJadwals as $j): ?>
                          <tr>
                            <td><?= e((string)$j['jadwal_id']) ?></td>
                            <td><?= e((string)$j['judul']) ?></td>
                            <td><?= e((string)$j['studio_nama']) ?></td>
                            <td><?= e((string)$j['tanggal']) ?></td>
                            <td><?= e((string)$j['jam']) ?></td>
                            <td><?= Helper::money((int)$j['harga']) ?></td>
                            <td>
                              <div class="btn-row">
                                <button class="btn btn-ghost btn-sm"
                                  onclick="openEditJadwal(<?= htmlspecialchars(json_encode($j),ENT_QUOTES) ?>)">Edit</button>
                                <form method="post" action="process.php" style="display:inline">
                                  <input type="hidden" name="action" value="admin_jadwal_delete"/>
                                  <input type="hidden" name="jadwal_id" value="<?= e((string)$j['jadwal_id']) ?>"/>
                                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                  <button class="btn btn-ghost btn-sm" style="color:#ef4444" onclick="return confirm('Hapus jadwal ini?')">Hapus</button>
                                </form>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- ===== TAB: USERS ===== -->
                <?php elseif($activeTab==='users'): ?>
                <div class="table-card">
                  <div class="table-head">Data User
                    <button class="btn btn-primary btn-sm" style="float:right" data-modal-open="modalUserCreate">+ Tambah User</button>
                  </div>
                  <div class="dash-search-bar">
                    <input class="input dash-search-input" type="search" placeholder="&#128269; Cari username, nama, email, HP..." data-search-target="tblAdminUsers" autocomplete="off"/>
                    <select class="input dash-filter-select" data-filter-col="2" data-filter-target="tblAdminUsers">
                      <option value="">Semua Role</option>
                      <option value="admin">Admin</option>
                      <option value="operator">Operator</option>
                      <option value="user">User</option>
                    </select>
                  </div>
                  <div class="table-wrap">
                    <table class="tbl" id="tblAdminUsers">
                      <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Nama</th><th>Email</th><th>HP</th><th>Daftar</th><th>Aksi</th></tr></thead>
                      <tbody>
                        <?php if(empty($adminUsers)): ?>
                          <tr><td colspan="8" style="text-align:center;padding:24px" class="muted">Tidak ada data user.</td></tr>
                        <?php endif; ?>
                        <?php foreach($adminUsers as $u): ?>
                          <?php $rc=match((string)$u['role']){'admin'=>'role-admin','operator'=>'role-operator',default=>'role-user'}; ?>
                          <tr>
                            <td><?= (int)$u['id'] ?></td>
                            <td><?= e((string)$u['username']) ?></td>
                            <td><span class="chip <?= $rc ?>"><?= e((string)$u['role']) ?></span></td>
                            <td><?= e((string)($u['nama']??'-')) ?></td>
                            <td><?= e((string)($u['email']??'-')) ?></td>
                            <td><?= e((string)($u['phone']??'-')) ?></td>
                            <td style="font-size:.8rem"><?= e(substr((string)$u['created_at'],0,10)) ?></td>
                            <td>
                              <div class="btn-row">
                                <?php if((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                  <button class="btn btn-ghost btn-sm" onclick="openUserDetail(<?= (int)$u['id'] ?>, '<?= e((string)$u['username']) ?>')">Lihat</button>
                                  <button class="btn btn-ghost btn-sm" onclick="openUserEdit(<?= (int)$u['id'] ?>, '<?= e((string)$u['username']) ?>', '<?= e((string)($u['nama']??'')) ?>', '<?= e((string)($u['email']??'')) ?>', '<?= e((string)($u['phone']??'')) ?>', '<?= e((string)$u['role']) ?>')">Edit</button>
                                  <form method="post" action="process.php" style="display:inline">
                                    <input type="hidden" name="action" value="admin_user_delete"/>
                                    <input type="hidden" name="del_user_id" value="<?= (int)$u['id'] ?>"/>
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
                                    <button class="btn btn-ghost btn-sm" style="color:#ef4444"
                                      onclick="return confirm('Hapus user <?= e((string)$u['username']) ?>? Semua datanya akan terhapus.')">Hapus</button>
                                  </form>
                                <?php else: ?><span class="muted">(Anda)</span><?php endif; ?>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <?php endif; ?>
              <?php endif; ?>

            </div><!-- dash-main -->
          </div><!-- dash-layout -->
        </section>
      <?php endif; ?>

    <?php else: ?>
      <section class="section">
        <div class="notice notice-warn">Halaman tidak tersedia.</div>
      </section>
    <?php endif; ?>
  </main>

  <!-- ===== MODAL: TRAILER ===== -->
  <div class="modal" id="trailerModal" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head">
        <div class="modal-title">Watch Trailer</div>
        <button class="icon-btn" data-modal-close="trailerModal">&#10005;</button>
      </div>
      <div class="modal-body" style="padding:0;aspect-ratio:16/9;background:#000;border-radius:0 0 16px 16px;overflow:hidden;">
        <iframe
          id="trailerFrame"
          src="https://www.youtube.com/embed/videoseries?list=PLbpi6ZahtOH6Ar_3GPy3workHCpMq7Gej"
          style="width:100%;height:100%;border:none;"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen>
        </iframe>
      </div>
    </div>
  </div>

  <!-- ===== MODAL: LOGIN ===== -->
  <div class="modal" id="loginModal" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head">
        <div class="modal-title">Login</div>
        <button class="icon-btn" data-modal-close="loginModal">&#10005;</button>
      </div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="login"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>"/>
        <label class="label">Username</label>
        <input class="input" type="text" name="username" required autocomplete="username"/>
        <label class="label">Password</label>
        <div class="pwd-wrap"><input class="input" type="password" name="password" required autocomplete="current-password"/><button type="button" class="pwd-eye" onclick="togglePwd(this)" tabindex="-1" aria-label="Tampilkan password">&#128065;</button></div>
        <div id="loginErrBox" style="display:none;padding:8px 12px;border-radius:8px;background:rgba(239,68,68,.12);color:#ef4444;font-size:.85rem;margin-top:4px"></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Masuk</button></div>
        <div style="margin-top:12px;font-size:.88rem;text-align:center;">
          Belum punya akun? <button type="button" class="btn btn-sm" data-modal-close="loginModal" data-modal-open="registerModal">Daftar sekarang</button>
        </div>
        <div class="muted" style="margin-top:10px;font-size:.82rem;display:flex;flex-direction:column;gap:4px;">
          <span>Demo &rarr;</span>
          <span><b>admin</b>/admin123</span>
          <span><b>operator</b>/operator123</span>
          <span><b>demo</b>/demo123</span>
        </div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: REGISTER ===== -->
  <div class="modal" id="registerModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-wide">
      <div class="modal-head">
        <div class="modal-title">&#127916; Buat Akun Bioskopp</div>
        <button class="icon-btn" data-modal-close="registerModal">&#10005;</button>
      </div>
      <form class="modal-body form" method="post" action="process.php" id="regForm">
        <input type="hidden" name="action" value="register"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>

        <div class="field-grid">
          <div>
            <label class="label">Nama Lengkap <span style="color:var(--red)">*</span></label>
            <input class="input" type="text" name="nama" required placeholder="Nama sesuai KTP" autocomplete="name"/>
          </div>
          <div>
            <label class="label">Username <span style="color:var(--red)">*</span></label>
            <input class="input" type="text" name="username" required placeholder="Hanya huruf, angka, _"
              pattern="[a-zA-Z0-9_]{3,30}" title="3-30 karakter, huruf/angka/underscore" autocomplete="username"/>
            <div class="input-hint">Min. 3 karakter, tanpa spasi</div>
          </div>
        </div>

        <div class="field-grid">
          <div>
            <label class="label">Alamat Email <span style="color:var(--red)">*</span></label>
            <input class="input" type="email" name="email" required placeholder="nama@email.com" autocomplete="email"/>
          </div>
          <div>
            <label class="label">No. HP / WhatsApp</label>
            <input class="input" type="tel" name="phone" placeholder="08xx-xxxx-xxxx" autocomplete="tel"/>
            <div class="input-hint">Opsional, untuk notifikasi booking</div>
          </div>
        </div>

        <div class="field-grid">
          <div>
            <label class="label">Password <span style="color:var(--red)">*</span></label>
            <div class="pwd-wrap"><input class="input" type="password" name="password" id="regPwd" required
              placeholder="Min. 8 karakter" minlength="8" autocomplete="new-password"/><button type="button" class="pwd-eye" onclick="togglePwd(this)" tabindex="-1" aria-label="Tampilkan password">&#128065;</button></div>
            <div class="input-hint" id="pwdStrengthHint"></div>
          </div>
          <div>
            <label class="label">Konfirmasi Password <span style="color:var(--red)">*</span></label>
            <div class="pwd-wrap"><input class="input" type="password" id="regPwdConfirm" required
              placeholder="Ulangi password" autocomplete="new-password"/><button type="button" class="pwd-eye" onclick="togglePwd(this)" tabindex="-1" aria-label="Tampilkan password">&#128065;</button></div>
            <div class="input-hint" id="pwdMatchHint"></div>
          </div>
        </div>

        <div style="margin-top:14px;padding:12px 14px;border:1px solid rgba(255,255,255,.07);
             border-radius:14px;background:rgba(255,255,255,.02);font-size:.85rem;line-height:1.6;color:var(--muted)">
          Dengan mendaftar, kamu menyetujui
          <a href="#" style="color:var(--red)">Syarat & Ketentuan</a> dan
          <a href="#" style="color:var(--red)">Kebijakan Privasi</a> Bioskopp.
        </div>

        <div class="form-actions" style="margin-top:16px">
          <button class="btn btn-primary" type="submit" style="flex:1">Buat Akun</button>
          <button type="button" class="btn btn-ghost" data-modal-close="registerModal">Batal</button>
        </div>
        <div style="text-align:center;margin-top:10px;font-size:.85rem;color:var(--muted)">
          Sudah punya akun?
          <button type="button" class="btn btn-ghost btn-sm"
            onclick="document.getElementById('registerModal').classList.remove('is-open');
                     document.getElementById('loginModal').classList.add('is-open');">
            Login di sini
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
  // Password strength indicator
  (function(){
    var pwd = document.getElementById('regPwd');
    var hint = document.getElementById('pwdStrengthHint');
    var confirm = document.getElementById('regPwdConfirm');
    var matchHint = document.getElementById('pwdMatchHint');
    if(!pwd) return;
    pwd.addEventListener('input', function(){
      var v = pwd.value;
      var score = 0;
      if(v.length >= 8) score++;
      if(/[A-Z]/.test(v)) score++;
      if(/[0-9]/.test(v)) score++;
      if(/[^A-Za-z0-9]/.test(v)) score++;
      var labels = ['','Lemah','Cukup','Kuat','Sangat Kuat'];
      var colors = ['','#ef4444','#eab308','#22c55e','#4ade80'];
      hint.textContent = v.length > 0 ? 'Kekuatan: ' + (labels[score]||'Sangat Lemah') : '';
      hint.style.color = colors[score] || '#ef4444';
    });
    confirm.addEventListener('input', function(){
      if(confirm.value === '') { matchHint.textContent=''; return; }
      if(confirm.value === pwd.value){
        matchHint.textContent = '&#10003; Password cocok';
        matchHint.style.color = '#22c55e';
      } else {
        matchHint.textContent = '&#10005; Password tidak cocok';
        matchHint.style.color = '#ef4444';
      }
    });
    document.getElementById('regForm').addEventListener('submit', function(e){
      if(pwd.value !== confirm.value){
        e.preventDefault();
        matchHint.textContent = '&#10005; Password tidak cocok!';
        matchHint.style.color = '#ef4444';
        confirm.focus();
      }
    });
  })();
  </script>

  <!-- ===== MODAL: TAMBAH FILM ===== -->
  <div class="modal" id="modalFilmCreate" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head"><div class="modal-title">Tambah Film</div><button class="icon-btn" data-modal-close="modalFilmCreate">&#10005;</button></div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="admin_film_create"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <label class="label">Judul</label><input class="input" type="text" name="judul" required/>
        <label class="label">Genre</label><input class="input" type="text" name="genre" required placeholder="Contoh: Action & Drama"/>
        <div class="field-grid">
          <div><label class="label">Durasi (menit)</label><input class="input" type="number" name="durasi_menit" min="1" required/></div>
          <div><label class="label">Rating Usia</label>
            <select class="input" name="rating_usia">
              <option value="SU">SU (Semua Umur)</option>
              <option value="7+">7+</option>
              <option value="13+">13+</option>
              <option value="17+" selected>17+</option>
              <option value="21+">21+</option>
            </select>
          </div>
        </div>
        <label class="label">Sinopsis</label><textarea class="input" name="sinopsis" rows="3" style="resize:vertical"></textarea>
        <label class="label">Nama File Poster</label><input class="input" type="text" name="poster" placeholder="contoh: film.jpg" required/>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan</button></div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: EDIT FILM ===== -->
  <div class="modal" id="modalFilmEdit" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head"><div class="modal-title">Edit Film</div><button class="icon-btn" data-modal-close="modalFilmEdit">&#10005;</button></div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="admin_film_update"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <input type="hidden" name="film_id" id="editFilmId"/>
        <label class="label">Judul</label><input class="input" type="text" name="judul" id="editFilmJudul" required/>
        <label class="label">Genre</label><input class="input" type="text" name="genre" id="editFilmGenre" required/>
        <div class="field-grid">
          <div><label class="label">Durasi (menit)</label><input class="input" type="number" name="durasi_menit" id="editFilmDurasi" min="1" required/></div>
          <div><label class="label">Rating Usia</label>
            <select class="input" name="rating_usia" id="editFilmRating">
              <option value="SU">SU</option><option value="7+">7+</option>
              <option value="13+">13+</option><option value="17+">17+</option><option value="21+">21+</option>
            </select>
          </div>
        </div>
        <label class="label">Sinopsis</label><textarea class="input" name="sinopsis" id="editFilmSinopsis" rows="3" style="resize:vertical"></textarea>
        <label class="label">Nama File Poster</label><input class="input" type="text" name="poster" id="editFilmPoster" required/>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Update</button></div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: TAMBAH STUDIO ===== -->
  <div class="modal" id="modalStudioCreate" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head"><div class="modal-title">Tambah Studio</div><button class="icon-btn" data-modal-close="modalStudioCreate">&#10005;</button></div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="admin_studio_create"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <label class="label">Nama Studio</label><input class="input" type="text" name="nama" required placeholder="Contoh: Studio 16 - Silver Screen"/>
        <label class="label">Kapasitas (kursi)</label><input class="input" type="number" name="kapasitas" min="1" required/>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan</button></div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: EDIT STUDIO ===== -->
  <div class="modal" id="modalStudioEdit" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head"><div class="modal-title">Edit Studio</div><button class="icon-btn" data-modal-close="modalStudioEdit">&#10005;</button></div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="admin_studio_update"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <input type="hidden" name="studio_id" id="editStudioId"/>
        <label class="label">Nama Studio</label><input class="input" type="text" name="nama" id="editStudioNama" required/>
        <label class="label">Kapasitas (kursi)</label><input class="input" type="number" name="kapasitas" id="editStudioKapasitas" min="1" required/>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Update</button></div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: TAMBAH JADWAL ===== -->
  <div class="modal" id="modalJadwalCreate" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head"><div class="modal-title">Tambah Jadwal Tayang</div><button class="icon-btn" data-modal-close="modalJadwalCreate">&#10005;</button></div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="admin_jadwal_create"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <label class="label">Film</label>
        <select class="input" name="film_id" required>
          <option value="">-- Pilih Film --</option>
          <?php foreach($adminFilms as $f): ?>
            <option value="<?= e((string)$f['film_id']) ?>"><?= e((string)$f['judul']) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="label">Studio</label>
        <select class="input" name="studio_id" required>
          <option value="">-- Pilih Studio --</option>
          <?php foreach($adminStudios as $s): ?>
            <option value="<?= e((string)$s['studio_id']) ?>"><?= e((string)$s['nama']) ?> (<?= (int)$s['kapasitas'] ?> kursi)</option>
          <?php endforeach; ?>
        </select>
        <div class="field-grid">
          <div><label class="label">Tanggal</label><input class="input" type="date" name="tanggal" required/></div>
          <div><label class="label">Jam</label><input class="input" type="time" name="jam" required/></div>
        </div>
        <label class="label">Harga Tiket (Rp)</label><input class="input" type="number" name="harga" min="1000" step="1000" required/>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan</button></div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: EDIT JADWAL ===== -->
  <div class="modal" id="modalJadwalEdit" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head"><div class="modal-title">Edit Jadwal Tayang</div><button class="icon-btn" data-modal-close="modalJadwalEdit">&#10005;</button></div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="admin_jadwal_update"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <input type="hidden" name="jadwal_id" id="editJadwalId"/>
        <label class="label">Film</label>
        <select class="input" name="film_id" id="editJadwalFilm" required>
          <?php foreach($adminFilms as $f): ?>
            <option value="<?= e((string)$f['film_id']) ?>"><?= e((string)$f['judul']) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="label">Studio</label>
        <select class="input" name="studio_id" id="editJadwalStudio" required>
          <?php foreach($adminStudios as $s): ?>
            <option value="<?= e((string)$s['studio_id']) ?>"><?= e((string)$s['nama']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="field-grid">
          <div><label class="label">Tanggal</label><input class="input" type="date" name="tanggal" id="editJadwalTanggal" required/></div>
          <div><label class="label">Jam</label><input class="input" type="time" name="jam" id="editJadwalJam" required/></div>
        </div>
        <label class="label">Harga Tiket (Rp)</label><input class="input" type="number" name="harga" id="editJadwalHarga" min="1000" step="1000" required/>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Update</button></div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: CREATE USER ===== -->
  <div class="modal" id="modalUserCreate" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head">
        <div class="modal-title">Tambah User Baru</div>
        <button class="icon-btn" data-modal-close="modalUserCreate">&#10005;</button>
      </div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="admin_user_create"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <div class="field-grid">
          <div>
            <label class="label">Nama Lengkap</label>
            <input class="input" type="text" name="nama" placeholder="Nama lengkap"/>
          </div>
          <div>
            <label class="label">Username <span style="color:var(--red)">*</span></label>
            <input class="input" type="text" name="username" required placeholder="username"/>
          </div>
        </div>
        <div class="field-grid">
          <div>
            <label class="label">Email</label>
            <input class="input" type="email" name="email" placeholder="email@domain.com"/>
          </div>
          <div>
            <label class="label">No. HP</label>
            <input class="input" type="tel" name="phone" placeholder="08xx-xxxx-xxxx"/>
          </div>
        </div>
        <div class="field-grid">
          <div>
            <label class="label">Password <span style="color:var(--red)">*</span></label>
            <div class="pwd-wrap"><input class="input" type="password" name="password" required placeholder="Min. 6 karakter" minlength="6"/><button type="button" class="pwd-eye" onclick="togglePwd(this)" tabindex="-1" aria-label="Tampilkan password">&#128065;</button></div>
          </div>
          <div>
            <label class="label">Role <span style="color:var(--red)">*</span></label>
            <select class="input" name="role" required>
              <option value="user">User</option>
              <option value="operator">Operator</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="form-actions" style="margin-top:16px">
          <button class="btn btn-primary" type="submit">Buat Akun</button>
          <button type="button" class="btn btn-ghost" data-modal-close="modalUserCreate">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: EDIT USER ===== -->
  <div class="modal" id="modalUserEdit" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-head">
        <div class="modal-title">Edit User</div>
        <button class="icon-btn" data-modal-close="modalUserEdit">&#10005;</button>
      </div>
      <form class="modal-body form" method="post" action="process.php">
        <input type="hidden" name="action" value="admin_user_update"/>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
        <input type="hidden" name="upd_user_id" id="editUserId"/>
        <div class="field-grid">
          <div>
            <label class="label">Nama Lengkap</label>
            <input class="input" type="text" name="nama" id="editNama"/>
          </div>
          <div>
            <label class="label">Username</label>
            <input class="input" type="text" id="editUsername" disabled style="opacity:.5"/>
          </div>
        </div>
        <div class="field-grid">
          <div>
            <label class="label">Email</label>
            <input class="input" type="email" name="email" id="editEmail"/>
          </div>
          <div>
            <label class="label">No. HP</label>
            <input class="input" type="tel" name="phone" id="editPhone"/>
          </div>
        </div>
        <div class="field-grid">
          <div>
            <label class="label">Password Baru <span class="muted" style="font-size:.8rem">(kosongkan jika tidak diganti)</span></label>
            <div class="pwd-wrap"><input class="input" type="password" name="password" placeholder="Password baru..."/><button type="button" class="pwd-eye" onclick="togglePwd(this)" tabindex="-1" aria-label="Tampilkan password">&#128065;</button></div>
          </div>
          <div>
            <label class="label">Role</label>
            <select class="input" name="role" id="editRole">
              <option value="user">User</option>
              <option value="operator">Operator</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="form-actions" style="margin-top:16px">
          <button class="btn btn-primary" type="submit">Simpan</button>
          <button type="button" class="btn btn-ghost" data-modal-close="modalUserEdit">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===== MODAL: DETAIL USER ===== -->
  <div class="modal" id="modalUserDetail" aria-hidden="true">
    <div class="modal-dialog" style="max-width:680px">
      <div class="modal-head">
        <div class="modal-title" id="detailUserTitle">Detail User</div>
        <button class="icon-btn" data-modal-close="modalUserDetail">&#10005;</button>
      </div>
      <div class="modal-body" id="detailUserBody" style="min-height:120px">
        <p class="muted" style="text-align:center;padding:24px">Memuat...</p>
      </div>
    </div>
  </div>

  <div class="toast" id="toast" role="status" aria-live="polite"></div>

  <script>
  function openUserEdit(id, username, nama, email, phone, role) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editNama').value = nama;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editRole').value = role;
    var m = document.getElementById('modalUserEdit');
    m.classList.add('is-open'); m.setAttribute('aria-hidden','false');
  }

  function openUserDetail(id, username) {
    var m = document.getElementById('modalUserDetail');
    var body = document.getElementById('detailUserBody');
    var title = document.getElementById('detailUserTitle');
    title.textContent = 'Detail: @' + username;
    body.innerHTML = '<p class="muted" style="text-align:center;padding:24px">Memuat...</p>';
    m.classList.add('is-open'); m.setAttribute('aria-hidden','false');

    var fd = new FormData();
    fd.append('action', 'admin_user_detail');
    fd.append('user_id', id);
    fd.append('csrf_token', document.querySelector('input[name=csrf_token]').value);

    fetch('process.php', { method:'POST', body: fd })
      .then(r => r.json())
      .then(u => {
        if (!u || !u.id) { body.innerHTML = '<p class="muted" style="padding:16px">Data tidak ditemukan.</p>'; return; }
        var totalSpend = (u.transaksi || []).reduce((s, t) => s + parseInt(t.total_harga||0), 0);
        var rows = (u.transaksi || []).map(t => {
          var cls = t.status==='confirmed' ? 'tag tag-ok' : (t.status==='cancelled' ? 'tag tag-cancel' : 'tag tag-warn');
          return '<tr><td>'+t.transaksi_id+'</td><td>'+t.judul+'</td><td>'+t.tanggal+'</td>'
            +'<td style="text-align:center">'+t.jumlah_tiket+'</td>'
            +'<td>Rp '+parseInt(t.total_harga).toLocaleString('id-ID')+'</td>'
            +'<td><span class="'+cls+'">'+t.status+'</span></td></tr>';
        }).join('');
        body.innerHTML =
          '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px;font-size:.9rem">'
          +'<div><div style="color:var(--muted);font-size:.85rem">Username</div><div style="font-weight:700">'+u.username+'</div></div>'
          +'<div><div style="color:var(--muted);font-size:.85rem">Role</div><div style="font-weight:700">'+u.role+'</div></div>'
          +'<div><div style="color:var(--muted);font-size:.85rem">Nama</div><div style="font-weight:700">'+(u.nama||'-')+'</div></div>'
          +'<div><div style="color:var(--muted);font-size:.85rem">Email</div><div style="font-weight:700">'+(u.email||'-')+'</div></div>'
          +'<div><div style="color:var(--muted);font-size:.85rem">HP</div><div style="font-weight:700">'+(u.phone||'-')+'</div></div>'
          +'<div><div style="color:var(--muted);font-size:.85rem">Total Belanja</div><div style="font-weight:700">Rp '+totalSpend.toLocaleString('id-ID')+'</div></div>'
          +'</div>'
          +'<h4 style="margin:16px 0 12px;font-weight:700">Riwayat Transaksi</h4>'
          +'<div style="overflow-x:auto">'
          +'<table class="tbl" style="font-size:.85rem"><thead><tr><th>ID</th><th>Film</th><th>Tanggal</th><th>Tiket</th><th>Total</th><th>Status</th></tr></thead>'
          +'<tbody>'+(rows.length ? rows : '<tr><td colspan="6" style="text-align:center;padding:12px" class="muted">Tidak ada transaksi.</td></tr>')+'</tbody>'
          +'</table></div>';
      })
      .catch(e => { body.innerHTML = '<p class="muted" style="padding:16px">Error memuat data.</p>'; });
  }
  
  // Helper buka modal edit Film
  function openEditFilm(data) {
    document.getElementById('editFilmId').value      = data.film_id;
    document.getElementById('editFilmJudul').value   = data.judul;
    document.getElementById('editFilmGenre').value   = data.genre;
    document.getElementById('editFilmDurasi').value  = data.durasi_menit;
    document.getElementById('editFilmSinopsis').value = data.sinopsis || '';
    document.getElementById('editFilmPoster').value  = data.poster;
    var sel = document.getElementById('editFilmRating');
    for (var i=0;i<sel.options.length;i++) {
      if (sel.options[i].value === data.rating_usia) { sel.selectedIndex=i; break; }
    }
    var m = document.getElementById('modalFilmEdit');
    m.classList.add('is-open'); m.setAttribute('aria-hidden','false');
  }
  function openEditStudio(data) {
    document.getElementById('editStudioId').value       = data.studio_id;
    document.getElementById('editStudioNama').value     = data.nama;
    document.getElementById('editStudioKapasitas').value = data.kapasitas;
    var m = document.getElementById('modalStudioEdit');
    m.classList.add('is-open'); m.setAttribute('aria-hidden','false');
  }
  function openEditJadwal(data) {
    document.getElementById('editJadwalId').value      = data.jadwal_id;
    document.getElementById('editJadwalTanggal').value = data.tanggal;
    document.getElementById('editJadwalJam').value     = data.jam.substring(0,5);
    document.getElementById('editJadwalHarga').value   = data.harga;
    var sf = document.getElementById('editJadwalFilm');
    for (var i=0;i<sf.options.length;i++) {
      if (sf.options[i].value === data.film_id) { sf.selectedIndex=i; break; }
    }
    var ss = document.getElementById('editJadwalStudio');
    for (var i=0;i<ss.options.length;i++) {
      if (ss.options[i].value === data.studio_id) { ss.selectedIndex=i; break; }
    }
    var m = document.getElementById('modalJadwalEdit');
    m.classList.add('is-open'); m.setAttribute('aria-hidden','false');
  }
  </script>

  <!-- ===== FOOTER ===== -->
  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-brand">
        <a class="brand" href="<?= route_link('home') ?>">
          <span class="brand-mark">B</span>
          <span class="brand-text">Bioskopp</span>
        </a>
        <p>Nonton film favorit dengan pengalaman booking yang mudah, cepat, dan nyaman.</p>
      </div>
      <div class="footer-links">
        <div class="footer-col">
          <div class="footer-col-title">Menu</div>
          <a href="<?= route_link('home') ?>">Home</a>
          <a href="<?= route_link('movies') ?>">Movies</a>
          <a href="<?= route_link('schedule') ?>">Schedule</a>
          <a href="<?= route_link('promotions') ?>">Promos</a>
        </div>
        <div class="footer-col">
          <div class="footer-col-title">Akun</div>
          <?php if(!$loggedIn): ?>
            <a href="#" data-modal-open="loginModal">Login</a>
            <a href="#" data-modal-open="registerModal">Daftar</a>
          <?php else: ?>
            <a href="<?= route_link('dashboard') ?>">Dashboard</a>
            <form method="post" action="process.php" style="display:inline"><input type="hidden" name="action" value="logout"/><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/><button type="submit" style="background:none;border:none;cursor:pointer;color:inherit;font:inherit;padding:0">Logout</button></form>
          <?php endif; ?>
        </div>
        <div class="footer-col">
          <div class="footer-col-title">Bantuan</div>
          <a href="#">FAQ</a>
          <a href="#">Cara Pesan Tiket</a>
          <a href="#">Kebijakan Refund</a>
          <a href="#">Hubungi Kami</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Bioskopp &mdash; Cinema Premium. All rights reserved.</p>
      <span class="footer-badge">Dark Mode Cinema</span>
    </div>
  </footer>
<style>
.pwd-wrap{position:relative;display:flex;align-items:center;}
.pwd-wrap .input{flex:1;padding-right:40px;}
.pwd-eye{position:absolute;right:10px;background:none;border:none;cursor:pointer;font-size:1.1rem;line-height:1;padding:4px;color:var(--muted);transition:color .2s;}
.pwd-eye:hover{color:var(--text);}
.dash-search-bar{display:flex;gap:8px;padding:12px 16px 0;flex-wrap:wrap;}
.dash-search-bar .dash-search-input{flex:1;min-width:180px;}
.dash-search-bar .dash-filter-select{flex:0 0 160px;min-width:130px;}
.dash-no-results{text-align:center;padding:24px;color:var(--muted);font-size:.9rem;}
</style>
<script>
function togglePwd(btn){
  var inp=btn.previousElementSibling;
  var show=inp.type==='password';
  inp.type=show?'text':'password';
  btn.textContent=show?'\uD83D\uDE48':'\uD83D\uDC41';
  btn.setAttribute('aria-label',show?'Sembunyikan password':'Tampilkan password');
}
(function(){
  // ── Utility: filter satu tabel berdasarkan query teks + kolom tertentu
  function filterTable(tableId, query, colIndex, colValue) {
    var tbl = document.getElementById(tableId);
    if (!tbl) return;
    var q = query.trim().toLowerCase();
    var rows = tbl.querySelectorAll('tbody tr');
    var visible = 0;

    rows.forEach(function(row) {
      // Jangan filter baris "belum ada data" (hanya 1 cell colspan)
      if (row.cells.length <= 1) { row.style.display = ''; return; }

      var textMatch = true;
      var colMatch  = true;

      if (q) {
        var rowText = row.textContent.toLowerCase();
        textMatch = rowText.indexOf(q) !== -1;
      }

      if (colIndex !== null && colValue) {
        var cell = row.cells[colIndex];
        colMatch = cell && cell.textContent.trim().toLowerCase().indexOf(colValue.toLowerCase()) !== -1;
      }

      var show = textMatch && colMatch;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    // Tampilkan pesan "tidak ditemukan" jika semua tersembunyi
    var noRes = tbl.querySelector('.dash-no-results-row');
    if (!noRes) {
      noRes = document.createElement('tr');
      noRes.className = 'dash-no-results-row';
      var td = document.createElement('td');
      td.colSpan = tbl.rows[0] ? tbl.rows[0].cells.length : 10;
      td.className = 'dash-no-results';
      td.textContent = 'Tidak ada data yang cocok.';
      noRes.appendChild(td);
      tbl.querySelector('tbody').appendChild(noRes);
    }
    noRes.style.display = (visible === 0 && (q || colValue)) ? '' : 'none';
  }

  // ── State per tabel: {query, colIndex, colValue}
  var state = {};

  function getState(id) {
    if (!state[id]) state[id] = {query:'', colIndex:null, colValue:''};
    return state[id];
  }

  function applyFilter(id) {
    var s = getState(id);
    filterTable(id, s.query, s.colIndex, s.colValue);
  }

  // ── Bind semua search inputs
  document.querySelectorAll('.dash-search-input').forEach(function(inp) {
    var targetId = inp.dataset.searchTarget;
    inp.addEventListener('input', function() {
      getState(targetId).query = inp.value;
      applyFilter(targetId);
    });
  });

  // ── Bind semua filter selects
  document.querySelectorAll('.dash-filter-select').forEach(function(sel) {
    var targetId  = sel.dataset.filterTarget;
    var colIndex  = parseInt(sel.dataset.filterCol, 10);
    getState(targetId).colIndex = colIndex;
    sel.addEventListener('change', function() {
      getState(targetId).colValue = sel.value;
      applyFilter(targetId);
    });
  });
})();
</script>
</body>
</html>