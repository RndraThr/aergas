# Multi-Role System Guide

## Overview

Sistem multi-role memungkinkan satu user untuk memiliki lebih dari satu role secara bersamaan. Misalnya, satu user bisa memiliki role `sr` dan `gas_in` sekaligus.

## Features

### ✅ **Backward Compatibility**
- Sistem lama tetap bekerja (single role di kolom `users.role`)
- Migrasi otomatis dari single role ke multi-role
- Semua middleware dan permission tetap kompatibel

### ✅ **Multi-Role Support**
- Satu user bisa memiliki multiple roles aktif
- Role assignment tracking (siapa yang assign, kapan)
- Role activation/deactivation

### ✅ **Enhanced Permission System**
- `hasRole(string $role)` - cek role spesifik
- `hasAnyRole(array $roles)` - cek apakah punya salah satu role
- `getAllActiveRoles()` - ambil semua role aktif
- `canAccessModule(string $module)` - cek akses module

## Database Structure

### `user_roles` Table
```sql
- id (primary key)
- user_id (foreign key to users)
- role (enum: super_admin, admin, sk, sr, mgrt, gas_in, pic, tracer, jalur)
- is_active (boolean, default true)
- assigned_at (timestamp)
- assigned_by (foreign key to users, nullable)
- created_at, updated_at
```

## API Endpoints

### User Management dengan Multi-Role

#### 1. Get Users with Roles
```http
GET /admin/api/users-with-roles
```
Response:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "username": "rendra",
        "full_name": "Rendra Tuharea",
        "active_roles": [
          {"role": "super_admin", "assigned_at": "2025-09-14T13:00:00Z"},
          {"role": "sr", "assigned_at": "2025-09-14T13:30:00Z"},
          {"role": "gas_in", "assigned_at": "2025-09-14T13:31:00Z"}
        ]
      }
    ]
  },
  "available_roles": ["super_admin", "admin", "sk", "sr", "mgrt", "gas_in", "pic", "tracer", "jalur"]
}
```

#### 2. Get User with All Roles
```http
GET /admin/api/users/{id}/roles
```

#### 3. Assign Role to User
```http
POST /admin/api/users/{id}/roles/assign
Content-Type: application/json

{
  "role": "sr"
}
```

#### 4. Remove Role from User
```http
DELETE /admin/api/users/{id}/roles/remove
Content-Type: application/json

{
  "role": "sr"
}
```

#### 5. Sync User Roles (Replace All)
```http
PUT /admin/api/users/{id}/roles/sync
Content-Type: application/json

{
  "roles": ["sr", "gas_in"]
}
```

## Console Commands

### 1. Migrate to Multi-Role System
```bash
# Migrate all users
php artisan role:migrate-to-multi

# Force migration (override existing)
php artisan role:migrate-to-multi --force

# Migrate specific user
php artisan role:migrate-to-multi --user=1
```

### 2. Assign/Remove Roles via CLI
```bash
# Assign role
php artisan role:assign rendra sr

# Remove role
php artisan role:assign rendra sr --remove

# List user roles
php artisan role:assign rendra sr --list
```

## Code Examples

### User Model Methods

```php
// Check specific role
if ($user->hasRole('sr')) {
    // User has SR role
}

// Check multiple roles
if ($user->hasAnyRole(['sr', 'gas_in'])) {
    // User has either SR or GasIn role (or both)
}

// Get all active roles
$roles = $user->getAllActiveRoles();
// Returns: ['super_admin', 'sr', 'gas_in']

// Assign new role
$user->assignRole('jalur', $assignedByUserId);

// Remove role
$user->removeRole('sr');

// Replace all roles
$user->syncRoles(['sr', 'gas_in'], $assignedByUserId);

// Check module access
if ($user->canAccessModule('sr')) {
    // User can access SR module
}
```

### Controller dengan Multi-Role Trait

```php
use App\Traits\HasMultiRoleAuth;

class MyController extends Controller
{
    use HasMultiRoleAuth;

