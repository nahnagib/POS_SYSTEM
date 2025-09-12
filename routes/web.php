<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    DashboardController,
    ProductController,
    CategoryController,
    OrderController,
    RoleController,
    UserController
};
use App\Http\Controllers\Auth\LoginController;


// ---------- Public / Guest ----------
Route::middleware('guest')->group(function () {
    // Landing: show login directly (or a public welcome if you want)
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('home');

    // Auth
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

// ---------- Logout (must be authenticated) ----------
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ---------- Authenticated redirect hub ----------
Route::get('/dashboard', function () {
    $u = auth()->user();
    if (!$u) return redirect()->route('login');

    // Redirect by role (Spatie)
    if ($u->hasRole('admin'))   return redirect()->route('admin.dashboard');
    if ($u->hasRole('cashier')) return redirect()->route('cashier.pos');

    // Fallback if no role yet
    return redirect()->route('admin.dashboard');
})->middleware('auth')->name('dashboard');




Route::middleware(['auth','role:admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Products (with variants inside form)
        Route::resource('products', ProductController::class);

        // Categories
        Route::resource('categories', CategoryController::class)
            ->except(['show']); // rarely needed

        // Orders (read-only, admin can also delete/void)
        Route::resource('orders', OrderController::class)
            ->only(['index','show','destroy']);

        // Roles (Spatie)
        Route::resource('roles', RoleController::class)
            ->only(['index','store','update','destroy']);

        // Users (assign roles)
        Route::resource('users', UserController::class)
            ->only(['index','store','update','destroy']);
    });


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|


Route::get('/', function () {
    return view('welcome');
});


// DEFAULT DASHBOARD & PROFILE
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
});

// ====== USERS ======
Route::middleware(['permission:user.menu'])->group(function () {
    Route::resource('/users', UserController::class)->except(['show']);
});

// ====== CUSTOMERS ======
Route::middleware(['permission:customer.menu'])->group(function () {
    Route::resource('/customers', CustomerController::class);
});

// ====== SUPPLIERS ======
Route::middleware(['permission:supplier.menu'])->group(function () {
    Route::resource('/suppliers', SupplierController::class);
});

// ====== EMPLOYEES ======
Route::middleware(['permission:employee.menu'])->group(function () {
    Route::resource('/employees', EmployeeController::class);
});

// ====== EMPLOYEE ATTENDENCE ======
Route::middleware(['permission:attendence.menu'])->group(function () {
    Route::resource('/employee/attendence', AttendenceController::class)->except(['show', 'update', 'destroy']);
});

// ====== SALARY EMPLOYEE ======
Route::middleware(['permission:salary.menu'])->group(function () {
    // PaySalary
    Route::resource('/pay-salary', PaySalaryController::class)->except(['show', 'create', 'edit', 'update']);
    Route::get('/pay-salary/history', [PaySalaryController::class, 'payHistory'])->name('pay-salary.payHistory');
    Route::get('/pay-salary/history/{id}', [PaySalaryController::class, 'payHistoryDetail'])->name('pay-salary.payHistoryDetail');
    Route::get('/pay-salary/{id}', [PaySalaryController::class, 'paySalary'])->name('pay-salary.paySalary');

    // Advance Salary
    Route::resource('/advance-salary', AdvanceSalaryController::class)->except(['show']);
});

// ====== PRODUCTS ======
Route::middleware(['permission:product.menu'])->group(function () {
    Route::get('/products/import', [ProductController::class, 'importView'])->name('products.importView');
    Route::post('/products/import', [ProductController::class, 'importStore'])->name('products.importStore');
    Route::get('/products/export', [ProductController::class, 'exportData'])->name('products.exportData');
    Route::resource('/products', ProductController::class);
});

// ====== CATEGORY PRODUCTS ======
Route::middleware(['permission:category.menu'])->group(function () {
    Route::resource('/categories', CategoryController::class);
});

