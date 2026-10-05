<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreFactoryRequest;
use App\Http\Requests\Api\UpdateFactoryRequest;
use App\Http\Resources\Api\FactoryResource;
use App\Models\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FactoryController extends BaseController
{
    public function index(Request $request)
    {
        $query = Factory::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('factory_name', 'like', "%{$search}%");
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        if ($request->has('with')) {
            $query->with(explode(',', $request->get('with')));
        }

        return $this->paginated($query, FactoryResource::class, $request);
    }

    public function store(StoreFactoryRequest $request): JsonResponse
    {
        $factory = Factory::create($request->validated());
        return $this->success(new FactoryResource($factory), 'Factory created successfully', 201);
    }

    public function show(Request $request, Factory $factory): JsonResponse
    {
        $factory->load(['departments']);
        return $this->success(new FactoryResource($factory));
    }

    public function update(UpdateFactoryRequest $request, Factory $factory): JsonResponse
    {
        $factory->update($request->validated());
        return $this->success(new FactoryResource($factory), 'Factory updated successfully');
    }

    public function destroy(Factory $factory): JsonResponse
    {
        $factory->delete();
        return $this->success(null, 'Factory deleted successfully');
    }
}
