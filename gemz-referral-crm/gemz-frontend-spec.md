:root {
  /* Brand Core Palette */
  --gemz-navy-dark: #05070C;
  --gemz-navy-base: #0B1220;
  --gemz-navy-light: #1E3A6E;
  
  --gemz-blue-primary: #2F6FED;
  --gemz-blue-hover: #2559C4;
  
  --gemz-purple-accent: #7C3AED;
  --gemz-cyan-accent: #4FD1F9;
  --gemz-red-accent: #DC2626;
  
  /* Neutral Palette */
  --gemz-bg-light: #F4F7FB;
  --gemz-card-light: #FFFFFF;
  --gemz-text-dark: #0B1220;
  --gemz-text-muted: #64748B;
  --gemz-border-light: #E2E6EA;
  
  /* Dark Glassmorphism & UI Tokens */
  --gemz-dark-card-bg: rgba(11, 18, 32, 0.75);
  --gemz-dark-card-border: rgba(79, 209, 249, 0.15);
  --gemz-glass-glow: radial-gradient(600px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(124, 58, 237, 0.15), rgba(79, 209, 249, 0.05) 40%, transparent 80%);
  
  /* Typography */
  --font-heading: 'Sora', -apple-system, BlinkMacSystemFont, sans-serif;
  --font-body: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  
  /* Spacing & Radii */
  --radius-sm: 8px;
  --radius-md: 14px;
  --radius-lg: 24px;
  --radius-pill: 9999px;
  
  --shadow-subtle: 0 4px 20px -2px rgba(11, 18, 32, 0.05);
  --shadow-floating: 0 20px 40px -15px rgba(11, 18, 32, 0.25);
  --shadow-glow: 0 0 30px -5px rgba(47, 111, 237, 0.4);
}

/* Base resets & typography bindings */
body {
  font-family: var(--font-body);
  color: var(--gemz-text-dark);
  background-color: var(--gemz-bg-light);
  line-height: 1.6;
}

h1, h2, h3, h4, h5, h6, .gemz-font-heading {
  font-family: var(--font-heading);
  font-weight: 700;
  letter-spacing: -0.02em;
}



Universal Navigation Header & Footer System
Replace the default theme header with this markup inside a Blocksy Custom Header Hook or global template.

Navigation Header Markup


<header class="gemz-header">
  <div class="gemz-container gemz-header-inner">
    <a href="/" class="gemz-brand-logo">
      <span class="gemz-logo-icon"></span>
      <span class="gemz-logo-text">GEMZ<span class="gemz-logo-dot">.</span></span>
    </a>

    <nav class="gemz-nav-menu">
      <div class="gemz-nav-item gemz-dropdown-wrapper">
        <button class="gemz-nav-link gemz-dropdown-toggle">
          Services <span class="gemz-dropdown-chevron">▼</span>
        </button>
        <div class="gemz-dropdown-menu">
          <a href="/roofing/" class="gemz-dropdown-item">
            <span class="gemz-icon-badge mini blue">🏠</span>
            <div><strong>Roofing</strong><span>Re-roofing & repairs</span></div>
          </a>
          <a href="/hvac/" class="gemz-dropdown-item">
            <span class="gemz-icon-badge mini cyan">❄️</span>
            <div><strong>HVAC</strong><span>Heating & cooling</span></div>
          </a>
          <a href="/solar/" class="gemz-dropdown-item">
            <span class="gemz-icon-badge mini purple">☀️</span>
            <div><strong>Solar Energy</strong><span>Panels & battery backup</span></div>
          </a>
          <a href="/plumbing/" class="gemz-dropdown-item">
            <span class="gemz-icon-badge mini blue">🚰</span>
            <div><strong>Plumbing</strong><span>Repairs & whole-home</span></div>
          </a>
          <a href="/remodeling/" class="gemz-dropdown-item">
            <span class="gemz-icon-badge mini cyan">🔨</span>
            <div><strong>Remodeling</strong><span>Kitchens & bathrooms</span></div>
          </a>
          <a href="/windows-doors/" class="gemz-dropdown-item">
            <span class="gemz-icon-badge mini purple">🪟</span>
            <div><strong>Windows & Doors</strong><span>Energy-efficient upgrades</span></div>
          </a>
          <a href="/tiny-modular-homes/" class="gemz-dropdown-item">
            <span class="gemz-icon-badge mini red">🏡</span>
            <div><strong>Tiny & Modular</strong><span>ADUs & custom builds</span></div>
          </a>
        </div>
      </div>

      <a href="/how-it-works/" class="gemz-nav-link">How Cash Back Works</a>
      <a href="/become-an-agent/" class="gemz-nav-link">Earn as an Agent</a>
      <a href="/blog/" class="gemz-nav-link">Blog</a>
    </nav>

    <div class="gemz-header-actions">
      <a href="/agent-portal/" class="gemz-btn gemz-btn-ghost">Agent Sign In</a>
      <a href="/#find-pro" class="gemz-btn gemz-btn-primary">Get Cash Back</a>
    </div>
  </div>
