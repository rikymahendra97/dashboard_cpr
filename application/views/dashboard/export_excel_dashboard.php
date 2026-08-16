<?php
// Memaksa browser untuk mendownload file sebagai format Excel (.xls)
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");

// CSS Inline untuk Styling Excel
$style_header = 'background-color: #3b536b; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #ffffff;';
$style_title  = 'font-size: 16px; font-weight: bold; text-align: left; background-color: #f8f9fa; color: #333; padding: 10px; border: 1px solid #3b536b;';
$style_cell   = 'vertical-align: middle; border: 1px solid #dddddd; text-align: center;';
$style_cell_l = 'vertical-align: middle; border: 1px solid #dddddd; text-align: left;';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Export Dashboard DC</title>
</head>

<body style="font-family: Arial, sans-serif; font-size: 11px;">

    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr>
                <th colspan="7" style="border: none; text-align: right; font-size: 12px; padding-bottom: 5px;">
                    Periode : <?= $periode; ?>
                </th>
            </tr>
            <tr>
                <th colspan="7" style="<?= $style_title; ?>">RESTART SERVER</th>
            </tr>
            <tr>
                <th rowspan="2" style="<?= $style_header; ?> width: 5%;">No</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 20%;">Nama Server</th>
                <th colspan="2" style="<?= $style_header; ?> width: 25%;">Start Restart</th>
                <th colspan="2" style="<?= $style_header; ?> width: 25%;">Finish Restart</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 10%;">No Tiket</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 15%;">Root Cause</th>
            </tr>
            <tr>
                <th style="<?= $style_header; ?>">Tanggal</th>
                <th style="<?= $style_header; ?>">Waktu</th>
                <th style="<?= $style_header; ?>">Tanggal</th>
                <th style="<?= $style_header; ?>">Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            if (!empty($data_restart)): foreach ($data_restart as $r): ?>
                    <tr>
                        <td style="<?= $style_cell; ?>"><?= $no++; ?></td>
                        <td style="<?= $style_cell_l; ?>"><?= $r['nama_server']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= !empty($r['start_downtime']) ? date('d M Y', strtotime($r['start_downtime'])) : '-'; ?></td>
                        <td style="<?= $style_cell; ?>"><?= !empty($r['start_downtime']) ? date('H:i', strtotime($r['start_downtime'])) : '-'; ?></td>
                        <td style="<?= $style_cell; ?>"><?= !empty($r['finish_downtime']) ? date('d M Y', strtotime($r['finish_downtime'])) : '-'; ?></td>
                        <td style="<?= $style_cell; ?>"><?= !empty($r['finish_downtime']) ? date('H:i', strtotime($r['finish_downtime'])) : '-'; ?></td>
                        <td style="<?= $style_cell; ?>"><?= $r['no_tiket']; ?></td>
                        <td style="<?= $style_cell_l; ?>"><?= $r['root_cause']; ?></td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="8" style="<?= $style_cell; ?>">Tidak ada data pada periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br><br>

    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr>
                <th colspan="6" style="<?= $style_title; ?>">URR SERVER TBN</th>
            </tr>
            <tr>
                <th rowspan="2" style="<?= $style_header; ?> width: 5%;">No</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 20%;">Nama Server</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 12%;">Tanggal</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 15%;">Tipe Request</th>
                <th colspan="2" style="<?= $style_header; ?> background-color: #5d5d5d; border-color: #e67e22; border-width: 2px;">Spesifikasi Hardware</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 15%;">Request by IRIS / Personal</th>
            </tr>
            <tr>
                <th style="<?= $style_header; ?> background-color: #6a6361; border-color: #e67e22; border-bottom: none;">Penambahan / Pengurangan Spesifikasi</th>
                <th style="<?= $style_header; ?> background-color: #6a6361; border-color: #e67e22; border-bottom: none;">Spesifikasi Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            if (!empty($data_urr)): foreach ($data_urr as $u): ?>
                    <tr>
                        <td style="<?= $style_cell; ?>"><?= $no++; ?></td>
                        <td style="<?= $style_cell_l; ?>"><?= $u['snapshot_vm_name']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= date('d M Y', strtotime($u['tanggal_eksekusi'])); ?></td>
                        <td style="<?= $style_cell; ?>"><?= $u['jenis_perubahan']; ?></td>

                        <?php
                        $delta_cpu = $u['target_cpu_count'] - $u['current_cpu_count'];
                        $delta_ram = ($u['target_memory_mb'] - $u['current_memory_mb']) / 1024;
                        $tanda_cpu = ($delta_cpu > 0) ? '+' : '';
                        $tanda_ram = ($delta_ram > 0) ? '+' : '';
                        ?>
                        <td style="<?= $style_cell; ?>">RAM <?= $tanda_ram . $delta_ram; ?> GB / CPU <?= $tanda_cpu . $delta_cpu; ?> Core</td>

                        <td style="<?= $style_cell; ?>">RAM <?= ($u['target_memory_mb'] / 1024); ?> GB / CPU <?= $u['target_cpu_count']; ?> Core</td>
                        <td style="<?= $style_cell; ?>"><?= $u['no_tiket_eksternal']; ?></td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="7" style="<?= $style_cell; ?>">Tidak ada data pada periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br><br>

    <?php /*
    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th colspan="10" style="<?= $style_title; ?>">PENAMBAHAN SERVER DI DC TBN</th>
            </tr>
            <tr>
                <th rowspan="2" style="<?= $style_header; ?> width: 5%;">No</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 10%;">Tanggal</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 8%;">Bulan</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 10%;">Kritikalitas</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 12%;">Tipe Request</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 10%;">Environment</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 15%;">Nama Server</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 10%;">Data Store</th>
                <th colspan="3" style="<?= $style_header; ?> background-color: #6d6e5a; border-color: #f1c40f; border-width: 2px;">Spesifikasi Hardware</th>
                <th rowspan="2" style="<?= $style_header; ?> width: 10%;">No Tiket</th>
            </tr>
            <tr>
                <th style="<?= $style_header; ?> background-color: #6d6e5a; border-color: #f1c40f;">CPU<br>(CORE)</th>
                <th style="<?= $style_header; ?> background-color: #6d6e5a; border-color: #f1c40f;">RAM<br>(GB)</th>
                <th style="<?= $style_header; ?> background-color: #6d6e5a; border-color: #f1c40f;">HDD<br>(GB)</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            if (!empty($data_prov)): foreach ($data_prov as $p): ?>
                    <tr>
                        <td style="<?= $style_cell; ?>"><?= $no++; ?></td>
                        <td style="<?= $style_cell; ?>"><?= date('d M Y', strtotime($p['tanggal_request'])); ?></td>
                        <td style="<?= $style_cell; ?>"><?= date('F', strtotime($p['tanggal_request'])); ?></td>
                        <td style="<?= $style_cell; ?>"><?= $p['kritikalitas']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= $p['tipe_request']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= $p['environment']; ?></td>
                        <td style="<?= $style_cell_l; ?>"><?= $p['nama_server']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= $p['datastore']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= $p['cpu']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= $p['ram']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= $p['hdd']; ?></td>
                        <td style="<?= $style_cell; ?>"><?= $p['no_tiket']; ?></td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="12" style="<?= $style_cell; ?>">Tidak ada data pada periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    */ ?>

</body>

</html>
