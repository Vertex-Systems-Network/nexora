# Nexora N0.32 — Membership + Helpdesk Foundations

N0.32 adds two provider-neutral Core domains without absorbing LMS, Booking, or Projects into Nexora Core.

## Membership

Membership owns access state, not billing identity. Plans can define typed entitlements and may optionally map to a Commerce recurring price. An explicit Commerce subscription link can synchronize a Membership record, while Commerce continues to own the customer, price, subscription, invoice, payment, and refund records.

Protected-content policies are evaluated by `MembershipAccessContract` before public Theme/Studio document rendering. A policy may require any/all selected plans and entitlement keys. Resources with no active policy behave exactly as before.

Maintenance command:

```bash
php artisan nexora:membership:expire
```

The command runs hourly and moves ended active/trial memberships into the expired state through the normal membership manager, preserving lifecycle events and Automation triggers.

## Helpdesk

Helpdesk owns support tickets, messages, internal notes, assignments, priorities, ticket events, and SLA deadline state. Requesters may link to a Nexora user, CRM contact, or Commerce customer without merging those identities.

SLA policies provide first-response and resolution targets in elapsed minutes. N0.32 deliberately does not claim business-calendar/SLA-calendar compliance; `business_hours` is reserved for future adapter-based evaluation.

Maintenance command:

```bash
php artisan nexora:helpdesk:sla-check
```

It runs every five minutes to refresh first-response and resolution breach state.

## Automation events

Membership:
- `membership.granted`
- `membership.status_changed`

Helpdesk:
- `helpdesk.ticket.created`
- `helpdesk.reply.added`
- `helpdesk.note.added`
- `helpdesk.status.changed`
- `helpdesk.priority.changed`
- `helpdesk.assigned_to.changed`

## External boundaries

LMS, Booking, Projects, Books, and CV/Profile remain external package families. They may consume Membership entitlements, Helpdesk, Commerce, CRM, Automation, and public Nexora contracts, but N0.32 does not introduce private Core shortcuts for them.
