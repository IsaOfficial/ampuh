<?php

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #eaeaea;
        }

        .filter-info {
            margin-bottom: 10px;
            font-size: 12px;
        }
    </style>
</head>

<body onload="window.print()">

    <h2>Laporan Kegiatan - Admin</h2>

    <div class="filter-info">
        <strong>Filter:</strong><br>
        Kata Kunci: <?= htmlspecialchars($filter['keyword']) ?> <br>
        Tanggal: <?= $filter['start'] ?> s/d <?= $filter['end'] ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Pegawai</th>
                <th>Kegiatan</th>
                <th>Output</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporan)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($laporan as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $row['tanggal'] ?></td>
                        <td><?= htmlspecialchars($row['nama_pegawai']) ?></td>
                        <td><?= htmlspecialchars($row['kegiatan']) ?></td>
                        <td><?= htmlspecialchars($row['output']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>