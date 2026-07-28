<?php
$filter = $filter ?? [];
$filterParts = [];
if (!empty($filter['keyword'])) {
    $filterParts[] = 'Kata kunci: ' . $filter['keyword'];
}
if (!empty($filter['jabatan'])) {
    $filterParts[] = 'Jabatan: ' . $filter['jabatan'];
}
if (!empty($filter['jenis_kelamin'])) {
    $filterParts[] = 'Jenis kelamin: ' . $filter['jenis_kelamin'];
}
$filterText = $filterParts ? implode(' | ', $filterParts) : 'Semua pegawai';
?>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background:#d5ffd5;">
            <th colspan="6">DATA PEGAWAI</th>
        </tr>
        <tr>
            <td colspan="6">Filter: <?= htmlspecialchars($filterText) ?></td>
        </tr>
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
                <td colspan="6" style="text-align:center;">Tidak ada data</td>
            </tr>
        <?php else: ?>
            <?php foreach ($pegawai as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nama']) ?></td>
                    <td><?= htmlspecialchars(!empty($p['nip']) ? $p['nip'] : $p['nik']); ?></td>
                    <td><?= htmlspecialchars($p['jabatan']) ?></td>
                    <td><?= htmlspecialchars($p['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['no_wa'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['jenis_kelamin'] ?? 'Tidak diketahui') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>