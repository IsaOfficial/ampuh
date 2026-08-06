<?php ob_start(); ?>
<?php
$filter = $filter ?? [];
$serverSideTable = true;
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
    <form class="form-row filter-card-form" method="GET">
      <div class="col-md-4 mb-3">
        <label class="small text-muted">Tanggal Awal</label>
        <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($selectedStart) ?>">
      </div>

      <div class="col-md-4 mb-3">
        <label class="small text-muted">Tanggal Akhir</label>
        <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($selectedEnd) ?>">
      </div>

      <div class="col-12">
        <div class="filter-card-actions">
          <div class="action-row action-row-start action-row-filter filter-card-primary-actions trash-filter-actions">
            <button type="submit" class="btn btn-info">
              <i class="fas fa-filter"></i> &nbsp Terapkan
            </button>
            <a href="/pegawai/laporan/sampah" class="btn btn-secondary">Reset</a>
          </div>
        </div>
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
      <table
        class="table table-bordered table-striped"
        id="dataTable"
        data-server-side="true"
        data-ajax-url="/pegawai/laporan/sampah/data"
        data-order-column="6"
        data-order-direction="desc"
        data-order-disabled="0,5,7"
        width="100%">
        <thead class="bg-warning text-white text-center">
          <tr>
            <th width="30"><input type="checkbox" id="selectAllPegawaiTrash"> Pilih Semua</th>
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
          <?php if (empty($serverSideTable)): ?>
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
                <td class="text-center">
                  <input type="checkbox" class="pegawaiTrashCheckbox" form="pegawaiTrashBulkForm" name="kegiatan_ids[]" value="<?= (int) $row['kegiatan_id'] ?>">
                </td>
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
              <td colspan="8" class="text-center text-muted">Tidak ada laporan terhapus.</td>
            </tr>
          <?php endif; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <form method="POST" action="/pegawai/laporan/bulk-process" id="pegawaiTrashBulkForm">
        <?= Csrf::input() ?>
        <div class="bulk-action-row mt-3">
          <div class="bulk-action-field">
            <label class="small text-muted">Aksi Kolektif</label>
            <select name="action" id="pegawaiTrashBulkAction" class="form-control" required>
              <option value="">-- Pilih Aksi --</option>
              <option value="restore">Pulihkan</option>
              <option value="force_delete">Hapus Permanen</option>
            </select>
          </div>

          <div class="bulk-action-submit">
            <button type="submit" class="btn btn-warning">
              <i class="fas fa-check-double"></i> &nbsp Jalankan
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var bulkForm = document.getElementById('pegawaiTrashBulkForm');
    var bulkSelection = window.AmpuhBulkActions ?
      window.AmpuhBulkActions.bindVisibleSelectAll({
        selectAllId: 'selectAllPegawaiTrash',
        checkboxSelector: '.pegawaiTrashCheckbox'
      }) :
      null;

    function selectedCount() {
      return bulkSelection ? bulkSelection.selectedCount() : 0;
    }

    if (bulkForm) {
      bulkForm.addEventListener('submit', function(event) {
        var count = selectedCount();
        var action = document.getElementById('pegawaiTrashBulkAction');
        var labels = {
          restore: 'memulihkan',
          force_delete: 'menghapus permanen'
        };
        var actionLabel = labels[action ? action.value : ''] || 'memproses';

        if (!count) {
          event.preventDefault();
          alert('Pilih minimal satu kegiatan terlebih dahulu.');
          return;
        }

        if (!confirm('Yakin ingin ' + actionLabel + ' ' + count + ' kegiatan yang dipilih?')) {
          event.preventDefault();
        }
      });
    }
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
