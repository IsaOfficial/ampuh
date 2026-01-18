<?php ob_start(); ?>

<!-- Judul Halaman -->
<h1 class="h4 mb-4 text-gray-800">Dashboard Pegawai</h1>

<!-- Card Info Pegawai -->
<div class="card shadow mb-4 p-4">
  <div class="d-flex align-items-center">
    <img
      src="<?= $pegawai['foto'] ? '/public/uploads/foto/' . $pegawai['foto'] : '/public/uploads/foto/default_profile.svg' ?>"
      class="rounded-circle profile-img" />

    <div class="ml-5">
      <h2 class="mb-2 font-weight-bold text-madrasah text-uppercase">
        <?= htmlspecialchars($pegawai['nama']); ?>
      </h2>
      <p class="mb-1">
        <?= $pegawai['nip'] ? 'NIP : ' . htmlspecialchars($pegawai['nip']) : 'NIK : ' . htmlspecialchars($pegawai['nik']); ?></p>
      <p class="mb-1">Jabatan : <?= htmlspecialchars($pegawai['jabatan']); ?></p>

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
        <input class="form-control" id="tanggalDisplay" readonly />

        <input
          name="tanggal"
          type="date"
          id="tanggalAsli"
          class="form-control d-none"
          readonly />
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
          <input type="file" name="bukti[]" class="form-control" required />
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

<?php
$content = ob_get_clean();

include __DIR__ . '/../layouts/main.php';
?>