<?php
session_start();
require 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="head">
        <h1>PENGUMUMAN HARI INI</h1>
        <a href="input_surat.php">Input</a>
        <img src="/img/logo.png">
    </div>
    
    <div class="pengumuman-list">
        <?php if ($result->num_rows > 0): ?>
    <div class="container">
        <h2>Daftar Surat Terupload</h2>
        <table class="surat-table">
            <thead>
                <tr>
                    <th>Tanggal Upload</th>
                    <th>Tanggal Surat</th>
                    <th>No. Surat</th>
                    <th>Jenis Surat</th>
                    <th>Perihal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Tanggal_Diinput']); ?></td>
                    <td><?php echo htmlspecialchars($row['Tanggal_Surat']); ?></td>
                    <td><?php echo htmlspecialchars($row['No_Surat']); ?></td>
                    <td><?php echo htmlspecialchars($row['Jenis_Surat']); ?></td>
                    <td><?php echo htmlspecialchars($row['Perihal']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="container">
        <p>Belum ada surat yang diupload.</p>
    </div>
    <?php endif; ?>
</body>
</html>
