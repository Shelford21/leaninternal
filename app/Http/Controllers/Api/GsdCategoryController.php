<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreGsdCategoryRequest;
use App\Http\Requests\Api\UpdateGsdCategoryRequest;
use App\Http\Resources\Api\GsdCategoryResource;
use App\Models\GsdCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GsdCategoryController extends BaseController
{
    public function index(Request $request)
    {
        $query = GsdCategory::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('category_name', 'like', "%{$search}%");
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        return $this->paginated($query, GsdCategoryResource::class, $request);
    }

    public function store(StoreGsdCategoryRequest $request): JsonResponse
    {
        $category = GsdCategory::create($request->validated());
        return $this->success(new GsdCategoryResource($category), 'GSD category created successfully', 201);
    }

    public function show(Request $request, GsdCategory $gsdCategory): JsonResponse
    {
        return $this->success(new GsdCategoryResource($gsdCategory));
    }

    public function update(UpdateGsdCategoryRequest $request, GsdCategory $gsdCategory): JsonResponse
    {
        $gsdCategory->update($request->validated());
        return $this->success(new GsdCategoryResource($gsdCategory), 'GSD category updated successfully');
    }

    public function destroy(GsdCategory $gsdCategory): JsonResponse
    {
        $gsdCategory->delete();
        return $this->success(null, 'GSD category deleted successfully');
    }
}
