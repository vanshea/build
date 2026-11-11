import { projects, asset, el, picture } from "./data.js";
const collection = document.getElementById("collection");
function byline(p) {
  return el("p", "folio", p.sector);
}
function credits(p) {
  return el("p", "credits", p.client + " · " + p.role);
}
function metric(p) {
  const block = el("div", "metric");
  block.append(
    el(
      "span",
      "folio",
      p.metric.placeholder
        ? "Illustrative metric · Placeholder"
        : "Outcome metric",
    ),
    el("strong", "", p.metric.value),
    el("span", "", p.metric.label),
  );
  return block;
}
function gallery(data) {
  collection.className = "gallery";
  const dialog = el("dialog", "viewer");
  dialog.setAttribute("aria-labelledby", "viewer-title");
  const toolbar = el("div", "viewer-bar"),
    title = el("h2"),
    close = el("button", "", "Close ×");
  title.id = "viewer-title";
  close.autofocus = true;
  toolbar.append(title, close);
  const imageSlot = el("div", "viewer-image"),
    controls = el("div", "viewer-controls");
  const prev = el("button", "", "← Previous"),
    next = el("button", "", "Next →"),
    info = el("span", "viewer-count");
  info.setAttribute("role", "status");
  controls.append(prev, info, next);
  dialog.append(toolbar, imageSlot, controls);
  document.body.append(dialog);
  let photos = [],
    idx = 0,
    trigger = null;
  function slide(step) {
    idx = (idx + step + photos.length) % photos.length;
    imageSlot.replaceChildren(picture(photos[idx], "100vw", true));
    info.textContent =
      idx + 1 + " / " + photos.length + " · " + photos[idx].label;
  }
  close.onclick = () => dialog.close();
  prev.onclick = () => slide(-1);
  next.onclick = () => slide(1);
  dialog.onclose = () => {
    document.body.classList.remove("modal-open");
    trigger?.focus();
  };
  dialog.onkeydown = (e) => {
    if (e.key === "Tab") {
      const boundary = e.shiftKey ? close : next;
      if (document.activeElement === boundary) {
        e.preventDefault();
        (e.shiftKey ? next : close).focus();
      }
    }
    if (["ArrowLeft", "ArrowRight"].includes(e.key)) {
      e.preventDefault();
      slide(e.key === "ArrowLeft" ? -1 : 1);
    }
  };
  let start = null;
  imageSlot.onpointerdown = (e) => {
    if (e.isPrimary) {
      start = [e.clientX, e.clientY];
      imageSlot.setPointerCapture(e.pointerId);
    }
  };
  imageSlot.onpointercancel = () => {
    start = null;
  };
  imageSlot.onpointerup = (e) => {
    if (!start) return;
    const dx = e.clientX - start[0],
      dy = e.clientY - start[1];
    start = null;
    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.4)
      slide(dx < 0 ? 1 : -1);
  };
  data.forEach((p, i) => {
    const article = el("article", "gallery-card"),
      launch = el("button", "cover");
    launch.setAttribute("aria-label", "Open " + p.title + " image gallery");
    launch.append(
      picture(p.hero, undefined, i === 0),
      el("span", "expand", "↗"),
    );
    launch.onclick = () => {
      trigger = launch;
      photos = [p.hero, ...p.images];
      idx = 0;
      title.textContent = p.title;
      slide(0);
      dialog.showModal();
      document.body.classList.add("modal-open");
    };
    article.append(
      launch,
      byline(p),
      el("h2", "", p.title),
      el("p", "deck", p.summary),
      credits(p),
    );
    collection.append(article);
  });
}
function narrative(data) {
  collection.className = "narrative";
  let observer = null;
  if (
    !CSS.supports("animation-timeline: view()") &&
    "IntersectionObserver" in window
  ) {
    observer = new IntersectionObserver((entries) =>
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add("entered");
          observer.unobserve(e.target);
        }
      }),
    );
  }
  data.forEach((p, i) => {
    const story = el("article", "story"),
      lead = el("div", "story-lead");
    lead.append(
      byline(p),
      el("h2", "", p.title),
      el("p", "deck", p.summary),
      credits(p),
      picture(p.hero, undefined, i === 0),
    );
    const chapters = el("div", "chapters");
    ["Problem", "Approach", "Outcome"].forEach((label, j) => {
      const chapter = el("section", "chapter");
      chapter.append(el("h3", "", label), el("p", "", p[label.toLowerCase()]));
      if (j === 2) chapter.append(metric(p));
      const images = j === 2 ? p.images.slice(j) : p.images.slice(j, j + 1);
      for (const img of images)
        chapter.append(picture(img, "(max-width:700px) 92vw, 40vw"));
      chapters.append(chapter);
      observer?.observe(chapter);
    });
    story.append(lead, chapters);
    collection.append(story);
  });
}
function processView(data) {
  collection.className = "process-list";
  const pref = matchMedia("(prefers-reduced-motion: reduce)");
  data.forEach((p, i) => {
    const row = el("article", "process"),
      visual = el("div", "film"),
      vid = el("video");
    const poster = asset(p.process.poster);
    if (i === 0 || !("IntersectionObserver" in window)) vid.poster = poster;
    vid.width = 1280;
    vid.height = 800;
    vid.loop = true;
    vid.muted = true;
    vid.defaultMuted = true;
    vid.playsInline = true;
    vid.preload = "none";
    vid.setAttribute(
      "aria-label",
      p.title + ": " + p.process.stages.join(", "),
    );
    const btn = el("button", "play", "Play process"),
      status = el("span", "film-status");
    status.setAttribute("role", "status");
    visual.append(vid, btn, status);
    let loaded = false,
      visible = false,
      paused = false,
      userPlay = false,
      near = false;
    const load = () => {
      if (loaded) return;
      loaded = true;
      for (const size of ["mobile", "desktop"])
        for (const fmt of ["mp4", "webm"]) {
          const s = el("source");
          if (size === "mobile") s.media = "(max-width: 720px)";
          s.type = "video/" + fmt;
          s.src = asset(p.process[size][fmt]);
          vid.append(s);
        }
      vid.load();
    };
    const sync = () => {
      if (near && !pref.matches) load();
      const canPlay =
        visible && !document.hidden && !paused && (!pref.matches || userPlay);
      vid.autoplay = canPlay && !pref.matches;
      if (!canPlay) {
        vid.pause();
        return;
      }
      load();
      vid.play().catch(() => {
        btn.textContent = "Play process";
      });
    };
    btn.onclick = () => {
      if (vid.paused) {
        visible = true;
        userPlay = true;
        paused = false;
        load();
        vid.play().catch(() => (status.textContent = "Playback unavailable."));
      } else {
        paused = true;
        userPlay = false;
        vid.pause();
      }
    };
    vid.onplay = () => {
      btn.textContent = "Pause process";
    };
    vid.onpause = () => {
      btn.textContent = "Play process";
    };
    pref.addEventListener("change", () => {
      userPlay = false;
      sync();
      if (pref.matches && loaded) {
        vid.load();
      }
    });
    matchMedia("(max-width: 720px)").addEventListener("change", () => {
      if (loaded) {
        vid.autoplay = false;
        vid.replaceChildren();
        vid.load();
        loaded = false;
        sync();
      }
    });
    document.addEventListener("visibilitychange", sync);
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(
        ([e]) => {
          near = e.isIntersecting;
          if (near && !vid.hasAttribute("poster")) vid.poster = poster;
          sync();
        },
        { rootMargin: "180px" },
      ).observe(vid);
      new IntersectionObserver(
        ([e]) => {
          visible = e.isIntersecting;
          sync();
        },
        { threshold: 0.01 },
      ).observe(vid);
    }
    const details = el("div", "process-copy");
    details.append(
      byline(p),
      el("h2", "", p.title),
      el("p", "deck", p.summary),
      credits(p),
      el("p", "stages", p.process.stages.join(" → ")),
    );
    const notes = el("details"),
      toggle = el("summary", "", "Read project notes");
    notes.append(toggle);
    for (const key of ["problem", "approach", "outcome"])
      notes.append(
        el("h3", "", key[0].toUpperCase() + key.slice(1)),
        el("p", "", p[key]),
      );
    notes.append(metric(p));
    notes.addEventListener("toggle", () => {
      if (!notes.open || notes.querySelector("picture")) return;
      for (const img of p.images) {
        notes.append(picture(img, "(max-width:700px) 92vw, 40vw"));
      }
    });
    details.append(notes);
    row.append(visual, details);
    collection.append(row);
  });
}
function compare(data) {
  collection.className = "versions";
  const entries = [
    [
      "Gallery",
      "v1-gallery",
      "Browse the image collection. Select a project to look closer.",
    ],
    [
      "Narrative",
      "v2-narrative",
      "Trace the choices behind each outcome, chapter by chapter.",
    ],
    [
      "Process",
      "v3-process",
      "Watch each study take shape in four short stages.",
    ],
  ];
  entries.forEach(([name, dir, copy], i) => {
    const a = el("a", "version");
    a.href = asset(dir + "/");
    a.append(
      picture(data[i].hero, "(max-width:700px) 92vw, 32vw", i === 0),
      el("h2", "", name),
      el("p", "", copy),
      el("span", "view-link", "Explore " + name.toLowerCase() + " →"),
    );
    collection.append(a);
  });
}
try {
  ({ compare, gallery, narrative, process: processView })[
    document.body.dataset.view
  ](await projects());
} catch (error) {
  const msg = el(
    "p",
    "",
    "The project collection could not be loaded. Please reload the page.",
  );
  msg.setAttribute("role", "alert");
  collection.append(msg);
  console.error(error);
} finally {
  collection.setAttribute("aria-busy", "false");
}
