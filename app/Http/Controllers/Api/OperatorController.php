<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreOperatorRequest;
use App\Http\Requests\Api\UpdateOperatorRequest;
use App\Http\Resources\Api\OperatorResource;
use App\Models\Operator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorController extends BaseController
{
    public function index(Request $request)
    {
        $query = Operator::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('operator_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        return $this->paginated($query, OperatorResource::class, $request);
    }

    public function store(StoreOperatorRequest $request): JsonResponse
    {
        $operator = Operator::create($request->validated());
        return $this->success(new OperatorResource($operator), 'Operator created successfully', 201);
    }

    public function show(Request $request, Operator $operator): JsonResponse
    {
        return $this->success(new OperatorResource($operator));
    }

    public function update(UpdateOperatorRequest $request, Operator $operator): JsonResponse
    {
        $operator->update($request->validated());
        return $this->success(new OperatorResource($operator), 'Operator updated successfully');
    }

    public function destroy(Operator $operator): JsonResponse
    {
        $operator->delete();
        return $this->success(null, 'Operator deleted successfully');
    }
}
