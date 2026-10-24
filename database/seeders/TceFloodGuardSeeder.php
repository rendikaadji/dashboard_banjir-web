<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\Reading;
use App\Models\Recipient;
use App\Models\RecipientGroup;
use App\Models\Site;
use App\Models\Threshold;
use App\Models\User;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TceFloodGuardSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inisialisasi Site
        $site = Site::firstOrCreate(
            ['code' => 'TCE'],
            [
                'name' => 'Taman Cipulir Estate',
                'address' => 'Jalan Taman Cipulir Raya, RW 08, Kel. Cipadu Jaya, Kec. Larangan, Kota Tangerang, Banten 15155',
                'timezone' => 'Asia/Jakarta',
            ]
        );

        // 2. Inisialisasi Zona (RT 06 s/d RT 11)
        $zonesData = [
            ['code' => 'RT-06', 'name' => 'RT 06 / RW 08', 'household_cnt' => 40, 'flood_prone' => true],
            ['code' => 'RT-07', 'name' => 'RT 07 / RW 08', 'household_cnt' => 38, 'flood_prone' => true],
            ['code' => 'RT-08', 'name' => 'RT 08 / RW 08', 'household_cnt' => 45, 'flood_prone' => true],
            ['code' => 'RT-09', 'name' => 'RT 09 / RW 08', 'household_cnt' => 42, 'flood_prone' => true],
            ['code' => 'RT-10', 'name' => 'RT 10 / RW 08', 'household_cnt' => 35, 'flood_prone' => true],
            ['code' => 'RT-11', 'name' => 'RT 11 / RW 08', 'household_cnt' => 40, 'flood_prone' => true],
        ];

        $zones = [];
        foreach ($zonesData as $data) {
            $zones[$data['code']] = Zone::firstOrCreate(
                ['site_id' => $site->id, 'code' => $data['code']],
                $data
            );
        }

        // 3. Inisialisasi Akun Pengguna Awal
        $users = [
            [
                'name' => 'Super Administrator',
                'username' => 'superadmin',
                'email' => 'admin@tcefloodguard.local',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_SUPER_ADMIN,
                'phone_e164' => '+6281200000000',
                'is_active' => true,
            ],
            [
                'name' => 'Technical Admin (EPICS)',
                'username' => 'admin_teknis',
                'email' => 'tech@tcefloodguard.local',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_TECH_ADMIN,
                'phone_e164' => '+6281200000001',
                'is_active' => true,
            ],
            [
                'name' => 'Pak Rudiyanto (Ketua RW 08)',
                'username' => 'pengurus_rw',
                'email' => 'rw08@tcefloodguard.local',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_RW_BOARD,
                'phone_e164' => '+6281200000002',
                'is_active' => true,
            ],
            [
                'name' => 'Pak Slamet (Operator Pos Satpam)',
                'username' => 'satpam',
                'email' => 'satpam@tcefloodguard.local',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_OPERATOR,
                'phone_e164' => '+6281200000003',
                'is_active' => true,
            ],
        ];

        $adminUser = null;
        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['username' => $userData['username']],
                $userData
            );
            if ($user->role === User::ROLE_SUPER_ADMIN) {
                $adminUser = $user;
            }
        }

        // 4. Inisialisasi Nodes Sesuai Site Plan GPS Taman Cipulir Estate
        $nodesData = [
            // --- WATER LEVEL NODES (BLUE PINS) ---
            [
                'node_id' => 'WL-09-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-09']->id,
                'node_type' => Node::TYPE_WATER_LEVEL,
                'label' => 'Gorong-gorong Depan Masjid (Titik Kritis)',
                'latitude' => -6.247250,
                'longitude' => 106.741200,
                'mount_height_cm' => 220.00,
                'ground_elev_cm' => 12.50,
                'calib_offset_cm' => -1.50,
                'min_valid_cm' => 5.00,
                'max_valid_cm' => 240.00,
                'fusion_weight' => 1.000,
                'is_critical' => true,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
            [
                'node_id' => 'WL-09-02',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-09']->id,
                'node_type' => Node::TYPE_WATER_LEVEL,
                'label' => 'Saluran Utama Depan Masjid (Timur)',
                'latitude' => -6.247180,
                'longitude' => 106.741850,
                'mount_height_cm' => 200.00,
                'ground_elev_cm' => 13.00,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 5.00,
                'max_valid_cm' => 220.00,
                'fusion_weight' => 0.850,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
            [
                'node_id' => 'WL-06-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-06']->id,
                'node_type' => Node::TYPE_WATER_LEVEL,
                'label' => 'Saluran Boulevard Utama Barat',
                'latitude' => -6.247350,
                'longitude' => 106.739800,
                'mount_height_cm' => 210.00,
                'ground_elev_cm' => 14.20,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 5.00,
                'max_valid_cm' => 230.00,
                'fusion_weight' => 0.900,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
            [
                'node_id' => 'WL-06-02',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-06']->id,
                'node_type' => Node::TYPE_WATER_LEVEL,
                'label' => 'Saluran Blok Barat Laut Dalam',
                'latitude' => -6.246600,
                'longitude' => 106.739500,
                'mount_height_cm' => 200.00,
                'ground_elev_cm' => 14.50,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 5.00,
                'max_valid_cm' => 220.00,
                'fusion_weight' => 0.850,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
            [
                'node_id' => 'WL-11-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-07']->id,
                'node_type' => Node::TYPE_WATER_LEVEL,
                'label' => 'Saluran Pemukiman Barat Laut Ujung',
                'latitude' => -6.246750,
                'longitude' => 106.738800,
                'mount_height_cm' => 250.00,
                'ground_elev_cm' => 11.80,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 5.00,
                'max_valid_cm' => 280.00,
                'fusion_weight' => 0.950,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],

            // --- WATER VELOCITY / FLOW SENSORS (GREEN PINS) ---
            [
                'node_id' => 'WF-01-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-10']->id,
                'node_type' => Node::TYPE_FLOW,
                'label' => 'Pintu Air Main Entrance TCE (Utara)',
                'latitude' => -6.246250,
                'longitude' => 106.742400,
                'mount_height_cm' => 160.00,
                'ground_elev_cm' => 13.50,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 0.00,
                'max_valid_cm' => 200.00,
                'fusion_weight' => 0.900,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
            [
                'node_id' => 'WF-02-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-08']->id,
                'node_type' => Node::TYPE_FLOW,
                'label' => 'Saluran Perimeter Timur Laut',
                'latitude' => -6.246750,
                'longitude' => 106.743400,
                'mount_height_cm' => 150.00,
                'ground_elev_cm' => 13.80,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 0.00,
                'max_valid_cm' => 180.00,
                'fusion_weight' => 0.850,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
            [
                'node_id' => 'WF-06-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-09']->id,
                'node_type' => Node::TYPE_FLOW,
                'label' => 'Pintu Air & Debit Belakang Masjid',
                'latitude' => -6.247800,
                'longitude' => 106.741350,
                'mount_height_cm' => 150.00,
                'ground_elev_cm' => 14.10,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 0.00,
                'max_valid_cm' => 180.00,
                'fusion_weight' => 0.800,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
            [
                'node_id' => 'WF-11-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-11']->id,
                'node_type' => Node::TYPE_FLOW,
                'label' => 'Saluran Retensi Aliran Parit Selatan',
                'latitude' => -6.249500,
                'longitude' => 106.741800,
                'mount_height_cm' => 170.00,
                'ground_elev_cm' => 11.50,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 0.00,
                'max_valid_cm' => 200.00,
                'fusion_weight' => 0.900,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],

            // --- RAINFALL STATIONS (RED PINS) ---
            [
                'node_id' => 'RN-07-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-07']->id,
                'node_type' => Node::TYPE_RAINFALL,
                'label' => 'Stasiun Cuaca & Hujan Sektor Barat',
                'latitude' => -6.247500,
                'longitude' => 106.738900,
                'mount_height_cm' => 300.00,
                'ground_elev_cm' => 15.00,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 0.00,
                'max_valid_cm' => 500.00,
                'fusion_weight' => 0.750,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
            [
                'node_id' => 'RN-08-01',
                'site_id' => $site->id,
                'zone_id' => $zones['RT-08']->id,
                'node_type' => Node::TYPE_RAINFALL,
                'label' => 'Stasiun Cuaca & Hujan Sektor Timur',
                'latitude' => -6.248600,
                'longitude' => 106.743200,
                'mount_height_cm' => 300.00,
                'ground_elev_cm' => 14.80,
                'calib_offset_cm' => 0.00,
                'min_valid_cm' => 0.00,
                'max_valid_cm' => 500.00,
                'fusion_weight' => 0.750,
                'is_critical' => false,
                'status' => Node::STATUS_ACTIVE,
                'firmware_version' => 'v1.4.2',
                'installed_at' => Carbon::now()->subMonths(2),
                'last_seen_at' => Carbon::now(),
            ],
        ];

        foreach ($nodesData as $node) {
            Node::firstOrCreate(['node_id' => $node['node_id']], $node);
        }

        // 5. Inisialisasi Ambang Batas Default (PRD §23.2)
        $thresholds = [
            // Level 1 - WASPADA
            [
                'scope' => 'GLOBAL',
                'level' => Threshold::LEVEL_WASPADA,
                'metric' => 'water_level_cm',
                'operator' => 'GTE',
                'value_low' => 70.00,
                'sustain_seconds' => 60,
                'hysteresis_cm' => 5.00,
                'cooldown_minutes' => 30,
            ],
            [
                'scope' => 'GLOBAL',
                'level' => Threshold::LEVEL_WASPADA,
                'metric' => 'rain_rate_mmh',
                'operator' => 'GTE',
                'value_low' => 10.00,
                'sustain_seconds' => 300,
                'hysteresis_cm' => 0.00,
                'cooldown_minutes' => 30,
            ],
            // Level 2 - SIAGA
            [
                'scope' => 'GLOBAL',
                'level' => Threshold::LEVEL_SIAGA,
                'metric' => 'water_level_cm',
                'operator' => 'GTE',
                'value_low' => 100.00,
                'sustain_seconds' => 30,
                'hysteresis_cm' => 10.00,
                'cooldown_minutes' => 15,
            ],
            [
                'scope' => 'GLOBAL',
                'level' => Threshold::LEVEL_SIAGA,
                'metric' => 'rise_rate_cmh',
                'operator' => 'GTE',
                'value_low' => 20.00,
                'sustain_seconds' => 60,
                'hysteresis_cm' => 5.00,
                'cooldown_minutes' => 15,
            ],
            // Level 3 - KRITIS
            [
                'scope' => 'GLOBAL',
                'level' => Threshold::LEVEL_KRITIS,
                'metric' => 'water_level_cm',
                'operator' => 'GTE',
                'value_low' => 150.00,
                'sustain_seconds' => 30,
                'hysteresis_cm' => 10.00,
                'cooldown_minutes' => 15,
            ],
            [
                'scope' => 'GLOBAL',
                'level' => Threshold::LEVEL_KRITIS,
                'metric' => 'rise_rate_cmh',
                'operator' => 'GTE',
                'value_low' => 40.00,
                'sustain_seconds' => 60,
                'hysteresis_cm' => 10.00,
                'cooldown_minutes' => 15,
            ],
            [
                'scope' => 'GLOBAL',
                'level' => Threshold::LEVEL_KRITIS,
                'metric' => 'flow_velocity_ms',
                'operator' => 'GTE',
                'value_low' => 1.50,
                'sustain_seconds' => 60,
                'hysteresis_cm' => 0.30,
                'cooldown_minutes' => 15,
            ],
        ];

        foreach ($thresholds as $t) {
            Threshold::firstOrCreate(
                [
                    'scope' => $t['scope'],
                    'level' => $t['level'],
                    'metric' => $t['metric'],
                ],
                array_merge($t, [
                    'is_active' => true,
                    'created_by' => $adminUser?->id,
                ])
            );
        }

        // 6. Inisialisasi Penerima Sintetis Anonim (Sesuai P4 AI-Usage-Policy)
        $recipients = [
            [
                'full_name' => 'Pak Rudiyanto (Ketua RW 08)',
                'phone_e164' => '+6281211110001',
                'zone_id' => $zones['RT-09']->id,
                'role_tag' => Recipient::ROLE_PENGURUS_RW,
                'min_level' => 2,
                'consent_at' => Carbon::now()->subMonths(3),
            ],
            [
                'full_name' => 'Pak Slamet (Petugas Satpam)',
                'phone_e164' => '+6281211110002',
                'zone_id' => $zones['RT-07']->id,
                'role_tag' => Recipient::ROLE_SATPAM,
                'min_level' => 1,
                'consent_at' => Carbon::now()->subMonths(3),
            ],
            [
                'full_name' => 'Ketua RT 09',
                'phone_e164' => '+6281211110009',
                'zone_id' => $zones['RT-09']->id,
                'role_tag' => Recipient::ROLE_KETUA_RT,
                'min_level' => 2,
                'consent_at' => Carbon::now()->subMonths(2),
            ],
            [
                'full_name' => 'Ketua RT 06',
                'phone_e164' => '+6281211110006',
                'zone_id' => $zones['RT-06']->id,
                'role_tag' => Recipient::ROLE_KETUA_RT,
                'min_level' => 2,
                'consent_at' => Carbon::now()->subMonths(2),
            ],
            [
                'full_name' => 'Koordinator Satgas Banjir',
                'phone_e164' => '+6281211110007',
                'zone_id' => $zones['RT-09']->id,
                'role_tag' => Recipient::ROLE_SATGAS,
                'min_level' => 2,
                'consent_at' => Carbon::now()->subMonths(2),
            ],
            [
                'full_name' => 'Warga Sampel RT 09',
                'phone_e164' => '+6281211110099',
                'zone_id' => $zones['RT-09']->id,
                'role_tag' => Recipient::ROLE_WARGA,
                'min_level' => 3,
                'consent_at' => Carbon::now()->subMonths(1),
            ],
        ];

        foreach ($recipients as $r) {
            Recipient::firstOrCreate(
                ['phone_e164' => $r['phone_e164']],
                array_merge($r, ['is_active' => true, 'channel' => 'WHATSAPP'])
            );
        }

        // Grup WhatsApp
        RecipientGroup::firstOrCreate(
            ['name' => 'Grup Warga RT 09 Tanggap Banjir'],
            ['wa_group_id' => '120363000000000009@g.us', 'zone_id' => $zones['RT-09']->id, 'min_level' => 3, 'is_active' => true]
        );
        RecipientGroup::firstOrCreate(
            ['name' => 'Grup Pengurus RW 08 & Satgas'],
            ['wa_group_id' => '120363000000000008@g.us', 'zone_id' => null, 'min_level' => 2, 'is_active' => true]
        );

        // 7. Data Riwayat Pembacaan Sensor Terkini (agar dashboard langsung berpenampilan hidup)
        $now = Carbon::now();
        $sampleReadings = [
            // WL-09-01 (Depan Masjid): TMA 62.4 cm (Kondisi normal/aman saat ini)
            [
                'node_id' => 'WL-09-01',
                'ts' => $now->copy()->subSeconds(5),
                'seq' => 101,
                'distance_cm' => 156.1,
                'water_level_cm' => 62.4,
                'water_level_ewma' => 62.1,
                'rise_rate_cmh' => 1.2,
                'battery_v' => 12.65,
                'solar_v' => 18.2,
                'rssi' => -65,
                'quality' => 'OK',
            ],
            // WL-09-02: TMA 48.0 cm
            [
                'node_id' => 'WL-09-02',
                'ts' => $now->copy()->subSeconds(10),
                'seq' => 98,
                'distance_cm' => 152.0,
                'water_level_cm' => 48.0,
                'water_level_ewma' => 48.2,
                'rise_rate_cmh' => 0.8,
                'battery_v' => 12.45,
                'solar_v' => 17.9,
                'rssi' => -68,
                'quality' => 'OK',
            ],
            // WL-06-01: TMA 35.5 cm
            [
                'node_id' => 'WL-06-01',
                'ts' => $now->copy()->subSeconds(12),
                'seq' => 87,
                'distance_cm' => 174.5,
                'water_level_cm' => 35.5,
                'water_level_ewma' => 35.6,
                'rise_rate_cmh' => 0.5,
                'battery_v' => 12.50,
                'solar_v' => 18.0,
                'rssi' => -71,
                'quality' => 'OK',
            ],
            // WF-06-01: Flow
            [
                'node_id' => 'WF-06-01',
                'ts' => $now->copy()->subSeconds(15),
                'seq' => 66,
                'flow_lpm' => 42.5,
                'flow_velocity_ms' => 0.35,
                'battery_v' => 12.55,
                'solar_v' => 18.1,
                'rssi' => -69,
                'quality' => 'OK',
            ],
            // RN-07-01: Rain
            [
                'node_id' => 'RN-07-01',
                'ts' => $now->copy()->subSeconds(20),
                'seq' => 45,
                'rainfall_mm' => 0.0,
                'rain_rate_mmh' => 0.0,
                'wind_speed_ms' => 1.8,
                'battery_v' => 12.70,
                'solar_v' => 18.4,
                'rssi' => -63,
                'quality' => 'OK',
            ],
            // WL-11-01: Kolam Retensi
            [
                'node_id' => 'WL-11-01',
                'ts' => $now->copy()->subSeconds(8),
                'seq' => 92,
                'distance_cm' => 195.0,
                'water_level_cm' => 55.0,
                'water_level_ewma' => 54.8,
                'rise_rate_cmh' => 0.9,
                'battery_v' => 12.60,
                'solar_v' => 18.2,
                'rssi' => -67,
                'quality' => 'OK',
            ],
            // WL-06-02: Barat Laut Dalam
            [
                'node_id' => 'WL-06-02',
                'ts' => $now->copy()->subSeconds(7),
                'seq' => 90,
                'distance_cm' => 160.0,
                'water_level_cm' => 40.0,
                'water_level_ewma' => 39.8,
                'rise_rate_cmh' => 0.4,
                'battery_v' => 12.50,
                'solar_v' => 18.0,
                'rssi' => -70,
                'quality' => 'OK',
            ],
            // WF-01-01: Main Entrance Flow
            [
                'node_id' => 'WF-01-01',
                'ts' => $now->copy()->subSeconds(6),
                'seq' => 85,
                'flow_lpm' => 38.0,
                'flow_velocity_ms' => 0.28,
                'battery_v' => 12.65,
                'solar_v' => 18.3,
                'rssi' => -64,
                'quality' => 'OK',
            ],
            // WF-02-01: Perimeter Timur Laut Flow
            [
                'node_id' => 'WF-02-01',
                'ts' => $now->copy()->subSeconds(9),
                'seq' => 82,
                'flow_lpm' => 30.5,
                'flow_velocity_ms' => 0.22,
                'battery_v' => 12.48,
                'solar_v' => 17.9,
                'rssi' => -72,
                'quality' => 'OK',
            ],
            // WF-11-01: Retensi Selatan Flow
            [
                'node_id' => 'WF-11-01',
                'ts' => $now->copy()->subSeconds(11),
                'seq' => 79,
                'flow_lpm' => 45.0,
                'flow_velocity_ms' => 0.38,
                'battery_v' => 12.58,
                'solar_v' => 18.1,
                'rssi' => -68,
                'quality' => 'OK',
            ],
            // RN-08-01: Rain Timur
            [
                'node_id' => 'RN-08-01',
                'ts' => $now->copy()->subSeconds(14),
                'seq' => 50,
                'rainfall_mm' => 0.0,
                'rain_rate_mmh' => 0.0,
                'wind_speed_ms' => 1.5,
                'battery_v' => 12.68,
                'solar_v' => 18.3,
                'rssi' => -65,
                'quality' => 'OK',
            ],
        ];

        // Buat 12 titik riwayat 1 jam terakhir untuk grafik indah
        for ($i = 12; $i >= 0; $i--) {
            $pastTime = $now->copy()->subMinutes($i * 5);
            foreach ($sampleReadings as $item) {
                Reading::create([
                    'node_id' => $item['node_id'],
                    'ts' => $pastTime,
                    'seq' => $item['seq'] - $i,
                    'distance_cm' => isset($item['distance_cm']) ? $item['distance_cm'] + ($i * 0.2) : null,
                    'water_level_cm' => isset($item['water_level_cm']) ? max(10, $item['water_level_cm'] - ($i * 0.3)) : null,
                    'water_level_ewma' => isset($item['water_level_ewma']) ? max(10, $item['water_level_ewma'] - ($i * 0.3)) : null,
                    'rise_rate_cmh' => $item['rise_rate_cmh'] ?? null,
                    'flow_lpm' => $item['flow_lpm'] ?? null,
                    'flow_velocity_ms' => $item['flow_velocity_ms'] ?? null,
                    'rainfall_mm' => $item['rainfall_mm'] ?? null,
                    'rain_rate_mmh' => $item['rain_rate_mmh'] ?? null,
                    'wind_speed_ms' => $item['wind_speed_ms'] ?? null,
                    'battery_v' => $item['battery_v'],
                    'solar_v' => $item['solar_v'],
                    'rssi' => $item['rssi'],
                    'quality' => 'OK',
                    'is_buffered' => false,
                ]);
            }
        }
    }
}
