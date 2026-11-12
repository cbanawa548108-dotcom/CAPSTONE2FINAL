<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\SalesTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $term = trim($validated['q']);
        $user = $request->user();
        $canManage = in_array($user->role, ['owner', 'manager'], true);

        $products = InventoryItem::query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('category', 'like', '%' . $term . '%');
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'category', 'stock_quantity', 'unit'])
            ->map(fn (InventoryItem $item) => [
                'type' => 'Product',
                'title' => $item->name,
                'detail' => ($item->category ?: 'Fruit') . ' · ' . $item->stock_quantity . ' ' . $item->unit . ' in stock',
                'url' => $canManage ? route('inventory') : route('pos'),
            ]);

        $transactions = SalesTransaction::query()
            ->where('type', 'sale')
            ->where(function ($query) use ($term) {
                $query->where('transaction_code', 'like', '%' . $term . '%')
                    ->orWhereHas('salesItems.inventoryItem', function ($itemQuery) use ($term) {
                        $itemQuery->where('name', 'like', '%' . $term . '%');
                    });
            })
            ->when(!$canManage, fn ($query) => $query->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'transaction_code', 'total_amount', 'status', 'created_at'])
            ->map(fn (SalesTransaction $sale) => [
                'type' => 'Sale',
                'title' => $sale->transaction_code,
                'detail' => ucfirst($sale->status) . ' · ₱' . number_format((float) $sale->total_amount, 2) . ' · ' . $sale->created_at->format('M j, Y'),
                'url' => $canManage ? route('sales') : route('pos'),
            ]);

        $pages = collect([
            ['title' => 'Sales', 'keywords' => 'sales transaction revenue', 'url' => $canManage ? route('sales') : route('pos'), 'available' => true],
            ['title' => 'Inventory', 'keywords' => 'inventory stock products fruits', 'url' => route('inventory'), 'available' => $canManage],
            ['title' => 'Sales Reports', 'keywords' => 'reports report sales revenue', 'url' => route('reports'), 'available' => $canManage],
            ['title' => 'Spoilage Predictions', 'keywords' => 'spoilage expiry risk prediction', 'url' => route('spoilage'), 'available' => $canManage],
            ['title' => 'Sales Forecast', 'keywords' => 'forecast demand sales', 'url' => route('forecast'), 'available' => $canManage],
            ['title' => 'Point of Sale', 'keywords' => 'pos checkout sell', 'url' => route('pos'), 'available' => true],
        ])->filter(fn (array $page) => $page['available'] && str_contains(mb_strtolower($page['title'] . ' ' . $page['keywords']), mb_strtolower($term)))
            ->take(4)
            ->map(fn (array $page) => [
                'type' => 'Page',
                'title' => $page['title'],
                'detail' => 'Open page',
                'url' => $page['url'],
            ]);

        return response()->json([
            'results' => $products->concat($transactions)->concat($pages)->take(10)->values(),
        ]);
    }
}
