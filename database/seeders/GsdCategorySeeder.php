<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GsdCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'Obtain and Match Part or Parts',
                'description' => 'Activities involving obtaining and matching parts or components.',
                'status' => 'active',
            ],
            [
                'category_name' => 'Aligning and Adjusting',
                'description' => 'Activities involving aligning, repositioning, and adjusting parts.',
                'status' => 'active',
            ],
            [
                'category_name' => 'Forming Shapes',
                'description' => 'Activities involving folding, forming, unfolding, or laying out parts.',
                'status' => 'active',
            ],
            [
                'category_name' => 'Triming and Tool Use',
                'description' => 'Activities involving trimming, cutting, and tool use.',
                'status' => 'active',
            ],
            [
                'category_name' => 'Asiding',
                'description' => 'Activities involving moving or sliding parts aside.',
                'status' => 'active',
            ],
            [
                'category_name' => 'Handling Machine',
                'description' => 'Activities involving handling and operating the sewing machine.',
                'status' => 'active',
            ],
            [
                'category_name' => 'Get and Put Data',
                'description' => 'Activities involving getting and putting parts.',
                'status' => 'active',
            ],
        ];

        foreach ($categories as $category) {
            DB::table('gsd_categories')->updateOrInsert(
                [
                    'category_name' => $category['category_name'],
                ],
                [
                    'description' => $category['description'],
                    'status' => $category['status'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}