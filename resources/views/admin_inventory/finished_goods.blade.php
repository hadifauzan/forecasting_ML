@extends('layouts.admin_inventory.app')

@section('title', 'Daftar Produk Jadi')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2"> Daftar Produk </h1>
            <p class="text-lg text-gray-600">Manajemen inventori produk dan pantau stok secara real-time</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Produk</p>
                        <p class="text-3xl font-black text-gray-900 mt-1">{{ $summary['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-2xl">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <form method="GET" action="{{ route('admin.inventory.finished-goods') }}" class="flex-1 flex gap-2">
                    <select
                        name="per_page"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        @foreach([10, 15, 25, 50, 100] as $option)
                            <option value="{{ $option }}" {{ (int) $perPage === $option ? 'selected' : '' }}>
                                {{ $option }}/hal
                            </option>
                        @endforeach
                    </select>
                    <input
                        type="text"
                        name="search"
                        id="live-search"
                        value="{{ $search }}"
                        placeholder="Cari produk ..."
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">No.</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Info Produk</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Stok Aktual</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rawMaterials as $row)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 align-top text-sm text-slate-700 font-medium">{{ $rawMaterials->firstItem() + $loop->index }}.</td>
                                <td class="px-6 py-4 align-top">
                                    <div class="font-bold text-slate-800">{{ $row['name_item'] }}</div>
                                    @if(!empty($row['code_item']))
                                        <div class="text-xs font-mono text-slate-500 mt-1"><i class="bi bi-upc-scan"></i> {{ $row['code_item'] }}</div>
                                    @endif
                                    <div class="text-[11px] text-slate-400 mt-1 uppercase tracking-wider"><i class="bi bi-shop"></i> {{ $row['inventory'] }}</div>
                                </td>
                                <td class="px-6 py-4 align-top text-sm">
                                    <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-md font-semibold text-xs border border-indigo-100">{{ $row['category'] }}</span>
                                </td>
                                <td class="px-6 py-4 align-top text-right text-sm font-black text-slate-800">{{ number_format($row['stock']) }} <span class="text-xs font-medium text-slate-500">Unit</span></td>
                                <td class="px-6 py-4 align-top text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.inventory.finished-goods.show', $row['item_stock_id']) }}" class="px-3 py-1.5 bg-[#e7e7ff] text-[#696cff] hover:bg-[#696cff] hover:text-white rounded text-xs font-bold transition-colors" title="Lihat detail produk & forecasting">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <button type="button" onclick="openEditModal({{ $row['item_stock_id'] }})" class="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-800 rounded text-xs font-bold transition-colors" title="Edit stok">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button type="button" onclick="deleteFinishedGoods({{ $row['item_stock_id'] }}, '{{ addslashes($row['name_item']) }}')" class="px-3 py-1.5 bg-[#ffe0db] text-[#ff3e1d] hover:bg-[#ff3e1d] hover:text-white rounded text-xs font-bold transition-colors" title="Hapus produk">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="bi bi-inbox text-5xl text-slate-300 block mb-4"></i>
                                    <h3 class="text-lg font-bold text-slate-700 mb-1">Tidak ada produk</h3>
                                    <p class="text-sm text-slate-500">Data produk tidak ditemukan atau belum ditambahkan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $rawMaterials->appends(request()->query())->links() }}
            </div>
        </div>

    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full max-h-screen overflow-y-auto">
        <div class="bg-[#d3ebf4] border-b border-[#b9dbe8] px-6 py-4 sticky top-0">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-slate-800">Detail Produk</h2>
                <button type="button" onclick="closeDetailModal()" class="text-slate-500 hover:text-slate-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6 text-slate-800" id="detailContent">
            <div class="flex justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end sticky bottom-0">
            <button type="button" onclick="closeDetailModal()" class="px-4 py-2 rounded border border-gray-300 text-slate-700 hover:bg-gray-50">
                Tutup
            </button>
            <button type="button" id="editFromDetailBtn"
             class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                Edit Stok
            </button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full max-h-screen overflow-y-auto">
        <div class="bg-[#d3ebf4] border-b border-[#b9dbe8] px-6 py-4 sticky top-0">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-slate-800">Edit Stok Produk</h2>
                <button type="button" onclick="closeEditModal()" class="text-slate-500 hover:text-slate-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="editForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            
            <div id="editContentInfo" class="pb-4 border-b">
                <p class="text-sm text-slate-500">Nama Item</p>
                <p id="editItemName" class="text-lg font-semibold text-slate-800">-</p>
                <p class="text-sm text-slate-500 mt-3">Kode Item</p>
                <p id="editItemCode" class="text-sm text-slate-700">-</p>
                <p class="text-sm text-slate-500 mt-3">Buffer Stock</p>
                <p id="editBufferStock" class="text-sm text-slate-700">-</p>
            </div>

            <div>
                <label for="editStock" class="block text-sm font-medium text-slate-700 mb-1">Stock Baru</label>
                <input
                    id="editStock"
                    type="number"
                    name="stock"
                    min="0"
                    max="9999999"
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-200"
                    required
                >
                <p id="editError" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>

            <div class="bg-gray-50 -mx-6 -mb-6 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end sticky bottom-0">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded border border-gray-300 text-slate-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 flex items-center gap-2">
                    <span id="submitText">Simpan</span>
                    <span id="submitSpinner" class="hidden animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentItemStockId = null;
const deleteFinishedGoodsUrlTemplate = '{{ route("admin.inventory.finished-goods.destroy", ["itemStock" => "__ID__"]) }}';

function getDeleteFinishedGoodsUrl(itemStockId) {
    return deleteFinishedGoodsUrlTemplate.replace('__ID__', encodeURIComponent(itemStockId));
}

async function deleteFinishedGoods(itemStockId, itemName) {
    if (!itemStockId) {
        alert('ID produk tidak valid.');
        return;
    }

    const confirmDelete = confirm(`Hapus data produk "${itemName}"? Tindakan ini tidak dapat dibatalkan.`);
    if (!confirmDelete) {
        return;
    }

    try {
        const response = await fetch(getDeleteFinishedGoodsUrl(itemStockId), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                    document.querySelector('input[name="_token"]')?.value
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showNotification(`<i class="bi bi-check-lg"></i> ${data.message}`, 'success');
            setTimeout(() => location.reload(), 1500);
            return;
        }

        showNotification(`<i class="bi bi-x-lg"></i> ${data.message || 'Gagal menghapus data produk.'}`, 'error');
    } catch (error) {
        console.error('Error:', error);
        showNotification(`<i class="bi bi-x-lg"></i> Terjadi kesalahan saat menghapus data.`, 'error');
    }
}

async function openDetailModal(itemStockId) {
    currentItemStockId = itemStockId;
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');
    
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="flex justify-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div></div>';

    try {
        const response = await fetch(`/admin/inventory/finished-goods/${itemStockId}`);
        const data = await response.json();

        content.innerHTML = `
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-slate-500">Nama Item</p>
                    <p class="text-lg font-semibold">${data.name_item || '-'}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Kode Item</p>
                    <p class="text-sm text-slate-700">${data.code_item || '-'}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Kategori</p>
                    <p class="text-sm text-slate-700">${data.category || 'Tanpa Kategori'}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Inventori</p>
                    <p class="text-sm text-slate-700">${data.inventory || '-'}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Stok Saat Ini</p>
                    <p class="text-lg font-semibold">${parseInt(data.stock).toLocaleString('id-ID')}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Buffer Stock</p>
                    <p class="text-lg font-semibold">${parseInt(data.buffer_stock).toLocaleString('id-ID')}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Stock Difference</p>
                    <span class="inline-block px-3 py-1 text-white rounded ${data.stock_difference > 0 ? 'bg-red-500' : 'bg-emerald-500'}">
                        ${Math.abs(data.stock_difference).toLocaleString('id-ID')}
                    </span>
                </div>
            </div>
        `;

        document.getElementById('editFromDetailBtn').onclick = function() {
            closeDetailModal();
            openEditModal(itemStockId);
        };
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = '<p class="text-red-600 text-center">Gagal memuat data</p>';
    }
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

async function openEditModal(itemStockId) {
    currentItemStockId = itemStockId;
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    
    modal.classList.remove('hidden');
    document.getElementById('editError').classList.add('hidden');

    try {
        const response = await fetch(`/admin/inventory/finished-goods/${itemStockId}`);
        const data = await response.json();

        document.getElementById('editItemName').textContent = data.name_item || '-';
        document.getElementById('editItemCode').textContent = data.code_item ? `Kode: ${data.code_item}` : '-';
        document.getElementById('editBufferStock').textContent = parseInt(data.buffer_stock).toLocaleString('id-ID');
        document.getElementById('editStock').value = data.stock;

        form.action = `/admin/inventory/finished-goods/${itemStockId}`;
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('editError').textContent = 'Gagal memuat data';
        document.getElementById('editError').classList.remove('hidden');
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const stock = document.getElementById('editStock').value;
    const submitBtn = this.querySelector('button[type="submit"]');
    const submitText = document.getElementById('submitText');
    const submitSpinner = document.getElementById('submitSpinner');

    submitText.classList.add('hidden');
    submitSpinner.classList.remove('hidden');
    submitBtn.disabled = true;

    try {
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                document.querySelector('input[name="_token"]')?.value
            },
            body: JSON.stringify({
                _method: 'PUT',
                stock: stock
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            closeEditModal();
            location.reload();
        } else {
            document.getElementById('editError').textContent = data.message || 'Gagal menyimpan data';
            document.getElementById('editError').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('editError').textContent = 'Terjadi kesalahan saat menyimpan';
        document.getElementById('editError').classList.remove('hidden');
    } finally {
        submitText.classList.remove('hidden');
        submitSpinner.classList.add('hidden');
        submitBtn.disabled = false;
    }
});

/**
 * Update Buffer Stock dari CSV ROP values
 */
async function updateBufferStockFromRop() {
    const btn = document.getElementById('updateRopBtn');
    const text = document.getElementById('updateRopText');
    const spinner = document.getElementById('updateRopSpinner');
    
    // Disable button and show spinner
    btn.disabled = true;
    text.classList.add('hidden');
    spinner.classList.remove('hidden');

    try {
        const response = await fetch('{{ route("admin.inventory.finished-goods.update-rop") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                document.querySelector('input[name="_token"]')?.value
            },
            body: JSON.stringify({
                inventory_id: 1
            })
        });

        const data = await response.json();

        if (data.success) {
            // Show success notification
            const summary = data.data;
            const message = `
                <div class="text-left">
                    <strong>${data.message}</strong><br/>
                    <small class="text-gray-600">
                        Updated: ${summary.updated} | 
                        Skipped: ${summary.skipped} | 
                        Errors: ${summary.errors}
                    </small>
                </div>
            `;
            showNotification(`<i class="bi bi-check-lg"></i> ${message}`, 'success', 5000);
            
            // Reload page after 2 seconds
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification(`<i class="bi bi-x-lg"></i> ${data.message}`, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(`<i class="bi bi-x-lg"></i> Terjadi kesalahan: ${error.message}`, 'error');
    } finally {
        // Re-enable button
        btn.disabled = false;
        text.classList.remove('hidden');
        spinner.classList.add('hidden');
    }
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
