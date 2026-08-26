# Gemz Referral CRM

Custom WordPress plugin powering refer.gemzonline.com — a referral/cashback
platform connecting customers to roofing, HVAC, solar, plumbing, remodeling,
and windows & doors fulfillment partners, with a multi-level agent commission
system.

## What's built

- **Database schema** (`includes/class-grc-activator.php`): partners,
  agents, campaigns, leads, milestones, commissions, notifications_log,
  audit_log, email_templates. Auto-upgrades on version bump (edit code,
  push, it re-syncs the schema on next page load — no manual reactivate
  needed).
- **Roles**: a `grc_agent` role for referrers, plus plugin capabilities
  granted to Administrator.
- **Referral codes**: unique per-agent codes (`AG-XXXXXX` format) and
  campaign tracking link builder.
- **REST API** (`gemz-crm/v1`): GET/POST /leads, GET /partners, GET
  /coverage-search (public industry/area browser), POST /agents/register
  (public self-signup).
- **Public front-end** (`public/class-grc-public.php` + shortcodes):
  `[gemz_appointment_form partner_id="N"]` booking form, `[gemz_industry_browser]`
  industry+location offer finder, agent portal/signup shortcodes. All wired
  to the REST API above.
- **Notifications dispatcher**: logs every notification sent. Email is
  wired via `wp_mail()`. WhatsApp sends through the `grc_send_whatsapp`
  filter — a built-in Twilio implementation is wired at priority 10
  (`GRC_Notifications::maybe_send_whatsapp_via_twilio()`) but stays inert
  until Referral CRM → Settings has a Twilio SID/token/from-number saved.
- **Multi-level commission calculation**: fires on `grc_lead_marked_completed`,
  walks the agent's sponsor chain up to 3 tiers, and creates commission rows
  for each tier present. Split percentages are configurable at Referral CRM
  → Settings (`grc_commission_split` option), defaulting to 70/20/10.
  Percentage/tiered partner payout types aren't handled yet — flat only.
- **Partner service areas**: Partners screen has a repeatable state/city/zip
  UI backed by the `service_areas` JSON column; `GRC_Coverage::search()`
  matches on zip, then city+state, then state-only.
- **Admin screens**: Dashboard, Partners (add/edit/list + service areas),
  Campaigns, Leads, Agents, Reports, Settings (commission split, WhatsApp
  provider, guarded test-data reset), Email Templates, Notification Log,
  Audit Log.

## Deliberately stubbed / next steps

- **Milestone templates per industry**: the `milestones` table is generic;
  industry-specific milestone sets (solar vs roofing vs windows & doors)
  still need to be defined and auto-created per lead.
- **Agent payment info UI**: agents table has a `payment_details` JSON
  field but there's no front-end form yet for agents to enter/edit
  Wise/PayPal/bank info themselves.
- **Landing pages per industry**: campaigns reference a `landing_page_id`
  (an ordinary WP page containing `[gemz_appointment_form]`), but building
  those pages is a WP-admin/content task, not plugin code. Solar and
  Windows & Doors still need pages cloned from the Roofing template.
- **WordPress REST API + Application Password** for Claude to edit live
  pages directly: this is a WP-admin setup step (Users → Profile →
  Application Passwords), not custom code — still a to-do.

## Install

1. Zip this folder (or the whole `gemz-referral-crm/` directory) and
   upload via Plugins → Add New → Upload Plugin, or push via Hostinger's
   Git deploy into `wp-content/plugins/gemz-referral-crm/`.
2. Activate. Tables are created automatically.
3. Go to Referral CRM → Partners and add your first fulfillment partner,
   including at least one service area — partners with no service areas
   never show up in the public industry browser.
4. Go to Referral CRM → Settings to confirm the commission split and, if
   you have a Twilio account, wire up WhatsApp.
