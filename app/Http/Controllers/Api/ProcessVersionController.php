<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreProcessVersionRequest;
use App\Http\Requests\Api\UpdateProcessVersionRequest;
use App\Http\Resources\Api\ProcessVersionResource;
use App\Models\ProcessVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessVersionController extends BaseController
{
    public function index(Request $request)
    {
        $query = ProcessVersion::query();

        if ($request->has('process_id')) {
            $query->where('process_id', $request->get('process_id'));
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        } else {
            $query->orderBy('version_number', 'desc');
        }

        if ($request->has('with')) {
            $query->with(explode(',', $request->get('with')));
        } else {
            $query->with(['process', 'creator']);
        }

        return $this->paginated($query, ProcessVersionResource::class, $request);
    }

    public function store(StoreProcessVersionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        // Auto-increment version number
        $latestVersion = ProcessVersion::where('process_id', $data['process_id'])
            ->max('version_number');
        $data['version_number'] = ($latestVersion ?? 0) + 1;

        $version = ProcessVersion::create($data);
        return $this->success(new ProcessVersionResource($version), 'Process version created successfully', 201);
    }

    public function show(Request $request, ProcessVersion $processVersion): JsonResponse
    {
        $processVersion->load(['process', 'creator']);
        return $this->success(new ProcessVersionResource($processVersion));
    }

    public function update(UpdateProcessVersionRequest $request, ProcessVersion $processVersion): JsonResponse
    {
        $processVersion->update($request->validated());
        return $this->success(new ProcessVersionResource($processVersion), 'Process version updated successfully');
    }

    public function destroy(ProcessVersion $processVersion): JsonResponse
    {
        $processVersion->delete();
        return $this->success(null, 'Process version deleted successfully');
    }
}
