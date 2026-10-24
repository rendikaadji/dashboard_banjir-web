<?php

// Fakta kawasan dan proyek. Sumber tunggal: proposal EPICS in IEEE 2026
// "Hybrid IoT and AI-Based Flood Early Warning System Using Multi-Sensor Fusion in Urban Residential Areas"
// (Problem Statement, Project Impact, Support Letter). Ubah di sini, bukan di tampilan.
return [
    'area_ha' => 13,
    'residents' => 1300,
    'rt_total' => 11,
    'rt_affected' => 6, // RT 06 sampai RT 11
    'pumps' => 11,
    'pump_share' => 'sepertiga', // kapasitas pompa terhadap debit air yang masuk
    'catchment' => ['Blok Jalan Amal', 'Pasar Cipadu', 'Kompleks Deplu'],
    'whatsapp_active_pct' => 95, // warga aktif di grup WhatsApp komunitas
    'latency_target_s' => '0,561',
    'org_name' => 'Pengurus RW 08 Taman Cipulir Estate',
    'university' => 'Fakultas Teknologi Informasi, Universitas Budi Luhur',
    'grant' => 'EPICS in IEEE 2026, didukung Industry Applications Society (IAS)',
    // Kontak pengurus RW 08 (formulir proposal, bagian Collaborators); kosong tampil [ISI DATA]
    'contact_email' => env('PUBLIC_CONTACT_EMAIL'),
    'contact_phone' => env('PUBLIC_CONTACT_PHONE'),
];
