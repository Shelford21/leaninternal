<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GsdElementSeeder extends Seeder
{
    public function run(): void
    {
        $categories = DB::table('gsd_categories')
            ->pluck('id', 'category_name');

        $elements = [
            // =========================================================
            // OBTAIN AND MATCH PART OR PARTS
            // =========================================================

            [
                'category' => 'Obtain and Match Part or Parts',
                'element_name' => 'Match & Get 2 Parts Together',
                'description' => 'Mengambil dan menggabung 2 komponen secara bersamaan',
                'code' => 'MG2T',
                'tmu' => 76,
                'seconds' => 2.70,
                'motion_sequence' => 'GGPGG',
            ],
            [
                'category' => 'Obtain and Match Part or Parts',
                'element_name' => 'Match & Get 2 Parts Separately',
                'description' => 'Mengambil dan menggabung 2 komponen secara terpisah',
                'code' => 'MG2S',
                'tmu' => 107,
                'seconds' => 3.90,
                'motion_sequence' => 'GPGPGG',
            ],
            [
                'category' => 'Obtain and Match Part or Parts',
                'element_name' => 'Match Parts to Foot (without obtain)',
                'description' => 'Menggabungkan komponen dibawah sepatu (tanpa melakukan pengambilan terlebih dahulu)',
                'code' => 'FOOT',
                'tmu' => 38,
                'seconds' => 1.40,
                'motion_sequence' => 'PF',
            ],
            [
                'category' => 'Obtain and Match Part or Parts',
                'element_name' => 'Match & Add Part with 1 Hand (Easy)',
                'description' => 'Menambahkan dan menggabungkan komponen dengan 1 tangan (Mudah)',
                'code' => 'MAPE',
                'tmu' => 50,
                'seconds' => 1.80,
                'motion_sequence' => 'GPG',
            ],
            [
                'category' => 'Obtain and Match Part or Parts',
                'element_name' => 'Match & Add Part with 1 Hand',
                'description' => 'Menambahkan dan menggabungkan komponen dengan 1 tangan',
                'code' => 'MAP1',
                'tmu' => 56,
                'seconds' => 2.00,
                'motion_sequence' => 'GPG',
            ],
            [
                'category' => 'Obtain and Match Part or Parts',
                'element_name' => 'Match & Add Part with 2 Hands',
                'description' => 'Menambahkan dan menggabungkan komponen dengan 2 tangan',
                'code' => 'MAP2',
                'tmu' => 69,
                'seconds' => 2.50,
                'motion_sequence' => 'GPGPG',
            ],

            // =========================================================
            // ALIGNING AND ADJUSTING
            // =========================================================

            [
                'category' => 'Aligning and Adjusting',
                'element_name' => 'Align and Match 2 Parts',
                'description' => 'Menggabung dan mensejajarkan 2 komponen',
                'code' => 'AM2P',
                'tmu' => 61,
                'seconds' => 2.20,
                'motion_sequence' => 'GGPG',
            ],
            [
                'category' => 'Aligning and Adjusting',
                'element_name' => 'Adjust 1 Part (Top)',
                'description' => 'Mengkoreksi 1 komponen (atas)',
                'code' => 'AJPT',
                'tmu' => 43,
                'seconds' => 1.50,
                'motion_sequence' => 'GPG',
            ],
            [
                'category' => 'Aligning and Adjusting',
                'element_name' => 'Align & Reposition Assembly Under Foot',
                'description' => 'Mensejajarkan dan mereposisi gabungan komponen dibawah sepatu',
                'code' => 'ARPN',
                'tmu' => 75,
                'seconds' => 2.70,
                'motion_sequence' => 'GPGPF',
            ],
            [
                'category' => 'Aligning and Adjusting',
                'element_name' => 'Align or Adjust Part(s) By Pushing or Sliding',
                'description' => 'Sejajarkan dan koreksi komponen dengan mendorong atau menggeser',
                'code' => 'APSH',
                'tmu' => 24,
                'seconds' => 0.90,
                'motion_sequence' => 'GP',
            ],

            // =========================================================
            // FORMING SHAPES
            // =========================================================

            [
                'category' => 'Forming Shapes',
                'element_name' => 'Form Fold',
                'description' => 'Melipat',
                'code' => 'FFLD',
                'tmu' => 43,
                'seconds' => 1.50,
                'motion_sequence' => 'GPG',
            ],
            [
                'category' => 'Forming Shapes',
                'element_name' => 'Form Crease in Folded Part',
                'description' => 'Membentuk tanda pada lipatan',
                'code' => 'FCRS',
                'tmu' => 28,
                'seconds' => 1.00,
                'motion_sequence' => 'GGWPPW',
            ],
            [
                'category' => 'Forming Shapes',
                'element_name' => 'Form Unfold or Layout',
                'description' => 'Membuka lipatan',
                'code' => 'FUNF',
                'tmu' => 23,
                'seconds' => 0.80,
                'motion_sequence' => 'GP',
            ],

            // =========================================================
            // TRIMING AND TOOL USE
            // =========================================================

            [
                'category' => 'Triming and Tool Use',
                'element_name' => 'Trim-Cut with Scissors (1st)',
                'description' => 'Memotong dengan gunting (pertama)',
                'code' => 'TCUT',
                'tmu' => 50,
                'seconds' => 1.80,
                'motion_sequence' => 'GPPP',
            ],
            [
                'category' => 'Triming and Tool Use',
                'element_name' => 'Trim-Cut with Scissors (additional)',
                'description' => 'Memotong dengan gunting (tambahan)',
                'code' => 'TCAT',
                'tmu' => 25,
                'seconds' => 0.90,
                'motion_sequence' => 'PP',
            ],
            [
                'category' => 'Triming and Tool Use',
                'element_name' => 'Trim-Cut thread with fixed blade',
                'description' => 'Memotong benang dengan pisau yang terpasang pada mesin',
                'code' => 'TBLD',
                'tmu' => 33,
                'seconds' => 1.20,
                'motion_sequence' => 'GP',
            ],
            [
                'category' => 'Triming and Tool Use',
                'element_name' => 'Trim-Dechain parts with scissors',
                'description' => 'Memotong sambungan benang antar komponen',
                'code' => 'TDCH',
                'tmu' => 49,
                'seconds' => 1.80,
                'motion_sequence' => 'GPPP',
            ],

            // =========================================================
            // ASIDING
            // =========================================================

            [
                'category' => 'Asiding',
                'element_name' => 'Aside-Push Away (Sliding)',
                'description' => 'Menggeser untuk menjauhkan',
                'code' => 'APSH',
                'tmu' => 24,
                'seconds' => 0.90,
                'motion_sequence' => 'GP',
            ],
            [
                'category' => 'Asiding',
                'element_name' => 'Aside Part with 1 Hand',
                'description' => 'Menggeser dengan 1 tangan',
                'code' => 'AS1H',
                'tmu' => 23,
                'seconds' => 0.80,
                'motion_sequence' => 'GP',
            ],
            [
                'category' => 'Asiding',
                'element_name' => 'Aside Part with 2 Hand',
                'description' => 'Menggeser dengan 2 tangan',
                'code' => 'AS2H',
                'tmu' => 42,
                'seconds' => 1.50,
                'motion_sequence' => 'GGP',
            ],

            // =========================================================
            // HANDLING MACHINE
            // =========================================================

            [
                'category' => 'Handling Machine',
                'element_name' => 'Machine Sew 1 cm Approx Greater 1 cm',
                'description' => 'Menjahit 1 cm dengan ketelitian lebih dari 1 cm',
                'code' => 'MS1A',
                'tmu' => 17,
                'seconds' => 0.60,
                'motion_sequence' => 'FF',
            ],
            [
                'category' => 'Handling Machine',
                'element_name' => 'Machine Sew 1 cm Accurately Within 1 cm',
                'description' => 'Menjahit 1 cm dengan keakuratan dalam 1 cm',
                'code' => 'MS1B',
                'tmu' => 26,
                'seconds' => 0.90,
                'motion_sequence' => 'FPBF',
            ],
            [
                'category' => 'Handling Machine',
                'element_name' => 'Machine Sew 1 cm Precisely Within 1/2 cm',
                'description' => 'Menjahit 1 cm dengan keakuratan dalam 1/2 cm',
                'code' => 'MS1C',
                'tmu' => 37,
                'seconds' => 1.30,
                'motion_sequence' => 'FPCF',
            ],
            [
                'category' => 'Handling Machine',
                'element_name' => 'Machine Handwheel to Raise/Lower Needle',
                'description' => 'Menurunkan/Menaikan jarum dengan handwheel',
                'code' => 'MHDW',
                'tmu' => 46,
                'seconds' => 1.70,
                'motion_sequence' => 'GPGPG',
            ],
            [
                'category' => 'Handling Machine',
                'element_name' => 'Machine Back Tack at Beginning (Lever)',
                'description' => 'Backtack/Atret pada awal jahit (menggunakan lever)',
                'code' => 'MBTB',
                'tmu' => 34,
                'seconds' => 1.20,
                'motion_sequence' => 'GPPPG',
            ],
            [
                'category' => 'Handling Machine',
                'element_name' => 'Machine Back Tack at End (Lever)',
                'description' => 'Backtack/Atret pada akhir jahit (menggunakan lever)',
                'code' => 'MBTE',
                'tmu' => 37,
                'seconds' => 1.30,
                'motion_sequence' => 'GPPTPPG',
            ],

            // =========================================================
            // GET AND PUT DATA
            // =========================================================

            [
                'category' => 'Get and Put Data',
                'element_name' => 'Get Part with 1 Hand (Easy)',
                'description' => 'Mengambil komponen dengan 1 tangan (mudah)',
                'code' => 'GP1E',
                'tmu' => 14,
                'seconds' => 0.50,
                'motion_sequence' => 'G',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Get Part With 1 Hand',
                'description' => 'Mengambil komponen dengan 1 tangan',
                'code' => 'GP1H',
                'tmu' => 20,
                'seconds' => 0.70,
                'motion_sequence' => 'G',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Get Part with 2 Hands',
                'description' => 'Mengambil dengan 2 tangan',
                'code' => 'GP2H',
                'tmu' => 33,
                'seconds' => 1.20,
                'motion_sequence' => 'GG',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Get Part Contact Only',
                'description' => 'Menjangkau komponen hanya dengan sentuhan',
                'code' => 'GPCO',
                'tmu' => 9,
                'seconds' => 0.30,
                'motion_sequence' => 'G',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Get Part From Other Hand',
                'description' => 'Mengambil komponen dari tangan yang lain',
                'code' => 'GPOH',
                'tmu' => 6,
                'seconds' => 0.20,
                'motion_sequence' => 'G',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Get Part by Adjusting Grasp',
                'description' => 'Mengambil komponen dengan menambahkan genggaman',
                'code' => 'GPAG',
                'tmu' => 10,
                'seconds' => 0.40,
                'motion_sequence' => 'G',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Put Part to Approximate Location',
                'description' => 'Menyimpan komponen ke lokasi yang ditentukan',
                'code' => 'PPAL',
                'tmu' => 10,
                'seconds' => 0.40,
                'motion_sequence' => 'P',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Put Part to Other Hand',
                'description' => 'Menyimpan/memberikan komponen ke tangan yang lain',
                'code' => 'PPOH',
                'tmu' => 6,
                'seconds' => 0.20,
                'motion_sequence' => 'P',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Put Part',
                'description' => 'Menyimpan komponen',
                'code' => 'PPST',
                'tmu' => 14,
                'seconds' => 0.50,
                'motion_sequence' => 'P',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Put Part Locate Once',
                'description' => 'Menyimpan komponen sekali',
                'code' => 'PPL1',
                'tmu' => 27,
                'seconds' => 1.00,
                'motion_sequence' => 'P',
            ],
            [
                'category' => 'Get and Put Data',
                'element_name' => 'Put Part Locate Twice',
                'description' => 'Menyimpan komponen dua kali',
                'code' => 'PPL2',
                'tmu' => 47,
                'seconds' => 1.70,
                'motion_sequence' => 'PP',
            ],
        ];

        foreach ($elements as $element) {
            $categoryId = $categories[$element['category']] ?? null;

            if (!$categoryId) {
                throw new \RuntimeException(
                    "GSD category not found: {$element['category']}"
                );
            }

            DB::table('gsd_elements')->updateOrInsert(
                [
                    'gsd_category_id' => $categoryId,
                    'element_name' => $element['element_name'],
                    'code' => $element['code'],
                ],
                [
                    'description' => $element['description'],
                    'tmu' => $element['tmu'],
                    'seconds' => $element['seconds'],
                    'motion_sequence' => $element['motion_sequence'],
                    'status' => 'active',
                    'updated_at' => now(),
                ]
            );
        }
    }
}