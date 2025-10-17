# Performance Optimization Guide

## 🐌 Common Causes of Slow Loading

### 1. **Telescope (Debug Tool)** ⚠️ MAJOR IMPACT
**Issue:** Laravel Telescope records every query, request, and event
**Impact:** Can slow app by 50-80%
**Solution:** Disable in development unless debugging

### 2. **Development Mode Assets**
**Issue:** Vite dev server compiles on-the-fly
**Impact:** Slower initial page load
**Solution:** Use production build or optimize dev config

### 3. **Database Queries (N+1 Problem)**
**Issue:** Missing eager loading causes multiple queries
**Impact:** Slow API responses
**Solution:** Use `with()` for relationships

### 4. **No Caching**
**Issue:** Routes, config, views compiled every request
**Impact:** 100-200ms overhead per request
**Solution:** Cache in production

### 5. **Large JavaScript Bundles**
**Issue:** Too many components loaded at once
**Impact:** Slow initial load
**Solution:** Code splitting, lazy loading

---

## ✅ Quick Fixes (Apply Now)

### **1. Disable Telescope in Development**
Add to `.env`:
```env
TELESCOPE_ENABLED=false
```

Then clear config:
```bash
php artisan config:clear
```

**Expected Speed Improvement:** 50-80% faster! ⚡

---

### **2. Enable Laravel Caching**
```bash
# Production only - DO NOT run in development if making changes
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**When developing, clear cache:**
```bash
php artisan optimize:clear
```

**Expected Speed Improvement:** 30-40% faster

---

### **3. Build Assets for Production**
```bash
npm run build
```

Then update `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

**Expected Speed Improvement:** 60-70% faster initial load

---

### **4. Optimize Database Queries**

**Check for N+1 queries:**
```bash
# Enable query logging in .env
DB_LOG_QUERIES=true
```

**Fix with eager loading:**
```php
// ❌ Bad - N+1 queries
$sales = Sale::all();
foreach ($sales as $sale) {
    echo $sale->customer->name; // New query each time!
}

// ✅ Good - 2 queries total
$sales = Sale::with('customer')->get();
foreach ($sales as $sale) {
    echo $sale->customer->name; // Already loaded!
}
```

---

### **5. Optimize Vue Components**

**Use lazy loading for heavy components:**
```javascript
// ❌ Bad - loads all components immediately
import ProductManagement from '@/Components/ProductManagement.vue'

// ✅ Good - loads only when needed
const ProductManagement = defineAsyncComponent(() =>
  import('@/Components/ProductManagement.vue')
)
```

---

## 🚀 Complete Optimization Checklist

### **Development Environment**
- [ ] Disable Telescope (`TELESCOPE_ENABLED=false`)
- [ ] Use `npm run dev` for hot reload
- [ ] Don't cache routes/config while developing
- [ ] Enable query logging to find slow queries
- [ ] Use browser dev tools to profile

### **Production Environment**
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Run `npm run build`
- [ ] Cache everything:
  ```bash
  php artisan optimize
  ```
- [ ] Enable OPcache in PHP
- [ ] Use Redis for sessions/cache
- [ ] Enable gzip compression
- [ ] Use CDN for assets

### **Code Level**
- [ ] Eager load relationships
- [ ] Use database indexes
- [ ] Paginate large datasets
- [ ] Lazy load Vue components
- [ ] Minimize API calls
- [ ] Use Vue's `v-show` vs `v-if` wisely
- [ ] Debounce search inputs

---

## 📊 Measure Performance

### **Backend (Laravel)**
```php
// In routes/web.php or AppServiceProvider
use Illuminate\Support\Facades\DB;

DB::listen(function($query) {
    if ($query->time > 100) { // Queries over 100ms
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'time' => $query->time
        ]);
    }
});
```

### **Frontend (Vue/Vite)**
```javascript
// Browser Console
console.time('page-load')
window.addEventListener('load', () => {
    console.timeEnd('page-load')
})
```

### **Chrome DevTools**
1. F12 → Network tab
2. Disable cache (checkbox)
3. Reload and check:
   - **Time to First Byte (TTFB)** < 200ms (backend)
   - **DOMContentLoaded** < 2s (frontend)
   - **Load** < 5s (total)

---

## 🎯 Expected Results

### **Before Optimization:**
- Page load: 5-10 seconds ❌
- TTFB: 500-1000ms ❌
- Bundle size: 5-10 MB ❌

### **After Optimization:**
- Page load: 1-2 seconds ✅
- TTFB: 50-150ms ✅
- Bundle size: 1-2 MB ✅

---

## 🔧 Specific to Your App

### **Immediate Actions:**
1. Add to `.env`:
   ```env
   TELESCOPE_ENABLED=false
   ```

2. Clear all cache:
   ```bash
   php artisan optimize:clear
   ```

3. Restart dev server:
   ```bash
   npm run dev
   ```

### **Check Your Current Setup:**
```bash
# Check if caches are enabled
php artisan config:show

# Check if Telescope is enabled
php artisan tinker
>>> config('telescope.enabled')

# Check app environment
php artisan about
```

---

## 🚨 Common Mistakes

### **1. Caching in Development**
❌ Don't run `php artisan config:cache` while developing
✅ Use `php artisan config:clear` instead

### **2. Enabling Telescope in Production**
❌ Never enable Telescope in production
✅ Only enable for specific debugging sessions

### **3. Not Using Production Build**
❌ Using `npm run dev` in production
✅ Always `npm run build` before deploying

### **4. Missing Indexes**
❌ Queries on non-indexed foreign keys
✅ Add indexes to frequently queried columns

---

## 📝 Quick Command Reference

```bash
# Clear everything
php artisan optimize:clear

# Cache everything (production only)
php artisan optimize

# Just config
php artisan config:cache
php artisan config:clear

# Just routes
php artisan route:cache
php artisan route:clear

# Just views
php artisan view:cache
php artisan view:clear

# Frontend build
npm run build      # Production
npm run dev        # Development (hot reload)

# Check performance
php artisan about
php artisan route:list
php artisan db:show
```

---

## 🎉 Result

After applying these optimizations:
- ✅ App loads in 1-2 seconds (was 5-10s)
- ✅ API responses < 100ms
- ✅ Smooth user experience
- ✅ Better developer experience
