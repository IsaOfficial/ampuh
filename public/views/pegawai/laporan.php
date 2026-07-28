<?php ob_start(); ?>
<?php
$hasApprovedReport = !empty(array_filter($laporan ?? [], fn($row) => ($row['status'] ?? 'pending') === 'approved'));
$appTimezone = new DateTimeZone('Asia/Jakarta');
$today = new DateTimeImmutable('today', $appTimezone);
$editableLimitDate = $today->modify('-3 days');
?>

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
            class="btn btn-danger mr-2" <?= $hasApprovedReport ? '' : 'disabled' ?>
            formaction="laporan/export/pdf"
            formtarget="_blank">
            <i class="fas fa-file-pdf"></i> PDF
          </button>

          <button
            type="submit"
            class="btn btn-madrasah" <?= $hasApprovedReport ? '' : 'disabled' ?>
            formaction="laporan/export/excel">
            <i class="fas fa-file-excel"></i> Excel
          </button>
        </div>
      </div>

      <?php if (!$hasApprovedReport): ?>
        <div class="col-12">
          <div class="alert alert-warning mb-0 py-2">
            Laporan baru bisa dicetak setelah admin menyetujui dan memberikan tanda tangan digital.
          </div>
        </div>
      <?php endif; ?>

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
            <th>Status</th>
            <th width="120">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($laporan)): ?>
            <?php $no = 1;
            foreach ($laporan as $row): ?>
              <?php
              $rowTanggal = substr((string) ($row['tanggal'] ?? ''), 0, 10);
              $rowDate = DateTimeImmutable::createFromFormat('!Y-m-d', $rowTanggal, $appTimezone);
              $rowDate = $rowDate && $rowDate->format('Y-m-d') === $rowTanggal ? $rowDate : null;
              $isApproved = (($row['status'] ?? 'pending') === 'approved');
              $isApprovalRevoked = !empty($row['approval_revoked_at']);
              $isOutsideEditWindow = !$rowDate || $rowDate < $editableLimitDate || $rowDate > $today;
              $disablePegawaiAction = $isApproved || (!$isApprovalRevoked && $isOutsideEditWindow);
              $disablePegawaiTitle = $isApproved
                ? 'Laporan sudah disahkan'
                : ((!$isApprovalRevoked && $isOutsideEditWindow) ? 'Batas edit maksimal 3 hari sebelumnya' : '');
              ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= DateHelper::hariTanggalIndo($row['tanggal']); ?></td>

                <td><?= htmlspecialchars($row['kegiatan']) ?></td>
                <td><?= htmlspecialchars($row['output']) ?></td>

                <td class="text-center">
                  <?php if ($row['bukti']): ?>
                    <a href="/public/uploads/bukti/<?= rawurlencode($row['bukti']) ?>" target="_blank" class="btn btn-info btn-sm">
                      <i class="fas fa-eye"></i> Lihat
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Tidak ada</span>
                  <?php endif; ?>
                </td>

                <td class="text-center">
                  <?php if (!empty($row['approval_revoked_at'])): ?>
                    <span class="badge badge-secondary">Pengesahan Dicabut</span><br>
                    <small>Dapat diperbaiki sampai disahkan kembali</small>
                  <?php elseif (($row['status'] ?? 'pending') === 'approved'): ?>
                    <span class="badge badge-success">Siap Cetak</span><br>
                    <small>Ditandatangani admin</small>
                  <?php elseif (($row['status'] ?? 'pending') === 'rejected'): ?>
                    <span class="badge badge-danger">Ditolak</span>
                    <?php if (!empty($row['rejection_note'])): ?>
                      <br><small><?= htmlspecialchars($row['rejection_note']) ?></small>
                    <?php endif; ?>
                    <?php if (!empty($isOutsideEditWindow)): ?>
                      <br><small class="text-muted">Batas edit sudah lewat</small>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="badge badge-warning">Menunggu</span><br>
                    <small>Belum bisa dicetak</small>
                    <?php if (!empty($isOutsideEditWindow)): ?>
                      <br><small class="text-muted">Batas edit sudah lewat</small>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>

                <td class="text-center">
                  <?php if ($disablePegawaiAction): ?>
                    <span title="<?= htmlspecialchars($disablePegawaiTitle) ?>">
                      <button
                        type="button"
                        class="btn btn-secondary btn-sm"
                        disabled
                        aria-label="<?= htmlspecialchars($disablePegawaiTitle) ?>">
                        <i class="fas fa-lock"></i>
                      </button>
                    </span>
                  <?php else: ?>
                    <div class="btn-group">
                      <button class="btn btn-warning btn-sm"
                        data-toggle="modal"
                        data-target="#editModal-<?= $row['kegiatan_id'] ?>">
                        <i class="fas fa-edit"></i>
                      </button>

                      <button class="btn btn-danger btn-sm ml-1"
                        data-toggle="modal"
                        data-target="#deleteModal-<?= $row['kegiatan_id'] ?>">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  <?php endif; ?>

                  <!-- Modal Edit -->
                  <div class="modal fade" id="editModal-<?= $row['kegiatan_id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">

                        <div class="modal-header bg-madrasah">
                          <h5 class="modal-title text-white">Edit Laporan</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>

                        <form method="POST" action="/pegawai/laporan/update" enctype="multipart/form-data">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-left">

                            <!-- Hidden ID -->
                            <input type="hidden" name="id" value="<?= $row['kegiatan_id'] ?>">

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
                              <input type="file" class="form-control" name="bukti" accept="image/*,application/pdf,video/*">
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
                  <div class="modal fade" id="deleteModal-<?= $row['kegiatan_id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">

                        <div class="modal-header bg-danger">
                          <h5 class="modal-title text-white">Hapus Laporan</h5>
                          <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>

                        <!-- Form langsung mengirim ke route dengan id -->
                        <form method="POST" action="/pegawai/laporan/delete">
                          <?= Csrf::input() ?>
                          <div class="modal-body text-left">

                            <!-- optional: kirim id via POST juga -->
                            <input type="hidden" name="id" value="<?= $row['kegiatan_id'] ?>">

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
              <td colspan="7" class="text-center text-muted">Belum ada laporan.</td>
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
