<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LineBalancingReport;
use App\Models\LineBalancingReportRow;
use App\Models\Factory;
use App\Models\Article;
use App\Models\ProductionLine;
use App\Models\MachineType;
use App\Models\User;

class LineBalancingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder is idempotent — it will not create duplicate records.
     * Sample data matches the Line Balancing Excel template specification.
     */
    public function run(): void
    {
        // Only seed if no reports exist yet
        if (LineBalancingReport::count() > 0) {
            $this->command->info('Line Balancing reports already exist. Skipping seeder.');
            return;
        }

        $factory = Factory::where('status', 'active')->first();
        $article = Article::where('status', 'active')->first();
        $line = ProductionLine::where('status', 'active')->first();
        $user = User::first();

        if (!$factory || !$article || !$line || !$user) {
            $this->command->info('Missing required data (factory, article, line, or user). Skipping Line Balancing seeder.');
            return;
        }

        // Find or use first machine type (SNLS equivalent)
        $snls = MachineType::where('status', 'active')->first();

        // Create the sample report matching the Excel template
        $report = LineBalancingReport::create([
            'factory_id' => $factory->id,
            'article_id' => $article->id,
            'line_id' => $line->id,
            'report_name' => 'LINE BALANCING SEWING — Livlig Husky',
            'target_output_per_hour' => 93,
            'output_actual' => 93,
            'working_hours_per_day' => 8,
            'allowance_percent' => 15,
            'update_date' => '2024-03-21',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        // Sample process data from the Excel workbook (21 rows)
        $sampleRows = [
            ['row_number' => 1, 'machine_type_id' => $snls?->id, 'process' => 'Kupnat badan depan + Jahit ekor awal', 'name' => 'Ujang S', 'joint_process' => null, 'operator' => 1, 'ct_1' => 27.78, 'ct_2' => 28.60, 'ct_3' => 27.75, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 2, 'machine_type_id' => $snls?->id, 'process' => 'Jahit paha dalam awal kiri & kanan + Jahit tangan kanan', 'name' => 'Dede Wiwin', 'joint_process' => null, 'operator' => 1, 'ct_1' => 24.73, 'ct_2' => 24.65, 'ct_3' => 25.50, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 3, 'machine_type_id' => $snls?->id, 'process' => 'Jahit tangan dalam kiri dan kanan + Jahit paha dalam kiri dan kanan', 'name' => 'Nunung', 'joint_process' => null, 'operator' => 1, 'ct_1' => 34.23, 'ct_2' => 33.74, 'ct_3' => 33.10, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 4, 'machine_type_id' => $snls?->id, 'process' => 'Gabung badan depan ke dada + Gabung badan samping kiri & kanan', 'name' => 'Supendi', 'joint_process' => null, 'operator' => 1, 'ct_1' => 26.79, 'ct_2' => 27.56, 'ct_3' => 26.82, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 5, 'machine_type_id' => $snls?->id, 'process' => 'Pasang tangan kiri & kanan ke body', 'name' => 'Winda', 'joint_process' => null, 'operator' => 1, 'ct_1' => 25.56, 'ct_2' => 25.16, 'ct_3' => 26.62, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 6, 'machine_type_id' => $snls?->id, 'process' => 'Tutup badan samping kiri & kanan', 'name' => 'Marna', 'joint_process' => null, 'operator' => 1, 'ct_1' => 35.47, 'ct_2' => 35.21, 'ct_3' => 34.31, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 7, 'machine_type_id' => $snls?->id, 'process' => 'Pasang paha kiri & kanan ke body', 'name' => 'Linda', 'joint_process' => null, 'operator' => 1, 'ct_1' => 31.69, 'ct_2' => 31.20, 'ct_3' => 30.50, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 8, 'machine_type_id' => $snls?->id, 'process' => 'Tutup ekor + Gabung ekor ke body + Tutup punggung', 'name' => 'Dewi Enggar', 'joint_process' => null, 'operator' => 1, 'ct_1' => 25.26, 'ct_2' => 24.31, 'ct_3' => 24.28, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 9, 'machine_type_id' => $snls?->id, 'process' => 'Jahit tangan awal kiri + Pasang label & tutup pantat', 'name' => 'Irnawati', 'joint_process' => null, 'operator' => 1, 'ct_1' => 25.32, 'ct_2' => 25.38, 'ct_3' => 25.43, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 10, 'machine_type_id' => $snls?->id, 'process' => 'Pasang telapak 3x', 'name' => 'Ucih', 'joint_process' => null, 'operator' => 1, 'ct_1' => 29.57, 'ct_2' => 30.06, 'ct_3' => 31.85, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 11, 'machine_type_id' => $snls?->id, 'process' => 'Gabung kepala ke badan + Pasang telapak 1x + Tutup punggung', 'name' => 'Cucu H', 'joint_process' => null, 'operator' => 1, 'ct_1' => 31.09, 'ct_2' => 31.99, 'ct_3' => 31.92, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 12, 'machine_type_id' => $snls?->id, 'process' => 'Pasang spot kiri & kanan', 'name' => 'Hasan', 'joint_process' => null, 'operator' => 1, 'ct_1' => 31.03, 'ct_2' => 30.07, 'ct_3' => 31.18, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 13, 'machine_type_id' => $snls?->id, 'process' => 'Jahit telinga awal kiri & kanan + Stik telinga kiri & kanan', 'name' => 'Munasipa', 'joint_process' => null, 'operator' => 1, 'ct_1' => 22.97, 'ct_2' => 22.68, 'ct_3' => 22.34, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 14, 'machine_type_id' => $snls?->id, 'process' => 'Pasang lidah ke bibir + Stik lidah + Kupnat tangan kiri & kanan', 'name' => 'Ardi Wijaya', 'joint_process' => null, 'operator' => 1, 'ct_1' => 25.96, 'ct_2' => 25.40, 'ct_3' => 25.49, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 15, 'machine_type_id' => $snls?->id, 'process' => 'Pasang kening ke pipi kiri & kanan + Jahit dagu', 'name' => 'Edeh Komala', 'joint_process' => null, 'operator' => 1, 'ct_1' => 25.78, 'ct_2' => 26.95, 'ct_3' => 26.47, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 16, 'machine_type_id' => $snls?->id, 'process' => 'Kupnat paha kiri & kanan + Pasang Embo mata ke pipi kiri & kanan', 'name' => 'Milata', 'joint_process' => null, 'operator' => 1, 'ct_1' => 25.41, 'ct_2' => 26.93, 'ct_3' => 27.73, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 17, 'machine_type_id' => $snls?->id, 'process' => 'Pasang telinga kiri & kanan + Pasang dagu', 'name' => 'Nani', 'joint_process' => null, 'operator' => 1, 'ct_1' => 25.81, 'ct_2' => 25.75, 'ct_3' => 26.68, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 18, 'machine_type_id' => $snls?->id, 'process' => 'Pasang + lipat hidung + Pasang Tc ke batang hidung', 'name' => 'Alit', 'joint_process' => null, 'operator' => 1, 'ct_1' => 30.47, 'ct_2' => 29.03, 'ct_3' => 29.97, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 19, 'machine_type_id' => $snls?->id, 'process' => 'Kupnat pipi kiri & kanan + Pasang pipi ke muka + Kupnat dada', 'name' => 'Itoh', 'joint_process' => null, 'operator' => 1, 'ct_1' => 29.00, 'ct_2' => 27.81, 'ct_3' => 28.43, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 20, 'machine_type_id' => $snls?->id, 'process' => 'Gabung lidah ke mulut + pasang mulut embo mata + kupnat dagu', 'name' => 'Nurhasanah', 'joint_process' => null, 'operator' => 1, 'ct_1' => 31.00, 'ct_2' => 30.06, 'ct_3' => 30.29, 'ct_4' => null, 'ct_5' => null],
            ['row_number' => 21, 'machine_type_id' => $snls?->id, 'process' => 'Gabung kepala belakang kiri dan kanan + Gabung kepala belakang ke muka', 'name' => 'Amanan Mulyanan', 'joint_process' => null, 'operator' => 1, 'ct_1' => 27.98, 'ct_2' => 27.32, 'ct_3' => 27.91, 'ct_4' => null, 'ct_5' => null],
        ];

        foreach ($sampleRows as $rowData) {
            $report->rows()->create($rowData);
        }

        $this->command->info('Line Balancing seeder completed: 1 report ("LINE BALANCING SEWING — Livlig Husky") with 21 process rows created.');
    }
}