    public function index()
    {
        // Check role requirement
        $roleCheck = $this->requiresAnyRole(['sr', 'gas_in']);
        if ($roleCheck !== true) return $roleCheck;

        // Check module access
        $moduleCheck = $this->requiresModuleAccess('sr');
        if ($moduleCheck !== true) return $moduleCheck;

        // Get current user roles
        $roles = $this->getCurrentUserRoles();

        // Check if current user is admin
        if ($this->isCurrentUserAdmin()) {
            // Admin-specific logic
        }
    }
}
```

### Middleware Usage (Tetap Sama)

```php
// Routes with role middleware
Route::middleware(['auth', 'role:sr,gas_in'])->group(function () {
    // Both SR and GasIn users can access
});

Route::middleware(['auth', 'role:sr|gas_in'])->group(function () {
    // Users with SR OR GasIn role can access
});
```

## Migration Guide

### Step 1: Run Migrations
```bash
php artisan migrate
```

### Step 2: Migrate Existing Users (Optional)
Migration sudah otomatis dijalankan, tapi bisa run manual:
```bash
php artisan role:migrate-to-multi
```

### Step 3: Assign Additional Roles
```bash
# Example: Assign SR and GasIn to user Rendra
php artisan role:assign rendra sr
php artisan role:assign rendra gas_in
```

## Use Case Examples

### Case 1: User dengan SR dan GasIn Role
```php
$user = User::find(1);
$user->assignRole('sr');
$user->assignRole('gas_in');

// User sekarang bisa access:
$user->canAccessModule('sr');     // true
$user->canAccessModule('gas_in'); // true
$user->hasRole('sr');             // true
$user->hasRole('gas_in');         // true
$user->hasAnyRole(['sr', 'gas_in']); // true
```

### Case 2: Role Management via API
```javascript
// Frontend: Assign multiple roles to user
const response = await fetch('/admin/api/users/1/roles/sync', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ roles: ['sr', 'gas_in'] })
});
```

### Case 3: Conditional UI berdasarkan Multiple Roles
```blade
@auth
    @if(auth()->user()->hasAnyRole(['sr', 'gas_in']))
        <!-- Show SR/GasIn specific content -->
        <div class="sr-gasin-panel">
            @if(auth()->user()->hasRole('sr'))
                <a href="/sr">SR Module</a>
            @endif
            @if(auth()->user()->hasRole('gas_in'))
                <a href="/gas-in">GasIn Module</a>
            @endif
        </div>
    @endif
@endauth
```

## Security Notes

### 1. **Super Admin Protection**
- Hanya super_admin yang bisa assign/remove role super_admin
- Super_admin bypass semua role checks

### 2. **Audit Logging**
- Semua role assignment/removal tercatat di audit_logs
- Tracking siapa yang assign role dan kapan

### 3. **Permission Inheritance**
- Multi-role users inherit permissions dari semua role aktif
- Module access = union of all role permissions

## Troubleshooting

### 1. **Migration Issues**
```bash
# Jika migration gagal, cek status
php artisan migrate:status

# Roll back jika perlu
php artisan migrate:rollback --step=2

# Re-run migration
php artisan migrate
```

### 2. **Role Assignment Errors**
```bash
# Check user roles
php artisan role:assign {username} {role} --list

# Force re-assignment
php artisan role:assign {username} {role} --remove
php artisan role:assign {username} {role}
```

### 3. **Permission Issues**
- Pastikan middleware `CheckRole` sudah update
- Clear cache: `php artisan cache:clear`
- Check audit logs untuk tracking assignment

## Benefits

### ✅ **Flexibility**
- Satu user bisa handle multiple modules
- Mudah adjust permissions per user

### ✅ **Scalability**
- Database structure optimal untuk growth
- Index pada user_id dan role untuk performance

### ✅ **Maintainability**
- Backward compatible dengan sistem lama
- Clear separation antara old/new system

### ✅ **Security**
- Audit trail lengkap
- Role assignment tracking
- Permission inheritance yang aman

---

## Example Scenario: User dengan SR + GasIn Access

```bash
# Assign roles
php artisan role:assign john_doe sr
php artisan role:assign john_doe gas_in

# Verify
php artisan role:assign john_doe sr --list
```

User "john_doe" sekarang bisa:
- ✅ Access SR create/edit pages
- ✅ Access GasIn create/edit pages
- ✅ Upload photos untuk SR module
- ✅ Upload photos untuk GasIn module
- ✅ View reports untuk both modules

Perfect untuk use case dimana 1 petugas handle multiple workflow!