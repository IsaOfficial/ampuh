<style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 6px;
        font-size: 11px;
        vertical-align: middle;
    }

    th {
        background: #f0f0f0;
        text-align: center;
    }

    .text-center {
        text-align: center;
    }

    .text-start {
        text-align: left;
    }

    .header-title {
        font-size: 16px;
        text-align: center;
        font-weight: bold;
    }

    .info {
        font-size: 13px;
        line-height: 1.6;
    }

    td img {
        display: block;
        margin: 0 auto 4px;
        max-width: 80px;
        max-height: 80px;
    }
</style>

<table>
    <!-- Header -->
    <tr>
        <th colspan="7" class="header-title">LAPORAN HARIAN PEGAWAI</th>
    </tr>
    <tr>
        <th colspan="7" class="header-title">Rekapitulasi Laporan Kegiatan</th>
    </tr>

    <!-- Info Pegawai -->
    <tr>
        <td colspan="7" class="info">
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
        </td>
    </tr>

    <!-- Table Header -->
    <tr>
        <th width="5%">No</th>
        <th width="15%">Tanggal</th>
        <th width="10%">Foto</th>
        <th width="15%">Nama Pegawai</th>
        <th>Kegiatan</th>
        <th>Output</th>
        <th width="15%">Bukti</th>
    </tr>

    <!-- Table Body -->
    <?php if (empty($laporan)): ?>
        <tr>
            <td colspan="7" class="text-center">
                Tidak ada data laporan pada rentang tanggal ini.
            </td>
        </tr>
    <?php else: ?>
        <?php $no = 1; ?>
        <?php foreach ($laporan as $item): ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center">
                    <?= date('d/m/Y', strtotime($item['tanggal'])) ?>
                </td>
                <td style="text-align:center; vertical-align:middle;">
                    <?php if (!empty($item['foto_pegawai'])): ?>
                        <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $item['foto_pegawai'])): ?>
                            <img src="<?= 'http://localhost/ampuh.mtsn1jepara.sch.id/public/uploads/foto/' . $item['foto_pegawai'] ?>"
                                width="50" height="50" alt="<?= htmlspecialchars($item['foto_pegawai']) ?>">
                            <br>
                        <?php endif; ?>
                        <?= htmlspecialchars($item['foto_pegawai']) ?>
                    <?php else: ?>
                        <img src="<?= 'http://localhost/ampuh.mtsn1jepara.sch.id/public/assets/img/avatars/default_profile.svg' ?>"
                            width="50" height="50" alt="<?= htmlspecialchars($item['foto_pegawai']) ?>">
                        <br>
                    <?php endif; ?>
                </td>
                <td class="text-start">
                    <?= htmlspecialchars($item['nama_pegawai']) ?>
                </td>
                <td class="text-start">
                    <?= htmlspecialchars($item['kegiatan']) ?>
                </td>
                <td class="text-start">
                    <?= htmlspecialchars($item['output']) ?>
                </td>
                <td style="text-align:center; vertical-align:middle; height:90px;">
                    <?php if (!empty($item['bukti'])): ?>
                        <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $item['bukti'])): ?>
                            <img src="<?= 'http://localhost/ampuh.mtsn1jepara.sch.id/public/uploads/bukti/' . $item['bukti'] ?>"
                                width="100" alt="<?= htmlspecialchars($item['bukti']) ?>">
                            <br>
                        <?php endif; ?>
                        <?= htmlspecialchars($item['bukti']) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>