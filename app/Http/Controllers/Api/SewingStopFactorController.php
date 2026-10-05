<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreSewingStopFactorRequest;
use App\Http\Requests\Api\UpdateSewingStopFactorRequest;
use App\Http\Resources\Api\SewingStopFactorResource;
use App\Models\SewingStopFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SewingStopFactorController extends BaseController
{
    public function index(Request $request)
    {
        $query = SewingStopFactor::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('factor_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        return $this->paginated($query, SewingStopFactorResource::class, $request);
    }

    public function store(StoreSewingStopFactorRequest $request): JsonResponse
    {
        $factor = SewingStopFactor::create($request->validated());
        return $this->success(new SewingStopFactorResource($factor), 'Sewing stop factor created successfully', 201);
    }

    public function show(Request $request, SewingStopFactor $sewingStopFactor): JsonResponse
    {
        return $this->success(new SewingStopFactorResource($sewingStopFactor));
    }

    public function update(UpdateSewingStopFactorRequest $request, SewingStopFactor $sewingStopFactor): JsonResponse
    {
        $sewingStopFactor->update($request->validated());
        return $this->success(new SewingStopFactorResource($sewingStopFactor), 'Sewing stop factor updated successfully');
    }

    public function destroy(SewingStopFactor $sewingStopFactor): JsonResponse
    {
        $sewingStopFactor->delete();
        return $this->success(null, 'Sewing stop factor deleted successfully');
    }
}