</header>


<footer class="gemz-footer">
  <div class="gemz-container">
    <div class="gemz-footer-grid">
      <div class="gemz-footer-col brand">
        <a href="/" class="gemz-brand-logo light">
          <span class="gemz-logo-icon"></span>
          <span class="gemz-logo-text">GEMZ<span class="gemz-logo-dot">.</span></span>
        </a>
        <p class="gemz-footer-desc">
          Connecting homeowners with top-rated local home improvement specialists—and paying real cash back just for taking a consultation.
        </p>
        <div class="gemz-trust-badge">
          <span class="gemz-shield-icon">🛡️</span> 100% Free & No-Obligation Guarantee
        </div>
      </div>

      <div class="gemz-footer-col">
        <h4>Home Services</h4>
        <ul>
          <li><a href="/roofing/">Roofing Replacement</a></li>
          <li><a href="/hvac/">HVAC & Air Quality</a></li>
          <li><a href="/solar/">Solar Power Installation</a></li>
          <li><a href="/plumbing/">Plumbing Solutions</a></li>
          <li><a href="/remodeling/">Home Remodeling</a></li>
          <li><a href="/windows-doors/">Windows & Exterior Doors</a></li>
          <li><a href="/tiny-modular-homes/">Tiny & Modular Homes</a></li>
        </ul>
      </div>

      <div class="gemz-footer-col">
        <h4>Partner Program</h4>
        <ul>
          <li><a href="/become-an-agent/">Become a Referral Agent</a></li>
          <li><a href="/agent-portal/">Agent Login & Portal</a></li>
          <li><a href="/how-commissions-work/">3-Tier Commission Model</a></li>
          <li><a href="/contractor-network/">Join as a Service Provider</a></li>
        </ul>
      </div>

      <div class="gemz-footer-col">
        <h4>Trust & Legal</h4>
        <ul>
          <li><a href="/privacy-policy/">Privacy Policy</a></li>
          <li><a href="/terms-of-service/">Terms of Service</a></li>
          <li><a href="/agent-earnings-disclaimer/">Earnings Disclaimer</a></li>
          <li><a href="/contact/">Support & Contact</a></li>
        </ul>
      </div>
    </div>
	
	
	Global Footer Markup

    <div class="gemz-footer-bottom">
      <p>© 2026 Gemz (refer.gemzonline.com). All rights reserved.</p>
      <p class="gemz-disclaimer-sm">
        Cash-back rewards are issued upon completion of a verified, eligible consultation with a network partner. Agent earnings are strictly based on successful customer bookings and sub-agent network activity. No guaranteed earnings are implied.
      </p>
    </div>
  </div>
</footer>



Homepage Template (/)
Copy and paste this structured layout block into a WordPress Custom HTML Block or Page Template for the main homepage (/).


