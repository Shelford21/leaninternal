<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use App\Models\Department;
use App\Models\Factory;
use App\Models\GsdCategory;
use App\Models\GsdElement;
use App\Models\MtmElement;
use App\Models\Operator;
use App\Models\Process;
use App\Models\ProcessVersion;
use App\Models\PtmsReport;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\SewingFactor;
use App\Models\SewingStopFactor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    public function stats(): JsonResponse
    {
        $data = [
            'factories' => Factory::count(),
            'departments' => Department::count(),
            'production_lines' => ProductionLine::count(),
            'articles' => Article::count(),
            'operators' => Operator::count(),
            'gsd_categories' => GsdCategory::count(),
            'gsd_elements' => GsdElement::count(),
            'mtm_elements' => MtmElement::count(),
            'sewing_factors' => SewingFactor::count(),
            'sewing_stop_factors' => SewingStopFactor::count(),
            'processes' => Process::count(),
            'process_versions' => ProcessVersion::count(),
            'ptms_reports' => PtmsReport::count(),
            'roles' => Role::count(),
            'users' => User::count(),
            'average_smv' => PtmsReport::avg('smv'),
        ];

        return $this->success($data, 'Dashboard statistics retrieved successfully');
    }
}
