(() => {
  const W = 200, D = 200, H = 90, G0 = 80, ZT = H + 22, PY = D * 0.62;
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

  const rgb = value => {
    const hex = value.trim().replace('#', '');
    if (/^[\da-f]{6}$/i.test(hex)) return [0, 2, 4].map(i => parseInt(hex.slice(i, i + 2), 16));
    if (/^[\da-f]{3}$/i.test(hex)) return [...hex].map(c => parseInt(c + c, 16));
    const parts = value.match(/[\d.]+/g);
    return parts ? parts.slice(0, 3).map(Number) : [0, 0, 0];
  };
  const mix = (a, b, weight) => `rgb(${rgb(a).map((v, i) => Math.round(v + (rgb(b)[i] - v) * weight)).join(',')})`;
  const luminance = color => rgb(color).map(v => v / 255).map(v => v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4).reduce((sum, v, i) => sum + v * [.2126, .7152, .0722][i], 0);
  const contrast = (a, b) => (Math.max(luminance(a), luminance(b)) + .05) / (Math.min(luminance(a), luminance(b)) + .05);
  const readable = (color, backgrounds, ink, ratio = 4.5) => {
    for (let i = 0; i <= 20; i++) {
      const candidate = mix(color, ink, i / 20);
      if (backgrounds.every(bg => contrast(candidate, bg) >= ratio)) return candidate;
    }
    return ink;
  };

  class JourneyMapAnimation extends HTMLElement {
    static get observedAttributes() { return ATTRS; }

    constructor() {
      super();
      this.attachShadow({ mode: 'open' });
      this._elapsed = 0;
      this._raf = 0;
      this._running = false;
      this._inView = false;
      this._userPaused = false;
      this._reduced = matchMedia('(prefers-reduced-motion: reduce)');
      this._scheme = matchMedia('(prefers-color-scheme: dark)');
      this._onTheme = () => this._updateCopyAndPalette();
      this._onVisibility = () => this._syncPlayback();
      this._onMotion = () => { this._updatePresentation(); this._syncPlayback(); };
      this._tick = now => {
        if (!this._running) return;
        this._elapsed = ((now - this._startAt) / 1000) % TOTAL;
        this._renderFrame(this._elapsed);
        this._raf = requestAnimationFrame(this._tick);
      };
    }

    connectedCallback() {
      if (!this._svg) this._build();
      this._themeObserver = new MutationObserver(this._onTheme);
      this._themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class', 'style'] });
      this._themeObserver.observe(document.body, { attributes: true, attributeFilter: ['class', 'style'] });
      this._scheme.addEventListener('change', this._onTheme);
      this._reduced.addEventListener('change', this._onMotion);
      document.addEventListener('visibilitychange', this._onVisibility);
      this._resizeObserver = new ResizeObserver(entries => {
        this._figureWidth = entries[0].contentRect.width;
        this._renderFrame(this._reduced.matches ? 31 : this._elapsed);
      });
      this._resizeObserver.observe(this._svg);
      this._intersectionObserver = new IntersectionObserver(entries => {
        this._inView = entries.some(entry => entry.isIntersecting);
        this._syncPlayback();
      });
      this._intersectionObserver.observe(this);
      this._updateCopyAndPalette();
      this._syncPlayback();
    }

    disconnectedCallback() {
      this._stopPlayback();
      this._themeObserver?.disconnect();
      this._resizeObserver?.disconnect();
      this._intersectionObserver?.disconnect();
      this._scheme.removeEventListener('change', this._onTheme);
      this._reduced.removeEventListener('change', this._onMotion);
      document.removeEventListener('visibilitychange', this._onVisibility);
    }

    attributeChangedCallback() { if (this._svg) this._updateCopyAndPalette(); }

    _copy() {
      return Object.fromEntries(Object.entries(COPY_DEFAULTS).map(([key, fallback]) => [key, this.getAttribute(key) ?? fallback]));
    }

    _palette() {
      // The site's inherited CSS variables, driven by html[data-theme], are authoritative.
      // The old theme attribute is only a fallback when used outside the site.
      const style = getComputedStyle(this);
      const fallback = PAL[(this.getAttribute('theme') || 'light').toLowerCase()] || PAL.light;
      const token = (name, defaultValue) => style.getPropertyValue(name).trim() || defaultValue;
      const bg = token('--bg', fallback.bg);
      const ink = token('--ink', fallback.ink);
      const surface = mix(bg, token('--panel', ink), .18);
      const left = mix(bg, ink, .12), right = mix(bg, ink, .22);
      const backgrounds = [bg, surface];
      const dark = luminance(bg) < .25;
      return {
        bg, ink: readable(ink, backgrounds, dark ? '#FFFFFF' : '#000000'),
        top: surface, left, right, surface,
        mute: readable(token('--muted', fallback.mute), backgrounds, ink),
        line: readable(token('--line', fallback.ink), backgrounds, ink, 3),
        grid: mix(bg, ink, .12),
        accent: readable(token('--accent', this.getAttribute('accent') || '#2F5BEA'), backgrounds, ink),
        gain: readable(dark ? '#66D99B' : '#176B3A', backgrounds, ink),
        loss: readable(dark ? '#FF8B7A' : '#B42318', [...backgrounds, left, right], ink)
      };
    }

    _syncPlayback() {
      if (!this._svg) return;
      const canRun = this._inView && !document.hidden && !this._reduced.matches && !this._userPaused;
      if (canRun && !this._running) {
        this._running = true;
        this._startAt = performance.now() - this._elapsed * 1000;
        this._raf = requestAnimationFrame(this._tick);
      } else if (!canRun) this._stopPlayback();
      if (this._reduced.matches) this._renderFrame(31);
      this._pause.disabled = this._reduced.matches;
      this._pause.textContent = this._reduced.matches ? 'Reduced motion' : this._userPaused ? 'Play animation' : 'Pause animation';
    }

    _stopPlayback() {
      if (this._running) this._elapsed = ((performance.now() - this._startAt) / 1000) % TOTAL;
      this._running = false;
      cancelAnimationFrame(this._raf);
      this._raf = 0;
    }

    _build() {
      this.shadowRoot.innerHTML = `<style>
        :host{display:block;min-width:0;color:var(--journey-text);font:400 16px/1.5 ${FONT};letter-spacing:normal}
        *{box-sizing:border-box} [hidden]{display:none!important} h2,h3,p,figure,ol,dl,dd{margin:0} ol{padding:0;list-style:none}
        .frame{background:var(--journey-bg);padding:0;min-width:0}
        .kicker{color:var(--journey-muted);font:400 14px/1.5 ${MONO};letter-spacing:.07em;margin-bottom:12px}
        .captions{display:grid;max-width:52rem}
        .caption{grid-area:1/1;visibility:hidden;align-self:start}
        .caption.active{visibility:visible}
        h2{font:600 clamp(24px,3.2vw,40px)/1.2 ${FONT};letter-spacing:-.02em;text-wrap:balance;overflow-wrap:break-word}
        .subhead{font-size:clamp(18px,2vw,24px);color:var(--journey-muted);margin-top:8px}
        .layout{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(300px,1fr);gap:clamp(20px,4vw,48px);align-items:center;margin-top:20px}
        figure{min-width:0} svg{display:block;width:100%;height:auto;aspect-ratio:4/3;overflow:hidden}
        .stages{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:8px;color:var(--journey-muted);font-size:14px;line-height:1.35;text-align:center}
        .stages span{display:block;color:var(--journey-text);font-weight:600}
        .readouts{display:grid;min-width:0}.readout{grid-area:1/1;visibility:hidden;align-self:center;min-width:0}.readout.active{visibility:visible}
        .metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
        .metric{padding:14px 12px;border:1px solid var(--journey-line);border-radius:10px;background:var(--journey-surface)}
        dt,.small{font-size:14px;line-height:1.4;color:var(--journey-muted);overflow-wrap:break-word}
        .value{font-size:clamp(26px,3vw,36px);font-weight:600;line-height:1.15;margin:8px 0;color:var(--journey-gain)}
        .metric dd:last-child{font-size:16px}.panel-label{font:400 14px/1.5 ${MONO};color:var(--journey-muted);margin-bottom:14px}
        .handoffs{display:grid;gap:12px}.handoff{padding:12px 0;border-bottom:1px solid var(--journey-line);display:grid;grid-template-columns:1fr auto;gap:6px 12px;align-items:center}
        .handoff p{font-size:16px;overflow-wrap:break-word}.loss{color:var(--journey-loss);font-weight:600;font-size:26px;white-space:nowrap}.handoff .small{grid-column:1/-1}
        .cohort{display:grid;gap:12px}.cohort-label{display:flex;justify-content:space-between;gap:12px;font-size:14px}.cohort-label strong{font-size:18px;font-variant-numeric:tabular-nums}
        .track{height:10px;background:var(--journey-surface);border:1px solid var(--journey-line);margin-top:4px;border-radius:3px;overflow:hidden}.bar{height:100%;background:var(--journey-text);transform-origin:left center}
        .cost-summary{margin-top:16px;font-size:16px;line-height:1.5}.cost-summary strong{color:var(--journey-loss);font-size:26px}
        .lanes{display:flex;flex-wrap:wrap;gap:8px 18px;font-size:14px;margin-top:16px;color:var(--journey-muted)}
        .controls{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:20px;padding-top:12px;border-top:1px solid var(--journey-line)}
        .phase{font-size:14px;color:var(--journey-muted)}
        button{font:600 14px/1.4 ${FONT};color:var(--journey-text);background:var(--journey-surface);border:1px solid var(--journey-line);border-radius:24px;min-height:44px;padding:10px 16px;cursor:pointer}
        button:focus-visible{outline:3px solid var(--journey-accent);outline-offset:3px}button:disabled{cursor:default}
        .narrative{display:none}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        @media(max-width:1000px){.layout{grid-template-columns:minmax(0,1fr);gap:18px}figure{width:min(100%,560px);margin-inline:auto}.readouts{width:100%;max-width:560px;margin-inline:auto}.grid{display:none}.captions{max-width:560px;margin-inline:auto}.kicker{max-width:560px;margin-inline:auto;margin-bottom:12px}}
        .static .readouts{display:flex;flex-direction:column;gap:24px}.static .readout{visibility:visible;width:100%}.static .narrative{display:grid;gap:10px;margin-top:24px;font-size:16px;max-width:70ch}.static .layout{grid-template-columns:minmax(0,1fr);align-items:start}.static figure{width:min(100%,560px);margin-inline:auto}.static .readouts{max-width:none;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px}.static .readout{grid-area:auto}@media(max-width:600px){.static .readouts{grid-template-columns:minmax(0,1fr)}}
      </style>
      <div class="frame">
        <header><p class="kicker"></p><div class="captions">
          ${Array.from({length:6},(_,i)=>`<div class="caption" data-caption="${i}" aria-hidden="true"><h2></h2></div>`).join('')}
          <div class="caption" data-caption="6" aria-hidden="true"><h2></h2><p class="subhead"></p></div>
        </div></header>
        <div class="layout">
          <figure><svg viewBox="-205 -170 1138 822" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Four departments become one connected customer journey. Numbered blocks correspond to the journey stages listed below."></svg>
            <figcaption class="stages">${DEPTS.map((d,i)=>`<div><span>0${i+1}</span>${d.stage}</div>`).join('')}</figcaption>
          </figure>
          <div class="readouts">
            <div class="readout" data-panel="metrics" aria-hidden="true"><dl class="metrics">${DEPTS.map((d,i)=>`<div class="metric"><dt>0${i+1} · ${d.dept}</dt><dd class="value">${d.metric}</dd><dd>${d.sub}</dd></div>`).join('')}</dl></div>
            <div class="readout" data-panel="gaps" aria-hidden="true"><ol class="handoffs">${DROPS.map((d,i)=>`<li class="handoff"><p>${d.from} → ${d.to}</p><strong class="loss">${d.pct}</strong><span class="small">handoff 0${i+1} · ${d.n} customers</span></li>`).join('')}</ol></div>
            <div class="readout" data-panel="cost" aria-hidden="true"><p class="panel-label">PER 1,000 CUSTOMERS · ILLUSTRATIVE</p><ol class="cohort">${COHORT.map((n,i)=>`<li><div class="cohort-label"><span>${DEPTS[i].stage}</span><strong>${n.toLocaleString('en-US')}</strong></div><div class="track" aria-hidden="true"><div class="bar" style="width:${n/10}%"></div></div></li>`).join('')}</ol><p class="cost-summary"><strong>59%</strong> of customers are lost in handoffs that no single team owns.</p></div>
            <div class="readout" data-panel="owned" aria-hidden="true"><ol class="handoffs">${DROPS.map((d,i)=>`<li class="handoff"><p>${d.from} → ${d.to}</p><strong class="loss">${d.n}</strong><span class="small">handoff 0${i+1} · owned</span></li>`).join('')}</ol><p class="lanes"><span>DOING</span><span>THINKING</span><span>FEELING</span></p></div>
          </div>
        </div>
        <ol class="narrative"></ol>
        <div class="controls"><p class="phase"></p><button type="button">Pause animation</button></div>
        <p class="sr-only" aria-live="polite" aria-atomic="true" data-live></p>
      </div>`;
      const root = this.shadowRoot;
      this._frame = root.querySelector('.frame');
      this._svg = root.querySelector('svg');
      this._live = root.querySelector('[data-live]');
      this._pause = root.querySelector('button');
      this._phase = root.querySelector('.phase');
      this._captions = [...root.querySelectorAll('[data-caption]')];
      this._panels = [...root.querySelectorAll('[data-panel]')];
      this._bars = [...root.querySelectorAll('.bar')];
      this._pause.addEventListener('click', () => { this._userPaused = !this._userPaused; this._syncPlayback(); });
      this._grid = svgEl('g', {class:'grid',fill:'none','aria-hidden':'true'}, this._svg);
      for (let y = -100; y <= 550; y += 100) svgEl('polyline', {points:points([[-100,y,0],[1140,y,0]])}, this._grid);
      for (let x = -100; x <= 1140; x += 100) svgEl('polyline', {points:points([[x,-100,0],[x,550,0]])}, this._grid);
      this._world = svgEl('g', {'aria-hidden':'true','data-journey-artwork':''}, this._svg);
      this._blocks = this._buildBlocks();
      this._lanes = svgEl('g', {opacity:0}, this._world);
      [66,133].forEach(y=>svgEl('line',{x1:0,y1:y,x2:800,y2:y,'stroke-width':1.5},this._lanes));
      [200,400,600].forEach(x=>svgEl('line',{x1:x,y1:0,x2:x,y2:200,'stroke-width':1.5,'stroke-dasharray':'5 6'},this._lanes));
      this._pathGroup = svgEl('g', {fill:'none','stroke-linecap':'round','stroke-linejoin':'round'}, this._world);
      this._pathLines = Array.from({length:8},()=>svgEl('polyline',{},this._pathGroup));
      this._head = svgEl('circle',{r:10},this._pathGroup);
      this._dipRings = DIPS.map(()=>svgEl('circle',{r:13,fill:'none','stroke-width':3},this._world));
      this._badges = DEPTS.map((d,i)=>{
        const group=svgEl('g',{'aria-hidden':'true','data-node-badge':''},this._svg);
        const circle=svgEl('circle',{'stroke-width':1.5},group);
        const text=svgEl('text',{'text-anchor':'middle','dominant-baseline':'central','font-family':MONO,'font-weight':600},group);
        text.textContent=`0${i+1}`;
        return {group,circle,text};
      });
    }

    _updateCopyAndPalette() {
      if (!this._svg) return;
      this._P = this._palette();
      this._Q = this._copy();
      const P = this._P;
      for (const [name,value] of Object.entries({bg:P.bg,text:P.ink,muted:P.mute,surface:P.surface,line:P.line,accent:P.accent,gain:P.gain,loss:P.loss})) this.style.setProperty(`--journey-${name}`,value);
      setText(this.shadowRoot.querySelector('.kicker'),this._Q.kicker);
      this._captions.forEach((node,i)=>richHtml(node.querySelector('h2'),i<6?this._Q[`caption${i+1}`]:this._Q.headline,P.accent,P));
      richHtml(this.shadowRoot.querySelector('.subhead'),this._Q.subhead,P.accent,P);
      const narrative=this.shadowRoot.querySelector('.narrative');
      narrative.replaceChildren();
      for(let i=1;i<=6;i++){const item=document.createElement('li');richHtml(item,this._Q[`caption${i}`],P.accent,P);narrative.appendChild(item);}
      setAttr(this._grid,'stroke',P.grid);
      setAttr(this._lanes,'stroke',P.line);
      this._presentationKey='';
      this._renderFrame(this._reduced.matches?31:this._elapsed);
    }

    _updatePresentation(T = this._reduced.matches ? 31 : this._elapsed) {
      const reduced=this._reduced.matches;
      const scene=T<7?0:T<15?1:T<23?2:T<32?3:4;
      const caption=reduced||T>=32?6:T>=27.8?5:T>=23?4:T>=15?3:T>=11.1?2:T>=7?1:0;
      const panel=scene===0?'metrics':scene===1?'gaps':scene===2?'cost':'owned';
      const key=`${caption}:${panel}:${reduced}`;
      if(key===this._presentationKey)return;
      this._presentationKey=key;
      this._frame.classList.toggle('static',reduced);
      this._captions.forEach((node,i)=>{node.classList.toggle('active',i===caption);setAttr(node,'aria-hidden',i!==caption);});
      this._panels.forEach(node=>{const active=reduced||node.dataset.panel===panel;node.classList.toggle('active',active);setAttr(node,'aria-hidden',!active);});
      setText(this._phase,reduced?'Complete journey':`${scene+1} / 5 · ${SCENES[scene].name}`);
      const copy=caption===6?`${this._Q.headline} ${this._Q.subhead}`:this._Q[`caption${caption+1}`];
      setText(this._live,copy.replace(/\*/g,''));
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


    _renderFrame(T) {
      if (!this._P || !Number.isFinite(T)) return;
      T=((T%TOTAL)+TOTAL)%TOTAL;
      const P=this._P, C=CUES.Customer, M=CUES.Map;
      const G=tw(T,M+.4,M+2.4,G0,0), L=4*W+3*G;
      const morph=tw(T,M+2.4,M+4.6,0,1);
      // Fit actual block geometry, including bars and badge radii, instead of cropping a desktop canvas.
      const minX=-D*C30-44, minY=-170, boxWidth=(L+D)*C30+88, boxHeight=(L+D)*S30+214;
      setAttr(this._svg,'viewBox',`${minX} ${minY} ${boxWidth} ${boxHeight}`);
      const width=this._figureWidth||this._svg.clientWidth||300;
      const scale=Math.min(width/boxWidth,(width*.75)/boxHeight);
      const pathP=draw(prog(T,C+.8,C+6.6));
      const head=pathP*L;
      const samples=[];
      for(let i=0;i<=260;i++){
        const s=i/260*L;
        let z=ZT, gap=false;
        for(let k=1;k<=3;k++){
          const start=k*W+(k-1)*G;
          if(G>.5&&s>start&&s<start+G){gap=true;z=ZT-(ZT-8)*Math.sin(Math.PI*(s-start)/G)**.55;}
        }
        const u=s/(W+G);
        const emotion=10+30*u/4-DIPS.reduce((sum,a,k)=>sum+a*Math.exp(-(((u-k-1)/.16)**2)),0);
        z+=morph*(70+emotion);
        samples.push({s,z,hot:gap||(morph>.4&&[1,2,3].some(k=>Math.abs(u-k)<.13))});
      }
      const runs=[];
      let run;
      for(const point of samples){
        if(point.s>head)break;
        if(!run||run.hot!==point.hot){if(run)run.points.push(point);run={hot:point.hot,points:[point]};runs.push(run);}else run.points.push(point);
      }
      setAttr(this._pathGroup,'opacity',T<C+.4?0:1);
      this._pathLines.forEach((line,i)=>{
        const part=runs[i];setAttr(line,'display',part?'inline':'none');if(!part)return;
        setAttr(line,'points',part.points.map(p=>iso(p.s,PY,p.z).join(',')).join(' '));
        setAttr(line,'stroke',part.hot?P.loss:P.ink);setAttr(line,'stroke-width',(part.hot?2.5:1.8)/scale);
      });
      const hp=samples.find(p=>p.s>=head)||samples[260], [hx,hy]=iso(head,PY,hp.z);
      setAttr(this._head,'cx',hx);setAttr(this._head,'cy',hy);setAttr(this._head,'r',4/scale);
      setAttr(this._head,'fill',P.ink);setAttr(this._head,'display',pathP>0&&pathP<1?'inline':'none');
      DEPTS.forEach((d,i)=>{
        const x=i*(W+G), h=H*enter(prog(T,.6+i*.35,1.9+i*.35));
        const barK=enter(prog(T,2.2+i*.3,3.4+i*.3))*(1-move(prog(T,M+.1,M+1)));
        this._drawBox(this._blocks[i].block,x,0,0,W,D,h,P,1/scale);
        d.bars.forEach((b,j)=>this._drawBox(this._blocks[i].bars[j],x+28+j*54,26,h,34,34,b*barK,P,.75/scale));
        const badge=this._badges[i], [bx,by]=iso(x+W/2,D+28,0);
        setAttr(badge.group,'transform',`translate(${bx} ${by})`);
        setAttr(badge.circle,'r',12/scale);setAttr(badge.circle,'fill',P.bg);setAttr(badge.circle,'stroke',P.line);setAttr(badge.circle,'stroke-width',1/scale);
        setAttr(badge.text,'font-size',14/scale);setAttr(badge.text,'fill',P.ink);
      });
      setAttr(this._lanes,'transform',`matrix(${C30} ${S30} ${-C30} ${S30} 0 ${-H})`);
      setAttr(this._lanes,'opacity',enter(prog(T,M+2,M+3)));
      this._dipRings.forEach((ring,i)=>{
        const k=i+1, s=k*W+(k-1)*G;
        const z=ZT+morph*(80+30*k/4-DIPS[i]);
        const [x,y]=iso(s,PY,z);
        setAttr(ring,'cx',x);setAttr(ring,'cy',y);setAttr(ring,'r',5/scale);setAttr(ring,'stroke-width',1.5/scale);setAttr(ring,'stroke',P.loss);
        setAttr(ring,'opacity',enter(prog(T,M+4.6+i*.45,M+5.2+i*.45)));
      });
      this._bars.forEach((bar,i)=>bar.style.transform=`scaleX(${this._reduced.matches?1:enter(prog(T,16.6+i*.55,17.6+i*.55))})`);
      this._updatePresentation(T);
    }
  }
  if(!customElements.get('journey-map-animation'))customElements.define('journey-map-animation',JourneyMapAnimation);
})();