<!-- HERO SECTION -->
<section class="gemz-hero-panel">
  <div class="gemz-container gemz-hero-grid">
    <div class="gemz-hero-content">
      <div class="gemz-chip-urgency">
        <span class="gemz-chip-dot"></span> Real Cash Back • Free Consultations
      </div>
      <h1 class="gemz-hero-title">
        Book a Local Pro. <br/>Get Paid <span class="gemz-text-gradient">Cash Back.</span>
      </h1>
      <p class="gemz-hero-subtitle">
        Compare vetted local specialists for your next home project. Get a 100% free, zero-obligation quote and claim your cash-back reward just for meeting with them.
      </p>

      <div class="gemz-trust-pills">
        <span class="gemz-pill">✓ No Credit Card Required</span>
        <span class="gemz-pill">✓ Vetted Local Pros</span>
        <span class="gemz-pill">✓ Instant Reward Eligibility</span>
      </div>
    </div>

    <!-- Interactive Industry Browser Shortcode Container -->
    <div class="gemz-hero-widget-card" id="find-pro">
      <div class="gemz-widget-header">
        <h3>Find Pros & Check Cash-Back Rewards</h3>
        <p>Select your category and zip code below:</p>
      </div>
      <div class="gemz-shortcode-embed">
        [gemz_industry_browser]
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS (HOMEOWNER) -->
<section class="gemz-section light">
  <div class="gemz-container">
    <div class="gemz-section-header center">
      <span class="gemz-section-subtitle">Frictionless & Transparent</span>
      <h2>How You Get Paid to Upgrade Your Home</h2>
      <p>No catches, no hidden fees, and no obligation to buy. Here is how our cash-back system works:</p>
    </div>

    <div class="gemz-grid-3">
      <div class="gemz-card-feature">
        <div class="gemz-icon-badge blue">1</div>
        <h3>1. Select Your Project</h3>
        <p>Choose your home service need—from roof replacements to solar setups—and enter your zip code to match with verified regional providers.</p>
      </div>

      <div class="gemz-card-feature">
        <div class="gemz-icon-badge purple">2</div>
        <h3>2. Book a Free Quote</h3>
        <p>Schedule a convenient, no-obligation consultation with a top-rated pro to assess your home and provide an exact estimate.</p>
      </div>

      <div class="gemz-card-feature">
        <div class="gemz-icon-badge cyan">3</div>
        <h3>3. Receive Your Cash Back</h3>
        <p>Once your consultation is verified, your cash reward is issued directly to you—whether or not you hire the pro.</p>
      </div>
    </div>
  </div>
</section>

<!-- SKEPTICISM-ADDRESSING COMPARISON SECTION -->
<section class="gemz-section white">
  <div class="gemz-container">
    <div class="gemz-skeptic-box">
      <div class="gemz-skeptic-header">
        <h2>"Sounds too good to be true. How can Gemz pay me for a free quote?"</h2>
        <p>We believe in full transparency. Here is the honest economics behind our model:</p>
      </div>

      <div class="gemz-comparison-table-wrapper">
        <table class="gemz-comparison-table">
          <thead>
            <tr>
              <th>Traditional Lead Ads (Google/TV)</th>
              <th>The Gemz Cash-Back Model</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Contractors pay massive advertising fees to search engines and ad networks just to get clicks.</td>
              <td>Contractors redirect their ad budget directly into customer cash-back rewards.</td>
            </tr>
            <tr>
              <td>High ad costs force contractors to mark up project estimates.</td>
              <td>Warm, direct consultations convert higher, lowering overall acquisition overhead.</td>
            </tr>
            <tr>
              <td>Homeowners receive zero benefit for spending time sitting through sales meetings.</td>
              <td>Homeowners get direct financial compensation for their valuable time and consideration.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- DUAL AUDIENCE SWITCH: AGENT RECRUITMENT -->
