<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreArticleRequest;
use App\Http\Requests\Api\UpdateArticleRequest;
use App\Http\Resources\Api\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends BaseController
{
    public function index(Request $request)
    {
        $query = Article::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('article_name', 'like', "%{$search}%")
                    ->orWhere('label_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('sort')) {
            $order = $request->get('order', 'asc');
            $query->orderBy($request->get('sort'), $order);
        }

        return $this->paginated($query, ArticleResource::class, $request);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = Article::create($request->validated());
        return $this->success(new ArticleResource($article), 'Article created successfully', 201);
    }

    public function show(Request $request, Article $article): JsonResponse
    {
        return $this->success(new ArticleResource($article));
    }

    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        $article->update($request->validated());
        return $this->success(new ArticleResource($article), 'Article updated successfully');
    }

    public function destroy(Article $article): JsonResponse
    {
        $article->delete();
        return $this->success(null, 'Article deleted successfully');
    }
}
