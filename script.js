const yearEl = document.getElementById("year");
if (yearEl) {
  yearEl.textContent = new Date().getFullYear();
}

const footerAnimationMeta = document.getElementById("footerAnimationMeta");
const siteFooter = document.getElementById("siteFooter");

function placeFooterAnimationMeta() {
  if (!footerAnimationMeta || !siteFooter) return;

  const footerRect = siteFooter.getBoundingClientRect();
  const metaRect = footerAnimationMeta.getBoundingClientRect();
  const edgePadding = 24;
  const reservedBottom = 18;
  const maxX = Math.max(edgePadding, footerRect.width - metaRect.width - edgePadding);
  const maxY = Math.max(edgePadding, footerRect.height - metaRect.height - reservedBottom);
  const x = edgePadding + Math.random() * Math.max(1, maxX - edgePadding);
  const y = edgePadding + Math.random() * Math.max(1, maxY - edgePadding);

  footerAnimationMeta.style.setProperty("--footer-meta-x", `${x.toFixed(1)}px`);
  footerAnimationMeta.style.setProperty("--footer-meta-y", `${y.toFixed(1)}px`);
}

function formatGenerationCount(count) {
  const value = Number(count) || 0;
  return `${value} ${value === 1 ? "time" : "times"}`;
}

async function syncFooterAnimationMeta() {
  if (!footerAnimationMeta) return;

  try {
    const response = await fetch("assets/footer-animation-log.json", {
      cache: "no-store"
    });
    if (!response.ok) return;

    const log = await response.json();
    const generatedCount = formatGenerationCount(log.generationCount);
    const generatedAt = log.lastGeneratedDisplay || log.lastGeneratedAt;
    if (!generatedAt) return;

    footerAnimationMeta.textContent = `Animation generated ${generatedCount} · ${generatedAt}`;
    placeFooterAnimationMeta();
  } catch (error) {
    // Keep the static fallback text if the log cannot be read.
    placeFooterAnimationMeta();
  }
}

syncFooterAnimationMeta();
window.addEventListener("load", placeFooterAnimationMeta);

const rootEl = document.documentElement;
const themeButtons = document.querySelectorAll(".theme-link");
const themeStorageKey = "vsc-site-theme-v2";
const availableThemes = new Set(["theme1", "theme2", "theme3", "theme4"]);
const brandLogoLightImage = document.getElementById("brandLogoLightImage");
const brandLogoDarkSource = document.getElementById("brandLogoDarkSource");

if (brandLogoLightImage) {
  brandLogoLightImage.dataset.logoLight =
    brandLogoLightImage.getAttribute("src") || brandLogoLightImage.src;

  if (brandLogoDarkSource?.getAttribute("srcset")) {
    brandLogoLightImage.dataset.logoDark = brandLogoDarkSource.getAttribute("srcset");
  }
}

function isDarkBackgroundTheme(theme) {
  if (theme === "theme4") return false;
  return theme === "theme3" || window.matchMedia("(prefers-color-scheme: dark)").matches;
}

function updateBrandLogoForTheme(theme) {
  if (!brandLogoLightImage) return;

  const lightLogo = brandLogoLightImage.dataset.logoLight || brandLogoLightImage.src;
  const darkLogo =
    brandLogoLightImage.dataset.logoDark ||
    brandLogoDarkSource?.getAttribute("srcset") ||
    lightLogo;
  brandLogoLightImage.src = isDarkBackgroundTheme(theme) ? darkLogo : lightLogo;
}

function setBrandLogoAssets(lightLogo, darkLogo) {
  if (!brandLogoLightImage) return;

  if (lightLogo) {
    brandLogoLightImage.dataset.logoLight = lightLogo;
  }

  if (darkLogo) {
    brandLogoLightImage.dataset.logoDark = darkLogo;

    if (brandLogoDarkSource) {
      brandLogoDarkSource.srcset = darkLogo;
    }
  }

  updateBrandLogoForTheme(rootEl.dataset.theme);
}

function applyTheme(theme) {
  const resolvedTheme = availableThemes.has(theme) ? theme : "theme2";
  rootEl.dataset.theme = resolvedTheme;

  themeButtons.forEach((button) => {
    const isActive = button.dataset.theme === resolvedTheme;
    button.classList.toggle("is-active", isActive);
    button.setAttribute("aria-pressed", isActive ? "true" : "false");
  });

  updateBrandLogoForTheme(resolvedTheme);
}

let initialTheme = "theme4";
try {
  const savedTheme = localStorage.getItem(themeStorageKey);
  if (savedTheme && availableThemes.has(savedTheme)) {
    initialTheme = savedTheme;
  }
} catch (error) {
  // Ignore storage access errors and fall back to default theme.
}

applyTheme(initialTheme);

themeButtons.forEach((button) => {
  button.addEventListener("click", () => {
    const selectedTheme = button.dataset.theme;
    if (!availableThemes.has(selectedTheme)) return;

    applyTheme(selectedTheme);

    try {
      localStorage.setItem(themeStorageKey, selectedTheme);
    } catch (error) {
      // Ignore storage write errors in restricted browsing contexts.
    }
  });
});

const footerPatternHosts = document.querySelectorAll("[data-footer-pattern]");

