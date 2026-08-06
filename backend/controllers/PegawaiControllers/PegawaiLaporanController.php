<?php

class PegawaiLaporanController
{
    private LaporanQueryModel $laporanQuery;
    private AuthService $authService;
    private LaporanService $laporanService;

    public function __construct()
    {
        $db = Database::getConnection();

        $this->laporanQuery = new LaporanQueryModel(
            $db
        );

        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );

        $this->laporanService = new LaporanService(
            new LaporanHarianModel($db),
            new LaporanKegiatanModel($db),
            new DocumentUploadService(),
        );
    }

    public function riwayatLaporan(): void
    {
        $pegawai = $this->authService->pegawai();
        $this->laporanService->purgeExpiredDeletedKegiatan();

        $hasApprovedReport = $this->laporanQuery->countLaporanByPegawaiData(
            (int) $pegawai['id'],
            false,
            null,
            null,
            true
        ) > 0;

        view('pegawai/laporan', [
            'title'             => 'Riwayat Laporan',
            'laporan'           => [],
            'hasApprovedReport' => $hasApprovedReport
        ]);
    }

    public function sampahLaporan(): void
    {
        $pegawai = $this->authService->pegawai();
        $this->laporanService->purgeExpiredDeletedKegiatan();

        $filter = [
            'start' => trim($_GET['start'] ?? '') ?: null,
            'end' => trim($_GET['end'] ?? '') ?: null,
        ];

        view('pegawai/sampah', [
            'title' => 'Sampah Laporan',
            'laporan' => [],
            'filter' => $filter,
        ]);
    }

    public function data(array $request): void
    {
        $this->laporanData($request, false);
    }

    public function trashData(array $request): void
    {
        $this->laporanData($request, true);
    }

    private function laporanData(array $request, bool $deleted): void
    {
        $draw = max(0, (int) ($request['draw'] ?? 0));
        ob_start();

        try {
            $pegawai = $this->authService->pegawai();
            $offset = max(0, (int) ($request['start'] ?? 0));
            $limit = (int) ($request['length'] ?? 10);
            $limit = $limit === -1 ? 100 : max(1, min(100, $limit));
            $search = is_array($request['search'] ?? null)
                ? trim((string) ($request['search']['value'] ?? ''))
                : '';
            $start = trim($request['filter_start'] ?? '') ?: null;
            $end = trim($request['filter_end'] ?? '') ?: null;
            $orderColumn = $this->laporanOrderColumn($request, $deleted);
            $orderDirection = strtoupper((string) ($request['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            $total = $this->laporanQuery->countLaporanByPegawaiData((int) $pegawai['id'], $deleted, $start, $end);
            $filtered = $search !== ''
                ? $this->laporanQuery->countLaporanByPegawaiData((int) $pegawai['id'], $deleted, $start, $end, false, $search)
                : $total;
            $rows = $this->laporanQuery->getLaporanByPegawaiPage(
                (int) $pegawai['id'],
                $deleted,
                $start,
                $end,
                false,
                $search,
                $orderColumn,
                $orderDirection,
                $offset,
                $limit
            );

            $data = [];
            foreach ($rows as $index => $row) {
                $data[] = $deleted
                    ? $this->renderTrashLaporanRow($row, $offset + $index + 1)
                    : $this->renderLaporanRow($row, $offset + $index + 1);
            }

            $this->sendDataTableJson([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            error_log('DataTables laporan pegawai error: ' . $e->getMessage());
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

    private function h(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function laporanOrderColumn(array $request, bool $deleted): string
    {
        $column = (int) ($request['order'][0]['column'] ?? 2);

        return match ($column) {
            2 => 'lh.tanggal',
            3 => 'lk.kegiatan',
            4 => 'lk.output',
            5 => 'lk.bukti',
            6 => $deleted ? 'lk.deleted_at' : "COALESCE(lk.approval_status, 'pending')",
            default => $deleted ? 'lk.deleted_at' : 'lh.tanggal',
        };
    }

    private function renderLaporanRow(array $row, int $number): array
    {
        return [
            '<input type="checkbox" class="pegawaiLaporanCheckbox" form="pegawaiLaporanBulkForm" name="kegiatan_ids[]" value="' . (int) $row['kegiatan_id'] . '">',
            (string) $number,
            DateHelper::hariTanggalIndo((string) $row['tanggal']),
            $this->h($row['kegiatan'] ?? ''),
            $this->h($row['output'] ?? ''),
            $this->renderEvidence($row),
            $this->renderStatus($row),
            $this->renderActions($row),
        ];
    }

    private function renderTrashLaporanRow(array $row, int $number): array
    {
        $deletedAt = !empty($row['deleted_at']) ? new DateTimeImmutable((string) $row['deleted_at']) : null;
        $deleteExpiresAt = $deletedAt ? $deletedAt->modify('+14 days') : null;
        $remainingDeleteDays = $deleteExpiresAt
            ? max(0, (int) (new DateTimeImmutable('today'))->diff($deleteExpiresAt)->format('%r%a'))
            : null;
        $deletedHtml = $deletedAt
            ? '<span class="badge badge-dark">Terhapus</span><br><small>' . $deletedAt->format('d/m/Y H:i') . '</small>'
            : '';

        if ($remainingDeleteDays !== null) {
            $deletedHtml .= '<br><small class="text-muted">Permanen dalam ' . $remainingDeleteDays . ' hari</small>';
        }

        return [
            '<input type="checkbox" class="pegawaiTrashCheckbox" form="pegawaiTrashBulkForm" name="kegiatan_ids[]" value="' . (int) $row['kegiatan_id'] . '">',
            (string) $number,
            DateHelper::hariTanggalIndo((string) $row['tanggal']),
            $this->h($row['kegiatan'] ?? ''),
            $this->h($row['output'] ?? ''),
            $this->renderEvidence($row),
            $deletedHtml,
            $this->renderTrashActions($row),
        ];
    }

    private function renderEvidence(array $row): string
    {
        if (empty($row['bukti'])) {
            return '<span class="text-muted">Tidak ada</span>';
        }

        $filename = (string) $row['bukti'];
        return '<a href="/public/uploads/bukti/' . rawurlencode($filename) . '" target="_blank" class="btn btn-info btn-sm">'
            . '<i class="fas fa-eye"></i> Lihat</a>';
    }

    private function renderStatus(array $row): string
    {
        $status = $row['status'] ?? 'pending';
        [$isOutsideEditWindow] = $this->pegawaiEditState($row);

        if (!empty($row['approval_revoked_at'])) {
            return '<span class="badge badge-secondary">Persetujuan Dicabut</span><br><small>Dapat diperbaiki kembali</small>';
        }

        if ($status === 'approved') {
            return '<span class="badge badge-success">Siap Cetak</span><br><small>Disetujui</small>';
        }

        if ($status === 'rejected') {
            $html = '<span class="badge badge-danger">Ditolak</span>';
            if (!empty($row['rejection_note'])) {
                $html .= '<br><small>' . $this->h($row['rejection_note']) . '</small>';
            }
            if ($isOutsideEditWindow) {
                $html .= '<br><small class="text-muted">Batas edit sudah lewat</small>';
            }
            return $html;
        }

        $html = '<span class="badge badge-warning">Menunggu</span><br><small>Belum bisa dicetak</small>';
        if ($isOutsideEditWindow) {
            $html .= '<br><small class="text-muted">Batas edit sudah lewat</small>';
        }
        return $html;
    }

    private function pegawaiEditState(array $row): array
    {
        $appTimezone = new DateTimeZone('Asia/Jakarta');
        $today = new DateTimeImmutable('today', $appTimezone);
        $editableLimitDate = $today->modify('-3 days');
        $rowTanggal = substr((string) ($row['tanggal'] ?? ''), 0, 10);
        $rowDate = DateTimeImmutable::createFromFormat('!Y-m-d', $rowTanggal, $appTimezone);
        $rowDate = $rowDate && $rowDate->format('Y-m-d') === $rowTanggal ? $rowDate : null;
        $isOutsideEditWindow = !$rowDate || $rowDate < $editableLimitDate || $rowDate > $today;
        $isApproved = (($row['status'] ?? 'pending') === 'approved');
        $isApprovalRevoked = !empty($row['approval_revoked_at']);
        $disableAction = $isApproved || (!$isApprovalRevoked && $isOutsideEditWindow);
        $disableTitle = $isApproved
            ? 'Laporan sudah disahkan'
            : ((!$isApprovalRevoked && $isOutsideEditWindow) ? 'Batas edit maksimal 3 hari sebelumnya' : '');

        return [$isOutsideEditWindow, $disableAction, $disableTitle];
    }

    private function renderActions(array $row): string
    {
        $id = (int) $row['kegiatan_id'];
        [, $disableAction, $disableTitle] = $this->pegawaiEditState($row);

        if ($disableAction) {
            return '<span title="' . $this->h($disableTitle) . '"><button type="button" class="btn btn-secondary btn-sm" disabled aria-label="' . $this->h($disableTitle) . '"><i class="fas fa-lock"></i></button></span>';
        }

        return '<div class="btn-group">'
            . '<button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editModal-' . $id . '"><i class="fas fa-edit"></i></button>'
            . '<button class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#deleteModal-' . $id . '"><i class="fas fa-trash"></i></button>'
            . '</div>'
            . $this->renderEditModal($row)
            . $this->renderDeleteModal($row);
    }

    private function renderTrashActions(array $row): string
    {
        $id = (int) $row['kegiatan_id'];

        return '<div class="btn-group">'
            . '<form method="POST" action="/pegawai/laporan/restore" class="d-inline">'
            . Csrf::input()
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<button type="submit" class="btn btn-success btn-sm" title="Pulihkan laporan"><i class="fas fa-trash-restore"></i></button>'
            . '</form>'
            . '<button class="btn btn-danger btn-sm ml-1" data-toggle="modal" data-target="#forceDeletePegawaiLaporanModal-' . $id . '" title="Hapus permanen"><i class="fas fa-times"></i></button>'
            . '</div>'
            . $this->renderForceDeleteModal($row);
    }

    private function renderEditModal(array $row): string
    {
        $id = (int) $row['kegiatan_id'];

        return '
          <div class="modal fade" id="editModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header bg-madrasah">
                  <h5 class="modal-title text-white">Edit Laporan</h5>
                  <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="/pegawai/laporan/update" enctype="multipart/form-data">
                  ' . Csrf::input() . '
                  <div class="modal-body text-left">
                    <input type="hidden" name="id" value="' . $id . '">
                    <div class="form-group">
                      <label>Tanggal</label>
                      <input type="date" class="form-control" name="tanggal" value="' . $this->h($row['tanggal'] ?? '') . '" readonly>
                    </div>
                    <div class="form-group">
                      <label>Kegiatan<span class="text-danger"> *</span></label>
                      <input class="form-control" name="kegiatan" value="' . $this->h($row['kegiatan'] ?? '') . '" required>
                    </div>
                    <div class="form-group">
                      <label>Output<span class="text-danger"> *</span></label>
                      <textarea class="form-control" name="output" rows="3" required>' . $this->h($row['output'] ?? '') . '</textarea>
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
          </div>';
    }

    private function renderDeleteModal(array $row): string
    {
        $id = (int) $row['kegiatan_id'];

        return '
          <div class="modal fade" id="deleteModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header bg-danger">
                  <h5 class="modal-title text-white">Hapus Laporan</h5>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="/pegawai/laporan/delete">
                  ' . Csrf::input() . '
                  <div class="modal-body text-left">
                    <input type="hidden" name="id" value="' . $id . '">
                    <p>Yakin ingin menghapus laporan kegiatan <strong>' . $this->h($row['kegiatan'] ?? '') . '</strong> pada <strong>' . DateHelper::hariTanggalIndo((string) $row['tanggal']) . '</strong>?</p>
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

    private function renderForceDeleteModal(array $row): string
    {
        $id = (int) $row['kegiatan_id'];

        return '
          <div class="modal fade" id="forceDeletePegawaiLaporanModal-' . $id . '" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header bg-danger">
                  <h5 class="modal-title text-white">Hapus Permanen</h5>
                  <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="/pegawai/laporan/bulk-process">
                  ' . Csrf::input() . '
                  <div class="modal-body text-left">
                    <input type="hidden" name="action" value="force_delete">
                    <input type="hidden" name="kegiatan_ids[]" value="' . $id . '">
                    <p>Laporan kegiatan <strong>' . $this->h($row['kegiatan'] ?? '') . '</strong> akan dihapus permanen beserta file buktinya.</p>
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



    private function rememberCreateInput(array $r): void
    {
        Session::flash('old_pegawai_laporan_create', [
            'tanggal' => (string)($r['tanggal'] ?? ''),
            'kegiatan' => array_values(array_map('strval', (array)($r['kegiatan'] ?? []))),
            'output' => array_values(array_map('strval', (array)($r['output'] ?? []))),
        ]);
    }

    public function create(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            $this->laporanService->createKegiatan(
                $pegawai['id'],
                $r['tanggal'],
                $r['kegiatan'] ?? [],
                $r['output'] ?? [],
                $r['bukti'] ?? []
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil dikirim.'
            ]);
        } catch (Exception $e) {
            $this->rememberCreateInput($r);

            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /pegawai/dashboard");
        exit;
    }

    public function update(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->updateKegiatanByPegawai(
                $pegawai['id'],
                (int)$r['id'],
                $r['kegiatan'],
                $r['output'],
                $r['bukti'] ?? []
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil diperbarui.'
            ]);
        } catch (Exception $e) {

            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /pegawai/laporan");
        exit;
    }

    public function delete(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->deleteKegiatanByPegawai(
                $pegawai['id'],
                (int)$r['id']
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil dihapus dan masih dapat dipulihkan selama 14 hari.'
            ]);
        } catch (Exception $e) {

            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /pegawai/laporan");
        exit;
    }

    public function restore(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->restoreKegiatanByPegawai(
                (int) $pegawai['id'],
                (int) $r['id']
            );

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

        header("Location: /pegawai/laporan/sampah");
        exit;
    }

    public function bulkProcess(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();
            $action = trim((string) ($r['action'] ?? ''));
            $kegiatanIds = array_values(array_unique(array_filter(array_map('intval', (array) ($r['kegiatan_ids'] ?? [])))));

            if (!$kegiatanIds) {
                throw new Exception("Pilih minimal satu kegiatan.");
            }

            $processed = 0;
            foreach ($kegiatanIds as $kegiatanId) {
                match ($action) {
                    'delete' => $this->laporanService->deleteKegiatanByPegawai((int) $pegawai['id'], $kegiatanId),
                    'restore' => $this->laporanService->restoreKegiatanByPegawai((int) $pegawai['id'], $kegiatanId),
                    'force_delete' => $this->laporanService->forceDeleteKegiatanByPegawai((int) $pegawai['id'], $kegiatanId),
                    default => throw new Exception("Aksi bulk tidak valid."),
                };
                $processed++;
            }

            Session::flash('flash', [
                'type' => 'success',
                'message' => "{$processed} kegiatan berhasil diproses."
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        $redirect = in_array(($r['action'] ?? ''), ['restore', 'force_delete'], true)
            ? '/pegawai/laporan/sampah'
            : '/pegawai/laporan';

        header("Location: {$redirect}");
        exit;
    }
}
