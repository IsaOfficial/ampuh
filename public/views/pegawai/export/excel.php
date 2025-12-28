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

    td img {
        display: block;
        margin: 0 auto 3px;
        /* margin bawah untuk nama file */
        max-width: 80px;
        max-height: 80px;
    }

    th {
        background: #f0f0f0;
        text-align: center;
    }

    .text-start {
        text-align: left;
    }

    .text-center {
        text-align: center;
    }

    .header-title {
        font-size: 16px;
        text-align: center;
        font-weight: bold;
    }

    .info {
        margin-top: 20px;
        font-size: 13px;
    }
</style>

<table>
    <!-- Header Title -->
    <tr>
        <th colspan="5" class="header-title">SISTEM LAPORAN HARIAN PEGAWAI</th>
    </tr>
    <tr>
        <th colspan="5" class="header-title">Rekapitulasi Laporan Kegiatan</th>
    </tr>

    <!-- Pegawai Info -->
    <tr>
        <td colspan="5" class="info">
            <strong>Nama Pegawai:</strong> <?= htmlspecialchars($user['nama']) ?>
            <br>
            <strong><?= !empty($user['nip']) ? 'NIP' : 'NIK' ?>:</strong> <?= htmlspecialchars($user['nip'] ?: $user['nik']) ?>
            <br>
            <strong>Periode:</strong>
            <?= ($start || $end) ? "$start s/d $end" : "Semua Data" ?>
        </td>
    </tr>

    <!-- Table Header -->
    <tr>
        <th width="5%">No</th>
        <th width="20%">Tanggal</th>
        <th>Kegiatan</th>
        <th>Output</th>
        <th width="20%">Bukti</th>
    </tr>

    <!-- Table Body -->
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
                <td class="text-start"><?= htmlspecialchars($item['kegiatan']) ?></td>
                <td class="text-start"><?= htmlspecialchars($item['output']) ?></td>
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