function buildFooterPattern(patternHost) {
  const patternLayer = patternHost.querySelector(".footer-pattern-layer");
  if (!patternLayer) return;

  const width = 1600;
  const height = 1200;
  const rand = (min, max) => min + Math.random() * (max - min);
  const patternCircles = [];

  for (let i = 0; i < 38; i += 1) {
    const radius = rand(8, 24);
    const white = Math.round(rand(214, 255));
    const fill = `rgb(${white}, ${white}, ${white})`;
    const isBright = i % 7 === 0;
    patternCircles.push(`
      <circle
        class="footer-pattern-circle${isBright ? " is-bright" : ""}"
        cx="${rand(radius, width - radius).toFixed(1)}"
        cy="${rand(radius, height - radius).toFixed(1)}"
        r="${radius.toFixed(1)}"
        fill="${fill}"
        stroke="${fill}"
        stroke-width="${rand(isBright ? 10 : 5, isBright ? 24 : 14).toFixed(1)}"
        stroke-opacity="${isBright ? "0.5" : "0.26"}"
        opacity="${rand(isBright ? 0.56 : 0.28, isBright ? 0.86 : 0.58).toFixed(2)}"
        filter="url(#footerPatternGlow)"
        style="--particle-delay: ${rand(0, 320).toFixed(0)}ms; --particle-drift-x: ${rand(-180, 180).toFixed(1)}px; --particle-drift-y: ${rand(-160, 160).toFixed(1)}px;"
      />
    `);
  }

  patternLayer.innerHTML = `
    <svg class="footer-pattern-svg" viewBox="0 0 ${width} ${height}" role="img" focusable="false" preserveAspectRatio="xMidYMid slice">
      <defs>
        <filter id="footerPatternGlow" x="-90%" y="-90%" width="280%" height="280%" color-interpolation-filters="sRGB">
          <feGaussianBlur in="SourceAlpha" stdDeviation="12" result="softGlow"/>
          <feColorMatrix in="softGlow" type="matrix" values="1.15 0 0 0 0  0 1.15 0 0 0  0 0 1.15 0 0  0 0 0 0.62 0" result="whiteGlow"/>
          <feMerge>
            <feMergeNode in="whiteGlow"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>
      </defs>
      <rect width="${width}" height="${height}" fill="none"/>
      <g class="footer-pattern-field">${patternCircles.join("")}</g>
    </svg>
  `;

  const circles = Array.from(patternLayer.querySelectorAll(".footer-pattern-circle"));
  const artTrigger = patternHost.querySelector("[data-footer-art-trigger]");
  const effectLayer = document.createElement("div");
  effectLayer.className = "footer-click-effect-layer";
  effectLayer.setAttribute("aria-hidden", "true");
  patternHost.appendChild(effectLayer);
  const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
  const piOptions = ["π", "π ≈ 3.14159", "C = 2πr", "A = πr²"];
  const createFooterClickEffect = (event) => {
    if (!artTrigger || !effectLayer) return;

    const footerRect = patternHost.getBoundingClientRect();
    const artRect = artTrigger.getBoundingClientRect();
    const circleCount = 3 + Math.floor(Math.random() * 3);
    const piIndex = Math.floor(Math.random() * circleCount);
    const baseX = typeof event?.clientX === "number" ? event.clientX : artRect.left + artRect.width / 2;
    const baseY = typeof event?.clientY === "number" ? event.clientY : artRect.top + artRect.height / 2;
    const smallestArtSide = Math.max(1, Math.min(artRect.width, artRect.height));

    for (let i = 0; i < circleCount; i += 1) {
      const circle = document.createElement("span");
      const size = rand(Math.max(34, smallestArtSide * 0.08), Math.max(68, smallestArtSide * 0.26));
      const x = clamp(
        baseX + rand(-artRect.width * 0.34, artRect.width * 0.34) - footerRect.left,
        size / 2,
        footerRect.width - size / 2
      );
      const y = clamp(
        baseY + rand(-artRect.height * 0.34, artRect.height * 0.34) - footerRect.top,
        size / 2,
        footerRect.height - size / 2
      );

      circle.className = "footer-click-circle";
      circle.style.left = `${x.toFixed(1)}px`;
      circle.style.top = `${y.toFixed(1)}px`;
      circle.style.setProperty("--circle-size", `${size.toFixed(1)}px`);
      circle.style.setProperty("--circle-stroke", `${rand(1.2, 2.8).toFixed(1)}px`);
      circle.style.setProperty("--circle-opacity", `${rand(0.22, 0.54).toFixed(2)}`);
      circle.style.setProperty("--pi-size", `${rand(0.68, 0.92).toFixed(2)}rem`);
      circle.style.animationDuration = `${rand(2600, 3000).toFixed(0)}ms`;

      if (i === piIndex) {
        const piText = document.createElement("span");
        piText.className = "footer-click-pi";
        piText.textContent = piOptions[Math.floor(Math.random() * piOptions.length)];
        circle.appendChild(piText);
      }

      const removeCircle = () => circle.remove();
      circle.addEventListener("animationend", removeCircle, { once: true });
      window.setTimeout(removeCircle, 3100);
      effectLayer.appendChild(circle);
    }
  };
  const movePatternCircles = () => {
    circles.forEach((circle) => {
      const radius = Number(circle.getAttribute("r")) || 12;
      circle.setAttribute("cx", rand(radius, width - radius).toFixed(1));
      circle.setAttribute("cy", rand(radius, height - radius).toFixed(1));
    });
  };

  circles.forEach((circle) => {
    circle.addEventListener("pointerenter", movePatternCircles);
  });

  if (artTrigger) {
    artTrigger.addEventListener("click", createFooterClickEffect);
    artTrigger.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      createFooterClickEffect();
    });
  }
}

if (footerPatternHosts.length) {
  footerPatternHosts.forEach(buildFooterPattern);

  if ("IntersectionObserver" in window) {
    const footerArtObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.2 }
    );

    footerPatternHosts.forEach((footer) => footerArtObserver.observe(footer));
  } else {
    footerPatternHosts.forEach((footer) => footer.classList.add("is-visible"));
  }
}

const experienceResumeSection = document.getElementById("experienceResumeSection");
const copyExperienceTextButton = document.getElementById("copyExperienceText");
const workGrid = document.querySelector("#work .grid");
const siteHeader = document.querySelector(".site-header");
const navToggle = document.querySelector(".nav-toggle");
const siteNav = document.querySelector(".nav");
const mobileNavMedia = window.matchMedia("(max-width: 820px)");
const recommendationsTrack = document.querySelector(".recommendations-track");
const recommendationsPrevButton = document.querySelector(".recommendations-nav-prev");
const recommendationsNextButton = document.querySelector(".recommendations-nav-next");
const FPO_ASSET_PATTERN = /(^|\/)assets\/fpo-(thumb|large)-/i;

function toSitePath(filePath) {
  if (!filePath) return "";
  if (/^(https?:)?\/\//.test(filePath)) return filePath;
  return filePath.startsWith("/") ? filePath : `/${filePath}`;
}

function normalizeAsciiText(value) {
  return String(value || "")
    .replace(/[\u2018\u2019\u201A\u201B]/g, "'")
    .replace(/[\u201C\u201D\u201E\u201F]/g, '"')
    .replace(/[\u2013\u2014\u2212]/g, "-")
    .replace(/\u2026/g, "...")
    .replace(/\u00A0/g, " ")
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^\x09\x0A\x0D\x20-\x7E]/g, "");
}

