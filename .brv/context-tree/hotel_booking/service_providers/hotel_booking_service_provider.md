
## Relations
@hotel_booking/module_overview
@hotel_booking/api_routes

'''php
<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\HotelBooking\Services\HotelService;
use Modules\HotelBooking\Services\RoomTypeService;
// ... other services

class HotelBookingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Register services as singletons
        $this->app->singleton(HotelService::class, function ($app) {
            return new HotelService();
        });

        $this->app->singleton(RoomTypeService::class, function ($app) {
            return new RoomTypeService();
        });

        // ... registration for all other module services
    }

    public function provides(): array
    {
        return [
            HotelService::class,
            RoomTypeService::class,
            // ... other provided services
        ];
    }
}
'''
