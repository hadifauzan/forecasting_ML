@extends('layouts.admin_inventory.app')

@section('title', 'Demand Forecasting')

@section('content')
{{-- ===== CDN: jQuery + Select2 + Chart.js ===== --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* ===== Select2 Custom Theme ===== */
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
        height: 46px;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 7px 14px;
        font-size: 14px;
        background: #fff;
        transition: border-color .2s, box-shadow .2s;
    }
    .select2-container--default .select2-selection--single:focus-within,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #696cff;
        box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.15);
        outline: none;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 30px;
        color: #566a7f;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 46px;
        right: 12px;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #696cff;
    }
    .select2-dropdown {
        border: 1.5px solid #e2e8f0;
        border-radius: 0.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,.12);
        overflow: hidden;
        margin-top: 4px;
    }
    .select2-search--dropdown .select2-search__field {
        border-radius: 0.375rem;
        border: 1.5px solid #e2e8f0;
        padding: 8px 12px;
        font-size: 13px;
    }
    .select2-search--dropdown .select2-search__field:focus {
        border-color: #696cff;
        outline: none;
        box-shadow: 0 0 0 2px rgba(105, 108, 255, 0.1);
    }
    .select2-results__option {
        padding: 10px 14px;
        font-size: 13.5px;
    }
    .select2-container--default .select2-selection__clear {
        font-size: 18px;
        margin-right: 8px;
        color: #a1acb8;
    }
    
    /* ===== Pulse loader ===== */
    @keyframes pulse-ring {
        0%   { transform: scale(.85); opacity: .6; }
        50%  { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(.85); opacity: .6; }
    }
    .pulse-loader { animation: pulse-ring 1.2s ease-in-out infinite; }
    
    /* ===== Kategori badge ===== */
    .badge-rendah   { background: #e8fadf; color: #71dd37; }
    .badge-menengah { background: #fff2d6; color: #ffab00; }
    .badge-tinggi   { background: #ffe0db; color: #ff3e1d; }
    .badge-mae {
        display: inline-block; padding: 2px 12px; border-radius: 99px;
        font-size: 11px; font-weight: 700; text-transform: capitalize;
    }
    #forecast-chart-wrap { position: relative; height: 340px; }
</style>

<div class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-[#e7e7ff] text-[#696cff] flex items-center justify-center shadow-sm">
                    <i class="bi bi-cpu text-xl"></i>
                </span>
                Demand Forecasting
            </h1>
            <p class="text-sm text-slate-500 mt-2 max-w-2xl leading-relaxed">
                Analisis pintar menggunakan Machine Learning (ARIMA) untuk memprediksi kebutuhan produksi berdasarkan tren historis. Sistem juga memberikan <strong>rekomendasi buffer stock</strong> dan rincian <strong>kebutuhan pembelian bahan baku (BOM)</strong>.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('admin.inventory.forecasting.run-dynamic') }}" method="POST" id="form-run-ml">
                @csrf
                <button type="button" onclick="confirmRunMl()" class="px-4 py-2 bg-[#696cff] text-white rounded-lg hover:bg-[#5f61e6] transition-colors shadow-sm shadow-[#696cff]/30 text-sm font-medium flex items-center gap-2">
                    <i class="bi bi-magic"></i> Jalankan Ulang Kalkulasi ML
                </button>
            </form>
            <div id="product-badge" class="hidden items-center gap-2 bg-[#e7e7ff] border border-[#696cff]/20 rounded-lg px-4 py-2 text-sm text-[#696cff] font-bold tracking-wide" style="display:none;">
                <i class="bi bi-box-seam"></i>
                <span id="product-badge-text"></span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1 space-y-6">
            {{-- ===== Product Selector Card ===== --}}
            <div class="sneat-card p-6 border-t-4 border-[#696cff]">
                <label for="product-select" class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wide">
                    <i class="bi bi-search text-[#696cff] mr-1"></i> Silakan Pilih Produk
                </label>
                <div class="relative">
                    <select id="product-select" style="width:100%">
                        <option value="">-- Ketik nama atau kode produk... --</option>
                        @forelse($masterItems as $item)
                            <option
                                value="{{ $item['code_item'] }}"
                                data-code="{{ $item['code_item'] }}"
                                data-name="{{ $item['name_item'] }}"
                                data-has-arima="{{ isset($arimaProductCodes[$item['code_item']]) ? '1' : '0' }}"
                            >
                                {{ $item['code_item'] }} - {{ $item['name_item'] }}{{ isset($arimaProductCodes[$item['code_item']]) ? ' ✨ (Tersedia)' : '' }}
                            </option>
                        @empty
                            <option value="" disabled>Tidak ada data produk di master items</option>
                        @endforelse
                    </select>
                </div>
                <div class="mt-4 flex items-start gap-3 p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <i class="bi bi-info-circle text-[#696cff] mt-0.5"></i> 
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Sistem akan otomatis memuat grafik peramalan dan rincian kebutuhan bahan baku. 
                        <span class="inline-block mt-1 px-1.5 py-0.5 bg-[#e7e7ff] text-[#696cff] font-semibold rounded text-[10px]">
                            ✨ (Tersedia)
                        </span> 
                        menandakan hasil analisis ML terbaru tersedia.
                    </p>
                </div>
            </div>

            {{-- ===== ARIMA Explanation Card ===== --}}
            <div class="sneat-card p-6">
                <h3 class="text-base font-bold text-slate-800 mb-3 flex items-center gap-2">
                    <span class="text-[#696cff]"><i class="bi bi-info-circle-fill"></i></span>
                    Definisi Model ARIMA(p,d,q)
                </h3>
                <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                    Model ARIMA(p,d,q) terdiri dari tiga komponen utama untuk memodelkan data peramalan:
                </p>
                <div class="overflow-hidden rounded-lg border border-slate-100">
                    <table class="min-w-full text-xs text-left border-collapse">
                        <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Komponen</th>
                                <th class="px-4 py-3 font-semibold">Penerapan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 align-top">
                                    <span class="font-bold text-slate-700 block mb-1">AR (p)</span>
                                    <span class="font-mono text-[#696cff] text-[10px]">AutoRegressive</span>
                                </td>
                                <td class="px-4 py-3 leading-relaxed">Pengaruh permintaan masa lalu ke masa depan.</td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 align-top">
                                    <span class="font-bold text-slate-700 block mb-1">I (d)</span>
                                    <span class="font-mono text-[#696cff] text-[10px]">Differencing</span>
                                </td>
                                <td class="px-4 py-3 leading-relaxed">Menghilangkan tren stok agar data stasioner.</td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 align-top">
                                    <span class="font-bold text-slate-700 block mb-1">MA (q)</span>
                                    <span class="font-mono text-[#696cff] text-[10px]">Moving Avg</span>
                                </td>
                                <td class="px-4 py-3 leading-relaxed">Menangkap pengaruh error masa lalu ke saat ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            {{-- ===== Empty State ===== --}}
            <div id="empty-state" class="h-full">
                <div class="sneat-card h-full border-2 border-dashed border-[#e7e7ff] p-16 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-[#e7e7ff] text-[#696cff] rounded-full flex items-center justify-center text-4xl mb-6 shadow-inner">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Pilih produk dari dropdown</h3>
                    <p class="text-slate-500 text-sm max-w-xs">Pilih produk di samping untuk melihat grafik peramalan, tingkat error, dan rekomendasi buffer stock.</p>
                </div>
            </div>

            {{-- ===== Loading State ===== --}}
            <div id="loading-state" style="display:none;" class="h-full">
                <div class="sneat-card h-full p-16 flex flex-col items-center justify-center text-center bg-slate-50/50 border border-slate-100">
                    <div class="pulse-loader w-10 h-10 bg-[#696cff] rounded-full shadow-[0_0_15px_rgba(105,108,255,0.5)] mb-4"></div>
                    <h3 class="text-lg font-bold text-[#696cff] mb-1">Memuat Data Peramalan...</h3>
                    <p class="text-slate-500 text-sm">Sistem sedang mengambil hasil komputasi Machine Learning.</p>
                </div>
            </div>

            {{-- ===== Error State ===== --}}
            <div id="error-state" style="display:none;" class="sneat-card p-5 mb-6 bg-[#ffe0db] border border-[#ff3e1d]/20 text-[#ff3e1d] flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-white/50 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="text-sm font-semibold">
                    <p id="error-msg">Terjadi kesalahan</p>
                </div>
            </div>

            {{-- ===== Detail Section ===== --}}
            <div id="detail-section" style="display:none;" class="space-y-6">

                {{-- --- Metric Cards --- --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="sneat-card p-4 border-b-4 border-[#696cff]">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">MAE</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h4 id="mae-value" class="text-2xl font-extrabold text-slate-800">-</h4>
                            </div>
                            <div class="text-[#696cff] bg-[#e7e7ff] p-1.5 rounded text-lg leading-none">
                                <i class="bi bi-graph-down"></i>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2">Mean Absolute Error</p>
                    </div>

                    <div class="sneat-card p-4 border-b-4 border-[#71dd37]">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">RMSE</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h4 id="rmse-value" class="text-2xl font-extrabold text-slate-800">-</h4>
                            </div>
                            <div class="text-[#71dd37] bg-[#e8fadf] p-1.5 rounded text-lg leading-none">
                                <i class="bi bi-bar-chart-line"></i>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2">Root Mean Sq. Error</p>
                    </div>

                    <div class="sneat-card p-4 border-b-4 border-[#ffab00]">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">MAPE</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h4 id="mape-value" class="text-2xl font-extrabold text-slate-800">-</h4>
                            </div>
                            <div class="text-[#ffab00] bg-[#fff2d6] p-1.5 rounded text-lg leading-none">
                                <i class="bi bi-percent"></i>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2">Mean Abs. % Error</p>
                    </div>

                    <div class="sneat-card p-4 border-b-4 border-[#03c3ec]">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">ARIMA Order</p>
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 id="arima-order-value" class="text-lg font-bold font-mono text-[#03c3ec]">-</h4>
                                <div id="kategori-badge-wrap" class="mt-1">
                                    <span id="kategori-badge" class="badge-mae">-</span>
                                </div>
                            </div>
                            <div class="text-[#03c3ec] bg-[#d7f5fc] p-1.5 rounded text-lg leading-none">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- --- Chart --- --}}
                <div class="sneat-card">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                                <i class="bi bi-activity text-[#696cff]"></i> Prediksi Stok Keluar
                            </h3>
                            <div class="mt-1">
                                <span id="data-source-badge" style="display:none;" class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-500"></span>
                            </div>
                        </div>
                        <div class="inline-flex rounded-lg p-1 bg-slate-50 border border-slate-200" id="chart-view-toggle">
                            <button type="button" data-view="compare" class="px-3 py-1.5 text-xs font-bold rounded shadow-sm bg-white text-[#696cff] border border-slate-200 transition-all">
                                <i class="bi bi-bezier2 mr-1"></i> Bandingkan
                            </button>
                            <button type="button" data-view="full" class="px-3 py-1.5 text-xs font-semibold rounded text-slate-500 hover:text-slate-800 hover:bg-white hover:shadow-sm transition-all">
                                <i class="bi bi-graph-up mr-1"></i> Full Siklus
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id="forecast-chart-wrap">
                            <canvas id="forecast-chart"></canvas>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- The following sections will appear below the grid when detail is active --}}
    <div id="detail-section-bottom" style="display:none;" class="space-y-6">
        
        {{-- --- Rekomendasi: Buffer Stock & ROP --- --}}
        <div class="sneat-card">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-1">
                    <span class="w-8 h-8 rounded bg-[#e8fadf] text-[#71dd37] flex items-center justify-center"><i class="bi bi-shield-check"></i></span>
                    Rekomendasi Inventory
                </h3>
                <p class="text-xs text-slate-500 pl-10">
                    Dihitung otomatis dari pola permintaan aktual. Rumus: <code class="bg-slate-100 text-[#696cff] px-1.5 py-0.5 rounded font-mono text-[11px]">(Max Stok Keluar - Avg Stok Keluar) × Max Lead Time</code>
                </p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Avg Daily Out</p>
                        <p class="text-3xl font-extrabold text-slate-800 mb-1" id="rec-avg">-</p>
                        <p class="text-[11px] text-slate-400">unit / hari</p>
                    </div>
                    <div class="pl-0 md:pl-6 border-none md:border-l border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Buffer Stock</p>
                        <p class="text-3xl font-extrabold text-[#696cff] mb-1" id="rec-buffer">-</p>
                        <p class="text-[11px] text-slate-400">unit pengaman (Max-Avg)×7</p>
                    </div>
                    <div class="pl-0 md:pl-6 border-none md:border-l border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Safety Stock</p>
                        <p class="text-3xl font-extrabold text-[#ffab00] mb-1" id="rec-safety">-</p>
                        <p class="text-[11px] text-slate-400">unit (Z × σ × √LT)</p>
                    </div>
                    <div class="pl-0 md:pl-6 border-none md:border-l border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Reorder Point</p>
                        <p class="text-3xl font-extrabold text-[#71dd37] mb-1" id="rec-rop">-</p>
                        <p class="text-[11px] text-slate-400">unit (Avg×LT + Safety)</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- --- Analisis Kebutuhan Manajemen (Bulan Depan) --- --}}
        <div class="sneat-card">
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <span class="text-[#696cff]"><i class="bi bi-journal-check"></i></span>
                        Analisis Kebutuhan Manajemen (Bulan Depan)
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Prediksi pengadaan bahan baku berdasarkan Bill of Materials (BOM).</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="#" id="btn-produce-forecast" class="px-4 py-2 bg-[#71dd37] text-white rounded-lg hover:bg-[#65c732] transition-colors shadow-sm font-medium text-xs flex items-center gap-2" style="display:none;">
                        <i class="bi bi-play-circle text-sm"></i> Produksi Sekarang
                    </a>
                    <div class="bg-[#e7e7ff] text-[#696cff] rounded-lg px-4 py-2 border border-[#696cff]/20 flex items-center gap-2">
                        <i class="bi bi-box-seam font-bold"></i>
                        <span class="text-xs font-semibold">Total Target Produksi: <strong id="mgt-target-finished" class="text-base font-extrabold ml-1">-</strong> Unit</span>
                    </div>
                </div>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm text-slate-600">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Bahan Baku</th>
                            <th class="px-6 py-4 font-semibold text-right">Keb./Unit</th>
                            <th class="px-6 py-4 font-semibold text-right">Total Keb.</th>
                            <th class="px-6 py-4 font-semibold text-right">Stok Saat Ini</th>
                            <th class="px-6 py-4 font-semibold text-right text-[#696cff]">Rekomendasi Beli</th>
                            <th class="px-6 py-4 font-semibold text-right">Est. Harga</th>
                            <th class="px-6 py-4 font-semibold text-right">Total Biaya</th>
                            <th class="px-6 py-4 font-semibold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="mgt-raw-materials-body" class="divide-y divide-slate-100">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        {{-- --- Sistem Verifikasi & Konversi --- --}}
        <div class="sneat-card">
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <span class="text-[#71dd37]"><i class="bi bi-shield-check-fill"></i></span>
                        Sistem Verifikasi & Konversi Satuan Terpadu
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Deteksi dini & pencegahan kesalahan konversi volume (mL vs Unit Botol).</p>
                </div>
                <div class="bg-[#e8fadf] text-[#71dd37] rounded-full px-3 py-1 flex items-center gap-2 border border-[#71dd37]/20">
                    <span class="pulse-loader w-2 h-2 bg-[#71dd37] rounded-full"></span>
                    <span class="text-[11px] font-bold uppercase tracking-wider">Auto-Verification</span>
                </div>
            </div>
            
            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 overflow-x-auto rounded-xl border border-slate-100">
                    <table class="w-full text-left border-collapse text-sm text-slate-600">
                        <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Bahan Baku / Kemasan</th>
                                <th class="px-4 py-3 font-semibold text-center">Satuan Asli</th>
                                <th class="px-4 py-3 font-semibold text-right">Tanpa Konversi</th>
                                <th class="px-4 py-3 font-semibold text-right text-[#71dd37]">Koreksi Sistem</th>
                                <th class="px-4 py-3 font-semibold text-right">Defisit Terhindar</th>
                                <th class="px-4 py-3 font-semibold text-center">Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody id="unit-verification-body" class="divide-y divide-slate-100">
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>

                {{-- --- Rantai Pasok Integration Status --- --}}
                <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 flex flex-col justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i class="bi bi-diagram-3-fill text-[#696cff]"></i> Alur Integrasi
                        </h4>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <i class="bi bi-check-circle-fill text-[#71dd37] mt-0.5"></i>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800">1. Konversi Terverifikasi</h5>
                                    <p class="text-[11px] text-slate-500 leading-relaxed mt-0.5">Volume bulk dikonversi ke Unit secara presisi.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i class="bi bi-check-circle-fill text-[#71dd37] mt-0.5"></i>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800">2. Toleransi Yield</h5>
                                    <p class="text-[11px] text-slate-500 leading-relaxed mt-0.5">Antisipasi penyusutan volume & botol pecah.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i class="bi bi-check-circle-fill text-[#71dd37] mt-0.5"></i>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800">3. Pengadaan Riil</h5>
                                    <p class="text-[11px] text-slate-500 leading-relaxed mt-0.5">Pengadaan dihitung berdasar volume bersih.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 pt-4 border-t border-slate-200 text-center">
                        <span class="inline-block px-3 py-1.5 bg-[#e8fadf] text-[#71dd37] text-[11px] font-bold rounded-lg uppercase tracking-wider">
                            Sistem Bebas Kesalahan
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- --- Data Table --- --}}
        <div class="sneat-card" id="table-section">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-table text-[#03c3ec]"></i> Detail Perbandingan Data Aktual vs Prediksi
                </h3>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm text-slate-600">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Tanggal</th>
                            <th class="px-6 py-4 font-semibold text-right">Aktual (Keluar)</th>
                            <th class="px-6 py-4 font-semibold text-right">Prediksi (Keluar)</th>
                            <th class="px-6 py-4 font-semibold text-right">Error</th>
                            <th class="px-6 py-4 font-semibold text-right">Abs. Error</th>
                            <th class="px-6 py-4 font-semibold text-center">Sumber</th>
                        </tr>
                    </thead>
                    <tbody id="table-body" class="divide-y divide-slate-100">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <div id="table-pagination" class="flex justify-between items-center px-6 py-4 border-t border-slate-100 bg-slate-50/50" style="display: none;">
                <div class="text-sm text-slate-500" id="pagination-info">Menampilkan 0-0 dari 0</div>
                <div class="flex gap-2">
                    <button type="button" id="btn-prev-page" class="px-3 py-1 bg-white border border-slate-200 rounded text-slate-600 hover:bg-slate-50 disabled:opacity-50 text-sm font-medium" disabled>Sebelumnya</button>
                    <button type="button" id="btn-next-page" class="px-3 py-1 bg-white border border-slate-200 rounded text-slate-600 hover:bg-slate-50 disabled:opacity-50 text-sm font-medium" disabled>Selanjutnya</button>
                </div>
            </div>
        </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ======================================================
       SELECT2 INIT
    ====================================================== */
    $('#product-select').select2({
        placeholder: 'Ketik kode atau nama produk...',
        allowClear: true,
        width: '100%',
    });
    /* ======================================================
       STATE
    ====================================================== */
    var chartInstance = null;
    var AVG_LT = 5.4;
    var MAX_LT = 7;
    var Z_SCORE = 1.65;
    var currentChartData = null; // Store chart data globally for re-rendering
    var activeChartView = 'compare'; // 'compare' or 'full'

    /* ======================================================
       AUTO-LOAD ON CHANGE — no button needed
    ====================================================== */
    $('#product-select').on('change', function() {
        var produk = $(this).val();
        if (!produk) {
            showEmpty();
            return;
        }

        // Cek apakah produk punya data ARIMA
        var selectedOption = this.options[this.selectedIndex];
        var hasArima = selectedOption.getAttribute('data-has-arima') === '1';
        var name = selectedOption.getAttribute('data-name') || produk;

        if (!hasArima) {
            showNoArima(produk, name);
            return;
        }

        loadData(produk);
    });

    /* ======================================================
       TOGGLE VIEW EVENT
    ====================================================== */
    $('#chart-view-toggle button').on('click', function() {
        var view = $(this).attr('data-view');
        if (view === activeChartView) return;

        activeChartView = view;

        // Update active class styles
        $('#chart-view-toggle button').removeClass('bg-white text-purple-700 border border-purple-100 shadow-sm')
            .addClass('text-slate-600 hover:text-slate-900');
        $(this).removeClass('text-slate-600 hover:text-slate-900')
            .addClass('bg-white text-purple-700 border border-purple-100 shadow-sm');

        if (currentChartData) {
            renderChart(currentChartData);
        }
    });    /* ======================================================
       LOAD DATA
    ====================================================== */
    function loadData(produk) {
        showLoading();

        fetch('/admin/inventory/forecasting/demand-detail/' + encodeURIComponent(produk))
            .then(function(resp) {
                var ct = resp.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    throw new Error('Server tidak mengembalikan JSON — pastikan sudah login.');
                }
                return resp.json();
            })
            .then(function(data) {
                if (!data.success) {
                    throw new Error(data.message || 'Gagal memuat data');
                }
                hideLoading();
                renderDetail(data);
            })
            .catch(function(err) {
                hideLoading();
                showError(err.message);
            });
    }

    /* ======================================================
       RENDER DETAIL
    ====================================================== */
    function renderDetail(data) {
        var s  = data.summary;
        var cd = data.chart_data;
        var td = data.table_data;
        var rec = data.dynamic_rec;

        /* --- product badge --- */
        document.getElementById('product-badge-text').textContent = s.produk + ' \u2014 ' + s.name_item;
        var badge = document.getElementById('product-badge');
        badge.style.display = 'flex';

        /* --- data source badge --- */
        var srcEl = document.getElementById('data-source-badge');
        if (srcEl) {
            if (data.has_transaction_data) {
                srcEl.innerHTML = '<i class="bi bi-database-check"></i> Actual: ' + data.transaction_data_count + ' hari dari DB Transaksi';
                srcEl.className = 'text-xs px-3 py-1 rounded-full font-semibold bg-green-100 text-green-700';
            } else {
                srcEl.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Tidak ada transaksi keluar di finished_goods_out';
                srcEl.className = 'text-xs px-3 py-1 rounded-full font-semibold bg-red-100 text-red-700';
            }
            srcEl.style.display = 'inline-flex';
            srcEl.style.alignItems = 'center';
            srcEl.style.gap = '5px';
        }

        /* --- metrics --- */
        document.getElementById('mae-value').textContent  = Number(s.mae).toFixed(4);
        document.getElementById('rmse-value').textContent  = Number(s.rmse).toFixed(4);
        document.getElementById('mape-value').textContent  = Number(s.mape_percentage).toFixed(2) + '%';
        document.getElementById('arima-order-value').textContent = s.arima_order;

        var k = (s.kategori_mae || '').toLowerCase();
        var badgeClass = k === 'rendah' ? 'badge-rendah' : k === 'menengah' ? 'badge-menengah' : 'badge-tinggi';
        var katBadge = document.getElementById('kategori-badge');
        katBadge.className = 'badge-mae ' + badgeClass;
        katBadge.textContent = k || '-';

        /* --- recommendations from server (dynamic) --- */
        renderRec(rec);

        /* --- chart --- */
        currentChartData = cd;
        renderChart(cd);

        /* --- table --- */
        renderTable(td);

        /* --- management forecast --- */
        var mf = data.management_forecast;
        if (mf) {
            document.getElementById('mgt-target-finished').textContent = mf.total_finished_goods_predicted;
            
            var btnProduce = document.getElementById('btn-produce-forecast');
            if (btnProduce && s.item_id) {
                btnProduce.href = "{{ route('admin.production.create') }}?item_id=" + s.item_id + "&qty=" + Math.ceil(mf.total_finished_goods_predicted);
                btnProduce.style.display = 'inline-flex';
            }
            
            var tbody = document.getElementById('mgt-raw-materials-body');
            tbody.innerHTML = '';
            
            if (mf.raw_materials && mf.raw_materials.length > 0) {
                mf.raw_materials.forEach(function(rm) {
                    var statusBadge = '';
                    if (rm.buy_qty > 0) {
                        statusBadge = '<span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"><i class="bi bi-cart-plus me-1"></i> Perlu Beli</span>';
                    } else {
                        statusBadge = '<span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><i class="bi bi-check-circle me-1"></i> Stok Cukup</span>';
                    }

                    var rowHtml = '<tr>' +
                        '<td class="px-4 py-3 text-left font-semibold text-gray-800">' + rm.material_name + '</td>' +
                        '<td class="px-4 py-3 text-right text-gray-600 font-mono">' + Number(rm.req_per_unit).toFixed(2) + ' <span class="text-xs text-gray-400">' + rm.unit + '</span></td>' +
                        '<td class="px-4 py-3 text-right text-gray-600 font-mono font-semibold">' + Number(rm.total_required).toFixed(2) + ' <span class="text-xs text-gray-400">' + rm.unit + '</span></td>' +
                        '<td class="px-4 py-3 text-right text-gray-600 font-mono">' + Number(rm.current_stock).toFixed(2) + ' <span class="text-xs text-gray-400">' + rm.unit + '</span></td>' +
                        '<td class="px-4 py-3 text-right font-mono font-bold ' + (rm.buy_qty > 0 ? 'text-red-600' : 'text-gray-500') + '">' + Number(rm.buy_qty).toFixed(2) + ' <span class="text-xs text-gray-400">' + rm.unit + '</span></td>' +
                        '<td class="px-4 py-3 text-right text-gray-600 font-mono">' + formatRupiah(rm.price) + '</td>' +
                        '<td class="px-4 py-3 text-right font-mono font-bold text-gray-800">' + formatRupiah(rm.estimated_cost) + '</td>' +
                        '<td class="px-4 py-3 text-center">' + statusBadge + '</td>' +
                        '</tr>';
                    tbody.innerHTML += rowHtml;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-gray-400">Tidak ada pemetaan Bill of Materials (BOM) untuk produk ini.</td></tr>';
            }

            /* --- unit conversion verification --- */
            var verificationBody = document.getElementById('unit-verification-body');
            verificationBody.innerHTML = '';

            if (mf.raw_materials && mf.raw_materials.length > 0) {
                mf.raw_materials.forEach(function(rm) {
                    var unitLower = (rm.unit || '').toLowerCase();
                    var originalRequired = rm.total_required;
                    var naiveRequired = 0.0;
                    var deficit = 0.0;
                    var isLiquid = false;

                    if (unitLower === 'ml' || unitLower === 'mili' || unitLower === 'milliliter' || unitLower === 'mililiter') {
                        isLiquid = true;
                        naiveRequired = Math.ceil(mf.total_finished_goods_predicted); 
                        deficit = originalRequired - naiveRequired;
                    } else if (unitLower === 'unit' || unitLower === 'pcs' || unitLower === 'botol' || unitLower === 'kemasan') {
                        naiveRequired = Math.ceil(mf.total_finished_goods_predicted);
                        deficit = originalRequired - naiveRequired;
                    } else {
                        naiveRequired = Math.ceil(mf.total_finished_goods_predicted);
                        deficit = Math.max(0.0, originalRequired - naiveRequired);
                    }

                    if (deficit < 0) deficit = 0.0;

                    var naiveText = '';
                    var correctedText = '';
                    var deficitText = '';
                    var statusBadge = '';

                    if (isLiquid) {
                        naiveText = '<span class="text-xs font-semibold text-red-500 font-mono">' + naiveRequired.toFixed(2) + ' mL</span>' +
                            '<div class="text-[10px] text-gray-400">Hanya dihitung ' + naiveRequired + ' unit</div>';
                        correctedText = '<span class="text-sm font-bold text-emerald-600 font-mono">' + originalRequired.toFixed(2) + ' mL</span>' +
                            '<div class="text-[10px] text-emerald-600 font-semibold font-mono">' + mf.total_finished_goods_predicted + ' Unit × ' + rm.req_per_unit + ' mL / (' + rm.yield_percentage + '%)</div>';
                        
                        if (deficit > 0) {
                            deficitText = '<span class="text-red-600 font-bold font-mono">+' + deficit.toFixed(2) + ' mL</span>' +
                                '<div class="text-[10px] text-red-500 font-medium">Bahan Kurang!</div>';
                            statusBadge = '<span class="px-2 py-0.5 inline-flex text-[11px] leading-5 font-bold rounded-full bg-emerald-100 text-emerald-800"><i class="bi bi-shield-fill-check me-1"></i> Terkoreksi Otomatis</span>';
                        } else {
                            deficitText = '<span class="text-gray-400 font-mono">0.00 mL</span>';
                            statusBadge = '<span class="px-2 py-0.5 inline-flex text-[11px] leading-5 font-bold rounded-full bg-green-100 text-green-800"><i class="bi bi-check-circle-fill me-1"></i> Aman</span>';
                        }
                    } else {
                        naiveText = '<span class="text-xs font-semibold text-amber-600 font-mono">' + naiveRequired.toFixed(0) + ' ' + rm.unit + '</span>' +
                            '<div class="text-[10px] text-gray-400">Tanpa Toleransi Susut</div>';
                        correctedText = '<span class="text-sm font-bold text-emerald-600 font-mono">' + Math.ceil(originalRequired) + ' ' + rm.unit + '</span>' +
                            '<div class="text-[10px] text-emerald-600 font-semibold font-mono">' + mf.total_finished_goods_predicted + ' / (' + rm.yield_percentage + '%)</div>';
                        
                        if (deficit > 0) {
                            deficitText = '<span class="text-indigo-600 font-bold font-mono">+' + deficit.toFixed(2) + ' ' + rm.unit + '</span>' +
                                '<div class="text-[10px] text-indigo-500 font-medium">Cadangan Aman</div>';
                            statusBadge = '<span class="px-2 py-0.5 inline-flex text-[11px] leading-5 font-bold rounded-full bg-emerald-100 text-emerald-800"><i class="bi bi-shield-fill-check me-1"></i> Terkoreksi Otomatis</span>';
                        } else {
                            deficitText = '<span class="text-gray-400 font-mono">0.00 ' + rm.unit + '</span>';
                            statusBadge = '<span class="px-2 py-0.5 inline-flex text-[11px] leading-5 font-bold rounded-full bg-green-100 text-green-800"><i class="bi bi-check-circle-fill me-1"></i> Aman</span>';
                        }
                    }

                    var rowHtml = '<tr>' +
                        '<td class="px-4 py-3 text-left font-semibold text-gray-800">' + rm.material_name + '</td>' +
                        '<td class="px-4 py-3 text-center text-gray-600 font-mono font-semibold"><span class="bg-slate-100 px-2 py-0.5 rounded text-xs">' + rm.unit + '</span></td>' +
                        '<td class="px-4 py-3 text-right">' + naiveText + '</td>' +
                        '<td class="px-4 py-3 text-right">' + correctedText + '</td>' +
                        '<td class="px-4 py-3 text-right">' + deficitText + '</td>' +
                        '<td class="px-4 py-3 text-center">' + statusBadge + '</td>' +
                        '</tr>';
                    verificationBody.innerHTML += rowHtml;
                });
            } else {
                verificationBody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-gray-400">Tidak ada pemetaan Bill of Materials (BOM) untuk produk ini.</td></tr>';
            }
        }

        /* show detail, hide empty */
        document.getElementById('detail-section').style.display = 'block';
        if(document.getElementById('detail-section-bottom')) {
            document.getElementById('detail-section-bottom').style.display = 'block';
        }
        document.getElementById('empty-state').style.display    = 'none';
        document.getElementById('error-state').style.display    = 'none';
    }

    function formatRupiah(num) {
        return 'Rp ' + Number(num).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    /* ======================================================
       RECOMMENDATIONS — dari server (dynamic_rec)
       Sumber: DB Transaksi (jika ada) atau ARIMA CSV (fallback)
    ====================================================== */
    function renderRec(rec) {
        if (!rec) {
            ['#rec-avg','#rec-buffer','#rec-safety','#rec-rop'].forEach(function(id) { $(id).text('\u2014'); });
            return;
        }
        document.getElementById('rec-avg').textContent    = Number(rec.avg_daily_sales).toFixed(2);
        document.getElementById('rec-buffer').textContent  = Number(rec.buffer_stock).toFixed(2);
        document.getElementById('rec-safety').textContent  = Number(rec.safety_stock_95).toFixed(2);
        document.getElementById('rec-rop').textContent     = Number(rec.rop).toFixed(2);

        // Update sub label dengan sumber data
        var srcLabel = rec.data_source === 'transaction_db'
            ? 'dari ' + rec.data_points + ' hari transaksi nyata'
            : 'dari ARIMA CSV (' + rec.data_points + ' titik)';
        var subEls = document.querySelectorAll('.rec-card .rec-sub');
        if (subEls[0]) subEls[0].textContent = 'unit / hari (' + srcLabel + ')';
    }

    /* ======================================================
       CHART
    ====================================================== */
    function renderChart(cd) {
        var labels     = [];
        var trainVals  = [];
        var actualVals = [];
        var predVals   = [];
        var datasets   = [];

        if (activeChartView === 'compare') {
            /* Only show the actual/test period comparison (Image 2 style) */
            if (cd.actual && cd.actual.length > 0) {
                cd.actual.forEach(function(d) {
                    labels.push(d.date);
                    actualVals.push(d.actual);
                    predVals.push(d.predicted);
                });
            }

            datasets = [
                {
                    label: 'Stok Keluar Aktual',
                    data: actualVals,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249,115,22,0.02)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: false,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#f97316',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#f97316',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    spanGaps: false,
                },
                {
                    label: 'Prediksi Stok Keluar',
                    data: predVals,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,0.02)',
                    borderWidth: 2.5,
                    borderDash: [6, 4],
                    tension: 0.35,
                    fill: false,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#22c55e',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#22c55e',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    spanGaps: true,
                }
            ];
        } else {
            /* Full Cycle View (Training + Testing + Forecast) */
            var hasTraining = cd.training && cd.training.length > 0;
            if (hasTraining) {
                cd.training.forEach(function(d) {
                    labels.push(d.date);
                    trainVals.push(d.value);
                    actualVals.push(null);
                    predVals.push(d.predicted !== undefined ? d.predicted : null);
                });
            }

            if (cd.actual && cd.actual.length > 0) {
                cd.actual.forEach(function(d) {
                    labels.push(d.date);
                    trainVals.push(null);
                    actualVals.push(d.actual);
                    predVals.push(d.predicted);
                });
            }

            if (cd.forecast && cd.forecast.length > 0) {
                cd.forecast.forEach(function(d) {
                    labels.push(d.date);
                    trainVals.push(null);
                    actualVals.push(null);
                    predVals.push(d.predicted);
                });
            }

            datasets = [
                {
                    label: 'Data Training (Stok Keluar)',
                    data: trainVals,
                    borderColor: '#94a3b8',
                    backgroundColor: 'rgba(148,163,184,.04)',
                    borderWidth: 1.5,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    spanGaps: false,
                },
                {
                    label: 'Stok Keluar Aktual',
                    data: actualVals,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249,115,22,.02)',
                    borderWidth: 2.5,
                    tension: 0.3,
                    fill: false,
                    pointRadius: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#f97316',
                    pointBorderWidth: 1.5,
                    pointHoverRadius: 6,
                    spanGaps: false,
                },
                {
                    label: 'Prediksi Stok Keluar',
                    data: predVals,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,.02)',
                    borderWidth: 2.5,
                    borderDash: [6, 4],
                    tension: 0.3,
                    fill: false,
                    pointRadius: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#22c55e',
                    pointBorderWidth: 1.5,
                    pointHoverRadius: 6,
                    spanGaps: true,
                }
            ];
        }

        var ctx = document.getElementById('forecast-chart').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.9)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.dataset.label + ': ' + (ctx.parsed.y !== null ? ctx.parsed.y.toFixed(4) : 'N/A');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { maxTicksLimit: 12, font: { size: 11 }, color: '#94a3b8' },
                        grid: { color: 'rgba(0,0,0,.02)' }
                    },
                    y: {
                        beginAtZero: true,
                        grace: '10%',
                        title: { display: true, text: 'Stok Keluar (unit)', font: { size: 11 }, color: '#64748b' },
                        grid: { color: 'rgba(0,0,0,.02)' },
                        ticks: { font: { size: 11 }, color: '#94a3b8' }
                    }
                }
            }
        });
    }

    /* ======================================================
       TABLE & PAGINATION
    ====================================================== */
    var globalTableData = [];
    var currentPage = 1;
    var rowsPerPage = 10;

    function renderTable(tableData) {
        var tableSection = document.getElementById('table-section');
        if (!tableData || tableData.length === 0) {
            tableSection.style.display = 'none';
            return;
        }
        tableSection.style.display = 'block';
        document.getElementById('table-pagination').style.display = 'flex';
        
        globalTableData = tableData;
        currentPage = 1;
        displayTablePage();
    }

    function displayTablePage() {
        var start = (currentPage - 1) * rowsPerPage;
        var end = start + rowsPerPage;
        var paginatedData = globalTableData.slice(start, end);
        
        var rows = '';
        paginatedData.forEach(function(r, i) {
            var err = Number(r.error);
            var errColor = err > 0 ? 'color:#16a34a;' : err < 0 ? 'color:#dc2626;' : 'color:#64748b;';
            var srcBadge = r.source === 'transaction'
                ? '<span style="background:#dcfce7;color:#15803d;padding:1px 8px;border-radius:99px;font-size:10px;font-weight:700;">DB</span>'
                : '<span style="background:#f1f5f9;color:#64748b;padding:1px 8px;border-radius:99px;font-size:10px;font-weight:700;">CSV</span>';
            rows += '<tr style="background:' + (i % 2 === 0 ? '#fff' : '#f8fafc') + ';">'
                + '<td class="px-4 py-2.5 text-gray-700">' + r.date + '</td>'
                + '<td class="px-4 py-2.5 text-right font-medium" style="color:#ea580c;">' + Number(r.actual_sales).toFixed(4) + '</td>'
                + '<td class="px-4 py-2.5 text-right font-medium" style="color:#16a34a;">' + (r.predicted_sales !== null ? Number(r.predicted_sales).toFixed(4) : '-') + '</td>'
                + '<td class="px-4 py-2.5 text-right font-medium" style="' + errColor + '">' + (r.error !== null ? err.toFixed(4) : '-') + '</td>'
                + '<td class="px-4 py-2.5 text-right text-gray-600">' + (r.absolute_error !== null ? Number(r.absolute_error).toFixed(4) : '-') + '</td>'
                + '<td class="px-4 py-2.5 text-center">' + srcBadge + '</td>'
                + '</tr>';
        });
        document.getElementById('table-body').innerHTML = rows;

        // Update Pagination Info & Buttons
        var total = globalTableData.length;
        document.getElementById('pagination-info').innerText = 'Menampilkan ' + (start + 1) + '-' + Math.min(end, total) + ' dari ' + total;
        document.getElementById('btn-prev-page').disabled = currentPage === 1;
        document.getElementById('btn-next-page').disabled = end >= total;
    }

    document.getElementById('btn-prev-page').addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            displayTablePage();
        }
    });

    document.getElementById('btn-next-page').addEventListener('click', function() {
        if (currentPage * rowsPerPage < globalTableData.length) {
            currentPage++;
            displayTablePage();
        }
    });

    /* ======================================================
       UI HELPERS
    ====================================================== */
    function showLoading() {
        document.getElementById('loading-state').style.display  = 'block';
        document.getElementById('detail-section').style.display = 'none';
        if(document.getElementById('detail-section-bottom')) {
            document.getElementById('detail-section-bottom').style.display = 'none';
        }
        document.getElementById('empty-state').style.display    = 'none';
        document.getElementById('error-state').style.display    = 'none';
        document.getElementById('product-badge').style.display  = 'none';
    }
    function hideLoading() {
        document.getElementById('loading-state').style.display = 'none';
    }
    function showEmpty() {
        document.getElementById('detail-section').style.display = 'none';
        if(document.getElementById('detail-section-bottom')) {
            document.getElementById('detail-section-bottom').style.display = 'none';
        }
        document.getElementById('error-state').style.display    = 'none';
        document.getElementById('empty-state').style.display    = 'block';
        document.getElementById('loading-state').style.display  = 'none';
        document.getElementById('product-badge').style.display  = 'none';
    }
    function showError(msg) {
        document.getElementById('error-msg').textContent = '❌ ' + msg;
        document.getElementById('error-state').style.display    = 'flex';
        document.getElementById('detail-section').style.display = 'none';
        if(document.getElementById('detail-section-bottom')) {
            document.getElementById('detail-section-bottom').style.display = 'none';
        }
        document.getElementById('empty-state').style.display    = 'block';
    }
    function showNoArima(code, name) {
        // Tampilkan info bahwa produk ini belum ada model ARIMA
        var errEl = document.getElementById('error-state');
        var msgEl = document.getElementById('error-msg');
        msgEl.innerHTML = '<i class="bi bi-info-circle-fill" style="color:#6366f1;"></i> '
            + '<strong>' + code + ' — ' + name + '</strong> belum memiliki data ARIMA forecast. '
            + 'Data stok keluar aktual dari transaksi finished_goods_out tetap tersedia untuk hitung buffer stock.';
        errEl.style.background   = '#eef2ff';
        errEl.style.borderColor  = '#c7d2fe';
        errEl.style.color        = '#4338ca';
        errEl.style.display      = 'flex';
        document.getElementById('detail-section').style.display = 'none';
        if(document.getElementById('detail-section-bottom')) {
            document.getElementById('detail-section-bottom').style.display = 'none';
        }
        document.getElementById('loading-state').style.display  = 'none';
        document.getElementById('empty-state').style.display    = 'block';
        document.getElementById('product-badge').style.display  = 'none';
    }

});
</script>
@endsection

