# Privasee API Documentation

## Overview

The Privasee API is a RESTful API built with Laravel 11 that provides endpoints for managing users, businesses, venues, offers, and more. All API responses follow a consistent JSON format.

## Base URL

```
Production: https://api.privasee.ae/v1
Staging: https://staging-api.privasee.ae/v1
Local: http://localhost/api/v1
```

## Authentication

The API uses Laravel Sanctum for authentication. Include the bearer token in the Authorization header:

```
Authorization: Bearer {your-token}
```

## Response Format

All API responses follow this structure:

```json
{
  "success": true|false,
  "message": "Response message",
  "data": {}, // Response data
  "errors": {}, // Validation errors (if any)
  "meta": {} // Additional metadata (pagination, etc.)
}
```

## Rate Limiting

- General API endpoints: 60 requests per minute
- Authentication endpoints: 5 requests per minute
- Search endpoints: 30 requests per minute

## Error Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error

## Authentication Endpoints

### Register User

```http
POST /auth/register
```

**Request Body:**
```json
{
  "first_name": "Sarah",
  "last_name": "Ahmed",
  "email": "sarah@example.com",
  "phone": "+971501234567",
  "password": "password123",
  "password_confirmation": "password123",
  "date_of_birth": "1990-05-15",
  "nationality": "UAE",
  "preferred_language": "en",
  "marketing_consent": true,
  "data_processing_consent": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful. Please verify your phone number.",
  "data": {
    "user": {
      "id": 1,
      "first_name": "Sarah",
      "last_name": "Ahmed",
      "email": "sarah@example.com",
      "phone": "+971501234567"
    },
    "requires_verification": true
  }
}
```

### Login User

```http
POST /auth/login
```

**Request Body:**
```json
{
  "email": "sarah@example.com",
  "password": "password123",
  "remember_me": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "first_name": "Sarah",
      "last_name": "Ahmed",
      "email": "sarah@example.com",
      "subscription_type": "premium",
      "subscription_status": "active"
    },
    "token": "1|abc123...",
    "subscription_active": true,
    "email_verified": true,
    "phone_verified": true
  }
}
```

### Logout User

```http
POST /auth/logout
```

**Headers:**
```
Authorization: Bearer {token}
```

### Send OTP

```http
POST /auth/send-otp
```

**Request Body:**
```json
{
  "phone": "+971501234567"
}
```

### Verify OTP

```http
POST /auth/verify-otp
```

**Request Body:**
```json
{
  "phone": "+971501234567",
  "otp": "123456"
}
```

## User Endpoints

### Get User Profile

```http
GET /user/profile
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "first_name": "Sarah",
      "last_name": "Ahmed",
      "email": "sarah@example.com",
      "phone": "+971501234567",
      "date_of_birth": "1990-05-15",
      "nationality": "UAE",
      "subscription_type": "premium",
      "subscription_status": "active",
      "subscription_expires_at": "2024-02-15T10:30:00Z"
    },
    "subscription_active": true,
    "profile_completion": 85
  }
}
```

### Update User Profile

```http
PUT /user/profile
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "first_name": "Sarah",
  "last_name": "Ahmed",
  "date_of_birth": "1990-05-15",
  "nationality": "UAE",
  "preferred_language": "en"
}
```

## Venue Endpoints

### Get Venues

```http
GET /venues
```

**Query Parameters:**
- `search` - Search by name or description
- `category_id` - Filter by category
- `emirate` - Filter by emirate
- `city` - Filter by city
- `price_range` - Filter by price range ($, $$, $$$, $$$$)
- `women_only` - Filter women-only venues (true/false)
- `featured` - Filter featured venues (true/false)
- `min_rating` - Minimum rating (1-5)
- `latitude` & `longitude` - Location-based search
- `radius` - Search radius in km (default: 10)
- `sort_by` - Sort by (relevance, rating, reviews, visits, newest, distance)
- `per_page` - Results per page (default: 20, max: 50)

