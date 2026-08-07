<?php ob_start(); ?>
<?php $jabatanModals = ''; ?>

<h1 class="h4 mb-4 text-gray-800">Kelola Jabatan</h1>

<?php if ($flash = Session::getFlash('flash')): ?>
  <div class="alert shadow alert-<?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
<?php endif; ?>

<div class="card shadow mb-4 border-left-success">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-success">Tambah Jabatan</h6>
  </div>
  <div class="card-body">
    <form action="/admin/kelola/jabatan/store" method="POST" class="form-row align-items-end">
      <?= Csrf::input() ?>
      <div class="form-group col-md-7 mb-3">
        <label for="namaJabatan">Nama Jabatan</label>
        <input
          type="text"
          name="nama"
          id="namaJabatan"
          class="form-control"
          maxlength="100"
          placeholder="Contoh: Guru Mapel"
          required>
      </div>
      <div class="form-group col-md-3 mb-3">
        <div class="custom-control custom-switch mt-md-4">
          <input type="checkbox" class="custom-control-input" id="jabatanAktif" name="is_active" value="1" checked>
          <label class="custom-control-label" for="jabatanAktif">Aktif di form pegawai</label>
        </div>
      </div>
      <div class="form-group col-md-2 mb-3">
        <button type="submit" class="btn btn-madrasah btn-block">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow mb-4 border-left-success">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-success">Daftar Jabatan</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table
        class="table table-bordered table-hover mb-0"
        id="dataTable"
        data-order-column="1"
        data-order-direction="asc"
        data-order-disabled="4"
        width="100%">
        <thead class="bg-success text-white text-center">
          <tr>
            <th style="width: 70px;">No</th>
            <th>Nama Jabatan</th>
            <th style="width: 150px;">Status</th>
            <th style="width: 150px;">Pegawai Aktif</th>
            <th style="width: 210px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($jabatanList as $index => $row): ?>
            <?php
            $id = (int) $row['id'];
            $nama = (string) $row['nama'];
            $isActive = !empty($row['is_active']);
            $pegawaiCount = (int) ($row['pegawai_count'] ?? 0);
            ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td class="font-weight-bold"><?= htmlspecialchars($nama) ?></td>
              <td>
                <span class="badge badge-<?= $isActive ? 'success' : 'secondary' ?>">
                  <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </td>
              <td><?= $pegawaiCount ?> pegawai</td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editJabatanModal-<?= $id ?>" title="Edit jabatan">
                    <i class="fas fa-edit"></i>
                  </button>

                  <form action="/admin/kelola/jabatan/toggle" method="POST" class="d-inline">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn btn-<?= $isActive ? 'secondary' : 'success' ?> btn-sm ml-1" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>">
                      <i class="fas <?= $isActive ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                    </button>
                  </form>

                  <button class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#deleteJabatanModal-<?= $id ?>" title="Hapus jabatan">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <?php ob_start(); ?>
            <div class="modal fade" id="editJabatanModal-<?= $id ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header bg-madrasah text-white">
                    <h5 class="modal-title">Edit Jabatan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                  </div>
                  <form action="/admin/kelola/jabatan/update" method="POST">
                    <?= Csrf::input() ?>
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <div class="form-group">
                        <label>Nama Jabatan</label>
                        <input type="text" name="nama" class="form-control" maxlength="100" value="<?= htmlspecialchars($nama) ?>" required>
                      </div>
                      <div class="custom-control custom-switch">
                        <input
                          type="checkbox"
                          class="custom-control-input"
                          id="jabatanActiveEdit-<?= $id ?>"
                          name="is_active"
                          value="1"
                          <?= $isActive ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="jabatanActiveEdit-<?= $id ?>">Aktif di form pegawai</label>
                      </div>
                      <small class="text-muted d-block mt-2">Jika nama jabatan diganti, data pegawai yang memakai jabatan lama ikut diperbarui.</small>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-madrasah">Simpan Perubahan</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="modal fade" id="deleteJabatanModal-<?= $id ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Hapus Jabatan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                  </div>
                  <form action="/admin/kelola/jabatan/delete" method="POST">
                    <?= Csrf::input() ?>
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <p>Yakin ingin menghapus jabatan <strong><?= htmlspecialchars($nama) ?></strong>?</p>
                      <?php if ($pegawaiCount > 0): ?>
                        <p class="text-danger mb-0">Jabatan ini masih dipakai <?= $pegawaiCount ?> pegawai. Sistem akan menolak hapus; gunakan Nonaktifkan jika hanya ingin menghilangkannya dari form.</p>
                      <?php else: ?>
                        <p class="text-muted mb-0">Aksi ini tidak dapat dibatalkan.</p>
                      <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php $jabatanModals .= ob_get_clean(); ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $jabatanModals ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
