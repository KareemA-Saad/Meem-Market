
## Relations
@hotel_booking/api_routes
@hotel_booking/service_providers

'''
# Hotel Booking Module

This module provides a complete multi-tenant hotel reservation system.

## Key Components:

**Entities:**
- Hotel, RoomType, Room, BookingInformation, BookingRoom, Inventory, Amenity, Review, CancellationPolicy, PaymentLog

**Services:**
- HotelService, RoomTypeService, RoomService, AmenityService, InventoryService, PricingService, RoomSearchService, RoomHoldService, BookingService, HotelPaymentService, RefundService, CancellationPolicyService

**Features:**
- 15-minute room holds with lazy cleanup
- Multi-room bookings
- Day-wise pricing from Inventory table
- Cancellation policy tiers with auto-refund
- Standard check-in (3 PM) / check-out (11 AM)
- Payment gateway integration (Stripe/PayPal/COD)

**API Structure:**
- 7 Admin Controllers and 4 Frontend Controllers with OpenAPI documentation.
- Routes are tenant-scoped under `/api/v1/tenant/{tenant}/`.
'''
