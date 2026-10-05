<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreProcessRequest;
use App\Http\Requests\Api\UpdateProcessRequest;
use App\Http\Resources\Api\ProcessResource;
use App\Models\Process;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessController extends BaseController
{
    public function index(Request $request)
    {
        $query = Process::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('process_name', 'like', "%{$search}%");
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        if ($request->has('with')) {
            $query->with(explode(',', $request->get('with')));
        }

        return $this->paginated($query, ProcessResource::class, $request);
    }

    public function store(StoreProcessRequest $request): JsonResponse
    {
        $process = Process::create($request->validated());
        return $this->success(new ProcessResource($process), 'Process created successfully', 201);
    }

    public function show(Request $request, Process $process): JsonResponse
    {
        $process->load(['latestVersion', 'versions']);
        return $this->success(new ProcessResource($process));
    }

    public function update(UpdateProcessRequest $request, Process $process): JsonResponse
    {
        $process->update($request->validated());
        return $this->success(new ProcessResource($process), 'Process updated successfully');
    }

    public function destroy(Process $process): JsonResponse
    {
        $process->delete();
        return $this->success(null, 'Process deleted successfully');
    }
}