function buildAsciiSectionText(section) {
  if (!section) return "";

  const clone = section.cloneNode(true);
  clone.querySelectorAll("[data-copy-exclude]").forEach((node) => {
    node.remove();
  });

  return normalizeAsciiText(clone.innerText || clone.textContent || "")
    .split(/\r?\n/)
    .map((line) => line.trimEnd())
    .join("\n")
    .replace(/\n{3,}/g, "\n\n")
    .trim();
}

async function copyPlainText(text) {
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(text);
    return;
  }

  const helper = document.createElement("textarea");
  helper.value = text;
  helper.setAttribute("readonly", "true");
  helper.style.position = "fixed";
  helper.style.opacity = "0";
  helper.style.pointerEvents = "none";
  document.body.appendChild(helper);
  helper.focus();
  helper.select();

  const copied = document.execCommand("copy");
  document.body.removeChild(helper);

  if (!copied) {
    throw new Error("Copy command failed");
  }
}

function activateCardHover(card) {
  if (!card) return;
  card.classList.add("is-hover-active");
}

function clearCardHoverActivation(card) {
  if (!card) return;
  card.classList.remove("is-hover-active");
}

function setMobileNavState(isOpen) {
  if (!siteHeader || !navToggle || !siteNav) return;

  siteHeader.classList.toggle("is-nav-open", isOpen);
  navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
  navToggle.setAttribute(
    "aria-label",
    isOpen ? "Close navigation menu" : "Open navigation menu"
  );

  if (mobileNavMedia.matches) {
    siteNav.hidden = !isOpen;
    siteNav.setAttribute("aria-hidden", isOpen ? "false" : "true");
  } else {
    siteNav.hidden = false;
    siteNav.setAttribute("aria-hidden", "false");
  }
}

function syncMobileNavMode() {
  if (!siteHeader || !navToggle || !siteNav) return;

  document.body.classList.add("nav-ready");

  if (mobileNavMedia.matches) {
    const isOpen = siteHeader.classList.contains("is-nav-open");
    siteNav.hidden = !isOpen;
    siteNav.setAttribute("aria-hidden", isOpen ? "false" : "true");
    navToggle.setAttribute("aria-hidden", "false");
    return;
  }

  siteHeader.classList.remove("is-nav-open");
  siteNav.hidden = false;
  siteNav.setAttribute("aria-hidden", "false");
  navToggle.setAttribute("aria-expanded", "false");
  navToggle.setAttribute("aria-label", "Open navigation menu");
  navToggle.setAttribute("aria-hidden", "true");
}

function getWorkCardElements() {
  if (!workGrid) return [];
  return Array.from(workGrid.children).filter((element) =>
    element.classList?.contains("card")
  );
}

function ensureCardImageShells() {
  getWorkCardElements().forEach((card) => {
    const image = card.querySelector(".card-image");
    if (!image || image.parentElement?.classList.contains("card-image-shell")) {
      return;
    }

    const shell = document.createElement("span");
    shell.className = "card-image-shell";
    image.parentNode.insertBefore(shell, image);
    shell.appendChild(image);
  });
}

