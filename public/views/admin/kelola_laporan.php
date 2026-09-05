<?php ob_start(); ?>
<?php
$filter = $filter ?? [];
$selectedPegawaiId = (string) ($filter['pegawai_id'] ?? '');
$selectedStatus = (string) ($filter['status'] ?? '');
$selectedStart = (string) ($filter['start'] ?? '');
$selectedEnd = (string) ($filter['end'] ?? '');
$bulkSelectableLaporanIds = [];
$oldCreateInput = Session::getFlash('old_admin_laporan_create') ?: [];
?>

<!-- Judul Halaman -->
<h1 class="h4 mb-4 text-gray-800">Kelola Laporan</h1>

<!-- Filter -->
<div class="card shadow mb-4 border-left-success">

  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-success">Filter & Cetak Laporan</h6>
  </div>

  <div class="card-body">
    <form class="form-row filter-card-form" method="GET">
      <div class="col-md-3 mb-3">
        <label class="small text-muted">Cetak Berdasarkan Pegawai</label>
        <select name="pegawai_id" class="form-control">
          <option value="">-- Semua Pegawai --</option>
          <?php foreach ($pegawai_list as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $selectedPegawaiId === (string) $p['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- <div class="col-md-3 mb-3">
        <label class="small text-muted">Cetak Berdasarkan Kata Kunci</label>
        <input
          type="text"
          name="keyword"
          class="form-control"
          placeholder="Masukkan kata kunci" />
      </div> -->

      <div class="col-md-3 mb-3">
        <label class="small text-muted">Tanggal Awal</label>
        <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($selectedStart) ?>" />
      </div>

      <div class="col-md-3 mb-3">
        <label class="small text-muted">Tanggal Akhir</label>
        <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($selectedEnd) ?>" />
      </div>

      <div class="col-md-3 mb-3">
        <label class="small text-muted">Status Laporan</label>
        <select name="status" class="form-control">
          <option value="" <?= $selectedStatus === '' ? 'selected' : '' ?>>Semua Status</option>
          <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Menunggu Disetujui</option>
          <option value="approved" <?= $selectedStatus === 'approved' ? 'selected' : '' ?>>Disahkan</option>
          <option value="rejected" <?= $selectedStatus === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
          <option value="revoked" <?= $selectedStatus === 'revoked' ? 'selected' : '' ?>>Dicabut</option>
        </select>
      </div>

      <div class="col-12">
        <div class="filter-card-actions">
          <div class="action-row action-row-start filter-card-primary-actions">
            <button type="submit" class="btn btn-info">
              <i class="fas fa-filter"></i> &nbsp Terapkan
            </button>
            <a href="/admin/kelola/laporan" class="btn btn-secondary">Reset</a>
          </div>

          <div class="action-row action-row-end action-row-compact filter-card-export-actions">
            <button
              type="submit"
              class="btn btn-danger"
              formaction="laporan/export/pdf"
              formtarget="_blank">
              <i class="fas fa-file-pdf"></i> &nbsp PDF
            </button>

            <button
              type="submit"
              class="btn btn-madrasah"
              formaction="laporan/export/excel">
              <i class="fas fa-file-excel"></i> &nbsp Excel
            </button>
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

<!-- Tabel -->
<div class="card shadow mb-4 border-left-success">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-success">Daftar Laporan</h6>

    <!-- MODAL TAMBAH LAPORAN -->
    <div class="modal fade" id="tambahLaporanModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <div class="modal-header bg-madrasah">
            <h5 class="modal-title text-white">Tambah Laporan Baru</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>

          <form method="POST" action="/admin/kelola/laporan/create" enctype="multipart/form-data">
            <?= Csrf::input() ?>
            <div class="modal-body">

              <!-- Pilih Pegawai -->
              <div class="form-group">
                <label>Pegawai</label>
                <select name="pegawai_id" class="form-control" required>
                  <option value="">-- Pilih Pegawai --</option>
                  <?php foreach ($pegawai_list as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $selectedPegawaiId === (string) $p['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($p['nama']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Pilih Tanggal -->
              <div class="form-group">
                <label>Tanggal Laporan</label>
                <input type="date" name="tanggal" class="form-control" required>
              </div>

              <hr>

              <!-- Form Kegiatan Dinamis -->
              <div class="mb-3">
                <strong>Daftar Kegiatan</strong>
              </div>

              <!-- Dinamis Kegiatan -->
              <div id="kegiatan-wrapper">
                <p class="mb-2">Kegiatan 1</p>
                <div class="row kegiatan-row mb-2">
                  <div class="col-md-4 mb-2">
                    <textarea name="kegiatan[]" class="form-control" placeholder="Nama Kegiatan" rows="2" required></textarea>
                  </div>

                  <div class="col-md-4 mb-2">
                    <textarea name="output[]" class="form-control" placeholder="Output" rows="2" required></textarea>
                  </div>

                  <div class="col-md-3 mb-2">
                    <div class="custom-file evidence-upload">
                      <input type="file" name="bukti[]" class="custom-file-input evidence-file-input" accept="image/*,application/pdf,video/*" required>
                      <label class="custom-file-label evidence-file-label" data-browse="Pilih"><i class="fas fa-paperclip mr-1"></i><span>Unggah bukti</span></label>
                    </div>
                    <small class="evidence-upload-hint">Format bukti: gambar, PDF, atau video.</small>
                  </div>

                  <div
                    class="col-md-1 mb-2 d-flex align-items-center justify-content-end"></div>
                </div>
              </div>

              <button
                type="button"
                class="btn btn-sm btn-primary my-2"
                onclick="addRow()">
                <i class="fas fa-plus-circle"></i> Tambah Kegiatan
              </button>

            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-madrasah">Simpan Laporan</button>
            </div>

          </form>

        </div>
      </div>
    </div>
    <!-- END MODAL TAMBAH -->
  </div>

  <div class="card-body">
    <div class="action-row action-row-start mb-3">
      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#tambahLaporanModal">
        <i class="fas fa-plus-circle"></i> &nbsp Tambah Laporan
      </button>
    </div>

    <div class="table-responsive">
      <div class="alert alert-info py-2 small">Aksi kolektif berlaku per kegiatan. Setiap baris kegiatan dapat diproses sendiri-sendiri.</div>


      <table
        class="table table-bordered table-striped"
        id="dataTable"
        data-server-side="true"
        data-ajax-url="/admin/kelola/laporan/data"
        data-order-column="2"
        data-order-direction="desc"
        data-order-disabled="0,3,7,9"
        width="100%">
        <thead class="bg-success text-white text-center">
          <tr>
            <th width="30">
              <input type="checkbox" id="selectAll"> Pilih Semua
            </th>
            <th>No</th>
            <th>Hari & Tanggal</th>
            <th>Foto</th>
            <th>Nama Pegawai</th>
            <th>Kegiatan</th>
            <th>Output</th>
            <th>Bukti</th>
            <th>Status</th>
            <th width="80">Aksi</th>
          </tr>
        </thead>

        <tbody></tbody>

      </table>
      <form method="POST" action="/admin/kelola/laporan/bulk-process" id="bulkProcessForm">
        <?= Csrf::input() ?>
        <div class="bulk-action-row mt-3">
          <div class="bulk-action-field">
            <label class="small text-muted">Aksi Kolektif</label>
            <select name="action" id="bulkAction" class="form-control" required>
              <option value="">-- Pilih Aksi --</option>
              <option value="approve">Setujui</option>
              <option value="reject">Tolak</option>
              <option value="revoke">Cabut Persetujuan</option>
              <option value="delete">Hapus</option>
            </select>
          </div>

          <div class="bulk-action-field bulk-action-field-wide" id="signatureNoteGroup">
            <label class="small text-muted">Catatan Tanda Tangan Digital</label>
            <input type="text" name="signature_note" class="form-control" placeholder="Opsional, contoh: Diverifikasi oleh admin">
          </div>

          <div class="bulk-action-field bulk-action-field-wide d-none" id="rejectionNoteGroup">
            <label class="small text-muted">Alasan Penolakan</label>
            <input type="text" name="rejection_note" id="rejectionNote" class="form-control" placeholder="Wajib diisi saat menolak laporan">
          </div>

          <div class="bulk-action-submit">
            <button type="submit" class="btn btn-primary">
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
    var bulkForm = document.getElementById('bulkProcessForm');
    var bulkAction = document.getElementById('bulkAction');
    var signatureNoteGroup = document.getElementById('signatureNoteGroup');
    var rejectionNoteGroup = document.getElementById('rejectionNoteGroup');
    var rejectionNote = document.getElementById('rejectionNote');
    var bulkSelection = window.AmpuhBulkActions
      ? window.AmpuhBulkActions.bindVisibleSelectAll({
        selectAllId: 'selectAll',
        checkboxSelector: '.rowCheckbox'
      })
      : null;

    function selectedCount() {
      return bulkSelection ? bulkSelection.selectedCount() : 0;
    }

    function syncBulkFields() {
      var action = bulkAction ? bulkAction.value : '';
      if (!signatureNoteGroup || !rejectionNoteGroup || !rejectionNote) {
        return;
      }

      signatureNoteGroup.classList.toggle('d-none', action === 'reject' || action === 'revoke' || action === 'delete');
      rejectionNoteGroup.classList.toggle('d-none', action !== 'reject');
      rejectionNote.required = action === 'reject';
    }

    if (bulkAction) {
      bulkAction.addEventListener('change', syncBulkFields);
      syncBulkFields();
    }

    if (bulkForm) {
      bulkForm.addEventListener('submit', function(event) {
        var count = selectedCount();
        var action = bulkAction ? bulkAction.value : '';
        var labels = {
          approve: 'menyetujui dan menandatangani',
          reject: 'menolak',
          revoke: 'mencabut pengesahan',
          delete: 'menghapus'
        };

        if (!count) {
          event.preventDefault();
          alert('Pilih minimal satu kegiatan terlebih dahulu.');
          return;
        }

        if (action === 'reject' && rejectionNote && rejectionNote.value.trim() === '') {
          event.preventDefault();
          alert('Alasan penolakan wajib diisi.');
          rejectionNote.focus();
          return;
        }

        if (!confirm('Yakin ingin ' + (labels[action] || 'memproses') + ' ' + count + ' kegiatan yang dipilih?')) {
          event.preventDefault();
        }
      });
    }
  });
</script>
<?php if (!empty($oldCreateInput)): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      restoreLaporanCreateDraft('form[action="/admin/kelola/laporan/create"]', <?= json_encode($oldCreateInput, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);

      if (window.jQuery) {
        window.jQuery('#tambahLaporanModal').modal('show');
      }
    });
  </script>
<?php endif; ?>
<?php
$content = ob_get_clean();

include __DIR__ . '/../layouts/main.php';
?>
