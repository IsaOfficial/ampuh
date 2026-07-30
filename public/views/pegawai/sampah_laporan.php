<?php ob_start(); ?>
<?php
$filter = $filter ?? [];
$selectedStart = (string) ($filter['start'] ?? '');
$selectedEnd = (string) ($filter['end'] ?? '');
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h4 mb-0 text-gray-800">Sampah Laporan</h1>
  <a href="/pegawai/laporan" class="btn btn-sm btn-secondary">
    <i class="fas fa-arrow-left"></i> Riwayat Laporan
  </a>
</div>

<div class="alert alert-warning shadow-sm">
  Laporan di halaman ini dapat dipulihkan selama belum otomatis dihapus permanen setelah lebih dari 14 hari.
</div>

<div class="card shadow mb-4 border-left-warning">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-warning">Filter Sampah Laporan</h6>
  </div>

  <div class="card-body">
    <form class="form-row" method="GET">
      <div class="col-md-4 mb-3">
        <label class="small text-muted">Tanggal Awal</label>
        <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($selectedStart) ?>">
      </div>

      <div class="col-md-4 mb-3">
        <label class="small text-muted">Tanggal Akhir</label>
        <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($selectedEnd) ?>">
      </div>

      <div class="col-md-4 mb-3 d-flex align-items-end export-card-actions">
        <button type="submit" class="btn btn-info">
          <i class="fas fa-filter"></i> Terapkan
        </button>
        <a href="/pegawai/laporan/sampah" class="btn btn-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<?php if ($flash = Session::getFlash('flash')): ?>
  <div class="alert shadow alert-<?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
<?php endif; ?>

<div class="card shadow mb-4 border-left-warning">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-warning">Daftar Laporan Terhapus</h6>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-striped" id="dataTable" width="100%">
        <thead class="bg-warning text-white text-center">
          <tr>
            <th>No</th>
            <th>Hari & Tanggal</th>
            <th>Kegiatan</th>
            <th>Output</th>
            <th>Bukti</th>
            <th>Dihapus</th>
            <th width="90">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($laporan)): ?>
            <?php $no = 1; ?>
            <?php foreach ($laporan as $row): ?>
              <?php
              $deletedAt = !empty($row['deleted_at']) ? new DateTimeImmutable($row['deleted_at']) : null;
              $deleteExpiresAt = $deletedAt ? $deletedAt->modify('+14 days') : null;
              $remainingDeleteDays = $deleteExpiresAt
                ? max(0, (int) (new DateTimeImmutable('today'))->diff($deleteExpiresAt)->format('%r%a'))
                : null;
              ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= DateHelper::hariTanggalIndo($row['tanggal']); ?></td>
                <td><?= htmlspecialchars($row['kegiatan']) ?></td>
                <td><?= htmlspecialchars($row['output']) ?></td>
                <td class="text-center">
                  <?php if (!empty($row['bukti'])): ?>
                    <a href="/public/uploads/bukti/<?= rawurlencode($row['bukti']) ?>" target="_blank" class="btn btn-info btn-sm">
                      <i class="fas fa-eye"></i> Lihat
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Tidak ada</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($deletedAt): ?>
                    <span class="badge badge-dark">Terhapus</span><br>
                    <small><?= $deletedAt->format('d/m/Y H:i') ?></small>
                  <?php endif; ?>
                  <?php if ($remainingDeleteDays !== null): ?>
                    <br><small class="text-muted">Permanen dalam <?= $remainingDeleteDays ?> hari</small>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <form method="POST" action="/pegawai/laporan/restore" class="d-inline">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['kegiatan_id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm" title="Pulihkan laporan">
                      <i class="fas fa-trash-restore"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted">Tidak ada laporan terhapus.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
