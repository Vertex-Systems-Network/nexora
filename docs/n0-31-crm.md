# Nexora N0.31 — CRM Foundation

N0.31 introduces a provider-neutral CRM domain on top of the existing Nexora Kernel, Identity, Automation, Search and Commerce boundaries.

## Core records

- Organizations / companies
- Contacts
- Leads
- Pipelines and ordered stages
- Opportunities / deals
- Activities
- Notes
- Relationship timeline events
- Opportunity stage history
- Typed custom-field definitions and values
- Explicit CRM ↔ Commerce-customer links

## Identity and billing boundary

A CRM Contact or Organization is a relationship/sales record. A Commerce Customer is a billing identity. Nexora deliberately does not merge these records. `nx_crm_commerce_links` joins them explicitly so CRM edits do not rewrite historical order, invoice or payment identity data.

## Pipeline safety

Lead conversion and opportunity stage movement execute inside database transactions. The target pipeline/stage is validated, the opportunity row is locked during stage movement, immutable stage-history events are appended, relationship timeline events are written, and Automation events are emitted only through the Automation event bus.

Opportunity values are stored as integer minor units with an ISO currency code. CRM does not use floating-point money.

## Provider-neutral activity integrations

CRM Core does not contain Gmail, Outlook, Microsoft Graph, Google Calendar or other provider SDK/OAuth implementations. Extensions may implement `CrmActivityProviderContract` and register themselves with `CrmActivityProviderRegistry`. This preserves Sentinel, supply-chain and extension-capability boundaries.

## Admin UI rules

CRM feature pages consume `@nexora/admin-ui`. Lists use the shared DataTable with sticky headers and sticky pagination. Date/time fields use the shared DateTimePicker. Native/raw feature controls remain prohibited by Source Guard.

## Extension boundary

Books, CV/Profile, LMS, Booking and Projects remain external package families. CRM Core does not pull those domains back into the internal platform.
