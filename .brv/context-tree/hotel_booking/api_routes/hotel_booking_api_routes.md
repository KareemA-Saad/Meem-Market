
## Relations
@hotel_booking/module_overview
@hotel_booking/service_providers

'''php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Admin Controllers
use Modules\HotelBooking\Http\Controllers\Admin\HotelController as AdminHotelController;
use Modules\HotelBooking\Http\Controllers\Admin\RoomTypeController as AdminRoomTypeController;
use Modules\HotelBooking\Http\Controllers\Admin\RoomController as AdminRoomController;
use Modules\HotelBooking\Http\Controllers\Admin\BookingController as AdminBookingController;
use Modules\HotelBooking\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use Modules\HotelBooking\Http\Controllers\Admin\AmenityController as AdminAmenityController;
use Modules\HotelBooking\Http\Controllers\Admin\CancellationPolicyController as AdminCancellationPolicyController;

// Frontend Controllers
use Modules\HotelBooking\Http\Controllers\Frontend\HotelController as FrontendHotelController;
use Modules\HotelBooking\Http\Controllers\Frontend\RoomController as FrontendRoomController;
use Modules\HotelBooking\Http\Controllers\Frontend\BookingController as FrontendBookingController;
use Modules\HotelBooking\Http\Controllers\Frontend\ReviewController as FrontendReviewController;

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    // Public Hotel Routes (Frontend)
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->group(function () {
            // ... (public hotel, room, and booking routes)
        });

    // Authenticated User Routes (Frontend)
    Route::middleware(['auth:api_tenant_user', 'tenancy.token', 'tenant.context'])
        ->group(function () {
            // ... (user's bookings and reviews routes)
        });

    // Admin Routes
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:hotel-booking'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            // ... (admin routes for hotels, room types, rooms, bookings, inventory, amenities, cancellation policies)
        });
});
'''
