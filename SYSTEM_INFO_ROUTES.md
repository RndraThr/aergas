# 🛠️ System Information Routes

## 🔐 Security Features

### **Authentication & Authorization**
- ✅ **Auth Required**: Must be logged in
- ✅ **Super Admin Only**: Role `super_admin` required
- ✅ **Double Security Check**: Controller level validation
- ✅ **Access Logging**: User info displayed in output

## 📍 Available Routes

### **1. PHP Info Page**
```
URL: /admin/system/phpinfo
Route Name: admin.system.phpinfo
Method: GET
Access: Super Admin Only
```

**Features:**
- ✅ Complete PHP configuration
- ✅ Loaded extensions
- ✅ Environment variables  
- ✅ AERGAS branded interface
- ✅ Security warning banner
- ✅ User info & timestamp

### **2. System Info API**
```
URL: /admin/system/info  
Route Name: admin.system.info
Method: GET
Access: Super Admin Only
Response: JSON
```

**Returns:**
```json
{
  "php_version": "8.x.x",
  "laravel_version": "11.x.x", 
  "memory_limit": "128M",
  "upload_max_filesize": "10M",
  "post_max_size": "12M",
  "max_execution_time": "30",
  "loaded_extensions": [...],
  "environment": "production",
  "debug_mode": false,
  "database": "mysql",
  "cache_driver": "file",
  "queue_driver": "sync"
}
```

## 🚀 Usage Examples

### **Access via Browser:**
```bash
# Login as super_admin first, then visit:
https://yourdomain.com/admin/system/phpinfo
https://yourdomain.com/admin/system/info
```

### **Access via API:**
```bash
curl -H "Authorization: Bearer your-token" \
     -H "Accept: application/json" \
     https://yourdomain.com/admin/system/info
```

### **Laravel Route Helper:**
```php
// Generate URLs in Blade templates
{{ route('admin.system.phpinfo') }}
{{ route('admin.system.info') }}
```

## ⚠️ Security Considerations

### **Production Safety:**
- ✅ No public access
- ✅ Role-based restrictions  
- ✅ Audit trail (user logged)
- ✅ Styled with warnings

### **Information Exposure:**
- ⚠️ Contains sensitive system info
- ⚠️ Should only be accessed when needed
- ⚠️ Monitor access logs

## 🔧 Development vs Production

### **Development (.env):**
```env
APP_ENV=local
APP_DEBUG=true
```

### **Production (.env):**
```env  
APP_ENV=production
APP_DEBUG=false
```

## 📋 Troubleshooting

### **403 Access Denied:**
- Check user role: `user.hasRole('super_admin')`
- Verify authentication: `auth()->check()`

### **Route Not Found:**
- Run: `php artisan route:cache`
- Check: `php artisan route:list --name=system`

---
**⚡ Ready to use! Access restricted to Super Admin only.**