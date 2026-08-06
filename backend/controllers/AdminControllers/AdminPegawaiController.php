<?php

class AdminPegawaiController
{
    private PegawaiModel $pegawaiModel;
    private AuthService $authService;
    private AdminPegawaiService $adminPegawaiService;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();

        $this->authService = new AuthService(
            $this->pegawaiModel,
            new AdminModel()
        );

        $this->adminPegawaiService = new AdminPegawaiService(
            $this->pegawaiModel,
            new ImageUploadService()
        );
    }

    private function authorize(): void
    {
        $this->authService->requireAdmin();
    }

    private function buildFilter(): array
    {
        return [
            'keyword' => trim($_GET['keyword'] ?? '') ?: null,
            'jabatan' => trim($_GET['jabatan'] ?? '') ?: null,
            'jenis_kelamin' => trim($_GET['jenis_kelamin'] ?? '') ?: null,
        ];
    }

    private function buildDataFilter(array $request): array
    {
        return [
            'keyword' => trim($request['filter_keyword'] ?? '') ?: null,
            'jabatan' => trim($request['filter_jabatan'] ?? '') ?: null,
            'jenis_kelamin' => trim($request['filter_jenis_kelamin'] ?? '') ?: null,
        ];
    }

    private function h(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    public function kelolaPegawai(): void
    {
        $this->authorize();

        $filter = $this->buildFilter();
        view('admin/kelola_pegawai', [
            'title'   => 'Kelola Pegawai',
            'pegawai' => [],
            'filter'  => $filter
        ]);
    }

    public function data(array $request): void
    {
        $this->pegawaiData($request, false);
    }

    public function trashData(array $request): void
    {
        $this->pegawaiData($request, true);
    }

    private function pegawaiData(array $request, bool $deleted): void
    {
        $draw = max(0, (int) ($request['draw'] ?? 0));
        ob_start();

        try {
            $this->authorize();

            $filter = $this->buildDataFilter($request);
            $offset = max(0, (int) ($request['start'] ?? 0));
            $limit = (int) ($request['length'] ?? 10);
            $limit = $limit === -1 ? 100 : max(1, min(100, $limit));
            $search = is_array($request['search'] ?? null)
                ? trim((string) ($request['search']['value'] ?? ''))
                : '';
            $orderColumn = $this->pegawaiOrderColumn($request, $deleted);
            $orderDirection = strtoupper((string) ($request['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

            $total = $this->pegawaiModel->countPegawaiData(
                $deleted,
                $filter['keyword'],
                $filter['jabatan'],
                $filter['jenis_kelamin']
            );
            $filtered = $search !== ''
                ? $this->pegawaiModel->countPegawaiData(
                    $deleted,
                    $filter['keyword'],
                    $filter['jabatan'],
                    $filter['jenis_kelamin'],
                    $search
                )
                : $total;
            $rows = $this->pegawaiModel->getPegawaiDataPage(
                $deleted,
                $filter['keyword'],
                $filter['jabatan'],
                $filter['jenis_kelamin'],
                $search,
                $orderColumn,
                $orderDirection,
                $offset,
                $limit
            );

            $data = [];
            foreach ($rows as $index => $row) {
                $data[] = $deleted
                    ? $this->renderDeletedPegawaiRow($row, $offset + $index + 1)
                    : $this->renderPegawaiRow($row, $offset + $index + 1);
            }

            $this->sendDataTableJson([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            error_log('DataTables pegawai admin error: ' . $e->getMessage());
            $this->sendDataTableJson([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Gagal memuat data pegawai. Periksa log server untuk detail.',
            ]);
        }
        exit;
    }

    private function sendDataTableJson(array $payload): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        $json = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
        echo $json === false
            ? '{"draw":0,"recordsTotal":0,"recordsFiltered":0,"data":[],"error":"Gagal membentuk respons JSON."}'
            : $json;
    }

    private function pegawaiOrderColumn(array $request, bool $deleted): string
    {
        $column = (int) ($request['order'][0]['column'] ?? ($deleted ? 6 : 3));

        if ($deleted) {
            return match ($column) {
                3 => 'u.nama',
                4 => 'u.nip',
                5 => 'u.jabatan',
                6 => 'u.deleted_at',
                default => 'u.deleted_at',
            };
        }

        return match ($column) {
            3 => 'u.nama',
            4 => 'u.nip',
            5 => 'u.jabatan',
            6 => 'u.jenis_kelamin',
            7 => 'u.email',
            8 => 'u.no_wa',
            default => 'u.nama',
        };
    }

    private function renderPegawaiRow(array $row, int $number): array
    {
        return [
            '<input type="checkbox" class="pegawaiCheckbox" form="pegawaiBulkForm" name="pegawai_ids[]" value="' . (int) $row['id'] . '">',
            (string) $number,
            $this->renderPegawaiPhoto($row),
            $this->h($row['nama'] ?? ''),
            $this->h(!empty($row['nip']) ? $row['nip'] : ($row['nik'] ?? '')),
            $this->h($row['jabatan'] ?? ''),
            $this->h($row['jenis_kelamin'] ?? 'Tidak diketahui'),
            $this->h($row['email'] ?? ''),
            $this->h($row['no_wa'] ?? ''),
            $this->renderPegawaiActions($row),
        ];
    }

    private function renderDeletedPegawaiRow(array $row, int $number): array
    {
        $deletedAt = !empty($row['deleted_at']) ? new DateTimeImmutable((string) $row['deleted_at']) : null;
        $deletedHtml = $deletedAt
            ? '<span class="badge badge-dark">Terhapus</span><br><small>' . $deletedAt->format('d/m/Y H:i') . '</small>'
            : '';

        if (!empty($row['deleted_by_name'])) {
            $deletedHtml .= '<br><small>Oleh: ' . $this->h($row['deleted_by_name']) . '</small>';
        }

        return [
            '<input type="checkbox" class="deletedPegawaiCheckbox" form="pegawaiTrashBulkForm" name="pegawai_ids[]" value="' . (int) $row['id'] . '">',
            (string) $number,
            $this->renderPegawaiPhoto($row),
            $this->h($row['nama'] ?? ''),
            $this->h(!empty($row['nip']) ? $row['nip'] : ($row['nik'] ?? '')),
            $this->h($row['jabatan'] ?? ''),
            $deletedHtml,
            $this->renderDeletedPegawaiActions($row),
        ];
    }

    private function renderPegawaiPhoto(array $row): string
    {
        $foto = !empty($row['foto']) && $row['foto'] !== 'default_profile.svg'
            ? '/public/uploads/foto/' . rawurlencode((string) $row['foto'])
            : '/public/assets/img/avatars/default_profile.svg';

        return '<img src="' . $foto . '" alt="Foto Profil Pegawai" class="profile-img-mini mb-2">';
    }

    private function renderPegawaiActions(array $row): string
    {
        $id = (int) $row['id'];

        return '<div class="btn-group">'
            . '<button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editPegawaiModal-' . $id . '"><i class="fas fa-edit"></i></button>'
            . '<button class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#deletePegawaiModal-' . $id . '"><i class="fas fa-trash"></i></button>'
            . '</div>'
            . $this->renderEditPegawaiModal($row)
            . $this->renderDeletePegawaiModal($row);
    }

    private function renderDeletedPegawaiActions(array $row): string
    {
        $id = (int) $row['id'];

        return '<div class="btn-group">'
            . '<form method="POST" action="/admin/kelola/pegawai/restore" class="d-inline">'
            . Csrf::input()
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<button type="submit" class="btn btn-success btn-sm" title="Pulihkan pegawai"><i class="fas fa-trash-restore"></i></button>'
            . '</form>'
            . '<button class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#forceDeletePegawaiModal-' . $id . '" title="Hapus permanen"><i class="fas fa-times"></i></button>'
            . '</div>'
            . $this->renderForceDeletePegawaiModal($row);
    }

    private function renderEditPegawaiModal(array $row): string
    {
        $id = (int) $row['id'];
        $passwordId = 'passwordEditByAdmin-' . $id;
        $foto = !empty($row['foto']) && $row['foto'] !== 'default_profile.svg'
            ? '/public/uploads/foto/' . rawurlencode((string) $row['foto'])
            : '/public/assets/img/avatars/default_profile.svg';
        $jabatanOptions = '<option value="">-- Pilih Jabatan --</option>';

        foreach (JabatanHelper::list() as $jabatan) {
            $selected = $jabatan === ($row['jabatan'] ?? '') ? ' selected' : '';
            $jabatanOptions .= '<option value="' . $this->h($jabatan) . '"' . $selected . '>' . $this->h($jabatan) . '</option>';
        }

        return '
          <div class="modal fade" id="editPegawaiModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header bg-madrasah text-white">
                  <h5 class="modal-title">Edit Pegawai</h5>
                  <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form action="/admin/kelola/pegawai/update" method="POST" enctype="multipart/form-data">
                  ' . Csrf::input() . '
                  <div class="modal-body text-center">
                    <input type="hidden" name="id" value="' . $id . '">
                    <div class="form-group text-center">
                      <div class="position-relative d-inline-block my-3">
                        <img src="' . $foto . '" alt="Foto Profil Pegawai" class="rounded-circle preview-foto">
                        <label class="overlay-hover">
                          <div class="overlay-text"><i class="fas fa-camera"></i> Unggah Foto</div>
                          <input type="file" name="foto" accept="image/*" class="d-none" onchange="previewImage(this)">
                        </label>
                      </div>
                      <small class="text-muted d-block">Format file harus .jpg atau .png</small>
                    </div>
                    <hr>
                    <div class="row text-left mt-3">
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-label">Nama Lengkap (Beserta Gelar)<span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" value="' . $this->h($row['nama'] ?? '') . '" required>
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-label">NIP (Jika Ada)</label>
                        <input type="text" name="nip" class="form-control" value="' . $this->h($row['nip'] ?? '') . '">
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-label">NIK<span class="text-danger"> *</span></label>
                        <input type="text" name="nik" class="form-control" value="' . $this->h($row['nik'] ?? '') . '">
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-label">Jabatan<span class="text-danger"> *</span></label>
                        <select name="jabatan" class="form-control" required>' . $jabatanOptions . '</select>
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-control" name="jenis_kelamin">
                          <option value="Tidak diketahui"' . (($row['jenis_kelamin'] ?? '') === 'Tidak diketahui' ? ' selected' : '') . '>-- Pilih Jenis Kelamin --</option>
                          <option value="Laki-laki"' . (($row['jenis_kelamin'] ?? '') === 'Laki-laki' ? ' selected' : '') . '>Laki-laki</option>
                          <option value="Perempuan"' . (($row['jenis_kelamin'] ?? '') === 'Perempuan' ? ' selected' : '') . '>Perempuan</option>
                        </select>
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-label">Ubah Login Password</label>
                        <div class="input-group password-group">
                          <input type="password" id="' . $passwordId . '" name="password" class="form-control" autocomplete="new-password" placeholder="(Kosongkan jika tidak diubah)">
                          <div class="input-group-append">
                            <button class="btn btn-secondary" type="button" onclick="togglePassword(\'' . $passwordId . '\', this)"><i class="fas fa-eye"></i></button>
                          </div>
                        </div>
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="' . $this->h($row['email'] ?? '') . '">
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-label">No WhatsApp</label>
                        <input type="text" name="no_wa" value="' . $this->h($row['no_wa'] ?? '') . '" class="form-control">
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
          </div>';
    }

    private function renderDeletePegawaiModal(array $row): string
    {
        $id = (int) $row['id'];

        return '
          <div class="modal fade" id="deletePegawaiModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header bg-danger">
                  <h5 class="modal-title text-white">Hapus Pegawai</h5>
                  <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="/admin/kelola/pegawai/delete">
                  ' . Csrf::input() . '
                  <div class="modal-body text-left">
                    <input type="hidden" name="id" value="' . $id . '">
                    <p>Yakin ingin menghapus pegawai dengan nama <strong>' . $this->h($row['nama'] ?? '') . '</strong>?</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                  </div>
                </form>
              </div>
            </div>
          </div>';
    }

    private function renderForceDeletePegawaiModal(array $row): string
    {
        $id = (int) $row['id'];

        return '
          <div class="modal fade" id="forceDeletePegawaiModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header bg-danger">
                  <h5 class="modal-title text-white">Hapus Permanen Pegawai</h5>
                  <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="/admin/kelola/pegawai/force-delete">
                  ' . Csrf::input() . '
                  <div class="modal-body text-left">
                    <input type="hidden" name="id" value="' . $id . '">
                    <p>Pegawai <strong>' . $this->h($row['nama'] ?? '') . '</strong> akan dihapus permanen.</p>
                    <p class="text-danger mb-0">Aksi ini tidak dapat dibatalkan dan hanya diproses jika pegawai belum memiliki riwayat laporan.</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus Permanen</button>
                  </div>
                </form>
              </div>
            </div>
          </div>';
    }

    public function create(): void
    {
        $this->authorize();

        try {
            $this->adminPegawaiService->create($_POST);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai berhasil ditambahkan.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/pegawai');
        exit;
    }

    public function update(): void
    {
        $this->authorize();

        try {
            if (empty($_POST['id'])) {
                throw new Exception('ID pegawai tidak valid.');
            }

            $this->adminPegawaiService->update(
                (int) $_POST['id'],
                $_POST,
                $_FILES['foto'] ?? null
            );

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Data pegawai berhasil diperbarui.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/pegawai');
        exit;
    }

    public function delete(): void
    {
        $this->authorize();

        try {
            if (empty($_POST['id'])) {
                throw new Exception('ID pegawai tidak valid.');
            }

            $admin = $this->authService->admin();
            $this->adminPegawaiService->deleteByAdmin((int) $_POST['id'], (int) $admin['id']);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai dipindahkan ke Sampah.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/pegawai');
        exit;
    }

    public function restore(): void
    {
        $this->authorize();

        try {
            if (empty($_POST['id'])) {
                throw new Exception('ID pegawai tidak valid.');
            }

            $this->adminPegawaiService->restore((int) $_POST['id']);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai berhasil dipulihkan.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan/sampah');
        exit;
    }

    public function forceDelete(): void
    {
        $this->authorize();

        try {
            if (empty($_POST['id'])) {
                throw new Exception('ID pegawai tidak valid.');
            }

            $this->adminPegawaiService->forceDelete((int) $_POST['id']);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai berhasil dihapus permanen.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan/sampah');
        exit;
    }

    public function bulkProcess(): void
    {
        $this->authorize();

        $action = trim($_POST['action'] ?? '');
        $pegawaiIds = $_POST['pegawai_ids'] ?? [];

        try {
            $admin = $this->authService->admin();
            $processed = $this->adminPegawaiService->bulkProcess(
                (array) $pegawaiIds,
                $action,
                (int) $admin['id']
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => "{$processed} pegawai berhasil diproses."
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        $redirect = in_array($action, ['restore', 'force_delete'], true)
            ? '/admin/kelola/laporan/sampah'
            : '/admin/kelola/pegawai';

        header("Location: {$redirect}");
        exit;
    }
}
