<?php

?>

<!DOCTYPE html>
<html lang="id">

<head>
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
        <h2>Daftar Pegawai</h2>
    </div>

    <div class="info">
        <p>Dicetak: <?= date('d/m/Y H:i') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIP/NIK</th>
                <th>Jabatan</th>
                <th>Email</th>
                <th>No WA</th>
                <th>Jenis Kelamin</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($pegawai)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($pegawai as $p): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $p['nama'] ?></td>
                        <td>
                            <?= htmlspecialchars(!empty($p['nip']) ? $p['nip'] : $p['nik']); ?>
                        </td>
                        <td><?= $p['jabatan'] ?></td>
                        <td><?= $p['email'] ?></td>
                        <td><?= $p['no_wa'] ?></td>
                        <td><?= $p['jenis_kelamin'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>