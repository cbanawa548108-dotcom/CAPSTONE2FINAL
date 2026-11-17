<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryController extends Controller
{
    /**
     * Display the inventory page with data.
     */
    public function page()
    {
        // Get all inventory items with their batches (only available and quantity > 0)
        $inventoryItems = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available')->where('quantity', '>', 0)->orderBy('expiry_date', 'asc');
        }])->get();

        // Group batches by fruit name for the overview cards
        $groupedItems = [];
        foreach ($inventoryItems as $item) {
            $groupedItems[$item->name] = [];
            
            foreach ($item->batches as $batch) {
                $remainingShelfLife = $batch->remaining_shelf_life ?? 0;
                
                // Determine status based on stock and shelf life
                $status = 'Available';
                $badgeClass = 'badge-green';
                
                if ($batch->quantity <= 0) {
                    $status = 'Out of Stock';
                    $badgeClass = 'badge-gray';
                } elseif ($batch->quantity <= 50) {
                    $status = 'Low Stock';
                    $badgeClass = 'badge-amber';
                } elseif ($remainingShelfLife <= 3) {
                    $status = 'Critical';
                    $badgeClass = 'badge-red';
                }
                
                // Calculate freshness percentage (based on shelf life)
                $freshnessPercentage = 100;
                if ($batch->expiry_date) {
                    $totalDays = Carbon::parse($batch->received_date)->diffInDays($batch->expiry_date);
                    $freshnessPercentage = $totalDays > 0 ? round(($remainingShelfLife / $totalDays) * 100) : 0;
                }
                
                // Determine if it's new or old stock (less than 3 days is new)
                $daysInStock = Carbon::parse($batch->received_date)->diffInDays(now());
                $stockAge = $daysInStock <= 3 ? 'New Stock' : 'Old Stock';
                
                $groupedItems[$item->name][] = [
                    $item->id, // 0: item_id
                    $batch->batch_code, // 1: batch_code
                    number_format($batch->quantity, 2) . ' kg', // 2: quantity (2 decimals for accuracy)
                    $batch->expiry_date ? $batch->expiry_date->format('M d, Y') : 'N/A', // 3: expiry_date
                    $batch->supplier ?? 'N/A', // 4: supplier
                    '₱' . number_format($batch->price_per_unit, 2) . '/kg', // 5: price
                    $status, // 6: status
                    $badgeClass, // 7: badge_class
                    $freshnessPercentage, // 8: freshness_percentage
                    $batch->received_date ? $batch->received_date->format('M d, Y') : 'N/A', // 9: received_date
                    $remainingShelfLife, // 10: remaining_shelf_life
                    $stockAge, // 11: stock_age
                    $batch->received_date && $batch->expiry_date
                        ? (int) $batch->received_date->diffInDays($batch->expiry_date)
                        : null, // 12: shelf_life_days
                ];
            }
        }

        // Prepare products table data
        $products = [];
        foreach ($inventoryItems as $item) {
            // Only sum quantities from available batches with quantity > 0
            $totalStock = $item->batches->where('status', 'available')->where('quantity', '>', 0)->sum('quantity');
            $avgPrice = $item->batches->where('quantity', '>', 0)->avg('price_per_unit') ?? 0;
            $nearestExpiry = $item->batches->where('quantity', '>', 0)->min('expiry_date');
            
            // Calculate total shelf life
            $shelfLife = 0;
            if ($nearestExpiry) {
                $shelfLife = now()->diffInDays(Carbon::parse($nearestExpiry));
            }
            
            $products[] = [
                $item->name, // 0: name
                number_format($totalStock, 2) . ' kg', // 1: total_stock (show decimals for accuracy)
                $item->batches->where('quantity', '>', 0)->count(), // 2: batch_count (only non-empty batches)
                $nearestExpiry ? Carbon::parse($nearestExpiry)->format('M d, Y') : 'N/A', // 3: nearest_expiry
                '₱' . number_format($avgPrice, 2) . '/kg', // 4: avg_price
                $shelfLife . ' days', // 5: shelf_life
            ];
        }

        // Get recent stock-in batches and group them by supplier for the receiving activity view.
        $stockInBatches = InventoryBatch::with('inventoryItem')
            ->orderBy('received_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $stockInRecords = $stockInBatches->map(function($batch) {
                return [
                    $batch->batch_code, // 0: batch_code
                    $batch->inventoryItem->name ?? 'Unknown', // 1: product_name
                    number_format($batch->initial_quantity ?? $batch->quantity, 0) . ' kg', // 2: quantity received
                    $batch->supplier ?? 'N/A', // 3: supplier
                    $batch->received_date ? $batch->received_date->format('M d, Y') : 'N/A', // 4: date
                    '₱' . number_format($batch->quantity * $batch->price_per_unit, 2), // 5: total_value
                    'Received', // 6: status
                    $batch->received_date ? $batch->received_date->format('Y-m-d') : '', // 7: date filter
                    $batch->created_at?->timestamp ?? ($batch->received_date?->timestamp ?? 0), // 8: sort timestamp
                    $this->batchShelfLifeDetails($batch), // 9: shelf life details
                ];
            });

        $stockInGroups = $stockInBatches
            ->groupBy(fn ($batch) => trim($batch->supplier ?? '') ?: 'N/A')
            ->map(function ($batches, $supplier) {
                return [
                    'supplier' => $supplier,
                    'batch_count' => $batches->count(),
                    'total_quantity' => $batches->sum(fn ($batch) => (float) ($batch->initial_quantity ?? $batch->quantity)),
                    'latest_received' => $batches->first()->received_date?->format('M d, Y') ?? 'N/A',
                    'items' => $batches->map(function ($batch) {
                        $receivedQuantity = (float) ($batch->initial_quantity ?? $batch->quantity);

                        return [
                            'product' => $batch->inventoryItem->name ?? 'Unknown',
                            'batch_code' => $batch->batch_code,
                            'quantity' => $receivedQuantity,
                            'received_date' => $batch->received_date?->format('M d, Y') ?? 'N/A',
                            'expiry_date' => $batch->expiry_date?->format('M d, Y') ?? 'N/A',
                            'shelf_life_days' => $this->batchShelfLifeDays($batch),
                            'remaining_shelf_life' => $batch->remaining_shelf_life,
                            'unit_cost' => (float) $batch->price_per_unit,
                            'total_cost' => $receivedQuantity * (float) $batch->price_per_unit,
                        ];
                    })->values(),
                ];
            })->values();

        // Get Stock Out Records (from sales transactions)
        $stockOutRecords = \App\Models\SalesTransaction::with('salesItems.inventoryBatch.inventoryItem')
            ->where(function ($query) {
                $query->whereNull('payment_method')->orWhere('payment_method', '!=', 'adjustment');
            })
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($transaction) {
                // Get first item for display (transactions can have multiple items)
                $firstItem = $transaction->salesItems->first();
                $itemName = $firstItem && $firstItem->inventoryBatch && $firstItem->inventoryBatch->inventoryItem 
                    ? $firstItem->inventoryBatch->inventoryItem->name 
                    : 'Multiple Items';
                
                $totalQty = $transaction->salesItems->sum('quantity');
                
                // Format date
                $date = $transaction->created_at->format('M d, Y g:i A');
                if ($transaction->created_at->isToday()) {
                    $date = 'Today, ' . $transaction->created_at->format('g:i A');
                } elseif ($transaction->created_at->isYesterday()) {
                    $date = 'Yesterday, ' . $transaction->created_at->format('g:i A');
                }
                
                return [
                    $transaction->transaction_code, // 0: transaction_code
                    $itemName, // 1: product_name
                    number_format($totalQty, 0) . ' kg', // 2: quantity
                    ucfirst(str_replace('_', ' ', $transaction->type ?? 'sale')), // 3: type
                    $date, // 4: date
                    '₱' . number_format($transaction->total_amount, 2), // 5: amount
                    ucfirst($transaction->status), // 6: status
                    $transaction->created_at->format('Y-m-d'), // 7: date filter
                    $transaction->created_at->timestamp, // 8: sort timestamp
                    $transaction->salesItems
                        ->map(fn ($saleItem) => $saleItem->inventoryBatch)
                        ->filter()
                        ->map(fn ($batch) => $this->batchShelfLifeDetails($batch))
                        ->unique()
                        ->implode('; '), // 9: shelf life details
                ];
            });

        // Get Stock Adjustment Records (from sales transactions with payment_method = 'adjustment')
        $adjustmentRecords = \App\Models\SalesTransaction::with('salesItems.inventoryBatch.inventoryItem', 'user')
            ->where('payment_method', 'adjustment')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($transaction) {
                // Get first item for display
                $firstItem = $transaction->salesItems->first();
                $itemName = $firstItem && $firstItem->inventoryBatch && $firstItem->inventoryBatch->inventoryItem 
                    ? $firstItem->inventoryBatch->inventoryItem->name 
                    : 'Multiple Items';
                
                $totalQty = $transaction->salesItems->sum('quantity');
                $isAddition = (float) $transaction->total_amount >= 0;
                $qtyDisplay = ($isAddition ? '+' : '-') . number_format(abs($totalQty), 0) . ' kg';
                
                // Format date
                $date = $transaction->created_at->format('M d, Y g:i A');
                if ($transaction->created_at->isToday()) {
                    $date = 'Today, ' . $transaction->created_at->format('g:i A');
                } elseif ($transaction->created_at->isYesterday()) {
                    $date = 'Yesterday, ' . $transaction->created_at->format('g:i A');
                }
                
                $badgeClass = $isAddition ? 'badge-green' : 'badge-red';
                $userName = $transaction->user ? $transaction->user->name : 'System';
                
                return [
                    $transaction->transaction_code, // 0: transaction_code
                    $itemName, // 1: product_name
                    $qtyDisplay, // 2: quantity_display (e.g., "+12 kg" or "-5 kg")
                    $transaction->notes ?? 'Stock adjustment', // 3: reason
                    $date, // 4: date
                    $userName, // 5: user_name
                    $badgeClass, // 6: badge_class
                    $transaction->created_at->format('Y-m-d'), // 7: date filter
                    $transaction->created_at->timestamp, // 8: sort timestamp
                    $transaction->salesItems
                        ->map(fn ($saleItem) => $saleItem->inventoryBatch)
                        ->filter()
                        ->map(fn ($batch) => $this->batchShelfLifeDetails($batch))
                        ->unique()
                        ->implode('; '), // 9: shelf life details
                ];
            });

        $historyRecords = collect();
        foreach ($stockInRecords as $record) {
            $historyRecords->push([
                'type' => 'Stock In',
                'item' => $record[1],
                'reference' => $record[0],
                'quantity' => '+' . $record[2],
                'details' => $record[3] . ($record[9] ? ' · ' . $record[9] : ''),
                'date' => $record[4],
                'date_filter' => $record[7],
                'timestamp' => $record[8],
            ]);
        }
        foreach ($stockOutRecords as $record) {
            $historyRecords->push([
                'type' => $record[3],
                'item' => $record[1],
                'reference' => $record[0],
                'quantity' => '-' . $record[2],
                'details' => $record[3] . ($record[9] ? ' · ' . $record[9] : ''),
                'date' => $record[4],
                'date_filter' => $record[7],
                'timestamp' => $record[8],
            ]);
        }
        foreach ($adjustmentRecords as $record) {
            $historyRecords->push([
                'type' => 'Adjustment',
                'item' => $record[1],
                'reference' => $record[0],
                'quantity' => $record[2],
                'details' => $record[3] . ($record[9] ? ' · ' . $record[9] : ''),
                'date' => $record[4],
                'date_filter' => $record[7],
                'timestamp' => $record[8],
            ]);
        }
        $historyRecords = $historyRecords->sortByDesc('timestamp')->values();

        return view('pages.inventory', [
            'groupedItems' => $groupedItems,
            'products' => $products,
            'inventoryItems' => $inventoryItems,
            'stockInRecords' => $stockInRecords,
            'stockInGroups' => $stockInGroups,
            'stockOutRecords' => $stockOutRecords,
            'adjustmentRecords' => $adjustmentRecords,
            'historyRecords' => $historyRecords,
        ]);
    }

    private function batchShelfLifeDays(InventoryBatch $batch): ?int
    {
        if (!$batch->received_date || !$batch->expiry_date) {
            return null;
        }

        return (int) $batch->received_date->diffInDays($batch->expiry_date);
    }

    private function batchShelfLifeDetails(InventoryBatch $batch): string
    {
        $product = $batch->inventoryItem?->name ?? 'Batch';
        $shelfLife = $this->batchShelfLifeDays($batch);
        $details = $product . ': shelf life ' . ($shelfLife === null ? 'not recorded' : $shelfLife . ' days');

        if ($batch->expiry_date) {
            $details .= ', expires ' . $batch->expiry_date->format('M d, Y');
        }

        return $details;
    }

    /**
     * Download all inventory items and their batches as CSV.
     */
    public function exportCSV()
    {
        $inventoryItems = InventoryItem::with('batches')->orderBy('name')->get();
        $filename = 'inventory-' . now()->toDateString() . '.csv';

        return response()->streamDownload(function () use ($inventoryItems) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open CSV output stream.');
            }

            $safeText = static function ($value): string {
                $text = (string) ($value ?? '');

                return preg_match('/^[=+\-@]/', $text) ? "'" . $text : $text;
            };

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Product',
                'Category',
                'Unit',
                'Total Stock',
                'Reorder Level',
                'Stock Status',
                'Product Status',
                'Batch Code',
                'Batch Stock',
                'Batch Status',
                'Unit Price',
                'Supplier',
                'Received Date',
                'Expiry Date',
                'Shelf Life (Days)',
                'Remaining Shelf Life (Days)',
            ]);

            foreach ($inventoryItems as $item) {
                $stock = (float) $item->stock_quantity;
                $stockStatus = $stock <= 0
                    ? 'Out of Stock'
                    : ($stock <= (float) $item->reorder_level ? 'Low Stock' : 'Available');
                $batches = $item->batches;

                if ($batches->isEmpty()) {
                    fputcsv($output, [
                        $safeText($item->name),
                        $safeText($item->category),
                        $safeText($item->unit),
                        $item->stock_quantity,
                        $item->reorder_level,
                        $stockStatus,
                        $safeText($item->status),
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ]);
                    continue;
                }

                foreach ($batches as $batch) {
                    fputcsv($output, [
                        $safeText($item->name),
                        $safeText($item->category),
                        $safeText($item->unit),
                        $item->stock_quantity,
                        $item->reorder_level,
                        $stockStatus,
                        $safeText($item->status),
                        $safeText($batch->batch_code),
                        $batch->quantity,
                        $safeText($batch->status),
                        $batch->price_per_unit,
                        $safeText($batch->supplier),
                        $batch->received_date?->toDateString() ?? '',
                        $batch->expiry_date?->toDateString() ?? '',
                        $this->batchShelfLifeDays($batch) ?? '',
                        $batch->remaining_shelf_life ?? '',
                    ]);
                }
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
    
    /**
     * Get all inventory items with batches (API).
     */
    public function index()
    {
        // Get all inventory items with their batches
        $inventoryItems = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available')->orderBy('expiry_date', 'asc');
        }])->get();

        // Group batches by fruit name for the overview cards
        $groupedItems = [];
        foreach ($inventoryItems as $item) {
            $groupedItems[$item->name] = [];
            
            foreach ($item->batches as $batch) {
                $remainingShelfLife = $batch->remaining_shelf_life ?? 0;
                
                // Determine status based on stock and shelf life
                $status = 'Available';
                $badgeClass = 'badge-green';
                
                if ($batch->quantity <= 0) {
                    $status = 'Out of Stock';
                    $badgeClass = 'badge-gray';
                } elseif ($batch->quantity <= 50) {
                    $status = 'Low Stock';
                    $badgeClass = 'badge-amber';
                } elseif ($remainingShelfLife <= 3) {
                    $status = 'Critical';
                    $badgeClass = 'badge-red';
                }
                
                // Calculate freshness percentage (based on shelf life)
                $freshnessPercentage = 100;
                if ($batch->expiry_date) {
                    $totalDays = Carbon::parse($batch->received_date)->diffInDays($batch->expiry_date);
                    $freshnessPercentage = $totalDays > 0 ? round(($remainingShelfLife / $totalDays) * 100) : 0;
                }
                
                // Determine if it's new or old stock (less than 3 days is new)
                $daysInStock = Carbon::parse($batch->received_date)->diffInDays(now());
                $stockAge = $daysInStock <= 3 ? 'New Stock' : 'Old Stock';
                
                $groupedItems[$item->name][] = [
                    $item->id, // 0: item_id
                    $batch->batch_code, // 1: batch_code
                    number_format($batch->quantity, 2) . ' kg', // 2: quantity (2 decimals for accuracy)
                    $batch->expiry_date ? $batch->expiry_date->format('M d, Y') : 'N/A', // 3: expiry_date
                    $batch->supplier ?? 'N/A', // 4: supplier
                    '₱' . number_format($batch->price_per_unit, 2) . '/kg', // 5: price
                    $status, // 6: status
                    $badgeClass, // 7: badge_class
                    $freshnessPercentage, // 8: freshness_percentage
                    $batch->received_date ? $batch->received_date->format('M d, Y') : 'N/A', // 9: received_date
                    $remainingShelfLife, // 10: remaining_shelf_life
                    $stockAge, // 11: stock_age
                    $this->batchShelfLifeDays($batch), // 12: shelf_life_days
                ];
            }
        }

        // Prepare products table data
        $products = [];
        foreach ($inventoryItems as $item) {
            // Only sum quantities from available batches with quantity > 0
            $totalStock = $item->batches->where('status', 'available')->where('quantity', '>', 0)->sum('quantity');
            $avgPrice = $item->batches->where('quantity', '>', 0)->avg('price_per_unit') ?? 0;
            $nearestExpiry = $item->batches->where('quantity', '>', 0)->min('expiry_date');
            
            // Calculate total shelf life
            $shelfLife = 0;
            if ($nearestExpiry) {
                $shelfLife = now()->diffInDays(Carbon::parse($nearestExpiry));
            }
            
            $products[] = [
                $item->name, // 0: name
                number_format($totalStock, 2) . ' kg', // 1: total_stock (show decimals for accuracy)
                $item->batches->where('quantity', '>', 0)->count(), // 2: batch_count (only non-empty batches)
                $nearestExpiry ? Carbon::parse($nearestExpiry)->format('M d, Y') : 'N/A', // 3: nearest_expiry
                '₱' . number_format($avgPrice, 2) . '/kg', // 4: avg_price
                $shelfLife . ' days', // 5: shelf_life
            ];
        }

        return view('pages.inventory', [
            'groupedItems' => $groupedItems,
            'products' => $products,
        ]);
    }

    /**
     * Get inventory statistics.
     */
    public function getStats()
    {
        $totalItems = InventoryItem::count();
        $totalBatches = InventoryBatch::where('status', 'available')->count();
        $totalValue = InventoryBatch::where('status', 'available')
            ->get()
            ->sum(function($batch) {
                return $batch->quantity * $batch->price_per_unit;
            });
        
        $lowStockItems = InventoryItem::lowStock()->count();
        $expiringItems = InventoryBatch::expiringSoon(7)->count();

        return response()->json([
            'total_items' => $totalItems,
            'total_batches' => $totalBatches,
            'total_value' => $totalValue,
            'low_stock_items' => $lowStockItems,
            'expiring_items' => $expiringItems,
        ]);
    }

    /**
     * Store a new product.
     */
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'unit' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // Get shelf life based on product name
        $shelfLifeMap = [
            'Mango' => 14,
            'Durian' => 7,
            'Pomelo' => 21,
            'Mangosteen' => 14,
            'Lanzones' => 10,
            'Banana' => 7,
            'Pineapple' => 14,
        ];

        $item = InventoryItem::create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'storage_notes' => $validated['description'] ?? null,
            'stock_quantity' => 0,
            'price_per_unit' => 0,
            'reorder_level' => 50,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully',
            'data' => $item,
        ], 201);
    }

    /**
     * Store a new stock in transaction.
     */
    public function stockIn(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:inventory_items,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price_per_unit' => 'required|numeric|min:0',
            'items.*.batch_code' => 'required|string',
            'items.*.expiry_date' => 'required|date',
            'items.*.supplier' => 'required|string',
            'supplier' => 'nullable|string',
            'received_date' => 'required|date',
            'reference_number' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                // Find or create inventory item
                if (!empty($itemData['product_id'])) {
                    $inventoryItem = InventoryItem::find($itemData['product_id']);
                } else {
                    $inventoryItem = InventoryItem::firstOrCreate(
                        ['name' => $itemData['product_name']],
                        [
                            'category' => 'Tropical Fruit',
                            'unit' => 'kg',
                            'price_per_unit' => $itemData['price_per_unit'],
                            'stock_quantity' => 0,
                            'reorder_level' => 50,
                            'status' => 'active',
                        ]
                    );
                }

                // Create batch
                $batch = InventoryBatch::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'batch_code' => $itemData['batch_code'],
                    'quantity' => $itemData['quantity'],
                    'initial_quantity' => $itemData['quantity'],
                    'price_per_unit' => $itemData['price_per_unit'],
                    'received_date' => $validated['received_date'],
                    'expiry_date' => $itemData['expiry_date'],
                    'supplier' => $itemData['supplier'],
                    'status' => 'available',
                ]);

                // Keep the product total derived from its available batches.
                $inventoryItem->syncStockQuantityFromBatches();
                $inventoryItem->update(['price_per_unit' => $itemData['price_per_unit']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully',
                'data' => [
                    'reference_number' => $validated['reference_number'] ?? 'N/A',
                    'items_count' => count($validated['items'])
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stock out transaction.
     */
    public function stockOut(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:inventory_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'type' => 'required|string|in:sale,spoilage,wastage,return,transfer,sample,other',
            'notes' => 'nullable|string',
            'reference' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $transactionItems = [];
            
            // First, validate all items and calculate total
            foreach ($validated['items'] as $itemData) {
                $batch = InventoryBatch::findOrFail($itemData['batch_id']);
                
                if ($batch->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for batch {$batch->batch_code}");
                }
                
                $itemTotal = $itemData['quantity'] * $batch->price_per_unit;
                $totalAmount += $itemTotal;
                
                $transactionItems[] = [
                    'batch' => $batch,
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $batch->price_per_unit,
                    'total' => $itemTotal,
                ];
            }
            
            // Create sales transaction record
            $transaction = \App\Models\SalesTransaction::create([
                'user_id' => auth()->id(),
                'transaction_code' => $validated['reference'] ?? 'SO-' . now()->format('ymd') . '-' . rand(100, 999),
                'type' => $validated['type'],
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['type'] === 'sale' ? 'cash' : 'n/a',
                'status' => 'completed',
                'notes' => $validated['notes'] ?? 'Stock out transaction',
            ]);
            
            // Process each item
            $affectedItemIds = [];
            foreach ($transactionItems as $itemData) {
                $batch = $itemData['batch'];
                
                // Create sales item record
                \App\Models\SalesItem::create([
                    'sale_transaction_id' => $transaction->id,
                    'inventory_item_id' => $batch->inventory_item_id,
                    'inventory_batch_id' => $batch->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['price_per_unit'],
                    'total_amount' => $itemData['total'],
                ]);
                
                // Reduce batch stock.
                $batch->decrement('quantity', $itemData['quantity']);

                // Update batch status if quantity is 0
                if ($batch->quantity <= 0) {
                    $batch->update(['status' => 'depleted']);
                }
                $affectedItemIds[$batch->inventory_item_id] = true;
            }

            foreach (array_keys($affectedItemIds) as $itemId) {
                InventoryItem::findOrFail($itemId)->syncStockQuantityFromBatches();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock removed successfully',
                'transaction_code' => $transaction->transaction_code,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stock adjustment.
     */
    public function stockAdjustment(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:inventory_batches,id',
            'items.*.quantity' => 'required|numeric',
            'type' => 'required|string|in:add,subtract',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'reference' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $transactionItems = [];
            
            $affectedItemIds = [];
            foreach ($validated['items'] as $itemData) {
                $batch = InventoryBatch::findOrFail($itemData['batch_id']);
                $adjustmentQty = abs($itemData['quantity']);

                // Calculate value for tracking
                $itemTotal = $adjustmentQty * $batch->price_per_unit;
                $totalAmount += $itemTotal;
                
                $transactionItems[] = [
                    'batch' => $batch,
                    'quantity' => $adjustmentQty,
                    'type' => $validated['type'],
                    'price_per_unit' => $batch->price_per_unit,
                    'total' => $itemTotal,
                ];
                
                if ($validated['type'] === 'add') {
                    $batch->increment('quantity', $adjustmentQty);
                    $batch->update(['status' => 'available']);
                } else {
                    if ($batch->quantity < $adjustmentQty) {
                        throw new \Exception("Cannot subtract more than available stock for batch {$batch->batch_code}");
                    }
                    $batch->decrement('quantity', $adjustmentQty);

                    if ($batch->quantity <= 0) {
                        $batch->update(['status' => 'depleted']);
                    }
                }
                $affectedItemIds[$batch->inventory_item_id] = true;
            }

            foreach (array_keys($affectedItemIds) as $itemId) {
                InventoryItem::findOrFail($itemId)->syncStockQuantityFromBatches();
            }
            
            // Create adjustment transaction record (using sales_transactions table)
            $transaction = \App\Models\SalesTransaction::create([
                'user_id' => auth()->id(),
                'transaction_code' => $validated['reference'] ?? 'ADJ-' . now()->format('ymd') . '-' . rand(100, 999),
                'type' => 'adjustment',
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $validated['type'] === 'add' ? $totalAmount : -$totalAmount,
                'payment_method' => 'adjustment',
                'status' => 'completed',
                'notes' => $validated['reason'] . (!empty($validated['notes']) ? ' - ' . $validated['notes'] : ''),
            ]);
            
            // Create sales item records for tracking
            foreach ($transactionItems as $itemData) {
                \App\Models\SalesItem::create([
                    'sale_transaction_id' => $transaction->id,
                    'inventory_item_id' => $itemData['batch']->inventory_item_id,
                    'inventory_batch_id' => $itemData['batch']->id,
                    'quantity' => $itemData['type'] === 'add' ? $itemData['quantity'] : -$itemData['quantity'],
                    'unit_price' => $itemData['price_per_unit'],
                    'total_amount' => $itemData['type'] === 'add' ? $itemData['total'] : -$itemData['total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'transaction_code' => $transaction->transaction_code,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get low stock items.
     */
    public function getLowStock()
    {
        $lowStockItems = InventoryItem::with('batches')
            ->lowStock()
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'current_stock' => $item->stock_quantity,
                    'reorder_level' => $item->reorder_level,
                    'status' => $item->stock_status,
                ];
            });

        return response()->json($lowStockItems);
    }

    /**
     * Get expiring items.
     */
    public function getExpiringItems()
    {
        $expiringBatches = InventoryBatch::with('inventoryItem')
            ->expiringSoon(7)
            ->get()
            ->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'product_name' => $batch->inventoryItem->name,
                    'batch_code' => $batch->batch_code,
                    'quantity' => $batch->quantity,
                    'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                    'shelf_life_days' => $this->batchShelfLifeDays($batch),
                    'days_remaining' => $batch->remaining_shelf_life,
                ];
            });

        return response()->json($expiringBatches);
    }
}