function isPlaceholderAssetPath(filePath) {
  const normalized = String(filePath || "")
    .trim()
    .replace(/^\//, "");
  return FPO_ASSET_PATTERN.test(normalized);
}

function setWorkCardPlaceholderState(card, isPlaceholder) {
  if (!card || card.dataset.generated === "true") return;
  card.classList.toggle("is-placeholder", isPlaceholder);
  card.dataset.placeholder = isPlaceholder ? "true" : "false";
}

function sortWorkGridCards() {
  if (!workGrid) return;

  const orderedCards = getWorkCardElements()
    .map((card, index) => ({
      card,
      index,
      rank: card.classList.contains("is-featured")
        ? 0
        : card.classList.contains("is-placeholder")
          ? 2
          : 1
    }))
    .sort((left, right) => left.rank - right.rank || left.index - right.index);

  orderedCards.forEach(({ card }) => {
    workGrid.appendChild(card);
  });
}

function refreshWorkCardState() {
  getWorkCardElements().forEach((card) => {
    if (card.dataset.generated === "true") {
      return;
    }

    const link = card.querySelector(".work-link");
    const thumbSource = link?.querySelector(".card-image")?.getAttribute("src") || "";
    const largeSource = link?.dataset?.lightboxSrc || link?.getAttribute("href") || "";
    const fullscreenSource = link?.dataset?.fullscreenSrc || "";
    const isPlaceholder = [thumbSource, largeSource, fullscreenSource].some(
      isPlaceholderAssetPath
    );

    setWorkCardPlaceholderState(card, isPlaceholder);
  });

  sortWorkGridCards();
  syncProjectFilters();
}

async function readSiteImageConfig() {
  const sources = ["/api/vscimage/config", "/assets/vscimage/config.json"];

  for (const source of sources) {
    try {
      const response = await fetch(source);
      if (!response.ok) continue;
      return await response.json();
    } catch (error) {
      continue;
    }
  }

  return null;
}

async function loadSiteImageConfig() {
  try {
    const config = await readSiteImageConfig();
    if (!config) {
      refreshWorkCardState();
      applyActiveFilter();
      return;
    }

    setBrandLogoAssets(
      config.logos?.light ? toSitePath(config.logos.light) : "",
      config.logos?.dark ? toSitePath(config.logos.dark) : ""
    );

    Object.entries(config.projects || {}).forEach(([projectId, projectConfig]) => {
      const link = document.querySelector(
        `.work-link[data-project-id="${projectId}"]`
      );
      if (!link || !projectConfig) return;
      const detailUrl = String(link.dataset.detailUrl || "").trim();

      if (projectConfig.title) {
        link.dataset.lightboxTitle = projectConfig.title;
      }

      if (projectConfig.large) {
        const largePath = toSitePath(projectConfig.large);
        if (!detailUrl) {
          link.setAttribute("href", largePath);
        }
        link.dataset.lightboxSrc = largePath;
      }

      if (projectConfig.fullscreen) {
        link.dataset.fullscreenSrc = toSitePath(projectConfig.fullscreen);
      }

      const normalizedDescription = String(projectConfig.description || "")
        .trim()
        .replace(/\s+/g, " ")
        .slice(0, 320);
      if (normalizedDescription) {
        link.dataset.lightboxDescription = normalizedDescription;
        const descriptionElement = link.querySelector("p");
        if (descriptionElement) {
          descriptionElement.textContent = normalizedDescription;
        }
      }

      const thumb = link.querySelector(".card-image");
      if (thumb && projectConfig.thumb) {
        thumb.setAttribute("src", toSitePath(projectConfig.thumb));
        thumb.setAttribute(
          "alt",
          `Preview image for ${projectConfig.title || link.dataset.lightboxTitle || "portfolio project"}`
        );
      }
    });

    refreshWorkCardState();
    applyActiveFilter();
  } catch (error) {
    // Keep the site functional using default markup if config is unavailable.
    refreshWorkCardState();
    applyActiveFilter();
  }
}

const projectInquiryForm = document.getElementById("projectInquiryForm");
const projectInquiryStatus = document.getElementById("projectInquiryStatus");
const projectInquirySubmit = document.getElementById("projectInquirySubmit");

function isValidPublicEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function parseGoogleEntryMap(formEl) {
  const entryName = String(formEl?.dataset.googleEntryName || "").trim();
  const entryEmail = String(formEl?.dataset.googleEntryEmail || "").trim();
  const entryBrief = String(formEl?.dataset.googleEntryBrief || "").trim();
  const entryPattern = /^entry\.\d+$/;

  if (
    entryPattern.test(entryName) &&
    entryPattern.test(entryEmail) &&
    entryPattern.test(entryBrief)
  ) {
    return {
      name: entryName,
      email: entryEmail,
      brief: entryBrief
    };
  }

  const prefillUrl = String(formEl?.dataset.googlePrefillUrl || "").trim();
  if (!prefillUrl) return null;

  try {
    const parsed = new URL(prefillUrl);
    const keys = [];
    parsed.searchParams.forEach((value, key) => {
      if (!entryPattern.test(key)) return;
      if (keys.includes(key)) return;
      keys.push(key);
    });

    if (keys.length < 3) {
      return null;
    }

    return {
      name: keys[0],
      email: keys[1],
      brief: keys[2]
    };
  } catch (error) {
    return null;
  }
}

function setProjectInquiryStatus(message, state = "") {
  if (!projectInquiryStatus) return;
  projectInquiryStatus.textContent = message;

  if (state) {
    projectInquiryStatus.dataset.state = state;
    return;
  }

  delete projectInquiryStatus.dataset.state;
}

async function submitToGoogleForm(action, fieldMap, values) {
  const payload = new URLSearchParams();
  payload.set(fieldMap.name, values.name);
  payload.set(fieldMap.email, values.email);
  payload.set(fieldMap.brief, values.brief);
  payload.set("fvv", "1");
  payload.set("draftResponse", "[]");
  payload.set("pageHistory", "0");
  payload.set("fbzx", String(Date.now()));

  await fetch(action, {
    method: "POST",
    mode: "no-cors",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
    },
    body: payload.toString()
  });
}

if (projectInquiryForm) {
  const googleFormAction = String(
    projectInquiryForm.dataset.googleFormAction || ""
  ).trim();
  const googleFormLink = String(projectInquiryForm.dataset.googleFormLink || "").trim();
  const googleEntryMap = parseGoogleEntryMap(projectInquiryForm);
  const contactLocation =
    window.siteAnalytics?.getElementLocation?.(projectInquiryForm) || "contact";

  if (!googleFormAction || !googleEntryMap) {
    setProjectInquiryStatus(
      googleFormLink
        ? "Form mapping is not configured yet. Add entry IDs or a prefill URL, or use the Google Form link below."
        : "Form mapping is not configured yet.",
      "error"
    );
  }

  projectInquiryForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    window.siteAnalytics?.trackContactClick?.("form", contactLocation);

    const formData = new FormData(projectInquiryForm);
    const name = String(formData.get("name") || "").trim();
    const email = String(formData.get("email") || "").trim();
    const brief = String(formData.get("brief") || "").trim();

    if (!name || !email || !brief) {
      setProjectInquiryStatus("Please complete all required fields.", "error");
      window.siteAnalytics?.trackContactFormSubmit?.(false);
      return;
    }

    if (!isValidPublicEmail(email)) {
      setProjectInquiryStatus("Please enter a valid email address.", "error");
      window.siteAnalytics?.trackContactFormSubmit?.(false);
      return;
    }

    if (!googleFormAction || !googleEntryMap) {
      setProjectInquiryStatus(
        googleFormLink
          ? "This page is not fully configured yet. Add entry IDs or a prefill URL, or use the Google Form link below."
          : "This page is not fully configured yet.",
        "error"
      );
      window.siteAnalytics?.trackContactFormSubmit?.(false);
      return;
    }

    if (projectInquirySubmit) {
      projectInquirySubmit.setAttribute("disabled", "true");
    }
    setProjectInquiryStatus("Submitting...", "loading");

    try {
      await submitToGoogleForm(googleFormAction, googleEntryMap, {
        name,
        email,
        brief
      });
      projectInquiryForm.reset();
      setProjectInquiryStatus("Thanks. Your inquiry was sent.", "success");
      window.siteAnalytics?.trackContactFormSubmit?.(true);
    } catch (error) {
      setProjectInquiryStatus(
        googleFormLink
          ? "Unable to submit from this page right now. Use the Google Form link below."
          : "Unable to submit from this page right now.",
        "error"
      );
      window.siteAnalytics?.trackContactFormSubmit?.(false);
    } finally {
      if (projectInquirySubmit) {
        projectInquirySubmit.removeAttribute("disabled");
      }
    }
  });
}

function getRecommendationCards(track) {
  if (!track) return [];
  return Array.from(track.querySelectorAll(".recommendation-card"));
}

function getRecommendationScrollBehavior() {
  return window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ? "auto"
    : "smooth";
}

