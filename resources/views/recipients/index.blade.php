@extends('layouts.app')

@section('title', 'Penerima WhatsApp')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-white">Daftar Penerima Notifikasi WhatsApp</h1>
            <p class="text-xs text-slate-400">Penyebaran peringatan banjir bersegmen wilayah RT dan peranan satgas</p>
        </div>
        <button onclick="openAddRecipientModal()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-white text-slate-900 text-xs font-semibold transition shadow-xs">
            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
            <span>Tambah Penerima</span>
        </button>
    </div>

    <!-- Cakupan Wilayah RT (Summary Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($zones as $z)
            <div class="glass-card p-3.5 rounded-xl border border-slate-800 text-center">
                <span class="text-[11px] font-bold text-slate-400 block">{{ $z->code }}</span>
                <span class="font-mono text-xl font-extrabold text-white mt-0.5 block">{{ $z->recipients_count }}</span>
                <span class="text-[10px] text-emerald-400 font-semibold block">
                    penerima
                </span>
            </div>
        @endforeach
    </div>

    <!-- Tabel Penerima -->
    <div class="glass-card rounded-2xl overflow-hidden border border-slate-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Nama Penerima</th>
                        <th class="py-3 px-4">Nomor WhatsApp</th>
                        <th class="py-3 px-4">Wilayah (RT)</th>
                        <th class="py-3 px-4">Peranan</th>
                        <th class="py-3 px-4">Batas Min Level</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($recipients as $r)
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 font-semibold text-white">{{ $r->full_name }}</td>
                            <td class="py-3 px-4 font-mono text-slate-300">
                                {{ substr($r->phone_e164, 0, 7) . '****' . substr($r->phone_e164, -2) }}
                            </td>
                            <td class="py-3 px-4 font-medium">{{ $r->zone?->name ?? 'Semua RT' }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $r->role_tag === 'WARGA' ? 'bg-slate-800 text-slate-300' : 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' }}">
                                    {{ $r->role_tag }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold {{ $r->min_level === 3 ? 'text-rose-400' : ($r->min_level === 2 ? 'text-orange-400' : 'text-amber-400') }}">
                                    Level {{ $r->min_level }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="w-2 h-2 rounded-full inline-block mr-1 {{ $r->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                <span class="text-[11px]">{{ $r->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <form action="{{ route('recipients.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus nomor ini dari daftar notifikasi?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-400 hover:text-rose-300 text-[11px] font-semibold">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">Belum ada penerima terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $recipients->links() }}
        </div>
    </div>
</div>

<!-- Modal Tambah Penerima -->
<div id="add-recipient-modal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-md w-full p-6 rounded-2xl border border-slate-700 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-white flex items-center space-x-2">
                <i data-lucide="user-plus" class="w-5 h-5 text-emerald-400"></i>
                <span>Tambah Penerima Notifikasi</span>
            </h3>
            <button onclick="closeAddRecipientModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('recipients.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap</label>
                <input type="text" name="full_name" required placeholder="Contoh: Pak Budi Santoso" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nomor WhatsApp (Format E.164)</label>
                <input type="text" name="phone_e164" required placeholder="+6281234567890" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Wilayah RT</label>
                    <select name="zone_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}">{{ $z->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Peranan</label>
                    <select name="role_tag" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                        <option value="WARGA">Warga</option>
                        <option value="KETUA_RT">Ketua RT</option>
                        <option value="SATPAM">Satpam</option>
                        <option value="SATGAS">Satgas</option>
                        <option value="PENGURUS_RW">Pengurus RW</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Tingkat Min Level Notifikasi</label>
                <select name="min_level" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                    <option value="1">Level 1 (Waspada) ke atas</option>
                    <option value="2">Level 2 (Siaga) ke atas</option>
                    <option value="3" selected>Level 3 (Kritis) saja</option>
                </select>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeAddRecipientModal()" class="px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-white text-slate-900 text-xs font-semibold transition shadow-xs">Simpan Penerima</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddRecipientModal() {
        const modal = document.getElementById('add-recipient-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeAddRecipientModal() {
        const modal = document.getElementById('add-recipient-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
