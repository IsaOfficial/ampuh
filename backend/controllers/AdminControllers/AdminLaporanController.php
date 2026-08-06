<?php

class AdminLaporanController
{
    private PegawaiModel $pegawaiModel;
    private LaporanQueryModel $laporanQuery;
    private AuthService $authService;
    private LaporanService $laporanService;

    public function __construct()
    {
        $db = Database::getConnection();

        $this->pegawaiModel = new PegawaiModel();

        $this->laporanQuery = new LaporanQueryModel(
            $db
        );

        $this->authService = new AuthService(
            $this->pegawaiModel,
            new AdminModel()
        );

        $this->laporanService = new LaporanService(
            new LaporanHarianModel($db),
            new LaporanKegiatanModel($db),
            new DocumentUploadService()
        );
    }

    private function authorize(): void
    {
        $this->authService->requireAdmin();
    }

    private function laporanFilterFromRequest(array $request, bool $fromDataTable = false): array
    {
        if ($fromDataTable) {
            return [
                'pegawai_id' => isset($request['filter_pegawai_id']) && $request['filter_pegawai_id'] !== '' ? (int) $request['filter_pegawai_id'] : null,
                'start' => trim($request['filter_start'] ?? '') ?: null,
                'end' => trim($request['filter_end'] ?? '') ?: null,
                'status' => trim($request['filter_status'] ?? '') ?: null,
            ];
        }

        return [
            'pegawai_id' => isset($request['pegawai_id']) && $request['pegawai_id'] !== '' ? (int) $request['pegawai_id'] : null,
            'start' => trim($request['start'] ?? '') ?: null,
            'end' => trim($request['end'] ?? '') ?: null,
            'status' => trim($request['status'] ?? '') ?: null,
        ];
    }