function getRecommendationScrollStep(track) {
  const firstCard = track?.querySelector(".recommendation-card");
  if (!track || !firstCard) return 0;

  const trackStyles = window.getComputedStyle(track);
  const gap = Number.parseFloat(trackStyles.columnGap || trackStyles.gap || "0") || 0;
  return firstCard.getBoundingClientRect().width + gap;
}

function getClosestRecommendationIndex(track) {
  const cards = getRecommendationCards(track);
  if (!track || !cards.length) return 0;

  const trackRect = track.getBoundingClientRect();
  const trackCenter = trackRect.left + trackRect.width / 2;
  let closestIndex = 0;
  let closestDistance = Number.POSITIVE_INFINITY;

  cards.forEach((card, index) => {
    const cardRect = card.getBoundingClientRect();
    const cardCenter = cardRect.left + cardRect.width / 2;
    const distance = Math.abs(trackCenter - cardCenter);

    if (distance < closestDistance) {
      closestDistance = distance;
      closestIndex = index;
    }
  });

  return closestIndex;
}

function scrollRecommendationIntoView(track, index) {
  const cards = getRecommendationCards(track);
  const target = cards[index];
  if (!target) return;

  target.scrollIntoView({
    behavior: getRecommendationScrollBehavior(),
    block: "nearest",
    inline: "center"
  });
}

function getRecommendationLeftEdgeScroll(track) {
  if (!track) return 0;

  const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
  if (!maxScroll) return 0;

  const isRtl = window.getComputedStyle(track).direction.toLowerCase() === "rtl";
  if (!isRtl) return 0;

  track.scrollLeft = -maxScroll;
  if (Math.abs(track.scrollLeft + maxScroll) <= 1) return -maxScroll;

  track.scrollLeft = maxScroll;
  if (Math.abs(track.scrollLeft - maxScroll) <= 1) return maxScroll;

  return 0;
}

function scrollRecommendationsToLeftEdge(track) {
  if (!track) return;

  window.requestAnimationFrame(() => {
    track.scrollLeft = getRecommendationLeftEdgeScroll(track);
    updateRecommendationNavState();
  });
}

function updateRecommendationNavState() {
  if (!recommendationsTrack || !recommendationsPrevButton || !recommendationsNextButton) {
    return;
  }

  const maxScroll = Math.max(
    0,
    recommendationsTrack.scrollWidth - recommendationsTrack.clientWidth
  );
  const isRtl =
    window.getComputedStyle(recommendationsTrack).direction.toLowerCase() === "rtl";
  const currentScroll = recommendationsTrack.scrollLeft;
  const tolerance = 2;
  const isAtLeftEdge = isRtl
    ? currentScroll <= -maxScroll + tolerance
    : currentScroll <= tolerance;
  const isAtRightEdge = isRtl
    ? currentScroll >= -tolerance
    : currentScroll >= maxScroll - tolerance;

  recommendationsPrevButton.disabled = isAtLeftEdge;
  recommendationsNextButton.disabled = isAtRightEdge;
}

function scrollRecommendationsBy(direction) {
  if (!recommendationsTrack) return;

  const step = getRecommendationScrollStep(recommendationsTrack);
  if (!step) return;

  const isRtl =
    window.getComputedStyle(recommendationsTrack).direction.toLowerCase() === "rtl";
  const scrollLeft = isRtl ? -direction * step : direction * step;
  const startPosition = recommendationsTrack.scrollLeft;

  recommendationsTrack.scrollBy({
    left: scrollLeft,
    behavior: getRecommendationScrollBehavior()
  });
  updateRecommendationNavState();

  window.setTimeout(() => {
    const moved = Math.abs(recommendationsTrack.scrollLeft - startPosition) > 1;
    if (moved) {
      updateRecommendationNavState();
      return;
    }

    const closestIndex = getClosestRecommendationIndex(recommendationsTrack);
    scrollRecommendationIntoView(recommendationsTrack, closestIndex + direction);
    window.setTimeout(updateRecommendationNavState, 180);
  }, 140);
}

if (recommendationsTrack && recommendationsPrevButton && recommendationsNextButton) {
  updateRecommendationNavState();
  scrollRecommendationsToLeftEdge(recommendationsTrack);
  window.addEventListener(
    "load",
    () => {
      scrollRecommendationsToLeftEdge(recommendationsTrack);
    },
    { once: true }
  );
  document.fonts?.ready?.then(() => {
    scrollRecommendationsToLeftEdge(recommendationsTrack);
  });
  recommendationsTrack.addEventListener("scroll", updateRecommendationNavState, {
    passive: true
  });
  window.addEventListener("resize", updateRecommendationNavState);

  recommendationsPrevButton.addEventListener("click", () => {
    scrollRecommendationsBy(1);
  });

  recommendationsNextButton.addEventListener("click", () => {
    scrollRecommendationsBy(-1);
  });

  recommendationsTrack.addEventListener("keydown", (event) => {
    if (event.key === "ArrowLeft") {
      event.preventDefault();
      scrollRecommendationsBy(1);
      return;
    }

    if (event.key === "ArrowRight") {
      event.preventDefault();
      scrollRecommendationsBy(-1);
      return;
    }

    if (event.key === "Home") {
      event.preventDefault();
      scrollRecommendationIntoView(recommendationsTrack, 0);
      return;
    }

    if (event.key === "End") {
      event.preventDefault();
      scrollRecommendationIntoView(
        recommendationsTrack,
        getRecommendationCards(recommendationsTrack).length - 1
      );
    }
  });
}

const filterGroup = document.querySelector("#work .filters");
const filterButtons = document.querySelectorAll(".filter-btn");
const workLoadMoreButton = document.getElementById("workLoadMore");
const WORK_ROWS_PER_PAGE = 2;
let visibleWorkCards = 0;
let featuredCardHeightFrame = 0;
const REGULAR_CARD_IMAGE_ASPECT_RATIO = 4 / 3;

function getWorkGridColumnCount() {
  if (!workGrid) return 3;

  const computed = window.getComputedStyle(workGrid);
  const template = String(computed.gridTemplateColumns || "").trim();
  const columns = template ? template.split(/\s+/).filter(Boolean).length : 3;
  return Math.max(1, columns);
}

function getWorkCardsPerPage() {
  return getWorkGridColumnCount() * WORK_ROWS_PER_PAGE;
}

