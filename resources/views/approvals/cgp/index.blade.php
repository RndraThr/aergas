@extends('layouts.app')

@section('title', 'CGP Approval Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">CGP Approval Dashboard</h1>
            <p class="text-gray-600 mt-1">Final approval untuk foto yang sudah di-review Tracer</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('approvals.cgp.customers') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                📋 Lihat Semua Pelanggan
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 mr-4">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Pending</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 mr-4">
                    <span class="text-yellow-600 font-bold">SK</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">SK Ready</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['sk_ready'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 mr-4">
                    <span class="text-blue-600 font-bold">SR</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">SR Ready</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['sr_ready'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 mr-4">
                    <span class="text-green-600 font-bold">GI</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Gas In Ready</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['gas_in_ready'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 mr-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Approved Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['today_approved'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('approvals.cgp.customers', ['status' => 'sk_ready']) }}" 
                   class="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-center">
                        <span class="bg-yellow-100 text-yellow-600 p-2 rounded-lg mr-3 font-bold">SK</span>
                        <div>
                            <h3 class="font-medium text-gray-900">Review SK Ready</h3>
                            <p class="text-sm text-gray-600">{{ $stats['sk_ready'] }} items ready for CGP</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('approvals.cgp.customers', ['status' => 'sr_ready']) }}" 
                   class="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-center">
                        <span class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3 font-bold">SR</span>
                        <div>
                            <h3 class="font-medium text-gray-900">Review SR Ready</h3>
                            <p class="text-sm text-gray-600">{{ $stats['sr_ready'] }} items ready for CGP</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('approvals.cgp.customers', ['status' => 'gas_in_ready']) }}" 
                   class="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-center">
                        <span class="bg-green-100 text-green-600 p-2 rounded-lg mr-3 font-bold">GI</span>
                        <div>
                            <h3 class="font-medium text-gray-900">Review Gas In Ready</h3>
                            <p class="text-sm text-gray-600">{{ $stats['gas_in_ready'] }} items ready for CGP</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Recent Approved Photos</h2>
        </div>
        <div class="p-6">
            @if($recentActivities->count() > 0)
                <div class="space-y-4">
                    @foreach($recentActivities as $activity)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $activity->reff_id_pelanggan }}</h4>
                                <p class="text-sm text-gray-600">
                                    {{ $activity->pelanggan->nama_pelanggan ?? 'N/A' }} - 
                                    <span class="capitalize">{{ $activity->module_name }}</span> - 
                                    {{ $activity->photo_field_name }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">{{ $activity->cgp_approved_at->diffForHumans() }}</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                CGP Approved
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada aktivitas</h3>
                    <p class="mt-1 text-sm text-gray-500">Mulai review foto untuk melihat aktivitas terbaru</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection