<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreMtmElementRequest;
use App\Http\Requests\Api\UpdateMtmElementRequest;
use App\Http\Resources\Api\MtmElementResource;
use App\Models\MtmElement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MtmElementController extends BaseController
{
    public function index(Request $request)
    {
        $query = MtmElement::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('element_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        return $this->paginated($query, MtmElementResource::class, $request);
    }

    public function store(StoreMtmElementRequest $request): JsonResponse
    {
        $element = MtmElement::create($request->validated());
        return $this->success(new MtmElementResource($element), 'MTM element created successfully', 201);
    }

    public function show(Request $request, MtmElement $mtmElement): JsonResponse
    {
        return $this->success(new MtmElementResource($mtmElement));
    }

    public function update(UpdateMtmElementRequest $request, MtmElement $mtmElement): JsonResponse
    {
        $mtmElement->update($request->validated());
        return $this->success(new MtmElementResource($mtmElement), 'MTM element updated successfully');
    }

    public function destroy(MtmElement $mtmElement): JsonResponse
    {
        $mtmElement->delete();
        return $this->success(null, 'MTM element deleted successfully');
    }
}
