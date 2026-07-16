<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Service Account JSON
    |--------------------------------------------------------------------------
    |
    | Path relatif dari root project ke file kredensial Google Service Account.
    | Contoh: 'credentials/google-service-account.json'
    |
    */
    'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),

    /*
    |--------------------------------------------------------------------------
    | Google Spreadsheet ID
    |--------------------------------------------------------------------------
    |
    | ID spreadsheet Google yang digunakan sebagai database arsip surat.
    | Bisa ditemukan di URL spreadsheet: https://docs.google.com/spreadsheets/d/{ID}/edit
    |
    */
    'sheet_id' => env('GOOGLE_SHEET_ID'),

    /*
    |--------------------------------------------------------------------------
    | Google Drive Folder ID
    |--------------------------------------------------------------------------
    |
    | ID folder Google Drive tujuan upload file PDF arsip.
    | Jika kosong, file akan diupload ke root Drive service account.
    | Set GOOGLE_DRIVE_FOLDER_ID di .env untuk mengisi nilai ini.
    |
    */
    'drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),

];