**Example:**
```http
GET /venues?emirate=Dubai&women_only=true&min_rating=4&per_page=10
```

**Response:**
```json
{
  "success": true,
  "data": {
    "venues": [
      {
        "id": 1,
        "name": "Luxury Spa Dubai",
        "description": "Premium spa services...",
        "address": "Dubai Marina",
        "city": "Dubai",
        "emirate": "Dubai",
        "latitude": 25.2048,
        "longitude": 55.2708,
        "phone": "+97143334444",
        "operating_hours": {
          "monday": {"open": "09:00", "close": "21:00"}
        },
        "amenities": ["WiFi", "Parking", "AC"],
        "price_range": "$$$",
        "is_women_only": true,
        "is_featured": false,
        "average_rating": 4.5,
        "total_reviews": 128,
        "business": {
          "id": 1,
          "name": "Spa Group LLC",
          "verification_status": "verified"
        },
        "category": {
          "id": 1,
          "name": "Beauty & Spa"
        },
        "featured_image": "https://...",
        "gallery": [
          {
            "id": 1,
            "url": "https://...",
            "thumb": "https://..."
          }
        ],
        "is_favorite": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 10,
      "total": 45,
      "has_more": true
    },
    "filters": {
      "emirate": "Dubai",
      "women_only": true,
      "min_rating": 4
    }
  }
}
```

### Get Venue Details

```http
GET /venues/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Luxury Spa Dubai",
    "description": "Premium spa services...",
    "address": "Dubai Marina",
    "operating_hours": {
      "monday": {"open": "09:00", "close": "21:00"},
      "tuesday": {"open": "09:00", "close": "21:00"}
    },
    "amenities": ["WiFi", "Parking", "AC"],
    "business": {},
    "category": {},
    "reviews": [
      {
        "id": 1,
        "rating": 5,
        "title": "Amazing experience",
        "comment": "Great service and ambiance",
        "user": {
          "name": "Sarah A.",
          "avatar": "https://..."
        },
        "created_at": "2024-01-15T10:30:00Z"
      }
    ],
    "offers": [
      {
        "id": 1,
        "title": "50% Off First Visit",
        "discount_value": 50,
        "end_date": "2024-02-15T23:59:59Z"
      }
    ],
    "is_favorite": false,
    "user_has_visited": true
  }
}
```

### Get Featured Venues

```http
GET /venues/featured
```

### Get Nearby Venues

```http
GET /venues/nearby?latitude=25.2048&longitude=55.2708&radius=10
```

### Toggle Favorite Venue

```http
POST /venues/{id}/favorite
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Venue added to favorites",
  "data": {
    "is_favorite": true
  }
}
```

### Get User Favorites

```http
GET /user/favorites
```

**Headers:**
```
Authorization: Bearer {token}
```

## Offer Endpoints

### Get Offers

```http
GET /offers
```

**Query Parameters:**
- `venue_id` - Filter by venue
- `business_id` - Filter by business
- `type` - Filter by offer type
- `featured` - Filter featured offers
- `per_page` - Results per page

**Response:**
```json
{
  "success": true,
  "data": {
    "offers": [
      {
        "id": 1,
        "title": "50% Off First Visit",
        "description": "Get 50% discount on your first spa treatment",
        "type": "discount",
        "discount_type": "percentage",
        "discount_value": 50,
        "original_price": 200,
        "discounted_price": 100,
        "start_date": "2024-01-01T00:00:00Z",
        "end_date": "2024-02-15T23:59:59Z",
        "usage_limit": 100,
        "used_count": 25,
        "is_available": true,
        "remaining_uses": 75,
        "days_remaining": 15,
        "venue": {
          "id": 1,
          "name": "Luxury Spa Dubai"
        },
        "business": {
          "id": 1,
          "name": "Spa Group LLC"
        },
        "user_can_redeem": true,
        "user_redemptions_count": 0
      }
    ],
    "pagination": {}
  }
}
```

