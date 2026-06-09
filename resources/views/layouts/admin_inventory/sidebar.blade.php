{{-- sidebar.blade.php --}}
<!-- Mobile Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-20 lg:hidden hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar" class="flex flex-col w-64 h-full bg-white border-r border-slate-100 transition-all duration-300 z-30 lg:relative absolute transform -translate-x-full lg:translate-x-0">
    
    <!-- Brand / Logo Area -->
    <div class="h-16 flex items-center px-6">
        <a href="#" class="flex items-center gap-2">
            <img src="{{ asset('images/top-bar.png') }}" alt="Logo" class="h-12">
        </a>
        <!-- Close button mobile -->
        <button id="sidebar-close-btn" class="lg:hidden ml-auto text-slate-400 hover:text-slate-600">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3 scrollbar-thin scrollbar-thumb-slate-200">
        
        <ul class="space-y-1">
            <li class="px-4 mt-6 mb-2">
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Menu</span>
            </li>
            {{-- General / Common Routes --}}
            @if(Auth::user() && method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin_inventory')))
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('admin.inventory.dashboard') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.inventory.dashboard') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-house-door text-lg mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            @endif

            {{-- Forecasting - Owner & Admin Inventory Only --}}
            @if(Auth::user() && method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin_inventory')))
                <!-- Demand Forecasting -->
                <li>
                    <a href="{{ route('admin.inventory.forecasting.demand') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.inventory.forecasting.*') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-graph-up text-lg mr-3"></i>
                        <span>Forecasting</span>
                    </a>
                </li>

                <!-- Model Evaluasi -->
                <li>
                    <a href="{{ route('admin.inventory.model-evaluation') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.inventory.model-evaluation') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-clipboard-check text-lg mr-3"></i>
                        <span>Model Evaluasi</span>
                    </a>
                </li>
            @endif

            {{-- Data Produk Jadi - Owner, Admin Inventory Only --}}
            @if(Auth::user() && method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin_inventory')))
                
                <li class="px-4 mt-6 mb-2">
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Inventory Management</span>
                </li>

                <!-- Master Data Dropdown -->
                <li>
                    <button onclick="toggleSubmenu('master-data')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg transition-colors duration-200 
                        {{ request()->routeIs('admin.inventory.master-items.*') || request()->routeIs('admin.inventory.master-categories.*')
                        ? 'bg-[#696cff]/10 text-[#696cff] font-medium'
                        : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <div class="flex items-center">
                            <i class="bi bi-database text-lg mr-3"></i>
                            <span>Master Data</span>
                        </div>
                        <i class="bi bi-chevron-right text-sm transition-transform duration-200 {{ request()->routeIs('admin.inventory.master-items.*') || request()->routeIs('admin.inventory.master-categories.*') ? 'rotate-90' : '' }}" id="master-data-chevron"></i>
                    </button>

                    <!-- Submenu -->
                    <ul id="master-data-submenu" class="mt-1 space-y-1 overflow-hidden transition-all duration-300 {{ request()->routeIs('admin.inventory.master-items.*') || request()->routeIs('admin.inventory.master-categories.*') ? 'max-h-40' : 'max-h-0' }}">
                        <li>
                            <a href="{{ route('admin.inventory.master-items.index') }}"
                                class="flex items-center pl-11 pr-4 py-2 rounded-lg transition-colors duration-200 text-[0.9rem]
                                {{ request()->routeIs('admin.inventory.master-items.*') ? 'text-[#696cff] font-medium' : 'text-[#697a8d] hover:text-slate-900' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-3 {{ request()->routeIs('admin.inventory.master-items.*') ? 'text-[#696cff] font-medium' : 'text-[#697a8d] hover:text-slate-900' }}"></span>
                                <span>Master Item (BOM)</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.inventory.master-categories.index') }}"
                                class="flex items-center pl-11 pr-4 py-2 rounded-lg transition-colors duration-200 text-[0.9rem]
                                {{ request()->routeIs('admin.inventory.master-categories.*') ? 'text-[#696cff] font-medium' : 'text-[#697a8d] hover:text-slate-900' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-3 {{ request()->routeIs('admin.inventory.master-categories.*') ? 'text-[#696cff] font-medium' : 'text-[#697a8d] hover:text-slate-900' }}"></span>
                                <span>Kategori Produk</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Data Produk Jadi -->
                <li>
                    <a href="{{ route('admin.inventory.finished-goods') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.inventory.finished-goods') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-box-seam text-lg mr-3"></i>
                        <span> Produk Jadi</span>
                    </a>
                </li>

                <!-- Buffer Stock Analysis -->
                <li>
                    <a href="{{ route('admin.inventory.buffer-stock.raw-materials') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.inventory.buffer-stock.*') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-bar-chart-line text-lg mr-3"></i>
                        <span>Buffer Stock</span>
                    </a>
                </li>

                <!-- Stock Opname -->
                <li>
                    <a href="{{ route('admin.inventory.stock-opname') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.inventory.stock-opname') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-clipboard-data text-lg mr-3"></i>
                        <span>Stock Opname</span>
                    </a>
                </li>

                <li class="px-4 mt-6 mb-2">
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Reports</span>
                </li>

                <!-- Riwayat Transaksi -->
                <li>
                    <a href="{{ route('admin.inventory.transaction-history') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.inventory.transaction-history') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-clock-history text-lg mr-3"></i>
                        <span>Riwayat Transaksi</span>
                    </a>
                </li>
            @endif

            {{-- Production & Stock Operations - Owner & Production Team Only --}}
            @if(Auth::user() && method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin_inventory')))
            
                <li class="px-4 mt-6 mb-2">
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Stock Operations</span>
                </li>

                <!-- Produksi BOM -->
                <li>
                    <a href="{{ route('admin.production.index') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.production.*') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-tools text-lg mr-3"></i>
                        <span>Produksi BOM</span>
                    </a>
                </li>

                <!-- Penyesuaian Stok (Stock Adjustment) -->
                <li>
                    <a href="{{ route('admin.stock-adjustment.index') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.stock-adjustment.*') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-plus-slash-minus text-lg mr-3"></i>
                        <span>Penyesuaian Stok</span>
                    </a>
                </li>

                <!-- Production Overview -->
                <li>
                    <a href="{{ route('admin.inventory.production.overview') }}"
                        class="flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200
                        {{ request()->routeIs('admin.inventory.production.overview') ? 'bg-[#696cff]/10 text-[#696cff] font-medium' : 'text-[#697a8d] hover:bg-slate-50' }}">
                        <i class="bi bi-activity text-lg mr-3"></i>
                        <span>Production Overview</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</aside>

<script>
    // New simple toggle for submenu since structure changed
    function toggleSubmenu(id) {
        const submenu = document.getElementById(id + '-submenu');
        const chevron = document.getElementById(id + '-chevron');
        
        if (submenu.classList.contains('max-h-0')) {
            submenu.classList.remove('max-h-0');
            submenu.classList.add('max-h-40');
            chevron.classList.add('rotate-90');
        } else {
            submenu.classList.remove('max-h-40');
            submenu.classList.add('max-h-0');
            chevron.classList.remove('rotate-90');
        }
    }
</script>