<section class="gemz-section dark-gradient">
  <div class="gemz-container gemz-split-grid">
    <div class="gemz-split-content">
      <div class="gemz-chip-urgency purple">
        <span>💰</span> Flexible Referral Income
      </div>
      <h2>Turn Your Local Network Into a 3-Tier Revenue Stream</h2>
      <p>
        Know homeowners planning roof replacements, solar builds, or remodels? Invite them to book through Gemz. They get free quotes and cash back—and you earn a direct commission on every completed booking.
      </p>

      <ul class="gemz-check-list">
        <li><strong>Tier 1 Direct Payouts:</strong> Earn cash every time someone uses your unique link.</li>
        <li><strong>Tier 2 & 3 Team Overrides:</strong> Build a sub-agent team and earn residual commissions on their referrals up to 3 levels deep.</li>
        <li><strong>Zero Cost to Join:</strong> 100% free signup, direct tracking dashboard, and instant link generation.</li>
      </ul>

      <div class="gemz-disclaimer-box">
        <span class="gemz-info-icon">ℹ️</span>
        <p><strong>Honest Earnings Policy:</strong> Agent income is based entirely on actual referred consultations and sales activity. We provide tools and tracking, but earnings depend on your personal effort and network outreach.</p>
      </div>
    </div>

    <div class="gemz-split-form-card">
      <div class="gemz-widget-header">
        <h3>Create Your Free Agent Account</h3>
        <p>Start sharing your referral link in under 2 minutes.</p>
      </div>
      <div class="gemz-shortcode-embed">
        [gemz_agent_signup]
      </div>
    </div>
  </div>
</section>


Use this template structure for /roofing/, /solar/, /windows-doors/, /tiny-modular-homes/, /hvac/, /plumbing/, and /remodeling/.

<!-- INDUSTRY HERO -->
<section class="gemz-hero-panel compact">
  <div class="gemz-container gemz-hero-grid">
    <div class="gemz-hero-content">
      <div class="gemz-breadcrumb">
        <a href="/">Home</a> / <a href="/services/">Services</a> / <span>Roofing</span>
      </div>
      <h1 class="gemz-hero-title">
        Vetted <span class="gemz-text-gradient">Roofing Specialists.</span> <br/>Guaranteed Cash Back.
      </h1>
      <p class="gemz-hero-subtitle">
        Need a roof replacement or emergency repair? Compare licensed, insured local roofing contractors, get a comprehensive free inspection, and claim your cash-back reward.
      </p>

      <div class="gemz-feature-badges-row">
        <div class="gemz-badge-item">
          <span class="gemz-icon-badge mini blue">🛡️</span>
          <span>Licensed & Insured</span>
        </div>
        <div class="gemz-badge-item">
          <span class="gemz-icon-badge mini purple">📋</span>
          <span>Free Roof Inspection</span>
        </div>
        <div class="gemz-badge-item">
          <span class="gemz-icon-badge mini cyan">💵</span>
          <span>Cash Back Verified</span>
        </div>
      </div>
    </div>

    <!-- BOOKING FORM SHORTCODE CONTAINER -->
    <div class="gemz-hero-widget-card">
      <div class="gemz-widget-header">
        <h3>Schedule Free Inspection</h3>
        <p>Enter your details to lock in your cash-back reward:</p>
      </div>
      <div class="gemz-shortcode-embed">
        <!-- Gracefully renders form if partner exists, or blank state gracefully handled by plugin -->
        [gemz_appointment_form partner_id="1"]
      </div>
    </div>
  </div>
</section>

