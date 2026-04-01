<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    // ========================================
    // DASHBOARD - Accessible to all authenticated admin users
    // ========================================
    $router->get('/', 'HomeController@index')->name('home');

    // ========================================
    // IMPLEMENTING PARTNERS (IP) — Backbone Multi-Tenancy
    // Super Admins manage all IPs; IP admins see their own only
    // ========================================
    $router->resource('implementing-partners', ImplementingPartnerController::class);

    $router->resource('deliveries', DeliveryController::class);
    $router->resource('product-categories', ProductCategoryController::class);
    $router->resource('ordered-items', OrderedItemController::class);


    $router->resource('delivery-addresses', DeliveryAddressController::class);
    $router->resource('deliveries', DeliveryController::class);
    $router->resource('product-categories', ProductCategoryController::class);
    $router->resource('products', ProductController::class);
    $router->resource('product-orders', ProductOrderController::class);

    // ========================================
    // USER IMPORT MANAGEMENT
    // ========================================
    $router->resource('import-tasks', ImportTaskController::class);

    // ========================================
    // INVESTMENT MANAGEMENT - Admin Only (Financial Operations)
    // ========================================

    $router->resource('projects', ProjectController::class);

    // ── VSLA Savings Cycles — dedicated controller with activate/deactivate ──
    // NOTE: Custom action routes MUST come before the resource route
    $router->get('cycles/{id}/activate', 'CycleController@activate')->name('cycles.activate');
    $router->get('cycles/{id}/deactivate', 'CycleController@deactivate')->name('cycles.deactivate');
    $router->resource('cycles', CycleController::class);

    $router->resource('project-shares', ProjectShareController::class);
    $router->resource('project-transactions', ProjectTransactionController::class);
    $router->resource('disbursements', DisbursementController::class);
    $router->resource('account-transactions', AccountTransactionController::class);
    $router->resource('account-transactions-deposit', AccountTransactionController::class);
    $router->resource('account-transactions-withdraw', AccountTransactionController::class);
    $router->resource('financial-accounts', FinancialAccountsController::class);

    // Withdraw Requests Management
    // NOTE: Specific routes MUST come before resource route to avoid conflicts
    $router->get('withdraw-requests/pdf-pending', 'WithdrawRequestController@generatePendingPDF')->name('withdraw-requests.pdf-pending');
    $router->get('withdraw-requests/{id}/approve', 'WithdrawRequestController@approve')->name('withdraw-requests.approve');
    $router->get('withdraw-requests/{id}/reject', 'WithdrawRequestController@reject')->name('withdraw-requests.reject');
    $router->resource('withdraw-requests', WithdrawRequestController::class);

    // ========================================
    // INSURANCE MANAGEMENT - Read access for managers, full access for admin
    // ========================================
    $router->resource('insurance-programs', InsuranceProgramController::class);
    $router->resource('insurance-subscriptions', InsuranceSubscriptionController::class);

    // Payment operations
    $router->resource('insurance-subscription-payments', InsuranceSubscriptionPaymentController::class);
    $router->resource('insurance-transactions', InsuranceTransactionController::class);

    // ========================================
    // MEDICAL SERVICES - Read access for managers, full access for admin
    // ========================================
    $router->resource('medical-service-requests', MedicalServiceRequestController::class);

    // ========================================
    // E-COMMERCE - Read access for managers, full access for admin
    // ========================================
    $router->resource('products', ProductController::class);
    $router->resource('orders', OrderController::class);

    // ========================================
    // MEMBERSHIP MANAGEMENT
    // ========================================
    $router->resource('membership-payments', MembershipPaymentController::class);
    $router->get('membership-payments/{id}/confirm', 'MembershipPaymentController@confirm')->name('membership-payments.confirm');

    // ========================================
    // SYSTEM MANAGEMENT
    // ========================================
    $router->resource('system-configurations', SystemConfigurationController::class);
    $router->resource('pesapal-payments', UniversalPaymentController::class);

    // System Health Check — Monitor data integrity and identify issues
    $router->get('system-health-check', 'SystemHealthCheckController@index')->name('system-health-check');

    // Health Check AJAX Batch Operations
    $router->post('system-health-check/batch-delete-groups', 'SystemHealthCheckController@batchDeleteGroups');
    $router->post('system-health-check/batch-delete-users', 'SystemHealthCheckController@batchDeleteUsers');
    $router->post('system-health-check/batch-assign-facilitator', 'SystemHealthCheckController@batchAssignFacilitator');
    $router->post('system-health-check/batch-assign-ip', 'SystemHealthCheckController@batchAssignIp');
    $router->post('system-health-check/batch-clear-field', 'SystemHealthCheckController@batchClearField');
    $router->post('system-health-check/batch-update-group-status', 'SystemHealthCheckController@batchUpdateGroupStatus');
    $router->post('system-health-check/merge-duplicate-users', 'SystemHealthCheckController@mergeDuplicateUsers');

    // Users management
    $router->resource('users', UserController::class);

    // ========================================
    // ADMIN USER MANAGEMENT — 360° portal account management
    // NOTE: Custom action routes MUST come before the resource to avoid conflicts
    // ========================================
    $router->get('admin-users/{id}/activate',         'AdminUserController@activate')->name('admin-users.activate');
    $router->get('admin-users/{id}/deactivate',       'AdminUserController@deactivate')->name('admin-users.deactivate');
    $router->get('admin-users/{id}/reset-password',   'AdminUserController@resetPassword')->name('admin-users.reset-password');
    $router->get('admin-users/{id}/send-credentials', 'AdminUserController@sendCredentials')->name('admin-users.send-credentials');
    $router->get('admin-users/{id}/change-password',  'AdminUserController@changePassword')->name('admin-users.change-password');
    $router->post('admin-users/{id}/change-password', 'AdminUserController@updatePassword')->name('admin-users.update-password');
    $router->resource('admin-users', AdminUserController::class);

    // ========================================
    // IP USER MANAGEMENT (hierarchy-scoped: super admin / IP manager / facilitator)
    // ========================================
    $router->get('ip-users/{id}/send-credentials', 'IPUserController@sendCredentials')->name('ip-users.send-credentials');
    $router->resource('ip-users', IPUserController::class);

    // ========================================
    // IP MANAGERS MANAGEMENT — Dedicated controller for IP Manager accounts
    // ========================================
    $router->get('ip-managers/{id}/send-credentials', 'IPManagerController@sendCredentials')->name('ip-managers.send-credentials');
    $router->get('ip-managers/{id}/reset-password', 'IPManagerController@resetPassword')->name('ip-managers.reset-password');
    $router->resource('ip-managers', IPManagerController::class);

    // ========================================
    // FACILITATORS MANAGEMENT
    // ========================================
    $router->get('facilitators/{id}/send-credentials', 'FacilitatorController@sendCredentials')->name('facilitators.send-credentials');
    $router->resource('facilitators', FacilitatorController::class);

    // ========================================
    // GROUPS MANAGEMENT - All group types handled by one controller
    // The controller detects group type from URL string
    // ========================================
    $router->resource('ffs-all-groups', FfsGroupController::class);
    $router->resource('ffs-farmer-field-schools', FfsGroupController::class);
    $router->resource('ffs-farmer-business-schools', FfsGroupController::class);
    $router->resource('ffs-vslas', FfsGroupController::class);
    $router->resource('ffs-group-associations', FfsGroupController::class);

    // ========================================
    // MEMBERS MANAGEMENT
    // ========================================
    $router->get('ffs-members/{id}/send-credentials', 'MemberController@sendCredentials')->name('ffs-members.send-credentials');
    $router->get('ffs-members/{id}/send-welcome', 'MemberController@sendWelcome')->name('ffs-members.send-welcome');
    $router->resource('ffs-members', MemberController::class);

    // ========================================
    // ADVISORY MODULE - Knowledge Management
    // ========================================
    
    // Advisory Categories - Organize articles by topic
    $router->resource('advisory-categories', AdvisoryCategoryController::class);
    
    // Advisory Posts/Articles - Educational content with multimedia
    $router->resource('advisory-posts', AdvisoryPostController::class);
    
    // Farmer Questions - Q&A from mobile app users
    $router->resource('farmer-questions', FarmerQuestionController::class);
    
    // Farmer Question Answers - Moderation & approval
    // NOTE: Specific routes MUST come before resource route
    $router->get('farmer-question-answers/{id}/approve', 'FarmerQuestionAnswerController@approve')->name('farmer-question-answers.approve');
    $router->get('farmer-question-answers/{id}/accept', 'FarmerQuestionAnswerController@accept')->name('farmer-question-answers.accept');
    $router->resource('farmer-question-answers', FarmerQuestionAnswerController::class);

    // ========================================
    // VSLA MODULE - Village Savings & Loan Association
    // ========================================
    
    // VSLA Profiles - Unified VSLA profiling (auto-generates group + cycle + chairperson)
    $router->resource('vsla-profiles', VslaProfileController::class);
    
    // VSLA Meetings - View meeting records from mobile app
    $router->resource('vsla-meetings', VslaMeetingController::class);
    
    // VSLA Loans - Loan management and tracking
    $router->resource('vsla-loans', VslaLoanController::class);
    
    // VSLA Loan Transactions - Detailed loan event tracking
    $router->resource('loan-transactions', LoanTransactionController::class);
    
    // VSLA Action Plans - Track action items from meetings
    $router->resource('vsla-action-plans', VslaActionPlanController::class);
    
    // VSLA Meeting Attendance - View attendance records
    $router->resource('vsla-meeting-attendance', VslaMeetingAttendanceController::class);

    // VSLA Opening Balances — cycle opening balances with per-member financial fan-out
    // NOTE: Custom routes MUST be registered BEFORE the resource to avoid parameter conflicts
    $router->get('vsla-opening-balance-cycles', 'VslaOpeningBalanceController@apiCyclesForGroup');
    $router->get('vsla-opening-balances/{id}/reprocess', 'VslaOpeningBalanceController@reprocess');
    $router->resource('vsla-opening-balances', VslaOpeningBalanceController::class);

    // ========================================
    // FFS MODULE - Training Sessions, Participants & Resolutions
    // ========================================
    
    // Training Sessions - Facilitator-scheduled sessions
    $router->resource('ffs-training-sessions', FfsTrainingSessionController::class);
    
    // Session Participants - Attendance tracking
    $router->resource('ffs-session-participants', FfsSessionParticipantController::class);
    
    // Session Resolutions (GAP) - Meeting outcomes & follow-ups
    $router->resource('ffs-session-resolutions', FfsSessionResolutionController::class);

    // ========================================
    // ENTERPRISE & PRODUCTION PROTOCOLS - Farming Venture Blueprints
    // ========================================
    
    // Enterprises - Farming ventures (livestock/crop based)
    $router->resource('enterprises', EnterpriseController::class);
    
    // Production Protocols - Activity blueprints for enterprises
    $router->resource('production-protocols', ProductionProtocolController::class);

    // Farms - Individual farmer plots/farms linked to enterprises
    $router->resource('farms', FarmController::class);

    // Farm Activities - Tracked activities on each farm
    $router->resource('farm-activities', FarmActivityController::class);

    // ========================================
    // MARKET PRICES - Agricultural Product Pricing Information
    // ========================================
    
    // Market Price Categories - Categories for agricultural products
    $router->resource('market-price-categories', MarketPriceCategoryController::class);
    
    // Market Price Products - Individual agricultural products
    $router->resource('market-price-products', MarketPriceProductController::class);
    
    // Market Prices - Price records for products
    $router->resource('market-prices', MarketPriceController::class);

    // ========================================
    // SERIES MOVIES — Debug & Content Management
    // Single controller handles all slugs; detects filter from URL
    // ========================================
    $router->resource('series-movies', SeriesMovieController::class);
    $router->resource('series-movies-pending', SeriesMovieController::class);
    $router->resource('series-movies-success', SeriesMovieController::class);
    $router->resource('series-movies-fail', SeriesMovieController::class);

    // ========================================
    // MOVIES — Debug & Content Management
    // Single controller handles all slugs; detects filter from URL
    // ========================================
    $router->resource('movies', MovieController::class);
    $router->resource('movies-pending', MovieController::class);
    $router->resource('movies-success', MovieController::class);
    $router->resource('movies-fail', MovieController::class);

    // ========================================
    // AESA — Agro-Ecosystem Analysis
    // Stats dashboard, sessions list, observations list
    // ========================================
    $router->get('aesa-stats', 'AesaStatsController@index');
    $router->resource('aesa-admin-sessions', AesaSessionController::class);
    $router->resource('aesa-admin-observations', AesaObservationController::class);
    $router->resource('aesa-admin-crop-observations', AesaCropObservationController::class);

    // ========================================
    // KPI TRACKING — Facilitator & IP performance
    // ========================================
    $router->resource('kpi-benchmarks', KpiFacilitatorController::class); // backward-compatible alias
    $router->resource('kpi-facilitators', KpiFacilitatorController::class);
    $router->get('kpi-ips', 'KpiIpController@index');
    $router->get('kpi-stats', 'KpiStatsController@index');

    // ========================================
    // FFS KPI MANUAL ENTRY — Monthly tracking against project targets
    // ========================================
    $router->get('ffs-kpi-dashboard', 'FfsKpiDashboardController@index');

    // PDF report routes MUST be before the resource() to avoid being matched as {id}
    $router->get('ffs-kpi-ip-entries/pdf-report',      'FfsKpiIpController@pdfReport')
           ->name('ffs-kpi-ip-entries.pdf-report');
    $router->get('ffs-kpi-ip-entries/pdf-performance', 'FfsKpiIpController@pdfPerformance')
           ->name('ffs-kpi-ip-entries.pdf-performance');

    $router->resource('ffs-kpi-ip-entries', 'FfsKpiIpController');

    $router->get('ffs-kpi-facilitator-entries/pdf-report', 'FfsKpiFacilitatorEntryController@pdfReport')
           ->name('ffs-kpi-facilitator-entries.pdf-report');
    $router->resource('ffs-kpi-facilitator-entries', 'FfsKpiFacilitatorEntryController');

    // ========================================
    // OPERATIONS DASHBOARD — Super Admin monitoring
    // Groups, facilitators, financial health, loan portfolio
    // ========================================
    $router->get('operations-dashboard', 'OperationsDashboardController@index')
           ->name('operations-dashboard');
});
