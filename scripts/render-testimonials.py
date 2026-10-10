from pathlib import Path
import json,html,re
root=Path(__file__).resolve().parents[1]
escape=html.escape
data=json.loads((root/'data/testimonials.json').read_text())
cards=[]
for i,t in enumerate(data):
 link=('<a href="'+escape(t['link'],quote=True)+'" target="_blank" rel="noopener noreferrer">View on LinkedIn<span class="sr-only"> (opens in a new tab)</span></a>') if t.get('link') else ''
 cards.append(f'<article class="recommendation-card" dir="ltr" aria-roledescription="slide" aria-label="{i+1} of {len(data)}"><div class="recommendation-mark" aria-hidden="true">&ldquo;</div><blockquote><p>{escape(t["quote"])}</p></blockquote><footer><cite>{escape(t["name"])}</cite><span>{escape(t["role"])}</span>{link}</footer></article>')
for name in ['index.html','work.html']:
 p=root/name;s=p.read_text();s=re.sub(r'<!-- TESTIMONIALS_START -->.*?<!-- TESTIMONIALS_END -->','<!-- TESTIMONIALS_START -->\n<!-- REVIEW: CR-5 replace with verbatim LinkedIn text -->\n'+'\n'.join(cards)+'\n<!-- TESTIMONIALS_END -->',s,flags=re.S);p.write_text(s)