@push('modals')
<div id="modalConfirmMl"
     class="fixed inset-0 z-[9999] flex items-center justify-center hidden"
     style="background: rgba(15,23,42,0.45); backdrop-filter: blur(6px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
         style="animation: modalSlideUp 0.25s cubic-bezier(.16,1,.3,1) both;">
        {{-- Close button --}}
        <div class="flex justify-end pt-4 pr-4">
            <button onclick="closeConfirmMl()" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all text-lg leading-none">&times;</button>
        </div>
        {{-- Body --}}
        <div class="px-8 pb-8 text-center">
            {{-- Icon --}}
            <div class="w-20 h-20 rounded-full mx-auto mb-5 flex items-center justify-center text-4xl"
                 style="background: linear-gradient(135deg,#fff9db 0%,#fff0b3 100%); color:#ffab00; box-shadow: 0 8px 20px rgba(255,171,0,0.18);">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-3">Jalankan Ulang Kalkulasi ML?</h3>
            <p class="text-slate-500 mb-5 leading-relaxed" style="font-size:13.5px;">
                Sistem akan memproses dan mengekspor seluruh histori produksi terbaru dari database, kemudian menjalankan ulang model optimasi Machine Learning (ARIMA) untuk semua produk aktif secara dinamis.
            </p>
            {{-- Warning note --}}
            <div class="flex items-start gap-2 rounded-xl px-4 py-3 mb-6 text-left"
                 style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; font-size:12.5px; font-weight:500; line-height:1.55;">
                <i class="bi bi-clock-history mt-0.5 shrink-0"></i>
                <span><strong>Catatan Penting:</strong> Proses komputasi ARIMA memerlukan waktu beberapa saat karena mengevaluasi data histori harian secara real-time.</span>
            </div>
            {{-- Buttons --}}
            <div class="flex items-center justify-center gap-3">
                <button onclick="closeConfirmMl()"
                        class="px-6 py-2.5 rounded-lg text-sm font-semibold text-slate-500 bg-slate-100 hover:bg-slate-200 transition-all">
                    Batal
                </button>
                <button onclick="submitRunMl()"
                        class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition-all"
                        style="background:linear-gradient(135deg,#696cff 0%,#5f61e6 100%); box-shadow:0 4px 14px rgba(105,108,255,0.35);">
                    Lanjut
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalLoadingMl"
     class="fixed inset-0 z-[9999] flex items-center justify-center hidden"
     style="background: rgba(15,23,42,0.45); backdrop-filter: blur(6px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden"
         style="animation: modalSlideUp 0.25s cubic-bezier(.16,1,.3,1) both;">
        <div class="px-8 py-8 text-center">
            <div class="w-14 h-14 rounded-full mx-auto mb-5 flex items-center justify-center text-2xl pulse-loader"
                 style="background:linear-gradient(135deg,#696cff 0%,#5f61e6 100%); color:#fff; box-shadow:0 8px 24px rgba(105,108,255,0.38);">
                <i class="bi bi-cpu"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Mengevaluasi &amp; Mentraining Model...</h3>
            <p class="text-slate-500 mb-0 leading-relaxed" style="font-size:13px;">
                Sistem sedang memproses algoritma ARIMA secara real-time.<br>
                <strong style="color:#696cff;">Mohon tidak menutup atau memuat ulang halaman ini.</strong>
            </p>
        </div>
    </div>
</div>

<style>
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)   scale(1);    }
}
</style>

<script>
function confirmRunMl() {
    var el = document.getElementById('modalConfirmMl');
    el.classList.remove('hidden');
    el.querySelector('.bg-white').style.animation = 'none';
    void el.querySelector('.bg-white').offsetWidth; // reflow trigger
    el.querySelector('.bg-white').style.animation = 'modalSlideUp 0.25s cubic-bezier(.16,1,.3,1) both';
}
function closeConfirmMl() {
    document.getElementById('modalConfirmMl').classList.add('hidden');
}
function submitRunMl() {
    closeConfirmMl();
    var el = document.getElementById('modalLoadingMl');
    el.classList.remove('hidden');
    document.getElementById('form-run-ml').submit();
}
// Close on backdrop click
document.getElementById('modalConfirmMl').addEventListener('click', function(e) {
    if (e.target === this) closeConfirmMl();
});
// ESC key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeConfirmMl();
});
</script>
@endpush
