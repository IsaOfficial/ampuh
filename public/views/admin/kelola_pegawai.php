<?php ob_start(); ?>

<!-- Judul Halaman -->
<h1 class="h4 mb-4 text-gray-800">Kelola Pegawai</h1>

<!-- Filter -->
<div class="card shadow mb-4 border-left-success">

  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-success">Cetak Data Pegawai</h6>
  </div>

  <div class="card-body">
    <form class="form-row" method="GET">
      <div class="col-md-9 mb-3">
        <label class="small text-muted">Cetak Berdasarkan Kata Kunci Tertentu</label>
        <input
          type="text"
          name="keyword"
          class="form-control"
          placeholder="Cari Nama, NIP, Jabatan..." />
      </div>

      <!-- Tombol Cetak -->
      <div class="col-md-3 mb-2 d-flex align-items-center justify-content-end">
        <div>
          <button
            type="submit"
            class="btn btn-danger mr-2"
            formaction="pegawai/export/pdf"
            formtarget="_blank">
            <i class="fas fa-file-pdf"></i> PDF
          </button>

          <button
            type="submit"
            class="btn btn-madrasah"
            formaction="pegawai/export/excel">
            <i class="fas fa-file-excel"></i> Excel
          </button>
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
  <div
    class="card-header py-3 d-flex justify-content-between align-items-center">
    <h6 class="m-0 font-weight-bold text-success">
      Daftar Pegawai
    </h6>

    <div class="row-md-2 justify-content-right">
      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#tambahPegawaiModal">
        <i class="fas fa-plus-circle"></i> Tambah Pegawai
      </button>
      <button class="btn btn-sm btn-madrasah" data-toggle="modal" data-target="#importPegawaiModal">
        <i class="fas fa-plus-circle"></i> Import Data
      </button>

      <!-- Modal Import CSV -->
      <div class="modal fade" id="importPegawaiModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header bg-madrasah">
              <h5 class="modal-title text-white">Import Data Pegawai dari CSV</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="/admin/kelola/pegawai/import" method="POST" enctype="multipart/form-data">
              <?= Csrf::input() ?>
              <div class="modal-body">

                <div class="form-group">
                  <label>Pilih File CSV</label>
                  <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                  <small class="text-muted">Format file harus .csv</small>
                </div>

              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-madrasah">Import</button>
              </div>

            </form>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal Tambah Pegawai -->
    <div class="modal fade" id="tambahPegawaiModal" tabindex="-1" aria-labelledby="modalTambahPegawaiLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <div class="modal-header bg-madrasah text-white">
            <h5 class="modal-title" id="modalTambahPegawaiLabel">Tambah Pegawai Baru</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>

          <form action="/admin/kelola/pegawai/create" method="POST" enctype="multipart/form-data" autocomplete="off">
            <?= Csrf::input() ?>
            <div class="modal-body text-center">

              <div class="form-group text-center">

                <!-- Foto Profil -->
                <div class="position-relative d-inline-block my-3">
                  <img id="previewFoto"
                    src="/public/assets/img/avatars/default_profile.svg"
                    alt="Foto Profil"
                    class="rounded-circle"
                    style="width:120px; height:120px; object-fit:cover; border:2px solid #ccc;">

                  <label for="fotoInput" class="overlay-hover">
                    <div class="overlay-text">
                      <i class="fas fa-camera"></i> Unggah Foto
                    </div>
                  </label>

                  <input type="file"
                    id="fotoInput"
                    name="foto"
                    style="display:none;"
                    onchange="previewImage(event)">
                </div>

                <!-- Teks di BAWAH foto -->
                <div>
                  <small class="text-muted d-block">
                    Format file harus .jpg atau .png
                  </small>
                </div>

              </div>

              <!-- Divider -->
              <hr>

              <div class="row text-left mt-3">

                <div class="form-group col-md-6 mb-3">
                  <label class="form-label">Nama Lengkap (Beserta Gelar)<span class="text-danger"> *</span></label>
                  <input type="text" name="nama" class="form-control" required>
                </div>

                <div class="form-group col-md-6 mb-3">
                  <label class="form-label">NIP (Jika Ada)</label>
                  <input type="text" name="nip" class="form-control">
                </div>

                <div class="form-group col-md-6 mb-3">
                  <label class="form-label">NIK<span class="text-danger"> *</span></label>
                  <input type="text" name="nik" class="form-control" required>
                </div>

                <!-- Jabatan -->
                <div class="form-group col-md-6 mb-3">
                  <label class="form-label">Jabatan<span class="text-danger"> *</span></label>
                  <select name="jabatan" class="form-control" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <?php foreach (JabatanHelper::list() as $jabatan): ?>
                      <option value="<?= $jabatan ?>">
                        <?= $jabatan ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group col-md-6 mb-3">
                  <label class="form-label">Jenis Kelamin</label>
                  <select class="form-control" name="jenis_kelamin">
                    <option value="Tidak diketahui">-- Pilih Jenis Kelamin --</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </div>

                <div class="form-group col-md-6 mb-3">
                  <label for="passwordEdit">Login Password<span class="text-danger"> *</span></label>
                  <div class="input-group password-group">
                    <input
                      type="password"
                      id="makePasswordByAdmin"
                      name="password"
                      class="form-control"
                      autocomplete="new-password"
                      placeholder="(Buat password untuk login)" />
                    <div class="input-group-append">
                      <button
                        class="btn btn-secondary"
                        type="button"
                        onclick="togglePassword('makePasswordByAdmin', this)">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="form-group col-md-6 mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control">
                </div>

                <div class="form-group col-md-6 mb-3">
                  <label class="form-label">No WhatsApp</label>
                  <input type="text" name="no_wa" class="form-control">
                </div>

              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-madrasah">Tambah Pegawai</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- END Modal Tambah Pegawai -->
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table
        class="table table-bordered table-striped"
        id="dataTable"
        width="100%">
        <thead class="bg-success text-white text-center">
          <tr>
            <th>No</th>
            <th>Foto</th>
            <th>Nama Pegawai</th>
            <th>NIP/NIK</th>
            <th>Jabatan</th>
            <th>Jenis Kelamin</th>
            <th>Email</th>
            <th>No Whatsapp</th>
            <th width="120">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($pegawai)): ?>
            <?php $no = 1;
            foreach ($pegawai as $row): ?>
              <tr>
                <td><?= $no++; ?></td>

                <td class="text-center">
                  <img
                    src="<?= $row['foto'] ? '/public/uploads/foto/' . $row['foto'] : '/public/assets/img/avatars/default_profile.svg' ?>"
                    alt="Foto Profil Pegawai"
                    class="profile-img-mini mb-3" />
                </td>

                <td><?= htmlspecialchars($row['nama']); ?></td>
                <td>
                  <?= htmlspecialchars(!empty($row['nip']) ? $row['nip'] : $row['nik']); ?>
                </td>
                <td><?= htmlspecialchars($row['jabatan']); ?></td>
                <td><?= htmlspecialchars($row['jenis_kelamin']); ?></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= htmlspecialchars($row['no_wa']); ?></td>

                <td class="text-center align-middle">
                  <div class="btn-group">

                    <!-- Tombol Edit -->
                    <button class="btn btn-warning btn-sm"
                      data-toggle="modal"
                      data-target="#editPegawaiModal-<?= $row['id'] ?>">
                      <i class="fas fa-edit"></i>
                    </button>

                    <!-- Tombol Delete -->
                    <button class="btn btn-danger btn-sm ml-1"
                      data-toggle="modal"
                      data-target="#deletePegawaiModal-<?= $row['id'] ?>">
                      <i class="fas fa-trash"></i>
                    </button>

                  </div>

                  <!-- MODAL EDIT -->
                  <div class="modal fade" id="editPegawaiModal-<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalTambahPegawaiLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header bg-madrasah text-white">
                          <h5 class="modal-title" id="modalTambahPegawaiLabel">Edit Pegawai</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>

                        <form action="/admin/kelola/pegawai/update/<?= $row['id'] ?>" method="POST" enctype="multipart/form-data">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-center">

                            <!-- Hidden ID -->
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">

                            <div class="form-group text-center">
                              <!-- Foto Profil Lingkaran -->
                              <div class="position-relative d-inline-block my-3">
                                <img
                                  id="previewFoto"
                                  src="<?= $row['foto'] ? '/public/uploads/foto/' . $row['foto'] : '/public/assets/img/avatars/default_profile.svg' ?>"
                                  alt="Foto Profil Pegawai"
                                  class="rounded-circle" style="width:120px; height:120px; object-fit:cover; border:2px solid #ccc;">

                                <label for="fotoInput" class="overlay-hover">
                                  <div class="overlay-text">
                                    <i class="fas fa-camera"></i> Unggah Foto
                                  </div>
                                </label>
                                <input type="file" id="fotoInput" name="foto" style="display:none;" onchange="previewImage(event)">
                              </div>

                              <!-- Teks di BAWAH foto -->
                              <div>
                                <small class="text-muted d-block">
                                  Format file harus .jpg atau .png
                                </small>
                              </div>
                            </div>

                            <!-- Divider -->
                            <hr>

                            <div class="row text-left mt-3">

                              <div class="form-group col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap (Beserta Gelar)<span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($row['nama']) ?>" required>
                              </div>

                              <div class="form-group col-md-6 mb-3">
                                <label class="form-label">NIP (Jika Ada)</label>
                                <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($row['nip']) ?>">
                              </div>

                              <div class="form-group col-md-6 mb-3">
                                <label class="form-label">NIK<span class="text-danger"> *</span></label>
                                <input type="text" name="nik" class="form-control" value="<?= htmlspecialchars($row['nik']) ?>">
                              </div>

                              <div class="form-group col-md-6 mb-3">
                                <label class="form-label">Jabatan<span class="text-danger"> *</span></label>
                                <select name="jabatan" class="form-control" required>
                                  <option value="">-- Pilih Jabatan --</option>
                                  <?php foreach (JabatanHelper::list() as $jabatan): ?>
                                    <option value="<?= $jabatan ?>"
                                      <?= ($jabatan === $row['jabatan']) ? 'selected' : '' ?>>
                                      <?= $jabatan ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>

                              <div class="form-group col-md-6 mb-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <select class="form-control" name="jenis_kelamin" id="jenis_kelamin">
                                  <option value="Tidak diketahui">-- Pilih Jenis Kelamin --</option>
                                  <option value="Laki-laki" <?= $row['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                  <option value="Perempuan" <?= $row['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                              </div>

                              <div class="form-group col-md-6 mb-3">
                                <label class="form-label">Ubah Login Password</label>
                                <div class="input-group password-group">
                                  <input
                                    type="password"
                                    id="passwordEditByAdmin"
                                    name="password"
                                    class="form-control"
                                    autocomplete="new-password"
                                    placeholder="(Kosongkan jika tidak diubah)">
                                  <div class="input-group-append">
                                    <button
                                      class="btn btn-secondary"
                                      type="button"
                                      onclick="togglePassword('passwordEditByAdmin', this)">
                                      <i class="fas fa-eye"></i>
                                    </button>
                                  </div>
                                </div>
                              </div>

                              <div class="form-group col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($row['email']) ?>" required>
                              </div>

                              <div class="form-group col-md-6 mb-3">
                                <label class="form-label">No WhatsApp</label>
                                <input type="text" name="no_wa" value="<?= htmlspecialchars($row['no_wa']) ?>" class="form-control">
                              </div>

                            </div>
                          </div>

                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-madrasah">Simpan Perubahan</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                  <!-- END MODAL EDIT -->

                  <!-- MODAL DELETE -->
                  <div class="modal fade" id="deletePegawaiModal-<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">

                        <div class="modal-header bg-danger">
                          <h5 class="modal-title text-white">Hapus Pegawai</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>

                        <form method="POST" action="/admin/kelola/pegawai/delete/<?= $row['id'] ?>">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-left">

                            <input type="hidden" name="id" value="<?= $row['id'] ?>">

                            <p>
                              Yakin ingin menghapus pegawai dengan nama
                              <strong><?= htmlspecialchars($row['nama']) ?></strong>
                              ?
                            </p>

                          </div>

                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Hapus</button>
                          </div>
                        </form>

                      </div>
                    </div>
                  </div>
                  <!-- END MODAL DELETE -->
                </td>
              </tr>
            <?php endforeach; ?>

          <?php else: ?>
            <tr>
              <td colspan="10" class="text-center">Tidak ada data pegawai</td>
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