function normalizeWorkCategory(value) {
  const normalized = String(value || "")
    .trim()
    .toLowerCase();
  return normalized === "web" ? "ux-design" : normalized;
}

function getAvailableWorkFilters() {
  const categories = new Set();
  const cards = getWorkCardElements();
  const displayCards = cards.some((card) => !card.classList.contains("is-placeholder"))
    ? cards.filter((card) => !card.classList.contains("is-placeholder"))
    : cards;

  displayCards.forEach((card) => {
    const category = normalizeWorkCategory(card.dataset.category);
    if (!category || category === "all") return;
    categories.add(category);
  });

  return categories;
}

function syncProjectFilters() {
  if (!filterGroup || !filterButtons.length) return;

  const availableFilters = getAvailableWorkFilters();
  const shouldShowFilters = availableFilters.size > 0;

  filterButtons.forEach((button) => {
    const filterValue = normalizeWorkCategory(button.dataset.filter);
    const showButton =
      shouldShowFilters &&
      (filterValue === "all" || availableFilters.has(filterValue));

    button.hidden = !showButton;
    button.setAttribute("aria-hidden", showButton ? "false" : "true");

    if (!showButton) {
      button.classList.remove("active");
    }
  });

  filterGroup.hidden = !shouldShowFilters;
  filterGroup.setAttribute("aria-hidden", shouldShowFilters ? "false" : "true");

  if (!shouldShowFilters) return;

  const activeVisibleButton = Array.from(filterButtons).find(
    (button) => !button.hidden && button.classList.contains("active")
  );
  if (activeVisibleButton) return;

  const allButton = Array.from(filterButtons).find(
    (button) => !button.hidden && normalizeWorkCategory(button.dataset.filter) === "all"
  );
  const fallbackButton = allButton || Array.from(filterButtons).find((button) => !button.hidden);

  if (!fallbackButton) return;

  filterButtons.forEach((button) => {
    button.classList.toggle("active", button === fallbackButton);
  });
}

function getFilteredCards() {
  const activeButton = document.querySelector(".filter-btn.active");
  const target = activeButton?.dataset?.filter || "all";
  const cards = getWorkCardElements();
  const displayCards = cards.some((card) => !card.classList.contains("is-placeholder"))
    ? cards.filter((card) => !card.classList.contains("is-placeholder"))
    : cards;

  return displayCards.filter((card) => {
    const category = normalizeWorkCategory(card.dataset.category);
    const normalizedTarget = normalizeWorkCategory(target);
    return normalizedTarget === "all" || category === normalizedTarget;
  });
}

function getFeaturedCardHeightScale() {
  if (window.innerWidth <= 640) {
    return 1.25;
  }

  if (window.innerWidth <= 960) {
    return 1.15;
  }

  return 1;
}

function updateFeaturedCardHeight() {
  if (!workGrid) return;

  const featuredImages = Array.from(
    workGrid.querySelectorAll(".card.is-featured:not(.hide) .card-image")
  );
  if (!featuredImages.length) {
    workGrid.style.removeProperty("--featured-card-image-height");
    return;
  }

  const computed = window.getComputedStyle(workGrid);
  const columns = getWorkGridColumnCount();
  const gap = Number.parseFloat(computed.columnGap || computed.gap || "0") || 0;
  const gridWidth = workGrid.getBoundingClientRect().width;
  const referenceWidth = (gridWidth - gap * Math.max(0, columns - 1)) / columns;
  const referenceHeight = Math.round(referenceWidth / REGULAR_CARD_IMAGE_ASPECT_RATIO);

  if (referenceHeight > 0) {
    const featuredHeight = Math.round(referenceHeight * getFeaturedCardHeightScale());
    workGrid.style.setProperty("--featured-card-image-height", `${featuredHeight}px`);
  } else {
    workGrid.style.removeProperty("--featured-card-image-height");
  }
}

function scheduleFeaturedCardHeightUpdate() {
  if (!workGrid) return;
  if (featuredCardHeightFrame) {
    window.cancelAnimationFrame(featuredCardHeightFrame);
  }

  featuredCardHeightFrame = window.requestAnimationFrame(() => {
    featuredCardHeightFrame = 0;
    updateFeaturedCardHeight();
  });
}

function applyActiveFilter() {
  const cards = getWorkCardElements();
  const filteredCards = getFilteredCards();
  const maxVisible = Math.max(0, visibleWorkCards || getWorkCardsPerPage());
  const visibleSet = new Set(filteredCards.slice(0, maxVisible));

  cards.forEach((card) => {
    const show = visibleSet.has(card);
    card.classList.toggle("hide", !show);
    card.setAttribute("aria-hidden", show ? "false" : "true");
  });

  if (workLoadMoreButton) {
    const hasMore = filteredCards.length > maxVisible;
    workLoadMoreButton.hidden = !hasMore;
    workLoadMoreButton.setAttribute("aria-hidden", hasMore ? "false" : "true");
  }

  scheduleFeaturedCardHeightUpdate();
}

filterButtons.forEach((button) => {
  button.addEventListener("click", () => {
    filterButtons.forEach((btn) => btn.classList.remove("active"));
    button.classList.add("active");
    visibleWorkCards = getWorkCardsPerPage();
    applyActiveFilter();
  });
});

if (workLoadMoreButton) {
  workLoadMoreButton.addEventListener("click", () => {
    visibleWorkCards += getWorkCardsPerPage();
    applyActiveFilter();
  });
}

if (workGrid) {
  workGrid.addEventListener(
    "load",
    (event) => {
      if (!event.target?.classList?.contains("card-image")) return;
      scheduleFeaturedCardHeightUpdate();
    },
    true
  );

  workGrid.addEventListener("pointerover", (event) => {
    if (event.pointerType && event.pointerType !== "mouse") return;
    const card = event.target.closest(".card");
    if (!card || !workGrid.contains(card)) return;
    const previousCard = event.relatedTarget?.closest?.(".card");
    if (previousCard === card) return;

    activateCardHover(card);
  });

  workGrid.addEventListener("pointerout", (event) => {
    if (event.pointerType && event.pointerType !== "mouse") return;
    const card = event.target.closest(".card");
    if (!card || !workGrid.contains(card)) return;
    const nextCard = event.relatedTarget?.closest?.(".card");
    if (nextCard === card) return;
    clearCardHoverActivation(card);
  });

  workGrid.addEventListener("focusin", (event) => {
    const card = event.target.closest(".card");
    if (!card || !workGrid.contains(card)) return;
    activateCardHover(card);
  });

  workGrid.addEventListener("focusout", (event) => {
    const card = event.target.closest(".card");
    if (!card || !workGrid.contains(card)) return;
    const nextCard = event.relatedTarget?.closest?.(".card");
    if (nextCard === card) return;
    clearCardHoverActivation(card);
  });
}

