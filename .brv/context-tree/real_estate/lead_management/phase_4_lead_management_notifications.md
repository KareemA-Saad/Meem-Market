## Relations
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md
@real_estate/foundation/phase_1_foundation_overview.md

## Raw Concept
**Task:**
Phase 4 RealEstate Lead Management & Notifications

**Changes:**
- Created 5 Notification classes for inquiry and viewing lifecycle.
- Created 3 Mailable classes and corresponding Blade templates.
- Implemented AgentDashboardController with 6 endpoints for stats and lead management.
- Updated InquiryService to handle notification dispatch and agent assignment.

**Files:**
- Modules/RealEstate/Notifications/NewInquiryNotification.php
- Modules/RealEstate/Notifications/InquiryReceivedNotification.php
- Modules/RealEstate/Notifications/InquiryAssignedNotification.php
- Modules/RealEstate/Notifications/InquiryStatusUpdatedNotification.php
- Modules/RealEstate/Notifications/ViewingAppointmentReminderNotification.php
- Modules/RealEstate/Mail/NewInquiryMail.php
- Modules/RealEstate/Mail/InquiryConfirmationMail.php
- Modules/RealEstate/Mail/ViewingReminderMail.php
- Modules/RealEstate/Http/Controllers/Agent/AgentDashboardController.php
- Modules/RealEstate/Services/InquiryService.php

**Flow:**
Customer submits inquiry -> InquiryService creates record -> InquiryService dispatches NewInquiryNotification (Staff) & InquiryReceivedNotification (Customer) -> Admin/System assigns Agent -> InquiryAssignedNotification dispatched -> Agent manages via AgentDashboardController -> Status updates trigger InquiryStatusUpdatedNotification.

**Timestamp:** 2026-01-20

## Narrative
### Structure
# Lead Management Structure
- **Controllers**:
  - `Modules/RealEstate/Http/Controllers/Agent/AgentDashboardController.php`: Handles agent-specific stats, properties, and inquiry management.
- **Services**:
  - `Modules/RealEstate/Services/InquiryService.php`: Core logic for inquiry handling, status updates, and notification dispatch.
- **Notifications**:
  - `NewInquiryNotification`: Alerts staff of new leads.
  - `InquiryReceivedNotification`: Customer confirmation.
  - `InquiryAssignedNotification`: Agent assignment alert.
  - `InquiryStatusUpdatedNotification`: Status change log.
  - `ViewingAppointmentReminderNotification`: Appointment reminders.
- **Mailables**:
  - `NewInquiryMail`: Detailed email for agents.
  - `InquiryConfirmationMail`: Template for customers.
  - `ViewingReminderMail`: Template for appointments.

### Dependencies
# System Dependencies
- MAIL_MAILER=smtp
- MAIL_HOST=smtp.mailgun.org (or other provider)
- MAIL_PORT=587
- MAIL_USERNAME=your-username
- MAIL_PASSWORD=your-password
- MAIL_ENCRYPTION=tls
- MAIL_FROM_ADDRESS=noreply@yourdomain.com
- MAIL_FROM_NAME=AppName
- QUEUE_CONNECTION=database (for async notifications)

### Features
# Real Estate Lead Management Features
- **Automated Notifications**: System alerts agents and admins of new inquiries, and confirms receipt to customers.
- **Agent Dashboard**: Dedicated endpoints for agents to manage assigned properties and inquiries, including performance metrics.
- **Inquiry Assignment**: Automated and manual assignment of leads to agents with notification triggers.
- **Status Tracking**: Database-level tracking of inquiry status changes with notifications.
- **Viewing Appointments**: Reminder system for scheduled property viewings.
