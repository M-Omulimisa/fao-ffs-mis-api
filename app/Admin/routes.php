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
    $router->resource('dtehm-memberships', DtehmMembershipController::class);
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
    $router->resource('cycles', ProjectController::class);
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

    // Users management
    $router->resource('users', UserController::class);

    // User Hierarchy & Network - View only for all admin users
    $router->resource('user-hierarchy', UserHierarchyController::class);

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
});
