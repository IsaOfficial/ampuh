<?php

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #eaeaea;
        }

        h3 {
            margin-bottom: 0;
        }
    </style>
</head>

<body onload="window.print()">

    <h3>Daftar Pegawai</h3>
    <p>Dicetak: <?= date('d/m/Y H:i') ?></p>

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
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($data as $p): ?>
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