    private function h(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    public function kelolaLaporan(): void
    {
        $this->authorize();
        $this->laporanService->purgeExpiredDeletedKegiatan();

        $pegawaiList = $this->pegawaiModel->getAllPegawai();
        $filter = $this->laporanFilterFromRequest($_GET);

        if ($filter['status'] === 'deleted') {
            header('Location: /admin/kelola/laporan/sampah');
            exit;
        }

        view('admin/kelola_laporan', [
            'title'        => 'Kelola Laporan',
            'laporan'      => [],
            'pegawai_list' => $pegawaiList,
            'filter'       => $filter
        ]);
    }

    public function data(array $request): void
    {
        $draw = max(0, (int) ($request['draw'] ?? 0));
        ob_start();

        try {
            $this->authorize();

            $filter = $this->laporanFilterFromRequest($request, true);
            $offset = max(0, (int) ($request['start'] ?? 0));
            $limit = (int) ($request['length'] ?? 10);
            $limit = $limit === -1 ? 100 : max(1, min(100, $limit));
            $search = is_array($request['search'] ?? null)
                ? trim((string) ($request['search']['value'] ?? ''))
                : '';
            $orderColumn = $this->datatableOrderColumn($request);
            $orderDirection = strtoupper((string) ($request['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            $total = $this->laporanQuery->countLaporanByAdmin(
                $filter['pegawai_id'],
                $filter['start'],
                $filter['end'],
                $filter['status']
            );

            $filtered = $search !== ''
                ? $this->laporanQuery->countLaporanByAdmin(
                    $filter['pegawai_id'],
                    $filter['start'],
                    $filter['end'],
                    $filter['status'],
                    $search
                )
                : $total;

            $rows = $this->laporanQuery->getLaporanByAdminPage(
                $filter['pegawai_id'],
                $filter['start'],
                $filter['end'],
                $filter['status'],
                $search,
                $orderColumn,
                $orderDirection,
                $offset,
                $limit
            );

            $data = [];
            foreach ($rows as $index => $row) {
                $data[] = $this->renderLaporanDataTableRow($row, $offset + $index + 1);
            }

            $this->sendDataTableJson([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            error_log('DataTables laporan admin error: ' . $e->getMessage());
            $this->sendDataTableJson([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Gagal memuat data laporan. Periksa log server untuk detail.',
            ]);
        }
        exit;
    }

    public function trashData(array $request): void
    {
        $draw = max(0, (int) ($request['draw'] ?? 0));
        ob_start();

        try {
            $this->authorize();

            $filter = $this->laporanFilterFromRequest($request, true);
            $filter['status'] = 'deleted';
            $offset = max(0, (int) ($request['start'] ?? 0));
            $limit = (int) ($request['length'] ?? 10);
            $limit = $limit === -1 ? 100 : max(1, min(100, $limit));
            $search = is_array($request['search'] ?? null)
                ? trim((string) ($request['search']['value'] ?? ''))
                : '';
            $orderColumn = $this->trashDatatableOrderColumn($request);
            $orderDirection = strtoupper((string) ($request['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            $total = $this->laporanQuery->countLaporanByAdmin(
                $filter['pegawai_id'],
                $filter['start'],
                $filter['end'],
                'deleted'
            );
            $filtered = $search !== ''
                ? $this->laporanQuery->countLaporanByAdmin(
                    $filter['pegawai_id'],
                    $filter['start'],
                    $filter['end'],
                    'deleted',
                    $search
                )
                : $total;
            $rows = $this->laporanQuery->getLaporanByAdminPage(
                $filter['pegawai_id'],
                $filter['start'],
                $filter['end'],
                'deleted',
                $search,
                $orderColumn,
                $orderDirection,
                $offset,
                $limit
            );

            $data = [];
            foreach ($rows as $index => $row) {
                $data[] = $this->renderTrashLaporanDataTableRow($row, $offset + $index + 1);
            }

            $this->sendDataTableJson([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            error_log('DataTables sampah laporan admin error: ' . $e->getMessage());
            $this->sendDataTableJson([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Gagal memuat data sampah laporan. Periksa log server untuk detail.',
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

    private function datatableOrderColumn(array $request): string
    {
        $column = (int) ($request['order'][0]['column'] ?? 2);

        return match ($column) {
            2 => 'lh.tanggal',
            4 => 'u.nama',
            5 => 'lk.kegiatan',
            6 => 'lk.output',
            8 => "COALESCE(lk.approval_status, 'pending')",
            default => 'lh.tanggal',
        };
    }

    private function trashDatatableOrderColumn(array $request): string
    {
        $column = (int) ($request['order'][0]['column'] ?? 7);

        return match ($column) {
            2 => 'lh.tanggal',
            3 => 'u.nama',
            4 => 'lk.kegiatan',
            5 => 'lk.output',
            6 => 'lk.bukti',
            7 => 'lk.deleted_at',
            default => 'lk.deleted_at',
        };
    }

    private function renderLaporanDataTableRow(array $row, int $number): array
    {
        return [
            '<input type="checkbox" class="rowCheckbox" form="bulkProcessForm" name="kegiatan_ids[]" value="' . (int) $row['kegiatan_id'] . '">',
            (string) $number,
            DateHelper::hariTanggalIndo((string) $row['tanggal']),
            $this->renderLaporanPhoto($row),
            $this->h($row['nama_pegawai'] ?? ''),
            $this->h($row['kegiatan'] ?? ''),
            $this->h($row['output'] ?? ''),
            $this->renderLaporanEvidence($row),
            $this->renderLaporanStatus($row),
            $this->renderLaporanActions($row),
        ];
    }

    private function renderTrashLaporanDataTableRow(array $row, int $number): array
    {
        return [
            '<input type="checkbox" class="rowCheckbox" form="trashBulkForm" name="kegiatan_ids[]" value="' . (int) $row['kegiatan_id'] . '">',
            (string) $number,
            DateHelper::hariTanggalIndo((string) $row['tanggal']),
            $this->h($row['nama_pegawai'] ?? ''),
            $this->h($row['kegiatan'] ?? ''),
            $this->h($row['output'] ?? ''),
            $this->renderLaporanEvidence($row),
            $this->renderDeletedInfo($row),
            $this->renderTrashLaporanActions($row),
        ];
    }

    private function renderDeletedInfo(array $row): string
    {
        $deletedAt = !empty($row['deleted_at']) ? new DateTimeImmutable((string) $row['deleted_at']) : null;
        $deleteExpiresAt = $deletedAt ? $deletedAt->modify('+14 days') : null;
        $remainingDeleteDays = $deleteExpiresAt
            ? max(0, (int) (new DateTimeImmutable('today'))->diff($deleteExpiresAt)->format('%r%a'))
            : null;
        $html = $deletedAt
            ? '<span class="badge badge-dark">Terhapus</span><br><small>' . $deletedAt->format('d/m/Y H:i') . '</small>'
            : '';

        if (!empty($row['deleted_by_name'])) {
            $html .= '<br><small>Oleh: ' . $this->h($row['deleted_by_name']) . '</small>';
        }

        if ($remainingDeleteDays !== null) {
            $html .= '<br><small class="text-muted">Permanen dalam ' . $remainingDeleteDays . ' hari</small>';
        }

        return $html;
    }

    private function renderLaporanPhoto(array $row): string
    {
        $foto = !empty($row['foto_pegawai']) && $row['foto_pegawai'] !== 'default_profile.svg'
            ? '/public/uploads/foto/' . rawurlencode((string) $row['foto_pegawai'])
            : '/public/assets/img/avatars/default_profile.svg';

        return '<img src="' . $foto . '" alt="Foto Profil Pegawai" class="profile-img-mini mb-3">';
    }

    private function renderLaporanEvidence(array $row): string
    {
        if (empty($row['bukti'])) {
            return '<span class="text-muted">Tidak ada</span>';
        }

        $filename = (string) $row['bukti'];
        return '<a href="/public/uploads/bukti/' . rawurlencode($filename) . '" target="_blank" class="btn btn-info btn-sm">'
            . '<i class="fas fa-eye"></i> Lihat</a>';
    }

    private function renderLaporanStatus(array $row): string
    {
        $status = $row['status'] ?? 'pending';

        if (!empty($row['approval_revoked_at'])) {
            $html = '<span class="badge badge-secondary">Persetujuan Dicabut</span>';
            if (!empty($row['verification_token'])) {
                $html .= '<br><small>Kode lama: ' . $this->h(substr((string) $row['verification_token'], 0, 12)) . '</small>';
            }
            return $html;
        }

        if ($status === 'approved') {
            $html = '<span class="badge badge-success">Disetujui</span>';
            if (!empty($row['verification_token'])) {
                $html .= '<br><br><small>Kode: ' . $this->h(substr((string) $row['verification_token'], 0, 12)) . '</small>';
            }
            return $html;
        }

        if ($status === 'rejected') {
            $html = '<span class="badge badge-danger">Ditolak</span>';
            if (!empty($row['rejection_note'])) {
                $html .= '<br><small>' . $this->h($row['rejection_note']) . '</small>';
            }
            return $html;
        }

        return '<span class="badge badge-warning">Menunggu</span>';
    }

    private function renderLaporanActions(array $row): string
    {
        $id = (int) $row['kegiatan_id'];
        $isApprovedActive = empty($row['deleted_at'])
            && empty($row['approval_revoked_at'])
            && (($row['status'] ?? 'pending') === 'approved');

        $deleteButton = $isApprovedActive
            ? '<button class="btn btn-secondary btn-sm ml-1" type="button" disabled title="Cabut persetujuan sebelum menghapus"><i class="fas fa-lock"></i></button>'
            : '<button class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#deleteModal-' . $id . '"><i class="fas fa-trash"></i></button>';

        return '<div class="btn-group">'
            . '<button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editModal-' . $id . '"><i class="fas fa-edit"></i></button>'
            . $deleteButton
            . '</div>'
            . $this->renderLaporanEditModal($row)
            . ($isApprovedActive ? '' : $this->renderLaporanDeleteModal($row));
    }

    private function renderLaporanEditModal(array $row): string
    {
        $id = (int) $row['kegiatan_id'];
        $bukti = (string) ($row['bukti'] ?? '');
        $buktiSaatIni = $bukti !== ''
            ? '<div class="form-group"><label>Bukti Saat Ini:</label><br><a href="/public/uploads/bukti/' . rawurlencode($bukti) . '" target="_blank">' . $this->h($bukti) . '</a></div>'
            : '';

        return '
          <div class="modal fade" id="editModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header bg-madrasah">
                  <h5 class="modal-title text-white">Edit Laporan</h5>
                  <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="/admin/kelola/laporan/update" enctype="multipart/form-data">
                  ' . Csrf::input() . '
                  <div class="modal-body text-left">
                    <input type="hidden" name="id" value="' . $id . '">
                    <div class="form-group">
                      <label>Tanggal</label>
                      <input type="date" class="form-control" name="tanggal" value="' . $this->h($row['tanggal'] ?? '') . '" readonly>
                    </div>
                    <div class="form-group">
                      <label>Pegawai</label>
                      <input type="text" class="form-control" value="' . $this->h($row['nama_pegawai'] ?? '') . '" readonly>
                    </div>
                    <div class="form-group">
                      <label>Kegiatan</label>
                      <input type="text" class="form-control" name="kegiatan" value="' . $this->h($row['kegiatan'] ?? '') . '">
                    </div>
                    <div class="form-group">
                      <label>Output</label>
                      <textarea class="form-control" name="output" rows="2">' . $this->h($row['output'] ?? '') . '</textarea>
                    </div>
                    <div class="form-group">
                      <label>Ubah Bukti (opsional)</label>
                      <input type="file" class="form-control" name="bukti" accept="image/*,application/pdf,video/*">
                    </div>
                    ' . $buktiSaatIni . '
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

    private function renderLaporanDeleteModal(array $row): string
    {
        $id = (int) $row['kegiatan_id'];

        return '
          <div class="modal fade" id="deleteModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header bg-danger">
                  <h5 class="modal-title text-white">Hapus Laporan</h5>
                  <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="/admin/kelola/laporan/delete">
                  ' . Csrf::input() . '
                  <div class="modal-body text-left">
                    <input type="hidden" name="id" value="' . $id . '">
                    <p>
                      Laporan kegiatan <strong>' . $this->h($row['kegiatan'] ?? '') . '</strong>
                      milik <strong>' . $this->h($row['nama_pegawai'] ?? '') . '</strong>
                      pada <strong>' . DateHelper::hariTanggalIndo((string) $row['tanggal']) . '</strong>
                      akan dipindahkan ke daftar terhapus.
                    </p>
                    <p class="mb-0 text-muted">Laporan dapat dipulihkan oleh admin selama 14 hari sebelum otomatis dihapus permanen.</p>
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

    private function renderTrashLaporanActions(array $row): string
    {
        $id = (int) $row['kegiatan_id'];

        return '<div class="btn-group">'
            . '<form method="POST" action="/admin/kelola/laporan/restore" class="d-inline">'
            . Csrf::input()
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<button type="submit" class="btn btn-success btn-sm" title="Pulihkan laporan"><i class="fas fa-trash-restore"></i></button>'
            . '</form>'
            . '<button class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#forceDeleteModal-' . $id . '" title="Hapus permanen"><i class="fas fa-times"></i></button>'
            . '</div>'
            . $this->renderTrashLaporanForceDeleteModal($row);
    }

    private function renderTrashLaporanForceDeleteModal(array $row): string
    {
        $id = (int) $row['kegiatan_id'];

        return '
          <div class="modal fade" id="forceDeleteModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header bg-danger">
                  <h5 class="modal-title text-white">Hapus Permanen</h5>
                  <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="/admin/kelola/laporan/force-delete">
                  ' . Csrf::input() . '
                  <div class="modal-body text-left">
                    <input type="hidden" name="id" value="' . $id . '">
                    <p>
                      Laporan kegiatan <strong>' . $this->h($row['kegiatan'] ?? '') . '</strong>
                      milik <strong>' . $this->h($row['nama_pegawai'] ?? '') . '</strong>
                      akan dihapus permanen beserta file buktinya.
                    </p>
                    <p class="text-danger mb-0">Aksi ini tidak dapat dibatalkan.</p>
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

    public function sampahLaporan(): void
    {
        $this->authorize();
        $this->laporanService->purgeExpiredDeletedKegiatan();

        $pegawaiList = $this->pegawaiModel->getAllPegawai();
        $filter = [
            'pegawai_id' => isset($_GET['pegawai_id']) && $_GET['pegawai_id'] !== '' ? (int) $_GET['pegawai_id'] : null,
            'start' => trim($_GET['start'] ?? '') ?: null,
            'end' => trim($_GET['end'] ?? '') ?: null,
            'status' => 'deleted',
        ];

        view('admin/sampah', [
            'title'          => 'Sampah',
            'laporan'        => [],
            'deletedPegawai' => [],
            'pegawai_list'   => $pegawaiList,
            'filter'         => $filter
        ]);
    }


    private function rememberCreateInput(array $r): void
    {
        Session::flash('old_admin_laporan_create', [
            'pegawai_id' => (string)($r['pegawai_id'] ?? ''),
            'tanggal' => (string)($r['tanggal'] ?? ''),
            'kegiatan' => array_values(array_map('strval', (array)($r['kegiatan'] ?? []))),
            'output' => array_values(array_map('strval', (array)($r['output'] ?? []))),
        ]);
    }

    public function create(array $r): void
    {
        $this->authorize();

        $pegawaiId = (int) ($r['pegawai_id'] ?? 0);
        $tanggal   = $r['tanggal']  ?? '';
        $kegiatan  = $r['kegiatan'] ?? [];
        $output    = $r['output']   ?? [];
        $files     = $r['bukti'] ?? [];

        try {
            $this->laporanService->createKegiatan(
                $pegawaiId,
                $tanggal,
                $kegiatan,
                $output,
                $files,
                true
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil ditambahkan.'
            ]);
        } catch (Exception $e) {
            $this->rememberCreateInput($r);

            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan');
        exit;
    }

    public function update(array $r): void
    {
        $this->authorize();

        try {
            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->updateKegiatanByAdmin(
                (int)$r['id'],
                $r['kegiatan'],
                $r['output'],
                $r['bukti'] ?? []
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil diubah.'
            ]);
        } catch (Exception $e) {

            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan');
        exit;
    }

    public function delete(array $r): void
    {
        $this->authorize();

        try {
            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $admin = $this->authService->admin();
            $this->laporanService->deleteKegiatanByAdmin(
                (int)$r['id'],
                (int)$admin['id']
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan dipindahkan ke daftar terhapus.'
            ]);
        } catch (Exception $e) {

            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan');
        exit;
    }

    public function restore(array $r): void
    {
        $this->authorize();

        try {
            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->restoreKegiatanByAdmin((int)$r['id']);

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil dipulihkan.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan/sampah');
        exit;
    }

    public function forceDelete(array $r): void
    {
        $this->authorize();

        try {
            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->forceDeleteKegiatanByAdmin((int)$r['id']);

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil dihapus permanen.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan/sampah');
        exit;
    }

    public function bulkProcess(array $r): void
    {
        $this->authorize();

        try {
            $admin = $this->authService->admin();
            $processed = $this->laporanService->bulkProcessByAdmin(
                $r['kegiatan_ids'] ?? $r['laporan_ids'] ?? [],
                $r['action'] ?? '',
                (int) $admin['id'],
                $admin['nama'] ?? 'Ka. TU MTs Negeri 1 Jepara',
                trim($r['signature_note'] ?? '') ?: null,
                trim($r['rejection_note'] ?? '') ?: null
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => $processed . ' kegiatan berhasil diproses.'
            ]);
        } catch (Exception $e) {

            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        $redirect = in_array(($r['action'] ?? ''), ['restore', 'force_delete'], true)
            ? '/admin/kelola/laporan/sampah'
            : '/admin/kelola/laporan';

        header('Location: ' . $redirect);
        exit;
    }
}
