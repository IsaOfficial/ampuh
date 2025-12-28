<?php ob_start(); ?>

<!-- Judul Halaman -->
<h1 class="h4 mb-4 text-gray-800">Riwayat Laporan Kegiatan</h1>

<!-- Filter Cetak -->
<div class="card shadow mb-4 border-left-success">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-success">Cetak Laporan</h6>
  </div>

  <div class="card-body">
    <form class="form-row" method="GET" id="cetakForm">

      <!-- Tanggal Awal -->
      <div class="col-md-4 mb-2">
        <label for="start_date" class="small text-muted">Tanggal Awal</label>
        <input
          type="date"
          id="start_date"
          name="start_date"
          class="form-control" />
      </div>

      <!-- Tanggal Akhir -->
      <div class="col-md-4 mb-4">
        <label for="end_date" class="small text-muted">Tanggal Akhir</label>
        <input
          type="date"
          id="end_date"
          name="end_date"
          class="form-control" />
      </div>

      <!-- Tombol Cetak -->
      <div class="col-md-4 mb-2 d-flex align-items-center justify-content-end">
        <div>
          <button
            type="submit"
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
    <h6 class="m-0 font-weight-bold text-success">Riwayat Laporan Anda</h6>

    <a href="/pegawai/dashboard/#formInputLaporan" class="btn btn-primary btn-sm">
      <i class="fas fa-plus"></i> Tambah Laporan
    </a>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-striped" id="dataTable">
        <thead class="bg-success text-white text-center">
          <tr>
            <th>No</th>
            <th>Hari & Tanggal</th>
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
                <td class="text-center"><?= $no++ ?></td>
                <td><?= DateHelper::hariTanggalIndo($row['tanggal']); ?></td>

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
                    <button class="btn btn-warning btn-sm"
                      data-toggle="modal"
                      data-target="#editModal-<?= $row['id'] ?>">
                      <i class="fas fa-edit"></i>
                    </button>

                    <button class="btn btn-danger btn-sm ml-1"
                      data-toggle="modal"
                      data-target="#deleteModal-<?= $row['id'] ?>">
                      <i class="fas fa-trash"></i>
                    </button>

                  </div>

                  <!-- Modal Edit -->
                  <div class="modal fade" id="editModal-<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">

                        <div class="modal-header bg-madrasah">
                          <h5 class="modal-title text-white">Edit Laporan</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>

                        <form method="POST" action="/pegawai/laporan/update/<?= $row['id'] ?>" enctype="multipart/form-data">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-left">

                            <!-- Hidden ID -->
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">

                            <div class="form-group">
                              <label>Tanggal</label>
                              <!-- Input type date harus Y-m-d -->
                              <input type="date" class="form-control"
                                name="tanggal"
                                value="<?= htmlspecialchars($row['tanggal']) ?>"
                                readonly>
                            </div>

                            <div class="form-group">
                              <label>Kegiatan<span class="text-danger"> *</span></label>
                              <input
                                class="form-control"
                                name="kegiatan"
                                value="<?= htmlspecialchars($row['kegiatan']) ?>"
                                required>
                            </div>

                            <div class="form-group">
                              <label>Output<span class="text-danger"> *</span></label>
                              <textarea
                                class="form-control"
                                name="output"
                                rows="3"
                                required><?= htmlspecialchars($row['output']) ?></textarea>
                            </div>

                            <div class="form-group">
                              <label>Ubah Bukti (opsional)</label>
                              <input type="file" class="form-control" name="bukti">
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

                  <!-- MODAL HAPUS KHUSUS RECORD INI -->
                  <div class="modal fade" id="deleteModal-<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">

                        <div class="modal-header bg-danger">
                          <h5 class="modal-title text-white">Hapus Laporan</h5>
                          <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>

                        <!-- Form langsung mengirim ke route dengan id -->
                        <form method="POST" action="/pegawai/laporan/delete/<?= $row['id'] ?>">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-left">

                            <!-- optional: kirim id via POST juga -->
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">

                            <p>Yakin ingin menghapus laporan kegiatan
                              <strong><?= htmlspecialchars($row['kegiatan']) ?></strong>
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
                  <!-- END MODAL -->
                </td>
              </tr>
            <?php endforeach; ?>

          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-muted">Belum ada laporan.</td>
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