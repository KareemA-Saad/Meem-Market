## Relations
@real_estate/lead_management/inquiry_communications.md

## Raw Concept
**Task:**
Automate property viewing appointment reminders.

**Changes:**
- Implemented automated viewing appointment reminder emails.

**Files:**
- Modules/RealEstate/Mail/ViewingReminderMail.php

**Flow:**
Trigger (Scheduler/Admin Action) -> ViewingReminderMail -> (Queue) -> Customer/Agent Email

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Mail/ViewingReminderMail.php

### Dependencies
- Laravel Mail system
- Carbon for date parsing
- Markdown email template (realestate::emails.viewing-reminder)

### Features
- Appointment Reminders: The ViewingReminderMail class formats and sends automated emails for scheduled property viewings.
- Dynamic Subjects: Includes the property title and formatted appointment date in the email subject for quick identification.
- Data Injection: Passes structured appointment data (customer info, property details, notes) and a Carbon instance of the date to the Markdown view.
- Queue Support: Implements ShouldQueue for asynchronous delivery to avoid blocking request flows.