<!-- INDUSTRY VALUE PROPS & TRUST -->
<section class="gemz-section light">
  <div class="gemz-container">
    <div class="gemz-section-header center">
      <h2>Why Book Your Roofing Consultation Through Gemz?</h2>
    </div>

    <div class="gemz-grid-3">
      <div class="gemz-card-white">
        <span class="gemz-icon-badge blue">🔍</span>
        <h3>Strictly Vetted Partners</h3>
        <p>We independently screen local contractors for active licensing, comprehensive liability insurance, and proven customer satisfaction ratings.</p>
      </div>
      <div class="gemz-card-white">
        <span class="gemz-icon-badge purple">🏷️</span>
        <h3>Fair Market Estimates</h3>
        <p>Receive detailed, line-item pricing on architectural shingles, metal roofing, or tile options with zero high-pressure sales tactics.</p>
      </div>
      <div class="gemz-card-white">
        <span class="gemz-icon-badge cyan">💳</span>
        <h3>No-Catch Reward Check</h3>
        <p>Your cash-back payout is confirmed automatically upon completion of your home consultation. No purchase contract required.</p>
      </div>
    </div>
  </div>
</section>



Agent Portal & Dashboard Layout (/agent-portal/)
Place this on the dedicated agent portal page for logged-in or registering agents.



<section class="gemz-hero-panel compact">
  <div class="gemz-container">
    <div class="gemz-section-header center dark">
      <span class="gemz-chip-urgency purple">Agent Partner Portal</span>
      <h1>Manage Your Referrals & Team Commissions</h1>
      <p>Track your active leads, copy your personal share links, and monitor your 3-tier earnings in real time.</p>
    </div>
  </div>
</section>

<section class="gemz-section light">
  <div class="gemz-container">
    <div class="gemz-dashboard-wrapper">
      <!-- Shortcode automatically switches between login state & full dashboard stats -->
      [gemz_agent_dashboard]
    </div>
  </div>
</section>


Complete CSS Architecture (gemz-theme-override.css)
Add this complete stylesheet directly into Blocksy Additional CSS or your theme stylesheet. It styles all raw HTML containers, layout grids, headers, footers, and shortcodes output by gemz-referral-crm.


/* ==========================================================================
   1. GLOBAL LAYOUT & CONTAINER CONTAINMENT
   ========================================================================== */
.gemz-container {
  width: 100%;
  max-width: 1240px;
  margin: 0 auto;
  padding: 0 24px;
  box-sizing: border-box;
}

.gemz-section {
  padding: 80px 0;
  position: relative;
}

.gemz-section.light { background-color: var(--gemz-bg-light); }
.gemz-section.white { background-color: var(--gemz-card-light); }
.gemz-section.dark-gradient {
  background: linear-gradient(135deg, var(--gemz-navy-dark) 0%, var(--gemz-navy-base) 100%);
  color: #FFFFFF;
}

/* Grids */
.gemz-grid-3 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 32px;
  margin-top: 48px;
}

.gemz-split-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 60px;
  align-items: center;
}

@media (max-width: 992px) {
  .gemz-split-grid { grid-template-columns: 1fr; gap: 40px; }
}

/* ==========================================================================
   2. HEADER & NAVIGATION SYSTEM
   ========================================================================== */
.gemz-header {
  background-color: var(--gemz-navy-base);
  border-bottom: 1px solid rgba(226, 230, 234, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
  padding: 16px 0;
}

.gemz-header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.gemz-brand-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}

.gemz-logo-icon {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, var(--gemz-blue-primary), var(--gemz-purple-accent));
  border-radius: var(--radius-sm);
  box-shadow: inset 0 1px 1px rgba(255,255,255,0.4);
}

.gemz-logo-text {
  font-family: var(--font-heading);
  font-size: 24px;
  font-weight: 800;
  color: #FFFFFF;
  letter-spacing: -0.03em;
}

.gemz-logo-dot { color: var(--gemz-cyan-accent); }

.gemz-nav-menu {
  display: flex;
  align-items: center;
  gap: 28px;
}

.gemz-nav-link {
  color: #CBD5E1;
  text-decoration: none;
  font-weight: 500;
  font-size: 15px;
  transition: color 0.2s ease;
}

.gemz-nav-link:hover { color: var(--gemz-cyan-accent); }

