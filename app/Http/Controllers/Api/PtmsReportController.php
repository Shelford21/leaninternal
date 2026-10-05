<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StorePtmsReportRequest;
use App\Http\Requests\Api\UpdatePtmsReportRequest;
use App\Http\Resources\Api\PtmsReportResource;
use App\Models\PtmsReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PtmsReportController extends BaseController
{
    public function index(Request $request)
    {
        $query = PtmsReport::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('report_number', 'like', "%{$search}%");
        }

        // Filters
        $filters = ['article_id', 'line_id', 'factory_id', 'department_id', 'operator_id', 'status'];
        foreach ($filters as $filter) {
            if ($request->has($filter)) {
                $query->where($filter, $request->get($filter));
            }
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('with')) {
            $query->with(explode(',', $request->get('with')));
        } else {
            $query->with(['article', 'processVersion', 'operator', 'factory', 'department', 'productionLine', 'creator']);
        }

        return $this->paginated($query, PtmsReportResource::class, $request);
    }

    public function store(StorePtmsReportRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $report = PtmsReport::create($data);
        $report->load(['article', 'processVersion', 'operator', 'factory', 'department', 'productionLine', 'creator']);
        return $this->success(new PtmsReportResource($report), 'PTMS report created successfully', 201);
    }

    public function show(Request $request, PtmsReport $ptmsReport): JsonResponse
    {
        $ptmsReport->load(['article', 'processVersion', 'operator', 'factory', 'department', 'productionLine', 'creator']);
        return $this->success(new PtmsReportResource($ptmsReport));
    }

    public function update(UpdatePtmsReportRequest $request, PtmsReport $ptmsReport): JsonResponse
    {
        $ptmsReport->update($request->validated());
        $ptmsReport->load(['article', 'processVersion', 'operator', 'factory', 'department', 'productionLine', 'creator']);
        return $this->success(new PtmsReportResource($ptmsReport), 'PTMS report updated successfully');
    }

    public function destroy(PtmsReport $ptmsReport): JsonResponse
    {
        $ptmsReport->delete();
        return $this->success(null, 'PTMS report deleted successfully');
    }

    public function updateStatus(Request $request, PtmsReport $ptmsReport): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,final,archived',
        ]);

        $ptmsReport->update(['status' => $request->get('status')]);
        return $this->success(new PtmsReportResource($ptmsReport), 'PTMS report status updated successfully');
    }
}
