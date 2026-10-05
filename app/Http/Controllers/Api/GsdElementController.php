<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreGsdElementRequest;
use App\Http\Requests\Api\UpdateGsdElementRequest;
use App\Http\Resources\Api\GsdElementResource;
use App\Models\GsdElement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GsdElementController extends BaseController
{
    public function index(Request $request)
    {
        $query = GsdElement::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('element_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('gsd_category_id')) {
            $query->where('gsd_category_id', $request->get('gsd_category_id'));
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        if ($request->has('with')) {
            $query->with(explode(',', $request->get('with')));
        } else {
            $query->with('gsdCategory');
        }

        return $this->paginated($query, GsdElementResource::class, $request);
    }

    public function store(StoreGsdElementRequest $request): JsonResponse
    {
        $element = GsdElement::create($request->validated());
        return $this->success(new GsdElementResource($element), 'GSD element created successfully', 201);
    }

    public function show(Request $request, GsdElement $gsdElement): JsonResponse
    {
        $gsdElement->load('gsdCategory');
        return $this->success(new GsdElementResource($gsdElement));
    }

    public function update(UpdateGsdElementRequest $request, GsdElement $gsdElement): JsonResponse
    {
        $gsdElement->update($request->validated());
        return $this->success(new GsdElementResource($gsdElement), 'GSD element updated successfully');
    }

    public function destroy(GsdElement $gsdElement): JsonResponse
    {
        $gsdElement->delete();
        return $this->success(null, 'GSD element deleted successfully');
    }
}
