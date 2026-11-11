export const root = new URL("../", import.meta.url);
export const asset = (path) => new URL(path, root).href;
export async function projects() {
  const res = await fetch(asset("data/projects.json"));
  if (!res.ok) throw new Error("Project data unavailable");
  return res.json();
}
export function el(tag, cls, text) {
  const node = document.createElement(tag);
  if (cls) node.className = cls;
  if (text != null) node.textContent = text;
  return node;
}
export function picture(
  data,
  sizes = "(max-width: 700px) 92vw, 60vw",
  priority = false,
) {
  const pic = el("picture"),
    source = el("source"),
    img = el("img");
  const widths = Object.keys(data.sources)
    .map(Number)
    .sort((a, b) => a - b);
  const set = (fmt) =>
    widths.map((w) => asset(data.sources[w][fmt]) + " " + w + "w").join(", ");
  source.type = "image/webp";
  source.sizes = sizes;
  source.srcset = set("webp");
  img.alt = data.alt;
  img.width = data.width;
  img.height = data.height;
  img.sizes = sizes;
  img.loading = priority ? "eager" : "lazy";
  img.decoding = "async";
  if (priority) img.fetchPriority = "high";
  img.srcset = set("jpg");
  img.src = asset(data.sources[widths[0]].jpg);
  pic.append(source, img);
  return pic;
}
