<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreProductionLineRequest;
use App\Http\Requests\Api\UpdateProductionLineRequest;
use App\Http\Resources\Api\ProductionLineResource;
use App\Models\ProductionLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionLineController extends BaseController
{
    public function index(Request $request)
    {
        $query = ProductionLine::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('line_name', 'like', "%{$search}%");
        }

        if ($request->has('division_id')) {
            $query->where('division_id', $request->get('division_id'));
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        if ($request->has('with')) {
            $query->with(explode(',', $request->get('with')));
        } else {
            $query->with('division');
        }

        return $this->paginated($query, ProductionLineResource::class, $request);
    }

    public function store(StoreProductionLineRequest $request): JsonResponse
    {
        $line = ProductionLine::create($request->validated());
        return $this->success(new ProductionLineResource($line), 'Production line created successfully', 201);
    }

    public function show(Request $request, ProductionLine $productionLine): JsonResponse
    {
        $productionLine->load('division');
        return $this->success(new ProductionLineResource($productionLine));
    }

    public function update(UpdateProductionLineRequest $request, ProductionLine $productionLine): JsonResponse
    {
        $productionLine->update($request->validated());
        return $this->success(new ProductionLineResource($productionLine), 'Production line updated successfully');
    }

    public function destroy(ProductionLine $productionLine): JsonResponse
    {
        $productionLine->delete();
        return $this->success(null, 'Production line deleted successfully');
    }
}
