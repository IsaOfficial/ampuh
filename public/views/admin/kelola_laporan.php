<?php ob_start(); ?>

<!-- Judul Halaman -->
<h1 class="h4 mb-4 text-gray-800">Kelola Laporan</h1>

<!-- Filter -->
<div class="card shadow mb-4 border-left-success">

  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-success">Cetak Laporan</h6>
  </div>

  <div class="card-body">
    <form class="form-row" method="GET">
      <div class="col-md-3 mb-3">
        <label class="small text-muted">Cetak Berdasarkan Pegawai</label>
        <select name="pegawai_id" class="form-control">
          <option value="">-- Semua Pegawai --</option>
          <?php foreach ($pegawai_list as $p): ?>
            <option value="<?= $p['id'] ?>">
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
        <input type="date" name="start" class="form-control" />
      </div>

      <div class="col-md-3 mb-3">
        <label class="small text-muted">Tanggal Akhir</label>
        <input type="date" name="end" class="form-control" />
      </div>

      <!-- Tombol Cetak -->
      <div class="col-md-3 mb-3 d-flex align-items-end justify-content-end">
        <div>
          <button
            type=" submit"
            class="btn btn-danger mr-2"
            formaction="laporan/export/pdf"
            formtarget="_blank">
            <i class="fas fa-file-pdf"></i> PDF
          </button>

          <button
            type="submit"
            class="btn btn-madrasah"
            formaction="laporan/export/excel">
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
    <h6 class="m-0 font-weight-bold text-success">Daftar Laporan</h6>

    <!-- BUTTON TAMBAH LAPORAN -->
    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#tambahLaporanModal">
      <i class="fas fa-plus-circle"></i> Tambah Laporan
    </button>

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
                    <option value="<?= $p['id'] ?>">
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
    <div class="table-responsive">
      <table
        class="table table-bordered table-striped"
        id="dataTable"
        width="100%">
        <thead class="bg-success text-white text-center">
          <tr>
            <th>No</th>
            <th>Hari & Tanggal</th>
            <th>Foto</th>
            <th>Nama Pegawai</th>
            <th>Kegiatan</th>
            <th>Output</th>
            <th>Bukti</th>
            <th width="120">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($laporan)): ?>
            <?php $no = 1;
            foreach ($laporan as $row): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= DateHelper::hariTanggalIndo($row['tanggal']); ?></td>

                <td class="text-center">
                  <img
                    src="<?= $row['foto_pegawai'] ? '/public/uploads/foto/' . $row['foto_pegawai'] : '/public/assets/img/avatars/default_profile.svg' ?>"
                    alt="Foto Profil Pegawai"
                    class="profile-img-mini mb-3" />
                </td>

                <td><?= htmlspecialchars($row['nama_pegawai']) ?></td>
                <td><?= htmlspecialchars($row['kegiatan']) ?></td>
                <td><?= htmlspecialchars($row['output']) ?></td>

                <td class="text-center">
                  <?php if ($row['bukti']): ?>
                    <a href="/public/uploads/bukti/<?= $row['bukti'] ?>" target="_blank" class="btn btn-info btn-sm">
                      <i class="fas fa-eye"></i> Lihat
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Tidak ada</span>
                  <?php endif; ?>
                </td>

                <td class="text-center">
                  <div class="btn-group">

                    <!-- Tombol Edit -->
                    <button class="btn btn-warning btn-sm"
                      data-toggle="modal"
                      data-target="#editModal-<?= $row['kegiatan_id'] ?>">
                      <i class="fas fa-edit"></i>
                    </button>

                    <!-- Tombol Delete -->
                    <button class="btn btn-danger btn-sm ml-1"
                      data-toggle="modal"
                      data-target="#deleteModal-<?= $row['kegiatan_id'] ?>">
                      <i class="fas fa-trash"></i>
                    </button>

                  </div>

                  <!-- MODAL EDIT -->
                  <div class="modal fade" id="editModal-<?= $row['kegiatan_id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">

                        <div class="modal-header bg-madrasah">
                          <h5 class="modal-title text-white">Edit Laporan</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>

                        <form method="POST" action="/admin/kelola/laporan/update" enctype="multipart/form-data">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-left">

                            <!-- Hidden ID -->
                            <input type="hidden" name="id" value="<?= $row['kegiatan_id'] ?>">

                            <!-- Tanggal (readonly, dari laporan_harian) -->
                            <div class="form-group">
                              <label>Tanggal</label>
                              <input type="date" class="form-control"
                                name="tanggal"
                                value="<?= htmlspecialchars($row['tanggal']) ?>"
                                readonly>
                            </div>

                            <div class="form-group">
                              <label>Pegawai</label>
                              <input type="text" class="form-control"
                                value="<?= htmlspecialchars($row['nama_pegawai']) ?>"
                                readonly>
                            </div>

                            <div class="form-group">
                              <label>Kegiatan</label>
                              <input type="text" class="form-control"
                                name="kegiatan" value="<?= htmlspecialchars($row['kegiatan']) ?>" />
                            </div>

                            <div class="form-group">
                              <label>Output</label>
                              <textarea class="form-control"
                                name="output"
                                rows="2"><?= htmlspecialchars($row['output']) ?></textarea>
                            </div>

                            <div class="form-group">
                              <label>Ubah Bukti (opsional)</label>
                              <input type="file" class="form-control" name="bukti">
                            </div>

                            <?php if ($row['bukti']): ?>
                              <div class="form-group">
                                <label>Bukti Saat Ini:</label><br>
                                <a href="/uploads/bukti/<?= $row['bukti'] ?>" target="_blank">
                                  <?= $row['bukti'] ?>
                                </a>
                              </div>
                            <?php endif; ?>

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

                  <!-- MODAL DELETE ADMIN -->
                  <div class="modal fade" id="deleteModal-<?= $row['kegiatan_id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">

                        <div class="modal-header bg-danger">
                          <h5 class="modal-title text-white">Hapus Laporan</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>

                        <form method="POST" action="/admin/kelola/laporan/delete">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-left">

                            <input type="hidden" name="id" value="<?= $row['kegiatan_id'] ?>">

                            <p>
                              Yakin ingin menghapus laporan kegiatan
                              <strong><?= htmlspecialchars($row['kegiatan']) ?></strong>
                              milik <strong><?= htmlspecialchars($row['nama_pegawai']) ?></strong>
                              pada <strong><?= DateHelper::hariTanggalIndo($row['tanggal']) ?></strong>?
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
              <td colspan="8" class="text-center text-muted">Tidak ada laporan ditemukan.</td>
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