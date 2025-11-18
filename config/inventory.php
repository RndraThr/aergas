<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration settings for the inventory
    | management system including warehouse types, item units, transaction
    | types, and other inventory-related settings.
    |
    */

    'warehouse_types' => [
        'pusat' => 'Gudang Pusat',
        'cabang' => 'Gudang Cabang',
        'proyek' => 'Gudang Proyek',
    ],

    'item_units' => [
        'm' => 'Meter (m)',
        'pcs' => 'Pieces (pcs)',
        'unit' => 'Unit',
        'kg' => 'Kilogram (kg)',
        'liter' => 'Liter (l)',
        'roll' => 'Roll',
        'set' => 'Set',
    ],

    'transaction_types' => [
        'in' => 'Stock In',
        'out' => 'Stock Out',
        'transfer' => 'Transfer',
        'adjustment' => 'Adjustment',
        'return' => 'Return',
    ],

    'transaction_statuses' => [
        'draft' => 'Draft',
        'pending' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
    ],

    'po_statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'partial_received' => 'Partially Received',
        'received' => 'Fully Received',
        'cancelled' => 'Cancelled',
    ],

    'opname_statuses' => [
        'draft' => 'Draft',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock Alert Settings
    |--------------------------------------------------------------------------
    */

    'low_stock_threshold' => 10, // Default reorder point if not set per item
    'enable_low_stock_alerts' => true,
    'enable_overstock_alerts' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-numbering Settings
    |--------------------------------------------------------------------------
    */

    'auto_generate_codes' => true,
    'code_prefixes' => [
        'warehouse' => 'WH',
        'item' => 'ITEM',
        'category' => 'CAT',
        'supplier' => 'SUP',
        'transaction' => 'TRX',
        'purchase_order' => 'PO',
        'goods_receipt' => 'GR',
        'stock_opname' => 'OPN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    'allowed_roles' => ['super_admin', 'admin', 'inventory'],
];