// ====== POS ======
Route::middleware(['permission:pos.menu'])->group(function () {
    Route::get('/pos', [PosController::class,'index'])->name('pos.index');
    Route::post('/pos/add', [PosController::class, 'addCart'])->name('pos.addCart');
    Route::post('/pos/update/{rowId}', [PosController::class, 'updateCart'])->name('pos.updateCart');
    Route::get('/pos/delete/{rowId}', [PosController::class, 'deleteCart'])->name('pos.deleteCart');
    Route::post('/pos/invoice/create', [PosController::class, 'createInvoice'])->name('pos.createInvoice');
    Route::post('/pos/invoice/print', [PosController::class, 'printInvoice'])->name('pos.printInvoice');

    // Create Order
    Route::post('/pos/order', [OrderController::class, 'storeOrder'])->name('pos.storeOrder');
});

// ====== ORDERS ======
Route::middleware(['permission:orders.menu'])->group(function () {
    Route::get('/orders/pending', [OrderController::class, 'pendingOrders'])->name('order.pendingOrders');
    Route::get('/orders/complete', [OrderController::class, 'completeOrders'])->name('order.completeOrders');
    Route::get('/orders/details/{order_id}', [OrderController::class, 'orderDetails'])->name('order.orderDetails');
    Route::put('/orders/update/status', [OrderController::class, 'updateStatus'])->name('order.updateStatus');
    Route::get('/orders/invoice/download/{order_id}', [OrderController::class, 'invoiceDownload'])->name('order.invoiceDownload');

    // Pending Due
    Route::get('/pending/due', [OrderController::class, 'pendingDue'])->name('order.pendingDue');
    Route::get('/order/due/{id}', [OrderController::class, 'orderDueAjax'])->name('order.orderDueAjax');
    Route::post('/update/due', [OrderController::class, 'updateDue'])->name('order.updateDue');

    // Stock Management
    Route::get('/stock', [OrderController::class, 'stockManage'])->name('order.stockManage');
});

// ====== DATABASE BACKUP ======
Route::middleware(['permission:database.menu'])->group(function () {
    Route::get('/database/backup', [DatabaseBackupController::class, 'index'])->name('backup.index');
    Route::get('/database/backup/now', [DatabaseBackupController::class, 'create'])->name('backup.create');
    Route::get('/database/backup/download/{getFileName}', [DatabaseBackupController::class, 'download'])->name('backup.download');
    Route::get('/database/backup/delete/{getFileName}', [DatabaseBackupController::class, 'delete'])->name('backup.delete');
});

// ====== ROLE CONTROLLER ======
Route::middleware(['permission:roles.menu'])->group(function () {
    // Permissions
    Route::get('/permission', [RoleController::class, 'permissionIndex'])->name('permission.index');
    Route::get('/permission/create', [RoleController::class, 'permissionCreate'])->name('permission.create');
    Route::post('/permission', [RoleController::class, 'permissionStore'])->name('permission.store');
    Route::get('/permission/edit/{id}', [RoleController::class, 'permissionEdit'])->name('permission.edit');
    Route::put('/permission/{id}', [RoleController::class, 'permissionUpdate'])->name('permission.update');
    Route::delete('/permission/{id}', [RoleController::class, 'permissionDestroy'])->name('permission.destroy');

    // Roles
    Route::get('/role', [RoleController::class, 'roleIndex'])->name('role.index');
    Route::get('/role/create', [RoleController::class, 'roleCreate'])->name('role.create');
    Route::post('/role', [RoleController::class, 'roleStore'])->name('role.store');
    Route::get('/role/edit/{id}', [RoleController::class, 'roleEdit'])->name('role.edit');
    Route::put('/role/{id}', [RoleController::class, 'roleUpdate'])->name('role.update');
    Route::delete('/role/{id}', [RoleController::class, 'roleDestroy'])->name('role.destroy');

    // Role Permissions
    Route::get('/role/permission', [RoleController::class, 'rolePermissionIndex'])->name('rolePermission.index');
    Route::get('/role/permission/create', [RoleController::class, 'rolePermissionCreate'])->name('rolePermission.create');
    Route::post('/role/permission', [RoleController::class, 'rolePermissionStore'])->name('rolePermission.store');
    Route::get('/role/permission/{id}', [RoleController::class, 'rolePermissionEdit'])->name('rolePermission.edit');
    Route::put('/role/permission/{id}', [RoleController::class, 'rolePermissionUpdate'])->name('rolePermission.update');
    Route::delete('/role/permission/{id}', [RoleController::class, 'rolePermissionDestroy'])->name('rolePermission.destroy');
});

require __DIR__.'/auth.php';
*/

// ====================
// Admin routes
// ====================
