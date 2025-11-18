<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Inventory System - Test Page</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Warehouses -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Warehouses ({{ $warehouses->count() }})</h2>
                <ul class="space-y-2">
                    @foreach($warehouses as $warehouse)
                        <li class="border-b pb-2">
                            <strong>{{ $warehouse->code }}</strong> - {{ $warehouse->name }}<br>
                            <span class="text-sm text-gray-600">{{ $warehouse->warehouse_type }} | {{ $warehouse->location }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Items ({{ $items->count() }})</h2>
                <ul class="space-y-2">
                    @foreach($items as $item)
                        <li class="border-b pb-2">
                            <strong>{{ $item->code }}</strong> - {{ $item->name }}<br>
                            <span class="text-sm text-gray-600">{{ $item->category->name }} | {{ $item->unit }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Categories -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Categories ({{ $categories->count() }})</h2>
                <ul class="space-y-2">
                    @foreach($categories as $category)
                        <li class="border-b pb-2">
                            <strong>{{ $category->code }}</strong> - {{ $category->name }}<br>
                            <span class="text-sm text-gray-600">{{ $category->items_count }} items</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Suppliers -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Suppliers ({{ $suppliers->count() }})</h2>
                <ul class="space-y-2">
                    @foreach($suppliers as $supplier)
                        <li class="border-b pb-2">
                            <strong>{{ $supplier->code }}</strong> - {{ $supplier->name }}<br>
                            <span class="text-sm text-gray-600">{{ $supplier->contact_person }} | {{ $supplier->phone }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Stock Transactions -->
        @if($transactions->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-bold mb-4">Recent Transactions ({{ $transactions->count() }})</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left">Transaction #</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Warehouse</th>
                            <th class="px-4 py-2 text-left">Item</th>
                            <th class="px-4 py-2 text-right">Quantity</th>
                            <th class="px-4 py-2 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $trx)
                        <tr class="border-b">
                            <td class="px-4 py-2">{{ $trx->transaction_number }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 text-xs rounded
                                    {{ $trx->transaction_type == 'in' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $trx->transaction_type == 'out' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $trx->transaction_type == 'transfer' ? 'bg-blue-100 text-blue-800' : '' }}
                                ">
                                    {{ $trx->transaction_type }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ $trx->warehouse->name }}</td>
                            <td class="px-4 py-2">{{ $trx->item->name }}</td>
                            <td class="px-4 py-2 text-right">{{ $trx->quantity }} {{ $trx->item->unit }}</td>
                            <td class="px-4 py-2">{{ $trx->transaction_date->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
            <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="/inventory/warehouses" class="bg-blue-500 text-white px-4 py-2 rounded text-center hover:bg-blue-600">
                    Warehouses
                </a>
                <a href="/inventory/items" class="bg-green-500 text-white px-4 py-2 rounded text-center hover:bg-green-600">
                    Items
                </a>
                <a href="/inventory/transactions" class="bg-purple-500 text-white px-4 py-2 rounded text-center hover:bg-purple-600">
                    Transactions
                </a>
                <a href="/inventory/suppliers" class="bg-yellow-500 text-white px-4 py-2 rounded text-center hover:bg-yellow-600">
                    Suppliers
                </a>
            </div>
        </div>
    </div>
</body>
</html>
