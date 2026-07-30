<?php ob_start(); ?>

<!-- Judul Halaman -->
<h1 class="h4 mb-4 text-gray-800">Dashboard Admin</h1>

<?php
$approvalStatus = $stats['approvalStatus'] ?? [
  'pending' => 0,
  'approved' => 0,
  'rejected' => 0,
  'revoked' => 0,
];
$today = $stats['tanggalHariIni'] ?? date('Y-m-d');
$todayQuery = http_build_query([
  'start' => $today,
  'end' => $today,
]);
$totalPegawai = (int) ($stats['totalPegawai'] ?? 0);
$sudahKirimHariIni = (int) ($stats['kirimHariIni'] ?? 0);
$belumKirimHariIni = (int) ($stats['belumKirimHariIni'] ?? 0);
$progress = $totalPegawai > 0
  ? max(0, min(100, round(($sudahKirimHariIni / $totalPegawai) * 100)))
  : 0;

$mainCards = [
  [
    'label' => 'Total Pegawai',
    'value' => $totalPegawai,
    'color' => 'primary',
    'icon' => 'fa-users',
    'href' => '/admin/kelola/pegawai',
  ],
  [
    'label' => 'Sudah Mengirim Hari Ini',
    'value' => $sudahKirimHariIni,
    'color' => 'success',
    'icon' => 'fa-user-check',
    'href' => '/admin/kelola/laporan?' . $todayQuery,
  ],
  [
    'label' => 'Belum Mengirim Hari Ini',
    'value' => $belumKirimHariIni,
    'color' => 'warning',
    'icon' => 'fa-user-clock',
    'href' => '#pegawai-belum-kirim',
  ],
  [
    'label' => 'Kepatuhan Hari Ini',
    'value' => $progress . '%',
    'color' => 'info',
    'icon' => 'fa-percentage',
    'href' => '#progres-laporan',
  ],
];

$statusCards = [
  [
    'label' => 'Menunggu Disetujui',
    'value' => $approvalStatus['pending'] ?? 0,
    'color' => 'warning',
    'icon' => 'fa-hourglass-half',
    'href' => '/admin/kelola/laporan?status=pending',
  ],
  [
    'label' => 'Disahkan',
    'value' => $approvalStatus['approved'] ?? 0,
    'color' => 'success',
    'icon' => 'fa-signature',
    'href' => '/admin/kelola/laporan?status=approved',
  ],
  [
    'label' => 'Ditolak',
    'value' => $approvalStatus['rejected'] ?? 0,
    'color' => 'danger',
    'icon' => 'fa-times-circle',
    'href' => '/admin/kelola/laporan?status=rejected',
  ],
  [
    'label' => 'Dicabut',
    'value' => $approvalStatus['revoked'] ?? 0,
    'color' => 'secondary',
    'icon' => 'fa-undo-alt',
    'href' => '/admin/kelola/laporan?status=revoked',
  ],
];

$renderDashboardCard = static function (array $card): void {
  ?>
  <div class="col-xl-3 col-md-6 mb-4">
    <?php if (!empty($card['href'])): ?>
      <a href="<?= htmlspecialchars($card['href']) ?>" class="text-decoration-none d-block h-100">
    <?php endif ?>
    <div class="card border-left-<?= htmlspecialchars($card['color']) ?> shadow h-100 py-2">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-xs font-weight-bold text-<?= htmlspecialchars($card['color']) ?> text-uppercase mb-1">
            <?= htmlspecialchars($card['label']) ?>
          </div>
          <div class="h5 font-weight-bold text-gray-800">
            <?= htmlspecialchars((string) $card['value']) ?>
          </div>
        </div>
        <i class="fas <?= htmlspecialchars($card['icon']) ?> fa-2x text-gray-300"></i>
      </div>
    </div>
    <?php if (!empty($card['href'])): ?>
      </a>
    <?php endif ?>
  </div>
  <?php
};

$statusBadge = static function (array $row): array {
  if (!empty($row['approval_revoked_at'])) {
    return ['Dicabut', 'secondary'];
  }

  return match ($row['status'] ?? 'pending') {
    'approved' => ['Disahkan', 'success'],
    'rejected' => ['Ditolak', 'danger'],
    default => ['Menunggu', 'warning'],
  };
};
?>

<div class="row">
  <?php foreach ($mainCards as $card): ?>
    <?php $renderDashboardCard($card); ?>
  <?php endforeach ?>
</div>

<div class="row">
  <?php foreach ($statusCards as $card): ?>
    <?php $renderDashboardCard($card); ?>
  <?php endforeach ?>
