<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background:#d5ffd5;">
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
            <?php foreach ($pegawai as $p): ?>
                <tr>
                    <td><?= $p['nama'] ?></td>
                    <td>'
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