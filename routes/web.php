<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DeviceTypeController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Admin\RestoreController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentManagerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\TechnicianController;
use Illuminate\Support\Facades\Route;

// ===== عام =====
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/track', [HomeController::class, 'track'])->name('track');
Route::post('/track', [HomeController::class, 'trackSearch'])->name('track.search');

// ===== المصادقة =====
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ===== محتاج تسجيل دخول =====
Route::middleware('auth')->group(function () {

    // العميل
    Route::get('/request', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/request', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/my-requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/my-requests/{serviceRequest}', [RequestController::class, 'show'])->name('requests.show');
    Route::post('/my-requests/{serviceRequest}/review', [RequestController::class, 'review'])->name('requests.review');
    Route::get('/my-requests/{serviceRequest}/receipt', [RequestController::class, 'receipt'])->name('requests.receipt');

    // حسابي
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    // الفني
    Route::middleware('role:TECHNICIAN')->group(function () {
        Route::get('/technician', [TechnicianController::class, 'index'])->name('technician');
        Route::post('/technician/requests/{serviceRequest}/status', [TechnicianController::class, 'status'])->name('technician.status');
    });

    // الاستقبال (ست موظفين = أدمن + استقبال — الطلبات والعملاء والملاحظات)
    Route::middleware('can:staff')->prefix('reception')->name('reception.')->group(function () {
        Route::get('/', [ReceptionController::class, 'index'])->name('index');
        Route::post('/requests', [ReceptionController::class, 'store'])->name('store');
        Route::get('/requests/{serviceRequest}', [ReceptionController::class, 'show'])->name('show');
        Route::put('/requests/{serviceRequest}', [ReceptionController::class, 'update'])->name('update');
        Route::get('/requests/{serviceRequest}/receipt', [ReceptionController::class, 'receipt'])->name('receipt');
        Route::post('/requests/{serviceRequest}/notes', [ReceptionController::class, 'storeNote'])->name('notes.store');
        Route::delete('/requests/notes/{requestNote}', [ReceptionController::class, 'destroyNote'])->name('notes.destroy');

        // مرتجع للفني — العميل رجّع الجهاز والشركة هتصلحه تاني (إنذار)
        Route::post('/requests/{serviceRequest}/return', [ReceptionController::class, 'returnRequest'])->name('return');

        // بيانات حية (JSON) — شريط غرفة العمليات يتحدث تلقائياً
        Route::get('/live-stats', [ReceptionController::class, 'liveStats'])->name('live-stats');

        // بحث عميل بالهاتف (JSON)
        Route::get('/lookup', [ReceptionController::class, 'lookup'])->name('lookup');

        // العملاء
        Route::get('/customers', [ReceptionController::class, 'customers'])->name('customers');
        Route::get('/customers/{user}', [ReceptionController::class, 'customerShow'])->name('customers.show');
        Route::post('/customers/{user}/notes', [ReceptionController::class, 'storeCustomerNote'])->name('customers.notes');

        // الإشعارات
        Route::get('/notifications', [ReceptionController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/read', [ReceptionController::class, 'markRead'])->name('notifications.read');
    });

    // الاستقبال — محرر الطلبات (أدمن + استقبال + مدير قسم: المخزن والقطع والمصروفات والشركاء)
    Route::middleware('can:request-editor')->prefix('reception')->name('reception.')->group(function () {
        // المخزن
        Route::get('/inventory', [ReceptionController::class, 'inventory'])->name('inventory');
        Route::post('/inventory', [ReceptionController::class, 'storeItem'])->name('inventory.store');

        // القطع المستخدمة في الطلبات
        Route::post('/requests/{serviceRequest}/parts', [ReceptionController::class, 'storePart'])->name('parts.store');
        Route::delete('/requests/parts/{usedPart}', [ReceptionController::class, 'destroyPart'])->name('parts.destroy');

        // الإحصائيات
        Route::get('/stats', [ReceptionController::class, 'stats'])->name('stats');

        // التقرير اليومي المفصل — كل ما حدث في اليوم (وارد/تسليمات/شركاء/تسويات/حالات جديدة)
        Route::get('/daily', [ReceptionController::class, 'daily'])->name('daily');

        // المصروفات
        Route::get('/expenses', [ReceptionController::class, 'expenses'])->name('expenses');
        Route::post('/expenses', [ReceptionController::class, 'storeExpense'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [ReceptionController::class, 'destroyExpense'])->name('expenses.destroy');

        // الشركاء (إضافة فقط — التعديل والحذف للأدمن)
        Route::get('/partners', [ReceptionController::class, 'partners'])->name('partners');
        Route::post('/partners', [ReceptionController::class, 'storePartner'])->name('partners.store');
        Route::post('/partners/{partnerTechnician}/repairs', [ReceptionController::class, 'storePartnerRepair'])->name('partners.repairs');
        Route::put('/partners/repairs/{partnerRepair}', [ReceptionController::class, 'updatePartnerRepair'])->name('partners.repairs.update');
        Route::post('/partners/{partnerTechnician}/settlements', [ReceptionController::class, 'storePartnerSettlement'])->name('partners.settlements');
    });

    // مدير القسم — 7 تبويبات مثل الأصل
    Route::middleware('role:DEPARTMENT_MANAGER')->prefix('department')->name('department.')->group(function () {
        Route::get('/', [DepartmentManagerController::class, 'index'])->name('index');
        Route::get('/requests', [DepartmentManagerController::class, 'requests'])->name('requests');
        Route::get('/requests/{serviceRequest}', [DepartmentManagerController::class, 'show'])->name('show');
        Route::post('/requests', [DepartmentManagerController::class, 'storeRequest'])->name('store');

        // المبيعات
        Route::get('/sales', [DepartmentManagerController::class, 'sales'])->name('sales');
        Route::post('/sales', [DepartmentManagerController::class, 'storeSale'])->name('sales.store');

        // الفنيين
        Route::get('/technicians', [DepartmentManagerController::class, 'technicians'])->name('technicians');
        Route::post('/technicians', [DepartmentManagerController::class, 'storeTechnician'])->name('technicians.store');
        Route::post('/technicians/{user}/toggle', [DepartmentManagerController::class, 'toggleTechnician'])->name('technicians.toggle');

        // العملاء
        Route::get('/customers', [DepartmentManagerController::class, 'customers'])->name('customers');

        // المخزن
        Route::get('/inventory', [DepartmentManagerController::class, 'inventory'])->name('inventory');
        Route::post('/inventory', [DepartmentManagerController::class, 'storeItem'])->name('inventory.store');
        Route::put('/inventory/{inventoryItem}', [DepartmentManagerController::class, 'updateItem'])->name('inventory.update');

        // التقرير المالي
        Route::get('/financial', [DepartmentManagerController::class, 'financial'])->name('financial');

        // الإشعارات
        Route::get('/notifications', [DepartmentManagerController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/read', [DepartmentManagerController::class, 'markRead'])->name('notifications.read');
    });

    // لوحة الأدمن
    Route::middleware('role:ADMIN')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/notifications', [DashboardController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/read', [DashboardController::class, 'markRead'])->name('notifications.read');

        Route::get('/activity', [DashboardController::class, 'activity'])->name('activity');
        Route::get('/heatmap', [DashboardController::class, 'heatmap'])->name('heatmap');
        Route::get('/reminders', [DashboardController::class, 'reminders'])->name('reminders');
        Route::put('/reminders', [DashboardController::class, 'remindersUpdate'])->name('reminders.update');
        Route::get('/performance', [DashboardController::class, 'performance'])->name('performance');
        Route::get('/backup', [DashboardController::class, 'backup'])->name('backup');

        // استعادة البيانات من مشروع Next.js القديم
        Route::get('/restore', [RestoreController::class, 'index'])->name('restore');
        Route::post('/restore/upload', [RestoreController::class, 'upload'])->name('restore.upload');
        Route::post('/restore/run', [RestoreController::class, 'run'])->name('restore.run');
        Route::post('/restore/cancel', [RestoreController::class, 'cancel'])->name('restore.cancel');

        // تحديد سعر صيانة شريك بعدين (السعر اختياري عند الاستلام)
        Route::put('/partners/repairs/{partnerRepair}', [PartnerController::class, 'updateRepair'])->name('partners.repairs.update');
        Route::post('/reset', [DashboardController::class, 'reset'])->name('reset');
        Route::get('/account', [DashboardController::class, 'account'])->name('account');
        Route::put('/account', [DashboardController::class, 'updateAccount'])->name('account.update');

        // الطلبات
        Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{serviceRequest}', [AdminRequestController::class, 'show'])->name('requests.show');
        Route::put('/requests/{serviceRequest}', [AdminRequestController::class, 'update'])->name('requests.update');
        Route::post('/requests/{serviceRequest}/assign', [AdminRequestController::class, 'assign'])->name('requests.assign');
        Route::post('/requests/{serviceRequest}/notes', [AdminRequestController::class, 'storeNote'])->name('requests.notes');
        Route::delete('/requests/notes/{requestNote}', [AdminRequestController::class, 'destroyNote'])->name('requests.notes.destroy');
        Route::post('/requests/{serviceRequest}/parts', [AdminRequestController::class, 'storePart'])->name('requests.parts');
        Route::delete('/requests/parts/{usedPart}', [AdminRequestController::class, 'destroyPart'])->name('requests.parts.destroy');
        Route::delete('/requests/{serviceRequest}', [AdminRequestController::class, 'destroy'])->name('requests.destroy');

        // العملاء
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{user}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('/customers/{user}/notes', [CustomerController::class, 'storeNote'])->name('customers.notes');

        // الموظفون
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // الأقسام
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        // أنواع الأجهزة
        Route::get('/device-types', [DeviceTypeController::class, 'index'])->name('device-types.index');
        Route::post('/device-types', [DeviceTypeController::class, 'store'])->name('device-types.store');
        Route::put('/device-types/{deviceType}', [DeviceTypeController::class, 'update'])->name('device-types.update');
        Route::delete('/device-types/{deviceType}', [DeviceTypeController::class, 'destroy'])->name('device-types.destroy');

        // المخزون
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::put('/inventory/{inventoryItem}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{inventoryItem}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
        Route::post('/inventory/{inventoryItem}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

        // المصروفات
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        // المالية
        Route::get('/financial', [FinancialReportController::class, 'index'])->name('financial.index');

        // الشركاء
        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::put('/partners/{partnerTechnician}', [PartnerController::class, 'update'])->name('partners.update');
        Route::post('/partners/{partnerTechnician}/toggle', [PartnerController::class, 'toggle'])->name('partners.toggle');
        Route::post('/partners/{partnerTechnician}/repairs', [PartnerController::class, 'storeRepair'])->name('partners.repairs');
        Route::post('/partners/{partnerTechnician}/settlements', [PartnerController::class, 'storeSettlement'])->name('partners.settlements');

        // التقييمات
        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
        Route::post('/reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');
        Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

        // الإعدادات
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
