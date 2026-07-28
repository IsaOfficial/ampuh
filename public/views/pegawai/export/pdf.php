<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Export Laporan' ?></title>
    <style>
        body {
            font-family: "Arial", Helvetica, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #000;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .header h4 {
            margin: 2px 0 0 0;
            font-size: 14px;
            font-weight: normal;
        }

        .info {
            margin: 15px 0;
            font-size: 13px;
        }

        .info p {
            margin: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        table td {
            text-align: left;
            vertical-align: top;
        }

        table th {
            background-color: #eaeaea;
            font-weight: bold;
            text-align: center;
        }

        table tr:nth-child(even) td {
            background-color: #f9f9f9;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 40px;
            font-size: 12px;
            text-align: right;
        }

        .signature {
            margin-top: 60px;
            text-align: right;
        }

        .approval-note {
            font-size: 10px;
        }

        .verification-note {
            margin-top: 10px;
            font-size: 10px;
            text-align: left;
        }

        .evidence-cell,
        .status-cell {
            text-align: center;
            vertical-align: top;
        }

        .evidence-link {
            display: inline-block;
            color: #000;
            text-decoration: none;
        }

        .evidence-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border: 1px solid #000;
            margin: 0 auto;
            font-size: 12px;
            font-weight: bold;
            box-sizing: border-box;
        }

        .evidence-icon-document {
            border-radius: 2px;
        }

        .evidence-pdf-icon,
        .evidence-video-icon {
            display: block;
            width: 52px;
            height: 52px;
            object-fit: contain;
            margin: 0 auto;
        }

        .evidence-link-label {
            display: block;
            font-size: 10px;
            font-weight: bold;
        }

        .evidence-filename {
            font-size: 9px;
            line-height: 1.2;
            word-break: break-all;
        }

        .status-qr-code {
            display: block;
            width: 88px;
            height: 88px;
            object-fit: contain;
            margin: 0 auto;
        }

        .status-code {
            font-size: 9px;
            margin-bottom: 8px;
            word-break: break-all;
        }

        .status-label {
            display: block;
            font-size: 10px;
            font-weight: bold;
        }

        .signature-name {
            font-weight: bold;
        }

        @media print {
            .approval-qr-code {
                display: block !important;
                visibility: visible !important;
                width: 100px !important;
                height: 100px !important;
                object-fit: contain;
                margin: 6px 0 6px auto;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .status-qr-code {
                display: block !important;
                visibility: visible !important;
                width: 78px !important;
                height: 78px !important;
                object-fit: contain;
                margin: 0 auto;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .approval-block {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            body {
                margin: 10mm;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <?php
    if (!function_exists('pegawaiPdfStatusLabel')) {
        function pegawaiPdfStatusLabel(?string $status, $revokedAt = null): string
        {
            if (!empty($revokedAt)) {
                return 'Pengesahan Dicabut';
            }

            return match ($status ?? 'pending') {
                'approved' => 'Terverifikasi',
                'rejected' => 'Ditolak',
                default => 'Menunggu',
            };
        }
    }

    if (!function_exists('pdfEvidenceKind')) {
        function pdfEvidenceKind(string $filename): string
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

    if (!function_exists('pdfEvidenceUrl')) {
        function pdfEvidenceUrl(string $filename): string
        {
            return '/public/uploads/bukti/' . rawurlencode($filename);
        }
    }

    if (!function_exists('pdfIconUrl')) {
        function pdfIconUrl(): string
        {
            return '/public/assets/img/pdf_icon.png';
        }
    }

    if (!function_exists('mp4IconUrl')) {
        function mp4IconUrl(): string
        {
            return '/public/assets/img/mp4_icon.png';
        }
    }

    $verificationCheckUrl = class_exists('AppConfig') ? AppConfig::url('/verifikasi-laporan') : '/verifikasi-laporan';
    $signedItems = array_values(array_filter($laporan ?? [], static function ($item) {
        return ($item['status'] ?? '') === 'approved'
            && empty($item['approval_revoked_at'])
            && !empty($item['verification_token']);
    }));
    ?>

    <div class="header">
        <h2>REKAPITULASI LAPORAN KEGIATAN PEGAWAI</h2>
    </div>

    <div class="info">
        <strong>Nama&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp:</strong> <?= htmlspecialchars($pegawai['nama']) ?><br>

        <?php if (!empty($pegawai['nip'])): ?>
            <strong>NIP&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp:</strong> <?= htmlspecialchars($pegawai['nip']) ?><br>
        <?php elseif (!empty($pegawai['nik'])): ?>
            <strong>NIK&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp:</strong> <?= htmlspecialchars($pegawai['nik']) ?><br>
        <?php endif; ?>

        <strong>Periode&nbsp&nbsp&nbsp:</strong>
        <?php if ($start && $end): ?>
            <?= htmlspecialchars($start) ?> s/d <?= htmlspecialchars($end) ?>
        <?php elseif ($start): ?>
            Dari <?= htmlspecialchars($start) ?> sampai saat ini
        <?php elseif ($end): ?>
            Sampai <?= htmlspecialchars($end) ?>
        <?php else: ?>
            Semua Periode
        <?php endif; ?>
    </div>

    <table>
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
                        <td><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($item['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($item['kegiatan']) ?></td>
                        <td><?= htmlspecialchars($item['output']) ?></td>
                        <td class="evidence-cell">
                            <?php if (!empty($item['bukti'])): ?>
                                <?php
                                $evidenceFile = (string) $item['bukti'];
                                $evidenceExt = strtolower(pathinfo($evidenceFile, PATHINFO_EXTENSION));
                                $evidenceKind = pdfEvidenceKind($evidenceFile);
                                $evidenceUrl = pdfEvidenceUrl($evidenceFile);
                                $evidenceExtLabel = strtoupper(substr($evidenceExt ?: 'FILE', 0, 4));
                                ?>
                                <?php if ($evidenceKind === 'image'): ?>
                                    <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" rel="noopener">
                                        <img src="<?= htmlspecialchars($evidenceUrl) ?>"
                                            alt="Bukti" style="max-width: 100px; max-height: 80px; display:block; margin: 0 auto 5px;">
                                    </a>
                                <?php elseif ($evidenceKind === 'document'): ?>
                                    <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" rel="noopener" class="evidence-link" title="Buka atau unduh dokumen bukti">
                                        <?php if ($evidenceExt === 'pdf'): ?>
                                            <img src="<?= htmlspecialchars(pdfIconUrl()) ?>" alt="PDF" class="evidence-pdf-icon">
                                        <?php else: ?>
                                            <span class="evidence-icon evidence-icon-document"><?= htmlspecialchars($evidenceExtLabel) ?></span>
                                        <?php endif; ?>
                                        <span class="evidence-link-label">Dokumen</span>
                                    </a>
                                <?php elseif ($evidenceKind === 'video'): ?>
                                    <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" rel="noopener" class="evidence-link" title="Buka preview video bukti">
                                        <img src="<?= htmlspecialchars(mp4IconUrl()) ?>" alt="Video" class="evidence-video-icon">
                                        <span class="evidence-link-label">Video</span>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" rel="noopener" class="evidence-link" title="Buka atau unduh file bukti">
                                        <span class="evidence-icon evidence-icon-document"><?= htmlspecialchars($evidenceExtLabel) ?></span>
                                        <span class="evidence-link-label">File</span>
                                    </a>
                                <?php endif; ?>
                                <div class="evidence-filename"><?= htmlspecialchars($evidenceFile) ?></div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <?php
                        $rowStatus = $item['status'] ?? 'pending';
                        $isVerified = $rowStatus === 'approved' && empty($item['approval_revoked_at']) && !empty($item['verification_token']);
                        $rowQrDataUri = '';

                        if ($isVerified && class_exists('QrCodeHelper')) {
                            $rowVerificationUrl = (class_exists('AppConfig') ? AppConfig::url('/verifikasi-laporan?token=' . urlencode($item['verification_token'])) : '/verifikasi-laporan?token=' . urlencode($item['verification_token']));
                            $rowQrDataUri = QrCodeHelper::dataUri($rowVerificationUrl, 160);
                        }
                        ?>
                        <td class="status-cell">
                            <?php if ($isVerified): ?>
                                <?php if (!empty($rowQrDataUri)): ?>
                                    <img src="<?= htmlspecialchars($rowQrDataUri) ?>" alt="QR Code Verifikasi" class="status-qr-code">
                                <?php endif; ?>
                                <div class="status-code">Kode: <?= htmlspecialchars(substr($item['verification_token'], 0, 12)) ?></div>
                            <?php endif; ?>
                            <span class="status-label"><?= htmlspecialchars(pegawaiPdfStatusLabel($rowStatus, $item['approval_revoked_at'] ?? null)) ?></span>
                        </td>

                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="verification-note">
        <i><span style="color: red;">* </span>Cek QR Code atau kode verifikasi pada kolom Status melalui <a href="<?= htmlspecialchars($verificationCheckUrl) ?>" target="_blank"><?= htmlspecialchars($verificationCheckUrl) ?></a>.
            Setiap QR Code dan kode berlaku untuk satu kegiatan pada baris yang sama.</i>
    </div>

    <div class="footer">
        Jepara, <?= date('d M Y') ?>
        <div class="signature approval-block" style="margin-top: 10px; text-align: right;">
            <div>Pegawai,</div>

            <br><br><br>
            ____________________________<br>
            <span class="signature-name"><?= htmlspecialchars($pegawai['nama']) ?></span><br>
        </div>
    </div>

</body>

</html>