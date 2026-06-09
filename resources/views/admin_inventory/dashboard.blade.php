@extends('layouts.admin_inventory.app')

@section('title', 'Dashboard Inventaris')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard Inventaris</h1>
            <p class="text-sm text-slate-500 mt-1">Ringkasan bahan baku, produksi, dan rantai pasok</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.inventory.forecasting.demand') }}" class="px-4 py-2 bg-[#696cff] text-white rounded-lg hover:bg-[#5f61e6] transition-colors shadow-sm shadow-[#696cff]/30 text-sm font-medium flex items-center">
                <i class="bi bi-graph-up mr-2"></i> Demand Forecast
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1 -->
        <div class="sneat-card p-6 flex items-center justify-between border-l-4 border-[#696cff]">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Total Produk</p>
                <p class="text-2xl font-bold text-slate-800">{{ number_format($totalStock ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $totalItems ?? 0 }} jenis bahan baku</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-[#e7e7ff] text-[#696cff] flex items-center justify-center text-2xl shadow-sm">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="sneat-card p-6 flex items-center justify-between border-l-4 border-[#71dd37]">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Bahan Baku Masuk Bulan Ini</p>
                <p class="text-2xl font-bold text-slate-800">{{ number_format($stockMasukBulanIni ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Rp {{ number_format($nilaiMasukBulanIni ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-[#e8fadf] text-[#71dd37] flex items-center justify-center text-2xl shadow-sm">
                <i class="bi bi-box-arrow-in-down"></i>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="sneat-card p-6 flex items-center justify-between border-l-4 border-[#ffab00]">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Produk Keluar Bulan Ini</p>
                <p class="text-2xl font-bold text-slate-800">{{ number_format($stockKeluarBulanIni ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Rp {{ number_format($nilaiKeluarBulanIni ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-[#fff2d6] text-[#ffab00] flex items-center justify-center text-2xl shadow-sm">
                <i class="bi bi-box-arrow-up"></i>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="sneat-card p-6 flex items-center justify-between border-l-4 border-[#ff3e1d]">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Bahan Baku Menipis (Alert)</p>
                <p class="text-2xl font-bold text-[#ff3e1d]">{{ number_format(($lowStockItems ?? 0) + ($emptyStockItems ?? 0)) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $emptyStockItems ?? 0 }} habis &bull; {{ $lowStockItems ?? 0 }} menipis</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-[#ffe0db] text-[#ff3e1d] flex items-center justify-center text-2xl shadow-sm">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
    </div>

    <!-- Modul 4: Premium Cohesive Supply Chain Visual Flow -->
    <style>
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 12s linear infinite;
        }
    </style>
    <div class="sneat-card overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-[#696cff] via-[#71dd37] to-[#ffab00]"></div>
        
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between bg-slate-50/50">
            <div>
                <h2 class="text-lg font-bold text-slate-800 flex items-center">
                    <i class="bi bi-gear-wide-connected mr-2 text-[#696cff] animate-spin-slow"></i>
                    Peta Alur Rantai Pasok Terintegrasi
                </h2>
                <p class="text-xs text-slate-500 mt-1">Sistem informasi visual end-to-end dari pembelian bahan baku hingga Produk keluar (Gentle Living)</p>
            </div>
            <div class="mt-2 md:mt-0">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-[#e7e7ff] text-[#696cff]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#696cff] mr-1.5 animate-ping"></span>
                    Sistem Real-Time
                </span>
            </div>
        </div>

        <!-- Flowchart Container -->
        <div class="p-6">
            <div class="flex flex-col lg:flex-row gap-4 relative">
                
                <!-- Step 1: Pembelian -->
                <div class="flex-1 bg-white border border-slate-200 hover:border-[#696cff] rounded-xl p-5 shadow-sm hover:shadow-md transition-all duration-300 relative group z-10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#696cff] bg-[#e7e7ff] px-2 py-0.5 rounded">Tahap 1</span>
                            @if(($rawMaterialsBelowBuffer ?? collect())->count() > 0)
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                            @else
                                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            @endif
                        </div>
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 bg-[#e7e7ff] text-[#696cff] rounded-lg flex items-center justify-center text-xl group-hover:scale-110 transition-transform duration-300">
                                <i class="bi bi-cart3"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm ml-3">Pembelian Bahan Baku</h3>
                        </div>
                        <p class="text-xs text-slate-500">Membeli bahan baku sesuai kebutuhan reorder & buffer.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-[11px] text-slate-500 font-medium">Bahan Kritis:</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded {{ ($rawMaterialsBelowBuffer ?? collect())->count() > 0 ? 'bg-[#ffe0db] text-[#ff3e1d]' : 'bg-[#e8fadf] text-[#71dd37]' }}">
                            {{ ($rawMaterialsBelowBuffer ?? collect())->count() }}
                        </span>
                    </div>
                </div>

                <!-- Connector 1 -->
                <div class="hidden lg:flex items-center justify-center -mx-2 z-0">
                    <i class="bi bi-chevron-double-right text-slate-300 text-2xl animate-pulse"></i>
                </div>

                <!-- Step 2: Produksi -->
                <div class="flex-1 bg-white border border-slate-200 hover:border-[#71dd37] rounded-xl p-5 shadow-sm hover:shadow-md transition-all duration-300 relative group z-10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#71dd37] bg-[#e8fadf] px-2 py-0.5 rounded">Tahap 2</span>
                        </div>
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 bg-[#e8fadf] text-[#71dd37] rounded-lg flex items-center justify-center text-xl group-hover:scale-110 transition-transform duration-300">
                                <i class="bi bi-tools"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm ml-3">Produksi</h3>
                        </div>
                        <p class="text-xs text-slate-500">Proses manufaktur produk berdasarkan BOM.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-[11px] text-slate-500 font-medium">BOM Terdaftar:</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                            {{ $totalItems ?? 0 }}
                        </span>
                    </div>
                </div>

                <!-- Connector 2 -->
                <div class="hidden lg:flex items-center justify-center -mx-2 z-0">
                    <i class="bi bi-chevron-double-right text-slate-300 text-2xl animate-pulse"></i>
                </div>

                <!-- Step 3: Produk -->
                <div class="flex-1 bg-white border border-slate-200 hover:border-[#03c3ec] rounded-xl p-5 shadow-sm hover:shadow-md transition-all duration-300 relative group z-10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#03c3ec] bg-[#d7f5fc] px-2 py-0.5 rounded">Tahap 3</span>
                            @if(($finishedGoodsBelowBuffer ?? collect())->count() > 0)
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 bg-[#d7f5fc] text-[#03c3ec] rounded-lg flex items-center justify-center text-xl group-hover:scale-110 transition-transform duration-300">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm ml-3">Produk pada gudang</h3>
                        </div>
                        <p class="text-xs text-slate-500">Penyimpanan produk dan pemantauan limit.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-[11px] text-slate-500 font-medium">Produk Kritis:</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded {{ ($finishedGoodsBelowBuffer ?? collect())->count() > 0 ? 'bg-[#ffe0db] text-[#ff3e1d]' : 'bg-[#e8fadf] text-[#71dd37]' }}">
                            {{ ($finishedGoodsBelowBuffer ?? collect())->count() }}
                        </span>
                    </div>
                </div>

                <!-- Connector 3 -->
                <div class="hidden lg:flex items-center justify-center -mx-2 z-0">
                    <i class="bi bi-chevron-double-right text-slate-300 text-2xl animate-pulse"></i>
                </div>

                <!-- Step 4: Transaksi -->
                <div class="flex-1 bg-white border border-slate-200 hover:border-[#ffab00] rounded-xl p-5 shadow-sm hover:shadow-md transition-all duration-300 relative group z-10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#ffab00] bg-[#fff2d6] px-2 py-0.5 rounded">Tahap 4</span>
                        </div>
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 bg-[#fff2d6] text-[#ffab00] rounded-lg flex items-center justify-center text-xl group-hover:scale-110 transition-transform duration-300">
                                <i class="bi bi-shop"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm ml-3">Transaksi Penjualan</h3>
                        </div>
                        <p class="text-xs text-slate-500">Order pembelian dari pelanggan (Sales).</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-[11px] text-slate-500 font-medium">Penjualan Bulan Ini:</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                            {{ number_format($stockKeluarBulanIni ?? 0) }} item
                        </span>
                    </div>
                </div>

                <!-- Connector 4 -->
                <div class="hidden lg:flex items-center justify-center -mx-2 z-0">
                    <i class="bi bi-chevron-double-right text-slate-300 text-2xl animate-pulse"></i>
                </div>

                <!-- Step 5: Produk  Keluar -->
                <div class="flex-1 bg-white border border-slate-200 hover:border-[#ff3e1d] rounded-xl p-5 shadow-sm hover:shadow-md transition-all duration-300 relative group z-10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#ff3e1d] bg-[#ffe0db] px-2 py-0.5 rounded">Tahap 5</span>
                        </div>
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 bg-[#ffe0db] text-[#ff3e1d] rounded-lg flex items-center justify-center text-xl group-hover:scale-110 transition-transform duration-300">
                                <i class="bi bi-truck"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm ml-3">Produk Keluar</h3>
                        </div>
                        <p class="text-xs text-slate-500">Pengiriman produk kepada konsumen.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-[11px] text-slate-500 font-medium">Nilai Keluar:</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-[#fff2d6] text-[#ffab00]">
                            Rp {{ number_format(substr($nilaiKeluarBulanIni ?? 0, 0, 4), 0, ',', '.') }}k+
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Layout Tables: Bahan Baku Masuk Terbaru & Produk  Keluar Terbaru -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Bahan Baku Masuk Terbaru -->
        <div class="sneat-card flex flex-col h-full">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                <h5 class="text-lg font-bold text-slate-800">Bahan Baku Masuk Terbaru</h5>
                <span class="text-xs text-slate-400">Pembelian bahan baku</span>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 border-collapse">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Nomor</th>
                            <th class="px-6 py-4 font-semibold">Tanggal</th>
                            <th class="px-6 py-4 font-semibold text-right">Nilai</th>
                            <th class="px-6 py-4 font-semibold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentMasuk ?? [] as $masuk)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-700">{{ $masuk->number ?? $masuk->purchase_number ?? '-' }}</td>
                                <td class="px-6 py-4">{{ ($masuk->date ?? $masuk->purchase_date) ? \Carbon\Carbon::parse($masuk->date ?? $masuk->purchase_date)->format('d M Y') : '-' }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-[#71dd37]">Rp {{ number_format($masuk->total_amount ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block px-2 py-0.5 text-xs rounded-full font-semibold
                                        {{ ($masuk->status ?? '') === 'completed' ? 'bg-[#e8fadf] text-[#71dd37]' :
                                           (($masuk->status ?? '') === 'pending' ? 'bg-[#fff2d6] text-[#ffab00]' : 'bg-slate-100 text-slate-600') }}">
                                        {{ ucfirst($masuk->status ?? 'Selesai') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada data pembelian.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Produk Keluar Terbaru -->
        <div class="sneat-card flex flex-col h-full">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                <h5 class="text-lg font-bold text-slate-800">Produk Keluar Terbaru</h5>
                <span class="text-xs text-slate-400">Detail penjualan</span>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 border-collapse">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Item</th>
                            <th class="px-6 py-4 font-semibold">Tanggal</th>
                            <th class="px-6 py-4 font-semibold text-center">Qty</th>
                            <th class="px-6 py-4 font-semibold text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentKeluar ?? [] as $keluar)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-700">{{ $keluar->masterItem->name_item ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $keluar->transactionSales ? \Carbon\Carbon::parse($keluar->transactionSales->date)->format('d M Y') : '-' }}</td>
                                <td class="px-6 py-4 text-center font-bold text-[#ffab00]">{{ number_format($keluar->qty ?? 0) }} pcs</td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-700">Rp {{ number_format($keluar->total_amount ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada data penjualan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
    // Additional scripts if needed for charts or interactions.
</script>
@endpush
@endsection
