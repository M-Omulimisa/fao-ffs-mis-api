# BlitXpress - Laravel E-commerce Backend

A comprehensive Laravel e-commerce backend system with admin panel, API endpoints, and robust order management capabilities. This project includes JWT authentication, Stripe payment integration, email notifications, and full CRUD operations for products, orders, and user management.

## Table of Contents

- [Project Overview](#project-overview)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Models & Database Schema](#models--database-schema)
- [API Endpoints](#api-endpoints)
- [Authentication System](#authentication-system)
- [Admin Panel](#admin-panel)
- [Payment Integration](#payment-integration)
- [Email System](#email-system)
- [Configuration](#configuration)
- [Deployment](#deployment)
- [Development Tools](#development-tools)

## Project Overview

BlitXpress is a full-featured e-commerce backend built with Laravel 8+ that provides:

- **Multi-channel API** - REST API for frontend applications (React, Flutter, etc.)
- **Admin Panel** - Laravel Admin for backend management
- **Order Management** - Complete order lifecycle with state management
- **Payment Processing** - Stripe integration for secure payments
- **User Authentication** - JWT-based authentication with role management
- **Email Notifications** - Automated email system for order states
- **Product Catalog** - Comprehensive product management with categories, variations, and images
- **Geographic Support** - Location-based delivery and regional management

## Architecture

```text
Frontend (React/Flutter) ← REST API → Laravel Backend ← Admin Panel
                                         ↓
                                   MySQL Database
                                         ↓
                              External Services (Stripe, Email)
```

### Technology Stack

- **Backend Framework**: Laravel 8+
- **Database**: MySQL
- **Authentication**: JWT (tymon/jwt-auth)
- **Admin Panel**: Laravel Admin (encore/laravel-admin)
- **Payment Gateway**: Stripe
- **Email Service**: SMTP (configurable)
- **Image Processing**: Custom utilities with thumbnail generation
- **File Storage**: Local filesystem (configurable for AWS S3)

## Project Structure

```text
blitxpress/
├── app/
│   ├── Admin/           # Laravel Admin controllers and routes
│   ├── Http/
│   │   ├── Controllers/ # API and web controllers
│   │   └── Middleware/  # Custom middleware (authentication, CORS)
│   ├── Models/          # Eloquent models
│   └── Traits/          # Reusable model traits
├── config/              # Laravel configuration files
├── database/
│   ├── migrations/      # Database schema migrations
│   └── seeders/         # Data seeders
├── public/              # Web-accessible files, images, assets
├── resources/           # Views, frontend assets, language files
├── routes/
│   ├── api.php         # API routes
│   ├── web.php         # Web routes (includes test/utility routes)
│   └── Admin/routes.php # Admin panel routes
└── storage/             # File storage, logs, cache
```

## Models & Database Schema

### Core Models

#### User Model (`app/Models/User.php`)

- **Purpose**: User authentication and profile management
- **Key Features**: JWT authentication, role-based access, profile fields
- **Relationships**: HasMany orders, delivery addresses
- **Special Fields**: `phone`, `avatar`, `is_admin`, JWT-related timestamps

#### Order Model (`app/Models/Order.php`)
- **Purpose**: Complete order management system
- **Key Features**: State machine, email notifications, payment tracking
- **Order States**: 
  - `0`: Pending
  - `1`: Processing  
  - `2`: Completed
  - `3`: Canceled
  - `4`: Failed
- **Key Fields**: `customer_id`, `amount`, `order_state`, `payment_confirmation`, `stripe_id`, `stripe_url`
- **Email Tracking**: `pending_mail_sent`, `processing_mail_sent`, `completed_mail_sent`, `canceled_mail_sent`, `failed_mail_sent`
- **Financial Fields**: `sub_total`, `tax`, `discount`, `delivery_fee`
- **Relationships**: BelongsTo User, HasMany OrderedItems

#### Product Model (`app/Models/Product.php`)
- **Purpose**: Product catalog management
- **Key Features**: Categories, variations (colors/sizes), pricing, images
- **Variation Support**: `has_colors`, `colors`, `has_sizes`, `sizes`
- **Financial Fields**: `price`, `sale_price`, `wholesale_price`
- **Meta Fields**: `details`, `description`, `features`
- **Relationships**: BelongsTo ProductCategory, HasMany OrderedItems

#### ProductCategory Model (`app/Models/ProductCategory.php`)
- **Purpose**: Product categorization and organization
- **Key Features**: Hierarchical structure, banner support
- **Special Fields**: `is_first_banner` (for promotional display)
- **Relationships**: HasMany Products

### Supporting Models

#### DeliveryAddress Model (`app/Models/DeliveryAddress.php`)
- **Purpose**: Delivery location management
- **Key Features**: GPS coordinates, shipping cost calculation
- **Fields**: `address`, `latitude`, `longitude`, `shipping_cost`

#### Location Model (`app/Models/Location.php`)
- **Purpose**: Geographic hierarchy (districts, sub-counties)
- **Key Features**: Parent-child relationships for administrative divisions
- **Methods**: `get_districts()`, `get_sub_counties()`, `get_sub_counties_array()`

#### Utils Model (`app/Models/Utils.php`)
- **Purpose**: Utility functions and shared operations
- **Key Features**: Image processing, email sending, data synchronization
- **Key Methods**: `mail_sender()`, `create_thumbail()`, `sync_products()`, `sync_orders()`

#### Gen Model (`app/Models/Gen.php`)
- **Purpose**: Code generation and dynamic form creation
- **Key Features**: Auto-generates admin forms and API responses
- **Methods**: `do_get()`, `make_forms()`, `to_json()`

### Legacy/Invoice Models
The system includes invoice-related models (`Invoice`, `InvoiceItem`, `Quotation`, `QuotationItem`, `Delivery`, `DeliveryItem`) which appear to be from a previous business logic iteration. These are maintained for data continuity but may not be actively used in the current e-commerce flow.

## API Endpoints

### Authentication API (`/api/auth/*`)

```php
POST /api/auth/login
```
- **Purpose**: User login with JWT token generation
- **Input**: `phone/email`, `password`
- **Output**: User data + JWT token
- **Controller**: `ApiAuthController@login`

```php
POST /api/auth/register  
```
- **Purpose**: User registration
- **Input**: User profile data
- **Output**: User data + JWT token
- **Controller**: `ApiAuthController@register`

### Products API (`/api/products/*`)

```php
GET /api/products
```
- **Purpose**: Get all products with pagination
- **Output**: Products list with categories, images, pricing
- **Controller**: `ApiResurceController@products`

```php
GET /api/products/{id}
```
- **Purpose**: Get single product details
- **Output**: Product details with variations, related products
- **Controller**: `ApiResurceController@products`

### Categories API

```php
GET /api/product-categories
```
- **Purpose**: Get product categories
- **Output**: Category hierarchy with banner information
- **Controller**: `ApiResurceController@product_categories`

### Orders API (`/api/orders/*`)

```php
POST /api/orders
```
- **Purpose**: Create new order
- **Input**: Order data, customer info, items list
- **Features**: 
  - Automatic user lookup/creation
  - Order state initialization
  - Email notification triggering
- **Controller**: `ApiResurceController@orders`

```php
GET /api/orders
```
- **Purpose**: Get user's order history
- **Authentication**: Required (JWT)
- **Output**: Orders with items, delivery info, payment status

### Users API

```php
GET /api/users/{id}
```
- **Purpose**: Get user profile
- **Output**: User data with orders, delivery addresses

### Utility Routes (`/api/utils/*`)

```php
GET /api/sync
```
- **Purpose**: Data synchronization between systems
- **Functions**: `Utils::sync_products()`, `Utils::sync_orders()`

## Authentication System

### JWT Implementation
- **Library**: `tymon/jwt-auth`
- **Token Storage**: Sent in Authorization header as `Bearer {token}`
- **User Lookup**: Multiple field support (`phone`, `email`, `username`)
- **Token Expiration**: Configurable in `config/jwt.php`

### Middleware Stack
- **EnsureTokenIsValid**: Custom middleware for API authentication
- **CORS**: Enabled for cross-origin requests
- **Rate Limiting**: Applied to API routes

### Authentication Flow
1. User submits login credentials
2. System validates against multiple fields (`phone`, `email`)
3. JWT token generated and returned
4. Token included in subsequent API requests
5. Middleware validates token and sets authenticated user

## Admin Panel

### Laravel Admin Integration
- **Framework**: `encore/laravel-admin`
- **URL**: `/admin` (configurable)
- **Authentication**: Separate admin user system

### Admin Controllers (`app/Admin/Controllers/`)

#### OrderController
- **Purpose**: Order management interface
- **Features**: Order state updates, payment tracking, customer communication
- **Views**: Order listing, detail view, state management

#### ProductController  
- **Purpose**: Product catalog management
- **Features**: Product CRUD, category assignment, image upload, variation management
- **Special Features**: Bulk operations, inventory tracking

#### UserController
- **Purpose**: Customer management
- **Features**: User profiles, order history, account status management

### Admin Features
- **Dashboard**: Order analytics, sales reports, system status
- **Data Grid**: Sortable, filterable tables for all entities
- **Form Builder**: Dynamic forms with validation
- **File Upload**: Image management with thumbnail generation
- **Export/Import**: Data export capabilities

## Payment Integration

### Stripe Integration
- **API Version**: Latest Stripe PHP SDK
- **Payment Flow**: 
  1. Order creation
  2. Stripe payment link generation
  3. Payment confirmation webhook
  4. Order state update

### Payment Routes (`/pay`)
- **Success Callback**: `/pay?task=success&id={order_id}`
- **Cancel Callback**: `/pay?task=canceled&id={order_id}`
- **Update Handler**: `/pay?task=update&id={order_id}`

### Payment Fields
- **Order.stripe_id**: Stripe payment session ID
- **Order.stripe_url**: Payment link URL
- **Order.payment_confirmation**: Payment status flag
- **Order.amount**: Total payment amount

## Email System

### Email Configuration
- **SMTP Settings**: Configured in `.env`
- **Mail Service**: Custom utility in `Utils::mail_sender()`
- **Templates**: HTML email templates in `resources/views/emails/`

### Automated Email Triggers
- **Order State Changes**: Emails sent when order state updates
- **Email Tracking**: Prevents duplicate emails with state flags
- **Email Types**:
  - Order Confirmation (Pending → Processing)
  - Order Processing Notification
  - Order Completion Notice
  - Order Cancellation Alert
  - Payment Failure Notice

### Email Method (`Utils::mail_sender()`)
```php
Utils::mail_sender([
    'email' => $recipient,
    'subject' => $subject,
    'name' => $customer_name,
    'data' => $email_body
]);
```

## Configuration

### Environment Variables (`.env`)

#### Application Settings
```env
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8888/blitxpress/
APP_FOLDER=/Applications/MAMP/htdocs/blitxpress
```

#### Database Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blitxpress
DB_USERNAME=root
DB_PASSWORD=root
DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock
```

#### Payment Gateway
```env
STRIPE_KEY=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
```

#### Email Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=hambren.com
MAIL_PORT=465
MAIL_USERNAME=noreply@hambren.com
MAIL_PASSWORD=Dev@Team@2
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@hambren.com
MAIL_FROM_NAME=HAMBREN
```

#### Push Notifications
```env
ONESIGNAL_APP_ID=89e02cdc-adf7-436d-8931-2f789bcd740a
ONESIGNAL_REST_API_KEY=ZGViYThmYmQtNGMwMC00OThiLWFhM2QtMDQ0MWUzYzBlMzVm
```

### Key Configuration Files

#### JWT Configuration (`config/jwt.php`)
- Token expiration settings
- Secret key management
- Refresh token logic

#### Admin Configuration (`config/admin.php`)
- Admin panel routing
- Menu configuration
- Authentication settings

#### CORS Configuration (`config/cors.php`)
- Cross-origin request settings
- Allowed origins for frontend applications

## Deployment

### Production Checklist

1. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

2. **Database Migration**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

3. **Asset Optimization**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   composer install --optimize-autoloader --no-dev
   ```

4. **File Permissions**
   ```bash
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   ```

5. **Web Server Configuration**
   - Point document root to `public/` directory
   - Configure URL rewriting for Laravel routes
   - Enable HTTPS for production

### Server Requirements
- **PHP**: 7.4+ (8.0+ recommended)
- **MySQL**: 5.7+ or 8.0+
- **Composer**: Latest version
- **Extensions**: OpenSSL, PDO, Mbstring, Tokenizer, XML, JSON, GD

## Development Tools

### Utility Routes (Development Only)

#### Database Management
```php
GET /migrate  # Run migrations
GET /clear    # Clear all caches
GET /artisan?command={command}  # Run artisan commands
```

#### Testing & Debug
```php
GET /test     # Payment testing interface
GET /mail-test # Email system testing
GET /sync     # Data synchronization
```

#### Code Generation
```php
GET /gen?id={model_id}      # Generate API responses
GET /gen-form?id={model_id} # Generate admin forms
GET /generate-class         # Generate model classes
```

### Debugging Features
- **API Response Formatting**: Consistent JSON responses via `Utils` methods
- **Error Logging**: Comprehensive error logging to `storage/logs/`
- **Development Mail**: Test email functionality without sending real emails
- **Database Seeding**: Sample data for development testing

### File Structure Utilities
- **Image Processing**: Automatic thumbnail generation
- **File Upload**: Organized file storage with validation
- **Asset Management**: Public asset serving with proper MIME types

## Integration Points

### Frontend Integration (React/Flutter)
- **API Base URL**: `/api/`
- **Authentication Header**: `Authorization: Bearer {jwt_token}`
- **Response Format**: Consistent JSON with `success`, `message`, `data` fields
- **Error Handling**: HTTP status codes with descriptive error messages

### Third-Party Services
- **Stripe**: Payment processing and webhook handling
- **OneSignal**: Push notification delivery
- **SMTP**: Email delivery service
- **Image CDN**: Configurable for external image hosting

### Database Relationships
- Users → Orders (1:Many)
- Orders → OrderedItems (1:Many)  
- Products → OrderedItems (1:Many)
- ProductCategories → Products (1:Many)
- Locations → SubCounties (1:Many)

## Notes & Considerations

### Legacy Code
- Invoice/Quotation models exist but may not be used in current e-commerce flow
- Some utility routes in `web.php` are development/testing only
- Gen model provides dynamic code generation (may be overkill for simple CRUD)

### Security Considerations
- JWT tokens have configurable expiration
- CORS middleware protects against unauthorized origins
- CSRF protection enabled for web routes
- Input validation implemented in controllers

### Performance Optimization
- Database indexing on foreign keys and frequently queried fields
- Image thumbnail generation for faster loading
- Caching layers for configuration and routes
- Optimized autoloader for production

### Scalability Notes
- File storage can be migrated to AWS S3
- Database can be optimized with read replicas
- Redis can be implemented for session/cache storage
- Queue system available for background processing

---

**Last Updated**: December 2024  
**Laravel Version**: 8+  
**PHP Version**: 7.4+
