<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $tanggal_diinput = date('Y-m-d');
    $tanggal_surat = $data['Tanggal_Surat'];
    $no_surat = $data['No_Surat'];
    $jenis_surat = $data['Jenis_Surat'];
    $perihal = $data['Perihal'];
    $ringkasan = $data['Ringkasan'];
    $pengirim = $data['Pengirim'];
    $penerima = $data['Penerima'];

    // Simpan data ke database
    $sql = "INSERT INTO Surat (Tanggal_Diinput, Tanggal_Surat, No_Surat, Jenis_Surat, Perihal, Ringkasan, Pengirim, Penerima)
            VALUES ('$tanggal_diinput', '$tanggal_surat', '$no_surat', '$jenis_surat', '$perihal', '$ringkasan', '$pengirim', '$penerima')";

    if ($conn->query($sql) === TRUE) {
        echo "Data berhasil disimpan.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
