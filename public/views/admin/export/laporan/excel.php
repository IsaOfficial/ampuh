<table border="1">
    <tr>
        <th colspan="5">
            <strong>Laporan Kegiatan - Admin</strong>
        </th>
    </tr>

    <tr>
        <td colspan="5">
            <strong>Filter:</strong><br>
            Kata Kunci: <?= htmlspecialchars($filter['keyword']) ?><br>
            Tanggal: <?= $filter['start'] ?> s/d <?= $filter['end'] ?>
        </td>
    </tr>

    <tr>
        <th>No</th>
        <th>Tanggal</th>
        <th>Pegawai</th>
        <th>Kegiatan</th>
        <th>Output</th>
    </tr>

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
</table>