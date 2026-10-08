(() => {
  const W = 200, D = 200, H = 90, G0 = 80, ZT = H + 22, PY = D * 0.62, LZ = H + 200;
  const C30 = 0.8660254, S30 = 0.5, TOTAL = 38.5;
  const FONT = "'Archivo', system-ui, sans-serif";
  const MONO = "'IBM Plex Mono', ui-monospace, monospace";
  const SCENES = [
    { name: 'Silos', dur: 7, desc: 'Four department blocks rise, each reporting healthy KPIs' },
    { name: 'Customer', dur: 8, desc: 'A customer path crosses the blocks and drops into every gap between teams' },
    { name: 'Cost', dur: 8, desc: 'A cohort panel quantifies how many customers are lost at each handoff' },
    { name: 'Map', dur: 9, desc: 'Gaps close into one platform; the path becomes an emotion curve with owned dips' },
    { name: 'Close', dur: 6.5, desc: 'Headline lands, then the scene settles back to an empty grid for the loop' }
  ];
  const CUES = { Silos: 0, Customer: 7, Cost: 15, Map: 23, Close: 32, END: TOTAL };
  const PAL = {
    light: { bg: '#F4F3EF', ink: '#121212', top: '#FFFFFF', left: '#D6D5D0', right: '#1C1C1C', grid: '#E1E0DA', mute: '#64635E', gain: '#176B3A', loss: '#B42318' },
    dark: { bg: '#0F0F0F', ink: '#F1F0EC', top: '#1A1A1A', left: '#383836', right: '#E8E7E2', grid: '#232322', mute: '#9C9B95', gain: '#66D99B', loss: '#FF7777' }
  };
  const DEPTS = [
    { dept: 'MARKETING', metric: '+18%', sub: 'lead volume', stage: 'DISCOVER', bars: [34, 48, 66] },
    { dept: 'SALES', metric: '31%', sub: 'win rate', stage: 'BUY', bars: [40, 52, 60] },
    { dept: 'ONBOARDING', metric: '97%', sub: 'SLA met', stage: 'SET UP', bars: [56, 62, 70] },
    { dept: 'SUPPORT', metric: '4.4/5', sub: 'CSAT', stage: 'GET HELP', bars: [44, 58, 64] }
  ];
  const COHORT = [1000, 720, 540, 410];
  const DROPS = [
    { pct: '−28%', n: '−280', from: 'Marketing', to: 'Sales' },
    { pct: '−25%', n: '−180', from: 'Sales', to: 'Onboarding' },
    { pct: '−24%', n: '−130', from: 'Onboarding', to: 'Support' }
  ];
  const DIPS = [55, 70, 45];
  const COPY_DEFAULTS = {
    caption1: 'Teams may report *gains*.',
    caption2: 'But I know customers don’t experience departments.',
    caption3: 'They remember the *gaps* between them.',
    caption4: 'Every handoff leaks value teams try to downplay..',
    caption5: 'Service design puts the whole journey in one view.',
    caption6: 'Every *dip* gets an owner, a metric and a budget.',
    kicker: 'CUSTOMER JOURNEY MAPPING',
    headline: 'Your customers live one journey.',
    subhead: 'Lead it as one.'
  };
  const ATTRS = Object.keys(COPY_DEFAULTS).concat(['accent', 'theme']);

  const clamp = v => Math.max(0, Math.min(1, v));
  const enter = v => 1 - Math.pow(1 - v, 3);
  const draw = v => -(Math.cos(Math.PI * v) - 1) / 2;
  const move = v => v < 0.5 ? 4 * v * v * v : 1 - Math.pow(-2 * v + 2, 3) / 2;
  const prog = (T, a, b) => clamp((T - a) / (b - a));
  const tw = (T, a, b, from, to, ease = move) => from + (to - from) * ease(prog(T, a, b));
  const keys = (T, k) => {
    let v = k[0][1];
    for (let i = 1; i < k.length; i++) v += (k[i][1] - k[i - 1][1]) * move(prog(T, k[i - 1][0], k[i][0]));
    return v;
  };
  const fade = (T, a, b, d = 0.5) => Math.min(enter(prog(T, a, a + d)), 1 - enter(prog(T, b - d, b)));
  const iso = (x, y, z) => [(x - y) * C30, (x + y) * S30 - z];
  const points = a => a.map(p => iso(p[0], p[1], p[2]).map(n => n.toFixed(1)).join(',')).join(' ');
  const svgNS = 'http://www.w3.org/2000/svg';
  const htmlNS = 'http://www.w3.org/1999/xhtml';
  const svgEl = (name, attrs = {}, parent) => {
    const el = document.createElementNS(svgNS, name);
    for (const [key, value] of Object.entries(attrs)) el.setAttribute(key, String(value));
    parent?.appendChild(el);
    return el;
  };
  const setText = (node, value) => { if (node.textContent !== value) node.textContent = value; };
  const setAttr = (node, name, value) => {
    const v = String(value);
    if (node.getAttribute(name) !== v) node.setAttribute(name, v);
  };
  const richHtml = (node, value, accent, palette) => {
    node.replaceChildren();
    const parts = String(value || '').split('*');
    parts.forEach((part, i) => {
      const span = document.createElementNS(htmlNS, 'span');
      span.textContent = part;
      if (i % 2) {
        const word = part.trim().toLowerCase();
        const color = /^gains?\b/.test(word) ? palette.gain : /^(gaps?|dips?)\b/.test(word) ? palette.loss : accent;
        span.setAttribute('style', `color:${color}`);
      }
      node.appendChild(span);
    });
  };
  const richSvg = (node, value, accent) => {
    node.replaceChildren();
    String(value || '').split('*').forEach((part, i) => {
      const span = svgEl('tspan', i % 2 ? { fill: accent } : {}, node);
      span.textContent = part;
    });
  };
  const safeAccent = value => /^#[\da-f]{3}(?:[\da-f]{3})?$/i.test(value || '') ? value : '#2F5BEA';
  const makeText = (parent, attrs, value = '') => {
    const n = svgEl('text', attrs, parent);
    n.textContent = value;
    return n;
  };

  class JourneyMapAnimation extends HTMLElement {
    static get observedAttributes() { return ATTRS; }

    constructor() {
      super();
      this.attachShadow({ mode: 'open' });
      this._elapsed = 0;
      this._startAt = 0;
      this._raf = 0;
      this._running = false;
      this._inView = false;
      this._docVisible = !document.hidden;
      this._reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
      this._lastCaption = '';
      this._nodes = null;
      this._onVisibility = () => {
        this._docVisible = !document.hidden;
        this._syncPlayback();
      };
      this._onReducedMotion = () => this._syncPlayback();
      this._tick = now => {
        if (!this._running) return;
        this._elapsed = ((now - this._startAt) / 1000) % TOTAL;
        this._renderFrame(this._reduced.matches ? 31 : this._elapsed);
        this._raf = requestAnimationFrame(this._tick);
      };
    }

    connectedCallback() {
      if (!this._nodes) this._build();
      document.addEventListener('visibilitychange', this._onVisibility);
      if (this._reduced.addEventListener) this._reduced.addEventListener('change', this._onReducedMotion);
      else this._reduced.addListener(this._onReducedMotion);
      if ('IntersectionObserver' in window) {
        this._observer = new IntersectionObserver(entries => {
          this._inView = entries.some(entry => entry.isIntersecting && entry.intersectionRatio > 0);
          this._syncPlayback();
        }, { threshold: 0.01 });
        this._observer.observe(this);
      } else {
        this._inView = true;
      }
      this._renderFrame(this._reduced.matches ? 31 : this._elapsed);
      this._syncPlayback();
    }

    disconnectedCallback() {
      document.removeEventListener('visibilitychange', this._onVisibility);
      if (this._reduced.removeEventListener) this._reduced.removeEventListener('change', this._onReducedMotion);
      else this._reduced.removeListener(this._onReducedMotion);
      this._observer?.disconnect();
      this._stopPlayback();
    }

    attributeChangedCallback() {
      if (!this._nodes) return;
      this._updateCopyAndPalette();
      this._renderFrame(this._reduced.matches ? 31 : this._elapsed);
    }

    _copy() {
      const copy = {};
      for (const name of Object.keys(COPY_DEFAULTS)) copy[name] = this.getAttribute(name) ?? COPY_DEFAULTS[name];
      return copy;
    }

    _palette() {
      const theme = (this.getAttribute('theme') || 'Dark').toLowerCase();
      return PAL[theme] || PAL.dark;
    }

    _syncPlayback() {
      if (!this._nodes) return;
      if (this._reduced.matches) {
        this._stopPlayback();
        this._renderFrame(31);
        return;
      }
      if (this._inView && this._docVisible) {
        if (!this._running) {
          this._running = true;
          this._startAt = performance.now() - this._elapsed * 1000;
          this._raf = requestAnimationFrame(this._tick);
        }
      } else {
        this._stopPlayback();
      }
    }

    _stopPlayback() {
      if (this._running) {
        this._elapsed = ((performance.now() - this._startAt) / 1000) % TOTAL;
        this._running = false;
      }
      if (this._raf) cancelAnimationFrame(this._raf);
      this._raf = 0;
    }

    _build() {
      this.shadowRoot.innerHTML = `<style>
        :host{display:block;width:100%;max-width:1920px;margin-inline:auto;aspect-ratio:16/9;contain:layout paint style}
        .frame{position:relative;width:100%;height:100%;aspect-ratio:16/9;overflow:hidden;background:transparent}
        svg{display:block;width:100%;height:100%;aspect-ratio:16/9}
        .sr-only{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
        @media(max-width:1000px){.frame svg{position:absolute;left:-12.5%;top:-12.5%;width:125%;height:125%;max-width:none}}
        @media(max-width:640px){svg [data-viewbox-background],svg [data-explanation-caption]{display:none!important}}
      </style><div class="frame"><div class="sr-only" aria-live="polite" aria-atomic="true" data-live></div><div role="img" data-image><svg viewBox="0 0 1920 1080" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false"></svg></div></div>`;
      const image = this.shadowRoot.querySelector('[data-image]');
      this._image = image;
      this._live = this.shadowRoot.querySelector('[data-live]');
      this._svg = this.shadowRoot.querySelector('svg');
      this._svg.setAttribute('width', '1920');
      this._svg.setAttribute('height', '1080');
      this._svg.setAttribute('xmlns', svgNS);
      this._bg = svgEl('rect', { x: 0, y: 0, width: 1920, height: 1080, 'data-viewbox-background': '' }, this._svg);
      this._camera = svgEl('g', {}, this._svg);
      this._grid = svgEl('g', { fill: 'none' }, this._camera);
      this._buildGrid();
      this._world = svgEl('g', {}, this._camera);
      this._blocks = this._buildBlocks();
      this._lanes = this._buildLanes();
      this._pathGroup = svgEl('g', { fill: 'none', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, this._world);
      this._pathLines = Array.from({ length: 260 }, () => svgEl('polyline', {}, this._pathGroup));
      this._head = svgEl('g', { visibility: 'hidden' }, this._pathGroup);
      this._headRing = svgEl('circle', { r: 14 }, this._head);
      this._headDot = svgEl('circle', { r: 5 }, this._head);
      this._dropNodes = this._buildDrops();
      this._dipNodes = this._buildDips();
      this._labelNodes = this._buildLabels();
      this._panel = this._buildPanel();
      this._caption = svgEl('foreignObject', { x: 120, y: 84, width: 1680, height: 120, opacity: 0, 'data-explanation-caption': '' }, this._svg);
      this._captionDiv = document.createElementNS(htmlNS, 'div');
      this._captionDiv.setAttribute('style', `font-family:${FONT};font-weight:600;font-size:50px;letter-spacing:-0.015em;color:#121212;line-height:1.18;`);
      this._caption.appendChild(this._captionDiv);
      this._closing = svgEl('foreignObject', { x: 120, y: 150, width: 1000, height: 600, opacity: 0 }, this._svg);
      this._closingDiv = document.createElementNS(htmlNS, 'div');
      this._closingDiv.setAttribute('style', `font-family:${FONT};color:#121212;`);
      this._kickerDiv = document.createElementNS(htmlNS, 'div');
      this._kickerDiv.setAttribute('style', `font-family:${MONO};font-size:22px;letter-spacing:.1em;color:#64635E;margin-bottom:28px;`);
      this._headlineDiv = document.createElementNS(htmlNS, 'div');
      this._headlineDiv.setAttribute('style', `font-family:${FONT};font-weight:600;font-size:92px;line-height:1.02;letter-spacing:-.025em;text-wrap:balance;`);
      this._subheadDiv = document.createElementNS(htmlNS, 'div');
      this._subheadDiv.setAttribute('style', `font-family:${FONT};font-weight:400;font-size:92px;line-height:1.02;letter-spacing:-.025em;color:#64635E;margin-top:6px;text-wrap:balance;`);
      this._rule = document.createElementNS(htmlNS, 'div');
      this._rule.setAttribute('style', 'height:4px;width:0px;margin-top:40px;background:#2F5BEA;');
      this._closingDiv.append(this._kickerDiv, this._headlineDiv, this._subheadDiv, this._rule);
      this._closing.appendChild(this._closingDiv);
      this._nodes = { image };
      this._updateCopyAndPalette();
    }

    _buildGrid() {
      const group = this._grid;
      for (let v = -400; v <= 700; v += 50) svgEl('polyline', { points: points([[-500, v, 0], [1500, v, 0]]) }, group);
      for (let v = -500; v <= 1500; v += 50) svgEl('polyline', { points: points([[v, -400, 0], [v, 700, 0]]) }, group);
    }

    _newBox(parent, sw = 2) {
      const group = svgEl('g', { 'stroke-linejoin': 'round' }, parent);
      const left = svgEl('polygon', { 'stroke-width': sw }, group);
      const right = svgEl('polygon', { 'stroke-width': sw }, group);
      const top = svgEl('polygon', { 'stroke-width': sw }, group);
      return { group, left, right, top, sw };
    }

    _drawBox(box, x, y, z, w, d, h, P, sw = box.sw) {
      const top = [[x, y, z + h], [x + w, y, z + h], [x + w, y + d, z + h], [x, y + d, z + h]];
      setAttr(box.group, 'stroke', P.ink);
      setAttr(box.left, 'fill', P.left); setAttr(box.right, 'fill', P.right); setAttr(box.top, 'fill', P.top);
      if (h < 0.5) {
        setAttr(box.top, 'points', points(top));
        box.left.setAttribute('display', 'none'); box.right.setAttribute('display', 'none');
        box.top.removeAttribute('display');
        return;
      }
      box.left.removeAttribute('display'); box.right.removeAttribute('display'); box.top.removeAttribute('display');
      setAttr(box.left, 'points', points([[x, y + d, z], [x + w, y + d, z], [x + w, y + d, z + h], [x, y + d, z + h]]));
      setAttr(box.right, 'points', points([[x + w, y, z], [x + w, y + d, z], [x + w, y + d, z + h], [x + w, y, z + h]]));
      setAttr(box.top, 'points', points(top));
      [box.left, box.right, box.top].forEach(p => setAttr(p, 'stroke-width', sw));
    }

    _buildBlocks() {
      return DEPTS.map(() => {
        const group = svgEl('g', {}, this._world);
        return { group, block: this._newBox(group), bars: Array.from({ length: 3 }, () => this._newBox(group, 1.6)) };
      });
    }

    _buildLanes() {
      const group = svgEl('g', { opacity: 0 }, this._world);
      [66, 133].forEach(v => svgEl('line', { x1: 0, y1: v, x2: 4 * W, y2: v, stroke: '#121212', 'stroke-width': 1, opacity: 0.35 }, group));
      [1, 2, 3].forEach(k => svgEl('line', { x1: k * W, y1: 0, x2: k * W, y2: D, stroke: '#121212', 'stroke-width': 1.2, 'stroke-dasharray': '5 6' }, group));
      ['DOING', 'THINKING', 'FEELING'].forEach((t, j) => {
        makeText(group, { x: 12, y: j * 66.6 + 38, 'font-family': MONO, 'font-size': 15, fill: '#64635E', 'letter-spacing': '0.1em' }, t);
      });
      [0, 1, 2, 3].forEach(k => [0, 1].forEach(j => svgEl('rect', { x: k * W + 150, y: j * 66.6 + 26, width: 14, height: 14, fill: 'none', stroke: '#121212', 'stroke-width': 1.5 }, group)));
      return group;
    }

    _buildDrops() {
      return DROPS.map(() => {
        const group = svgEl('g', { opacity: 0 }, this._world);
        const line = svgEl('line', { stroke: '#2F5BEA', 'stroke-width': 1.5, 'stroke-dasharray': '4 5' }, group);
        const pct = makeText(group, { 'font-family': MONO, 'font-weight': 600, 'font-size': 30, fill: '#B42318' });
        const names = makeText(group, { 'font-family': MONO, 'font-size': 17, fill: '#64635E' });
        return { group, line, pct, names };
      });
    }

    _buildDips() {
      return DROPS.map(() => {
        const group = svgEl('g', { opacity: 0 }, this._world);
        const ring = svgEl('circle', { r: 13, fill: 'none', stroke: '#B42318', 'stroke-width': 2.5 }, group);
        const dot = svgEl('circle', { r: 4.5, fill: '#B42318' }, group);
        const line = svgEl('line', { stroke: '#B42318', 'stroke-width': 1.5 }, group);
        const number = makeText(group, { 'font-family': MONO, 'font-weight': 600, 'font-size': 22, fill: '#B42318' });
        const label = makeText(group, { 'font-family': MONO, 'font-size': 17, fill: '#64635E' });
        return { group, ring, dot, line, number, label };
      });
    }

    _buildLabels() {
      return DEPTS.map(() => {
        const group = svgEl('g', { opacity: 0 }, this._world);
        const line = svgEl('line', { stroke: '#121212', 'stroke-width': 1.5 }, group);
        const dot = svgEl('circle', { r: 4, fill: '#121212' }, group);
        const dept = makeText(group, { 'font-family': MONO, 'font-size': 19, fill: '#64635E', 'letter-spacing': '0.08em' });
        const metric = makeText(group, { 'font-family': FONT, 'font-weight': 600, 'font-size': 44, fill: '#121212' });
        const stage = makeText(group, { 'font-family': FONT, 'font-weight': 600, 'font-size': 44, fill: '#121212' });
        const sub = makeText(group, { 'font-family': MONO, 'font-size': 18, fill: '#64635E' });
        const stageNo = makeText(group, { 'font-family': MONO, 'font-size': 18, fill: '#64635E' });
        return { group, line, dot, dept, metric, stage, sub, stageNo };
      });
    }

    _buildPanel() {
      const group = svgEl('g', { opacity: 0 }, this._svg);
      const heading = makeText(group, { x: 0, y: 0, 'font-family': MONO, 'font-size': 18, fill: '#64635E', 'letter-spacing': '0.08em' }, 'PER 1,000 CUSTOMERS · ILLUSTRATIVE');
      const rule = svgEl('line', { x1: 0, y1: 22, x2: 560, y2: 22, stroke: '#121212', 'stroke-width': 1.5 }, group);
      const rows = DEPTS.map(() => {
        const row = svgEl('g', { opacity: 0 }, group);
        const stage = makeText(row, { x: 0, y: 0, 'font-family': MONO, 'font-size': 18, fill: '#121212', 'letter-spacing': '0.06em' });
        const bar = svgEl('rect', { x: 130, y: 0, width: 0, height: 24, fill: '#121212' }, row);
        const lost = svgEl('rect', { y: 0, width: 0, height: 24, fill: 'none', stroke: '#2F5BEA', 'stroke-width': 2, 'stroke-dasharray': '4 3' }, row);
        const number = makeText(row, { x: 560, y: 0, 'text-anchor': 'end', 'font-family': FONT, 'font-weight': 600, 'font-size': 28, fill: '#121212' });
        return { row, stage, bar, lost, number };
      });
      const pct = makeText(group, { x: -6, y: 470, 'font-family': FONT, 'font-weight': 600, 'font-size': 150, fill: '#B42318', 'letter-spacing': '-0.03em' });
      const line1 = makeText(group, { x: 0, y: 520, 'font-family': FONT, 'font-size': 28, fill: '#121212' }, 'of customers are lost in handoffs');
      const line2 = makeText(group, { x: 0, y: 556, 'font-family': FONT, 'font-size': 28, fill: '#121212' }, 'that no single team owns.');
      return { group, heading, rule, rows, pct, line1, line2 };
    }

    _updateCopyAndPalette() {
      this._P = this._palette();
      this._A = safeAccent(this.getAttribute('accent') || '#2F5BEA');
      this._Q = this._copy();
      this._image?.setAttribute('aria-label', 'An animated isometric journey map shows separate team metrics, customer drop-off at handoffs, and a connected journey with owned experience dips.');
      if (!this._svg) return;
      setAttr(this._bg, 'fill', this._P.bg);
      this._captionDiv.setAttribute('style', `font-family:${FONT};font-weight:600;font-size:50px;letter-spacing:-0.015em;color:${this._P.ink};line-height:1.18;`);
      this._kickerDiv.setAttribute('style', `font-family:${MONO};font-size:22px;letter-spacing:.1em;color:${this._P.mute};margin-bottom:28px;`);
      this._headlineDiv.setAttribute('style', `font-family:${FONT};font-weight:600;font-size:92px;line-height:1.02;letter-spacing:-.025em;text-wrap:balance;color:${this._P.ink};`);
      this._subheadDiv.setAttribute('style', `font-family:${FONT};font-weight:400;font-size:92px;line-height:1.02;letter-spacing:-.025em;color:${this._P.mute};margin-top:6px;text-wrap:balance;`);
      this._rule.setAttribute('style', `height:4px;width:0px;margin-top:40px;background:${this._A};`);
      richHtml(this._headlineDiv, this._Q.headline, this._A, this._P);
      richHtml(this._subheadDiv, this._Q.subhead, this._A, this._P);
      this._visualCaptionKey = '';
      setText(this._kickerDiv, this._Q.kicker);
      this._buildLanesPalette();
      this._renderFrame(this._reduced.matches ? 31 : this._elapsed);
    }

    _buildLanesPalette() {
      if (!this._lanes) return;
      this._lanes.querySelectorAll('line, text, rect').forEach(node => {
        if (node.tagName.toLowerCase() === 'line') setAttr(node, 'stroke', this._P.ink);
        else if (node.tagName.toLowerCase() === 'text') setAttr(node, 'fill', this._P.mute);
        else setAttr(node, 'stroke', this._P.ink);
      });
    }

    _renderFrame(T) {
      if (!this._nodes || !Number.isFinite(T)) return;
      T = ((T % TOTAL) + TOTAL) % TOTAL;
      const P = this._P, A = this._A;
      const S = CUES.Silos, C = CUES.Customer, K = CUES.Cost, M = CUES.Map, E = CUES.Close, END = CUES.END;
      const G = tw(T, M + 0.4, M + 2.4, G0, 0);
      const L = 4 * W + 3 * G;
      const morph = tw(T, M + 2.4, M + 4.6, 0, 1);
      const outro = 1 - move(prog(T, E + 3.6, E + 5.6));
      const ox = keys(T, [[0, 672], [K, 672], [K + 1.6, 420], [M + 0.2, 420], [M + 2.4, 760], [E, 760], [E + 2.4, 900]]);
      const oy = keys(T, [[0, 455], [E, 455], [E + 2.4, 560]]);
      const sc = keys(T, [[0, 0.74], [S + 6, 0.8], [K, 0.8], [K + 1.6, 0.78], [M + 0.2, 0.78], [M + 2.4, 0.82], [E, 0.82], [E + 6.5, 0.74]]);
      setAttr(this._camera, 'transform', `translate(${ox.toFixed(2)} ${oy.toFixed(2)}) scale(${sc.toFixed(4)})`);
      setAttr(this._grid, 'stroke', P.grid);
      setAttr(this._grid, 'stroke-width', 1.2);
      setAttr(this._grid, 'opacity', fade(T, S, END, 1.2));
      setAttr(this._world, 'opacity', outro);
      const pathP = draw(prog(T, C + 0.8, C + 6.6));
      const samples = [];
      for (let i = 0; i <= 260; i++) {
        const s = (i / 260) * L;
        let z = ZT, gap = false;
        for (let k = 1; k <= 3; k++) {
          const gs = k * W + (k - 1) * G;
          if (G > 0.5 && s > gs && s < gs + G) {
            gap = true;
            z = ZT - (ZT - 8) * Math.pow(Math.sin(Math.PI * (s - gs) / G), 0.55);
          }
        }
        const u = s / (W + G);
        const emo = 10 + 30 * (u / 4) - DIPS.reduce((sum, a, k) => sum + a * Math.exp(-Math.pow((u - (k + 1)) / 0.16, 2)), 0);
        z += morph * (70 + emo);
        const dip = morph > 0.4 && [1, 2, 3].some(k => Math.abs(u - k) < 0.13);
        samples.push({ s, z, hot: gap || dip });
      }
      const head = pathP * L;
      const runs = [];
      let cur = null;
      for (const p of samples) {
        if (p.s > head) break;
        if (!cur || cur.hot !== p.hot) {
          if (cur) cur.pts.push(p);
          cur = { hot: p.hot, pts: [p] };
          runs.push(cur);
        } else cur.pts.push(p);
      }
      const hi = samples.findIndex(p => p.s >= head);
      const hp = samples[Math.max(0, hi)] || samples[260];
      const [hx, hy] = iso(Math.min(head, L), PY, hp.z);
      const pathO = fade(T, C + 0.4, E + 4.2, 0.6) * outro;
      setAttr(this._pathGroup, 'opacity', pathO);
      runs.forEach((r, i) => {
        const line = this._pathLines[i];
        setAttr(line, 'points', r.pts.map(p => iso(p.s, PY, p.z).map(n => n.toFixed(1)).join(',')).join(' '));
        setAttr(line, 'stroke', r.hot ? P.loss : P.ink);
        setAttr(line, 'stroke-width', r.hot ? 4 : 3.2);
        line.removeAttribute('display');
      });
      for (let i = runs.length; i < this._pathLines.length; i++) this._pathLines[i].setAttribute('display', 'none');
      const headVisible = pathP > 0 && pathP < 1;
      setAttr(this._head, 'visibility', headVisible ? 'visible' : 'hidden');
      setAttr(this._headRing, 'cx', hx); setAttr(this._headRing, 'cy', hy); setAttr(this._headRing, 'fill', P.bg); setAttr(this._headRing, 'stroke', P.ink); setAttr(this._headRing, 'stroke-width', 3);
      setAttr(this._headDot, 'cx', hx); setAttr(this._headDot, 'cy', hy); setAttr(this._headDot, 'fill', P.ink);

      DEPTS.forEach((d, i) => {
        const x = i * (W + G);
        const h = H * enter(prog(T, S + 0.6 + i * 0.35, S + 1.9 + i * 0.35)) * (0.0001 + outro);
        const barK = enter(prog(T, S + 2.2 + i * 0.3, S + 3.4 + i * 0.3)) * (1 - move(prog(T, M + 0.1, M + 1.0)));
        this._drawBox(this._blocks[i].block, x, 0, 0, W, D, h, P);
        d.bars.forEach((b, j) => this._drawBox(this._blocks[i].bars[j], x + 28 + j * 54, 26, h, 34, 34, b * barK, P, 1.6));
      });
      const lanesO = enter(prog(T, M + 2.0, M + 3.0)) * outro;
      setAttr(this._lanes, 'transform', `matrix(${C30} ${S30} ${-C30} ${S30} 0 ${-H})`);
      setAttr(this._lanes, 'opacity', lanesO);

      this._dropNodes.forEach((n, k) => {
        const xc = (k + 1) * W + k * G + G / 2;
        const o = Math.min(enter(prog(head, xc - 10, xc + 60)), 1 - enter(prog(T, M + 0.2, M + 1.0))) * (pathP > 0 ? 1 : 0);
        setAttr(n.group, 'opacity', o);
        const [gx, gy] = iso(xc, PY, 8), [bx, by] = iso(xc - 30, D + 110, 0);
        setAttr(n.line, 'x1', gx); setAttr(n.line, 'y1', gy); setAttr(n.line, 'x2', bx); setAttr(n.line, 'y2', by); setAttr(n.line, 'stroke', P.loss);
        setAttr(n.pct, 'x', bx - 8); setAttr(n.pct, 'y', by + 34); setAttr(n.pct, 'fill', P.loss); setText(n.pct, DROPS[k].pct);
        setAttr(n.names, 'x', bx - 8); setAttr(n.names, 'y', by + 60); setAttr(n.names, 'fill', P.mute); setText(n.names, `${DROPS[k].from} → ${DROPS[k].to}`);
      });
      this._dipNodes.forEach((n, i) => {
        const k = i + 1;
        const o = enter(prog(T, M + 4.6 + (k - 1) * 0.45, M + 5.2 + (k - 1) * 0.45)) * outro * pathO;
        setAttr(n.group, 'opacity', o);
        const s = k * W + (k - 1) * G, z = ZT + morph * (70 + (10 + 30 * (k / 4) - DIPS.reduce((sum, a, q) => sum + a * Math.exp(-Math.pow((k - (q + 1)) / 0.16, 2)), 0)));
        const [px, py] = iso(s, PY, z), [lx, ly] = iso(s, PY + 120, z - 30);
        [n.ring, n.dot].forEach(node => { setAttr(node, 'cx', px); setAttr(node, 'cy', py); });
        setAttr(n.ring, 'stroke', P.loss); setAttr(n.dot, 'fill', P.loss);
        setAttr(n.line, 'x1', px - 8); setAttr(n.line, 'y1', py + 12); setAttr(n.line, 'x2', lx); setAttr(n.line, 'y2', ly); setAttr(n.line, 'stroke', P.loss);
        setAttr(n.number, 'x', lx - 4); setAttr(n.number, 'y', ly + 26); setAttr(n.number, 'fill', P.loss); setText(n.number, DROPS[k - 1].n);
        setAttr(n.label, 'x', lx - 4); setAttr(n.label, 'y', ly + 50); setAttr(n.label, 'fill', P.mute); setText(n.label, `handoff 0${k} · owned`);
      });
      const sw = move(prog(T, M + 1.0, M + 2.0));
      this._labelNodes.forEach((n, i) => {
        const d = DEPTS[i], x = i * (W + G), o = fade(T, S + 2.8 + i * 0.3, E + 1.2, 0.6);
        setAttr(n.group, 'opacity', o);
        const [ax, ay] = iso(x, 0, H), [tx, ty] = iso(x, 0, LZ);
        setAttr(n.line, 'x1', ax); setAttr(n.line, 'y1', ay - 4); setAttr(n.line, 'x2', tx); setAttr(n.line, 'y2', ty); setAttr(n.line, 'stroke', P.ink);
        setAttr(n.dot, 'cx', ax); setAttr(n.dot, 'cy', ay); setAttr(n.dot, 'fill', P.ink);
        setAttr(n.dept, 'x', tx + 14); setAttr(n.dept, 'y', ty + 14); setAttr(n.dept, 'fill', P.mute); setText(n.dept, d.dept);
        [n.metric, n.stage].forEach(node => { setAttr(node, 'x', tx + 12); setAttr(node, 'y', ty + 58); setAttr(node, 'fill', node === n.metric ? P.gain : P.ink); });
        setAttr(n.metric, 'opacity', 1 - sw); setText(n.metric, d.metric);
        setAttr(n.stage, 'opacity', sw); setText(n.stage, d.stage);
        [n.sub, n.stageNo].forEach(node => { setAttr(node, 'x', tx + 14); setAttr(node, 'y', ty + 86); setAttr(node, 'fill', P.mute); });
        setAttr(n.sub, 'opacity', 1 - sw); setText(n.sub, d.sub);
        setAttr(n.stageNo, 'opacity', sw); setText(n.stageNo, `stage 0${i + 1}`);
      });

      const panelO = fade(T, K + 1.0, M + 0.9, 0.7);
      const panelX = 1240 + (1 - enter(prog(T, K + 1.0, K + 2.0))) * 40 + move(prog(T, M + 0.1, M + 0.9)) * 40;
      const lostSum = Math.round(590 * move(prog(T, K + 4.2, K + 5.6)));
      setAttr(this._panel.group, 'opacity', panelO);
      setAttr(this._panel.group, 'transform', `translate(${panelX.toFixed(1)} 300)`);
      setAttr(this._panel.heading, 'fill', P.mute); setAttr(this._panel.rule, 'stroke', P.ink);
      this._panel.rows.forEach((r, i) => {
        const k = enter(prog(T, K + 1.6 + i * 0.55, K + 2.6 + i * 0.55));
        const n = COHORT[i], prev = i ? COHORT[i - 1] : n, bw = 320, y = 62 + i * 72;
        setAttr(r.row, 'opacity', k); setAttr(r.stage, 'y', y + 18); setAttr(r.stage, 'fill', P.ink); setText(r.stage, DEPTS[i].stage);
        setAttr(r.bar, 'y', y); setAttr(r.bar, 'width', bw * (n / 1000) * k); setAttr(r.bar, 'fill', P.ink);
        setAttr(r.lost, 'x', 130 + bw * (n / 1000)); setAttr(r.lost, 'y', y); setAttr(r.lost, 'width', bw * ((prev - n) / 1000) * k); setAttr(r.lost, 'stroke', P.loss);
        setAttr(r.number, 'y', y + 21); setAttr(r.number, 'fill', P.ink); setText(r.number, Math.round(n * k).toLocaleString('en-US'));
      });
      setAttr(this._panel.pct, 'fill', P.loss); setText(this._panel.pct, `${lostSum / 10 | 0}%`);
      setAttr(this._panel.line1, 'fill', P.ink); setAttr(this._panel.line2, 'fill', P.ink);

      const caps = [
        { at: S + 0.8, until: S + 6.7, value: this._Q.caption1 },
        { at: C + 0.4, until: C + 3.9, value: this._Q.caption2 },
        { at: C + 4.1, until: C + 7.8, value: this._Q.caption3 },
        { at: K + 0.5, until: K + 7.8, value: this._Q.caption4 },
        { at: M + 0.8, until: M + 4.6, value: this._Q.caption5 },
        { at: M + 4.8, until: M + 8.9, value: this._Q.caption6 }
      ];
      const cap = caps.find(c => T >= c.at && T < c.until);
      const capO = cap ? fade(T, cap.at, cap.until, 0.45) : 0;
      const capY = cap ? (1 - enter(prog(T, cap.at, cap.at + 0.6))) * 14 : 0;
      setAttr(this._caption, 'opacity', capO);
      setAttr(this._caption, 'transform', `translate(0 ${capY.toFixed(1)})`);
      const visualCaptionKey = cap ? `${cap.value}\u0000${A}` : '';
      if (visualCaptionKey && visualCaptionKey !== this._visualCaptionKey) richHtml(this._captionDiv, cap.value, A, P);
      this._visualCaptionKey = visualCaptionKey;
      const liveText = cap ? cap.value.replace(/\*/g, '') : '';
      if (liveText !== this._lastCaption) {
        setText(this._live, liveText);
        this._lastCaption = liveText;
      }
      const headO = fade(T, E + 0.6, END - 0.4, 0.8);
      const ruleW = 120 * enter(prog(T, E + 1.4, E + 2.4));
      setAttr(this._closing, 'opacity', headO);
      this._rule.setAttribute('style', `height:4px;width:${ruleW.toFixed(2)}px;margin-top:40px;background:${A};`);
    }
  }

  if (!customElements.get('journey-map-animation')) customElements.define('journey-map-animation', JourneyMapAnimation);
})();