/* Dropdown */
.gemz-dropdown-wrapper { position: relative; }
.gemz-dropdown-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  font-family: inherit;
}
.gemz-dropdown-chevron { font-size: 10px; margin-left: 4px; }

.gemz-dropdown-menu {
  position: absolute;
  top: 100%;
  left: -20px;
  width: 290px;
  background: var(--gemz-navy-base);
  border: 1px solid var(--gemz-dark-card-border);
  border-radius: var(--radius-md);
  padding: 12px;
  box-shadow: var(--shadow-floating);
  display: none;
  flex-direction: column;
  gap: 4px;
  margin-top: 12px;
}

.gemz-dropdown-wrapper:hover .gemz-dropdown-menu { display: flex; }

.gemz-dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  text-decoration: none;
  color: #FFFFFF;
  border-radius: var(--radius-sm);
  transition: background 0.2s ease;
}

.gemz-dropdown-item:hover { background: rgba(255, 255, 255, 0.06); }
.gemz-dropdown-item strong { display: block; font-size: 14px; }
.gemz-dropdown-item span { font-size: 12px; color: var(--gemz-text-muted); }

.gemz-header-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

/* ==========================================================================
   3. HERO PANELS & CARDS
   ========================================================================== */
.gemz-hero-panel {
  background: radial-gradient(circle at 80% 20%, var(--gemz-navy-light) 0%, var(--gemz-navy-dark) 70%);
  color: #FFFFFF;
  padding: 90px 0 100px;
  position: relative;
  overflow: hidden;
}

.gemz-hero-panel.compact { padding: 60px 0 70px; }

.gemz-hero-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 50px;
  align-items: center;
}

@media (max-width: 992px) {
  .gemz-hero-grid { grid-template-columns: 1fr; }
}

.gemz-hero-title {
  font-size: 52px;
  line-height: 1.1;
  margin: 16px 0 24px;
  color: #FFFFFF;
}

@media (max-width: 768px) {
  .gemz-hero-title { font-size: 36px; }
}

.gemz-text-gradient {
  background: linear-gradient(135deg, var(--gemz-cyan-accent), var(--gemz-blue-primary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.gemz-hero-subtitle {
  font-size: 18px;
  color: #94A3B8;
  max-width: 560px;
  margin-bottom: 32px;
}

/* Glassmorphism Card Wrapper for Shortcodes */
.gemz-hero-widget-card, .gemz-split-form-card {
  background: var(--gemz-dark-card-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--gemz-dark-card-border);
  border-radius: var(--radius-lg);
  padding: 32px;
  box-shadow: var(--shadow-floating);
}

.gemz-widget-header {
  margin-bottom: 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 16px;
}

.gemz-widget-header h3 {
  font-size: 20px;
  color: #FFFFFF;
  margin: 0 0 6px 0;
}

.gemz-widget-header p {
  font-size: 14px;
  color: var(--gemz-text-muted);
  margin: 0;
}

/* ==========================================================================
   4. BUTTONS, BADGES & ICON GLOSS
   ========================================================================== */
.gemz-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 24px;
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 15px;
  border-radius: var(--radius-pill);
  text-decoration: none;
  transition: all 0.25s ease;
  cursor: pointer;
  border: none;
}

.gemz-btn-primary {
  background: linear-gradient(135deg, var(--gemz-blue-primary), var(--gemz-blue-hover));
  color: #FFFFFF;
  box-shadow: var(--shadow-glow);
}

.gemz-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 35px rgba(47, 111, 237, 0.6);
}

.gemz-btn-ghost {
  background: transparent;
  color: #FFFFFF;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.gemz-btn-ghost:hover {
  border-color: var(--gemz-cyan-accent);
  color: var(--gemz-cyan-accent);
}

/* Icon Badges (Glossy Rounded Square) */
.gemz-icon-badge {
  width: 54px;
  height: 54px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  font-weight: 700;
  color: #FFFFFF;
  position: relative;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.5), var(--shadow-subtle);
  margin-bottom: 20px;
}

