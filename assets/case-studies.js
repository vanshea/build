(function () {
  const lightbox = document.getElementById("caseStudyLightbox");
  const lightboxDataNode = document.getElementById("caseStudyLightboxData");
  const lightboxImage = document.getElementById("caseStudyLightboxImage");
  const lightboxTitle = document.getElementById("caseStudyLightboxTitle");
  const lightboxCaption = document.getElementById("caseStudyLightboxCaption");
  const lightboxCounter = document.getElementById("caseStudyLightboxCounter");
  const lightboxClose = document.getElementById("caseStudyLightboxClose");
  const lightboxPrev = document.getElementById("caseStudyLightboxPrev");
  const lightboxNext = document.getElementById("caseStudyLightboxNext");
  const featuredViewer = document.querySelector("[data-featured-viewer]");

  let lightboxImages = [];
  let activeLightboxIndex = 0;

  if (lightboxDataNode) {
    try {
      lightboxImages = JSON.parse(lightboxDataNode.textContent || "[]");
    } catch (_error) {
      lightboxImages = [];
    }
  }

  function normalizeIndex(index) {
    if (!lightboxImages.length) return 0;
    const numeric = Number(index);
    if (!Number.isFinite(numeric)) return 0;
    return ((numeric % lightboxImages.length) + lightboxImages.length) % lightboxImages.length;
  }

  function renderLightbox(index) {
    if (!lightboxImages.length || !lightboxImage) return;

    activeLightboxIndex = normalizeIndex(index);
    const image = lightboxImages[activeLightboxIndex];

    lightboxImage.src = image.src;
    lightboxImage.alt = image.alt || image.title || "Case study image";

    if (lightboxTitle) {
      lightboxTitle.textContent = image.title || "";
      lightboxTitle.hidden = !image.title;
    }

    if (lightboxCaption) {
      lightboxCaption.textContent = image.caption || image.alt || "";
      lightboxCaption.hidden = !lightboxCaption.textContent;
    }

    if (lightboxCounter) {
      lightboxCounter.textContent = `${activeLightboxIndex + 1} / ${lightboxImages.length}`;
    }
  }

  function openLightbox(index) {
    if (!lightbox || !lightboxImages.length) return;

    renderLightbox(index);
    lightbox.classList.add("is-open");
    lightbox.setAttribute("aria-hidden", "false");
    document.body.classList.add("case-study-lightbox-open");
  }

  function closeLightbox() {
    if (!lightbox) return;

    lightbox.classList.remove("is-open");
    lightbox.setAttribute("aria-hidden", "true");
    document.body.classList.remove("case-study-lightbox-open");
  }

  function bindLightboxTriggers() {
    document.querySelectorAll("[data-lightbox-trigger]").forEach((trigger) => {
      trigger.addEventListener("click", () => {
        openLightbox(trigger.dataset.lightboxIndex || 0);
      });
    });
  }

  function bindLightboxControls() {
    if (!lightbox) return;

    lightbox.addEventListener("click", (event) => {
      if (event.target === lightbox) {
        closeLightbox();
      }
    });

    if (lightboxClose) {
      lightboxClose.addEventListener("click", closeLightbox);
    }

    if (lightboxPrev) {
      lightboxPrev.addEventListener("click", () => {
        renderLightbox(activeLightboxIndex - 1);
      });
    }

    if (lightboxNext) {
      lightboxNext.addEventListener("click", () => {
        renderLightbox(activeLightboxIndex + 1);
      });
    }

    document.addEventListener("keydown", (event) => {
      if (!lightbox.classList.contains("is-open")) return;

      if (event.key === "Escape") {
        closeLightbox();
      }

      if (event.key === "ArrowLeft") {
        renderLightbox(activeLightboxIndex - 1);
      }

      if (event.key === "ArrowRight") {
        renderLightbox(activeLightboxIndex + 1);
      }
    });
  }

  function initFeaturedViewer() {
    if (!featuredViewer) return;

    const stage = featuredViewer.querySelector("[data-featured-stage]");
    const stageImage = featuredViewer.querySelector("[data-featured-stage-image]");
    const stageTitle = featuredViewer.querySelector("[data-featured-stage-title]");
    const stageCaption = featuredViewer.querySelector("[data-featured-stage-caption]");
    const thumbs = Array.from(featuredViewer.querySelectorAll("[data-featured-thumb]"));

    if (!stage || !stageImage || thumbs.length === 0) return;

    function setActiveThumb(nextThumb) {
      thumbs.forEach((thumb) => {
        const isActive = thumb === nextThumb;
        thumb.classList.toggle("is-active", isActive);
        thumb.setAttribute("aria-selected", isActive ? "true" : "false");
      });

      stage.dataset.lightboxIndex = nextThumb.dataset.lightboxIndex || "0";
      stageImage.src = nextThumb.dataset.src || stageImage.src;
      stageImage.alt = nextThumb.dataset.alt || "";

      if (stageTitle) {
        stageTitle.textContent = nextThumb.dataset.title || "Selected visual";
      }

      if (stageCaption) {
        stageCaption.textContent = nextThumb.dataset.caption || nextThumb.dataset.alt || "";
      }
    }

    thumbs.forEach((thumb) => {
      thumb.addEventListener("click", () => {
        setActiveThumb(thumb);
      });
    });
  }

  bindLightboxTriggers();
  bindLightboxControls();
  initFeaturedViewer();
})();
