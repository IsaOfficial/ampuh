<?php
if (!function_exists('pegawaiExcelPeriodText')) {
    function pegawaiExcelPeriodText(?string $start, ?string $end): string
    {
        if ($start && $end) {
            return $start . ' s/d ' . $end;
        }

        if ($start) {
            return 'Dari ' . $start . ' sampai saat ini';
        }

        if ($end) {
            return 'Sampai ' . $end;
        }

        return 'Semua Periode';
    }
}

if (!function_exists('pegawaiExcelStatusText')) {
    function pegawaiExcelStatusText(array $item): string
    {
        if (!empty($item['approval_revoked_at'])) {
            return 'Pengesahan Dicabut';
        }

        $status = $item['status'] ?? 'pending';

        if ($status === 'approved') {
            $text = 'Terverifikasi';
            if (!empty($item['verification_token'])) {
                $text .= "\nKode: " . substr($item['verification_token'], 0, 12);
            }
            if (!empty($item['approved_at'])) {
                $text .= "\nDisahkan: " . date('d/m/Y H:i', strtotime($item['approved_at']));
            }
            return $text;
        }

        if ($status === 'rejected') {
            $text = 'Ditolak';
            if (!empty($item['rejection_note'])) {
                $text .= "\n" . $item['rejection_note'];
            }
            return $text;
        }

        return 'Menunggu';
    }
}

