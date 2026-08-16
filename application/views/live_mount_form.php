<?php
// Konfigurasi koneksi database
$host = '172.19.65.146';
$user = 'cpr';
$password = 'P@ssw0rdcpr';
$dbname = 'db_live_mount_monitoring';

// Membuat koneksi
$conn = new mysqli($host, $user, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Query untuk ambil data dengan flag = 'gray'
// Tangani update os_problem dan issue
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['os_problem'])) {
    foreach ($_POST['os_problem'] as $id => $os_val) {
        $os_val_safe = ($os_val === '1') ? 1 : 0;
        $issue_val = isset($_POST['issue'][$id]) ? $conn->real_escape_string($_POST['issue'][$id]) : '';
        $update = "UPDATE live_mount_log SET os_problem = $os_val_safe, issue = '$issue_val' WHERE id = $id";
        $conn->query($update);
    }
    echo "<p style='text-align:center;color:green;'>✅ Data berhasil diupdate!</p>";
}

// Filter tanggal (default: semua)
$selected_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$where_clause = "flag != 'green'";
if (!empty($selected_date)) {
    $safe_date = $conn->real_escape_string($selected_date);
    $where_clause .= " AND date_live_mount = '$safe_date'";
}

// Ambil data
$sql = "SELECT id, virtual_machine, date_live_mount, flag, os_problem, issue, image_file, image_data FROM live_mount_log WHERE $where_clause ORDER BY date_live_mount DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Live Mount dengan Flag Gray</title>
    <style>
        table {
            width: 95%;
            border-collapse: collapse;
            margin: 10px auto;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #ddd;
        }
        form, .filter-form {
            text-align: center;
        }
        input[type="text"] {
            width: 100%;
            box-sizing: border-box;
        }
        .filter-box {
            margin: 20px auto;
            text-align: center;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">Daftar Live Mount dengan Flag Non Green</h2>

<!-- Filter Tanggal -->
<div class="filter-box">
    <form method="get" class="filter-form">
        <label for="filter_date">Filter berdasarkan tanggal:</label>
        <input type="date" name="filter_date" id="filter_date" value="<?= htmlspecialchars($selected_date) ?>">
        <input type="submit" value="Tampilkan">
        <a href="live_mount_form.php" style="margin-left: 10px;">🔄 Reset</a>
    </form>
</div>

<form method="post">
    <table>
        <thead>
            <tr>
                <th>Virtual Machine</th>
                <th>Date Live Mount</th>
                <th>Flag</th>
                <th>OS Problem</th>
		<th>Issue</th>
                <th>Image</th>

            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
			<td>
                        <a href="https://172.19.156.144/images/<?= urlencode($row['image_file']) ?>" target="_blank">
			<?= htmlspecialchars($row['virtual_machine']) ?> </a>
                        </td>
                        <td><?= htmlspecialchars($row['date_live_mount']) ?></td>
                        <td><?= htmlspecialchars($row['flag']) ?></td>
                        <td>
                            <select name="os_problem[<?= $row['id'] ?>]">
                                <option value="0" <?= $row['os_problem'] == 0 ? 'selected' : '' ?>>✅ Sukses Booting</option>
                                <option value="1" <?= $row['os_problem'] == 1 ? 'selected' : '' ?>>❌ Failed Booting</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="issue[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['issue']) ?>">
			</td>
                        <td>
                        <a href="https://172.19.156.144/images/<?= urlencode($row['image_file']) ?>" target="_blank">
			Link Gambar
                        </a>
			- <a>

                      
                       <a href="image.php?id=<?= $row['id'] ?>" target="_blank">Lihat Screenshot</a>
                       <?php
$imgData = base64_encode($row['image_data']);
#echo '<img src="data:image/png;base64,'.$imgData.'" alt="Screenshot">';
?>
                        </a>
                        </td>

                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Tidak ada data dengan flag 'gray'<?= $selected_date ? " pada tanggal $selected_date" : "" ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br>
    <input type="submit" value="Update Data">
</form>

</body>
</html>

<?php
$conn->close();
?>