</div>

<!-- Chart Row -->
<div class="row">
  <!-- Area Chart -->
  <div class="col-xl-8 col-lg-7">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Grafik Pengiriman 30 Hari
        </h6>
      </div>
      <div class="card-body">
        <div class="chart-area">
          <canvas id="myAreaChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Pie Chart -->
  <div class="col-xl-4 col-lg-5">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Pegawai Berdasarkan Jenis Kelamin
        </h6>
      </div>
      <div class="card-body">
        <div class="chart-pie pt-4 dashboard-chart-pie">
          <canvas id="myPieChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Feed Prioritas -->
<div class="row">
  <div class="col-lg-6 mb-4">
    <div class="card shadow mb-4" id="pegawai-belum-kirim">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Belum Mengirim Hari Ini
        </h6>
      </div>
      <div class="card-body">
        <table class="table table-striped text-danger mb-0">
          <tbody>
            <?php if (empty($tidakKirim)): ?>
              <tr>
                <td class="text-success">
                  Semua pegawai sudah mengirim laporan hari ini.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($tidakKirim as $row): ?>
                <tr>
                  <td>
                    <b><?= htmlspecialchars($row['nama']) ?></b>
                    <?php if (!empty($row['jabatan'])): ?>
                      <small class="text-muted d-block"><?= htmlspecialchars($row['jabatan']) ?></small>
                    <?php endif ?>
                  </td>
                </tr>
              <?php endforeach ?>
            <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Menunggu Disetujui Terbaru
        </h6>
      </div>
      <div class="card-body">
        <table class="table table-striped align-items-center mb-0">
          <tbody>
            <?php if (empty($menungguDisetujui)): ?>
              <tr>
                <td class="text-center text-muted">
                  Tidak ada kegiatan yang menunggu disetujui.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($menungguDisetujui as $row): ?>
                <tr>
                  <td class="table-avatar-cell">
                    <img
                      src="<?= $row['foto']
                              ? '/public/uploads/foto/' . $row['foto']
                              : '/public/assets/img/avatars/default_profile.svg' ?>"
                      class="profile-img-mini">
                  </td>
                  <td>
                    <b><?= htmlspecialchars($row['nama']) ?></b>
                    <small class="text-muted d-block">
                      <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                    </small>
                    <span><?= htmlspecialchars($row['kegiatan']) ?></span>
                  </td>
                  <td class="text-right">
                    <a href="/admin/kelola/laporan?status=pending" class="btn btn-sm btn-warning">
                      Proses
                    </a>
                  </td>
                </tr>
              <?php endforeach ?>
            <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Laporan Terbaru -->
  <div class="col-lg-8 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Aktivitas Laporan Terbaru
        </h6>
      </div>
      <div class="card-body">
        <table class="table table-striped align-items-center mb-0">
          <tbody>
            <?php if (empty($laporanTerbaru)): ?>
              <tr>
                <td colspan="3" class="text-center text-muted">
                  Belum ada laporan.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($laporanTerbaru as $row): ?>
                <?php [$label, $badge] = $statusBadge($row); ?>
                <tr>
                  <td class="table-avatar-cell">
                    <img
                      src="<?= $row['foto']
                              ? '/public/uploads/foto/' . $row['foto']
                              : '/public/assets/img/avatars/default_profile.svg' ?>"
                      class="profile-img-mini">
                  </td>
                  <td>
                    <b><?= htmlspecialchars($row['nama']) ?></b>
                    <small class="text-muted d-block">
                      <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                    </small>
                    <span><?= htmlspecialchars($row['kegiatan']) ?></span>
                  </td>
                  <td class="text-right">
                    <span class="badge badge-<?= htmlspecialchars($badge) ?>">
                      <?= htmlspecialchars($label) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach ?>
            <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Progres & Laporan -->
  <div class="col-lg-4 mb-4">
    <div class="card shadow mb-4" id="progres-laporan">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Kepatuhan Hari Ini
        </h6>
      </div>
      <div class="card-body">
        <h4 class="small font-weight-bold">
          Laporan Masuk
          <span class="float-right"><?= $progress ?>%</span>
        </h4>

        <div class="progress mb-3">
          <div
            class="progress-bar bg-success"
            style="width: <?= $progress ?>%">
          </div>
        </div>

        <div class="small text-muted">
          <?= $sudahKirimHariIni ?> dari <?= $totalPegawai ?> pegawai sudah mengirim laporan hari ini.
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();

include __DIR__ . '/../layouts/main.php';
?>
