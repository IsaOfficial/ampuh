<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Kegiatan Pegawai</title>
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

        .signature-name {
            font-weight: bold;
        }

        @media print {
            body {
                margin: 10mm;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header">
        <h2>SISTEM LAPORAN HARIAN PEGAWAI</h2>
        <h4>Rekapitulasi Laporan Kegiatan</h4>
    </div>

    <div class="info">
        <p><strong>Nama Pegawai:</strong> <?= htmlspecialchars($user['nama']) ?></p>
        <p>
            <strong><?= !empty($user['nip']) ? 'NIP' : 'NIK' ?>:</strong> <?= htmlspecialchars($user['nip'] ?: $user['nik']) ?>
        </p>
        <p><strong>Periode:</strong>
            <?= ($start && $end) ? "$start s/d $end" : "Semua Data" ?>
        </p>
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
        <div class="signature" style="margin-top: 50px; text-align: right;">
            ____________________________<br>
            <span class="signature-name"><?= htmlspecialchars($user['nama']) ?></span><br>
            <span class="signature-id">
                <?= !empty($user['nip']) ? 'NIP. ' . htmlspecialchars($user['nip']) : 'NIK. ' . htmlspecialchars($user['nik'] ?? '-') ?>
            </span>
        </div>
    </div>

</body>

</html>