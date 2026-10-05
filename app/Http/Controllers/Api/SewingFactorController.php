<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreSewingFactorRequest;
use App\Http\Requests\Api\UpdateSewingFactorRequest;
use App\Http\Resources\Api\SewingFactorResource;
use App\Models\SewingFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SewingFactorController extends BaseController
{
    public function index(Request $request)
    {
        $query = SewingFactor::query();

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

        return $this->paginated($query, SewingFactorResource::class, $request);
    }

    public function store(StoreSewingFactorRequest $request): JsonResponse
    {
        $factor = SewingFactor::create($request->validated());
        return $this->success(new SewingFactorResource($factor), 'Sewing factor created successfully', 201);
    }

    public function show(Request $request, SewingFactor $sewingFactor): JsonResponse
    {
        return $this->success(new SewingFactorResource($sewingFactor));
    }

    public function update(UpdateSewingFactorRequest $request, SewingFactor $sewingFactor): JsonResponse
    {
        $sewingFactor->update($request->validated());
        return $this->success(new SewingFactorResource($sewingFactor), 'Sewing factor updated successfully');
    }

    public function destroy(SewingFactor $sewingFactor): JsonResponse
    {
        $sewingFactor->delete();
        return $this->success(null, 'Sewing factor deleted successfully');
    }
}
