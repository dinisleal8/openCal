<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = $request->user()
            ->products()
            ->orderByDesc('updated_at')
            ->paginate(20);

        return response()->json($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $request->user()->products()->create([
            ...$request->validated(),
            'source' => $request->input('source', 'barcode'),
        ]);

        return response()->json($product, 201);
    }

    public function show(Product $product): JsonResponse
    {
        Gate::authorize('view', $product);

        return response()->json($product);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $product->update($request->validated());

        return response()->json($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

    public function barcode(string $barcode): JsonResponse
    {

        $response = Http::connectTimeout(3)
            ->timeout(5)
            ->get("https://world.openfoodfacts.org/api/v2/product/{$barcode}.json");

        if ($response->failed() || data_get($response->json(), 'status') !== 1) {
            return response()->json([
                'message' => 'Product not found for barcode: '.$barcode,
            ], 404);
        }

        $product = data_get($response->json(), 'product');

        return response()->json([
            'name' => data_get($product, 'product_name', ''),
            'brand' => data_get($product, 'brands', ''),
            'barcode' => $barcode,
            'calories_kcal_per_100g' => data_get($product, 'nutriments.energy-kcal_100g', 0),
            'protein_g_per_100g' => data_get($product, 'nutriments.proteins_100g', 0),
            'carbs_g_per_100g' => data_get($product, 'nutriments.carbohydrates_100g', 0),
            'fat_g_per_100g' => data_get($product, 'nutriments.fat_100g', 0),
            'serving_description' => data_get($product, 'serving_size', ''),
        ]);
    }
}
