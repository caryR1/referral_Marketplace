# Gemz Referral CRM

Custom WordPress plugin powering refer.gemzonline.com — a referral/cashback
platform connecting customers to roofing, HVAC, and (later) solar
fulfillment partners, with a multi-level agent commission system.

## What's built (v0.1.0 — foundation pass)

- **Database schema** (`includes/class-grc-activator.php`): partners,
  agents, campaigns, leads, milestones, commissions, notifications_log,
  audit_log. Auto-upgrades on version bump (edit code, push, it re-syncs
  the schema on next page load — no manual reactivate needed).
- **Roles**: a `grc_agent` role for referrers, plus plugin capabilities
  granted to Administrator.
- **Referral codes**: unique per-agent codes (`AG-XXXXXX` format) and
  campaign tracking link builder.
- **REST API** (`gemz-crm/v1`): GET /leads, GET /partners, POST /leads
  (the endpoint the public appointment-booking funnel will submit to).
- **Notifications dispatcher**: logs every notification sent; email is
  wired via `wp_mail()`, WhatsApp is a stub (see below) waiting on a
  provider account.
- **Admin screens**: Dashboard, Partners (full add/edit/list), Leads
  (list), Agents (list w/ conversion rate), Reports (commission summary
  + partner outcomes), Audit Log, Settings (incl. guarded test-data
  reset — triple-guarded: capability check, nonce, and a confirmation
  checkbox, so it can never fire by accident).

## Deliberately stubbed / next steps

- **WhatsApp sending**: `GRC_Notifications::send()` has a stub that logs
  intent but doesn't actually send — needs a provider (Twilio or Meta
  Cloud API) hooked in via the `grc_send_whatsapp` filter once that
  account exists.
- **Public funnel front-end**: the landing-page/appointment-booking
  shortcode or block that customers actually see and submit through
  isn't built yet — the REST endpoint it will call (`POST /leads`) is
  ready.
- **Multi-level commission calculation**: the `commissions` table
  supports tiers (1 = direct agent, 2/3 = upline sponsors), but the
  actual split-calculation logic (who gets what % when a lead converts)
  isn't wired yet — needs the actual split percentages confirmed first.
- **Milestone templates per industry**: the `milestones` table is
  generic; industry-specific milestone sets (solar vs roofing) still
  need to be defined and auto-created per lead.
- **Payment info UI for agents**: agents table has a `payment_details`
  JSON field but there's no front-end form yet for agents to enter/edit
  Wise/PayPal/bank info themselves.
- **WordPress REST API + Application Password** for Claude to edit live
  pages directly: this is a WP-admin setup step (Users → Profile →
  Application Passwords), not custom code — flagged as a to-do for the
  terminal session.

## Install

1. Zip this folder (or the whole `gemz-referral-crm/` directory) and
   upload via Plugins → Add New → Upload Plugin, or push via Hostinger's
   Git deploy into `wp-content/plugins/gemz-referral-crm/`.
2. Activate. Tables are created automatically.
3. Go to Referral CRM → Partners and add your first fulfillment partner.
