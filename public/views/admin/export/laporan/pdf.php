<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
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

        .profile-img-mini {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #2e8b57;
            /* Tema madrasah */
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
        <h2>LAPORAN HARIAN PEGAWAI</h2>
        <h4>Rekapitulasi Laporan Kegiatan</h4>
    </div>

    <div class="info">
        <strong>Nama Pegawai:</strong> <?= htmlspecialchars($pegawai['nama'] ?? 'Semua Pegawai') ?><br>

        <?php if (!empty($pegawai['nip'])): ?>
            <strong>NIP:</strong> <?= htmlspecialchars($pegawai['nip']) ?><br>
        <?php elseif (!empty($pegawai['nik'])): ?>
            <strong>NIK:</strong> <?= htmlspecialchars($pegawai['nik']) ?><br>
        <?php endif; ?>

        <strong>Periode:</strong>
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
                <th width="10%">Foto</th>
                <th width="15%">Nama Pegawai</th>
                <th>Kegiatan</th>
                <th>Output</th>
                <th width="15%">Bukti</th>
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
                        <td class="text-center"><?= date('d/m/Y', strtotime($item['tanggal'])) ?></td>
                        <td class="text-center">
                            <img
                                src="<?= $item['foto_pegawai'] ? '/public/uploads/foto/' . $item['foto_pegawai'] : '/public/assets/img/avatars/default_profile.svg' ?>"
                                alt="Foto Profil Pegawai"
                                class="profile-img-mini" />
                        </td>
                        <td><?= htmlspecialchars($item['nama_pegawai']) ?></td>
                        <td><?= htmlspecialchars($item['kegiatan']) ?></td>
                        <td><?= htmlspecialchars($item['output']) ?></td>
                        <td class=" text-center">
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
            <span class="signature-name"><?= htmlspecialchars($admin['nama']) ?></span><br>
        </div>
    </div>

</body>

</html>