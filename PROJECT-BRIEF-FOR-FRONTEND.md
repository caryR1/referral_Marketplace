# Gemz Referral CRM — Front-End Project Brief

## What this is

Gemz is a referral/cashback marketing platform for home-services (and, newly, home-buying) categories: roofing, HVAC, solar, plumbing, remodeling, windows & doors, and tiny/modular homes. It connects homeowners to vetted local service providers and pays the homeowner cash back for booking a free consultation — and separately runs a multi-level referral/agent program so everyday people can earn income by referring others (and by recruiting other referrers).

Live site: **refer.gemzonline.com**

## The business model, in plain terms

1. A visitor picks a project type (e.g. "Solar") and enters their zip code.
2. They're matched to a vetted local company and book a free, no-obligation consultation.
3. The moment they book, they get real cash back — whether or not they proceed with the project.
4. Separately, anyone can become an "agent": they get a personal referral link, earn a commission when people they refer book, and can recruit other agents under them, earning a smaller commission on *their* referrals too, three tiers deep.

The cash-back economics are real, not a gimmick: referred leads convert at meaningfully higher rates than paid-ad leads and cost the service company far less to acquire, so there's real margin to share back with the person who made the referral.

## Two distinct audiences — design for both

1. **Homeowners** looking to book a free quote and get cash back. They need trust signals fast (no cost, no obligation, no catch) and a frictionless path from "what do I need" to "booked."
2. **Prospective agents** looking for a flexible side income through referrals and team-building. They need the earning model explained clearly and honestly (no guaranteed-income hype — it's effort-and-network-dependent) and a fast signup path.

## Brand identity

**Palette** (already established, feel free to propose evolution but this is the current system):
- Navy `#0B1220`, near-black `#05070C`, deep blue `#1E3A6E`
- Primary blue `#2F6FED` / darker blue `#2559C4`
- Purple accent `#7C3AED`
- Cyan glow accent `#4FD1F9`
- Red accent (sparing use, e.g. urgency/CTA chips) `#DC2626`
- Slate/gray text `#64748B`, light line `#E2E6EA`, off-white background `#F4F7FB`, white `#FFFFFF`

**Typography:** Sora (display/headlines, weights 600–800) paired with Inter (body, 400–700). Both via Google Fonts.

**Tone/mood:** Bright, clean, modern, almost futuristic — "polished and a little cinematic without being childish." In practice this has meant: dark navy-to-black gradient panels with a soft purple/cyan radial glow for hero moments and key decision points (a "what do you need" picker), contrasted against mostly-white/light functional sections (forms, content, listings). Glossy rounded-square icon badges (gradient fill + inner highlight sheen) rather than illustration or stock photography — **no AI-generated illustration or hand-drawn art**; if imagery is wanted, it should be real photography supplied by the client or licensed stock, not generated.

**Copy voice:** Direct, benefit-first, skepticism-aware (the offer sounds "too good to be true," so copy should proactively address that rather than oversell). Short, punchy headlines; no filler marketing-speak; concrete numbers and specifics over vague claims.

## Technical context (important constraints)

- **Platform:** WordPress, **Blocksy** theme (a classic/Customizer-based theme, not a block/FSE theme).
- **Custom plugin:** `gemz-referral-crm` provides the actual functionality via shortcodes dropped into normal WordPress page content:
  - `[gemz_industry_browser]` — the "pick your project + enter your zip" interactive funnel; fetches live offers from a REST endpoint.
  - `[gemz_appointment_form partner_id="N"]` — the booking form for a specific industry landing page (omit `partner_id` if no partner is set up yet for that industry — it degrades gracefully to blank for visitors).
  - `[gemz_agent_signup]` — self-serve agent registration (name/email/password, instant activation).
  - `[gemz_agent_dashboard]` — logged-in agent's referral code/stats view.
- Front-end deliverables should be **WordPress-page-compatible markup** (HTML/CSS, inline or via the theme's Additional CSS / a child theme stylesheet) that can sit inside a normal WP page alongside these shortcodes — not a separate SPA/framework build. JS should be vanilla or lightweight; the plugin already enqueues its own JS/CSS per-shortcode-presence.
- The plugin's current front-end CSS lives at `assets/css/gemz-brand.css`, `industry-browser.css`, `appointment-form.css`, `agent-portal.css` inside the plugin folder — these define the CSS custom-property tokens (`--gemz-blue`, `--gemz-navy`, etc.) already in use.

## Site structure (current — extend or restructure as needed)

- **Home** (`/`) — hero, industry picker, "how it works" (3 steps), agent-recruitment section with live signup form.
- **Industry landing pages**, one per vertical, each with a hero + `[gemz_appointment_form]`: Roofing, Solar, Windows & Doors, Tiny & Modular Homes (Roofing has a real fulfillment partner wired up; Solar/Windows & Doors/Tiny & Modular Homes don't yet, so their forms currently render blank to visitors until a partner is added — that's expected, not a bug).
- **Blog** (`/blog/`) — index of articles; currently 6 published covering how the referral model works, the economics case for referrals, how the agent program works, and category-specific educational content (e.g. roof-replacement warning signs, what tiny/modular homes are).
- **Agent-facing pages**: signup (embedded on Home currently) and a dashboard for logged-in agents (shortcode exists, no dedicated page built yet).

## What's explicitly open for Gemini to (re)design

- Overall page layout/structure and visual hierarchy — nothing here is meant to lock that down.
- The site currently has **no real header/nav visual design system** beyond a functional WordPress menu (Home / Roofing ▾ / Solar / Windows & Doors / Tiny & Modular Homes / Blog) — a proper header/footer design is genuinely undone.
- How the two audiences (homeowners vs. prospective agents) are separated or woven together across the site.
- Whether/how to incorporate real photography once available.

## What should NOT change without a good reason

- The core shortcode-driven functionality (booking form, industry picker, agent signup) — these are wired to a working backend; a redesign should restyle/rearrange them, not replace their function.
- The honest, no-guaranteed-income framing on anything agent-earnings-related (compliance-motivated, not just a style choice).
- The "offers are shown by industry/area, never by fulfillment-partner identity" principle — the public front-end never names which company will actually do the work; that's a deliberate privacy/business decision baked into the backend.
