<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterInventory;
use App\Models\MasterItem;
use App\Models\MasterBranch;
use App\Models\MasterItemBillOfMaterials;
use App\Models\MasterItemStock;
use App\Models\MasterItemRawMaterial;
use App\Models\ProductionOrder;
use App\Models\RawMaterialIn;
use App\Models\RawMaterialOut;
use App\Models\FinishedGoodsIn;
use App\Models\FinishedGoodsOut;
use App\Models\BufferStockConfig;
use App\Models\StockAdjustment;
use App\Models\TransactionPurchase;
use App\Models\TransactionSalesDetails;
use App\Models\TransactionSales;
use App\Services\BufferStockCalculationService;
use App\Services\InventoryAnalysisService;
use App\Services\ProductionCodeService;
use App\Services\DynamicForecastingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class InventoryDashboardController extends Controller
{
    public function index()
    {
        // Total inventori dan item
        $totalInventories = MasterInventory::count();
        $totalItems       = MasterItem::count();
        $totalStock       = MasterItemStock::sum('stock');
        $lowStockItems    = MasterItemStock::where('stock', '<', 10)->where('stock', '>', 0)->count();
        $emptyStockItems  = MasterItemStock::where('stock', 0)->count();

        // Stok Masuk bulan ini (dari pembelian)
        $stockMasukBulanIni = TransactionPurchase::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
        $nilaiMasukBulanIni = TransactionPurchase::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('total_amount');

        // Stok Keluar bulan ini (dari finished_goods_out)
        $stockKeluarBulanIni = FinishedGoodsOut::whereMonth('out_date', now()->month)
            ->whereYear('out_date', now()->year)
            ->sum('qty_out');
        $nilaiKeluarBulanIni = FinishedGoodsOut::whereMonth('out_date', now()->month)
            ->whereYear('out_date', now()->year)
            ->sum('total_cost');

        // Data trend 6 bulan terakhir untuk grafik (dari finished_goods_out)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyData[] = [
                'month'   => $date->format('M Y'),
                'masuk'   => TransactionPurchase::whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->count(),
                'nilai_masuk' => TransactionPurchase::whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->sum('total_amount'),
                'keluar'  => FinishedGoodsOut::whereMonth('out_date', $date->month)
                    ->whereYear('out_date', $date->year)
                    ->sum('qty_out'),
                'nilai_keluar' => FinishedGoodsOut::whereMonth('out_date', $date->month)
                    ->whereYear('out_date', $date->year)
                    ->sum('total_cost'),
            ];
        }

        // Transaksi pembelian terbaru (stok masuk)
        $recentMasuk = TransactionPurchase::orderBy('date', 'desc')
            ->limit(5)
            ->get();

        // Transaksi penjualan terbaru per item (stok keluar dari finished_goods_out)
        $rawRecentKeluar = FinishedGoodsOut::with('item')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentKeluar = $rawRecentKeluar->map(function ($row) {
            $obj = new \stdClass();
            
            $itemObj = new \stdClass();
            $itemObj->name_item = $row->item?->name_item ?? '-';
            $obj->masterItem = $itemObj;
            
            $txObj = new \stdClass();
            $txObj->date = $row->out_date;
            $obj->transactionSales = $txObj;
            
            $obj->qty = $row->qty_out;
            $obj->total_amount = $row->total_cost;
            return $obj;
        });

        // Daftar item dengan stok saat ini
        $itemStocks = MasterItemStock::with(['item', 'inventory'])
            ->orderBy('stock', 'asc')
            ->get();

        // Ambil barang jadi di bawah buffer stock (Modul 4)
        $finishedGoodsBelowBuffer = MasterItemStock::with(['item', 'inventory'])
            ->whereRaw('stock < buffer_stock')
            ->where('buffer_stock', '>', 0)
            ->get();

        // Ambil bahan baku di bawah buffer stock (Modul 4)
        $rawMaterialsBelowBuffer = MasterItemRawMaterial::whereRaw('current_stock < buffer_stock')
            ->where('buffer_stock', '>', 0)
            ->get();

        return view('admin_inventory.dashboard', compact(
            'totalInventories', 'totalItems', 'totalStock', 'lowStockItems', 'emptyStockItems',
            'stockMasukBulanIni', 'nilaiMasukBulanIni',
            'stockKeluarBulanIni', 'nilaiKeluarBulanIni',
            'monthlyData', 'recentMasuk', 'recentKeluar', 'itemStocks',
            'finishedGoodsBelowBuffer', 'rawMaterialsBelowBuffer'
        ));
    }

    public function rawMaterials(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search', '');

        // Query builder untuk raw materials dengan item dan inventory
        $query = MasterItemStock::with(['item.category', 'inventory']);

        // Filter berdasarkan search
        if ($search) {
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name_item', 'like', "%{$search}%")
                    ->orWhere('code_item', 'like', "%{$search}%");
            })->orWhereHas('inventory', function ($q) use ($search) {
                $q->where('name_inventory', 'like', "%{$search}%");
            });
        }

        // Get paginated data
        $itemStocks = $query->paginate($perPage);

        // Format data untuk view
        $rawMaterials = $itemStocks->through(function ($itemStock) {
            $stock = $itemStock->stock;
            $bufferStock = (int) $itemStock->buffer_stock;
            $stockDifference = $stock - $bufferStock;
            $needsOrder = $stockDifference < 0;

            return [
                'item_stock_id' => $itemStock->item_stock_id,
                'name_item' => $itemStock->item?->name_item ?? '-',
                'code_item' => $itemStock->item?->code_item ?? '',
                'inventory' => $itemStock->inventory?->name_inventory ?? '-',
                'category' => $itemStock->item?->category?->name_category ?? '-',
                'stock' => $stock,
                'buffer_stock' => $bufferStock,
                'stock_difference' => abs($stockDifference),
                'needs_order' => $needsOrder
            ];
        });

        // Hitung summary data
        $allItemStocks = MasterItemStock::all();
        $summary = [
            'total' => $allItemStocks->count(),
            'needs_order' => $allItemStocks->filter(function ($item) {
                return ($item->stock - (int) $item->buffer_stock) < 0;
            })->count(),
            'sufficient' => $allItemStocks->filter(function ($item) {
                return ($item->stock - (int) $item->buffer_stock) >= 0;
            })->count(),
        ];

        return view('admin_inventory.finished_goods', compact(
            'rawMaterials',
            'summary',
            'perPage',
            'search'
        ));
    }

    public function getRawMaterialDetail($itemStockId)
    {
        $itemStock = MasterItemStock::with(['item.category', 'inventory'])->find($itemStockId);

        if (!$itemStock) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        $stock = $itemStock->stock;
        $bufferStock = (int) $itemStock->buffer_stock;
        $stockDifference = $stock - $bufferStock;
        $needsOrder = $stockDifference < 0;

        return response()->json([
            'item_stock_id' => $itemStock->item_stock_id,
            'name_item' => $itemStock->item->name_item ?? '-',
            'code_item' => $itemStock->item->code_item ?? '',
            'inventory' => $itemStock->inventory->name_inventory ?? '-',
            'category' => $itemStock->item->category->name_category ?? '-',
            'stock' => $stock,
            'buffer_stock' => $bufferStock,
            'stock_difference' => abs($stockDifference),
            'needs_order' => $needsOrder
        ]);
    }

    public function finishedGoodsShow($itemStockId)
    {
        $itemStock = MasterItemStock::with(['item.category', 'inventory'])->find($itemStockId);

        if (!$itemStock) {
            abort(404, 'Data tidak ditemukan');
        }

        $stock = $itemStock->stock;
        $bufferStock = (int) $itemStock->buffer_stock;
        $stockDifference = abs($stock - $bufferStock);
        $needsOrder = ($stock - $bufferStock) < 0;

        // Get BOM
        $bomList = \App\Models\MasterItemBillOfMaterials::with('rawMaterial')
            ->where('item_id', $itemStock->item_id)
            ->get();

        // Get Forecasting Data
        $forecastSummary = null;
        $forecastDetails = collect();
        if ($itemStock->item && $itemStock->item->code_item) {
            $forecastSummary = DB::table('arima_forecast_summaries')
                ->where('produk', $itemStock->item->code_item)
                ->first();
                
            $forecastDetails = DB::table('arima_forecast_details')
                ->where('produk', $itemStock->item->code_item)
                ->where('data_type', 'forecast')
                ->orderBy('date', 'asc')
                ->get();
        }

        return view('admin_inventory.finished_goods_show', compact(
            'itemStock',
            'bufferStock',
            'stockDifference',
            'needsOrder',
            'bomList',
            'forecastSummary',
            'forecastDetails'
        ));
    }

    public function finishedGoodsEdit($itemStockId)
    {
        $itemStock = MasterItemStock::with(['item.category', 'inventory'])->find($itemStockId);

        if (!$itemStock) {
            abort(404, 'Data tidak ditemukan');
        }

        $bufferStock = (int) $itemStock->buffer_stock;

        return view('admin_inventory.finished_goods_edit', compact(
            'itemStock',
            'bufferStock'
        ));
    }

    public function updateRawMaterial(Request $request, $itemStockId)
    {
        $request->validate([
            'stock' => 'required|integer|min:0|max:9999999'
        ]);

        $itemStock = MasterItemStock::find($itemStockId);

        if (!$itemStock) {
            return response()->json(['error' => 'Data tidak ditemukan', 'success' => false], 404);
        }

        $itemStock->update([
            'stock' => $request->stock
        ]);

        if (!$request->expectsJson()) {
            return redirect()
                ->route('admin.inventory.finished-goods.show', $itemStockId)
                ->with('success', 'Stok berhasil diperbarui');
        }

        return response()->json([
            'success' => true,
            'message' => 'Stok berhasil diperbarui'
        ]);
    }

    public function destroyFinishedGoods($itemStockId)
    {
        $itemStock = MasterItemStock::with('item')->find($itemStockId);

        if (!$itemStock) {
            return response()->json([
                'success' => false,
                'message' => 'Data produk jadi tidak ditemukan.'
            ], 404);
        }

        $itemName = $itemStock->item->name_item ?? 'Produk';
        $itemStock->delete();

        return response()->json([
            'success' => true,
            'message' => $itemName . ' berhasil dihapus.'
        ]);
    }

    /**
     * GET: /admin/inventory/finished-goods/create/form-data
     * Get items and inventories for create form
     */
    public function getFinishedGoodsFormData()
    {
        try {
            $items = MasterItem::select('item_id', 'name_item', 'code_item')
                ->where('status_item', 'active')
                ->orderBy('name_item')
                ->get();

            $inventories = MasterInventory::select('inventory_id', 'name_inventory')
                ->where('status_inventory', 'active')
                ->orderBy('name_inventory')
                ->get();

            return response()->json([
                'success' => true,
                'items' => $items,
                'inventories' => $inventories
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting form data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data form'
            ], 500);
        }
    }

    /**
     * POST: /admin/inventory/finished-goods
     * Store new finished goods
     */
    public function storeFinishedGoods(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_id' => 'required|integer|exists:master_items,item_id',
                'inventory_id' => 'required|integer|exists:master_inventories,inventory_id',
                'stock' => 'required|integer|min:0|max:9999999',
                'buffer_stock' => 'required|integer|min:0|max:9999999'
            ]);

            Log::info('Creating finished goods', [
                'item_id' => $validated['item_id'],
                'inventory_id' => $validated['inventory_id'],
                'stock' => $validated['stock'],
                'buffer_stock' => $validated['buffer_stock'],
            ]);

            // Check if already exists
            $existing = MasterItemStock::where('item_id', $validated['item_id'])
                ->where('inventory_id', $validated['inventory_id'])
                ->first();

            if ($existing) {
                Log::warning('Finished goods already exists', [
                    'item_id' => $validated['item_id'],
                    'inventory_id' => $validated['inventory_id'],
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kombinasi item dan inventori sudah ada',
                        'errors' => ['item_id' => ['Kombinasi ini sudah terdaftar']]
                    ], 422);
                }

                return redirect()
                    ->back()
                    ->with('error', 'Kombinasi item dan inventori sudah ada');
            }

            $itemStock = MasterItemStock::create([
                'item_id' => $validated['item_id'],
                'inventory_id' => $validated['inventory_id'],
                'stock' => $validated['stock'],
                'buffer_stock' => $validated['buffer_stock']
            ]);

            Log::info('Finished goods created successfully', [
                'item_stock_id' => $itemStock->item_stock_id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Produk jadi berhasil ditambahkan',
                    'data' => $itemStock
                ], 201);
            }

            return redirect()
                ->route('admin.inventory.finished-goods')
                ->with('success', 'Produk jadi berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in storeFinishedGoods', [
                'errors' => $e->errors(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error storing finished goods: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan data produk jadi: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Gagal menyimpan data produk jadi');
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/raw-materials
     * Display raw materials with buffer stock calculations from CSV analysis
     */
    public function bufferStockRawMaterials(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search', '');

        try {
            // Query database directly for raw materials
            $query = MasterItemRawMaterial::query();

            $filter = $request->get('filter', 'all');

            // Apply search filter
            if ($search !== '') {
                $keyword = $search;
                $query->where(function ($q) use ($keyword) {
                    $q->where('item_raw_id', 'like', "%{$keyword}%")
                        ->orWhere('material_name', 'like', "%{$keyword}%")
                        ->orWhere('unit', 'like', "%{$keyword}%")
                        ->orWhere('supplier_name', 'like', "%{$keyword}%");
                });
            }

            // Apply status filter
            if ($filter === 'low') {
                $query->whereColumn('current_stock', '<=', 'reorder_point');
            } elseif ($filter === 'out_of_stock') {
                $query->where('current_stock', '<=', 0);
            } elseif ($filter === 'below_buffer') {
                $query->whereColumn('current_stock', '<', 'buffer_stock');
            }

            // Get paginated data
            $materialData = $query->latest('created_at')->paginate($perPage);

            // Calculate summary statistics
            $allMaterials = MasterItemRawMaterial::all();
            $summary = [
                'total_materials' => $allMaterials->count(),
                'total_inventory_value' => $allMaterials->sum(fn($m) => ($m->current_stock ?? 0) * ($m->purchase_price ?? 0)),
                'avg_buffer_stock' => round($allMaterials->avg('buffer_stock') ?? 0, 2),
                'avg_lead_time' => round($allMaterials->avg('lead_time_days') ?? 0, 2),
                'empty_stock' => $allMaterials->filter(fn($m) => ($m->current_stock ?? 0) <= 0)->count(),
                'items_below_buffer' => $allMaterials->filter(fn($m) => ($m->current_stock ?? 0) < ($m->buffer_stock ?? 0))->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Buffer Stock Raw Materials Error: ' . $e->getMessage());
            return back()->with('error', 'Error membaca data bahan baku: ' . $e->getMessage());
        }

        return view('admin_inventory.buffer_stock_raw_materials', compact(
            'materialData',
            'summary',
            'perPage',
            'search',
            'filter'
        ));
    }

    /**
     * GET: /admin/inventory/forecasting/demand
     * Display demand forecasting for finished goods
     */
    public function demandForecasting(Request $request)
    {
        $summaryTable    = 'arima_forecast_summaries';
        $masterItemTable = (new MasterItem())->getTable();

        // ── 1. SEMUA PRODUK dari master_items → untuk Select2 dropdown ──────
        $masterItems = DB::table($masterItemTable . ' as mi')
            ->select([
                'mi.item_id',
                'mi.code_item',
                'mi.name_item',
                'mi.costprice_item',
                'mi.sellingprice_item',
                'mi.current_inventory',
                'mi.status_item',
            ])
            ->where('mi.status_item', 'active')
            ->orderBy('mi.created_at', 'desc')
            ->orderBy('mi.name_item', 'asc')
            ->get()
            ->map(fn($row) => [
                'item_id'          => $row->item_id,
                'code_item'        => $row->code_item,
                'name_item'        => $row->name_item,
                'costprice_item'   => $row->costprice_item ?? 0,
                'sellingprice_item'=> $row->sellingprice_item ?? 0,
                'current_inventory'=> $row->current_inventory ?? 0,
            ]);

        // ── 2. ARIMA forecast summaries → lookup map (by code_item) ──────────
        $forecastData = collect();

        if (DB::getSchemaBuilder()->hasTable($summaryTable)) {
            $rawForecastData = DB::table($summaryTable . ' as afs')
                ->leftJoin($masterItemTable . ' as mi', 'mi.code_item', '=', 'afs.produk')
                ->select([
                    'afs.produk',
                    'afs.arima_order',
                    'afs.mae',
                    'afs.rmse',
                    'afs.mape_percentage',
                    'afs.stationary',
                    'afs.adf_p_value',
                    'afs.kategori_mae',
                    'afs.updated_at',
                    'mi.item_id',
                    'mi.code_item',
                    'mi.name_item',
                ])
                ->orderByRaw("CASE afs.kategori_mae WHEN 'rendah' THEN 1 WHEN 'menengah' THEN 2 WHEN 'tinggi' THEN 3 ELSE 4 END")
                ->orderBy('afs.mae', 'asc')
                ->get();

            $forecastData = $rawForecastData->map(fn($row) => [
                'produk'       => $row->produk,
                'code_item'    => $row->code_item ?: $row->produk,
                'name_item'    => $row->name_item ?: $row->produk,
                'arima_order'  => $row->arima_order,
                'mae'          => (float) $row->mae,
                'rmse'         => (float) $row->rmse,
                'mape_percentage' => (float) $row->mape_percentage,
                'kategori_mae' => $row->kategori_mae,
            ]);
        }

        // Buat set code_item yang punya ARIMA data → untuk badge di dropdown
        $arimaProductCodes = $forecastData->pluck('produk')->flip()->toArray();

        return view('admin_inventory.demand_forecasting', compact(
            'masterItems',
            'forecastData',
            'arimaProductCodes'
        ));
    }

    public function runDynamicForecast(DynamicForecastingService $service)
    {
        $result = $service->runDynamicForecast();
        
        if ($result['success']) {
            return redirect()->route('admin.inventory.forecasting.demand')->with('success', $result['message']);
        } else {
            return redirect()->route('admin.inventory.forecasting.demand')->with('error', $result['message']);
        }
    }

    /**
     * GET: /admin/inventory/forecasting/demand-detail/{produk}
     *
     * Actual sales  → HANYA dari transaction_sales_details (real-time, NO CSV fallback)
     * Predicted     → dari arima_forecast_details (hasil ARIMA)
     * Training      → dari arima_forecast_details (data_type=training)
     * Buffer/ROP    → dihitung dari actual sales transaksi SAJA
     */
    public function getDemandForecastDetail(Request $request, $produk)
    {
        $summaryTable = 'arima_forecast_summaries';

        // ── 1. ARIMA Summary ─────────────────────────────────────────────
        $summary = DB::table($summaryTable)->where('produk', $produk)->first();

        if (!$summary) {
            return response()->json([
                'success' => false,
                'message' => "Produk '{$produk}' tidak ditemukan di ARIMA forecast data.",
            ], 404);
        }

        // ── 2. Master Item Info ───────────────────────────────────────────
        $masterItem = DB::table((new MasterItem())->getTable())
            ->where('code_item', $produk)
            ->first();

        $itemId = $masterItem?->item_id;

        // ── 3. ACTUAL DEMAND — dari finished_goods_out (stok keluar = permintaan nyata) ─
        $actualFromTransactions = [];

        if ($itemId) {
            $txRows = DB::table('finished_goods_out')
                ->where('item_id', $itemId)
                ->whereNotNull('out_date')
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('DATE(out_date) as sale_date'),
                    DB::raw('SUM(qty_out) as total_qty')
                )
                ->groupBy(DB::raw('DATE(out_date)'))
                ->orderBy('sale_date', 'asc')
                ->get();

            foreach ($txRows as $row) {
                $actualFromTransactions[$row->sale_date] = (float) $row->total_qty;
            }
        }

        $hasTransactionData = !empty($actualFromTransactions);

        // ── 4. ARIMA DETAIL DATA dari DB ─────────────────────────────────
        $detailData    = DB::table('arima_forecast_details')
            ->where('produk', $produk)
            ->orderBy('date', 'asc')
            ->get();

        $trainingRows  = $detailData->where('data_type', 'training')->values();
        $predictedRows = $detailData->where('data_type', 'actual')->values();   // test period
        $forecastRows  = $detailData->where('data_type', 'forecast')->values();

        // ── 5. BUILD CHART DATA ───────────────────────────────────────────

        // Training: ambil nilai aktual dari transaksi DB (jika ada), fallback ke actual_sales dari ARIMA CSV
        $trainingChart = $trainingRows->map(fn($d) => [
            'date'      => substr($d->date, 0, 10),
            'value'     => (float) ($actualFromTransactions[substr($d->date, 0, 10)] ?? (float)($d->actual_sales ?? 0.0)),
            'predicted' => (float) $d->predicted_sales,
        ])->toArray();

        // Actual (test period): gunakan transaksi DB jika ada, fallback ke actual_sales ARIMA CSV
        $actualChart = [];
        $tableData   = [];

        foreach ($predictedRows as $d) {
            $dateStr  = substr($d->date, 0, 10);
            // Predicted: selalu tampilkan dari ARIMA (naik-turun mengikuti pola)
            $predQty  = (float) $d->predicted_sales;

            $hasRealTx = isset($actualFromTransactions[$dateStr]);
            // Actual: dari transaksi DB jika ada, fallback ke actual_sales CSV agar tidak flat 0
            $actualQty = $hasRealTx
                ? $actualFromTransactions[$dateStr]
                : (float) ($d->actual_sales ?? 0.0);
            $err    = round($actualQty - $predQty, 4);
            $absErr = round(abs($err), 4);

            $actualChart[] = [
                'date'      => $dateStr,
                'actual'    => $actualQty,
                'predicted' => $predQty,
                'error'     => $err,
            ];

            $tableData[] = [
                'date'            => $dateStr,
                'actual_sales'    => $actualQty,
                'predicted_sales' => $predQty,
                'error'           => $err,
                'absolute_error'  => $absErr,
                'source'          => $hasRealTx ? 'transaction' : 'arima_csv',
            ];
        }

        // Jika ada transaksi di LUAR periode ARIMA (tanggal baru), tampilkan juga
        $arimaDatesCovered = collect($detailData)->pluck('date')
            ->map(fn($d) => substr($d, 0, 10))->flip()->toArray();

        foreach ($actualFromTransactions as $txDate => $txQty) {
            if (!isset($arimaDatesCovered[$txDate])) {
                // Tanggal transaksi di luar periode prediksi ARIMA → tampil sebagai actual saja
                $actualChart[] = [
                    'date'      => $txDate,
                    'actual'    => $txQty,
                    'predicted' => null,
                    'error'     => null,
                ];
                $tableData[] = [
                    'date'            => $txDate,
                    'actual_sales'    => $txQty,
                    'predicted_sales' => null,
                    'error'           => null,
                    'absolute_error'  => null,
                    'source'          => 'transaction',
                ];
            }
        }

        // Sort chart by date
        usort($actualChart, fn($a, $b) => strcmp($a['date'], $b['date']));
        usort($tableData,   fn($a, $b) => strcmp($a['date'], $b['date']));

        // Future forecast dari ARIMA
        $forecastChart = $forecastRows->map(fn($d) => [
            'date'      => substr($d->date, 0, 10),
            'predicted' => $hasTransactionData ? (float) $d->predicted_sales : 0.0,
        ])->toArray();

        // ── 6. BUFFER STOCK — HANYA dari transaksi nyata ─────────────────
        $dynamicRec = null;

        if ($hasTransactionData) {
            $allActualValues = array_values(array_filter(
                $actualFromTransactions,
                fn($qty) => $qty > 0
            ));

            if (!empty($allActualValues)) {
                $n           = count($allActualValues);
                $avgSales    = array_sum($allActualValues) / $n;
                $maxSales    = max($allActualValues);
                $avgLt       = 5.4;   // rata-rata lead time (hari)
                $maxLt       = 7;     // max lead time (hari)
                $zScore      = 1.645;  // Z-score 95%
                $variance    = array_sum(array_map(fn($v) => pow($v - $avgSales, 2), $allActualValues)) / $n;
                $stdDev      = sqrt($variance);

                $safetyStock = $zScore * $stdDev * sqrt($avgLt);
                $bufferStock = $safetyStock;
                $rop         = ($avgSales * $avgLt) + $safetyStock;

                $dynamicRec = [
                    'avg_daily_sales' => round($avgSales, 4),
                    'max_daily_sales' => round($maxSales, 4),
                    'std_dev'         => round($stdDev, 4),
                    'buffer_stock'    => round($bufferStock, 2),
                    'safety_stock_95' => round($safetyStock, 2),
                    'rop'             => round($rop, 2),
                    'data_points'     => $n,
                    'data_source'     => 'transaction_db',
                    'date_range'      => [
                        'from' => array_key_first($actualFromTransactions),
                        'to'   => array_key_last($actualFromTransactions),
                    ],
                ];
            }
        }

        // ── 6a. KEBUTUHAN BAHAN BAKU MANAJEMEN (BOM) ──────────────────────
        $totalForecastQty = 0.0;
        if (!empty($forecastChart)) {
            foreach ($forecastChart as $f) {
                $totalForecastQty += (float) ($f['predicted'] ?? 0);
            }
        }
        
        // Fallback jika tidak ada future forecast dari ARIMA
        if ($totalForecastQty <= 0 && !empty($actualChart)) {
            $predSum = 0;
            $predCount = 0;
            foreach ($actualChart as $ac) {
                if (isset($ac['predicted']) && $ac['predicted'] !== null) {
                    $predSum += $ac['predicted'];
                    $predCount++;
                }
            }
            if ($predCount > 0) {
                $totalForecastQty = ($predSum / $predCount) * 30;
            }
        }
        $totalForecastQty = ceil($totalForecastQty);

        $rawMaterialsNeeded = [];
        if ($itemId) {
            $bomRules = MasterItemBillOfMaterials::with('rawMaterial')
                ->where('item_id', $itemId)
                ->get();

            foreach ($bomRules as $rule) {
                if (!$rule->rawMaterial) continue;

                $reqPerUnit = (float) $rule->quantity_required;
                $yield = (float) ($rule->yield_percentage ?? 100.00);
                $reqPerUnitAdjusted = $reqPerUnit / ($yield / 100.00);
                $totalRequired = ceil($reqPerUnitAdjusted * $totalForecastQty * 100) / 100;
                
                $currentStock = (float) $rule->rawMaterial->current_stock;
                $buyQty = max(0.0, $totalRequired - $currentStock);
                $price = (float) $rule->rawMaterial->purchase_price;
                $estCost = $buyQty * $price;

                $rawMaterialsNeeded[] = [
                    'material_name' => $rule->rawMaterial->material_name,
                    'unit' => $rule->rawMaterial->unit,
                    'req_per_unit' => round($reqPerUnit, 4),
                    'yield_percentage' => $yield,
                    'req_per_unit_adjusted' => round($reqPerUnitAdjusted, 4),
                    'total_required' => $totalRequired,
                    'current_stock' => $currentStock,
                    'buy_qty' => ceil($buyQty * 100) / 100,
                    'price' => $price,
                    'estimated_cost' => $estCost,
                    'status' => $buyQty > 0 ? 'Perlu Dibeli' : 'Stok Cukup',
                ];
            }
        }

        $managementForecast = [
            'total_finished_goods_predicted' => $totalForecastQty,
            'raw_materials' => $rawMaterialsNeeded
        ];

        $showMae = $hasTransactionData ? (float) $summary->mae : 0.0;
        $showRmse = $hasTransactionData ? (float) $summary->rmse : 0.0;
        $showMape = $hasTransactionData ? (float) $summary->mape_percentage : 0.0;

        // ── 7. RESPONSE ───────────────────────────────────────────────────
        return response()->json([
            'success' => true,
            'summary' => [
                'produk'          => $summary->produk,
                'item_id'         => $itemId,
                'name_item'       => $masterItem?->name_item ?? $produk,
                'mae'             => $showMae,
                'rmse'            => $showRmse,
                'mape_percentage' => $showMape,
                'arima_order'     => $summary->arima_order,
                'kategori_mae'    => $summary->kategori_mae,
                'stationary'      => $summary->stationary,
                'adf_p_value'     => $summary->adf_p_value,
            ],
            'chart_data' => [
                'training' => $trainingChart,
                'actual'   => $actualChart,
                'forecast' => $forecastChart,
            ],
            'table_data'             => $tableData,
            'dynamic_rec'            => $dynamicRec,
            'management_forecast'    => $managementForecast,
            'has_transaction_data'   => $hasTransactionData,
            'transaction_data_count' => count($actualFromTransactions),
        ]);
    }

    /**
     * GET: /admin/inventory/transaction-history
     * Display all sales transactions with statistics
     */
    public function transactionHistory(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = (int) $request->get('per_page', 10);

        $query = TransactionSales::with(['customer', 'user', 'transactionSalesDetails.masterItem'])
            ->orderBy('date', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('shipping_address', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name_customer', 'like', "%{$search}%")
                        ->orWhere('email_customer', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->paginate($perPage);

        // Hitung statistik rangkuman
        $totalSalesAmount = TransactionSales::where('shipping_status', '!=', 'cancelled')->sum('total_amount');
        $totalTransactions = TransactionSales::count();
        
        $lunasCount = 0;
        $belumBayarCount = 0;
        $sebagianCount = 0;
        $cancelledCount = 0;

        $allTransactions = TransactionSales::all();
        foreach ($allTransactions as $t) {
            if ($t->shipping_status === 'cancelled') {
                $cancelledCount++;
            } else {
                $status = $t->payment_status;
                if ($status === 'lunas') {
                    $lunasCount++;
                } elseif ($status === 'belum-bayar') {
                    $belumBayarCount++;
                } else {
                    $sebagianCount++;
                }
            }
        }

        $summary = [
            'total_sales' => $totalSalesAmount,
            'total_transactions' => $totalTransactions,
            'lunas' => $lunasCount,
            'belum_bayar' => $belumBayarCount,
            'sebagian' => $sebagianCount,
            'cancelled' => $cancelledCount
        ];

        return view('admin_inventory.transaction_history', compact(
            'transactions',
            'summary',
            'perPage',
            'search'
        ));
    }

    /**
     * GET: /admin/inventory/transaction-history/{id}
     * Fetch single sales transaction details for AJAX modal
     */
    public function transactionHistoryDetail($id)
    {
        $transaction = TransactionSales::with([
            'customer', 
            'user', 
            'transactionSalesDetails.masterItem',
            'payments.paymentMethod'
        ])->find($id);

        if (!$transaction) {
            return redirect()->back()->with('error', 'Transaksi tidak ditemukan');
        }

        // Build elegant structure
        $items = [];
        foreach ($transaction->transactionSalesDetails as $detail) {
            $items[] = [
                'name_item' => $detail->masterItem->name_item ?? 'Item Terhapus',
                'code_item' => $detail->masterItem->code_item ?? '',
                'qty' => $detail->qty,
                'sell_price' => (float) $detail->sell_price,
                'total_amount' => (float) $detail->total_amount,
            ];
        }

        $paymentsList = [];
        foreach ($transaction->payments as $payment) {
            $paymentsList[] = [
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('d M Y H:i') : '-',
                'payment_method' => $payment->paymentMethod->name_payment_method ?? ($payment->payment_type ?: '-'),
                'amount' => (float) $payment->amount,
                'payment_status' => $payment->payment_status,
                'payment_status_label' => $payment->status_label,
            ];
        }

        $details = [
            'transaction_sales_id' => $transaction->transaction_sales_id,
            'number' => $transaction->number,
            'date' => $transaction->date ? $transaction->date->format('d M Y H:i') : '-',
            'customer_name' => $transaction->customer->name_customer ?? 'Umum',
            'customer_whatsapp' => $transaction->customer->whatsapp_customer ?? $transaction->whatsapp ?? '-',
            'shipping_address' => $transaction->shipping_address ?? '-',
            'shipping_cost' => (float) $transaction->shipping_cost,
            'shipping_courier' => $transaction->shipping_courier ?? '-',
            'shipping_service' => $transaction->shipping_service ?? '-',
            'shipping_status' => $transaction->shipping_status_label,
            'subtotal' => (float) $transaction->subtotal,
            'discount' => (float) $transaction->discount_amount,
            'total_amount' => (float) $transaction->total_amount,
            'grand_total' => (float) $transaction->grand_total,
            'payment_status' => $transaction->payment_status,
            'overall_status_label' => $transaction->status_label,
            'items' => $items,
            'payments' => $paymentsList,
        ];

        return view('admin_inventory.transaction_history_show', compact('details'));
    }

    /**
     * GET: /admin/inventory/stock-opname
     * Display stock opname and adjustment history
     */
    public function stockOpname(Request $request)
    {
        // Parameter tab & search
        $activeTab = $request->get('tab', 'comparison');
        $search = strtolower($request->get('search', ''));
        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();

        // Get Gentle Living branch
        $gentleLivingBranch = MasterBranch::where('name_branch', 'like', '%Gentle Living%')->first();
        
        if (!$gentleLivingBranch) {
            $gentleLivingBranch = MasterBranch::first(); // Fallback to first branch
        }

        // Get all inventories for Gentle Living branch
        $inventories = MasterInventory::where('branch_id', $gentleLivingBranch->branch_id)
            ->pluck('inventory_id')
            ->toArray();

        // Get all items with their stock data from latest adjustment
        $allItems = MasterItem::with('itemStocks')
            ->where('status_item', 'active')
            ->get();

        // Prepare stock comparison data
        $stockComparisonRaw = [];
        
        // 1. Finished Goods
        foreach ($allItems as $item) {
            // Get latest adjustment for this item
            $latestAdjustment = StockAdjustment::where('item_id', $item->item_id)
                ->where('item_type', 'finished_good')
                ->whereIn('inventory_id', $inventories)
                ->orderBy('adjusted_at', 'desc')
                ->first();

            // Get current system stock from MasterItemStock
            $itemStock = MasterItemStock::where('item_id', $item->item_id)
                ->whereIn('inventory_id', $inventories)
                ->first();

            // Skip if no inventory record exists yet
            if (!$itemStock) {
                continue;
            }

            $qtySystem = $itemStock->stock ?? 0;
            $qtyPhysical = $latestAdjustment ? $latestAdjustment->qty_physical : $qtySystem;
            $qtyDifference = $qtyPhysical - $qtySystem;

            $stockComparisonRaw[] = (object)[
                'item_id' => $item->item_id,
                'item_name' => $item->name_item,
                'item_code' => $item->code_item,
                'qty_system' => $qtySystem,
                'qty_physical' => $qtyPhysical,
                'qty_difference' => $qtyDifference,
                'unit' => $item->unit ?? 'pcs',
                'reason' => $latestAdjustment->reason ?? '-',
                'adjusted_at' => $latestAdjustment->adjusted_at ?? null,
                'adjustment_id' => $latestAdjustment->adjustment_id ?? null,
                'inventory_id' => $itemStock->inventory_id,
                'item_type' => 'finished_good',
                'rawMaterial' => null
            ];
        }

        // 2. Raw Materials
        $allRawMaterials = MasterItemRawMaterial::get();
        foreach ($allRawMaterials as $raw) {
            $latestAdjustment = StockAdjustment::where('item_id', $raw->item_raw_id)
                ->where('item_type', 'raw_material')
                ->whereIn('inventory_id', $inventories)
                ->orderBy('adjusted_at', 'desc')
                ->first();

            $qtySystem = $raw->current_stock ?? 0;
            $qtyPhysical = $latestAdjustment ? $latestAdjustment->qty_physical : $qtySystem;
            $qtyDifference = $qtyPhysical - $qtySystem;

            $stockComparisonRaw[] = (object)[
                'item_id' => $raw->item_raw_id,
                'item_name' => $raw->material_name,
                'item_code' => 'RM-' . str_pad($raw->item_raw_id, 4, '0', STR_PAD_LEFT),
                'qty_system' => $qtySystem,
                'qty_physical' => $qtyPhysical,
                'qty_difference' => $qtyDifference,
                'unit' => $raw->unit ?? 'unit',
                'reason' => $latestAdjustment->reason ?? '-',
                'adjusted_at' => $latestAdjustment->adjusted_at ?? null,
                'adjustment_id' => $latestAdjustment->adjustment_id ?? null,
                'inventory_id' => !empty($inventories) ? $inventories[0] : null,
                'item_type' => 'raw_material',
                'rawMaterial' => $raw
            ];
        }

        // Filter comparison based on search
        if ($search !== '') {
            $stockComparisonRaw = array_filter($stockComparisonRaw, function($item) use ($search) {
                return str_contains(strtolower($item->item_name), $search) || 
                       str_contains(strtolower($item->item_code), $search);
            });
        }

        // Sort by difference
        usort($stockComparisonRaw, function($a, $b) {
            return abs($b->qty_difference) <=> abs($a->qty_difference);
        });

        // Calculate comparison stats
        $comparisonStats = [
            'total_items_checked' => count($stockComparisonRaw),
            'items_with_surplus' => count(array_filter($stockComparisonRaw, fn($item) => $item->qty_difference > 0)),
            'items_with_deficit' => count(array_filter($stockComparisonRaw, fn($item) => $item->qty_difference < 0)),
            'items_matched' => count(array_filter($stockComparisonRaw, fn($item) => $item->qty_difference == 0)),
            'total_difference' => array_sum(array_map(fn($item) => $item->qty_difference, $stockComparisonRaw)),
            'branch_name' => $gentleLivingBranch->name_branch
        ];

        // Paginate Comparison
        $currentItems = array_slice($stockComparisonRaw, ($currentPage - 1) * $perPage, $perPage);
        $stockComparison = new \Illuminate\Pagination\LengthAwarePaginator($currentItems, count($stockComparisonRaw), $perPage, $currentPage, [
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
            'query' => $request->query()
        ]);

        // Get adjustment data for history
        $adjustmentsQuery = StockAdjustment::whereIn('inventory_id', $inventories)
            ->with(['rawMaterial', 'adjustedByUser']);

        if ($search !== '') {
            $adjustmentsQuery->where(function($q) use ($search) {
                $q->whereHas('rawMaterial', function($q2) use ($search) {
                    $q2->where('material_name', 'like', "%{$search}%");
                })->orWhere(function($q3) use ($search) {
                    $matchingItemIds = MasterItem::where('name_item', 'like', "%{$search}%")->pluck('item_id');
                    $q3->where('item_type', 'finished_good')->whereIn('item_id', $matchingItemIds);
                })->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $adjustments = $adjustmentsQuery->orderBy('adjusted_at', 'desc')
            ->paginate($perPage)
            ->appends($request->query())
            ->through(function ($adj) {
                $itemName = 'N/A';
                if ($adj->item_type === 'raw_material' && $adj->rawMaterial) {
                    $itemName = $adj->rawMaterial->material_name;
                } else if ($adj->item_type === 'finished_good') {
                    $item = MasterItem::find($adj->item_id);
                    $itemName = $item ? $item->name_item : 'N/A';
                }
                $adj->display_name = $itemName;
                return $adj;
            });

        // Get materials with adjustments
        $materialsRaw = StockAdjustment::whereIn('inventory_id', $inventories)
            ->selectRaw('item_id, item_type, COUNT(*) as adjustment_count, SUM(qty_difference) as total_adjustment, unit')
            ->groupBy('item_id', 'item_type', 'unit')
            ->with('rawMaterial')
            ->get()
            ->map(function ($mat) {
                $itemName = 'N/A';
                $unit = $mat->unit ?? 'unit';
                if ($mat->item_type === 'raw_material' && $mat->rawMaterial) {
                    $itemName = $mat->rawMaterial->material_name;
                    $unit = $mat->rawMaterial->unit ?? $unit;
                } else if ($mat->item_type === 'finished_good') {
                    $item = MasterItem::find($mat->item_id);
                    $itemName = $item ? $item->name_item : 'N/A';
                    $unit = $item ? ($item->unit ?? 'pcs') : $unit;
                }
                $mat->display_name = $itemName;
                $mat->display_unit = $unit;
                return $mat;
            });

        if ($search !== '') {
            $materialsRaw = $materialsRaw->filter(function($mat) use ($search) {
                return str_contains(strtolower($mat->display_name), $search);
            })->values();
        }

        // Paginate materials
        $matItems = $materialsRaw->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $materialsWithAdjustments = new \Illuminate\Pagination\LengthAwarePaginator($matItems, $materialsRaw->count(), $perPage, $currentPage, [
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
            'query' => $request->query()
        ]);

        // Get summary data
        $adjustmentTypes = StockAdjustment::whereIn('inventory_id', $inventories)
            ->selectRaw("adjustment_type, SUM(ABS(qty_difference)) as qty")
            ->groupBy('adjustment_type')
            ->pluck('qty', 'adjustment_type')
            ->toArray();

        $adjustmentReasons = StockAdjustment::whereIn('inventory_id', $inventories)
            ->selectRaw("reason, SUM(ABS(qty_difference)) as qty")
            ->groupBy('reason')
            ->pluck('qty', 'reason')
            ->toArray();

        $summary = [
            'total_adjustments' => StockAdjustment::whereIn('inventory_id', $inventories)->count(),
            'period_days' => (int) $request->get('days', 30),
            'adjustment_types' => $adjustmentTypes,
            'adjustment_reasons' => $adjustmentReasons
        ];

        // Get final stocks data
        $finalStocksQuery = MasterItemStock::whereIn('inventory_id', $inventories)
            ->with(['item', 'inventory'])
            ->orderBy('stock', 'desc')
            ->get();

        // Map final stocks to include received dates
        $finalStocks = $finalStocksQuery->map(function ($stock) {
            $receivedDate = null;
            $orderDate = null;
            
            // Get latest received date and order date based on item type
            if ($stock->item) {
                // Check if item is raw material
                if ($stock->item->is_raw_material ?? false) {
                    $rawMaterialIn = RawMaterialIn::where('item_raw_id', function ($q) use ($stock) {
                        $q->select('item_raw_id')
                            ->from('master_items_raw_material')
                            ->where('item_id', $stock->item_id);
                    })
                    ->orderBy('received_date', 'desc')
                    ->first();
                    
                    if ($rawMaterialIn) {
                        $receivedDate = $rawMaterialIn->received_date;
                        $orderDate = $rawMaterialIn->created_at;
                    }
                } else {
                    // Check finished goods
                    $finishedGoodsIn = FinishedGoodsIn::where('item_id', $stock->item_id)
                        ->orderBy('received_date', 'desc')
                        ->first();
                    
                    if ($finishedGoodsIn) {
                        $receivedDate = $finishedGoodsIn->received_date;
                        $orderDate = $finishedGoodsIn->production_date;
                    }
                }
            }
            
            $stock->received_date = $receivedDate;
            $stock->order_date = $orderDate;
            
            return $stock;
        });


        // Get raw material outflows
        $rawMaterialOuts = RawMaterialOut::where('branch_id', $gentleLivingBranch->branch_id)
            ->with(['rawMaterial', 'issuedByUser', 'productionOrder'])
            ->orderBy('issued_date', 'desc')
            ->paginate(15);

        // Get recent physical inputs from stock adjustment
        $physicalInputs = StockAdjustment::whereIn('inventory_id', $inventories)
            ->where('reason', 'opname_result')
            ->with([
                'rawMaterial' => function ($query) {
                    $query->select('item_raw_id', 'material_name', 'current_stock', 'unit');
                }
            ])
            ->select([
                'adjustment_id',
                'item_id',
                'item_type',
                'qty_system',
                'qty_physical',
                'qty_difference',
                'adjusted_by',
                'adjusted_at',
                'notes'
            ])
            ->orderBy('adjusted_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($adjustment) {
                $itemName = 'N/A';
                if ($adjustment->item_type === 'raw_material' && $adjustment->rawMaterial) {
                    $itemName = $adjustment->rawMaterial->material_name;
                } else if ($adjustment->item_type === 'finished_good') {
                    $item = MasterItem::find($adjustment->item_id);
                    $itemName = $item ? $item->name_item : 'N/A';
                }
                $adjustment->item_name = $itemName;
                return $adjustment;
            });

        return view('admin_inventory.stock_opname', compact(
            'stockComparison',
            'comparisonStats',
            'adjustments',
            'materialsWithAdjustments',
            'summary',
            'finalStocks',
            'rawMaterialOuts',
            'physicalInputs',
            'activeTab'
        ));
    }

    /**
     * POST: /admin/inventory/stock-opname/save-physical-stock
     * Save physical stock value
     */
    public function savePhysicalStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_id' => 'required|integer',
                'qty_physical' => 'required|numeric|min:0',
                'qty_system' => 'required|numeric|min:0',
                'inventory_id' => 'required|integer',
                'adjustment_id' => 'nullable|integer',
                'item_type' => 'nullable|string|in:finished_good,raw_material'
            ]);

            $itemType = $validated['item_type'] ?? 'finished_good';

            // Get inventory to get branch_id
            $inventory = MasterInventory::findOrFail($validated['inventory_id']);

            $qtyDifference = $validated['qty_physical'] - $validated['qty_system'];
            $adjustmentType = $qtyDifference > 0 ? 'increase' : ($qtyDifference < 0 ? 'decrease' : 'none');

            // Create or update stock adjustment
            $adjustment = StockAdjustment::updateOrCreate(
                [
                    'adjustment_id' => $validated['adjustment_id']
                ],
                [
                    'item_type' => $itemType,
                    'item_id' => $validated['item_id'],
                    'inventory_id' => $validated['inventory_id'],
                    'branch_id' => $inventory->branch_id,
                    'qty_system' => $validated['qty_system'],
                    'qty_physical' => $validated['qty_physical'],
                    'qty_difference' => $qtyDifference,
                    'qty_after_adjustment' => $validated['qty_physical'],
                    'reason' => 'opname_result',
                    'adjustment_type' => $adjustmentType,
                    'adjusted_by' => Auth::id(),
                    'adjusted_at' => now(),
                    'notes' => 'Updated via stock comparison interface'
                ]
            );

            // Update respective stock table
            if ($itemType === 'raw_material') {
                $rawMaterial = MasterItemRawMaterial::findOrFail($validated['item_id']);
                $rawMaterial->current_stock = $validated['qty_physical'];
                $rawMaterial->save();

                // Add raw material flow log
                if ($qtyDifference != 0) {
                    if ($qtyDifference > 0) {
                        RawMaterialIn::create([
                            'item_raw_id' => $rawMaterial->item_raw_id,
                            'supplier_id' => null,
                            'branch_id' => $inventory->branch_id,
                            'received_by' => Auth::id() ?? 1,
                            'document_number' => 'RMI-' . date('YmdHis'),
                            'qty_ordered' => $qtyDifference,
                            'qty_received' => $qtyDifference,
                            'qty_rejected' => 0,
                            'unit' => $rawMaterial->unit,
                            'unit_cost' => $rawMaterial->purchase_price,
                            'total_cost' => $qtyDifference * $rawMaterial->purchase_price,
                            'stock_before' => $validated['qty_system'],
                            'stock_after' => $validated['qty_physical'],
                            'received_date' => now()->toDateString(),
                            'notes' => 'Updated via stock comparison interface (Stock Opname)',
                        ]);
                    } else {
                        $diff = abs($qtyDifference);
                        RawMaterialOut::create([
                            'item_raw_id' => $rawMaterial->item_raw_id,
                            'production_order_id' => null,
                            'bom_id' => null,
                            'branch_id' => $inventory->branch_id,
                            'issued_by' => Auth::id() ?? 1,
                            'document_number' => 'RMO-' . date('YmdHis'),
                            'qty_requested' => $diff,
                            'qty_issued' => $diff,
                            'unit' => $rawMaterial->unit,
                            'unit_cost' => $rawMaterial->purchase_price,
                            'total_cost' => $diff * $rawMaterial->purchase_price,
                            'stock_before' => $validated['qty_system'],
                            'stock_after' => $validated['qty_physical'],
                            'reason' => 'adjustment',
                            'issued_date' => now()->toDateString(),
                            'notes' => 'Updated via stock comparison interface (Stock Opname)',
                        ]);
                    }
                }
            } else {
                MasterItemStock::updateOrCreate(
                    [
                        'item_id' => $validated['item_id'],
                        'inventory_id' => $validated['inventory_id']
                    ],
                    [
                        'stock' => (int) $validated['qty_physical']
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Stok fisik berhasil disimpan',
                'data' => [
                    'item_id' => $validated['item_id'],
                    'qty_physical' => $validated['qty_physical'],
                    'qty_difference' => $qtyDifference,
                    'adjustment_id' => $adjustment->adjustment_id
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving physical stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST: /inventory/physical-input
     * Store physical input data for items
     */
    public function storePhysicalInput(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_type' => 'required|in:raw_material,finished_good',
                'item_id' => 'required|integer',
                'qty_physical' => 'required|numeric|min:0.01',
                'warehouse_location' => 'nullable|string|max:255',
                'condition' => 'nullable|in:good,damaged,expired,other',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Get default inventory with proper null checks
            $branch = MasterBranch::where('name_branch', 'like', '%Gentle Living%')->first() 
                ?? MasterBranch::first();
            
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data branch tidak ditemukan. Hubungi administrator.'
                ], 422);
            }
            
            $inventory = MasterInventory::where('branch_id', $branch->branch_id)->first()
                ?? MasterInventory::first();
            
            if (!$inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data inventory tidak ditemukan. Hubungi administrator.'
                ], 422);
            }

            // Get current stock for comparison
            $currentStock = 0;
            if ($validated['item_type'] === 'raw_material') {
                $material = MasterItemRawMaterial::find($validated['item_id']);
                if (!$material) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bahan baku dengan ID ' . $validated['item_id'] . ' tidak ditemukan.'
                    ], 422);
                }
                $currentStock = $material->current_stock ?? 0;
            } else {
                $itemStock = MasterItemStock::where('item_id', $validated['item_id'])
                    ->where('inventory_id', $inventory->inventory_id)
                    ->first();
                if (!$itemStock) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Produk jadi dengan ID ' . $validated['item_id'] . ' tidak ditemukan di inventory ini.'
                    ], 422);
                }
                $currentStock = $itemStock->stock ?? 0;
            }

            $qtyDifference = $validated['qty_physical'] - $currentStock;

            // Create stock adjustment record
            $adjustment = StockAdjustment::create([
                'item_type' => $validated['item_type'],
                'item_id' => $validated['item_id'],
                'inventory_id' => $inventory->inventory_id,
                'branch_id' => $branch->branch_id,
                'qty_system' => $currentStock,
                'qty_physical' => $validated['qty_physical'],
                'qty_difference' => $qtyDifference,
                'qty_after_adjustment' => $validated['qty_physical'],
                'reason' => 'opname_result',
                'adjustment_type' => $qtyDifference > 0 ? 'increase' : ($qtyDifference < 0 ? 'decrease' : 'none'),
                'adjusted_by' => Auth::id(),
                'adjusted_at' => now(),
                'notes' => 'Kondisi: ' . ($validated['condition'] ?? 'tidak ditentukan') . '. ' . 
                          'Lokasi: ' . ($validated['warehouse_location'] ?? 'tidak ditentukan') . '. ' .
                          ($validated['notes'] ? 'Catatan: ' . $validated['notes'] : '')
            ]);

            // Update stock in system
            if ($validated['item_type'] === 'raw_material') {
                $material = MasterItemRawMaterial::find($validated['item_id']);
                if ($material) {
                    $material->update(['current_stock' => $validated['qty_physical']]);
                    
                    if ($qtyDifference > 0) {
                        RawMaterialIn::create([
                            'item_raw_id' => $material->item_raw_id,
                            'supplier_id' => null,
                            'branch_id' => $branch->branch_id,
                            'received_by' => Auth::id() ?? 1,
                            'document_number' => 'RMI-' . date('YmdHis'),
                            'qty_ordered' => $qtyDifference,
                            'qty_received' => $qtyDifference,
                            'qty_rejected' => 0,
                            'unit' => $material->unit,
                            'unit_cost' => $material->purchase_price,
                            'total_cost' => $qtyDifference * $material->purchase_price,
                            'stock_before' => $currentStock,
                            'stock_after' => $validated['qty_physical'],
                            'received_date' => now()->toDateString(),
                            'notes' => 'Penyesuaian fisik tambah (Physical Input): ' . ($validated['notes'] ?? ''),
                        ]);
                    } else if ($qtyDifference < 0) {
                        $diff = abs($qtyDifference);
                        RawMaterialOut::create([
                            'item_raw_id' => $material->item_raw_id,
                            'production_order_id' => null,
                            'bom_id' => null,
                            'branch_id' => $branch->branch_id,
                            'issued_by' => Auth::id() ?? 1,
                            'document_number' => 'RMO-' . date('YmdHis'),
                            'qty_requested' => $diff,
                            'qty_issued' => $diff,
                            'unit' => $material->unit,
                            'unit_cost' => $material->purchase_price,
                            'total_cost' => $diff * $material->purchase_price,
                            'stock_before' => $currentStock,
                            'stock_after' => $validated['qty_physical'],
                            'reason' => 'adjustment',
                            'issued_date' => now()->toDateString(),
                            'notes' => 'Penyesuaian fisik kurang (Physical Input): ' . ($validated['notes'] ?? ''),
                        ]);
                    }
                }
            } else {
                MasterItemStock::updateOrCreate(
                    [
                        'item_id' => $validated['item_id'],
                        'inventory_id' => $inventory->inventory_id
                    ],
                    [
                        'stock' => $validated['qty_physical']
                    ]
                );
            }

            Log::info('Physical input stored', [
                'user_id' => Auth::id(),
                'item_type' => $validated['item_type'],
                'item_id' => $validated['item_id'],
                'qty_physical' => $validated['qty_physical'],
                'adjustment_id' => $adjustment->adjustment_id,
                'warehouse_location' => $validated['warehouse_location'] ?? null,
                'condition' => $validated['condition'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data fisik barang berhasil disimpan',
                'data' => [
                    'adjustment_id' => $adjustment->adjustment_id,
                    'qty_physical' => $validated['qty_physical'],
                    'qty_difference' => $qtyDifference,
                    'condition' => $validated['condition'] ?? 'tidak ditentukan',
                    'location' => $validated['warehouse_location'] ?? 'tidak ditentukan'
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing physical input: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/api/raw-materials
     * Get raw materials for dropdown selection in physical input form
     */
    public function getRawMaterialsForSelection()
    {
        try {
            $materials = MasterItemRawMaterial::orderBy('material_name')
                ->select('item_raw_id', 'material_name', 'unit', 'current_stock')
                ->get()
                ->map(function ($material) {
                    return [
                        'id' => $material->item_raw_id,
                        'name' => $material->material_name . ' (' . $material->unit . ')',
                        'text' => $material->material_name,
                        'unit' => $material->unit,
                        'current_stock' => $material->current_stock
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $materials
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting raw materials: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data bahan baku'
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/api/finished-goods
     * Get finished goods for dropdown selection in physical input form
     */
    public function getFinishedGoodsForSelection()
    {
        try {
            $goods = MasterItemStock::with('item')
                ->whereHas('item', function ($query) {
                    // Filter untuk produk jadi (bukan bahan baku)
                    // Bisa disesuaikan dengan kategori atau tipe produk jika ada
                })
                ->orderBy('stock', 'desc')
                ->select('item_stock_id', 'item_id', 'stock')
                ->get()
                ->map(function ($good) {
                    $itemName = optional($good->item)->name_item ?? 'Unknown Item';
                    $itemCode = optional($good->item)->code_item ?? '';
                    $displayName = $itemCode ? "{$itemName} ({$itemCode})" : $itemName;

                    return [
                        'id' => $good->item_id,
                        'item_stock_id' => $good->item_stock_id,
                        'name' => $displayName,
                        'text' => $itemName,
                        'code' => $itemCode,
                        'current_stock' => $good->stock
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $goods
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting finished goods: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data produk jadi'
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/api/received-goods
     * Get goods received within a date range for filter
     */
    public function getReceivedGoodsByDateRange(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            if (!$dateFrom || !$dateTo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date range is required'
                ], 400);
            }

            $receivedGoods = [];

            // Get raw materials received in date range
            $rawMaterialsIn = RawMaterialIn::whereBetween('received_date', [$dateFrom, $dateTo])
                ->with('rawMaterial')
                ->orderBy('received_date', 'desc')
                ->get()
                ->map(function ($material) {
                    return [
                        'id' => $material->item_raw_id,
                        'name' => optional($material->rawMaterial)->material_name,
                        'type' => 'raw_material',
                        'received_date' => $material->received_date,
                        'qty_received' => $material->qty_received,
                        'unit' => $material->unit
                    ];
                });

            // Get finished goods received in date range
            $finishedGoodsIn = FinishedGoodsIn::whereBetween('received_date', [$dateFrom, $dateTo])
                ->with('item')
                ->orderBy('received_date', 'desc')
                ->get()
                ->map(function ($good) {
                    return [
                        'id' => $good->item_id,
                        'name' => optional($good->item)->name_item,
                        'type' => 'finished_good',
                        'received_date' => $good->received_date,
                        'qty_received' => $good->qty_received,
                        'unit' => $good->unit
                    ];
                });

            $receivedGoods = $rawMaterialsIn->concat($finishedGoodsIn)->values();

            return response()->json([
                'success' => true,
                'data' => $receivedGoods
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting received goods: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching received goods'
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/production-overview
     * Display production orders and raw material tracking
     */
    public function productionOverview(Request $request)
    {
        $daysBack = (int) $request->get('days', 30);
        $startDate = Carbon::now()->subDays($daysBack);

        $search = $request->get('search', '');
        
        $query = ProductionOrder::where('planned_date', '>=', $startDate)->with('item');
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('production_code', 'LIKE', "%{$search}%")
                  ->orWhereHas('item', function($cq) use ($search) {
                      $cq->where('name_item', 'LIKE', "%{$search}%")
                         ->orWhere('code_item', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Recent production orders
        $productionOrders = $query->orderBy('planned_date', 'desc')->paginate(15);

        // Raw materials in/out summary
        $rawMaterialInSummary = RawMaterialIn::where('received_date', '>=', $startDate)
            ->selectRaw('item_raw_id, COUNT(*) as receipt_count, SUM(qty_received) as total_received')
            ->groupBy('item_raw_id')
            ->with('rawMaterial')
            ->get();

        $rawMaterialOutSummary = RawMaterialOut::where('issued_date', '>=', $startDate)
            ->selectRaw('item_raw_id, COUNT(*) as usage_count, SUM(qty_issued) as total_used')
            ->groupBy('item_raw_id')
            ->with('rawMaterial')
            ->get();

        // Production status breakdown
        $productionStatus = ProductionOrder::where('planned_date', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count, SUM(qty_planned) as total_qty')
            ->groupBy('status')
            ->pluck('total_qty', 'status');

        // Finished goods in/out - dengan production code
        $finishedGoodsIn = FinishedGoodsIn::where('received_date', '>=', $startDate)
            ->with('item')
            ->orderBy('received_date', 'desc')
            ->limit(100)
            ->get();

        // Group data untuk summary stats
        $finishedGoodsInSummary = FinishedGoodsIn::where('received_date', '>=', $startDate)
            ->selectRaw('item_id, COUNT(*) as batch_count, SUM(qty_received) as total_produced')
            ->groupBy('item_id')
            ->with('item')
            ->get();

        $finishedGoodsOut = FinishedGoodsOut::where('out_date', '>=', $startDate)
            ->selectRaw('item_id, COUNT(*) as transaction_count, SUM(qty_out) as total_sold')
            ->groupBy('item_id')
            ->with('item')
            ->get();

        $summary = [
            'period_days' => $daysBack,
            'total_production_orders' => $productionOrders->total(),
            'production_status' => $productionStatus->toArray(),
            'total_raw_material_in' => $rawMaterialInSummary->sum('total_received'),
            'total_raw_material_out' => $rawMaterialOutSummary->sum('total_used'),
            'total_finished_goods_in' => $finishedGoodsInSummary->sum('total_produced'),
            'total_finished_goods_out' => $finishedGoodsOut->sum('total_sold')
        ];

        return view('admin_inventory.production_overview', compact(
            'productionOrders',
            'rawMaterialInSummary',
            'rawMaterialOutSummary',
            'finishedGoodsIn',
            'finishedGoodsInSummary',
            'finishedGoodsOut',
            'summary',
            'daysBack',
            'search'
        ));
    }

    /**
     * GET: /admin/inventory/buffer-stock/details/{itemRawId}
     * Get detailed buffer stock calculation for a specific material
     */
    public function bufferStockDetail($itemRawId)
    {
        $service = new BufferStockCalculationService();
        $material = MasterItemRawMaterial::find($itemRawId);

        if (!$material) {
            return response()->json(['error' => 'Material not found'], 404);
        }

        $calculation = $service->calculateBufferStock($itemRawId);
        $adjustmentAnalysis = $service->getStockAdjustmentAnalysis($itemRawId);

        // Get historical usage data for last 30 days
        $usageHistory = RawMaterialOut::where('item_raw_id', $itemRawId)
            ->where('issued_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(issued_date) as date, SUM(qty_issued) as daily_usage')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get receipt history for last 30 days
        $receiptHistory = RawMaterialIn::where('item_raw_id', $itemRawId)
            ->where('received_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(received_date) as date, SUM(qty_received) as daily_receipt')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'material' => $material,
            'calculation' => $calculation,
            'adjustment_analysis' => $adjustmentAnalysis,
            'usage_history' => $usageHistory,
            'receipt_history' => $receiptHistory
        ]);
    }



    /**
     * GET: /admin/inventory/buffer-stock/production-master-data
     * Get master data for production modal: raw materials, inventories, and BOM products.
     */
    public function bufferStockProductionMasterData()
    {
        $inventoryOptions = MasterInventory::orderBy('inventory_id')
            ->get(['inventory_id', 'name_inventory', 'branch_id']);

        $materials = MasterItemRawMaterial::orderBy('material_name')
            ->get(['item_raw_id', 'material_name', 'unit', 'current_stock']);

        $itemIds = MasterItem::query()
            ->select('item_id')
            ->pluck('item_id')
            ->values();

        $productOptions = collect();

        foreach ($itemIds as $itemId) {
            $item = MasterItem::find($itemId);
            if (!$item) {
                continue;
            }

            $recipe = $this->buildProductionRecipeForItem((int) $itemId, 1);
            if (empty($recipe['materials'])) {
                continue;
            }

            $maxProducible = (int) ($recipe['max_producible'] ?? 0);
            $bomPreview = collect($recipe['materials'])
                ->map(function ($need) {
                    $raw = $need['raw'] ?? null;

                    return [
                        'item_raw_id' => (int) ($raw->item_raw_id ?? 0),
                        'material_name' => (string) ($need['material_name'] ?? ($raw->material_name ?? '-')),
                        'unit' => (string) ($need['unit'] ?? ($raw->unit ?? '-')),
                        'required_per_unit' => round((float) ($need['required_per_unit'] ?? 0), 1),
                        'stock_now' => (float) ($raw->current_stock ?? 0),
                    ];
                })
                ->values()
                ->all();

            $productOptions->push([
                'item_id' => (int) $item->item_id,
                'code_item' => (string) ($item->code_item ?? ''),
                'name_item' => (string) ($item->name_item ?? '-'),
                'max_producible' => max(0, $maxProducible),
                'recipe_source' => (string) ($recipe['source'] ?? 'bom'),
                'bom_preview' => $bomPreview,
            ]);
        }

        return response()->json([
            'success' => true,
            'materials' => $materials,
            'inventories' => $inventoryOptions,
            'products' => $productOptions,
        ]);
    }

    /**
     * GET: /admin/inventory/buffer-stock/production-options/{itemRawId}
     * Get finished goods that can be produced by selected raw material.
     */
    public function bufferStockProductionOptions($itemRawId)
    {
        $material = MasterItemRawMaterial::find($itemRawId);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Bahan baku tidak ditemukan.'
            ], 404);
        }

        $inventoryOptions = MasterInventory::orderBy('inventory_id')
            ->get(['inventory_id', 'name_inventory', 'branch_id']);

        $itemIds = MasterItem::query()
            ->select('item_id')
            ->pluck('item_id')
            ->values();

        $productOptions = collect();

        foreach ($itemIds as $itemId) {
            $item = MasterItem::find($itemId);
            if (!$item) {
                continue;
            }

            $recipe = $this->buildProductionRecipeForItem((int) $itemId, 1);
            if (empty($recipe['materials'])) {
                continue;
            }

            $containsSelectedRaw = collect($recipe['materials'])
                ->contains(function ($need) use ($itemRawId) {
                    return (int) (($need['raw']->item_raw_id ?? 0)) === (int) $itemRawId;
                });

            if (!$containsSelectedRaw) {
                continue;
            }

            $maxProducible = (int) ($recipe['max_producible'] ?? 0);
            $bomPreview = collect($recipe['materials'])
                ->map(function ($need) {
                    $raw = $need['raw'] ?? null;

                    return [
                        'item_raw_id' => (int) ($raw->item_raw_id ?? 0),
                        'material_name' => (string) ($need['material_name'] ?? ($raw->material_name ?? '-')),
                        'unit' => (string) ($need['unit'] ?? ($raw->unit ?? '-')),
                        'required_per_unit' => round((float) ($need['required_per_unit'] ?? 0), 1),
                        'stock_now' => (float) ($raw->current_stock ?? 0),
                    ];
                })
                ->values()
                ->all();

            $productOptions->push([
                'item_id' => (int) $item->item_id,
                'code_item' => (string) ($item->code_item ?? ''),
                'name_item' => (string) ($item->name_item ?? '-'),
                'max_producible' => max(0, $maxProducible),
                'recipe_source' => (string) ($recipe['source'] ?? 'bom'),
                'bom_preview' => $bomPreview,
            ]);
        }

        return response()->json([
            'success' => true,
            'material' => [
                'item_raw_id' => (int) $material->item_raw_id,
                'material_name' => (string) ($material->material_name ?? '-'),
                'unit' => (string) ($material->unit ?? '-'),
                'current_stock' => (int) ($material->current_stock ?? 0),
            ],
            'inventories' => $inventoryOptions,
            'products' => $productOptions,
        ]);
    }

    /**
     * POST: /admin/inventory/buffer-stock/produce
     * Consume raw materials and increase finished goods stock.
     */
    public function produceFromRawMaterial(Request $request)
    {
        try {
            Log::info('Production request received', [
                'item_id' => $request->item_id,
                'inventory_id' => $request->inventory_id,
                'qty_produced' => $request->qty_produced,
            ]);

            $validated = $request->validate([
                'item_id' => 'required|integer|exists:master_items,item_id',
                'inventory_id' => 'required|integer|exists:master_inventories,inventory_id',
                'qty_produced' => 'required|integer|min:1|max:1000000',
                'selected_materials' => 'nullable|array',
                'selected_materials.*.material_id' => 'required|string',
                'selected_materials.*.required_qty' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            $itemId = (int) $validated['item_id'];
            $inventoryId = (int) $validated['inventory_id'];
            $qtyProduced = (int) $validated['qty_produced'];
            $selectedMaterials = $validated['selected_materials'] ?? [];

            $inventory = MasterInventory::find($inventoryId);
            if (!$inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventori tidak ditemukan.'
                ], 404);
            }

            $branchId = (int) $inventory->branch_id;
            $userId = (int) (Auth::id() ?? 0);

            if ($userId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session user tidak valid. Silakan login ulang.'
                ], 401);
            }

            $recipe = $this->buildProductionRecipeForItem($itemId, $qtyProduced);

            Log::info('Recipe built', [
                'source' => $recipe['source'] ?? 'none',
                'materials_count' => count($recipe['materials'] ?? []),
                'max_producible' => $recipe['max_producible'] ?? 0,
            ]);

            if (empty($recipe['materials'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Racikan produk belum tersedia (BOM/CSV), produksi tidak dapat diproses.'
                ], 422);
            }

            $allMaterialNeeds = collect($recipe['materials'])->map(function ($need) {
                $raw = $need['raw'] ?? null;

                return [
                    'bom' => $need['bom'] ?? null,
                    'raw' => $raw,
                    'material_name' => (string) ($need['material_name'] ?? ($raw->material_name ?? '-')),
                    'required_qty' => round((float) ($need['required_qty'] ?? 0), 1),
                    'unit_cost' => (float) ($raw->purchase_price ?? 0),
                    'total_cost' => round((float) ($need['required_qty'] ?? 0) * (float) ($raw->purchase_price ?? 0), 2),
                ];
            })->values()->all();

            // Filter materials to only selected ones if specified
            $materialNeeds = $allMaterialNeeds;
            if (!empty($selectedMaterials)) {
                $selectedMaterialIds = collect($selectedMaterials)->pluck('material_id')->all();
                $materialNeeds = array_filter($allMaterialNeeds, function ($need) use ($selectedMaterialIds, $selectedMaterials) {
                    $rawId = (int) (($need['raw']->item_raw_id ?? null) ?? 0);
                    return in_array((string) $rawId, $selectedMaterialIds) || 
                           in_array($need['material_name'], array_column($selectedMaterials, 'material_name'));
                });
                $materialNeeds = array_values($materialNeeds);
            }

            if (empty($materialNeeds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada material yang dipilih untuk dikurangi dari stok.'
                ], 422);
            }

            $totalMaterialCost = 0;

            foreach ($materialNeeds as $need) {
                $raw = $need['raw'];
                if (!$raw) {
                    Log::warning('Raw material not found in materialNeeds', [
                        'material_name' => $need['material_name'],
                        'required_qty' => $need['required_qty'],
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Ada bahan baku racikan yang tidak ditemukan di database: ' . $need['material_name']
                    ], 422);
                }

                $requiredQty = (float) ($need['required_qty'] ?? 0);
                $stockNow = (float) ($raw->current_stock ?? 0);
                
                Log::info('Checking raw material stock', [
                    'material_name' => $raw->material_name,
                    'required_qty' => $requiredQty,
                    'stock_now' => $stockNow,
                    'sufficient' => $stockNow >= $requiredQty,
                ]);

                if ($stockNow < $requiredQty) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Stok bahan baku "' . ($raw->material_name ?? '-') . '" tidak mencukupi. Dibutuhkan ' . round($requiredQty, 1) . ', tersedia ' . round($stockNow, 1) . '.'
                    ], 422);
                }

                $totalMaterialCost += (float) ($need['total_cost'] ?? 0);
            }

            Log::info('All materials validated, proceeding to transaction', [
                'item_id' => $itemId,
                'qty_produced' => $qtyProduced,
                'total_cost' => $totalMaterialCost,
                'materials_count' => count($materialNeeds),
            ]);

            $unitCostFinished = $qtyProduced > 0 ? ($totalMaterialCost / $qtyProduced) : 0;

            $now = Carbon::now();
            $orderNumber = 'PRD-' . $now->format('YmdHis') . '-' . $itemId;
            $rawOutDoc = 'RMO-' . $now->format('YmdHis');
            $fgInDoc = 'FGI-' . $now->format('YmdHis');
            $batchNumber = 'BATCH-' . $now->format('YmdHis');

            $result = DB::transaction(function () use (
                $itemId,
                $branchId,
                $userId,
                $orderNumber,
                $qtyProduced,
                $totalMaterialCost,
                $unitCostFinished,
                $materialNeeds,
                $rawOutDoc,
                $fgInDoc,
                $batchNumber,
                $inventoryId,
                $validated,
                $now
            ) {
                try {
                    $productionOrder = ProductionOrder::create([
                        'item_id' => $itemId,
                        'branch_id' => $branchId,
                        'created_by' => $userId,
                        'approved_by' => $userId,
                        'order_number' => $orderNumber,
                        'qty_planned' => $qtyProduced,
                        'qty_produced' => $qtyProduced,
                        'unit' => 'unit',
                        'status' => 'completed',
                        'planned_date' => $now->toDateString(),
                        'started_at' => $now,
                        'completed_at' => $now,
                        'total_material_cost' => round($totalMaterialCost, 2),
                        'overhead_cost' => 0,
                        'hpp_per_unit' => round($unitCostFinished, 2),
                        'notes' => $validated['notes'] ?? null,
                    ]);

                    Log::info('Production order created', [
                        'production_order_id' => $productionOrder->production_order_id,
                        'order_number' => $orderNumber,
                    ]);

                    foreach ($materialNeeds as $need) {
                        $raw = $need['raw'];
                        $requiredQty = round((float) $need['required_qty'], 1);
                        $stockBefore = (float) ($raw->current_stock ?? 0);
                        $stockAfter = max(0, round($stockBefore - $requiredQty, 1));

                        Log::info('Updating raw material stock', [
                            'item_raw_id' => $raw->item_raw_id,
                            'material_name' => $raw->material_name,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'decrease_by' => $requiredQty,
                        ]);

                        $raw->update([
                            'current_stock' => $stockAfter,
                        ]);

                        // Verify update
                        $raw->refresh();
                        Log::info('Raw material stock verified', [
                            'item_raw_id' => $raw->item_raw_id,
                            'current_stock' => $raw->current_stock,
                        ]);

                        RawMaterialOut::create([
                            'item_raw_id' => (int) $raw->item_raw_id,
                            'production_order_id' => (int) $productionOrder->production_order_id,
                            'bom_id' => isset($need['bom']) && $need['bom'] ? (int) $need['bom']->bom_id : null,
                            'branch_id' => $branchId,
                            'issued_by' => $userId,
                            'document_number' => $rawOutDoc,
                            'qty_requested' => $requiredQty,
                            'qty_issued' => $requiredQty,
                            'unit' => $raw->unit,
                            'unit_cost' => round((float) $need['unit_cost'], 1),
                            'total_cost' => round((float) $need['total_cost'], 2),
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'reason' => 'production',
                            'issued_date' => $now->toDateString(),
                            'notes' => 'Auto produksi dari dashboard buffer stock.',
                        ]);

                        Log::info('Raw material out created', [
                            'item_raw_id' => $raw->item_raw_id,
                        ]);
                    }

                    $finishedStock = MasterItemStock::where('item_id', $itemId)
                        ->where('inventory_id', $inventoryId)
                        ->first();

                    $stockBeforeFinished = (int) ($finishedStock->stock ?? 0);
                    $stockAfterFinished = $stockBeforeFinished + $qtyProduced;

                    Log::info('Updating finished goods stock', [
                        'item_id' => $itemId,
                        'inventory_id' => $inventoryId,
                        'stock_before' => $stockBeforeFinished,
                        'stock_after' => $stockAfterFinished,
                        'increase_by' => $qtyProduced,
                    ]);

                    if ($finishedStock) {
                        $finishedStock->update([
                            'stock' => $stockAfterFinished,
                        ]);
                        // Verify update
                        $finishedStock->refresh();
                        Log::info('Finished goods stock updated and verified', [
                            'item_id' => $itemId,
                            'stock' => $finishedStock->stock,
                        ]);
                    } else {
                        MasterItemStock::create([
                            'item_id' => $itemId,
                            'inventory_id' => $inventoryId,
                            'stock' => $stockAfterFinished,
                            'buffer_stock' => 0,
                        ]);
                        Log::info('New finished goods stock created', [
                            'item_id' => $itemId,
                            'stock' => $stockAfterFinished,
                        ]);
                    }

                    // Generate production code
                    try {
                        $productionCodeService = new ProductionCodeService();
                        $productionCode = $productionCodeService->generateProductionCode($itemId, $branchId);
                    } catch (\Exception $e) {
                        Log::error('Production Code Generation Error: ' . $e->getMessage(), [
                            'item_id' => $itemId,
                            'branch_id' => $branchId,
                            'exception' => $e
                        ]);
                        throw new \Exception(
                            "Gagal generate kode produksi. " . $e->getMessage()
                        );
                    }

                    FinishedGoodsIn::create([
                        'item_id' => $itemId,
                        'production_order_id' => (int) $productionOrder->production_order_id,
                        'inventory_id' => $inventoryId,
                        'branch_id' => $branchId,
                        'received_by' => $userId,
                        'document_number' => $fgInDoc,
                        'batch_number' => $batchNumber,
                        'production_code' => $productionCode,
                        'qty_received' => $qtyProduced,
                        'unit' => 'unit',
                        'unit_cost' => round($unitCostFinished, 1),
                        'total_cost' => round($totalMaterialCost, 2),
                        'stock_before' => $stockBeforeFinished,
                        'stock_after' => $stockAfterFinished,
                        'production_date' => $now->toDateString(),
                        'received_date' => $now->toDateString(),
                        'qc_status' => 'passed',
                        'qc_notes' => 'Auto pass dari produksi dashboard.',
                        'notes' => 'Auto produksi dari dashboard buffer stock.',
                    ]);

                    Log::info('Finished goods in created', [
                        'item_id' => $itemId,
                        'qty_received' => $qtyProduced,
                        'production_code' => $productionCode,
                    ]);

                    return [
                        'production_order_id' => (int) $productionOrder->production_order_id,
                        'qty_produced' => $qtyProduced,
                        'total_material_cost' => round($totalMaterialCost, 2),
                        'hpp_per_unit' => round($unitCostFinished, 2),
                        'stock_after_finished' => $stockAfterFinished,
                        'materials_consumed' => count($materialNeeds),
                    ];
                } catch (\Exception $e) {
                    Log::error('Error in transaction', [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }, 5);

            return response()->json([
                'success' => true,
                'message' => 'Produksi berhasil! Diproduksi ' . $qtyProduced . ' unit. ' . count($materialNeeds) . ' bahan baku dikurangi dari stok dan stok produk jadi bertambah ' . $qtyProduced . ' unit.',
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', array_reduce($e->errors(), function($carry, $item) { return array_merge($carry, (array)$item); }, [])),
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Production error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildProductionRecipeForItem(int $itemId, int $qtyProduced): array
    {
        $item = MasterItem::find($itemId);
        if (!$item) {
            return ['source' => 'none', 'materials' => [], 'max_producible' => 0];
        }

        $recipes = $this->parseProductionRecipesFromCsv();
        $matchedRecipe = $this->matchCsvRecipeToItem($item, $recipes);

        if ($matchedRecipe) {
            $rawMaterials = MasterItemRawMaterial::all();
            $materials = [];
            $maxProducible = null;

            foreach ($matchedRecipe['ingredients'] as $ingredient) {
                $raw = $this->matchRawMaterialByName((string) ($ingredient['material_name'] ?? ''), $rawMaterials);
                
                $requiredPerUnit = (float) ($ingredient['required_per_unit'] ?? 0);
                $csvUnit = (string) ($ingredient['unit'] ?? '');
                
                if ($requiredPerUnit <= 0) {
                    continue;
                }

                // Convert CSV unit to database unit before calculation
                if ($raw && $csvUnit !== '') {
                    $dbUnit = (string) ($raw->unit ?? '');
                    $requiredPerUnit = $this->convertUnitTo($requiredPerUnit, $csvUnit, $dbUnit);
                }

                $requiredPerUnit = round($requiredPerUnit, 1);

                if ($raw) {
                    $stockNow = (float) ($raw->current_stock ?? 0);
                    $maxByRaw = (int) floor($stockNow / $requiredPerUnit);
                    $maxProducible = is_null($maxProducible) ? $maxByRaw : min($maxProducible, $maxByRaw);
                } else {
                    $maxProducible = 0;
                }

                $materials[] = [
                    'bom' => null,
                    'raw' => $raw,
                    'material_name' => (string) ($raw->material_name ?? ($ingredient['material_name'] ?? '-')),
                    'unit' => (string) ($raw->unit ?? 'unit'),
                    'required_per_unit' => $requiredPerUnit,
                    'required_qty' => round($requiredPerUnit * $qtyProduced, 1),
                ];
            }

            // Deduplicate materials by item_raw_id to prevent duplicate entries
            $seenRawIds = [];
            $materials = array_filter($materials, function ($mat) use (&$seenRawIds) {
                $rawId = $mat['raw'] ? (int) $mat['raw']->item_raw_id : null;
                if (!$rawId || in_array($rawId, $seenRawIds)) {
                    return false; // Skip duplicates
                }
                $seenRawIds[] = $rawId;
                return true;
            });
            $materials = array_values($materials); // Re-index array

            if (!empty($materials)) {
                return [
                    'source' => 'csv',
                    'materials' => $materials,
                    'max_producible' => max(0, (int) ($maxProducible ?? 0)),
                ];
            }
        }

        $bomRows = MasterItemBillOfMaterials::with('rawMaterial')
            ->where('item_id', $itemId)
            ->get();

        if ($bomRows->isEmpty()) {
            return ['source' => 'none', 'materials' => [], 'max_producible' => 0];
        }

        $materials = [];
        $maxProducible = null;

        foreach ($bomRows as $bom) {
            $raw = $bom->rawMaterial;
            if (!$raw) {
                continue;
            }

            $requiredPerUnit = (float) $bom->quantity_required;
            $yield = (float) ($bom->yield_percentage ?? 0);

            if ($yield > 0 && $yield < 100) {
                $requiredPerUnit = $requiredPerUnit / ($yield / 100);
            }

            $requiredPerUnit = round($requiredPerUnit, 1);
            if ($requiredPerUnit <= 0) {
                continue;
            }

            $stockNow = (float) ($raw->current_stock ?? 0);
            $maxByRaw = (int) floor($stockNow / $requiredPerUnit);
            $maxProducible = is_null($maxProducible) ? $maxByRaw : min($maxProducible, $maxByRaw);

            $materials[] = [
                'bom' => $bom,
                'raw' => $raw,
                'material_name' => (string) ($raw->material_name ?? '-'),
                'unit' => (string) ($raw->unit ?? '-'),
                'required_per_unit' => $requiredPerUnit,
                'required_qty' => round($requiredPerUnit * $qtyProduced, 1),
            ];
        }

        return [
            'source' => 'bom',
            'materials' => $materials,
            'max_producible' => max(0, (int) ($maxProducible ?? 0)),
        ];
    }

    private function parseProductionRecipesFromCsv(): array
    {
        $path = base_path('python/Kalkulator_Produksi_Sesuai_Excel_update.csv');
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $recipes = [];
        $currentProduct = null;
        $inIngredientSection = false;

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            $col0 = trim((string) ($row[0] ?? ''));
            $col1 = trim((string) ($row[1] ?? ''));

            if ($col0 === '' && $col1 === '') {
                continue;
            }

            if (stripos($col0, 'Produk:') === 0) {
                $productName = trim(substr($col0, strlen('Produk:')));
                $currentProduct = $productName;
                $recipes[$currentProduct] = [
                    'product_name' => $productName,
                    'ingredients' => [],
                ];
                $inIngredientSection = false;
                continue;
            }

            if (!$currentProduct) {
                continue;
            }

            if (strcasecmp($col0, 'Bahan') === 0) {
                $inIngredientSection = true;
                continue;
            }

            if (!$inIngredientSection) {
                continue;
            }

            if ($col0 === '' || stripos($col0, 'Potensi Hasil Produk') === 0) {
                continue;
            }

            $parsedData = $this->parseValueWithUnit($col1);
            if (!$parsedData) {
                continue;
            }

            $requiredPerUnit = $parsedData['value'];
            $unit = $parsedData['unit'];

            $recipes[$currentProduct]['ingredients'][] = [
                'material_name' => $col0,
                'required_per_unit' => $requiredPerUnit,
                'unit' => $unit,
            ];
        }

        return array_values(array_filter($recipes, function ($recipe) {
            return !empty($recipe['ingredients']);
        }));
    }

    private function matchCsvRecipeToItem(MasterItem $item, array $recipes): ?array
    {
        $itemName = strtoupper((string) ($item->name_item ?? ''));
        $itemCode = strtoupper((string) ($item->code_item ?? ''));
        $itemVolume = $this->extractMlValue($itemName . ' ' . $itemCode);

        foreach ($recipes as $recipe) {
            $productName = strtoupper((string) ($recipe['product_name'] ?? ''));
            [$recipeCode, $recipeVolume] = $this->extractRecipeProductCodeAndVolume($productName);

            if ($recipeCode === '') {
                continue;
            }

            $codeMatched = str_contains($itemName, $recipeCode)
                || str_contains($itemCode, $recipeCode);

            if (!$codeMatched) {
                continue;
            }

            if ($recipeVolume !== null && $itemVolume !== null) {
                if (abs($recipeVolume - $itemVolume) > 0.01) {
                    continue;
                }
            }

            return $recipe;
        }

        return null;
    }

    private function matchRawMaterialByName(string $csvMaterialName, $rawMaterials): ?MasterItemRawMaterial
    {
        $needle = $this->normalizeText($csvMaterialName);
        if ($needle === '') {
            return null;
        }

        foreach ($rawMaterials as $raw) {
            $name = $this->normalizeText((string) ($raw->material_name ?? ''));
            if ($name === $needle) {
                return $raw;
            }
        }

        foreach ($rawMaterials as $raw) {
            $name = $this->normalizeText((string) ($raw->material_name ?? ''));
            if (str_contains($name, $needle) || str_contains($needle, $name)) {
                return $raw;
            }
        }

        return null;
    }

    /**
     * Parse value and unit from text
     * e.g., "2 liter" → ['value' => 2.0, 'unit' => 'liter']
     * e.g., "0.15 ml" → ['value' => 0.15, 'unit' => 'ml']
     * e.g., "5" → ['value' => 5.0, 'unit' => '']
     */
    private function parseValueWithUnit(string $text): ?array
    {
        if ($text === '') {
            return null;
        }

        // Match: number (optional decimals) + optional space + optional unit
        if (!preg_match('/(\d+(?:[\.,]\d+)?)\s*([a-zA-Z]*)/i', $text, $matches)) {
            return null;
        }

        $value = (float) str_replace(',', '.', $matches[1]);
        if ($value <= 0) {
            return null;
        }

        $unit = strtolower(trim($matches[2] ?? ''));

        return [
            'value' => $value,
            'unit' => $unit,
        ];
    }

    /**
     * Convert value from one unit to another
     * Supports: volume (liter/l/ml) and weight (kg/g)
     * Returns converted value, or original value if units are incompatible
     */
    private function convertUnitTo(float $value, string $fromUnit, string $toUnit): float
    {
        $from = strtolower(trim($fromUnit));
        $to = strtolower(trim($toUnit));

        // If units are the same, no conversion
        if ($from === $to) {
            return $value;
        }

        // Volume conversions (normalize to ml)
        $volumeUnits = ['liter', 'l', 'litre', 'ml', 'milliliter', 'millilitre', 'cc'];
        $isFromVolume = in_array($from, $volumeUnits);
        $isToVolume = in_array($to, $volumeUnits);

        if ($isFromVolume && $isToVolume) {
            // Convert from unit to ml first
            $valueInMl = $value;
            if (in_array($from, ['liter', 'l', 'litre'])) {
                $valueInMl = $value * 1000;
            }

            // Convert from ml to target unit
            if (in_array($to, ['liter', 'l', 'litre'])) {
                return $valueInMl / 1000;
            }
            return $valueInMl; // Return as ml
        }

        // Weight conversions (normalize to gram)
        $weightUnits = ['kg', 'kilogram', 'g', 'gram'];
        $isFromWeight = in_array($from, $weightUnits);
        $isToWeight = in_array($to, $weightUnits);

        if ($isFromWeight && $isToWeight) {
            // Convert from unit to gram first
            $valueInGram = $value;
            if (in_array($from, ['kg', 'kilogram'])) {
                $valueInGram = $value * 1000;
            }

            // Convert from gram to target unit
            if (in_array($to, ['kg', 'kilogram'])) {
                return $valueInGram / 1000;
            }
            return $valueInGram; // Return as gram
        }

        // If units are incompatible (e.g., liter to kg), return original value
        // This case would indicate a data error, but we're lenient
        return $value;
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower($text);
        return preg_replace('/[^a-z0-9]/', '', $text) ?? '';
    }

    private function parseDecimalValue(string $text): ?float
    {
        if ($text === '') {
            return null;
        }

        if (!preg_match('/\d+(?:[\.,]\d+)?/', $text, $matches)) {
            return null;
        }

        $value = str_replace(',', '.', $matches[0]);
        return is_numeric($value) ? (float) $value : null;
    }

    private function extractMlValue(string $text): ?float
    {
        if (!preg_match('/(\d+(?:[\.,]\d+)?)\s*ml/i', $text, $matches)) {
            return null;
        }

        $value = str_replace(',', '.', $matches[1]);
        return is_numeric($value) ? (float) $value : null;
    }

    private function extractRecipeProductCodeAndVolume(string $productName): array
    {
        if (preg_match('/([A-Z0-9]+)\s*-\s*(\d+(?:[\.,]\d+)?)\s*ML/i', $productName, $matches)) {
            $code = strtoupper(trim($matches[1]));
            $volume = (float) str_replace(',', '.', $matches[2]);
            return [$code, $volume];
        }

        if (preg_match('/([A-Z0-9]+)/i', $productName, $matches)) {
            return [strtoupper(trim($matches[1])), null];
        }

        return ['', null];
    }

    /**
     * POST: /admin/inventory/buffer-stock/sync
     * Sync all buffer stocks calculations to database
     */
    public function syncBufferStocks()
    {
        try {
            $service = new BufferStockCalculationService();
            $result = $service->syncAllBufferStocks();

            // Check if all items failed
            if ($result['updated'] === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Semua {$result['total_materials']} bahan gagal diperbarui. Silakan periksa logs untuk detail error.",
                    'data' => $result
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil! {$result['updated']} dari {$result['total_materials']} bahan diperbarui.",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Buffer stock sync error: " . $e->getMessage(), $e->getTrace());
            
            return response()->json([
                'success' => false,
                'message' => "Error sinkronisasi: " . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/items-to-order
     * Get list of items that need to be ordered
     */
    public function itemsToOrder()
    {
        $service = new BufferStockCalculationService();

        $materials = MasterItemRawMaterial::where('stock_status', 'critical')
            ->orWhere(function ($q) {
                $q->where('stock_status', 'low')->where('current_stock', '<', 100);
            })
            ->get();

        $itemsNeedingOrder = $materials->map(function ($material) use ($service) {
            $calculation = $service->calculateBufferStock($material->item_raw_id);
            $shortageQty = $calculation['reorder_point'] - $material->current_stock;

            return array_merge($calculation, [
                'shortage_quantity' => max(0, $shortageQty),
                'estimated_cost' => max(0, $shortageQty) * $material->purchase_price,
                'min_order_qty' => max($calculation['min_reorder_qty'], round($shortageQty, 0))
            ]);
        });

        $summary = [
            'total_items_low' => $itemsNeedingOrder->count(),
            'total_shortage_qty' => $itemsNeedingOrder->sum('shortage_quantity'),
            'total_estimated_cost' => $itemsNeedingOrder->sum('estimated_cost')
        ];

        return response()->json([
            'success' => true,
            'items' => $itemsNeedingOrder,
            'summary' => $summary
        ]);
    }

    /**
     * POST: /admin/inventory/buffer-stock/sync-from-csv
     * Sync buffer stock calculations from CSV to database
     */
    public function syncBufferStockFromCSV(Request $request)
    {
        $analysisService = new InventoryAnalysisService();

        try {
            $csvPath = storage_path('app/python/master_items_raw_material.csv');
            if (!file_exists($csvPath)) {
                $csvPath = base_path('python/master_items_raw_material.csv');
            }
            if (!file_exists($csvPath)) {
                $csvPath = public_path('master_items_raw_material.csv');
            }

            // Import and process CSV data
            $csvData = $analysisService->importFromCSV($csvPath);
            $processedData = $analysisService->processAllItems($csvData);

            // Sync to database (using updateOrCreate, so all should succeed)
            $result = $analysisService->syncToDatabase($processedData);

            // Log sync details
            Log::info('Buffer Stock CSV Sync', [
                'synced' => $result['synced'],
                'failed' => $result['failed'],
                'total' => $result['total'],
                'timestamp' => now()
            ]);

            // Check if all succeeded
            if ($result['failed'] === 0) {
                return response()->json([
                    'success' => true,
                    'message' => "✅ Sinkronisasi berhasil! Semua {$result['total']} bahan telah diperbarui.",
                    'data' => $result,
                    'redirect_url' => route('admin.inventory.buffer-stock.raw-materials')
                ]);
            } else {
                // Some items failed - log but still return success with warning
                $failedItems = array_filter($result['details'], fn($d) => $d['status'] !== 'success');
                
                Log::warning('Some items failed during sync', [
                    'synced' => $result['synced'],
                    'failed' => $result['failed'],
                    'failed_items' => array_slice($failedItems, 0, 5)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "⚠️ Sinkronisasi selesai dengan peringatan: {$result['synced']} berhasil, {$result['failed']} gagal dari {$result['total']} bahan. Periksa logs untuk detail.",
                    'data' => $result,
                    'redirect_url' => route('admin.inventory.buffer-stock.raw-materials')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Buffer Stock CSV Sync Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sinkronisasi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/export-analysis
     * Export inventory analysis to Excel
     */
    public function exportInventoryAnalysis()
    {
        $analysisService = new InventoryAnalysisService();

        try {
            $csvPath = storage_path('app/python/master_items_raw_material.csv');
            if (!file_exists($csvPath)) {
                $csvPath = base_path('python/master_items_raw_material.csv');
            }
            if (!file_exists($csvPath)) {
                $csvPath = public_path('master_items_raw_material.csv');
            }

            // Import and process CSV data
            $csvData = $analysisService->importFromCSV($csvPath);
            $processedData = $analysisService->processAllItems($csvData);
            $exportData = $analysisService->exportAnalysis($processedData);

            // Create Excel file
            $filename = 'inventory_analysis_' . now()->format('Ymd_His') . '.csv';
            
            $file = fopen('php://memory', 'w');
            fputcsv($file, array_keys($exportData[0]));
            
            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }
            
            rewind($file);
            $content = stream_get_contents($file);
            fclose($file);

            return response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\""
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error exporting: ' . $e->getMessage());
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/import-status
     * Show import status from CSV
     */
    public function bufferStockImportStatus()
    {
        $analysisService = new InventoryAnalysisService();

        try {
            $csvPath = storage_path('app/python/master_items_raw_material.csv');
            if (!file_exists($csvPath)) {
                $csvPath = base_path('python/master_items_raw_material.csv');
            }
            if (!file_exists($csvPath)) {
                $csvPath = public_path('master_items_raw_material.csv');
            }

            // Import and process CSV data
            $csvData = $analysisService->importFromCSV($csvPath);
            $processedData = $analysisService->processAllItems($csvData);
            $summary = $analysisService->generateSummary($processedData);
            $criticalItems = $analysisService->getCriticalItems($processedData);

            return view('admin_inventory.buffer_stock_import_status', compact(
                'summary',
                'criticalItems',
                'csvPath'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to determine stock status
     */
    private function determineStockStatus($currentStock, $bufferStock)
    {
        if ($currentStock <= 0) {
            return 'critical';
        } elseif ($currentStock < $bufferStock) {
            return 'low';
        } else {
            return 'normal';
        }
    }

    /**
     * POST: Update Buffer Stock dari CSV ROP values
     * Menjalankan update ROP ke database tanpa script Python
     */
    public function updateBufferStockFromRop(Request $request)
    {
        try {
            // Check user authorization
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda harus login terlebih dahulu'
                ], 401);
            }

            // Check if user has required role or permission
            $hasAccess = false;
            if (method_exists($user, 'hasRole')) {
                $hasAccess = $user->hasRole(['owner', 'admin_inventory']);
            }
            if (!$hasAccess && method_exists($user, 'hasPermissionTo')) {
                $hasAccess = $user->hasPermissionTo('update-finished-goods');
            }

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk update buffer stock'
                ], 403);
            }

            $inventoryId = (int)$request->get('inventory_id', 1);

            // Step 1: Try using Python service (Solusi 2) untuk hasil lebih akurat
            Log::info("Attempting to update buffer stock via Python service");
            
            $pythonService = new \App\Services\PythonBufferStockService();
            $result = $pythonService->executeUpdate($inventoryId);

            // Step 2: Fallback ke ROPBufferStockUpdaterService jika Python tidak tersedia
            if (!$result['success'] && (
                strpos($result['message'], 'Python') !== false || 
                strpos($result['message'], 'tidak ditemukan') !== false ||
                strpos($result['message'], 'script') !== false
            )) {
                Log::warning("Python service failed, falling back to ROP CSV service: " . $result['message']);
                
                $ropService = new \App\Services\ROPBufferStockUpdaterService(
                    'buffer_stock_per_produk.csv',
                    'product_mapping.json'
                );
                $result = $ropService->updateBufferStock($inventoryId);
            }

            // Sanitize result untuk ensure format yang benar
            $result = $this->sanitizeBufferStockResult($result);

            if ($result['success']) {
                Log::info('Buffer stock updated successfully');
                Log::info('Updated: ' . $result['updated'] . ', Skipped: ' . $result['skipped'] . ', Errors: ' . $result['errors']);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'updated' => $result['updated'],
                        'skipped' => $result['skipped'],
                        'errors' => $result['errors']
                    ]
                ]);
            } else {
                Log::warning('Buffer stock update incomplete: ' . $result['message']);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => [
                        'updated' => $result['updated'],
                        'skipped' => $result['skipped'],
                        'errors' => $result['errors']
                    ]
                ], 400);
            }

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            
            Log::error('Error updating buffer stock: ' . $errorMsg);
            Log::debug('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $errorMsg
            ], 500);
        }
    }

    /**
     * Sanitize result dari buffer stock update services
     * Memastikan semua values adalah scalar (bukan array/object)
     */
    private function sanitizeBufferStockResult(array $result): array
    {
        return [
            'success' => (bool)($result['success'] ?? false),
            'message' => (string)($result['message'] ?? 'Unknown error'),
            'updated' => (int)($result['updated'] ?? $result['updated_count'] ?? 0),
            'skipped' => (int)($result['skipped'] ?? $result['not_found_count'] ?? 0),
            'errors' => (int)($result['errors'] ?? $result['error_count'] ?? 0),
        ];
    }

    /**
     * GET: /admin/inventory/buffer-stock/export
     * Export buffer stock raw materials ke file Excel
     */
    public function exportBufferStockExcel()
    {
        try {
            $service = new \App\Services\BufferStockExcelService();
            $filePath = $service->exportToExcel();

            return response()->download($filePath, 'buffer_stock_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error exporting buffer stock: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengekspor file: ' . $e->getMessage());
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/download-template
     * Download template Excel untuk import buffer stock
     */
    public function downloadBufferStockTemplate()
    {
        try {
            $service = new \App\Services\BufferStockExcelService();
            $filePath = $service->createImportTemplate();

            return response()->download($filePath, 'buffer_stock_template.xlsx')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error downloading template: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengunduh template: ' . $e->getMessage());
        }
    }

    /**
     * POST: /admin/inventory/buffer-stock/import
     * Import buffer stock raw materials dari file Excel
     */
    public function importBufferStockExcel(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:5120' // Max 5MB
            ]);

            $file = $request->file('file');
            $filePath = $file->store('imports', 'local');
            $fullPath = storage_path('app/' . $filePath);

            $service = new \App\Services\BufferStockExcelService();
            $result = $service->importFromExcel($fullPath);

            // Clean up uploaded file
            @unlink($fullPath);

            // Return JSON response instead of redirect
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error importing buffer stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimpor file: ' . $e->getMessage()
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  RAW MATERIAL CRUD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * POST: /admin/inventory/buffer-stock/raw-materials
     * Create new raw material
     */
    public function storeBufferStockRawMaterial(Request $request)
    {
        try {
            $validated = $request->validate([
                'material_name'  => 'required|string|max:255',
                'unit'           => 'required|string|max:50',
                'purchase_price' => 'required|numeric|min:0',
                'current_stock'  => 'required|numeric|min:0',
                'lead_time_days' => 'required|integer|min:0|max:365',
                'buffer_stock'   => 'required|numeric|min:0',
                'supplier_name'  => 'nullable|string|max:255',
            ]);

            $raw = MasterItemRawMaterial::create($validated);

            return response()->json([
                'success' => true,
                'message' => "Bahan baku \"{$raw->material_name}\" berhasil ditambahkan.",
                'data'    => $raw,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('storeBufferStockRawMaterial error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PUT: /admin/inventory/buffer-stock/raw-materials/{itemRawId}
     * Update existing raw material
     */
    public function updateBufferStockRawMaterial(Request $request, $itemRawId)
    {
        try {
            $raw = MasterItemRawMaterial::findOrFail($itemRawId);

            $validated = $request->validate([
                'material_name'  => 'required|string|max:255',
                'unit'           => 'required|string|max:50',
                'purchase_price' => 'required|numeric|min:0',
                'current_stock'  => 'required|numeric|min:0',
                'lead_time_days' => 'required|integer|min:0|max:365',
                'buffer_stock'   => 'required|numeric|min:0',
                'supplier_name'  => 'nullable|string|max:255',
            ]);

            $raw->update($validated);

            return response()->json([
                'success' => true,
                'message' => "Bahan baku \"{$raw->material_name}\" berhasil diperbarui.",
                'data'    => $raw,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Bahan baku tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('updateBufferStockRawMaterial error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE: /admin/inventory/buffer-stock/raw-materials/{itemRawId}
     * Soft-delete raw material
     */
    public function destroyBufferStockRawMaterial($itemRawId)
    {
        try {
            $raw = MasterItemRawMaterial::findOrFail($itemRawId);
            $name = $raw->material_name;

            // Cek apakah masih dipakai di BOM
            $bomCount = MasterItemBillOfMaterials::where('item_raw_id', $itemRawId)->count();
            if ($bomCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Bahan baku \"{$name}\" masih digunakan di {$bomCount} resep BOM. Hapus resep BOM terlebih dahulu.",
                ], 422);
            }

            $raw->delete();

            return response()->json([
                'success' => true,
                'message' => "Bahan baku \"{$name}\" berhasil dihapus.",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Bahan baku tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('destroyBufferStockRawMaterial error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  BOM (BILL OF MATERIALS) CRUD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * GET: /admin/inventory/finished-goods/{itemId}/bom
     * List BOM entries for a finished-good item
     */
    public function getBomForItem($itemId)
    {
        try {
            $item = MasterItem::find($itemId);
            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
            }

            $boms = MasterItemBillOfMaterials::with('rawMaterial')
                ->where('item_id', $itemId)
                ->get()
                ->map(fn($b) => [
                    'bom_id'            => $b->bom_id,
                    'item_raw_id'       => $b->item_raw_id,
                    'material_name'     => $b->rawMaterial?->material_name ?? '-',
                    'unit'              => $b->rawMaterial?->unit ?? '-',
                    'current_stock'     => (float) ($b->rawMaterial?->current_stock ?? 0),
                    'quantity_required' => (float) $b->quantity_required,
                    'yield_percentage'  => (float) ($b->yield_percentage ?? 100),
                ]);

            $allRaws = MasterItemRawMaterial::select('item_raw_id', 'material_name', 'unit', 'current_stock')
                ->orderBy('material_name')
                ->get();

            return response()->json([
                'success'      => true,
                'item_id'      => $item->item_id,
                'item_name'    => $item->name_item,
                'code_item'    => $item->code_item,
                'bom_entries'  => $boms,
                'raw_materials' => $allRaws,
            ]);
        } catch (\Exception $e) {
            Log::error('getBomForItem error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat BOM: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST: /admin/inventory/finished-goods/{itemId}/bom
     * Add a new BOM entry
     */
    public function storeBomItem(Request $request, $itemId)
    {
        try {
            $item = MasterItem::findOrFail($itemId);

            $validated = $request->validate([
                'item_raw_id'       => 'required|integer|exists:master_items_raw_material,item_raw_id',
                'quantity_required' => 'required|numeric|min:0.001',
                'yield_percentage'  => 'nullable|numeric|min:0|max:100',
            ]);

            // Cek duplikasi bahan baku di BOM yang sama
            $exists = MasterItemBillOfMaterials::where('item_id', $itemId)
                ->where('item_raw_id', $validated['item_raw_id'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bahan baku ini sudah ada dalam resep BOM produk tersebut.',
                ], 422);
            }

            $bom = MasterItemBillOfMaterials::create([
                'item_id'           => (int) $itemId,
                'item_raw_id'       => (int) $validated['item_raw_id'],
                'quantity_required' => (float) $validated['quantity_required'],
                'yield_percentage'  => (float) ($validated['yield_percentage'] ?? 100),
            ]);

            $bom->load('rawMaterial');

            return response()->json([
                'success' => true,
                'message' => "Resep BOM untuk \"{$item->name_item}\" berhasil ditambahkan.",
                'data'    => [
                    'bom_id'            => $bom->bom_id,
                    'item_raw_id'       => $bom->item_raw_id,
                    'material_name'     => $bom->rawMaterial?->material_name ?? '-',
                    'unit'              => $bom->rawMaterial?->unit ?? '-',
                    'current_stock'     => (float) ($bom->rawMaterial?->current_stock ?? 0),
                    'quantity_required' => (float) $bom->quantity_required,
                    'yield_percentage'  => (float) $bom->yield_percentage,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('storeBomItem error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menambah BOM: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PUT: /admin/inventory/bom/{bomId}
     * Update a BOM entry
     */
    public function updateBomItem(Request $request, $bomId)
    {
        try {
            $bom = MasterItemBillOfMaterials::with('rawMaterial')->findOrFail($bomId);

            $validated = $request->validate([
                'quantity_required' => 'required|numeric|min:0.001',
                'yield_percentage'  => 'nullable|numeric|min:0|max:100',
            ]);

            $bom->update([
                'quantity_required' => (float) $validated['quantity_required'],
                'yield_percentage'  => (float) ($validated['yield_percentage'] ?? 100),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Resep BOM berhasil diperbarui.",
                'data'    => [
                    'bom_id'            => $bom->bom_id,
                    'material_name'     => $bom->rawMaterial?->material_name ?? '-',
                    'quantity_required' => (float) $bom->quantity_required,
                    'yield_percentage'  => (float) $bom->yield_percentage,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Entry BOM tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('updateBomItem error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui BOM: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE: /admin/inventory/bom/{bomId}
     * Delete a BOM entry
     */
    public function destroyBomItem($bomId)
    {
        try {
            $bom = MasterItemBillOfMaterials::with('rawMaterial')->findOrFail($bomId);
            $name = $bom->rawMaterial?->material_name ?? "ID {$bomId}";
            $bom->delete();

            return response()->json([
                'success' => true,
                'message' => "Resep bahan \"{$name}\" berhasil dihapus dari BOM.",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Entry BOM tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('destroyBomItem error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus BOM: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  PROCUREMENT CALCULATOR
    // ═══════════════════════════════════════════════════════════════════

    /**
     * GET: /admin/inventory/procurement-calculator
     * Hitung kebutuhan pembelian bahan baku berdasarkan ARIMA forecast
     */
    public function procurementCalculator(Request $request)
    {
        try {
            // Ambil semua produk yang punya forecast
            $forecastSummaries = DB::table('arima_forecast_summaries as afs')
                ->leftJoin('master_items as mi', 'mi.code_item', '=', 'afs.produk')
                ->select(['afs.produk', 'mi.item_id', 'mi.name_item', 'mi.code_item'])
                ->get();

            $results = [];

            foreach ($forecastSummaries as $summary) {
                $itemId = $summary->item_id;
                if (!$itemId) {
                    continue;
                }

                // Ambil forecast bulan depan (data_type = 'forecast')
                $forecastRows = DB::table('arima_forecast_details')
                    ->where('produk', $summary->produk)
                    ->where('data_type', 'forecast')
                    ->orderBy('date', 'asc')
                    ->get();

                $totalForecastQty = $forecastRows->sum('predicted_sales');

                if ($totalForecastQty <= 0) {
                    continue;
                }

                // Ambil BOM untuk produk ini
                $bomEntries = MasterItemBillOfMaterials::with('rawMaterial')
                    ->where('item_id', $itemId)
                    ->get();

                if ($bomEntries->isEmpty()) {
                    continue;
                }

                // Hitung stok produk jadi saat ini
                $currentFinishedStock = MasterItemStock::where('item_id', $itemId)->sum('stock');

                // Qty produk jadi yang perlu diproduksi = forecast - stok saat ini (min 0)
                $qtyToProduce = max(0, $totalForecastQty - $currentFinishedStock);

                $rawNeeds = [];
                foreach ($bomEntries as $bom) {
                    $raw = $bom->rawMaterial;
                    if (!$raw) {
                        continue;
                    }

                    $yield = (float) ($bom->yield_percentage ?? 100);
                    $requiredPerUnit = (float) $bom->quantity_required;
                    if ($yield > 0 && $yield < 100) {
                        $requiredPerUnit = $requiredPerUnit / ($yield / 100);
                    }

                    $totalRequired  = round($requiredPerUnit * $qtyToProduce, 2);
                    $currentStock   = (float) ($raw->current_stock ?? 0);
                    $bufferStock    = (float) ($raw->buffer_stock ?? 0);
                    $toOrder        = max(0, round($totalRequired + $bufferStock - $currentStock, 2));
                    $unitCost       = (float) ($raw->purchase_price ?? 0);

                    $rawNeeds[] = [
                        'item_raw_id'      => $raw->item_raw_id,
                        'material_name'    => $raw->material_name,
                        'unit'             => $raw->unit ?? '-',
                        'quantity_required_per_unit' => round($requiredPerUnit, 3),
                        'total_required'   => $totalRequired,
                        'current_stock'    => $currentStock,
                        'buffer_stock'     => $bufferStock,
                        'qty_to_order'     => $toOrder,
                        'unit_cost'        => $unitCost,
                        'estimated_cost'   => round($toOrder * $unitCost, 2),
                    ];
                }

                if (!empty($rawNeeds)) {
                    $results[] = [
                        'item_id'               => $itemId,
                        'code_item'             => $summary->produk,
                        'name_item'             => $summary->name_item ?? $summary->produk,
                        'total_forecast_qty'    => round($totalForecastQty, 2),
                        'current_finished_stock'=> $currentFinishedStock,
                        'qty_to_produce'        => round($qtyToProduce, 2),
                        'raw_material_needs'    => $rawNeeds,
                        'total_procurement_cost'=> round(collect($rawNeeds)->sum('estimated_cost'), 2),
                    ];
                }
            }

            // Sort by total_procurement_cost descending
            usort($results, fn($a, $b) => $b['total_procurement_cost'] <=> $a['total_procurement_cost']);

            return response()->json([
                'success'          => true,
                'total_products'   => count($results),
                'total_cost'       => round(collect($results)->sum('total_procurement_cost'), 2),
                'products'         => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('procurementCalculator error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghitung kebutuhan pengadaan: ' . $e->getMessage()], 500);
        }
    }
}


