<?php
if (!function_exists('adminPdfStatusLabel')) {
    function adminPdfStatusLabel(?string $status, $revokedAt = null): string
    {
        if (!empty($revokedAt)) {
            return 'Dicabut';
        }

        return match ($status ?? 'pending') {
            'approved' => 'Disahkan',
            'rejected' => 'Ditolak',
            default => 'Menunggu',
        };
    }
}

if (!function_exists('adminPdfPeriodText')) {
    function adminPdfPeriodText(?string $start, ?string $end): string
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

$signedReportsById = [];
foreach (($laporan ?? []) as $row) {
    if (
        ($row['status'] ?? '') === 'approved'
        && empty($row['approval_revoked_at'])
        && !empty($row['verification_token'])
        && !empty($row['laporan_id'])
    ) {
        $signedReportsById[(int) $row['laporan_id']] = $row;
    }
}

$signedReportValues = array_values($signedReportsById);
$singleSignedReport = count($signedReportValues) === 1 ? $signedReportValues[0] : [];
$verificationUrl = '';
$qrDataUri = '';

if ($singleSignedReport && class_exists('AppConfig') && class_exists('QrCodeHelper')) {
    $verificationUrl = AppConfig::url('/verifikasi-laporan?token=' . urlencode($singleSignedReport['verification_token']));
    $qrDataUri = QrCodeHelper::dataUri($verificationUrl, 220);
}
?>
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

        .text-small {
            font-size: 10px;
        }

        .status-label {
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            font-size: 12px;
            text-align: right;
        }

        .signature {
            margin-top: 10px;
            text-align: right;
        }

        .approval-note {
            font-size: 10px;
        }

        .approval-qr-code {
            display: block;
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin: 6px 0 6px auto;
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

    <div class="header">
        <h2>LAPORAN KEGIATAN PEGAWAI</h2>
        <h4>Rekapitulasi Laporan Kegiatan</h4>
    </div>

    <div class="info">
        <strong>Nama&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= htmlspecialchars($pegawai['nama'] ?? 'Semua Pegawai') ?><br>

        <?php if (!empty($pegawai['nip'])): ?>
            <strong>NIP&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= htmlspecialchars($pegawai['nip']) ?><br>
        <?php elseif (!empty($pegawai['nik'])): ?>
            <strong>NIK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= htmlspecialchars($pegawai['nik']) ?><br>
        <?php endif; ?>

        <strong>Periode&nbsp;&nbsp;&nbsp;:</strong> <?= htmlspecialchars(adminPdfPeriodText($start ?? null, $end ?? null)) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="16%">Nama Pegawai</th>
                <th>Kegiatan</th>
                <th width="16%">Output</th>
                <th width="14%">Bukti</th>
                <th width="13%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporan)): ?>
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data laporan pada rentang tanggal ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($laporan as $item): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= !empty($item['tanggal']) ? date('d/m/Y', strtotime($item['tanggal'])) : '-' ?></td>
                        <td>
                            <?= htmlspecialchars($item['nama_pegawai'] ?? '-') ?>
                            <?php if (!empty($item['nip'])): ?>
                                <div class="text-small">NIP: <?= htmlspecialchars($item['nip']) ?></div>
                            <?php elseif (!empty($item['nik'])): ?>
                                <div class="text-small">NIK: <?= htmlspecialchars($item['nik']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['kegiatan'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($item['output'] ?? '-') ?></td>
                        <td class="text-center">
                            <?php if (!empty($item['bukti'])): ?>
                                <?php
                                $ext = strtolower(pathinfo($item['bukti'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png'], true)):
                                ?>
                                    <img src="/public/uploads/bukti/<?= htmlspecialchars($item['bukti']) ?>"
                                        alt="Bukti" style="max-width: 140px; max-height: 120px; display:block; margin: 0 auto 5px;">
                                <?php endif; ?>
                                <?= htmlspecialchars($item['bukti']) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="status-label"><?= htmlspecialchars(adminPdfStatusLabel($item['status'] ?? 'pending', $item['approval_revoked_at'] ?? null)) ?></span>
                            <?php if (($item['status'] ?? 'pending') === 'rejected' && !empty($item['rejection_note'])): ?>
                                <div class="text-small"><?= htmlspecialchars($item['rejection_note']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['verification_token']) && empty($item['approval_revoked_at'])): ?>
                                <div class="text-small">Kode: <?= htmlspecialchars(substr($item['verification_token'], 0, 12)) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['approved_at']) && empty($item['approval_revoked_at'])): ?>
                                <div class="text-small"><?= date('d/m/Y H:i', strtotime($item['approved_at'])) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Jepara, <?= date('d M Y') ?>
        <div class="signature approval-block">
            <?php if (!empty($qrDataUri)): ?>
                <img src="<?= htmlspecialchars($qrDataUri) ?>" alt="QR Code Verifikasi Dokumen" class="approval-qr-code">
                <div class="approval-note">Laporan ini telah disahkan secara elektronik.</div>
                <div class="approval-note">Kode: <?= htmlspecialchars(substr($singleSignedReport['verification_token'], 0, 12)) ?></div>
                <div class="approval-note">Disahkan pada:
                    <span><?= !empty($singleSignedReport['approved_at']) ? date('d/m/Y H:i', strtotime($singleSignedReport['approved_at'])) : '-' ?></span>
                </div>
                <?php if (!empty($singleSignedReport['signature_note'])): ?>
                    <div class="approval-note"><?= htmlspecialchars($singleSignedReport['signature_note']) ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="approval-note">Dicetak oleh admin: <?= htmlspecialchars($admin['nama'] ?? '-') ?></div>
                <div class="approval-note">Status dan kode verifikasi tercantum pada tabel laporan.</div>
            <?php endif; ?>
            style="margin-top: 50px; text-align: right;">
            ____________________________<br>
            <span class="signature-name"><?= htmlspecialchars($admin['nama']) ?></span><br>
        </div>
    </div>

</body>

</html>