@extends('layouts.admin_inventory.app')

@section('title', 'Model Evaluasi - Gentle Living')

@section('content')
{{-- ===== CDN: jQuery + Select2 + Chart.js ===== --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

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

    /* ===== Pulse loader ===== */
    @keyframes pulse-ring {
        0%   { transform: scale(.85); opacity: .6; }
        50%  { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(.85); opacity: .6; }
    }
    .pulse-loader { animation: pulse-ring 1.2s ease-in-out infinite; }

    /* ===== Winner badge ===== */
    .badge-win {
        background: #e8fadf;
        color: #71dd37;
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    /* ===== Chart container ===== */
    #comparison-chart-wrap { position: relative; height: 380px; }
</style>

<div class="space-y-6">
    {{-- ===== Header Area ===== --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-[#e7e7ff] text-[#696cff] flex items-center justify-center shadow-sm">
                    <i class="bi bi-clipboard-check text-xl"></i>
                </span>
                Model Evaluasi & Komparasi
            </h1>
            <p class="text-sm text-slate-500 mt-2 max-w-3xl leading-relaxed">
                Analisis komparatif tingkat akurasi peramalan antara metode <strong>ARIMA (Machine Learning)</strong> dengan <strong>Regresi Linear Berganda / Tren</strong> secara dinamis. Mendukung evaluasi agregat <strong>Keseluruhan</strong> maupun <strong>Produk Individu</strong> langsung dari database.
            </p>
        </div>
        <div class="flex items-center gap-2 bg-[#e7e7ff]/60 border border-[#696cff]/10 rounded-lg px-4 py-2.5 text-[#696cff] font-bold text-xs">
            <i class="bi bi-cpu-fill text-sm"></i>
            <span id="active-filter-badge">Semua Produk (Keseluruhan)</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- ===== Selector & Explanations Panel ===== --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Selector Card --}}
            <div class="sneat-card p-6 border-t-4 border-[#696cff]">
                <label for="product-select" class="block text-xs font-bold text-slate-700 mb-3 uppercase tracking-wider">
                    <i class="bi bi-funnel-fill text-[#696cff] mr-1"></i> Pilih Opsi Filter
                </label>
                <div class="relative">
                    <select id="product-select" style="width:100%">
                        <option value="all" selected>Semua Produk (Keseluruhan)</option>
                        @foreach($masterItems as $item)
                            <option value="{{ $item->code_item }}">
                                {{ $item->code_item }} - {{ $item->name_item }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-4 flex items-start gap-3 p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <i class="bi bi-info-circle text-[#696cff] mt-0.5"></i> 
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Pilih <strong>Keseluruhan</strong> untuk membandingkan total penjualan agregat harian, atau pilih <strong>Produk Individu</strong> untuk mengevaluasi fitting tren linear khusus per produk.
                    </p>
                </div>
            </div>

            {{-- Academic Definition Card --}}
            <div class="sneat-card p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                    <span class="text-[#696cff]"><i class="bi bi-book-half"></i></span>
                    Karakteristik Model
                </h3>
                <div class="space-y-4 text-xs text-slate-600">
                    <div class="p-3 bg-purple-50 rounded-lg border border-purple-100">
                        <strong class="text-purple-700 block mb-1">ARIMA (p, d, q)</strong>
                        <p class="leading-relaxed text-slate-500">
                            Model stokastik deret waktu. Sangat sensitif terhadap pola musiman, fluktuasi harian, dan autokorelasi data masa lalu. Ideal untuk kontrol inventory produk individu.
                        </p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                        <strong class="text-blue-700 block mb-1">Regresi Linear</strong>
                        <p class="leading-relaxed text-slate-500">
                            Model deterministik yang memetakan tren linear waktu (y = mx + c). Sangat stabil pada level makro agregat karena mengabaikan noise fluktuatif harian.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Metrics & Visualizations Area ===== --}}
        <div class="lg:col-span-3 space-y-6">
            {{-- Loading State --}}
            <div id="loading-state" class="sneat-card p-20 flex flex-col items-center justify-center text-center bg-slate-50/50 border border-slate-100">
                <div class="pulse-loader w-10 h-10 bg-[#696cff] rounded-full shadow-[0_0_15px_rgba(105,108,255,0.5)] mb-4"></div>
                <h3 class="text-lg font-bold text-[#696cff] mb-1">Menganalisis Performa Model...</h3>
                <p class="text-slate-500 text-sm">Mengambil data dan melakukan kalkulasi fitting model Regresi Linear secara real-time.</p>
            </div>

            {{-- Empty State / No Data Available --}}
            <div id="empty-state" style="display:none;" class="sneat-card p-20 flex flex-col items-center justify-center text-center border-2 border-dashed border-[#e7e7ff]">
                <div class="w-16 h-16 bg-[#ffe0db] text-[#ff3e1d] rounded-full flex items-center justify-center text-3xl mb-4 shadow-inner">
                    <i class="bi bi-folder-x"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1" id="empty-state-title">Data Tidak Tersedia</h3>
                <p class="text-slate-500 text-sm max-w-md" id="empty-state-text">
                    Tidak ada data histori peramalan atau data training kosong di database untuk produk yang dipilih. Silakan pilih opsi lain.
                </p>
            </div>

            {{-- Main Data Panel --}}
            <div id="data-panel" style="display:none;" class="space-y-6">
                {{-- Comparative Metric Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- MAE Card --}}
                    <div class="sneat-card p-5 border-b-4 border-slate-100 hover:border-slate-300 transition-colors">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Mean Absolute Error (MAE)</p>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Regresi Linear</span>
                                <span class="font-mono text-sm font-bold text-slate-800" id="lr-mae">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">ARIMA</span>
                                <span class="font-mono text-sm font-bold text-slate-800" id="arima-mae">-</span>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                                <span class="text-[10px] text-slate-400">Model Terbaik:</span>
                                <div id="mae-winner-wrap"></div>
                            </div>
                        </div>
                    </div>

                    {{-- RMSE Card --}}
                    <div class="sneat-card p-5 border-b-4 border-slate-100 hover:border-slate-300 transition-colors">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Root Mean Sq. Error (RMSE)</p>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Regresi Linear</span>
                                <span class="font-mono text-sm font-bold text-slate-800" id="lr-rmse">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">ARIMA</span>
                                <span class="font-mono text-sm font-bold text-slate-800" id="arima-rmse">-</span>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                                <span class="text-[10px] text-slate-400">Model Terbaik:</span>
                                <div id="rmse-winner-wrap"></div>
                            </div>
                        </div>
                    </div>

                    {{-- MAPE Card --}}
                    <div class="sneat-card p-5 border-b-4 border-slate-100 hover:border-slate-300 transition-colors">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Mean Abs. Percentage Error (MAPE)</p>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Regresi Linear</span>
                                <span class="font-mono text-sm font-bold text-slate-800" id="lr-mape">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">ARIMA</span>
                                <span class="font-mono text-sm font-bold text-slate-800" id="arima-mape">-</span>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                                <span class="text-[10px] text-slate-400">Model Terbaik:</span>
                                <div id="mape-winner-wrap"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Interactive Multi-Line Chart --}}
                <div class="sneat-card">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-activity text-[#696cff]"></i> Plot Perbandingan Aktual vs Prediksi Model
                        </h3>
                        <div class="flex items-center gap-4 text-xs font-semibold">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 bg-[#8592a3] rounded-full"></span> Aktual</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 bg-[#71dd37] rounded-full"></span> ARIMA</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 bg-[#696cff] rounded-full"></span> Regresi Linear</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id="comparison-chart-wrap">
                            <canvas id="comparison-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Table & Academic Discussion Area ===== --}}
    <div id="bottom-panel" style="display:none;" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Table Section --}}
        <div class="lg:col-span-2 sneat-card" id="table-section">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-table text-indigo-500"></i> Detail Komparasi Harian Periode Pengujian (Testing)
                </h3>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-5 py-3 font-bold">Tanggal</th>
                            <th class="px-5 py-3 font-bold text-right">Aktual</th>
                            <th class="px-5 py-3 font-bold text-right text-[#71dd37]">Prediksi ARIMA</th>
                            <th class="px-5 py-3 font-bold text-right text-[#71dd37]">Error ARIMA</th>
                            <th class="px-5 py-3 font-bold text-right text-[#696cff]">Pred. Regresi</th>
                            <th class="px-5 py-3 font-bold text-right text-[#696cff]">Error Regresi</th>
                        </tr>
                    </thead>
                    <tbody id="comparison-table-body" class="divide-y divide-slate-100">
                        <!-- Filled dynamically -->
                    </tbody>
                </table>
            </div>

            {{-- Table Pagination --}}
            <div class="flex justify-between items-center px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                <div class="text-[11px] text-slate-400" id="pagination-info">Menampilkan 0-0 dari 0</div>
                <div class="flex gap-2">
                    <button type="button" id="btn-prev-page" class="px-2.5 py-1 bg-white border border-slate-200 rounded text-slate-600 hover:bg-slate-50 disabled:opacity-50 text-[11px] font-semibold" disabled>Sebelumnya</button>
                    <button type="button" id="btn-next-page" class="px-2.5 py-1 bg-white border border-slate-200 rounded text-slate-600 hover:bg-slate-50 disabled:opacity-50 text-[11px] font-semibold" disabled>Selanjutnya</button>
                </div>
            </div>
        </div>

        {{-- Academic Summary Card --}}
        <div class="lg:col-span-1 sneat-card p-6 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="bi bi-mortarboard-fill text-amber-500"></i> Kesimpulan Perbandingan Model
                </h3>
                <div class="space-y-4 text-xs text-slate-600 leading-relaxed" id="academic-analysis-text">
                    <!-- Dynamic analysis text filled here -->
                </div>
            </div>
            
            <div class="pt-4 border-t border-slate-100 mt-5 text-center">
                <span class="inline-block px-3 py-1.5 bg-[#e7e7ff] text-[#696cff] text-[10px] font-bold rounded-lg uppercase tracking-wider">
                    Model Akurasi Dinamis Terverifikasi
                </span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('#product-select').select2({
        placeholder: 'Pilih opsi filter...',
        width: '100%'
    });

    var chartInstance = null;
    var currentTableData = [];
    var currentPage = 1;
    var itemsPerPage = 8;

    // Load initial data
    loadEvaluationData('all');

    // Trigger on selector change
    $('#product-select').on('change', function() {
        var selected = $(this).val();
        loadEvaluationData(selected);
        
        // Update active filter badge text
        if (selected === 'all') {
            document.getElementById('active-filter-badge').textContent = 'Semua Produk (Keseluruhan)';
        } else {
            document.getElementById('active-filter-badge').textContent = 'Produk: ' + selected;
        }
    });

    /**
     * AJAX Load Evaluation Data
     */
    function loadEvaluationData(produk) {
        document.getElementById('loading-state').style.display = 'flex';
        document.getElementById('empty-state').style.display = 'none';
        document.getElementById('data-panel').style.display = 'none';
        document.getElementById('bottom-panel').style.display = 'none';

        fetch('/admin/inventory/model-evaluation/data/' + encodeURIComponent(produk))
            .then(resp => resp.json())
            .then(data => {
                document.getElementById('loading-state').style.display = 'none';
                if (!data.success) {
                    document.getElementById('empty-state-title').textContent = 'Kalkulasi Gagal';
                    document.getElementById('empty-state-text').textContent = data.message || 'Tidak ada data training yang tersedia untuk kalkulasi.';
                    document.getElementById('empty-state').style.display = 'flex';
                    return;
                }

                // Render metrics, charts, tables, and analysis
                renderDashboard(data);
            })
            .catch(err => {
                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('empty-state-title').textContent = 'Terjadi Kesalahan';
                document.getElementById('empty-state-text').textContent = 'Kesalahan jaringan atau server: ' + err.message;
                document.getElementById('empty-state').style.display = 'flex';
            });
    }

    /**
     * Render the dynamic metrics, charts, and summaries
     */
    function renderDashboard(data) {
        var s = data.summary;
        var cd = data.chart_data;
        currentTableData = data.table_data;
        currentPage = 1;

        // Render Metric Value Cards
        document.getElementById('lr-mae').textContent = Number(s.linear_regression.mae).toFixed(4);
        document.getElementById('lr-rmse').textContent = Number(s.linear_regression.rmse).toFixed(4);
        document.getElementById('lr-mape').textContent = Number(s.linear_regression.mape).toFixed(2) + '%';

        document.getElementById('arima-mae').textContent = Number(s.arima.mae).toFixed(4);
        document.getElementById('arima-rmse').textContent = Number(s.arima.rmse).toFixed(4);
        document.getElementById('arima-mape').textContent = Number(s.arima.mape).toFixed(2) + '%';

        // Render Winners (MAE, RMSE, MAPE)
        renderWinnerBadge('mae-winner-wrap', s.linear_regression.mae, s.arima.mae);
        renderWinnerBadge('rmse-winner-wrap', s.linear_regression.rmse, s.arima.rmse);
        renderWinnerBadge('mape-winner-wrap', s.linear_regression.mape, s.arima.mape);

        // Show main panels
        document.getElementById('data-panel').style.display = 'block';
        document.getElementById('bottom-panel').style.display = 'grid';

        // Render Multi-Line Chart
        renderChart(cd);

        // Render Pagination Table
        renderTablePage();

        // Render Academic Analysis Text
        renderAcademicAnalysis(s);
    }

    /**
     * Render Winner Badge Side-by-Side
     */
    function renderWinnerBadge(elementId, lrVal, arimaVal) {
        var wrap = document.getElementById(elementId);
        wrap.innerHTML = '';
        
        var diff = lrVal - arimaVal;
        
        if (Math.abs(diff) < 0.0001) {
            wrap.innerHTML = '<span class="badge-win bg-slate-100 text-slate-600"><i class="bi bi-circle-fill text-[6px]"></i> Seimbang</span>';
        } else if (diff < 0) {
            wrap.innerHTML = '<span class="badge-win" style="background:#e7e7ff;color:#696cff"><i class="bi bi-trophy-fill"></i> Regresi Linier</span>';
        } else {
            wrap.innerHTML = '<span class="badge-win"><i class="bi bi-trophy-fill"></i> ARIMA</span>';
        }
    }

    /**
     * Render Multi-series Line Chart using Chart.js
     */
    function renderChart(cd) {
        var ctx = document.getElementById('comparison-chart').getContext('2d');
        
        if (chartInstance) {
            chartInstance.destroy();
        }

        // Combine training, actual (testing), and forecast into continuous date sequences
        var dates = [];
        var actualSales = [];
        var arimaPred = [];
        var lrPred = [];

        // 1. Training Period (Actual sales exist, ARIMA predictions are not modeled/null, LR exists)
        cd.training.forEach(function(row) {
            dates.push(row.date);
            actualSales.push(row.actual);
            arimaPred.push(null);
            lrPred.push(row.lr_pred);
        });

        // 2. Testing Period (Both Actual, ARIMA predictions, and LR exist)
        cd.actual.forEach(function(row) {
            dates.push(row.date);
            actualSales.push(row.actual);
            arimaPred.push(row.arima_pred);
            lrPred.push(row.lr_pred);
        });

        // 3. Forecast Period (No Actual sales, ARIMA exists, LR exists)
        cd.forecast.forEach(function(row) {
            dates.push(row.date);
            actualSales.push(null);
            arimaPred.push(row.arima_pred);
            lrPred.push(row.lr_pred);
        });

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Penjualan Aktual',
                        data: actualSales,
                        borderColor: '#8592a3',
                        backgroundColor: 'rgba(133, 146, 163, 0.1)',
                        borderWidth: 2,
                        pointRadius: 1,
                        spanGaps: false,
                        fill: false
                    },
                    {
                        label: 'Prediksi ARIMA',
                        data: arimaPred,
                        borderColor: '#71dd37',
                        backgroundColor: 'rgba(113, 221, 55, 0.1)',
                        borderWidth: 2,
                        pointRadius: 1,
                        spanGaps: true,
                        fill: false
                    },
                    {
                        label: 'Prediksi Regresi Linear',
                        data: lrPred,
                        borderColor: '#696cff',
                        backgroundColor: 'rgba(105, 108, 255, 0.1)',
                        borderWidth: 2,
                        pointRadius: 1,
                        spanGaps: true,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kuantitas Unit'
                        }
                    }
                },
                plugins: {
                    legend: { display: false } // custom legends rendered in HTML
                }
            }
        });
    }

    /**
     * Render the comparative data table with client-side pagination
     */
    function renderTablePage() {
        var tbody = document.getElementById('comparison-table-body');
        tbody.innerHTML = '';

        var totalItems = currentTableData.length;
        var totalPages = Math.ceil(totalItems / itemsPerPage);

        if (totalItems === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-gray-400">Tidak ada data untuk ditampilkan.</td></tr>';
            document.getElementById('pagination-info').textContent = 'Menampilkan 0 dari 0';
            document.getElementById('btn-prev-page').disabled = true;
            document.getElementById('btn-next-page').disabled = true;
            return;
        }

        var startIdx = (currentPage - 1) * itemsPerPage;
        var endIdx = Math.min(startIdx + itemsPerPage, totalItems);

        for (var i = startIdx; i < endIdx; i++) {
            var row = currentTableData[i];

            var arimaErrorClass = row.arima_error >= 0 ? 'text-green-600' : 'text-red-600';
            var lrErrorClass = row.lr_error >= 0 ? 'text-green-600' : 'text-indigo-600';

            var rowHtml = '<tr>' +
                '<td class="px-5 py-2.5 font-semibold text-slate-800">' + row.date + '</td>' +
                '<td class="px-5 py-2.5 font-bold text-right font-mono">' + row.actual + '</td>' +
                '<td class="px-5 py-2.5 font-semibold text-right font-mono text-green-600 bg-green-50/20">' + row.arima_pred + '</td>' +
                '<td class="px-5 py-2.5 text-right font-mono ' + arimaErrorClass + '">' + (row.arima_error >= 0 ? '+' : '') + row.arima_error + '</td>' +
                '<td class="px-5 py-2.5 font-semibold text-right font-mono text-indigo-600 bg-indigo-50/20">' + row.lr_pred + '</td>' +
                '<td class="px-5 py-2.5 text-right font-mono ' + lrErrorClass + '">' + (row.lr_error >= 0 ? '+' : '') + row.lr_error + '</td>' +
                '</tr>';
            tbody.innerHTML += rowHtml;
        }

        // Update Pagination Controls
        document.getElementById('pagination-info').textContent = 'Menampilkan ' + (startIdx + 1) + '-' + endIdx + ' dari ' + totalItems;
        document.getElementById('btn-prev-page').disabled = currentPage === 1;
        document.getElementById('btn-next-page').disabled = currentPage === totalPages;
    }

    // Pagination Click Events
    document.getElementById('btn-prev-page').addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            renderTablePage();
        }
    });

    document.getElementById('btn-next-page').addEventListener('click', function() {
        var totalPages = Math.ceil(currentTableData.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderTablePage();
        }
    });

    /**
     * Render dynamic thesis-level Indonesian comparative summary
     */
    function renderAcademicAnalysis(s) {
        var wrap = document.getElementById('academic-analysis-text');
        wrap.innerHTML = '';

        var arimaVal = s.arima.mape;
        var lrVal = s.linear_regression.mape;
        
        var isAll = s.produk === 'Semua Produk';
        var selectedProduct = s.produk;
        
        var winner = arimaVal < lrVal ? 'ARIMA' : 'Regresi Linier';
        var winnerExplanation = '';

        if (arimaVal < lrVal) {
            winnerExplanation = 'Untuk filter aktif ini, <strong>ARIMA</strong> terbukti lebih akurat berdasarkan MAPE. ARIMA unggul karena mampu memodelkan pergerakan tren acak harian dan menyesuaikan baseline penjualan secara adaptif.';
        } else {
            winnerExplanation = 'Untuk filter aktif ini, <strong>Regresi Linear</strong> terbukti lebih akurat berdasarkan MAPE. Regresi Linear unggul karena data historis cenderung memiliki pola tren naik/turun linear yang stabil tanpa fluktuasi acak berlebih.';
        }

        var html = '';

        if (isAll) {
            html += '<p><strong>Analisis Agregat (Total Sales):</strong></p>' +
                '<p>Pada level keseluruhan gabungan, Regresi Linear cenderung memberikan kestabilan prediksi yang sangat baik. Hal ini terbukti karena Regresi Linear memodelkan hubungan tren makro waktu terhadap total penjualan.</p>' +
                '<p>Sementara itu, ARIMA secara dinamis menjumlahkan seluruh prediksi produk individual secara paralel. Hal ini dapat meningkatkan deviasi error agregat karena noise dari masing-masing produk berfluktuasi secara independen.</p>';
        } else {
            html += '<p><strong>Analisis Produk Individu (' + selectedProduct + '):</strong></p>' +
                '<p>Pada tingkat produk tunggal, pergerakan data penjualan riil sangat fluktuatif (seringkali bernilai 0 pada hari-hari tertentu). Model ARIMA melacak pola autokorelasi (AR) dan tren differencing (I) harian dengan sangat baik.</p>' +
                '<p>Sebaliknya, Regresi Linear Tren menyederhanakan data fluktuatif tersebut ke dalam satu garis lurus tren waktu ($y = ' + s.linear_regression.slope + 'x + ' + s.linear_regression.intercept + '$). Model Regresi ini sangat andal untuk estimasi volume jangka panjang, namun mengabaikan fluktuasi harian.</p>';
        }

        html += '<div class="p-3 bg-amber-50 rounded-lg border border-amber-100 mt-3">' +
            '<p class="font-bold text-amber-700"><i class="bi bi-award"></i> Kesimpulan Performa:</p>' +
            '<p class="mt-1 text-slate-600">' + winnerExplanation + '</p>' +
            '</div>';

        wrap.innerHTML = html;
    }

    /**
     * Show Bootstrap alert-like notifications
     */
    function showErrorNotification(message) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, 'error');
        } else {
            alert(message);
        }
    }
});
</script>
@endsection
