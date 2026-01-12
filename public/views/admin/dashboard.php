<?php ob_start(); ?>

<!-- Judul Halaman -->
<h1 class="h4 mb-4 text-gray-800">Dashboard Admin</h1>

<div class="row">
  <!-- Total Pegawai -->
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
      <div
        class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div
            class="text-xs font-weight-bold text-primary text-uppercase mb-1">
            Total Pegawai
          </div>
          <div class="h5 font-weight-bold text-gray-800">
            <?= $stats['totalPegawai'] ?? '-' ?>
          </div>
        </div>
        <i class="fas fa-user fa-2x text-gray-300"></i>
      </div>
    </div>
  </div>

  <!-- Belum Kirim Laporan -->
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
      <div
        class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div
            class="text-xs font-weight-bold text-warning text-uppercase mb-1">
            Belum Kirim Laporan
          </div>
          <div class="h5 font-weight-bold text-gray-800">
            <?= $stats['belumKirimHariIni'] ?? '-' ?>
          </div>
        </div>
        <i class="fas fa-comments fa-2x text-gray-300"></i>
      </div>
    </div>
  </div>

  <!-- Total Laporan Harian -->
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
      <div
        class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div
            class="text-xs font-weight-bold text-success text-uppercase mb-1">
            Total Laporan Harian
          </div>
          <div class="h5 font-weight-bold text-gray-800">
            <?= $stats['totalLaporanHarian'] ?? '-' ?>
          </div>
        </div>
        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
      </div>
    </div>
  </div>

  <!-- Total Laporan Kegiatan -->
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
      <div
        class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div
            class="text-xs font-weight-bold text-info text-uppercase mb-1">
            Total Laporan Kegiatan
          </div>
          <div class="h5 font-weight-bold text-gray-800">
            <?= $stats['totalLaporanKegiatan'] ?? '-' ?>
          </div>
        </div>
        <i class="fas fa-clipboard fa-2x text-gray-300"></i>
      </div>
    </div>
  </div>
</div>

<!-- Chart Row -->
<div class="row">
  <!-- Area Chart -->
  <div class="col-xl-8 col-lg-7">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Laporan dengan Grafik Area
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
        <div class="chart-pie pt-4" style="height: 300px">
          <canvas id="myPieChart"></canvas>
        </div>
        <div class="mt-4 text-center small">
          <span class="mr-2"><i class="fas fa-circle text-primary"></i>
            Laki-laki</span>
          <span class="mr-2"><i class="fas fa-circle text-danger"></i>
            Perempuan</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tidak mengirim laporan -->
<div class="row">
  <div class="col-lg-6 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Tidak Mengirim Laporan
        </h6>
      </div>
      <div class="card-body">
        <table class="table table-striped text-danger">

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
                    tidak mengirim laporan hari ini.
                  </td>
                </tr>
              <?php endforeach ?>
            <?php endif ?>
          </tbody>

        </table>
      </div>
    </div>
  </div>

  <!-- Laporan Terbaru -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Laporan Terbaru
        </h6>
      </div>
      <div class="card-body">
        <table class="table table-striped align-items-center">

          <tbody>
            <?php if (empty($laporanTerbaru)): ?>
              <tr>
                <td colspan="2" class="text-center text-muted">
                  Belum ada laporan.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($laporanTerbaru as $row): ?>
                <tr>
                  <td>
                    <img
                      src="<?= $row['foto']
                              ? '/public/uploads/foto/' . $row['foto']
                              : '/public/assets/img/avatars/default_profile.svg' ?>"
                      class="profile-img-mini">
                  </td>
                  <td>
                    <b><?= htmlspecialchars($row['nama']) ?></b>
                    mengirim laporan
                    <small class="text-muted">
                      (<?= date('H:i', strtotime($row['created_at'])) ?>)
                    </small>
                  </td>
                </tr>
              <?php endforeach ?>
            <?php endif ?>
          </tbody>

        </table>
      </div>
    </div>

    <!-- Progres & Laporan -->
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-success">
          Progres Laporan
        </h6>
      </div>
      <div class="card-body">

        <?php
        $progress = $stats['totalPegawai'] > 0
          ? round(
            (($stats['totalPegawai'] - $stats['belumKirimHariIni'])
              / $stats['totalPegawai']) * 100
          )
          : 0;
        ?>

        <h4 class="small font-weight-bold">
          Laporan Hari Ini
          <span class="float-right"><?= $progress ?>%</span>
        </h4>

        <div class="progress mb-4">
          <div
            class="progress-bar bg-success"
            style="width: <?= $progress ?>%">
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();

include __DIR__ . '/../layouts/main.php';
?>