<?php ob_start(); ?>

<h1 class="h4 mb-4 text-gray-800">Profil Pegawai</h1>

<?php if ($flash = Session::getFlash('flash')): ?>
  <div class="alert shadow alert-<?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
<?php endif; ?>

<div class="row">
  <!-- Card Foto -->
  <div class="col-lg-4">
    <div class="card shadow mb-4">
      <div class="card-body text-center">

        <img
          src="<?= $pegawai['foto'] ? '/public/uploads/foto/' . $pegawai['foto'] : 'default_profile.svg' ?>"
          class="profile-img mb-3"
          alt="Foto Profil">

        <form action="/pegawai/profil/update-foto" method="POST" enctype="multipart/form-data" id="formFoto">
          <?= Csrf::input() ?>
          <input type="hidden" name="id" value="<?= $pegawai['id']; ?>">

          <!-- Input file disembunyikan -->
          <input
            type="file"
            name="foto"
            id="fotoInput"
            accept="image/*"
            style="display: none;"
            onchange="document.getElementById('formFoto').submit();">

          <h4 class="text-gray-800 font-weight-bold">
            <?= htmlspecialchars($pegawai['nama']); ?>
          </h4>

          <div class="form-group text-center">
            <button type="button" class="btn btn-madrasah mt-3" onclick="document.getElementById('fotoInput').click();">
              <i class="fas fa-upload"></i> Ganti Foto
            </button>
            <div>
              <small class="text-muted">Format file harus .jpg atau .png</small>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Form Edit Profil -->
  <div class="col-lg-8">
    <div class="card shadow mb-4">
      <div class="card-header py-3 bg-madrasah">
        <h6 class="m-0 font-weight-bold text-white">Edit Profil</h6>
      </div>

      <div class="card-body">
        <form action="/pegawai/profil/update" method="POST" enctype="multipart/form-data" autocomplete="off">
          <?= Csrf::input() ?>
          <input type="hidden" name="id" value="<?= $pegawai['id']; ?>">

          <div class="form-group">
            <label for="nama">Nama Lengkap (Beserta Gelar)<span class="text-danger"> *</span></label>
            <input type="text" class="form-control"
              name="nama"
              id="nama"
              value="<?= htmlspecialchars($pegawai['nama']); ?>" required />
          </div>

          <div class="form-group">
            <label for="nip">NIP</label>
            <input type="text" class="form-control"
              name="nip"
              id="nip"
              value="<?= htmlspecialchars($pegawai['nip']); ?>" readonly />
          </div>

          <div class="form-group">
            <label for="nik">NIK<span class="text-danger"> *</span></label>
            <input type="text" class="form-control"
              name="nik"
              id="nik"
              value="<?= htmlspecialchars($pegawai['nik']); ?>" readonly />
          </div>

          <div class="form-group">
            <label for="jabatan">Jabatan<span class="text-danger"> *</span></label>
            <input type="text" id="jabatan" class="form-control"
              name="jabatan"
              value="<?= htmlspecialchars($pegawai['jabatan']); ?>" readonly />
          </div>

          <div class="form-group">
            <label for="jenis_kelamin">Jenis Kelamin</label>
            <select class="form-control" name="jenis_kelamin" id="jenis_kelamin">
              <option value="Tidak diketahui">-- Pilih Jenis Kelamin --</option>
              <option value="Laki-laki" <?= $pegawai['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
              <option value="Perempuan" <?= $pegawai['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
            </select>
          </div>

          <div class="form-group">
            <label for="passwordEdit">Ubah Login Password</label>
            <div class="input-group password-group">
              <input
                type="password"
                id="passwordEdit"
                name="password"
                class="form-control"
                autocomplete="new-password"
                placeholder="(Kosongkan jika tidak diubah)" />
              <div class="input-group-append">
                <button
                  class="btn btn-secondary"
                  type="button"
                  onclick="togglePassword('passwordEdit', this)">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" class="form-control"
              name="email"
              value="<?= htmlspecialchars($pegawai['email']); ?>" />
          </div>

          <div class="form-group">
            <label for="no_wa">No Whatsapp</label>
            <input type="text" class="form-control"
              name="no_wa"
              id="no_wa"
              value="<?= htmlspecialchars($pegawai['no_wa']); ?>" />
          </div>

          <div class="d-flex justify-content-center mt-4">
            <button type="submit" class="btn btn-madrasah">
              <i class="fas fa-save"></i> Simpan Perubahan
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>