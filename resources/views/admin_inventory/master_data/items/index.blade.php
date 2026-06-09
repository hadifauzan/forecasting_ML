@extends('layouts.admin_inventory.app')

@section('title', 'Master Data Produk (Item)')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Master Data Produk</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola data dasar produk jadi beserta resep/Bill of Materials (BOM).</p>
        </div>
        <div>
            <a href="{{ route('admin.inventory.master-items.create') }}" class="px-4 py-2 bg-[#696cff] text-white rounded-lg hover:bg-[#5f61e6] transition-colors shadow-sm shadow-[#696cff]/30 text-sm font-medium flex items-center">
                <i class="bi bi-plus-lg mr-2"></i> Tambah Produk Baru
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="bg-[#ffe0db] text-[#ff3e1d] px-4 py-3 rounded-lg sneat-shadow flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                <span class="font-medium text-sm">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Card Container -->
    <div class="sneat-card">
        <!-- Card Header & Filter -->
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <form action="{{ route('admin.inventory.master-items.index') }}" method="GET" class="w-full md:w-1/3 flex gap-2">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i class="bi bi-search"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode produk..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-[#696cff] focus:border-[#696cff] outline-none transition-all bg-slate-50 focus:bg-white text-slate-600">
                </div>
                <button type="submit" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors text-sm font-medium">Cari</button>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 border-collapse">
                <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                    <tr>
                        <th class="px-6 py-4 font-semibold w-16">No</th>
                        <th class="px-6 py-4 font-semibold">Produk</th>
                        <th class="px-6 py-4 font-semibold">Deskripsi & Komposisi</th>
                        <th class="px-6 py-4 font-semibold">Kategori</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Resep BOM</th>
                        <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $index => $item)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4 text-slate-500">{{ $items->firstItem() + $index }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#e7e7ff] text-[#696cff] flex items-center justify-center font-bold overflow-hidden">
                                        @if($item->picture_item)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($item->picture_item) }}" alt="{{ $item->name_item }}" class="w-full h-full object-cover">
                                        @else
                                            {{ substr($item->name_item, 0, 1) }}
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700">{{ $item->name_item }}</span>
                                        <span class="text-xs text-slate-400 mt-0.5">{{ $item->code_item }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-slate-600 max-w-xs">
                                    <p class="font-semibold mb-1 whitespace-normal break-words" title="{{ $item->description_item }}">{{ $item->description_item ?: '-' }}</p>
                                    <p class="text-slate-400 whitespace-normal break-words" title="{{ $item->ingredient_item }}">Komposisi: {{ $item->ingredient_item ?: '-' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($item->categories as $category)
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded-md font-medium">{{ $category->name_category }}</span>
                                    @endforeach
                                    @if($item->categories->isEmpty())
                                        <span class="text-slate-400 italic text-xs">Tidak ada</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($item->status_item == 'active')
                                    <span class="px-2.5 py-1 bg-[#e8fadf] text-[#71dd37] text-xs font-semibold rounded-full">Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 bg-[#ffe0db] text-[#ff3e1d] text-xs font-semibold rounded-full">Inaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php $bomCount = \App\Models\MasterItemBillOfMaterials::where('item_id', $item->item_id)->count(); @endphp
                                @if($bomCount > 0)
                                    <button type="button" onclick="openBomModal({{ $item->item_id }}, '{{ addslashes($item->name_item) }}')" class="inline-flex items-center px-2.5 py-1 bg-[#fff2d6] text-[#ffab00] hover:bg-[#ffab00] hover:text-white transition-colors text-xs font-semibold rounded-full cursor-pointer">
                                        <i class="bi bi-journal-text mr-1.5"></i> {{ $bomCount }} Bahan
                                    </button>
                                @else
                                    <button type="button" onclick="openBomModal({{ $item->item_id }}, '{{ addslashes($item->name_item) }}')" class="inline-flex items-center px-2.5 py-1 border border-dashed border-slate-300 text-slate-500 hover:text-[#696cff] hover:border-[#696cff] transition-colors text-xs font-semibold rounded-full cursor-pointer">
                                        <i class="bi bi-plus-circle mr-1.5"></i> Tambah BOM
                                    </button>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('admin.inventory.master-items.edit', $item->item_id) }}" class="w-8 h-8 rounded bg-[#e7e7ff] text-[#696cff] hover:bg-[#696cff] hover:text-white flex items-center justify-center transition-colors" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('admin.inventory.master-items.destroy', $item->item_id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded bg-[#ffe0db] text-[#ff3e1d] hover:bg-[#ff3e1d] hover:text-white flex items-center justify-center transition-colors" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-inbox text-4xl mb-3 text-slate-300"></i>
                                    <p class="text-sm">Belum ada data produk.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($items->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $items->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- BOM Modal -->
<div id="bomModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col transform scale-95 transition-transform duration-300 overflow-hidden">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-[#fff2d6] text-[#ffab00] flex items-center justify-center text-sm shadow-sm">
                        <i class="bi bi-journal-text"></i>
                    </span>
                    Bill of Materials (BOM)
                </h3>
                <p class="text-sm text-slate-500 mt-1">Resep produk: <strong id="bomItemName" class="text-[#696cff]"></strong></p>
            </div>
            <button onclick="closeBomModal()" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6 bg-slate-50">
            
            <div id="bomLoading" class="hidden flex-col items-center justify-center py-10">
                <div class="w-10 h-10 border-4 border-slate-200 border-t-[#696cff] rounded-full animate-spin mb-3"></div>
                <p class="text-sm font-semibold text-slate-500">Memuat resep BOM...</p>
            </div>

            <div id="bomContent" class="space-y-6">
                <!-- Add New BOM Entry -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="bi bi-plus-circle-fill text-[#696cff]"></i> Tambah Bahan Baru
                    </h4>
                    
                    <form id="addBomForm" onsubmit="submitBom(event)" class="flex flex-col md:flex-row gap-3">
                        <input type="hidden" id="bomItemId" name="item_id">
                        
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Bahan Baku</label>
                            <select id="bomRawMaterial" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] outline-none">
                                <option value="">Pilih Bahan...</option>
                            </select>
                        </div>
                        
                        <div class="w-full md:w-32">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Kebutuhan</label>
                            <input type="number" step="0.01" min="0" id="bomQty" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] outline-none" placeholder="0.0">
                        </div>
                        
                        <div class="w-full md:w-32">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Yield (%)</label>
                            <input type="number" step="0.01" min="0" max="100" id="bomYield" value="100" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] outline-none" placeholder="100">
                        </div>

                        <div class="w-full md:w-auto flex items-end">
                            <button type="submit" class="w-full md:w-auto px-4 py-2 bg-[#696cff] text-white rounded-lg text-sm font-bold hover:bg-[#5f61e6] transition-colors flex items-center justify-center gap-2">
                                <i class="bi bi-plus-lg"></i> Tambah
                            </button>
                        </div>
                    </form>
                </div>

                <!-- BOM Table -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left border-collapse text-sm text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                            <tr>
                                <th class="px-5 py-3">Nama Bahan Baku</th>
                                <th class="px-5 py-3 text-right">Kebutuhan</th>
                                <th class="px-5 py-3 text-right">Stok Saat Ini</th>
                                <th class="px-5 py-3 text-center">Yield</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="bomTableBody" class="divide-y divide-slate-100">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfToken = '{{ csrf_token() }}';
    const bomModal = document.getElementById('bomModal');
    const bomModalInner = bomModal.querySelector('div');
    const bomTableBody = document.getElementById('bomTableBody');
    const bomRawMaterialSelect = document.getElementById('bomRawMaterial');
    let currentItemId = null;

    function openBomModal(itemId, itemName) {
        currentItemId = itemId;
        document.getElementById('bomItemId').value = itemId;
        document.getElementById('bomItemName').textContent = itemName;
        
        bomModal.classList.remove('hidden');
        bomModal.classList.add('flex');
        
        // Trigger animations
        setTimeout(() => {
            bomModal.classList.remove('opacity-0');
            bomModalInner.classList.remove('scale-95');
        }, 10);

        fetchBomData(itemId);
    }

    function closeBomModal() {
        bomModal.classList.add('opacity-0');
        bomModalInner.classList.add('scale-95');
        
        setTimeout(() => {
            bomModal.classList.remove('flex');
            bomModal.classList.add('hidden');
        }, 300);
    }

    async function fetchBomData(itemId) {
        document.getElementById('bomLoading').classList.remove('hidden');
        document.getElementById('bomLoading').classList.add('flex');
        document.getElementById('bomContent').classList.add('hidden');

        try {
            const response = await fetch(`/admin/inventory/finished-goods/${itemId}/bom`);
            const data = await response.json();
            
            if (data.success) {
                renderBomTable(data.bom_entries);
                renderRawMaterials(data.raw_materials);
            } else {
                alert(data.message || 'Gagal memuat data BOM');
            }
        } catch (error) {
            console.error(error);
            alert('Terjadi kesalahan saat memuat BOM');
        } finally {
            document.getElementById('bomLoading').classList.add('hidden');
            document.getElementById('bomLoading').classList.remove('flex');
            document.getElementById('bomContent').classList.remove('hidden');
        }
    }

    function renderBomTable(entries) {
        bomTableBody.innerHTML = '';
        
        if (entries.length === 0) {
            bomTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                        <div class="flex flex-col items-center">
                            <i class="bi bi-inbox text-3xl mb-2 text-slate-300"></i>
                            <p class="font-semibold">BOM masih kosong</p>
                            <p class="text-xs">Tambahkan bahan baku pada formulir di atas.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        entries.forEach(entry => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors';
            tr.innerHTML = `
                <td class="px-5 py-3 font-semibold text-slate-800">${entry.material_name}</td>
                <td class="px-5 py-3 text-right font-bold text-[#696cff]">${entry.quantity_required} <span class="text-xs text-slate-500 font-normal ml-1">${entry.unit}</span></td>
                <td class="px-5 py-3 text-right font-mono text-slate-600">${entry.current_stock}</td>
                <td class="px-5 py-3 text-center">
                    <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-xs font-semibold">${entry.yield_percentage}%</span>
                </td>
                <td class="px-5 py-3 text-center">
                    <button onclick="deleteBom(${entry.bom_id})" class="w-7 h-7 rounded-lg bg-[#ffe0db] text-[#ff3e1d] hover:bg-[#ff3e1d] hover:text-white flex items-center justify-center transition-colors tooltip" data-tip="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            bomTableBody.appendChild(tr);
        });
    }

    function renderRawMaterials(materials) {
        bomRawMaterialSelect.innerHTML = '<option value="">Pilih Bahan...</option>';
        materials.forEach(mat => {
            bomRawMaterialSelect.innerHTML += `<option value="${mat.item_raw_id}">${mat.material_name} (${mat.unit}) - Stok: ${mat.current_stock}</option>`;
        });
    }

    async function submitBom(e) {
        e.preventDefault();
        
        const rawId = document.getElementById('bomRawMaterial').value;
        const qty = document.getElementById('bomQty').value;
        const yieldPerc = document.getElementById('bomYield').value;
        
        if (!rawId || !qty) return alert('Mohon lengkapi formulir');

        const submitBtn = e.target.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Menyimpan...';

        try {
            const response = await fetch(`/admin/inventory/finished-goods/${currentItemId}/bom`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    item_raw_id: rawId,
                    quantity_required: qty,
                    yield_percentage: yieldPerc
                })
            });

            const data = await response.json();
            if (data.success) {
                // Reset form and refresh BOM list
                document.getElementById('bomQty').value = '';
                bomRawMaterialSelect.value = '';
                fetchBomData(currentItemId);
                
                // Refresh page after a short delay so user sees update, or we can just let them stay
                // We don't necessarily need to reload, but the badge count in the main table needs to update
                // For a seamless experience we can just refresh the page when they close the modal
            } else {
                alert(data.message || 'Gagal menyimpan BOM');
            }
        } catch (error) {
            console.error(error);
            alert('Terjadi kesalahan koneksi');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-plus-lg"></i> Tambah';
        }
    }

    async function deleteBom(bomId) {
        if (!confirm('Hapus bahan baku ini dari resep?')) return;

        try {
            const response = await fetch(`/admin/inventory/bom/${bomId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (data.success) {
                fetchBomData(currentItemId);
            } else {
                alert(data.message || 'Gagal menghapus BOM');
            }
        } catch (error) {
            console.error(error);
            alert('Terjadi kesalahan koneksi');
        }
    }

    // Refresh page when modal closes to update the BOM counts in the table
    bomModal.addEventListener('click', (e) => {
        if (e.target === bomModal) {
            closeBomModal();
            window.location.reload();
        }
    });
    
    // Also override the close button to reload
    const originalClose = closeBomModal;
    closeBomModal = function() {
        originalClose();
        setTimeout(() => window.location.reload(), 300);
    }
</script>
@endsection
