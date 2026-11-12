<?php

namespace App\Http\Controllers;

use App\Models\SalesTransaction;
use App\Models\SalesItem;
use App\Models\InventoryItem;
use App\Models\InventoryBatch;
use App\Models\OwnerSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SalesController extends Controller
{
    private const TAX_RATE = 0.12;

    /**
     * Display the sales page.
     */
    public function page(Request $request)
    {
        $timezone = OwnerSetting::forUser(Auth::id())->timezone ?: config('app.timezone');
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $selectedDate = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m-d', $validated['date'], $timezone)
            : Carbon::now($timezone);
        $dayStart = $selectedDate->copy()->startOfDay()->utc();
        $nextDayStart = $dayStart->copy()->addDay();

        $salesQuery = SalesTransaction::with(['salesItems.inventoryItem', 'salesItems.inventoryBatch.inventoryItem', 'user'])
            ->where('payment_method', '!=', 'adjustment') // Exclude adjustments
            ->where('type', 'sale') // Exclude spoilage/wastage write-offs
            ->where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $nextDayStart);

        $sales = $salesQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // Summary cards follow the date selected in the transaction filter.
        $todayTotal = SalesTransaction::completed()
            ->where('payment_method', '!=', 'adjustment')
            ->where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $nextDayStart)
            ->sum('total_amount');
        
        $todayTransactions = SalesTransaction::completed()
            ->where('payment_method', '!=', 'adjustment')
            ->where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $nextDayStart)
            ->count();
        
        $avgSaleValue = $todayTransactions > 0 ? $todayTotal / $todayTransactions : 0;
        
        // Get top seller today
        $topSeller = \App\Models\SalesItem::select('inventory_batch_id', DB::raw('SUM(quantity) as total_qty'))
            ->whereHas('saleTransaction', function($q) use ($dayStart, $nextDayStart) {
                $q->completed()
                    ->where('payment_method', '!=', 'adjustment')
                    ->where('created_at', '>=', $dayStart)
                    ->where('created_at', '<', $nextDayStart);
            })
            ->groupBy('inventory_batch_id')
            ->orderByDesc('total_qty')
            ->with('inventoryBatch.inventoryItem')
            ->first();
        
        $topSellerName = $topSeller && $topSeller->inventoryBatch && $topSeller->inventoryBatch->inventoryItem 
            ? $topSeller->inventoryBatch->inventoryItem->name 
            : 'N/A';

        return view('pages.sales', [
            'sales' => $sales,
            'todayTotal' => $todayTotal,
            'todayTransactions' => $todayTransactions,
            'avgSaleValue' => $avgSaleValue,
            'topSellerName' => $topSellerName,
            'timezone' => $timezone,
            'selectedDate' => $selectedDate->toDateString(),
        ]);
    }
    
    /**
     * Get sales list (API).
     */
    public function index()
    {
        $sales = SalesTransaction::with(['salesItems.inventoryItem', 'user'])
            ->where('type', 'sale')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($sales);
    }

    /**
     * Display the POS page.
     */
    public function pos()
    {
        $timezone = OwnerSetting::forUser(Auth::id())->timezone ?: config('app.timezone');
        $todayStart = Carbon::now($timezone)->startOfDay();
        $tomorrowStart = $todayStart->copy()->addDay();
        $todayStart->utc();
        $tomorrowStart->utc();

        $products = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available')
                  ->where('quantity', '>', 0)
                  ->orderBy('expiry_date', 'asc'); // FIFO
        }])
        ->where('status', 'active')
        ->whereHas('batches', function($query) {
            $query->where('status', 'available')->where('quantity', '>', 0);
        })
        ->get();

        $todaySales = SalesTransaction::completed()
            ->whereBetween('created_at', [$todayStart, $tomorrowStart])
            ->sum('total_amount');
        $todayTransactions = SalesTransaction::completed()
            ->whereBetween('created_at', [$todayStart, $tomorrowStart])
            ->count();
        $topSeller = SalesItem::query()
            ->select('inventory_item_id', DB::raw('SUM(quantity) as total_qty'))
            ->whereHas('saleTransaction', function ($query) use ($todayStart, $tomorrowStart) {
                $query->completed()->whereBetween('created_at', [$todayStart, $tomorrowStart]);
            })
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_qty')
            ->with('inventoryItem')
            ->first();

        $recentTransactions = SalesTransaction::completed()
            ->where('payment_method', '!=', 'adjustment')
            ->with('salesItems')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function ($transaction) use ($timezone) {
                $customer = preg_match('/^Customer:\s*(.+)$/i', (string) $transaction->notes, $matches)
                    ? trim($matches[1])
                    : 'Walk-in Customer';

                return [
                    'transaction_code' => $transaction->transaction_code,
                    'customer' => $customer ?: 'Walk-in Customer',
                    'line_count' => $transaction->salesItems->count(),
                    'total_amount' => (float) $transaction->total_amount,
                    'payment_method' => ucfirst($transaction->payment_method),
                    'created_at' => $transaction->created_at->copy()->timezone($timezone)->format('h:i A'),
                ];
            })
            ->values();

        $activeProductCount = InventoryItem::where('status', 'active')
            ->whereHas('batches', function ($query) {
                $query->where('status', 'available')->where('quantity', '>', 0);
            })
            ->count();

        $posSummary = [
            ['M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z','text-violet-500',"Today's Sales",'₱' . number_format((float) $todaySales, 2),'text-violet-700'],
            ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','text-green-500','Transactions',(string) $todayTransactions,'text-green-700'],
            ['M5 3l14 9-14 9V3z','text-amber-500',"Today's Best Seller",$topSeller?->inventoryItem?->name ?? 'N/A','text-amber-700'],
            ['M13 7h8m0 0v8m0-8l-8 8-4-4-6 6','text-blue-500','Active Products',(string) $activeProductCount,'text-blue-700'],
        ];

        return view('pages.pos', [
            'products' => $products,
            'posAlerts' => $this->posAlerts(),
            'recentTransactions' => $recentTransactions,
            'posSummary' => $posSummary,
            'timezone' => $timezone,
        ]);
    }

    /**
     * Alerts shown as floating cards on the POS screen: items at or below their
     * reorder level, and batches the spoilage model rates Critical / High Risk today.
     */
    private function posAlerts(): array
    {
        // In-app push alerts can be switched off in Settings > Notifications
        if (!\App\Models\OwnerSetting::forUser(Auth::id())->push_notifications) {
            return [];
        }

        $low = InventoryItem::lowStock()->active()
            ->orderBy('stock_quantity')->limit(3)->get()
            ->map(fn ($i) => [
                'type' => 'low_stock',
                'name' => $i->name,
                'detail' => rtrim(rtrim(number_format((float) $i->stock_quantity, 2, '.', ''), '0'), '.') . ' ' . $i->unit,
            ]);

        $spoilage = collect();
        $date = DB::table('spoilage_predictions')->max('prediction_date');
        if ($date) {
            $spoilage = DB::table('spoilage_predictions as p')
                ->join('inventory_batches as b', 'b.id', '=', 'p.inventory_batch_id')
                ->join('inventory_items as i', 'i.id', '=', 'p.inventory_item_id')
                ->where('p.prediction_date', $date)->where('p.horizon_day', 0)
                ->whereIn('p.risk_level', ['Critical', 'High Risk'])
                ->where('b.status', 'available')->where('b.quantity', '>', 0)
                ->orderByDesc('p.risk_score')->limit(2)
                ->get(['i.name', 'b.batch_code', 'p.risk_level', 'p.recommendation'])
                ->map(fn ($r) => [
                    'type' => 'spoilage',
                    'name' => $r->name,
                    'detail' => $r->recommendation ?: 'Sell soon',
                    'level' => $r->risk_level,
                    'batch' => $r->batch_code,
                ]);
        }

        return $low->concat($spoilage)->values()->all();
    }

    /**
     * Create a new sale transaction.
     */
    public function createSale(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:inventory_items,id',
            'items.*.batch_id' => 'required|exists:inventory_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,gcash',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'cash_received' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $cashReceived = (float) ($validated['cash_received'] ?? 0);
        $transaction = DB::transaction(function () use ($validated, $cashReceived) {
            $lines = collect($validated['items']);
            $batchLinesById = $lines->groupBy('batch_id')->sortKeys();
            $batches = [];
            foreach ($batchLinesById as $batchId => $batchLines) {
                $batch = InventoryBatch::whereKey($batchId)->lockForUpdate()->first();
                if (!$batch || $batch->status !== 'available' || (float) $batch->quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'One of the selected batches is no longer available. Refresh the products and try again.',
                    ]);
                }

                if ($batchLines->contains(fn ($line) => (int) $line['product_id'] !== (int) $batch->inventory_item_id)) {
                    throw ValidationException::withMessages([
                        'items' => 'A selected batch does not belong to its product.',
                    ]);
                }

                $requestedQuantity = $batchLines->sum(fn ($line) => (float) $line['quantity']);
                if ((float) $batch->quantity < $requestedQuantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock in batch {$batch->batch_code}. Refresh the products and try again.",
                    ]);
                }

                $product = InventoryItem::whereKey($batch->inventory_item_id)
                    ->lockForUpdate()
                    ->first();
                if (!$product || $product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'items' => 'One of the selected products is no longer active.',
                    ]);
                }

                $batches[$batchId] = $batch;
            }

            $subtotal = 0;
            foreach ($lines as $item) {
                $batch = $batches[$item['batch_id']];
                $subtotal += round((float) $item['quantity'] * (float) $batch->price_per_unit, 2);
            }

            $discountPercent = (float) ($validated['discount_percent'] ?? 0);
            $discountAmount = round($subtotal * $discountPercent / 100, 2);

            $taxableAmount = max(0, $subtotal - $discountAmount);
            $taxAmount = round($taxableAmount * self::TAX_RATE, 2);
            $totalAmount = round($taxableAmount + $taxAmount, 2);
            if ($validated['payment_method'] === 'cash' && $cashReceived > 0 && $cashReceived < $totalAmount) {
                throw ValidationException::withMessages([
                    'cash_received' => 'Cash received is less than the server-calculated total.',
                ]);
            }

            // Generate transaction code using the user's local date.
            $timezone = OwnerSetting::forUser(Auth::id())->timezone ?: config('app.timezone');
            $transactionCode = 'TXN-' . now($timezone)->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $transaction = SalesTransaction::create([
                'user_id' => Auth::id(),
                'transaction_code' => $transactionCode,
                'type' => 'sale',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lines as $itemData) {
                $batch = $batches[$itemData['batch_id']];
                $unitPrice = (float) $batch->price_per_unit;
                SalesItem::create([
                    'sale_transaction_id' => $transaction->id,
                    'inventory_item_id' => $itemData['product_id'],
                    'inventory_batch_id' => $itemData['batch_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'total_amount' => round((float) $itemData['quantity'] * $unitPrice, 2),
                ]);
            }

            $affectedItemIds = [];
            foreach ($batchLinesById as $batchId => $batchLines) {
                $batch = $batches[$batchId];
                $batch->decrement('quantity', $batchLines->sum(fn ($line) => (float) $line['quantity']));
                $batch->refresh();
                if ($batch->quantity <= 0) {
                    $batch->update(['status' => 'depleted']);
                }
                $affectedItemIds[$batch->inventory_item_id] = true;
            }

            foreach (array_keys($affectedItemIds) as $itemId) {
                \App\Models\InventoryItem::findOrFail($itemId)->syncStockQuantityFromBatches();
            }

            return $transaction;
        });

        return response()->json([
            'success' => true,
            'message' => 'Sale completed successfully',
            'data' => [
                'transaction_code' => $transaction->transaction_code,
                'subtotal' => (float) $transaction->subtotal,
                'tax_amount' => (float) $transaction->tax_amount,
                'discount_amount' => (float) $transaction->discount_amount,
                'total_amount' => (float) $transaction->total_amount,
                'transaction_id' => $transaction->id,
                'created_at' => $transaction->created_at->copy()
                    ->timezone(OwnerSetting::forUser(Auth::id())->timezone ?: config('app.timezone'))
                    ->format('h:i A'),
                'payment_method' => ucfirst($transaction->payment_method),
                'change_amount' => $transaction->payment_method === 'cash'
                    ? round(max(0, $cashReceived - (float) $transaction->total_amount), 2)
                    : 0,
                'customer' => preg_match('/^Customer:\s*(.+)$/i', (string) $transaction->notes, $matches)
                    ? (trim($matches[1]) ?: 'Walk-in Customer')
                    : 'Walk-in Customer',
                'line_count' => count($validated['items']),
            ],
        ], 201);
    }

    /**
     * Get sales statistics.
     */
    public function getStats(Request $request)
    {
        $period = $request->input('period', 'today'); // today, week, month, year

        $query = SalesTransaction::completed();

        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $totalSales = $query->sum('total_amount');
        $totalTransactions = $query->count();
        $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        // Get top selling products
        $topProducts = SalesItem::select('inventory_item_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_amount) as total_sales'))
            ->whereHas('saleTransaction', function($q) use ($period) {
                $q->completed();
                switch ($period) {
                    case 'today':
                        $q->today();
                        break;
                    case 'week':
                        $q->thisWeek();
                        break;
                    case 'month':
                        $q->thisMonth();
                        break;
                    case 'year':
                        $q->whereYear('created_at', now()->year);
                        break;
                }
            })
            ->with('inventoryItem')
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'product_name' => $item->inventoryItem->name,
                    'quantity_sold' => $item->total_qty,
                    'total_sales' => $item->total_sales,
                ];
            });

        return response()->json([
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'average_transaction' => $averageTransaction,
            'top_products' => $topProducts,
        ]);
    }

    /**
     * Get daily sales data for charts.
     */
    public function getDailySales(Request $request)
    {
        $days = $request->input('days', 7);
        
        $sales = SalesTransaction::completed()
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($sales);
    }

    /**
     * Get transaction details.
     */
    public function getTransaction($id)
    {
        $transaction = SalesTransaction::with(['salesItems.inventoryItem', 'salesItems.inventoryBatch', 'user'])
            ->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * Get available products for POS.
     */
    public function getAvailableProducts()
    {
        $products = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available')
                  ->where('quantity', '>', 0)
                  ->orderBy('expiry_date', 'asc');
        }])
        ->where('status', 'active')
        ->get()
        ->filter(function($item) {
            // Only include items that have available batches
            return $item->batches->count() > 0;
        })
        ->map(function($item) {
            // Calculate total available quantity from all batches
            $totalAvailable = $item->batches->sum('quantity');
            
            // Get the first batch (FIFO - earliest expiry)
            $firstBatch = $item->batches->first();
            
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'unit' => $item->unit,
                'freshness_score' => $item->freshness_score,
                'spoilage_risk' => $item->spoilage_risk,
                'storage_notes' => $item->storage_notes,
                'available_stock' => $totalAvailable,
                'batches' => $item->batches->map(function($batch) {
                    return [
                        'id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'quantity' => $batch->quantity,
                        'price_per_unit' => $batch->price_per_unit,
                        'expiry_date' => $batch->expiry_date,
                    ];
                }),
                'price' => $firstBatch ? $firstBatch->price_per_unit : 0,
                'batch_id' => $firstBatch ? $firstBatch->id : null,
                'batch_code' => $firstBatch ? $firstBatch->batch_code : null,
            ];
        })
        ->values(); // Reset array keys

        return response()->json($products);
    }

    /**
     * Delete a sales transaction (only if not completed).
     */
    public function deleteSale($id)
    {
        $transaction = SalesTransaction::findOrFail($id);

        if ($transaction->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete completed transaction',
            ], 400);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ]);
    }
}
