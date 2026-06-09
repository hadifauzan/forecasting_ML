<?php

use App\Http\Controllers\Admin\AboutUsContentController;
use App\Http\Controllers\Admin\AffiliateContentController;
use App\Http\Controllers\Admin\InventoryDashboardController;
use App\Http\Controllers\Admin\InventoryMasterItemController;
use App\Http\Controllers\Admin\InventoryRawMaterialController;
use App\Http\Controllers\Admin\InventoryCategoryController;
use App\Http\Controllers\Admin\BufferStockRopController;
use App\Http\Controllers\Admin\ResellerContentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AffiliateSubmissionController;
use App\Http\Controllers\Admin\AffiliateSubmissionAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerSettingsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\HomepageContentController;
use App\Http\Controllers\Admin\ResellerPricingController;
use App\Http\Controllers\Admin\SalesProductController;

// Main Pages Routes
Route::get('/', [LandingController::class, 'home'])->name('home');


Route::get('/affiliate', [LandingController::class, 'partner'])->name('affiliate');
Route::get('/product/{id}', [LandingController::class, 'productDetail'])->name('product.detail');
Route::get('/about-us', [LandingController::class, 'aboutUs'])->name('about-us');
Route::get('/reseller', [LandingController::class, 'reseller'])->name('reseller');
Route::get('/products', [LandingController::class, 'products'])->name('products');
Route::get('/articles', [LandingController::class, 'articles'])->name('articles');
Route::get('/articles/popular', [LandingController::class, 'articlePopular'])->name('articles.popular');
Route::get('/articles/latest', [LandingController::class, 'articleLatest'])->name('articles.latest');
Route::get('/articles/{id}', [LandingController::class, 'articleDetail'])->name('articles.detail');
Route::get('/articles/category/{category}', [LandingController::class, 'articleCategory'])->name('articles.category');
// Shopping routes (public access)
Route::get('/shopping', [LandingController::class, 'shopping'])->name('shopping');
Route::get('/shopping/products', [LandingController::class, 'shoppingProducts'])->name('shopping.products');
Route::get('/shopping/cart', [LandingController::class, 'cart'])->name('shopping.cart');
Route::get('/shopping/cart/count', [LandingController::class, 'getCartCount'])->name('shopping.cart.count');
Route::get('/shopping/product-by-variant', [LandingController::class, 'findProductByVariant'])->name('shopping.product.by.variant');

// Shopping cart routes (accessible by both admin and customer)
Route::middleware(['auth:web,customer'])->group(function () {
    Route::post('/shopping/cart/add', [LandingController::class, 'addToCart'])->name('shopping.cart.add');
    Route::post('/shopping/cart/update', [LandingController::class, 'updateCart'])->name('shopping.cart.update');
    Route::delete('/shopping/cart/remove/{id}', [LandingController::class, 'removeFromCart'])->name('shopping.cart.remove');
});

// Shopping routes that require authentication and reseller status check
Route::middleware('check.reseller.status')->group(function () {
    // Moved cart routes above to allow admin access
});

// Public API routes (no authentication required)
Route::get('/api/cities-by-province', [LandingController::class, 'getCitiesByProvince'])->name('api.cities-by-province');
Route::post('/api/calculate-shipping', [LandingController::class, 'calculateShippingCost'])->name('api.calculate-shipping');

// Test route untuk QRIS (bypass auth)
Route::post('/test/checkout/process', [LandingController::class, 'testCheckoutProcess'])->name('test.checkout.process');

// History route (accessible by both admin and customer)
Route::get('/shopping/history', [LandingController::class, 'history'])->name('shopping.history');

// Order management routes (accessible by both admin and customer)
Route::middleware(['auth:web,customer'])->group(function () {
    Route::post('/cancel-order/{id}', [LandingController::class, 'cancelOrder'])->name('order.cancel');
    Route::post('/cancel-active-order/{id}', [LandingController::class, 'cancelActiveOrder'])->name('order.cancel.active');
    Route::post('/confirm-order/{id}', [LandingController::class, 'confirmOrder'])->name('order.confirm');
    Route::post('/buy-again/{id}', [LandingController::class, 'buyAgain'])->name('order.buyAgain');
    Route::post('/give-review/{id}', [LandingController::class, 'giveReview'])->name('order.review');
});

// Checkout routes (require authentication and reseller status check)
Route::middleware('check.reseller.status')->group(function () {
    Route::get('/shopping/checkout', [LandingController::class, 'checkout'])->name('shopping.checkout');
    Route::post('/shopping/checkout/process', [LandingController::class, 'processCheckout'])->name('shopping.checkout.process');
});

// Payment routes
Route::prefix('payment')->group(function () {
    Route::post('/qris', [PaymentController::class, 'createQrisPayment'])->name('payment.qris');
    Route::post('/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');
    Route::get('/status/{transactionId}', [PaymentController::class, 'checkStatus'])->name('payment.status');
    // Put more specific route first to avoid conflicts
    Route::get('/qris/order/{orderId}', [PaymentController::class, 'showPayment'])->name('payment.qris.order');
    Route::get('/qris/{paymentId}', [PaymentController::class, 'showQrisPayment'])->name('payment.qris.show');
});

// Route to update items to active status
Route::get('/update-items-active', function () {
    $updated = \App\Models\MasterItem::whereNull('status_item')->orWhere('status_item', '!=', 'active')->update(['status_item' => 'active']);

    return response()->json([
        'message' => 'Items updated to active status',
        'updated_count' => $updated
    ]);
});

Route::get('/lacak-pengiriman', function () {
    return view('shipping-tracking');
})->name('shipping.tracking');

