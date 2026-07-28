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
    $signedReport = $laporan[0] ?? [];
    $verificationUrl = '';
    $qrDataUri = '';

    if (($signedReport['status'] ?? '') === 'approved' && !empty($signedReport['verification_token'])) {
        $verificationUrl = AppConfig::url('/verifikasi-laporan?token=' . urlencode($signedReport['verification_token']));
        $qrDataUri = QrCodeHelper::dataUri($verificationUrl, 220);
    }
    ?>

    <div class="header">
        <h2>LAPORAN KEGIATAN PEGAWAI</h2>
        <h4>Rekapitulasi Laporan Kegiatan</h4>
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
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th>Kegiatan</th>
                <th>Output</th>
                <th width="20%">Bukti</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporan)): ?>
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data laporan pada rentang tanggal ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($laporan as $item): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($item['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($item['kegiatan']) ?></td>
                        <td><?= htmlspecialchars($item['output']) ?></td>
                        <td class="text-center">
                            <?php if (!empty($item['bukti'])): ?>
                                <?php
                                $ext = strtolower(pathinfo($item['bukti'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png'], true)):
                                ?>
                                    <img src="/public/uploads/bukti/<?= $item['bukti'] ?>"
                                        alt="Bukti" style="max-width: 140px; max-height: 120px; display:block; margin: 0 auto 5px;">
                                <?php endif; ?>
                                <?= htmlspecialchars($item['bukti']) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Jepara, <?= date('d M Y') ?>
        <div class="signature approval-block" style="margin-top: 10px; text-align: right;">
            <?php if (!empty($qrDataUri)): ?>
                <img src="<?= htmlspecialchars($qrDataUri) ?>" alt="QR Code Verifikasi Dokumen" class="approval-qr-code">
                <div class="approval-note">Laporan ini telah disahkan secara elektronik.</div>
                <div class="approval-note">Kode: <?= htmlspecialchars(substr($signedReport['verification_token'], 0, 12)) ?></div>
            <?php else: ?>
                <div class="approval-note">Laporan belum disahkan.</div>
            <?php endif; ?>
            <div class="approval-note">Disahkan pada:
                <span><?= !empty($signedReport['approved_at']) ? date('d/m/Y H:i', strtotime($signedReport['approved_at'])) : '-' ?></span>
            </div>
            <?php if (!empty($signedReport['signature_note'])): ?>
                <div class="approval-note"><?= htmlspecialchars($signedReport['signature_note']) ?></div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>