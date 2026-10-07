const defaults = {
  html: `<main class="demo">
  <p class="kicker">Experiment</p>
  <h2>Prototype an idea fast</h2>
  <p>Edit the HTML, CSS, or JavaScript tabs. The preview updates as you work.</p>
  <button id="sparkBtn" type="button">Add a spark</button>
  <ul id="sparkList"></ul>
</main>`,
  css: `body {
  margin: 0;
  min-height: 100vh;
  display: grid;
  place-items: center;
  background: #f8fafc;
  color: #111827;
  font-family: Inter, system-ui, sans-serif;
}

.demo {
  width: min(560px, calc(100vw - 32px));
  padding: 32px;
  border: 1px solid #d8dee8;
  border-radius: 8px;
  background: white;
  box-shadow: 0 24px 70px rgba(17, 24, 39, 0.12);
}

.kicker {
  color: #0f766e;
  font-weight: 800;
  text-transform: uppercase;
}

h2 {
  margin: 0 0 12px;
  font-size: 40px;
  line-height: 1;
}

button {
  border: 0;
  border-radius: 6px;
  background: #0f766e;
  color: white;
  cursor: pointer;
  font-weight: 800;
  padding: 12px 16px;
}

li {
  margin-top: 10px;
}`,
  js: `const button = document.querySelector("#sparkBtn");
const list = document.querySelector("#sparkList");
let count = 0;

button.addEventListener("click", () => {
  count += 1;
  const item = document.createElement("li");
  item.textContent = \`Spark \${count}: \${new Date().toLocaleTimeString()}\`;
  list.prepend(item);
  console.log("Added", item.textContent);
});`,
  codexPrompt: `Use this page as a scratchpad. Example Codex prompt:

Turn the current HTML, CSS, and JS into a cleaner component with accessible controls. Keep it framework-free and explain the key changes.`,
  claudePrompt: `Use this page as a scratchpad. Example Claude prompt:

Review this prototype for usability and visual hierarchy. Suggest specific CSS and copy changes, then provide a revised version.`
};

const storageKey = "vsc-lab-playground";
const htmlInput = document.querySelector("#htmlInput");
const cssInput = document.querySelector("#cssInput");
const jsInput = document.querySelector("#jsInput");
const codexPrompt = document.querySelector("#codexPrompt");
const claudePrompt = document.querySelector("#claudePrompt");
const previewFrame = document.querySelector("#previewFrame");
const consoleOutput = document.querySelector("#consoleOutput");
const autoRun = document.querySelector("#autoRun");
const runBtn = document.querySelector("#runBtn");
const resetBtn = document.querySelector("#resetBtn");
const clearConsoleBtn = document.querySelector("#clearConsoleBtn");
const tabs = document.querySelectorAll(".tab");
const panels = document.querySelectorAll("[data-panel]");

let runTimer = null;

function loadDefaults() {
  htmlInput.value = defaults.html;
  cssInput.value = defaults.css;
  jsInput.value = defaults.js;
  codexPrompt.value = defaults.codexPrompt;
  claudePrompt.value = defaults.claudePrompt;
}

function loadSavedWork() {
  try {
    const saved = JSON.parse(window.localStorage.getItem(storageKey));
    if (!saved) return false;
    htmlInput.value = saved.html || defaults.html;
    cssInput.value = saved.css || defaults.css;
    jsInput.value = saved.js || defaults.js;
    codexPrompt.value = saved.codexPrompt || defaults.codexPrompt;
    claudePrompt.value = saved.claudePrompt || defaults.claudePrompt;
    return true;
  } catch (error) {
    console.warn("Could not load saved lab work.", error);
    return false;
  }
}

function saveWork() {
  const payload = {
    html: htmlInput.value,
    css: cssInput.value,
    js: jsInput.value,
    codexPrompt: codexPrompt.value,
    claudePrompt: claudePrompt.value
  };
  window.localStorage.setItem(storageKey, JSON.stringify(payload));
}

function getPreviewDocument() {
  const userScript = jsInput.value;
  const consoleBridge = `
    const send = (level, args) => parent.postMessage({
      source: "lab-console",
      level,
      args: args.map((value) => {
        try {
          return typeof value === "string" ? value : JSON.stringify(value);
        } catch (error) {
          return String(value);
        }
      })
    }, "*");

    ["log", "warn", "error"].forEach((level) => {
      const original = console[level];
      console[level] = (...args) => {
        original.apply(console, args);
        send(level, args);
      };
    });

    window.addEventListener("error", (event) => {
      send("error", [event.message + " at line " + event.lineno]);
    });
  `;

  return `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>${cssInput.value}</style>
  </head>
  <body>
    ${htmlInput.value}
    <script>${consoleBridge}<\/script>
    <script>${userScript}<\/script>
  </body>
</html>`;
}

function runPreview() {
  consoleOutput.textContent = "";
  previewFrame.srcdoc = getPreviewDocument();
}

function scheduleRun() {
  saveWork();
  if (!autoRun.checked) return;
  window.clearTimeout(runTimer);
  runTimer = window.setTimeout(runPreview, 350);
}

function switchTab(name) {
  tabs.forEach((tab) => {
    tab.classList.toggle("active", tab.dataset.editor === name);
  });
  panels.forEach((panel) => {
    panel.classList.toggle("active", panel.dataset.panel === name);
  });
}

function appendConsole(level, args) {
  const prefix = level.toUpperCase();
  consoleOutput.textContent += `[${prefix}] ${args.join(" ")}\n`;
  consoleOutput.scrollTop = consoleOutput.scrollHeight;
}

tabs.forEach((tab) => {
  tab.addEventListener("click", () => switchTab(tab.dataset.editor));
});

[htmlInput, cssInput, jsInput].forEach((input) => {
  input.addEventListener("input", scheduleRun);
  input.addEventListener("keydown", (event) => {
    if (event.key !== "Tab") return;
    event.preventDefault();
    const start = input.selectionStart;
    const end = input.selectionEnd;
    input.setRangeText("  ", start, end, "end");
    scheduleRun();
  });
});

[codexPrompt, claudePrompt].forEach((input) => {
  input.addEventListener("input", saveWork);
});

window.addEventListener("message", (event) => {
  if (!event.data || event.data.source !== "lab-console") return;
  appendConsole(event.data.level, event.data.args);
});

runBtn.addEventListener("click", runPreview);
resetBtn.addEventListener("click", () => {
  window.localStorage.removeItem(storageKey);
  loadDefaults();
  runPreview();
});
clearConsoleBtn.addEventListener("click", () => {
  consoleOutput.textContent = "";
});

if (!loadSavedWork()) {
  loadDefaults();
}
runPreview();