// Shipping API Routes
Route::prefix('api/shipping')->group(function () {
    Route::get('/provinces', [ShippingController::class, 'getProvinces'])->name('shipping.provinces');
    Route::get('/cities', [ShippingController::class, 'getCities'])->name('shipping.cities');
    Route::get('/couriers', [ShippingController::class, 'getAvailableCouriers'])->name('shipping.couriers');
    Route::post('/cost', [ShippingController::class, 'calculateShippingCost'])->name('shipping.cost');
    Route::post('/cost/all', [ShippingController::class, 'calculateAllShippingCosts'])->name('shipping.cost.all');
    Route::post('/track', [ShippingController::class, 'trackDelivery'])->name('shipping.track');
    Route::post('/update-tracking', [ShippingController::class, 'updateTrackingNumber'])->name('shipping.update-tracking');
    Route::post('/transaction-tracking', [ShippingController::class, 'getTransactionTracking'])->name('shipping.transaction-tracking');
});

// Affiliate Routes
Route::get('/affiliate/daftar', [AffiliateController::class, 'showForm'])->name('affiliate.form');
Route::post('/affiliate/daftar', [AffiliateController::class, 'store'])->name('affiliate.store');
Route::get('/affiliate/verify-otp', [AffiliateController::class, 'showVerifyOtp'])->name('affiliate.verify-otp');
Route::post('/affiliate/verify-otp', [AffiliateController::class, 'verifyOtp'])->name('affiliate.verify-otp.post');
Route::post('/affiliate/resend-otp', [AffiliateController::class, 'resendOtp'])->name('affiliate.resend-otp');

// Email Verification Request Routes (for users who lost OTP page)
Route::get('/affiliate/request-verification', [AffiliateController::class, 'showRequestVerification'])->name('affiliate.request-verification');
Route::post('/affiliate/request-verification', [AffiliateController::class, 'requestVerification'])->name('affiliate.request-verification.post');

Route::get('/affiliate/terima-kasih', [AffiliateController::class, 'thankYou'])->name('affiliate.thankyou');

// Affiliate Submission Routes (Protected - only for logged in affiliates)
Route::middleware(['affiliate'])->prefix('affiliate')->group(function () {
    Route::get('/submission/create', [AffiliateSubmissionController::class, 'create'])->name('affiliate.submission.create');
    Route::post('/submission/store', [AffiliateSubmissionController::class, 'store'])->name('affiliate.submission.store');
    Route::get('/submission/success', [AffiliateSubmissionController::class, 'success'])->name('affiliate.submission.success');
    Route::get('/submission/check', [AffiliateSubmissionController::class, 'checkActiveSubmission'])->name('affiliate.submission.check');
    Route::get('/submissions', [AffiliateSubmissionController::class, 'index'])->name('affiliate.submissions.list');
    Route::get('/submissions/{id}', [AffiliateSubmissionController::class, 'show'])->name('affiliate.submissions.detail');
    Route::post('/submissions/{id}/confirm-received', [AffiliateSubmissionController::class, 'confirmReceived'])->name('affiliate.submissions.confirmReceived');
    Route::post('/submissions/{id}/submit-video', [AffiliateSubmissionController::class, 'submitVideo'])->name('affiliate.submissions.submitVideo');
    Route::get('/guide', [AffiliateSubmissionController::class, 'guide'])->name('affiliate.guide');
});

// Reseller Routes
Route::get('/reseller/daftar', [App\Http\Controllers\ResellerController::class, 'showForm'])->name('reseller.form');
Route::post('/reseller/daftar', [App\Http\Controllers\ResellerController::class, 'store'])->name('reseller.store');
Route::get('/reseller/terima-kasih', [App\Http\Controllers\ResellerController::class, 'thankYou'])->name('reseller.thankyou');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:web,customer');

// Change Password Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('change-password');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password.post');
});

