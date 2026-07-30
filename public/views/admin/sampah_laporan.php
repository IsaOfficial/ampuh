<?php ob_start(); ?>
<?php
$filter = $filter ?? [];
$selectedPegawaiId = (string) ($filter['pegawai_id'] ?? '');
$selectedStart = (string) ($filter['start'] ?? '');
$selectedEnd = (string) ($filter['end'] ?? '');
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h4 mb-0 text-gray-800">Sampah Laporan</h1>
  <a href="/admin/kelola/laporan" class="btn btn-sm btn-secondary">
    <i class="fas fa-arrow-left"></i> Kelola Laporan
  </a>
</div>

<div class="alert alert-warning shadow-sm">
  Laporan di halaman ini sudah dihapus sementara dan akan otomatis dihapus permanen setelah lebih dari 14 hari.
</div>

<div class="card shadow mb-4 border-left-warning">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-warning">Filter Sampah Laporan</h6>
  </div>

  <div class="card-body">
    <form class="form-row" method="GET">
      <div class="col-md-4 mb-3">
        <label class="small text-muted">Pegawai</label>
        <select name="pegawai_id" class="form-control">
          <option value="">-- Semua Pegawai --</option>
          <?php foreach ($pegawai_list as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $selectedPegawaiId === (string) $p['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="small text-muted">Tanggal Awal</label>
        <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($selectedStart) ?>">
      </div>

      <div class="col-md-3 mb-3">
        <label class="small text-muted">Tanggal Akhir</label>
        <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($selectedEnd) ?>">
      </div>

      <div class="col-md-2 mb-3 d-flex align-items-end export-card-actions">
        <button type="submit" class="btn btn-info">
          <i class="fas fa-filter"></i> Terapkan
        </button>
        <a href="/admin/kelola/laporan/sampah" class="btn btn-secondary">Reset</a>
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
            <th width="30"><input type="checkbox" id="selectAll"> Pilih Semua</th>
            <th>No</th>
            <th>Hari & Tanggal</th>
            <th>Nama Pegawai</th>
            <th>Kegiatan</th>
            <th>Output</th>
            <th>Bukti</th>
            <th>Dihapus</th>
            <th width="110">Aksi</th>
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
                <td class="text-center">
                  <input type="checkbox" class="rowCheckbox" form="trashBulkForm" name="kegiatan_ids[]" value="<?= (int) $row['kegiatan_id'] ?>">
                </td>
                <td><?= $no++ ?></td>
                <td><?= DateHelper::hariTanggalIndo($row['tanggal']); ?></td>
                <td><?= htmlspecialchars($row['nama_pegawai']) ?></td>
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
                  <?php if (!empty($row['deleted_by_name'])): ?>
                    <br><small>Oleh: <?= htmlspecialchars($row['deleted_by_name']) ?></small>
                  <?php endif; ?>
                  <?php if ($remainingDeleteDays !== null): ?>
                    <br><small class="text-muted">Permanen dalam <?= $remainingDeleteDays ?> hari</small>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <div class="btn-group">
                    <form method="POST" action="/admin/kelola/laporan/restore" class="d-inline">
                      <?= Csrf::input() ?>
                      <input type="hidden" name="id" value="<?= (int) $row['kegiatan_id'] ?>">
                      <button type="submit" class="btn btn-success btn-sm" title="Pulihkan laporan">
                        <i class="fas fa-trash-restore"></i>
                      </button>
                    </form>

                    <button class="btn btn-danger btn-sm ml-1"
                      data-toggle="modal"
                      data-target="#forceDeleteModal-<?= (int) $row['kegiatan_id'] ?>"
                      title="Hapus permanen">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>

                  <div class="modal fade" id="forceDeleteModal-<?= (int) $row['kegiatan_id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header bg-danger">
                          <h5 class="modal-title text-white">Hapus Permanen</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>

                        <form method="POST" action="/admin/kelola/laporan/force-delete">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-left">
                            <input type="hidden" name="id" value="<?= (int) $row['kegiatan_id'] ?>">
                            <p>
                              Laporan kegiatan
                              <strong><?= htmlspecialchars($row['kegiatan']) ?></strong>
                              milik <strong><?= htmlspecialchars($row['nama_pegawai']) ?></strong>
                              akan dihapus permanen beserta file buktinya.
                            </p>
                            <p class="text-danger mb-0">Aksi ini tidak dapat dibatalkan.</p>
                          </div>

                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Hapus Permanen</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="text-center text-muted">Tidak ada laporan terhapus.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <form method="POST" action="/admin/kelola/laporan/bulk-process" id="trashBulkForm">
        <?= Csrf::input() ?>
        <div class="form-row align-items-end mt-3">
          <div class="col-md-4 mb-2">
            <label class="small text-muted">Aksi Kolektif</label>
            <select name="action" id="bulkAction" class="form-control" required>
              <option value="">-- Pilih Aksi --</option>
              <option value="restore">Pulihkan</option>
              <option value="force_delete">Hapus Permanen</option>
            </select>
          </div>

          <div class="col-md-8 mb-2 text-right">
            <button type="submit" class="btn btn-warning">
              <i class="fas fa-check-double"></i> Jalankan
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.rowCheckbox');
    var bulkForm = document.getElementById('trashBulkForm');
    var bulkAction = document.getElementById('bulkAction');

    function selectedCount() {
      var selected = {};
      checkboxes.forEach(function(checkbox) {
        if (checkbox.checked) {
          selected[checkbox.value] = true;
        }
      });
      return Object.keys(selected).length;
    }

    if (selectAll) {
      selectAll.addEventListener('change', function() {
        checkboxes.forEach(function(checkbox) {
          checkbox.checked = selectAll.checked;
        });
      });
    }

    if (bulkForm) {
      bulkForm.addEventListener('submit', function(event) {
        var count = selectedCount();
        var action = bulkAction ? bulkAction.value : '';
        var labels = {
          restore: 'memulihkan',
          force_delete: 'menghapus permanen'
        };

        if (!count) {
          event.preventDefault();
          alert('Pilih minimal satu kegiatan terlebih dahulu.');
          return;
        }

        if (!confirm('Yakin ingin ' + (labels[action] || 'memproses') + ' ' + count + ' kegiatan yang dipilih?')) {
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
