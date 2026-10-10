(() => {
  'use strict';
  const root = document.documentElement;
  const themes = ['theme1', 'theme3', 'theme4', 'theme5'];
  const key = 'vsc-site-theme-v2';
  const cookieKey = 'vsc_site_theme_v2';
  const dark = matchMedia('(prefers-color-scheme: dark)');
  const readSharedTheme = () => {
    try {
      const prefix = `${cookieKey}=`;
      const cookie = document.cookie.split('; ').find(value => value.startsWith(prefix));
      return cookie ? decodeURIComponent(cookie.slice(prefix.length)) : '';
    } catch {
      return '';
    }
  };
  const saveTheme = value => {
    try { localStorage.setItem(key, value); } catch {}
    try {
      const domain = /(^|\.)vanshea\.com$/i.test(location.hostname) ? '; Domain=.vanshea.com' : '';
      const secure = location.protocol === 'https:' ? '; Secure' : '';
      document.cookie = `${cookieKey}=${encodeURIComponent(value)}; Path=/; Max-Age=31536000; SameSite=Lax${domain}${secure}`;
    } catch {}
  };
  let theme = dark.matches ? 'theme1' : 'theme4';
  try { theme = [readSharedTheme(), localStorage.getItem(key), localStorage.getItem('vsc-site-theme')].find(value => themes.includes(value)) || theme; } catch {}
  const applyTheme = value => {
    theme = themes.includes(value) ? value : 'theme4';
    root.dataset.theme = theme;
    const isDark = theme === 'theme3' || (theme === 'theme1' && dark.matches);
    document.querySelectorAll('[data-theme-favicon]').forEach(link => {
      link.media = link.dataset.themeFavicon === (isDark ? 'dark' : 'light') ? 'all' : 'not all';
    });
    document.querySelectorAll('input[name="color-theme"]').forEach(input => {
      input.checked = input.value === theme;
    });
    const themeSelect = document.querySelector('select[name="theme"]');
    if (themeSelect) themeSelect.value = theme;
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', getComputedStyle(root).getPropertyValue('--bg').trim());
  };
  applyTheme(theme);
  saveTheme(theme);
  dark.addEventListener('change', () => applyTheme(theme));
  addEventListener('storage', event => { if (event.key === key) applyTheme(event.newValue); });

  const initialize = () => {
    root.classList.add('js');
    // Keep localhost /build/ review inside its static tree; production stays root-relative.
    if (location.pathname.startsWith('/build/')) {
      document.querySelectorAll('a[href^="/"]').forEach(link => {
        const href = link.getAttribute('href');
        if (!/^\/(?:build|blog|api)(?:\/|$)/.test(href) && !href.startsWith('//')) {
          link.setAttribute('href', '/build' + href);
        }
      });
    }
    applyTheme(theme);
    const header = document.querySelector('.home-header');
    const syncHeader = () => header?.classList.toggle('is-scrolled', scrollY > 24);
    addEventListener('scroll', syncHeader, { passive: true });
    syncHeader();
    const introTarget = document.getElementById('human-agency');
    document.querySelectorAll('a[href="#human-agency"]').forEach(link => {
      link.addEventListener('click', event => {
        if (!introTarget) return;
        event.preventDefault();
        const headerHeight = header?.getBoundingClientRect().height || 0;
        const targetTop = window.scrollY + introTarget.getBoundingClientRect().top;
        introTarget.focus({ preventScroll: true });
        if (window.location.hash !== '#human-agency') {
          window.history.pushState(null, '', '#human-agency');
        }
        window.scrollTo({
          top: Math.max(0, targetTop - headerHeight - 18),
          behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        });
      });
    });
    document.querySelector('.theme-control')?.addEventListener('change', event => {
      if (event.target.matches('input[name="color-theme"],select[name="theme"]')) {
        applyTheme(event.target.value);
        saveTheme(theme);
      }
    });
    const menu = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.home-nav');
    const closeMenu = () => { nav?.classList.remove('is-open'); menu?.setAttribute('aria-expanded', 'false'); menu?.setAttribute('aria-label', 'Open menu'); if (menu) menu.textContent = 'Menu'; };
    menu?.addEventListener('click', () => {
      const open = menu.getAttribute('aria-expanded') !== 'true';
      nav.classList.toggle('is-open', open);
      menu.setAttribute('aria-expanded', String(open));
      menu.textContent = open ? 'Close' : 'Menu';
      menu.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
    nav?.addEventListener('click', event => { if (event.target.closest('a')) closeMenu(); });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && menu?.getAttribute('aria-expanded') === 'true') { closeMenu(); menu.focus(); }
    });
    matchMedia('(min-width:701px)').addEventListener('change', closeMenu);

    document.querySelectorAll('[data-recommendations-carousel]').forEach(carousel => {
      const track = carousel.querySelector('[data-recommendations-track]');
      const cards = [...(track?.querySelectorAll('.recommendation-card') || [])];
      const previous = carousel.querySelector('[data-recommendations-previous]');
      const next = carousel.querySelector('[data-recommendations-next]');
      const status = carousel.querySelector('[data-recommendations-status]');
      const progress = carousel.querySelector('[data-recommendations-progress]');
      const mobile = matchMedia('(max-width:700px)');
      const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
      let current = 0;
      let scrollTimer = 0;
      if (!track || !cards.length || !previous || !next) return;

      const updateControls = () => {
        previous.disabled = current === 0;
        next.disabled = current === cards.length - 1;
        if (status) status.textContent = `Slide ${current + 1} of ${cards.length}`;
        if (progress) progress.style.transform = `scaleX(${(current + 1) / cards.length})`;
      };
      const sync = () => {
        const trackLeft = track.getBoundingClientRect().left;
        current = cards.reduce((closest, card, index) => {
          const distance = Math.abs(card.getBoundingClientRect().left - trackLeft);
          const closestDistance = Math.abs(cards[closest].getBoundingClientRect().left - trackLeft);
          return distance < closestDistance ? index : closest;
        }, 0);
        updateControls();
      };
      const scheduleSync = () => {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(sync, 120);
      };
      const move = direction => {
        sync();
        const destination = Math.max(0, Math.min(cards.length - 1, current + direction));
        const left = cards[destination].offsetLeft - track.offsetLeft;
        track.scrollTo({ left, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
        current = destination;
        updateControls();
      };
      previous.addEventListener('click', () => move(-1));
      next.addEventListener('click', () => move(1));
      track.addEventListener('scroll', scheduleSync, { passive: true });
      track.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft') { event.preventDefault(); move(-1); }
        if (event.key === 'ArrowRight') { event.preventDefault(); move(1); }
      });
      mobile.addEventListener('change', () => {
        track.tabIndex = mobile.matches ? 0 : -1;
        if (!mobile.matches) track.scrollLeft = 0;
        sync();
      });
      track.tabIndex = mobile.matches ? 0 : -1;
      sync();
    });

    const stage = document.querySelector('.footer-wave');
    const control = stage?.querySelector('.footer-wave-toggle');
    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)');
    let wavePlaying = !reduceMotion.matches;
    const syncWave = () => {
      stage?.classList.toggle('is-paused', !wavePlaying);
      if (control) {
        control.textContent = wavePlaying ? 'Pause wave' : 'Play wave';
        control.setAttribute('aria-label', wavePlaying ? 'Pause footer wave animation' : 'Play footer wave animation');
        control.setAttribute('aria-pressed', String(wavePlaying));
      }
    };
    const toggleWave = () => { wavePlaying = !wavePlaying; syncWave(); };
    control?.addEventListener('click', event => { event.stopPropagation(); toggleWave(); });
    stage?.addEventListener('click', event => {
      if (!event.target.closest('.footer-wave-toggle')) toggleWave();
    });
    reduceMotion.addEventListener('change', event => {
      if (event.matches) wavePlaying = false;
      syncWave();
    });
    syncWave();

    const labStrip = document.querySelector('.lab-strip');
    const labPreview = labStrip?.querySelector('[data-lab-preview]');
    const labFrame = labStrip?.querySelector('[data-lab-frame]');
    const labCaption = labStrip?.querySelector('[data-lab-caption]');
    const labIndex = labStrip?.querySelector('[data-lab-index]');
    const mobileLab = matchMedia('(max-width:700px)');
    const fitLabPreview = () => {
      if (!labFrame || !labFrame.clientWidth) return;
      labFrame.style.setProperty('--lab-scale', String(labFrame.clientWidth / 720));
    };
    const updateLabPreview = idea => {
      if (!labPreview || !idea) return;
      labPreview.title = idea.dataset.alt || `${idea.dataset.title || 'AI Design'} interactive prototype`;
      if (mobileLab.matches) {
        labPreview.removeAttribute('src');
        return;
      }
      if (idea.dataset.demo && labPreview.getAttribute('src') !== idea.dataset.demo) {
        labPreview.style.opacity = '0';
        labPreview.setAttribute('src', idea.dataset.demo);
      }
    };
    if (labFrame && labPreview) {
      fitLabPreview();
      if ('ResizeObserver' in window) new ResizeObserver(fitLabPreview).observe(labFrame);
      else addEventListener('resize', fitLabPreview, { passive: true });
      labPreview.addEventListener('load', () => { labPreview.style.opacity = '1'; });
      updateLabPreview(labStrip.querySelector('[data-lab-idea][open]'));
      mobileLab.addEventListener('change', () => {
        updateLabPreview(labStrip.querySelector('[data-lab-idea][open]'));
      });
    }
    labStrip?.querySelectorAll('[data-lab-idea]').forEach(idea => {
      idea.addEventListener('toggle', () => {
        if (!idea.open) return;
        labStrip.querySelectorAll('[data-lab-idea]').forEach(other => {
          if (other !== idea) other.open = false;
        });
        updateLabPreview(idea);
        if (labCaption) labCaption.textContent = idea.dataset.title || '';
        if (labIndex) labIndex.textContent = `${idea.dataset.index || ''} / 04`;
      });
    });

  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
})();