if (!function_exists('excelEvidenceKind')) {
    function excelEvidenceKind(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'heic', 'heif', 'tif', 'tiff', 'jfif', 'ico'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt', 'ods', 'odp'];
        $videoExtensions = ['mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'mpeg', 'mpg', '3gp', '3g2', 'wmv', 'ogv', 'flv', 'asf', 'mts', 'm2ts', 'hevc', 'h265'];

        if (in_array($ext, $imageExtensions, true)) {
            return 'image';
        }

        if (in_array($ext, $documentExtensions, true)) {
            return 'document';
        }

        if (in_array($ext, $videoExtensions, true)) {
            return 'video';
        }

        return 'file';
    }
}

if (!function_exists('excelEvidenceUrl')) {
    function excelEvidenceUrl(string $filename): string
    {
        return class_exists('AppConfig')
            ? AppConfig::url('public/uploads/bukti/' . rawurlencode($filename))
            : '/public/uploads/bukti/' . rawurlencode($filename);
    }
}

if (!function_exists('excelPdfIconUrl')) {
    function excelPdfIconUrl(): string
    {
        return class_exists('AppConfig')
            ? AppConfig::url('public/assets/img/pdf_icon.png')
            : '/public/assets/img/pdf_icon.png';
    }
}

if (!function_exists('excelMp4IconUrl')) {
    function excelMp4IconUrl(): string
    {
        return class_exists('AppConfig')
            ? AppConfig::url('public/assets/img/mp4_icon.png')
            : '/public/assets/img/mp4_icon.png';
    }
}

$verificationCheckUrl = class_exists('AppConfig') ? AppConfig::url('/verifikasi-laporan') : '/verifikasi-laporan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        .meta-table td, .meta-table th { border: none; padding: 3px 6px; }
        .title { text-align: center; font-size: 16px; font-weight: bold; border-bottom: 2px solid #000 !important; padding-bottom: 8px !important; }
        .info-label { width: 80px; font-weight: bold; }
        .report-table { margin-top: 12px; font-size: 11px; }
        .report-table th, .report-table td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        .report-table th { background: #eaeaea; text-align: center; font-weight: bold; }
        .report-table tr:nth-child(even) td { background: #f9f9f9; }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .evidence-cell, .status-cell { text-align: center; vertical-align: top; }
        .evidence-img { display: block; margin: 0 auto 4px; max-width: 90px; max-height: 70px; }
        .evidence-icon { display: block; margin: 0 auto 4px; width: 44px; height: 44px; object-fit: contain; }
        .file-badge { display: inline-block; min-width: 44px; padding: 12px 4px; border: 1px solid #000; font-weight: bold; text-align: center; }
        .file-name { display: block; font-size: 10px; word-break: break-all; }
        .status-text { white-space: pre-line; font-weight: bold; }
        .note { margin-top: 10px; font-size: 10px; font-style: italic; text-align: left; }
        .footer-table { margin-top: 28px; }
        .signature { text-align: right; border: none !important; }
    </style>
</head>

<body>
    <table class="meta-table">
        <tr>
            <th colspan="6" class="title">REKAPITULASI LAPORAN KEGIATAN PEGAWAI</th>
        </tr>
        <tr><td colspan="6">&nbsp;</td></tr>
        <tr>
            <td class="info-label">Nama</td>
            <td colspan="5">: <?= htmlspecialchars($pegawai['nama'] ?? '-') ?></td>
        </tr>
        <?php if (!empty($pegawai['nip'])): ?>
            <tr>
                <td class="info-label">NIP</td>
                <td colspan="5">: <?= htmlspecialchars($pegawai['nip']) ?></td>
            </tr>
        <?php elseif (!empty($pegawai['nik'])): ?>
            <tr>
                <td class="info-label">NIK</td>
                <td colspan="5">: <?= htmlspecialchars($pegawai['nik']) ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="info-label">Periode</td>
            <td colspan="5">: <?= htmlspecialchars(pegawaiExcelPeriodText($start ?? null, $end ?? null)) ?></td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="12%">Tanggal</th>
                <th width="28%">Kegiatan</th>
                <th width="20%">Output</th>
                <th width="18%">Bukti</th>
                <th width="18%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporan)): ?>
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data laporan pada rentang tanggal ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($laporan as $item): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= !empty($item['tanggal']) ? date('d/m/Y', strtotime($item['tanggal'])) : '-' ?></td>
                        <td class="text-start"><?= htmlspecialchars($item['kegiatan'] ?? '-') ?></td>
                        <td class="text-start"><?= htmlspecialchars($item['output'] ?? '-') ?></td>
                        <td class="evidence-cell">
                            <?php if (!empty($item['bukti'])): ?>
                                <?php
                                $evidenceFile = (string) $item['bukti'];
                                $evidenceKind = excelEvidenceKind($evidenceFile);
                                $evidenceUrl = excelEvidenceUrl($evidenceFile);
                                $evidenceExt = strtoupper(substr(pathinfo($evidenceFile, PATHINFO_EXTENSION) ?: 'FILE', 0, 4));
                                ?>
                                <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank">
                                    <?php if ($evidenceKind === 'image'): ?>
                                        <img src="<?= htmlspecialchars($evidenceUrl) ?>" alt="Bukti" class="evidence-img">
                                    <?php elseif ($evidenceKind === 'document' && strtolower(pathinfo($evidenceFile, PATHINFO_EXTENSION)) === 'pdf'): ?>
                                        <img src="<?= htmlspecialchars(excelPdfIconUrl()) ?>" alt="PDF" class="evidence-icon">
                                    <?php elseif ($evidenceKind === 'video'): ?>
                                        <img src="<?= htmlspecialchars(excelMp4IconUrl()) ?>" alt="Video" class="evidence-icon">
                                    <?php else: ?>
                                        <span class="file-badge"><?= htmlspecialchars($evidenceExt) ?></span><br>
                                    <?php endif; ?>
                                    <span class="file-name"><?= htmlspecialchars($evidenceFile) ?></span>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="status-cell"><span class="status-text"><?= nl2br(htmlspecialchars(pegawaiExcelStatusText($item))) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="note">
        * Cek kode verifikasi pada kolom Status melalui <?= htmlspecialchars($verificationCheckUrl) ?>. Setiap kode berlaku untuk satu kegiatan pada baris yang sama.
    </div>

    <table class="footer-table">
        <tr>
            <td class="signature" colspan="6">
                Jepara, <?= date('d M Y') ?><br><br>
                Pegawai,<br><br><br><br>
                ____________________________<br>
                <strong><?= htmlspecialchars($pegawai['nama'] ?? '-') ?></strong>
            </td>
        </tr>
    </table>
</body>

</html>
