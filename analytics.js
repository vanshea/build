(() => {
  "use strict";

  const CONSENT_KEY = "vsc-analytics-consent-v1";
  const USER_KEY = "vsc-analytics-user-id";
  const FIRST_TOUCH_KEY = "vsc-analytics-first-touch";
  const SESSION_KEY = "vsc-analytics-session-v1";

  const SCROLL_THRESHOLDS = [25, 50, 75, 90];
  const SESSION_IDLE_MS = 30 * 60 * 1000;
  const ALLOWED_CONTACT_METHODS = new Set(["email", "form", "phone"]);
  const ALLOWED_RESUME_LOCATIONS = new Set(["header", "footer", "case_study"]);

  const state = {
    config: {
      provider: "none",
      measurementId: "",
      gaEnabled: false,
      collectEnabled: false,
      excludeInternal: false,
      debug: false
    },
    consent: "unset",
    gaInitialized: false,
    dntEnabled: false,
    caseStudy: {
      slug: "",
      title: "",
      reachedPercent: 0,
      viewSent: false,
      sentDepths: new Set(),
      listenersBound: false
    }
  };

  function safeStorageGet(key) {
    try {
      return window.localStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function safeStorageSet(key, value) {
    try {
      window.localStorage.setItem(key, value);
    } catch (error) {
      // Ignore storage errors in restricted contexts.
    }
  }

  function safeSessionGet(key) {
    try {
      return window.sessionStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function safeSessionSet(key, value) {
    try {
      window.sessionStorage.setItem(key, value);
    } catch (error) {
      // Ignore storage errors in restricted contexts.
    }
  }

  function randomId(prefix, bytes = 12) {
    const array = new Uint8Array(bytes);
    window.crypto.getRandomValues(array);
    const token = Array.from(array)
      .map((item) => item.toString(16).padStart(2, "0"))
      .join("");
    return `${prefix}_${token}`;
  }

  function normalizePathname(pathname) {
    const normalized = String(pathname || "/").split("?")[0].split("#")[0] || "/";
    if (normalized.length > 1 && normalized.endsWith("/")) {
      return normalized.slice(0, -1);
    }
    return normalized;
  }

  function sanitizeText(value, maxLength = 96) {
    const compact = String(value || "").replace(/\s+/g, " ").trim();
    if (!compact) return "";
    return compact.slice(0, maxLength);
  }

  function sanitizeSlug(value) {
    return String(value || "")
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9-_]/g, "-")
      .replace(/-+/g, "-")
      .replace(/^-+|-+$/g, "")
      .slice(0, 80);
  }

  function sanitizeLocation(value) {
    const cleaned = String(value || "")
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9_]/g, "_")
      .replace(/_+/g, "_")
      .replace(/^_+|_+$/g, "");

    return cleaned || "unknown";
  }

  function sanitizeDomain(value) {
    const raw = String(value || "").trim().toLowerCase();
    if (!raw) return "";

    const host = raw
      .replace(/^https?:\/\//, "")
      .split("/")[0]
      .split(":")[0]
      .replace(/^www\./, "")
      .replace(/[^a-z0-9.-]/g, "")
      .slice(0, 120);

    return host;
  }

  function getCurrentPath() {
    return normalizePathname(window.location.pathname);
  }

  function getCaseStudySlugFromPath() {
    const pathname = getCurrentPath();
    const match = pathname.match(/^\/(?:livesite|comingsoon)\/case-studies\/([^/]+)$/i) ||
      pathname.match(/^\/case-studies\/([^/]+)$/i);
    if (!match) return "";
    return sanitizeSlug(decodeURIComponent(match[1]));
  }

  function getCaseStudyTitle() {
    const h1 = document.querySelector("main h1, article h1, h1");
    const h1Text = sanitizeText(h1?.textContent || "", 120);
    if (h1Text) return h1Text;

    const docTitle = sanitizeText(document.title, 120);
    if (!docTitle) return "Case Study";

    const split = docTitle.split("|")[0] || docTitle;
    return sanitizeText(split, 120) || "Case Study";
  }

  function getElementLocation(el) {
    if (!el) return "unknown";

    const explicit = sanitizeLocation(el.getAttribute("data-analytics-location"));
    if (explicit !== "unknown") return explicit;

    if (el.closest("header")) return "header";
    if (el.closest("footer")) return "footer";
    if (el.closest("#contact")) return "contact";
    if (el.closest("#experience")) return "experience";
    if (el.closest("#about")) return "about";
    if (el.closest("#work")) return "work";
    if (getCaseStudySlugFromPath()) return "case_study";

    return "unknown";
  }

  function getResumeLocation(el) {
    const explicit = sanitizeLocation(el?.getAttribute("data-analytics-location"));
    if (ALLOWED_RESUME_LOCATIONS.has(explicit)) {
      return explicit;
    }

    const inferred = getElementLocation(el);
    if (ALLOWED_RESUME_LOCATIONS.has(inferred)) {
      return inferred;
    }

    if (getCaseStudySlugFromPath()) {
      return "case_study";
    }

    return "";
  }

  function isResumeLink(link) {
    if (!link) return false;

    const analyticType = sanitizeText(link.getAttribute("data-analytics"), 48).toLowerCase();
    if (analyticType === "resume_download") return true;

    const rawHref = sanitizeText(link.getAttribute("href"), 300).toLowerCase();
    if (!rawHref) return false;

    if (/\.pdf($|[?#])/.test(rawHref)) return true;
    if (rawHref.includes("/resume")) return true;

    return false;
  }

  function toUrl(value) {
    try {
      return new URL(value, window.location.origin);
    } catch (error) {
      return null;
    }
  }

  function isSameHostname(urlObj) {
    return urlObj && urlObj.hostname === window.location.hostname;
  }

  function getOutboundDomain(urlObj) {
    if (!urlObj) return "";
    return sanitizeText(urlObj.hostname.replace(/^www\./i, "").toLowerCase(), 120);
  }

  function readConsentState() {
    const saved = safeStorageGet(CONSENT_KEY);
    if (saved === "granted" || saved === "denied") {
      return saved;
    }
    return "unset";
  }

  function setConsentState(nextState) {
    state.consent = nextState === "granted" ? "granted" : "denied";
    safeStorageSet(CONSENT_KEY, state.consent);
    updateConsentUi();
    syncConsentWithGa();
    syncCaseStudySignals();
  }

  function isDoNotTrackEnabled() {
    const dnt =
      navigator.doNotTrack ||
      window.doNotTrack ||
      navigator.msDoNotTrack ||
      "";
    return String(dnt) === "1";
  }

  function ensureDataLayer() {
    if (!Array.isArray(window.dataLayer)) {
      window.dataLayer = [];
    }

    if (typeof window.gtag !== "function") {
      window.gtag = function gtag() {
        window.dataLayer.push(arguments);
      };
    }
  }

  function initGaCore() {
    if (state.gaInitialized) return;
    if (!state.config.gaEnabled || !state.config.measurementId) return;

    ensureDataLayer();

    window.gtag("consent", "default", {
      analytics_storage: "denied",
      ad_storage: "denied",
      ad_user_data: "denied",
      ad_personalization: "denied",
      wait_for_update: 500
    });

    window.gtag("js", new Date());
    window.gtag("config", state.config.measurementId, {
      send_page_view: false,
      anonymize_ip: true,
      allow_google_signals: false,
      allow_ad_personalization_signals: false
    });

    state.gaInitialized = true;
  }

  function loadGaScript() {
    if (!state.config.measurementId) return;
    if (document.querySelector('script[data-analytics-loader="ga4"]')) return;

    const script = document.createElement("script");
    script.async = true;
    script.setAttribute("data-analytics-loader", "ga4");
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(
      state.config.measurementId
    )}`;
    document.head.appendChild(script);
  }

  function syncConsentWithGa() {
    if (!state.config.gaEnabled || !state.config.measurementId) return;

    initGaCore();

    if (state.consent === "granted" && !state.dntEnabled) {
      loadGaScript();
      window.gtag("consent", "update", {
        analytics_storage: "granted",
        ad_storage: "denied",
        ad_user_data: "denied",
        ad_personalization: "denied"
      });
      return;
    }

    window.gtag("consent", "update", {
      analytics_storage: "denied",
      ad_storage: "denied",
      ad_user_data: "denied",
      ad_personalization: "denied"
    });
  }

  function canCollect() {
    return (
      state.config.collectEnabled &&
      !state.config.excludeInternal &&
      !state.dntEnabled &&
      state.consent === "granted"
    );
  }

  function canTrackGa() {
    return (
      state.config.provider === "ga4" &&
      state.config.gaEnabled &&
      state.config.measurementId &&
      state.gaInitialized &&
      typeof window.gtag === "function" &&
      canCollect()
    );
  }

  function getOrCreateUserId() {
    const existing = sanitizeText(safeStorageGet(USER_KEY), 64);
    if (existing) return existing;

    const created = randomId("u", 10);
    safeStorageSet(USER_KEY, created);
    return created;
  }

  function getOrCreateSessionId() {
    const now = Date.now();
    const raw = safeSessionGet(SESSION_KEY);

    if (raw) {
      try {
        const parsed = JSON.parse(raw);
        if (
          parsed &&
          typeof parsed.id === "string" &&
          parsed.id &&
          typeof parsed.lastSeen === "number" &&
          now - parsed.lastSeen < SESSION_IDLE_MS
        ) {
          parsed.lastSeen = now;
          safeSessionSet(SESSION_KEY, JSON.stringify(parsed));
          return parsed.id;
        }
      } catch (error) {
        // Continue with a fresh session ID.
      }
    }

    const created = {
      id: randomId("s", 10),
      lastSeen: now
    };
    safeSessionSet(SESSION_KEY, JSON.stringify(created));
    return created.id;
  }

  function readAttributionContext() {
    const params = new URLSearchParams(window.location.search || "");
    const utmSource = sanitizeText(params.get("utm_source"), 64);
    const utmMedium = sanitizeText(params.get("utm_medium"), 64);
    const utmCampaign = sanitizeText(params.get("utm_campaign"), 120);

    const referrerUrl = sanitizeText(document.referrer, 300);
    let referrerDomain = "";

    if (referrerUrl) {
      const parsedReferrer = toUrl(referrerUrl);
      if (parsedReferrer && parsedReferrer.hostname !== window.location.hostname) {
        referrerDomain = sanitizeDomain(parsedReferrer.hostname);
      }
    }

    const sessionSource = utmSource || referrerDomain || "(direct)";
    const sessionMedium = utmMedium || (referrerDomain ? "referral" : "(none)");
    const sessionCampaign = utmCampaign || "(not set)";

    let firstTouch = null;
    const firstTouchRaw = safeStorageGet(FIRST_TOUCH_KEY);
    if (firstTouchRaw) {
      try {
        const parsed = JSON.parse(firstTouchRaw);
        if (parsed && parsed.source && parsed.medium) {
          firstTouch = parsed;
        }
      } catch (error) {
        // Ignore malformed first-touch state.
      }
    }

    if (!firstTouch) {
      firstTouch = {
        source: sessionSource,
        medium: sessionMedium,
        campaign: sessionCampaign
      };
      safeStorageSet(FIRST_TOUCH_KEY, JSON.stringify(firstTouch));
    }

    return {
      referrerDomain: referrerDomain || null,
      sessionSource,
      sessionMedium,
      sessionCampaign,
      firstTouchSource: sanitizeText(firstTouch.source, 64) || "(direct)",
      firstTouchMedium: sanitizeText(firstTouch.medium, 64) || "(none)",
      firstTouchCampaign: sanitizeText(firstTouch.campaign, 120) || "(not set)"
    };
  }

  function cleanParams(params) {
    const payload = {};

    Object.entries(params || {}).forEach(([key, value]) => {
      if (value === undefined || value === null) return;

      if (
        typeof value === "string" ||
        typeof value === "number" ||
        typeof value === "boolean"
      ) {
        payload[key] = value;
      }
    });

    if (state.config.debug) {
      payload.debug_mode = true;
    }

    return payload;
  }

  function sendCollectEvent(eventName, params) {
    if (!canCollect()) return false;

    const payload = {
      eventName,
      params: cleanParams(params),
      pagePath: getCurrentPath(),
      pageTitle: sanitizeText(document.title, 200),
      eventTime: new Date().toISOString(),
      userId: getOrCreateUserId(),
      sessionId: getOrCreateSessionId(),
      ...readAttributionContext()
    };

    fetch("/api/collect", {
      method: "POST",
      credentials: "same-origin",
      keepalive: true,
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload)
    }).catch(() => {
      // Keep UX unaffected when collection endpoint is unavailable.
    });

    return true;
  }

  function trackEvent(eventName, params = {}) {
    const normalizedParams = cleanParams(params);
    const collectSent = sendCollectEvent(eventName, normalizedParams);

    if (canTrackGa()) {
      window.gtag("event", eventName, normalizedParams);
    }

    return collectSent || canTrackGa();
  }

  function trackContactClick(method, location) {
    const cleanMethod = sanitizeText(method, 24).toLowerCase();
    if (!ALLOWED_CONTACT_METHODS.has(cleanMethod)) return false;

    return trackEvent("click_contact", {
      method: cleanMethod,
      location: sanitizeLocation(location)
    });
  }

  function trackContactFormSubmit(success) {
    return trackEvent("submit_contact_form", {
      success: Boolean(success)
    });
  }

  function trackCopyEmail(location) {
    return trackEvent("copy_email", {
      location: sanitizeLocation(location)
    });
  }

  function getReadableLinkText(link) {
    if (!link) return "link";
    const dataLabel = sanitizeText(link.getAttribute("data-analytics-label"), 80);
    if (dataLabel) return dataLabel;

    const ariaLabel = sanitizeText(link.getAttribute("aria-label"), 80);
    if (ariaLabel) return ariaLabel;

    const text = sanitizeText(link.textContent, 80);
    if (text) return text;

    return "link";
  }

  function bindGlobalLinkTracking() {
    document.addEventListener("click", (event) => {
      const link = event.target.closest("a[href]");
      if (!link) return;

      const href = sanitizeText(link.getAttribute("href"), 300);
      if (!href) return;

      if (href.startsWith("mailto:")) {
        trackContactClick("email", getElementLocation(link));
        return;
      }

      if (href.startsWith("tel:")) {
        trackContactClick("phone", getElementLocation(link));
        return;
      }

      if (isResumeLink(link)) {
        const location = getResumeLocation(link);
        if (location) {
          trackEvent("click_resume_download", { location });
        }
      }

      const urlObj = toUrl(href);
      if (!urlObj) return;
      if (!/^https?:$/i.test(urlObj.protocol)) return;
      if (isSameHostname(urlObj)) return;

      trackEvent("outbound_click", {
        destination_domain: getOutboundDomain(urlObj),
        link_text: getReadableLinkText(link),
        location: getElementLocation(link)
      });
    });
  }

  function bindCopyEmailTracking() {
    document.addEventListener("click", async (event) => {
      const trigger = event.target.closest('[data-analytics="copy_email"]');
      if (!trigger) return;

      const copyValue = sanitizeText(trigger.getAttribute("data-copy-value"), 160);
      if (copyValue && navigator.clipboard?.writeText) {
        try {
          await navigator.clipboard.writeText(copyValue);
        } catch (error) {
          // Keep tracking behavior even if clipboard write is blocked.
        }
      }

      trackCopyEmail(getElementLocation(trigger));
    });
  }

  function updateCaseStudyProgress() {
    if (!state.caseStudy.slug) return;

    const doc = document.documentElement;
    const scrollableHeight = Math.max(doc.scrollHeight - window.innerHeight, 0);
    const currentScroll = Math.max(window.scrollY, 0);
    const rawPercent =
      scrollableHeight > 0 ? Math.round((currentScroll / scrollableHeight) * 100) : 100;

    state.caseStudy.reachedPercent = Math.max(
      state.caseStudy.reachedPercent,
      Math.min(rawPercent, 100)
    );
  }

  function syncCaseStudySignals() {
    if (!state.caseStudy.slug) return;

    if (!state.caseStudy.viewSent) {
      const sent = trackEvent("view_case_study", {
        slug: state.caseStudy.slug,
        title: state.caseStudy.title || "Case Study"
      });

      if (sent) {
        state.caseStudy.viewSent = true;
      }
    }

    SCROLL_THRESHOLDS.forEach((threshold) => {
      if (state.caseStudy.reachedPercent < threshold) return;
      if (state.caseStudy.sentDepths.has(threshold)) return;

      const sent = trackEvent("scroll_depth", {
        slug: state.caseStudy.slug,
        percent: threshold
      });

      if (sent) {
        state.caseStudy.sentDepths.add(threshold);
      }
    });
  }

  function bindCaseStudyTracking() {
    const slug = getCaseStudySlugFromPath();
    if (!slug) return;

    state.caseStudy.slug = slug;
    state.caseStudy.title = getCaseStudyTitle();

    if (state.caseStudy.listenersBound) return;
    state.caseStudy.listenersBound = true;

    let rafId = 0;
    const onScroll = () => {
      if (rafId) return;
      rafId = window.requestAnimationFrame(() => {
        rafId = 0;
        updateCaseStudyProgress();
        syncCaseStudySignals();
      });
    };

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);

    updateCaseStudyProgress();
    syncCaseStudySignals();
  }

  function updateConsentUi() {
    const banner = document.getElementById("consentBanner");
    if (!banner) return;

    if (!state.config.collectEnabled || state.dntEnabled) {
      banner.hidden = true;
      return;
    }

    banner.hidden = state.consent !== "unset";
  }

  function bindConsentControls() {
    const allowBtn = document.getElementById("consentAllowBtn");
    const denyBtn = document.getElementById("consentDenyBtn");
    const manageBtn = document.getElementById("consentManageBtn");
    const banner = document.getElementById("consentBanner");

    if (allowBtn) {
      allowBtn.addEventListener("click", () => {
        setConsentState("granted");
      });
    }

    if (denyBtn) {
      denyBtn.addEventListener("click", () => {
        setConsentState("denied");
      });
    }

    if (manageBtn) {
      manageBtn.addEventListener("click", () => {
        if (!banner || !state.config.collectEnabled || state.dntEnabled) return;
        state.consent = "unset";
        updateConsentUi();
      });
    }
  }

  async function fetchAnalyticsConfig() {
    try {
      const response = await fetch("/api/analytics/config", { credentials: "same-origin" });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const json = await response.json();

      return {
        provider: sanitizeText(json?.provider, 16).toLowerCase() || "none",
        measurementId: sanitizeText(json?.measurementId, 32).toUpperCase(),
        gaEnabled: Boolean(json?.gaEnabled),
        collectEnabled: Boolean(json?.collectEnabled),
        excludeInternal: Boolean(json?.excludeInternal),
        debug: Boolean(json?.debug)
      };
    } catch (error) {
      return {
        provider: "none",
        measurementId: "",
        gaEnabled: false,
        collectEnabled: false,
        excludeInternal: false,
        debug: false
      };
    }
  }

  function exposePublicApi() {
    window.siteAnalytics = {
      trackEvent,
      getElementLocation,
      trackContactClick,
      trackContactFormSubmit,
      trackCopyEmail
    };
  }

  async function init() {
    exposePublicApi();
    bindGlobalLinkTracking();
    bindCopyEmailTracking();
    bindCaseStudyTracking();

    state.dntEnabled = isDoNotTrackEnabled();
    state.config = await fetchAnalyticsConfig();
    state.consent = readConsentState();

    bindConsentControls();
    updateConsentUi();

    if (!state.config.collectEnabled && !state.config.gaEnabled) return;

    initGaCore();
    syncConsentWithGa();
    syncCaseStudySignals();
  }

  init();
})();
