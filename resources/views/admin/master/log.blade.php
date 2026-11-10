@extends('layouts.admin')
@section('title', 'Log Aktivitas')

@section('content')
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left">@include('admin._sort-th', ['label' => 'Waktu', 'key' => 'created_at'])<th class="px-4 py-3">Pegawai</th>@include('admin._sort-th', ['label' => 'Aksi', 'key' => 'aksi'])<th class="px-4 py-3">Keterangan</th></tr></thead>
        <tbody>
        @forelse ($log as $l)
            <tr class="border-t"><td class="px-4 py-3 whitespace-nowrap">{{ $l->created_at?->format('d M Y H:i') }}</td><td class="px-4 py-3">{{ $l->user?->name ?? 'Sistem' }}</td><td class="px-4 py-3 font-mono text-xs">{{ $l->aksi }}</td><td class="px-4 py-3">{{ $l->keterangan }}</td></tr>
        @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada log aktivitas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $log->links() }}</div>
@endsection
