<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreDepartmentRequest;
use App\Http\Requests\Api\UpdateDepartmentRequest;
use App\Http\Resources\Api\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends BaseController
{
    public function index(Request $request)
    {
        $query = Department::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('department_name', 'like', "%{$search}%");
        }

        if ($request->has('factory_id')) {
            $query->where('factory_id', $request->get('factory_id'));
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        if ($request->has('with')) {
            $query->with(explode(',', $request->get('with')));
        } else {
            $query->with('factory');
        }

        return $this->paginated($query, DepartmentResource::class, $request);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());
        return $this->success(new DepartmentResource($department), 'Department created successfully', 201);
    }

    public function show(Request $request, Department $department): JsonResponse
    {
        $department->load('factory');
        return $this->success(new DepartmentResource($department));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());
        return $this->success(new DepartmentResource($department), 'Department updated successfully');
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();
        return $this->success(null, 'Department deleted successfully');
    }
}