if (navToggle && siteHeader && siteNav) {
  const navLinks = Array.from(siteNav.querySelectorAll("a"));
  syncMobileNavMode();

  navToggle.addEventListener("click", () => {
    if (!mobileNavMedia.matches) return;
    const isOpen = siteHeader.classList.contains("is-nav-open");
    setMobileNavState(!isOpen);
  });

  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      if (!mobileNavMedia.matches) return;
      setMobileNavState(false);
    });
  });

  document.addEventListener("click", (event) => {
    if (!mobileNavMedia.matches) return;
    if (!siteHeader.classList.contains("is-nav-open")) return;
    if (siteHeader.contains(event.target)) return;
    setMobileNavState(false);
  });

  window.addEventListener("hashchange", () => {
    if (!mobileNavMedia.matches) return;
    setMobileNavState(false);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    if (!mobileNavMedia.matches) return;
    if (!siteHeader.classList.contains("is-nav-open")) return;
    setMobileNavState(false);
    navToggle.focus();
  });
}

if (copyExperienceTextButton && experienceResumeSection) {
  const defaultCopyLabel = copyExperienceTextButton.textContent.trim() || "Copy Text";
  let copyLabelTimer = 0;

  copyExperienceTextButton.addEventListener("click", async () => {
    const asciiText = buildAsciiSectionText(experienceResumeSection);
    if (!asciiText) return;

    copyExperienceTextButton.disabled = true;

    try {
      await copyPlainText(asciiText);
      copyExperienceTextButton.textContent = "Copied";
    } catch (error) {
      copyExperienceTextButton.textContent = "Copy Failed";
    } finally {
      window.clearTimeout(copyLabelTimer);
      copyLabelTimer = window.setTimeout(() => {
        copyExperienceTextButton.textContent = defaultCopyLabel;
        copyExperienceTextButton.disabled = false;
      }, 1600);
    }
  });
}

let workResizeTimer = null;
window.addEventListener("resize", () => {
  syncMobileNavMode();
  clearTimeout(workResizeTimer);
  workResizeTimer = setTimeout(() => {
    const perPage = getWorkCardsPerPage();
    const currentPages = Math.max(1, Math.ceil((visibleWorkCards || perPage) / perPage));
    visibleWorkCards = currentPages * perPage;
    applyActiveFilter();
  }, 100);
});

const revealItems = document.querySelectorAll(".reveal");
const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add("is-visible");
      observer.unobserve(entry.target);
    });
  },
  {
    threshold: 0.16,
    rootMargin: "0px 0px -8% 0px"
  }
);

revealItems.forEach((item, index) => {
  item.style.transitionDelay = `${Math.min(index * 45, 220)}ms`;
  observer.observe(item);
});

const lightbox = document.getElementById("lightbox");
const lightboxImage = document.getElementById("lightboxImage");
const lightboxCaption = document.getElementById("lightboxCaption");
const lightboxClose = document.getElementById("lightboxClose");
const lightboxFullscreen = document.getElementById("lightboxFullscreen");
const lightboxFullscreenIcon = document.getElementById("lightboxFullscreenIcon");
const lightboxPrev = document.getElementById("lightboxPrev");
const lightboxNext = document.getElementById("lightboxNext");
let activeLightboxIndex = 0;
let useFullscreenAssets = false;

function getWorkLinks() {
  return Array.from(document.querySelectorAll(".card:not(.hide) .work-link"));
}

function isFullscreenActive() {
  return Boolean(document.fullscreenElement || document.webkitFullscreenElement);
}

function exitFullscreenSafely() {
  if (document.exitFullscreen) {
    document.exitFullscreen().catch(() => {});
    return;
  }

  if (document.webkitExitFullscreen) {
    document.webkitExitFullscreen();
  }
}

function updateLightboxFullscreenButton() {
  if (!lightboxFullscreen) return;

  const active = isFullscreenActive();
  lightboxFullscreen.setAttribute(
    "aria-label",
    active ? "Exit fullscreen" : "Enter fullscreen"
  );
  lightboxFullscreen.setAttribute("aria-pressed", active ? "true" : "false");

  if (lightboxFullscreenIcon) {
    lightboxFullscreenIcon.textContent = "⤢";
  }
}

function getCustomLightboxLink(link) {
  const text = String(link?.dataset?.lightboxLinkText || "")
    .trim()
    .replace(/\s+/g, " ")
    .slice(0, 80);
  const url = String(link?.dataset?.lightboxLinkUrl || "").trim().slice(0, 320);

  if (!text || !url) {
    return null;
  }

  if (!/^(\/|https?:|mailto:|tel:)/i.test(url)) {
    return null;
  }

  return { text, url };
}

function renderLightboxCaption(link, title, description, index, total) {
  if (!lightboxCaption) return;

  const counterText = `(${index + 1}/${total})`;
  const summary = description ? `${title}: ${description} ${counterText}` : `${title} ${counterText}`;
  const customLink = getCustomLightboxLink(link);

  lightboxCaption.replaceChildren();

  const copy = document.createElement("span");
  copy.textContent = summary;
  lightboxCaption.appendChild(copy);

  if (!customLink) {
    return;
  }

  lightboxCaption.appendChild(document.createTextNode(" "));

  const divider = document.createElement("span");
  divider.className = "lightbox-caption-separator";
  divider.setAttribute("aria-hidden", "true");
  divider.textContent = "•";
  lightboxCaption.appendChild(divider);

  lightboxCaption.appendChild(document.createTextNode(" "));

  const editLink = document.createElement("a");
  editLink.className = "lightbox-caption-link";
  editLink.href = customLink.url;
  editLink.textContent = customLink.text;
  editLink.target = "_blank";
  editLink.rel = "noreferrer noopener";
  lightboxCaption.appendChild(editLink);
}

