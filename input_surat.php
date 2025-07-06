<?php
session_start();
require 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengolahan Surat Otomatis</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.min.js"></script>
    <style>
        .dropzone {
            border: 2px dashed #3b82f6;
            border-radius: 0.5rem;
            transition: all 0.3s;
            padding: 20px;
            text-align: center;
        }
        .dropzone:hover {
            background-color: #f0f7ff;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistem Pengolahan Surat Otomatis</h1>
        
        <div class="dropzone" id="dropZone">
            <p>Seret file PDF ke sini atau klik untuk memilih file</p>
            <input type="file" id="fileInput" accept=".pdf" class="hidden">
        </div>
        
        <div id="resultSection" class="hidden">
            <h2>Data yang Terekstrak</h2>
            <div id="metadataTable"></div>
            <button id="saveToDbBtn">Simpan ke Database</button>
            <p id="dbStatus" class="hidden"></p>
        </div>
    </div>

    <script>
        let extractedData = {}; // Variabel untuk menyimpan data yang diekstrak

        document.getElementById('dropZone').addEventListener('click', () => {
            document.getElementById('fileInput').click();
        });

        document.getElementById('fileInput').addEventListener('change', handleFileSelect);

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file && file.type === 'application/pdf') {
                extractPdfData(file);
            } else {
                alert('Hanya file PDF yang didukung');
            }
        }

        async function extractPdfData(file) {
            const fileReader = new FileReader();

            fileReader.onload = async function() {
                const typedArray = new Uint8Array(this.result);
                const pdf = await pdfjsLib.getDocument(typedArray).promise;

                let text = '';
                const maxPages = Math.min(pdf.numPages, 3); // Batasi jumlah halaman yang diproses

                for (let i = 1; i <= maxPages; i++) {
                    const page = await pdf.getPage(i);
                    const content = await page.getTextContent();
                    const strings = content.items.map(item => item.str);
                    text += strings.join(' ') + '\n';
                }

                // Parse teks untuk mengekstrak metadata
                extractedData = parsePdfText(text); // Simpan data yang diekstrak
                displayMetadata(extractedData);
            };

            fileReader.onerror = function(error) {
                console.error('Error reading PDF file:', error);
                alert('Terjadi kesalahan saat membaca file PDF.');
            };

            fileReader.readAsArrayBuffer(file);
        }

        function parsePdfText(text) {
            // Implementasi sederhana parser teks PDF
            const data = {
                Tanggal_Surat: extractValue(text, /(?:Tanggal|Tgl)\s*[:.]?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i),
                No_Surat: extractValue(text, /(?:Nomor|No\.?)\s*[:.]?\s*([A-Za-z0-9\/\.\-]+)/i),
                Jenis_Surat: extractValue(text, /(?:Jenis|Tipe)\s*[:.]?\s*([A-Za-z\s]+)/i) || 'Surat Biasa',
                Perihal: extractValue(text, /(?:Perihal|/Hal)\s*[:.]?\s*([^\n\r]+)/i),
                Ringkasan: extractSummary(text),
                Pengirim: extractValue(text, /(?:Dari|Pengirim)\s*[:.]?\s*([^\n\r]+)/i),
                Penerima: extractValue(text, /(?:Kepada|Penerima|Yth.|Kepada Yth)\s*[:.]?\s*([^\n\r]+)/i),
            };

            return data;
        }

        function extractValue(text, regex) {
            const match = text.match(regex);
            return match ? match[1].trim() : '';
        }

        function extractSummary(text) {
            const sentences = text.split(/[.!?]+/).filter(s => s.trim().length > 0);
            return sentences.slice(0, 3).join('. ') + (sentences.length > 3 ? '...' : '');
        }

        function displayMetadata(data) {
            const metadataTable = document.getElementById('metadataTable');
            metadataTable.innerHTML = `
                <p>Tanggal Surat: ${data.Tanggal_Surat}</p>
                <p>No. Surat: ${data.No_Surat}</p>
                <p>Jenis Surat: ${data.Jenis_Surat}</p>
                <p>No Agenda: ${data.No_Agenda}</p>
                <p>Perihal: ${data.Perihal}</p>
                <p>Ringkasan: ${data.Ringkasan}</p>
                <p>Pengirim: ${data.Pengirim}</p>
                <p>Penerima: ${data.Penerima}</p>
            `;
            document.getElementById('resultSection').classList.remove('hidden');
        }

        document.getElementById('saveToDbBtn').addEventListener('click', async () => {
            const response = await fetch('saving.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(extractedData) // Kirim data yang diekstrak
            });

            const result = await response.text();
            document.getElementById('dbStatus').innerText = result;
            document.getElementById('dbStatus').classList.remove('hidden');
        });
    </script>
</body>
</html>