.gemz-icon-badge.mini {
  width: 36px;
  height: 36px;
  font-size: 16px;
  margin-bottom: 0;
  border-radius: var(--radius-sm);
}

.gemz-icon-badge.blue { background: linear-gradient(135deg, var(--gemz-blue-primary), #1E40AF); }
.gemz-icon-badge.purple { background: linear-gradient(135deg, var(--gemz-purple-accent), #5B21B6); }
.gemz-icon-badge.cyan { background: linear-gradient(135deg, var(--gemz-cyan-accent), #0284C7); }
.gemz-icon-badge.red { background: linear-gradient(135deg, var(--gemz-red-accent), #991B1B); }

/* Urgency & Trust Chips */
.gemz-chip-urgency {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 16px;
  background: rgba(47, 111, 237, 0.12);
  border: 1px solid rgba(79, 209, 249, 0.3);
  border-radius: var(--radius-pill);
  font-size: 13px;
  font-weight: 600;
  color: var(--gemz-cyan-accent);
}

.gemz-chip-urgency.purple {
  background: rgba(124, 58, 237, 0.15);
  border-color: rgba(124, 58, 237, 0.4);
  color: #DDD6FE;
}

.gemz-chip-dot {
  width: 8px;
  height: 8px;
  background-color: var(--gemz-cyan-accent);
  border-radius: 50%;
  box-shadow: 0 0 10px var(--gemz-cyan-accent);
}

/* ==========================================================================
   5. CARDS, TABLES & CONTENT BLOCKS
   ========================================================================== */
.gemz-card-feature, .gemz-card-white {
  background: var(--gemz-card-light);
  border: 1px solid var(--gemz-border-light);
  border-radius: var(--radius-md);
  padding: 36px 28px;
  box-shadow: var(--shadow-subtle);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.gemz-card-feature:hover, .gemz-card-white:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-floating);
}

.gemz-card-feature h3, .gemz-card-white h3 {
  font-size: 20px;
  margin: 0 0 12px 0;
  color: var(--gemz-navy-base);
}

.gemz-card-feature p, .gemz-card-white p {
  color: var(--gemz-text-muted);
  font-size: 15px;
  margin: 0;
}

/* Skepticism Comparison Table */
.gemz-skeptic-box {
  background: var(--gemz-bg-light);
  border: 1px solid var(--gemz-border-light);
  border-radius: var(--radius-lg);
  padding: 48px;
}

.gemz-skeptic-header {
  text-align: center;
  max-width: 720px;
  margin: 0 auto 36px;
}

.gemz-comparison-table-wrapper { overflow-x: auto; }

.gemz-comparison-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--gemz-card-light);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-subtle);
}

.gemz-comparison-table th {
  background: var(--gemz-navy-base);
  color: #FFFFFF;
  font-family: var(--font-heading);
  padding: 18px 24px;
  text-align: left;
  font-size: 15px;
  width: 50%;
}

.gemz-comparison-table td {
  padding: 20px 24px;
  border-bottom: 1px solid var(--gemz-border-light);
  font-size: 14px;
  color: #334155;
  vertical-align: top;
}

.gemz-comparison-table td:last-child {
  background: rgba(47, 111, 237, 0.03);
  font-weight: 500;
  color: var(--gemz-navy-base);
}

/* Legal/Honesty Disclaimer Box */
.gemz-disclaimer-box {
  display: flex;
  gap: 14px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--radius-md);
  padding: 16px 20px;
  margin-top: 28px;
}

.gemz-disclaimer-box p {
  font-size: 13px;
  color: #94A3B8;
  margin: 0;
  line-height: 1.5;
}

/* ==========================================================================
   6. SHORTCODE OVERRIDES (FOR GEMZ-REFERRAL-CRM PLUGIN OUTPUT)
   ========================================================================== */
/* Form Inputs */
.gemz-shortcode-embed input[type="text"],
.gemz-shortcode-embed input[type="email"],
.gemz-shortcode-embed input[type="password"],
.gemz-shortcode-embed select {
  width: 100%;
  height: 48px;
  background: rgba(11, 18, 32, 0.6);
  border: 1px solid rgba(226, 230, 234, 0.2);
  border-radius: var(--radius-sm);
  padding: 0 16px;
  color: #FFFFFF;
  font-family: var(--font-body);
  font-size: 15px;
  box-sizing: border-box;
  margin-bottom: 16px;
  transition: border-color 0.2s ease;
}

.gemz-shortcode-embed input:focus,
.gemz-shortcode-embed select:focus {
  outline: none;
  border-color: var(--gemz-cyan-accent);
  box-shadow: 0 0 10px rgba(79, 209, 249, 0.2);
}

.gemz-shortcode-embed button,
.gemz-shortcode-embed input[type="submit"] {
  width: 100%;
  height: 50px;
  background: linear-gradient(135deg, var(--gemz-blue-primary), var(--gemz-blue-hover));
  color: #FFFFFF;
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 16px;
  border: none;
  border-radius: var(--radius-pill);
  cursor: pointer;
  transition: all 0.25s ease;
  box-shadow: var(--shadow-glow);
}

.gemz-shortcode-embed button:hover,
.gemz-shortcode-embed input[type="submit"]:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 35px rgba(47, 111, 237, 0.6);
}

/* Light Theme Overrides for Dashboard Embeds */
.gemz-section.light .gemz-shortcode-embed input,
.gemz-section.light .gemz-shortcode-embed select {
  background: #FFFFFF;
  border-color: var(--gemz-border-light);
  color: var(--gemz-text-dark);
}

/* ==========================================================================
   7. FOOTER STYLING
   ========================================================================== */
.gemz-footer {
  background-color: var(--gemz-navy-dark);
  color: #94A3B8;
  padding: 80px 0 36px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.gemz-footer-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr 1fr;
  gap: 48px;
  margin-bottom: 60px;
}

@media (max-width: 992px) {
  .gemz-footer-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 576px) {
  .gemz-footer-grid { grid-template-columns: 1fr; }
}

.gemz-footer-col h4 {
  color: #FFFFFF;
  font-size: 16px;
  margin: 0 0 20px 0;
}

.gemz-footer-col ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.gemz-footer-col li { margin-bottom: 12px; }

.gemz-footer-col a {
  color: #94A3B8;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.2s ease;
}

.gemz-footer-col a:hover { color: var(--gemz-cyan-accent); }

.gemz-footer-desc {
  font-size: 14px;
  line-height: 1.6;
  margin: 20px 0;
  max-width: 320px;
}

.gemz-footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 32px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  font-size: 13px;
}

.gemz-disclaimer-sm {
  font-size: 12px;
  color: #64748B;
  line-height: 1.5;
}


Strategic Recommendations
Shortcode Fallback Handling: Your blank-slate handling for verticals without active fulfillment partners (Solar, Windows & Doors, Tiny Homes) works cleanly as designed. For non-partner pages, wrap the shortcode in a subtle UI notification pill: "Checking partner coverage in your area..." so visitors know the system is actively expanding rather than encountering an unexpected gap.

Mobile Menu Drawer: Ensure your Blocksy mobile drawer menu is mapped to use the identical dropdown hierarchy (Services vs. Partner Program) so mobile navigation remains clear.Strategic Recommendations
Shortcode Fallback Handling: Your blank-slate handling for verticals without active fulfillment partners (Solar, Windows & Doors, Tiny Homes) works cleanly as designed. For non-partner pages, wrap the shortcode in a subtle UI notification pill: "Checking partner coverage in your area..." so visitors know the system is actively expanding rather than encountering an unexpected gap.

Mobile Menu Drawer: Ensure your Blocksy mobile drawer menu is mapped to use the identical dropdown hierarchy (Services vs. Partner Program) so mobile navigation remains clear.