function renderLightboxImage(index) {
  const workLinks = getWorkLinks();
  if (workLinks.length === 0) return;

  const safeIndex = ((index % workLinks.length) + workLinks.length) % workLinks.length;
  const link = workLinks[safeIndex];
  if (!link || !lightboxImage || !lightboxCaption) return;

  const standardSource = link.dataset.lightboxSrc || link.getAttribute("href");
  const fullscreenSource = link.dataset.fullscreenSrc || standardSource;
  const source = useFullscreenAssets ? fullscreenSource : standardSource;
  const title = link.dataset.lightboxTitle || "Selected work sample";
  const inlineDescription = link.querySelector("p")?.textContent || "";
  const description =
    String(link.dataset.lightboxDescription || inlineDescription || "")
      .trim()
      .replace(/\s+/g, " ")
      .slice(0, 320) || "";

  activeLightboxIndex = safeIndex;
  lightboxImage.setAttribute("src", source);
  lightboxImage.setAttribute("alt", `Large FPO image for ${title}`);
  renderLightboxCaption(link, title, description, safeIndex, workLinks.length);
}

function openLightbox(index, useFullscreenVersion = false) {
  const workLinks = getWorkLinks();
  if (workLinks.length === 0) return;
  if (!lightbox) return;
  useFullscreenAssets = useFullscreenVersion;
  activeLightboxIndex =
    ((index % workLinks.length) + workLinks.length) % workLinks.length;
  renderLightboxImage(activeLightboxIndex);
  lightbox.classList.add("is-open");
  lightbox.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "hidden";
  updateLightboxFullscreenButton();
}

function requestElementFullscreen(element) {
  if (!element) return;

  if (element.requestFullscreen) {
    element.requestFullscreen().catch(() => {});
    return;
  }

  if (element.webkitRequestFullscreen) {
    element.webkitRequestFullscreen();
  }
}

function closeLightbox() {
  if (!lightbox) return;

  if (isFullscreenActive()) {
    exitFullscreenSafely();
  }

  useFullscreenAssets = false;
  lightbox.classList.remove("is-open");
  lightbox.setAttribute("aria-hidden", "true");
  document.body.style.overflow = "";
  updateLightboxFullscreenButton();
}

if (lightbox && lightboxImage && lightboxCaption) {
  if (workGrid) {
    workGrid.addEventListener("click", (event) => {
      const fullscreenButton = event.target.closest(".card-fullscreen");
      if (fullscreenButton && workGrid.contains(fullscreenButton)) {
        event.preventDefault();
        event.stopPropagation();

        const card = fullscreenButton.closest(".card");
        const link = card ? card.querySelector(".work-link") : null;
        const detailUrl = String(link?.dataset?.detailUrl || "").trim();
        if (detailUrl) {
          window.location.assign(detailUrl);
          return;
        }
        const workLinks = getWorkLinks();
        const index = workLinks.indexOf(link);
        if (index < 0) return;

        openLightbox(index, true);
        requestElementFullscreen(lightbox);
        return;
      }

      const link = event.target.closest(".work-link");
      if (!link || !workGrid.contains(link)) return;
      if (
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
      ) {
        return;
      }
      if (link.dataset.detailUrl) {
        return;
      }
      event.preventDefault();
      const workLinks = getWorkLinks();
      const index = workLinks.indexOf(link);
      if (index < 0) return;
      openLightbox(index, false);
    });
  }

  if (lightboxClose) {
    lightboxClose.addEventListener("click", closeLightbox);
  }

  if (lightboxFullscreen) {
    lightboxFullscreen.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();

      if (!lightbox.classList.contains("is-open")) return;

      if (isFullscreenActive()) {
        exitFullscreenSafely();
        return;
      }

      useFullscreenAssets = true;
      renderLightboxImage(activeLightboxIndex);
      requestElementFullscreen(lightbox);
      updateLightboxFullscreenButton();
    });
  }

  if (lightboxPrev) {
    lightboxPrev.addEventListener("click", () => {
      const workLinks = getWorkLinks();
      if (workLinks.length === 0) return;
      activeLightboxIndex =
        (activeLightboxIndex - 1 + workLinks.length) % workLinks.length;
      renderLightboxImage(activeLightboxIndex);
    });
  }

  if (lightboxNext) {
    lightboxNext.addEventListener("click", () => {
      const workLinks = getWorkLinks();
      if (workLinks.length === 0) return;
      activeLightboxIndex = (activeLightboxIndex + 1) % workLinks.length;
      renderLightboxImage(activeLightboxIndex);
    });
  }

  lightbox.addEventListener("click", (event) => {
    if (event.target === lightbox) {
      closeLightbox();
    }
  });

  document.addEventListener("keydown", (event) => {
    const workLinks = getWorkLinks();
    const isOpen = lightbox.classList.contains("is-open");
    if (event.key === "Escape" && isOpen) {
      if (isFullscreenActive()) return;
      closeLightbox();
    }
    if (event.key === "ArrowLeft" && isOpen && workLinks.length > 0) {
      activeLightboxIndex =
        (activeLightboxIndex - 1 + workLinks.length) % workLinks.length;
      renderLightboxImage(activeLightboxIndex);
    }
    if (event.key === "ArrowRight" && isOpen && workLinks.length > 0) {
      activeLightboxIndex = (activeLightboxIndex + 1) % workLinks.length;
      renderLightboxImage(activeLightboxIndex);
    }
  });

  document.addEventListener("fullscreenchange", () => {
    if (!lightbox.classList.contains("is-open")) {
      updateLightboxFullscreenButton();
      return;
    }

    useFullscreenAssets = isFullscreenActive();
    renderLightboxImage(activeLightboxIndex);
    updateLightboxFullscreenButton();
  });

  document.addEventListener("webkitfullscreenchange", () => {
    if (!lightbox.classList.contains("is-open")) {
      updateLightboxFullscreenButton();
      return;
    }

    useFullscreenAssets = isFullscreenActive();
    renderLightboxImage(activeLightboxIndex);
    updateLightboxFullscreenButton();
  });
}

ensureCardImageShells();
refreshWorkCardState();
visibleWorkCards = getWorkCardsPerPage();
applyActiveFilter();
loadSiteImageConfig();