### Get Offer Details

```http
GET /offers/{id}
```

### Redeem Offer

```http
POST /offers/{id}/redeem
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Offer redeemed successfully",
  "data": {
    "redemption": {
      "id": 1,
      "verification_code": "ABC12345",
      "redeemed_at": "2024-01-15T10:30:00Z",
      "status": "pending"
    }
  }
}
```

### Get User Redemptions

```http
GET /offers/my-redemptions
```

**Headers:**
```
Authorization: Bearer {token}
```

## Business Endpoints

### Register Business

```http
POST /business/register
```

**Request Body:**
```json
{
  "name": "Luxury Spa Group",
  "name_ar": "مجموعة السبا الفاخرة",
  "description": "Premium spa and wellness services",
  "email": "info@luxuryspa.ae",
  "phone": "+97143334444",
  "website": "https://luxuryspa.ae",
  "trade_license_number": "TL-123456",
  "trade_license_expiry": "2025-12-31",
  "owner_name": "Ahmed Al Mansouri",
  "owner_email": "ahmed@luxuryspa.ae",
  "owner_phone": "+971501234567",
  "is_women_only": true
}
```

## Payment Endpoints

### Subscribe to Plan

```http
POST /payments/subscribe
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "plan_type": "premium",
  "payment_method_id": "pm_1234567890",
  "payment_method_type": "card"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription created successfully",
  "data": {
    "subscription": {
      "id": 1,
      "type": "premium",
      "status": "active",
      "amount": 199,
      "expires_at": "2024-02-15T10:30:00Z"
    },
    "payment": {
      "id": 1,
      "amount": 199,
      "status": "completed",
      "transaction_id": "pi_1234567890"
    }
  }
}
```

### Get Payment History

```http
GET /payments/history
```

**Headers:**
```
Authorization: Bearer {token}
```

## Categories

### Get Categories

```http
GET /venues/categories
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Beauty & Spa",
      "name_ar": "الجمال والسبا",
      "slug": "beauty-spa",
      "icon": "spa",
      "color": "#EC4899",
      "venues_count": 45,
      "children": [
        {
          "id": 2,
          "name": "Hair Salons",
          "name_ar": "صالونات الشعر",
          "venues_count": 12
        }
      ]
    }
  ]
}
```

## Search

### Global Search

```http
GET /search?q=spa&type=venues,offers
```

**Query Parameters:**
- `q` - Search query
- `type` - Search types (venues, offers, businesses)
- `emirate` - Filter by emirate
- `category_id` - Filter by category

## Webhooks

### Stripe Webhook

```http
POST /webhooks/stripe
```

This endpoint handles Stripe webhook events for payment processing.

## Error Handling

### Validation Errors

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Authentication Errors

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "error": "Token not provided or invalid"
}
```

### Rate Limit Errors

```json
{
  "success": false,
  "message": "Too Many Attempts.",
  "error": "Rate limit exceeded. Try again in 60 seconds."
}
```

## SDKs and Libraries

### JavaScript/TypeScript

```javascript
// Example using fetch
const response = await fetch('https://api.privasee.ae/v1/venues', {
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

const data = await response.json();
```

### PHP

```php
// Example using Guzzle
$client = new \GuzzleHttp\Client([
    'base_uri' => 'https://api.privasee.ae/v1/',
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ]
]);

$response = $client->get('venues');
$data = json_decode($response->getBody(), true);
```

## Testing

Use the following test credentials for development:

**Test User:**
- Email: `test@privasee.ae`
- Password: `password123`

**Test Business:**
- Email: `business@privasee.ae`
- Password: `password123`

## Support

For API support, contact:
- Email: api-support@privasee.ae
- Documentation: https://docs.privasee.ae
- Status Page: https://status.privasee.ae