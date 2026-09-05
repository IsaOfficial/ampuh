<?php ob_start(); ?>
<?php
$appTimezone = new DateTimeZone('Asia/Jakarta');
$today = new DateTimeImmutable('today', $appTimezone);
$minimumReportDate = $today->modify('-3 days');
$oldCreateInput = Session::getFlash('old_pegawai_laporan_create') ?: [];
?>

<!-- Judul Halaman -->
<h1 class="h4 mb-4 text-gray-800">Dashboard Pegawai</h1>

<!-- Card Info Pegawai -->
<div class="card shadow mb-4 profile-summary-card">
  <div class="card-body profile-summary">
    <div class="profile-summary-media">
      <img
        src="<?= !empty($pegawai['foto']) && $pegawai['foto'] !== 'default_profile.svg' ? '/public/uploads/foto/' . $pegawai['foto'] : '/public/assets/img/avatars/default_profile.svg' ?>"
        class="profile-img profile-summary-img"
        alt="Foto Profil Pegawai" />
    </div>

    <div class="profile-summary-content">
      <h2 class="profile-summary-name mb-3 font-weight-bold text-madrasah text-uppercase">
        <?= htmlspecialchars($pegawai['nama']); ?>
      </h2>
      <p class="profile-summary-meta mb-2">
        <span class="profile-summary-label"><?= $pegawai['nip'] ? 'NIP' : 'NIK'; ?></span>
        <span class="profile-summary-separator">:</span>
        <span class="profile-summary-value"><?= htmlspecialchars($pegawai['nip'] ?: $pegawai['nik']); ?></span>
      </p>
      <p class="profile-summary-meta profile-summary-position mb-0">
        <span class="profile-summary-label">Jabatan</span>
        <span class="profile-summary-separator">:</span>
        <span class="profile-summary-value"><?= htmlspecialchars($pegawai['jabatan']); ?></span>
      </p>
    </div>
  </div>
</div>

<?php if ($flash = Session::getFlash('flash')): ?>
  <div class="alert shadow alert-<?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
<?php endif; ?>

<!-- Form Laporan Harian -->
<div class="card shadow mb-4 border-left-success" id="formInputLaporan">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-success">Form Input Laporan Harian</h6>
  </div>

  <form class="p-4" method="POST" action="/pegawai/laporan/store" enctype="multipart/form-data">
    <?= Csrf::input() ?>
    <div class="form-group form-row">
      <label class="col-md-3 col-form-label">Hari, Tanggal :</label>

      <div class="col-md-9">
        <input
          name="tanggal"
          type="date"
          id="tanggalAsli"
          class="form-control"
          value="<?= $today->format('Y-m-d') ?>"
          min="<?= $minimumReportDate->format('Y-m-d') ?>"
          max="<?= $today->format('Y-m-d') ?>"
          required />
        <small class="form-text text-muted"><span id="tanggalDisplay"></span>. Laporan tertinggal dapat diinput maksimal 3 hari ke belakang.</small>
      </div>
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

    <div class="d-flex justify-content-center mt-4">
      <button type="submit" class="btn btn-md btn-madrasah">
        Kirim Laporan
      </button>
    </div>
  </form>
</div>

<?php if (!empty($oldCreateInput)): ?>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    restoreLaporanCreateDraft('form[action="/pegawai/laporan/store"]', <?= json_encode($oldCreateInput, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
  });
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();

include __DIR__ . '/../layouts/main.php';
?>
