@extends('layouts.admin_inventory.app')

@section('title', 'Production Overview')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2"> Production Overview</h1>
                <p class="text-lg text-gray-600">Pantau alur produksi, raw material, dan finished goods dalam {{ $summary['period_days'] }} hari terakhir</p>
            </div>
<!--             
            <form action="{{ route('admin.inventory.production.overview') }}" method="GET" class="w-full md:w-1/3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="bi bi-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ $search ?? '' }}" id="live-search" placeholder="Cari produksi..." class="pl-10 w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 py-2.5">
                </div>
            </form> -->
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Production Orders</p>
                        <p class="text-3xl font-bold text-orange-600">{{ $summary['total_production_orders'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Raw Material In</p>
                        <p class="text-3xl font-bold text-blue-600">{{ number_format($summary['total_raw_material_in'], 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Raw Material Out</p>
                        <p class="text-3xl font-bold text-red-600">{{ number_format($summary['total_raw_material_out'], 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Finished Goods Out</p>
                        <p class="text-3xl font-bold text-green-600">{{ number_format($summary['total_finished_goods_out'], 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="flex border-b border-gray-200 overflow-x-auto">
                <button onclick="showTab('production')" class="flex-1 px-6 py-4 text-center font-semibold text-blue-600 border-b-2 border-blue-600 bg-blue-50 whitespace-nowrap">
                     Production Orders
                </button>
                <button onclick="showTab('rawmaterial')" class="flex-1 px-6 py-4 text-center font-semibold text-gray-600 hover:text-blue-600 whitespace-nowrap">
                     Raw Material Flow
                </button>
                <button onclick="showTab('finished')" class="flex-1 px-6 py-4 text-center font-semibold text-gray-600 hover:text-blue-600 whitespace-nowrap">
                     Finished Goods
                </button>
            </div>
        </div>

        <!-- Tab: Production Orders -->
        <div id="production" class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Tanggal Rencana</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Produk</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Qty Plan</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Tgl Mulai</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Tgl Selesai</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($productionOrders as $po)
                        <tr class="hover:bg-orange-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $po->planned_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900">{{ $po->item->name_item ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $po->item->code_item ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                {{ number_format($po->qty_planned, 0) }}
                            </td>
                            <td class="px-6 py-4">
                                @switch($po->status)
                                    @case('planning')
                                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium"> Planning</span>
                                        @break
                                    @case('in_progress')
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium"> In Progress</span>
                                        @break
                                    @case('completed')
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium"> Completed</span>
                                        @break
                                    @case('cancelled')
                                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium"> Cancelled</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $po->started_at ? $po->started_at->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $po->completed_at ? $po->completed_at->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $po->notes ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                Tidak ada production order dalam periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $productionOrders->appends(request()->query())->links() }}
            </div>
        </div>

        <!-- Tab: Raw Material Flow -->
        <div id="rawmaterial" class="hidden space-y-6 mb-8">
            <!-- Raw Material In -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-4">
                    <h3 class="text-lg font-bold">📥 Raw Material In (Penerimaan)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Bahan Baku</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Jumlah Penerimaan</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Frekuensi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($rawMaterialInSummary as $material)
                            <tr class="hover:bg-blue-50">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $material->rawMaterial->material_name ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-blue-600">
                                    {{ number_format($material->total_received, 2) }} {{ $material->rawMaterial->unit ?? 'unit' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                        {{ $material->receipt_count }}x
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada penerimaan raw material.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Raw Material Out -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4">
                    <h3 class="text-lg font-bold"> Raw Material Out (Penggunaan)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Bahan Baku</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Total Penggunaan</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Frekuensi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($rawMaterialOutSummary as $material)
                            <tr class="hover:bg-red-50">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $material->rawMaterial->material_name ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-red-600">
                                    {{ number_format($material->total_used, 2) }} {{ $material->rawMaterial->unit ?? 'unit' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                                        {{ $material->usage_count }}x
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada penggunaan raw material.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Finished Goods -->
        <div id="finished" class="hidden space-y-6 mb-8">
            <!-- Finished Goods In -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-4">
                    <h3 class="text-lg font-bold"> Finished Goods In (Produksi Selesai)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Produk</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Total Produksi</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Batch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($finishedGoodsIn as $goods)
                            <tr class="hover:bg-green-50">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $goods->item->name_item ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-green-600">
                                    {{ number_format($goods->total_produced, 0) }} unit
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                        {{ $goods->batch_count }}x
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada finished goods received.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Finished Goods Out -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-6 py-4">
                    <h3 class="text-lg font-bold"> Finished Goods Out (Penjualan)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-purple-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Produk</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Total Penjualan</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Transaksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($finishedGoodsOut as $goods)
                            <tr class="hover:bg-purple-50">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $goods->item->name_item ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-purple-600">
                                    {{ number_format($goods->total_sold, 0) }} unit
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                                        {{ $goods->transaction_count }}x
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada penjualan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Production Flow Chart -->
        <div class="bg-white rounded-lg shadow p-6 mt-8">
            <h3 class="text-lg font-bold text-gray-900 mb-6"> Production Flow</h3>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex-1 min-w-[150px] bg-blue-50 p-4 rounded-lg text-center border-l-4 border-blue-500">
                    <p class="font-semibold text-gray-700 mt-2">Raw Material In</p>
                    <p class="text-xl font-bold text-blue-600">{{ number_format($summary['total_raw_material_in'], 0) }}</p>
                </div>
                <div class="text-3xl text-gray-400">→</div>
                <div class="flex-1 min-w-[150px] bg-orange-50 p-4 rounded-lg text-center border-l-4 border-orange-500">
                    <p class="font-semibold text-gray-700 mt-2">Production</p>
                    <p class="text-xl font-bold text-orange-600">{{ $summary['total_production_orders'] }}</p>
                </div>
                <div class="text-3xl text-gray-400">→</div>
                <div class="flex-1 min-w-[150px] bg-green-50 p-4 rounded-lg text-center border-l-4 border-green-500">
                    <p class="font-semibold text-gray-700 mt-2">Finished Goods</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($summary['total_finished_goods_in'], 0) }}</p>
                </div>
                <div class="text-3xl text-gray-400">→</div>
                <div class="flex-1 min-w-[150px] bg-purple-50 p-4 rounded-lg text-center border-l-4 border-purple-500">
                    <p class="font-semibold text-gray-700 mt-2">Sales Out</p>
                    <p class="text-xl font-bold text-purple-600">{{ number_format($summary['total_finished_goods_out'], 0) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    document.getElementById('production').classList.add('hidden');
    document.getElementById('rawmaterial').classList.add('hidden');
    document.getElementById('finished').classList.add('hidden');
    
    document.getElementById(tabName).classList.remove('hidden');
    
    document.querySelectorAll('button[onclick^="showTab"]').forEach(btn => {
        btn.classList.remove('text-blue-600', 'bg-blue-50', 'border-b-2', 'border-blue-600');
        btn.classList.add('text-gray-600');
    });
    
    event.target.classList.remove('text-gray-600');
    event.target.classList.add('text-blue-600', 'bg-blue-50', 'border-b-2', 'border-blue-600');
}

function number_format(num) {
    if (!num) return '0';
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Live Search Debounce
let searchTimeout = null;
const searchInput = document.getElementById('live-search');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 800);
    });
    
    // Auto focus and place cursor at end
    const val = searchInput.value;
    if (val) {
        searchInput.focus();
        searchInput.setSelectionRange(val.length, val.length);
    }
}
</script>
@endsection