// Admin Routes (Protected with Role-Based Access Control)
Route::middleware(['auth'])->prefix('admin')->group(function () {
    // Dashboard - Accessible by all admin types
    Route::middleware(['role:admin'])->get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // Profile and account settings - Accessible by all admin types
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/profile', [AuthController::class, 'showProfile'])->name('admin.profile');
        Route::get('/settings/account', [AuthController::class, 'showAccountSettings'])->name('admin.settings.account');
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    });

    // Change Password Routes - Accessible by all admin types
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('admin.change-password');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('admin.change-password.post');
    });
    
    // User Management - Superadmin only
    Route::middleware(['role:superadmin'])->group(function () {
        Route::get('/users', [AdminController::class, 'dataUser'])->name('admin.users.index');
        Route::get('/data-user', [AdminController::class, 'dataUser'])->name('admin.data-user');
        Route::get('/admins', [AdminController::class, 'admins'])->name('admin.admins.index');
    });
    
    // Customer Management - Superadmin and Partner Admin
    Route::middleware(['role:superadmin,admin_partner'])->group(function () {
        Route::get('/data-customer', [AdminController::class, 'dataCustomer'])->name('admin.data-customer');
        Route::get('/data-customer/{id}/view', [AdminController::class, 'viewCustomer'])->name('admin.customer.view');
        Route::get('/data-customer/{id}/details', [AdminController::class, 'getCustomerDetails'])->name('admin.customer.details');
        Route::get('/data-customer/{id}/edit', [AdminController::class, 'editCustomer'])->name('admin.customer.edit');
        Route::put('/data-customer/{id}', [AdminController::class, 'updateCustomer'])->name('admin.customer.update');
        Route::post('/data-customer/{id}/update-status', [AdminController::class, 'updateCustomerStatus'])->name('admin.customer.update-status');
        Route::delete('/data-customer/{id}', [AdminController::class, 'deleteCustomer'])->name('admin.customer.delete');
        Route::get('/data-customer/export', [AdminController::class, 'exportCustomers'])->name('admin.customer.export');
        
        // Affiliate Submission Management
        Route::prefix('affiliate-submissions')->name('admin.affiliate-submissions.')->group(function () {
            Route::get('/', [AffiliateSubmissionAdminController::class, 'index'])->name('index');
            Route::get('/{id}', [AffiliateSubmissionAdminController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [AffiliateSubmissionAdminController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [AffiliateSubmissionAdminController::class, 'reject'])->name('reject');
            Route::post('/{id}/update-shipping', [AffiliateSubmissionAdminController::class, 'updateShipping'])->name('updateShipping');
            Route::post('/{id}/mark-received', [AffiliateSubmissionAdminController::class, 'markAsReceived'])->name('markReceived');
            Route::post('/{id}/mark-failed', [AffiliateSubmissionAdminController::class, 'markAsFailed'])->name('markFailed');
        });
        
        // Affiliate Guide Management
        Route::prefix('affiliate-guide')->name('admin.affiliate-guide.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AffiliateGuideController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AffiliateGuideController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AffiliateGuideController::class, 'store'])->name('store');
            Route::get('/{affiliateGuide}/edit', [\App\Http\Controllers\Admin\AffiliateGuideController::class, 'edit'])->name('edit');
            Route::put('/{affiliateGuide}', [\App\Http\Controllers\Admin\AffiliateGuideController::class, 'update'])->name('update');
            Route::delete('/{affiliateGuide}', [\App\Http\Controllers\Admin\AffiliateGuideController::class, 'destroy'])->name('destroy');
            Route::post('/{affiliateGuide}/toggle-status', [\App\Http\Controllers\Admin\AffiliateGuideController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/update-order', [\App\Http\Controllers\Admin\AffiliateGuideController::class, 'updateOrder'])->name('update-order');
        });
    });
    
    // Affiliate Management - Superadmin and Partner Admin
    Route::middleware(['role:superadmin,admin_partner'])->group(function () {
        Route::get('/view-data', [AdminController::class, 'viewData'])->name('admin.view-data');
        Route::get('/data-affiliator', [AdminController::class, 'viewData'])->name('admin.data-affiliator');
        Route::get('/data-reseller', [AdminController::class, 'dataReseller'])->name('admin.data-reseller');
        
        Route::get('/affiliate/{id}/view', [AdminController::class, 'viewAffiliate'])->name('admin.affiliate.view');
        Route::get('/affiliate/{id}/edit', [AdminController::class, 'editAffiliate'])->name('admin.affiliate.edit');
        Route::get('/affiliate/{id}/details', [AdminController::class, 'getDetails'])->name('admin.affiliate.details');
        Route::put('/affiliate/{id}/update', [AdminController::class, 'updateAffiliate'])->name('admin.affiliate.update');
        Route::post('/affiliate/{id}/update', [AdminController::class, 'updateAffiliate'])->name('admin.affiliate.update.post');
        Route::delete('/affiliate/{id}/delete', [AdminController::class, 'deleteAffiliate'])->name('admin.affiliate.delete');
        Route::post('/affiliate/{id}/update-status', [AdminController::class, 'updateAffiliateStatus'])->name('admin.affiliate.update-status');
        Route::get('/affiliate/export', [AdminController::class, 'export'])->name('admin.affiliate.export');
        Route::get('/export-excel', [AdminController::class, 'exportExcel'])->name('admin.export-excel');
        
        // Reseller Management Routes
        Route::get('/reseller/{id}/view', [AdminController::class, 'viewReseller'])->name('admin.reseller.view');
        Route::get('/reseller/{id}/edit', [AdminController::class, 'editReseller'])->name('admin.reseller.edit');
        Route::put('/reseller/{id}', [AdminController::class, 'updateReseller'])->name('admin.reseller.update');
        Route::delete('/reseller/{id}', [AdminController::class, 'deleteReseller'])->name('admin.reseller.delete');
        Route::get('/export-reseller-excel', [AdminController::class, 'exportResellerExcel'])->name('admin.reseller.export');
        Route::post('/reseller/terms-conditions', [AdminController::class, 'updateResellerTerms'])->name('admin.reseller.terms.update');
        
        // Affiliate route aliases for sidebar compatibility
        Route::get('/affiliates/requests', [AdminController::class, 'viewData'])->name('admin.affiliates.requests');
        Route::get('/affiliates/active', [AdminController::class, 'viewData'])->name('admin.affiliates.active');
        Route::get('/affiliates/info', [AdminController::class, 'viewData'])->name('admin.affiliates.info');
        Route::get('/affiliates/commission', function () { return view('admin.dashboard'); })->name('admin.affiliates.commission');
        
        // Partner reports
        Route::get('/reports/affiliate-performance', function () { return view('admin.dashboard'); })->name('admin.reports.affiliate-performance');
        Route::get('/reports/commissions', function () { return view('admin.dashboard'); })->name('admin.reports.commissions');
    });


    // Product Management - Superadmin, Content Admin, and Seller Admin (view only for seller)
    Route::middleware(['role:superadmin,admin_content'])->group(function () {
        // Sales Products Management (Kelola Penjualan)
        Route::prefix('sales')->name('admin.sales.')->group(function () {
            Route::resource('products', SalesProductController::class)->except(['show']);
            Route::post('/products/{product}/toggle-status', [SalesProductController::class, 'toggleStatus'])->name('products.toggle-status');
            Route::post('/products/{product}/duplicate', [SalesProductController::class, 'duplicate'])->name('products.duplicate');
            Route::post('/products-bulk', [SalesProductController::class, 'bulk'])->name('products.bulk');
        });

        // Legacy product routes for backward compatibility
        Route::get('/products/create', [AdminController::class, 'createProduct'])->name('admin.products.create');
        Route::post('/products', [AdminController::class, 'storeProduct'])->name('admin.products.store');
        Route::get('/products/{id}/edit', [AdminController::class, 'editProduct'])->name('admin.products.edit');
        Route::put('/products/{id}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
        Route::delete('/products/{id}', [AdminController::class, 'deleteProduct'])->name('admin.products.destroy');
    });
    
    // Product viewing - All admin types can view products
    Route::middleware(['role:superadmin,admin_content,admin_seller'])->group(function () {
        Route::get('/products', [AdminController::class, 'manageProducts'])->name('admin.products.index');
    });
    
    // Content Management - Superadmin and Content Admin only
    Route::middleware(['role:superadmin,admin_content'])->group(function () {
        Route::get('/content', [AdminController::class, 'contentManagement'])->name('admin.content-products.index');

        // Carousel Produk Management
        Route::get('/content/carousel-produk', [AdminController::class, 'carouselProduk'])->name('admin.content-products.carousel-produk');
        Route::get('/content/carousel-produk/create', [AdminController::class, 'createCarouselProduk'])->name('admin.content-products.carousel-produk.create');
        Route::post('/content/carousel-produk', [AdminController::class, 'storeCarouselProduk'])->name('admin.content-products.carousel-produk.store');
        Route::get('/content/carousel-produk/{id}/edit', [AdminController::class, 'editCarouselProduk'])->name('admin.content-products.carousel-produk.edit');
        Route::put('/content/carousel-produk/{id}', [AdminController::class, 'updateCarouselProduk'])->name('admin.content-products.carousel-produk.update');
        Route::patch('/content/carousel-produk/{id}/status', [AdminController::class, 'updateCarouselProdukStatus'])->name('admin.content-products.carousel-produk.status');
        Route::delete('/content/carousel-produk/{id}', [AdminController::class, 'deleteCarouselProduk'])->name('admin.content-products.carousel-produk.destroy');

        // Benefits Management
        Route::get('/content/benefits', [AdminController::class, 'benefits'])->name('admin.content-products.benefits');
        Route::post('/content/product-description', [AdminController::class, 'storeProductDescription'])->name('admin.content-products.product-description.store');
        Route::get('/content/benefits/create', [AdminController::class, 'createBenefit'])->name('admin.content-products.benefits.create');
        Route::post('/content/benefits', [AdminController::class, 'storeBenefit'])->name('admin.content-products.benefits.store');
        Route::get('/content/benefits/{id}/edit', [AdminController::class, 'editBenefit'])->name('admin.content-products.benefits.edit');
        Route::put('/content/benefits/{id}', [AdminController::class, 'updateBenefit'])->name('admin.content-products.benefits.update');
        Route::delete('/content/benefits/{id}', [AdminController::class, 'deleteBenefit'])->name('admin.content-products.benefits.delete');

        // Reviews Management
        Route::get('/content/reviews', [AdminController::class, 'reviews'])->name('admin.content-products.reviews');
        Route::post('/content/reviews/{id}/toggle-featured', [AdminController::class, 'toggleReviewFeatured'])->name('admin.content-products.reviews.toggle-featured');
        Route::delete('/content/reviews/{id}', [AdminController::class, 'deleteReview'])->name('admin.content-products.reviews.delete');

        // Carousel Varian Management (existing products)
        Route::get('/content/carousel-varian', [AdminController::class, 'carouselVarian'])->name('admin.content-products.carousel-varian');
        Route::get('/content/carousel-varian/create', [AdminController::class, 'createCarouselVarian'])->name('admin.content-products.carousel-varian.create');
        Route::post('/content/carousel-varian', [AdminController::class, 'storeCarouselVarian'])->name('admin.content-products.carousel-varian.store');
        Route::get('/content/carousel-varian/{id}/edit', [AdminController::class, 'editCarouselVarian'])->name('admin.content-products.carousel-varian.edit');
        Route::put('/content/carousel-varian/{id}', [AdminController::class, 'updateCarouselVarian'])->name('admin.content-products.carousel-varian.update');
        Route::delete('/content/carousel-varian/{id}', [AdminController::class, 'deleteCarouselVarian'])->name('admin.content-products.carousel-varian.destroy');
        Route::post('/content/carousel-varian/{id}/toggle-status', [AdminController::class, 'updateCarouselVarianStatus'])->name('admin.content-products.carousel-varian.toggle-status');

        // API endpoints for AJAX requests
        Route::get('/api/products-by-category', [AdminController::class, 'getProductsByCategory'])->name('admin.api.products-by-category');
    });



    // Order Management - Superadmin and Seller Admin
    Route::middleware(['role:superadmin,admin_seller'])->group(function () {
        Route::prefix('orders')->name('admin.orders.')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminOrderController::class, 'show'])->name('show');
            Route::post('/{id}/update-status', [AdminOrderController::class, 'updateStatus'])->name('update-status');
            Route::post('/bulk-update-status', [AdminOrderController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
            Route::get('/statistics', [AdminOrderController::class, 'statistics'])->name('statistics');
        });

        // Shipping Status Management
        Route::prefix('shipping-status')->name('admin.shipping-status.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ShippingStatusController::class, 'index'])->name('index');
            Route::get('/{id}', [App\Http\Controllers\Admin\ShippingStatusController::class, 'show'])->name('show');
            Route::post('/{id}/update', [App\Http\Controllers\Admin\ShippingStatusController::class, 'updateStatus'])->name('update');
        });
        
        // Additional order routes for sidebar
        Route::get('/orders/pending', [AdminOrderController::class, 'index'])->name('admin.orders.pending');
        Route::get('/orders/processing', [AdminOrderController::class, 'index'])->name('admin.orders.processing');
        Route::get('/orders/shipped', [AdminOrderController::class, 'index'])->name('admin.orders.shipped');
        
        // Sales reports for seller admin
        Route::get('/reports/daily-sales', function () { return view('admin.dashboard'); })->name('admin.reports.daily-sales');
        Route::get('/reports/monthly-sales', function () { return view('admin.dashboard'); })->name('admin.reports.monthly-sales');
        Route::get('/reports/product-performance', function () { return view('admin.dashboard'); })->name('admin.reports.product-performance');
    });
    
    // Superadmin-only routes
    Route::middleware(['role:superadmin'])->group(function () {
        // Reports routes
        Route::get('/reports/sales', function () { return view('admin.dashboard'); })->name('admin.reports.sales');
        Route::get('/reports/affiliates', function () { return view('admin.dashboard'); })->name('admin.reports.affiliates');
        Route::get('/reports/users', function () { return view('admin.dashboard'); })->name('admin.reports.users');
        
        // Settings routes
        Route::get('/settings/system', function () { return view('admin.dashboard'); })->name('admin.settings.system');
        Route::get('/settings/payment', function () { return view('admin.dashboard'); })->name('admin.settings.payment');
        Route::get('/settings/shipping', function () { return view('admin.dashboard'); })->name('admin.settings.shipping');
    });
    
    // Content routes for sidebar
    Route::middleware(['role:superadmin,admin_content'])->group(function () {
        Route::get('/content/banners', function () { return view('admin.dashboard'); })->name('admin.content.banners');
        Route::get('/content/pages', function () { return view('admin.dashboard'); })->name('admin.content.pages');
        Route::get('/content/announcements', function () { return view('admin.dashboard'); })->name('admin.content.announcements');
    });
    
    // Partner Management - Superadmin and Partner Admin
    Route::middleware(['role:superadmin,admin_partner'])->group(function () {
        Route::get('/partner', [PartnerSettingsController::class, 'partnerSettings'])->name('admin.partner.index');
        Route::post('/partner/update', [PartnerSettingsController::class, 'update'])->name('admin.partner.update');
        
        // Reseller Pricing Management
        Route::prefix('reseller-pricing')->name('admin.reseller-pricing.')->group(function () {
            Route::get('/', [ResellerPricingController::class, 'index'])->name('index');
            Route::get('/statistics', [ResellerPricingController::class, 'getStatistics'])->name('statistics');
            Route::get('/points', [ResellerPricingController::class, 'points'])->name('points');
            Route::get('/purchases', [ResellerPricingController::class, 'purchases'])->name('purchases');
            Route::get('/customer/{customerId}/transactions', [ResellerPricingController::class, 'getCustomerTransactions'])->name('customer.transactions');
            Route::post('/update-discounts', [ResellerPricingController::class, 'updateDiscounts'])->name('update.discounts');
        });
    });

    // Content Management - Superadmin and Content Admin only (continued)
    Route::middleware(['role:superadmin,admin_content'])->group(function () {
        // Homepage Content Management
        Route::prefix('homepage-content')->name('admin.homepage-content.')->group(function () {
            Route::get('/banner', [HomepageContentController::class, 'banner'])->name('banner');
            Route::get('/banner/{section}/edit', [HomepageContentController::class, 'editBanner'])->name('banner.edit');
            Route::put('/banner/{section}/update', [HomepageContentController::class, 'updateBanner'])->name('banner.update');
            Route::post('/store', [HomepageContentController::class, 'store'])->name('store');
            Route::delete('/destroy', [HomepageContentController::class, 'destroy'])->name('destroy');
            Route::get('/information', [HomepageContentController::class, 'information'])->name('information');
            Route::get('/information/{section}/edit', [HomepageContentController::class, 'editInformation'])->name('information.edit');
            Route::put('/information/{section}/update', [HomepageContentController::class, 'updateInformation'])->name('information.update');
            Route::get('/faq', [HomepageContentController::class, 'faq'])->name('faq');
            Route::get('/faq/create', [HomepageContentController::class, 'createFAQ'])->name('faq.create');
            Route::post('/faq/store', [HomepageContentController::class, 'storeFAQ'])->name('faq.store');
            Route::get('/faq/{section}/edit', [HomepageContentController::class, 'editFAQ'])->name('faq.edit');
            Route::put('/faq/{section}/update', [HomepageContentController::class, 'updateFAQ'])->name('faq.update');
            Route::delete('/faq/{section}/delete', [HomepageContentController::class, 'deleteFAQ'])->name('faq.delete');
        });

        // Article Management
        Route::prefix('articles')->name('admin.articles.')->group(function () {
            Route::get('/', [ArticleController::class, 'index'])->name('index');
            Route::get('/create', [ArticleController::class, 'create'])->name('create');
            Route::post('/', [ArticleController::class, 'store'])->name('store');
            Route::get('/{id}', [ArticleController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ArticleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ArticleController::class, 'update'])->name('update');
            Route::delete('/{id}', [ArticleController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [ArticleController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/category/{categoryId}', [ArticleController::class, 'byCategory'])->name('by-category');
        });
    });
    
    // Affiliate Content Management - Superadmin, Content Admin and Partner Admin
    Route::middleware(['role:superadmin,admin_content,admin_partner'])->group(function () {
        Route::prefix('affiliate-content')->name('admin.affiliate-content.')->group(function () {
            Route::get('/', [AffiliateContentController::class, 'index'])->name('index');
            Route::get('/{section}/edit', [AffiliateContentController::class, 'edit'])->name('edit');
            Route::put('/{section}/update', [AffiliateContentController::class, 'update'])->name('update');
            Route::get('/banner', [AffiliateContentController::class, 'banner'])->name('banner');
            Route::get('/reasons', [AffiliateContentController::class, 'reasons'])->name('reasons');
            Route::post('/reasons', [AffiliateContentController::class, 'reasons'])->name('reasons.store');
            Route::get('/reasons/{section}/edit', [AffiliateContentController::class, 'editReasons'])->name('reasons.edit');
            Route::get('/benefits', [AffiliateContentController::class, 'benefits'])->name('benefits');
            Route::post('/benefits', [AffiliateContentController::class, 'benefits'])->name('benefits.store');
            Route::get('/benefits/{section}/edit', [AffiliateContentController::class, 'editBenefits'])->name('benefits.edit');
            Route::get('/perfect-for', [AffiliateContentController::class, 'perfectFor'])->name('perfect-for');
            Route::post('/perfect-for', [AffiliateContentController::class, 'perfectFor'])->name('perfect-for.store');
            Route::get('/perfect-for/{section}/edit', [AffiliateContentController::class, 'editPerfectFor'])->name('perfect-for.edit');
            Route::get('/videos', [AffiliateContentController::class, 'videos'])->name('videos');
            Route::get('/videos/create', [AffiliateContentController::class, 'createVideo'])->name('videos.create');
            Route::post('/videos/store', [AffiliateContentController::class, 'storeVideo'])->name('videos.store');
            Route::get('/videos/{section}/edit', [AffiliateContentController::class, 'editVideo'])->name('videos.edit');
            Route::put('/videos/{section}/update', [AffiliateContentController::class, 'updateVideo'])->name('videos.update');
            Route::delete('/videos/{section}', [AffiliateContentController::class, 'destroyVideo'])->name('videos.destroy');
            Route::get('/steps', [AffiliateContentController::class, 'steps'])->name('steps');
            Route::get('/steps/create', [AffiliateContentController::class, 'createStep'])->name('steps.create');
            Route::get('/steps/{section}/edit', [AffiliateContentController::class, 'editSteps'])->name('steps.edit');

            // Dynamic create and delete routes
            Route::post('/store', [AffiliateContentController::class, 'store'])->name('store');
            Route::delete('/{section}', [AffiliateContentController::class, 'destroy'])->name('destroy');
        });
    });

    // About Us & Reseller Content Management - Superadmin and Content Admin only (continued)  
    Route::middleware(['role:superadmin,admin_content'])->group(function () {
        // About Us Content Management
        Route::prefix('about-us-content')->name('admin.about-us-content.')->group(function () {
            // Banner Management Routes
            Route::get('/banner', [AboutUsContentController::class, 'banner'])->name('banner');
            Route::get('/banner/{section}/edit', [AboutUsContentController::class, 'edit'])->name('edit-banner');
            Route::put('/banner/{section}/update', [AboutUsContentController::class, 'update'])->name('update-banner');

            // Tentang Kami Content Routes
            Route::get('/tentang-kami', [AboutUsContentController::class, 'showAbout'])->name('tentang-kami');
            Route::get('/tentang-kami/edit', [AboutUsContentController::class, 'editAbout'])->name('edit-tentang-kami');
            Route::post('/tentang-kami/update', [AboutUsContentController::class, 'updateAbout'])->name('update-tentang-kami');

            // Journey Content Routes
            Route::get('/journey', [AboutUsContentController::class, 'journey'])->name('journey');
            Route::get('/journey/edit', [AboutUsContentController::class, 'editJourney'])->name('edit-journey');
            Route::post('/journey/update', [AboutUsContentController::class, 'updateJourney'])->name('update-journey');

            // Vision & Mission Content Routes
            Route::get('/vision-mission', [AboutUsContentController::class, 'visionMission'])->name('vision-mission');
            Route::get('/vision/edit', [AboutUsContentController::class, 'editVision'])->name('edit-vision');
            Route::post('/vision/update', [AboutUsContentController::class, 'updateVision'])->name('update-vision');
            Route::get('/mission/edit', [AboutUsContentController::class, 'editMission'])->name('edit-mission');
            Route::post('/mission/update', [AboutUsContentController::class, 'updateMission'])->name('update-mission');

            // Family Content Routes
            Route::get('/family', [AboutUsContentController::class, 'family'])->name('family');
            // Family Header Routes
            Route::get('/family/header/edit', [AboutUsContentController::class, 'editFamilyHeader'])->name('edit-family-header');
            Route::put('/family/header/update', [AboutUsContentController::class, 'updateFamilyHeader'])->name('update-family-header');

            // Family Photos Routes
            Route::get('/family/photos/edit/{photoId?}', [AboutUsContentController::class, 'editFamilyPhotos'])->name('edit-family-photos');
            Route::put('/family/photo/{photoId}/update', [AboutUsContentController::class, 'updateSingleFamilyPhoto'])->name('update-single-family-photo');
            Route::post('/family/photos/add', [AboutUsContentController::class, 'addFamilyPhoto'])->name('add-family-photo');
            Route::delete('/family/image/{imageId}', [AboutUsContentController::class, 'deleteFamilyImage'])->name('delete-family-image');
        });

        // Reseller Content Management
        Route::prefix('reseller-content')->name('admin.reseller-content.')->group(function () {
            // Banner Management Routes
            Route::get('/banner', [ResellerContentController::class, 'banner'])->name('banner');
            Route::get('/banner/edit', [ResellerContentController::class, 'editBanner'])->name('edit-banner');
            Route::put('/banner/update', [ResellerContentController::class, 'updateBanner'])->name('update-banner');

            // Generic Section Management Routes
            Route::get('/section/{sectionKey}', [ResellerContentController::class, 'showSection'])->name('section');
            Route::get('/section/{sectionKey}/edit/{section}', [ResellerContentController::class, 'editSectionItem'])->name('section.edit');
            Route::put('/section/{sectionKey}/update/{section}', [ResellerContentController::class, 'updateSectionItem'])->name('section.update');
            Route::post('/section/{sectionKey}/store', [ResellerContentController::class, 'storeSectionItem'])->name('section.store');
            Route::delete('/section/{sectionKey}/delete/{section}', [ResellerContentController::class, 'deleteSectionItem'])->name('section.delete');

            // Steps specific routes
            Route::get('/steps', [App\Http\Controllers\Admin\ResellerContentController::class, 'showSection'])->defaults('sectionKey', 'steps')->name('steps');
            Route::get('/steps/create', function () {
                // Find next available step number
                $existingSteps = App\Models\MasterContent::where('type_of_page', 'partner')
                    ->where('section', 'like', 'reseller-step-%')
                    ->pluck('section')
                    ->map(function ($section) {
                        return (int) str_replace('reseller-step-', '', $section);
                    })
                    ->sort()
                    ->values();

                $nextNumber = 1;
                for ($i = 1; $i <= 10; $i++) {
                    if (!$existingSteps->contains($i)) {
                        $nextNumber = $i;
                        break;
                    }
                }

                return view('admin.reseller-content.form-steps', [
                    'section' => 'reseller-step-' . $nextNumber,
                    'isCreate' => true,
                    'content' => null
                ]);
            })->name('steps.create');
        });
    });

    // Kategori A: Hak Akses Analisis, Perencanaan, & Master Data - Hanya Owner & Admin Inventory
    Route::middleware(['role:owner,admin_inventory'])->group(function () {
        // Master Data
        Route::resource('/inventory/master-items', InventoryMasterItemController::class, ['as' => 'admin.inventory']);
        Route::resource('/inventory/raw-materials', InventoryRawMaterialController::class, ['as' => 'admin.inventory'])->except(['index']);
        Route::resource('/inventory/master-categories', InventoryCategoryController::class, ['as' => 'admin.inventory'])->except(['show', 'create', 'edit']);

        Route::get('/inventory-dashboard', [InventoryDashboardController::class, 'index'])->name('admin.inventory.dashboard');
        Route::get('/inventory/finished-goods', [InventoryDashboardController::class, 'rawMaterials'])->name('admin.inventory.finished-goods');
        Route::get('/inventory/finished-goods/create/form-data', [InventoryDashboardController::class, 'getFinishedGoodsFormData'])->name('admin.inventory.finished-goods.form-data');
        Route::post('/inventory/finished-goods', [InventoryDashboardController::class, 'storeFinishedGoods'])->name('admin.inventory.finished-goods.store');
        Route::post('/inventory/finished-goods/update-rop', [InventoryDashboardController::class, 'updateBufferStockFromRop'])->name('admin.inventory.finished-goods.update-rop');
        Route::get('/inventory/finished-goods/{itemStock}/show', [InventoryDashboardController::class, 'finishedGoodsShow'])->name('admin.inventory.finished-goods.show');
        Route::get('/inventory/finished-goods/{itemStock}/edit', [InventoryDashboardController::class, 'finishedGoodsEdit'])->name('admin.inventory.finished-goods.edit');
        Route::get('/inventory/finished-goods/{itemStock}', [InventoryDashboardController::class, 'getRawMaterialDetail']);
        Route::put('/inventory/finished-goods/{itemStock}', [InventoryDashboardController::class, 'updateRawMaterial'])->name('admin.inventory.finished-goods.update');
        Route::delete('/inventory/finished-goods/{itemStock}', [InventoryDashboardController::class, 'destroyFinishedGoods'])->name('admin.inventory.finished-goods.destroy');

        // Buffer Stock Routes
        Route::get('/inventory/buffer-stock/raw-materials', [InventoryDashboardController::class, 'bufferStockRawMaterials'])->name('admin.inventory.buffer-stock.raw-materials');
        Route::post('/inventory/buffer-stock/raw-materials', [InventoryDashboardController::class, 'storeBufferStockRawMaterial'])->name('admin.inventory.buffer-stock.store');
        Route::get('/inventory/buffer-stock/details/{itemRawId}', [InventoryDashboardController::class, 'bufferStockDetail'])->name('admin.inventory.buffer-stock.detail');
        Route::get('/inventory/buffer-stock/production-master-data', [InventoryDashboardController::class, 'bufferStockProductionMasterData'])->name('admin.inventory.buffer-stock.production-master-data');
        Route::get('/inventory/buffer-stock/production-options/{itemRawId}', [InventoryDashboardController::class, 'bufferStockProductionOptions'])->name('admin.inventory.buffer-stock.production-options');
        Route::post('/inventory/buffer-stock/produce', [InventoryDashboardController::class, 'produceFromRawMaterial'])->name('admin.inventory.buffer-stock.produce');
        Route::put('/inventory/buffer-stock/raw-materials/{itemRawId}', [InventoryDashboardController::class, 'updateBufferStockRawMaterial'])->name('admin.inventory.buffer-stock.update');
        Route::delete('/inventory/buffer-stock/raw-materials/{itemRawId}', [InventoryDashboardController::class, 'destroyBufferStockRawMaterial'])->name('admin.inventory.buffer-stock.destroy');
        Route::post('/inventory/buffer-stock/sync', [InventoryDashboardController::class, 'syncBufferStocks'])->name('admin.inventory.buffer-stock.sync');
        Route::post('/inventory/buffer-stock/sync-from-csv', [InventoryDashboardController::class, 'syncBufferStockFromCSV'])->name('admin.inventory.buffer-stock.sync-from-csv');
        Route::get('/inventory/buffer-stock/export-analysis', [InventoryDashboardController::class, 'exportInventoryAnalysis'])->name('admin.inventory.buffer-stock.export-analysis');
        Route::get('/inventory/buffer-stock/import-status', [InventoryDashboardController::class, 'bufferStockImportStatus'])->name('admin.inventory.buffer-stock.import-status');
        Route::get('/inventory/buffer-stock/items-to-order', [InventoryDashboardController::class, 'itemsToOrder'])->name('admin.inventory.buffer-stock.items-to-order');
        
        // Excel Export/Import Routes
        Route::get('/inventory/buffer-stock/export-excel', [InventoryDashboardController::class, 'exportBufferStockExcel'])->name('admin.inventory.buffer-stock.export-excel');
        Route::get('/inventory/buffer-stock/download-template', [InventoryDashboardController::class, 'downloadBufferStockTemplate'])->name('admin.inventory.buffer-stock.download-template');
        Route::post('/inventory/buffer-stock/import-excel', [InventoryDashboardController::class, 'importBufferStockExcel'])->name('admin.inventory.buffer-stock.import-excel');

        // ROP Calculation Routes
        Route::post('/inventory/buffer-stock/calculate-rop', [BufferStockRopController::class, 'calculateRop'])->name('admin.inventory.buffer-stock.calculate-rop');
        Route::get('/inventory/buffer-stock/rop-summary', [BufferStockRopController::class, 'getRopSummary'])->name('admin.inventory.buffer-stock.rop-summary');

        // Forecasting Routes
        Route::get('/inventory/forecasting/demand', [InventoryDashboardController::class, 'demandForecasting'])->name('admin.inventory.forecasting.demand');
        Route::post('/inventory/forecasting/run-dynamic', [InventoryDashboardController::class, 'runDynamicForecast'])->name('admin.inventory.forecasting.run-dynamic');
        Route::get('/inventory/forecasting/demand-detail/{produk}', [InventoryDashboardController::class, 'getDemandForecastDetail'])->name('admin.inventory.forecasting.demand-detail');

        // Model Evaluation Routes
        Route::get('/inventory/model-evaluation', [\App\Http\Controllers\Admin\ModelEvaluationController::class, 'index'])->name('admin.inventory.model-evaluation');
        Route::get('/inventory/model-evaluation/data/{produk}', [\App\Http\Controllers\Admin\ModelEvaluationController::class, 'getComparisonData'])->name('admin.inventory.model-evaluation.data');

        // BOM CRUD Routes
        Route::get('/inventory/finished-goods/{itemId}/bom', [InventoryDashboardController::class, 'getBomForItem'])->name('admin.inventory.bom.index');
        Route::post('/inventory/finished-goods/{itemId}/bom', [InventoryDashboardController::class, 'storeBomItem'])->name('admin.inventory.bom.store');
        Route::put('/inventory/bom/{bomId}', [InventoryDashboardController::class, 'updateBomItem'])->name('admin.inventory.bom.update');
        Route::delete('/inventory/bom/{bomId}', [InventoryDashboardController::class, 'destroyBomItem'])->name('admin.inventory.bom.destroy');

        // Procurement Calculator Route
        Route::get('/inventory/procurement-calculator', [InventoryDashboardController::class, 'procurementCalculator'])->name('admin.inventory.procurement-calculator');

        // Stock Opname
        Route::get('/inventory/stock-opname', [InventoryDashboardController::class, 'stockOpname'])->name('admin.inventory.stock-opname');
        Route::post('/inventory/stock-opname/save-physical-stock', [InventoryDashboardController::class, 'savePhysicalStock'])->name('admin.inventory.stock-opname.save-physical-stock');
        Route::post('/inventory/physical-input', [InventoryDashboardController::class, 'storePhysicalInput'])->name('admin.inventory.physical-input.store');
        Route::get('/inventory/api/raw-materials', [InventoryDashboardController::class, 'getRawMaterialsForSelection'])->name('admin.inventory.api.raw-materials');
        Route::get('/inventory/api/finished-goods', [InventoryDashboardController::class, 'getFinishedGoodsForSelection'])->name('admin.inventory.api.finished-goods');
        Route::get('/inventory/api/received-goods', [InventoryDashboardController::class, 'getReceivedGoodsByDateRange'])->name('admin.inventory.api.received-goods');

        // History Transaksi Routes
        Route::get('/inventory/transaction-history', [InventoryDashboardController::class, 'transactionHistory'])->name('admin.inventory.transaction-history');
        Route::get('/inventory/transaction-history/{id}', [InventoryDashboardController::class, 'transactionHistoryDetail'])->name('admin.inventory.transaction-history.detail');
    });

    // Kategori B: Hak Akses Pencatatan Keluar Masuk & Operasional Stok - Hanya Owner & Tim Produksi
   
    Route::middleware(['role:owner'])->group(function () {
        // Production Overview Route
        Route::get('/inventory/production-overview', [InventoryDashboardController::class, 'productionOverview'])->name('admin.inventory.production.overview');

        // Production Process Routes (BOM Production)
        Route::get('/inventory/production', [\App\Http\Controllers\Admin\ProductionController::class, 'index'])->name('admin.production.index');
        Route::get('/inventory/production/create', [\App\Http\Controllers\Admin\ProductionController::class, 'create'])->name('admin.production.create');
        Route::post('/inventory/production/calculate-bom', [\App\Http\Controllers\Admin\ProductionController::class, 'calculateBom'])->name('admin.production.calculateBom');
        Route::post('/inventory/production/store', [\App\Http\Controllers\Admin\ProductionController::class, 'store'])->name('admin.production.store');

        // Stock Adjustment Routes
        Route::get('/inventory/stock-adjustment', [\App\Http\Controllers\Admin\StockAdjustmentController::class, 'index'])->name('admin.stock-adjustment.index');
        Route::get('/inventory/stock-adjustment/create', [\App\Http\Controllers\Admin\StockAdjustmentController::class, 'create'])->name('admin.stock-adjustment.create');
        Route::get('/inventory/stock-adjustment/get-system-stock', [\App\Http\Controllers\Admin\StockAdjustmentController::class, 'getSystemStock'])->name('admin.stock-adjustment.get-system-stock');
        Route::post('/inventory/stock-adjustment/store', [\App\Http\Controllers\Admin\StockAdjustmentController::class, 'store'])->name('admin.stock-adjustment.store');
    });

       
});

// Partner Registration Routes
Route::post('/register-partner', [LandingController::class, 'registerPartner'])->name('register.partner');
Route::post('/register-partner-submit', [LandingController::class, 'submitPartnerRegistration'])->name('submit.partner.registration');

// Cek apakah environment production atau local
if (app()->environment('production')) {
    // Route untuk Production dengan domain berbeda
    Route::domain(env('NYAM_DOMAIN', 'nyam.com'))->group(function () {
        Route::get('/', [LandingPageController::class, 'nyam'])->name('nyam.landing');
    });

    Route::domain(env('GENTLELIVING_DOMAIN', 'gentleliving.com'))->group(function () {
        Route::get('/', [LandingPageController::class, 'gentleLiving'])->name('gentleLiving.landing');
    });

    
    Route::domain(env('MAMINA_DOMAIN', 'mamina.com'))->group(function () {
        Route::get('/', [LandingPageController::class, 'mamina'])->name('mamina.landing');
    });
} else {
    // Route untuk Local Development dengan path berbeda
    Route::get('/nyam', [LandingPageController::class, 'nyam'])->name('nyam.landing');
    Route::get('/gentleLiving', [LandingPageController::class, 'gentleLiving'])->name('gentleLiving.landing');
    Route::get('/mamina', [LandingPageController::class, 'mamina'])->name('mamina.landing');
}