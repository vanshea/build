from pathlib import Path
import json,re,html
root=Path(__file__).resolve().parents[1]
data=json.loads((root/'data/case-studies.json').read_text());esc=html.escape
for item in data:
 slug=item['slug']
 for name in ['index.html','work.html','case-studies/index.html']:
  p=root/name;s=p.read_text()
  def render(m):
   card=m[0];headline=item['short_headline'] if name=='index.html' else item['title']
   card=re.sub(r'(<h[23][^>]*>)(.*?)(</h[23]>)',lambda h:h[1]+(re.sub(r'(<a[^>]*>).*?(</a>)',lambda a:a[1]+esc(headline)+a[2],h[2]) if '<a' in h[2] else esc(headline))+h[3],card,count=1,flags=re.S)
   card=re.sub(r'(<img\b[^>]*src=")[^"]*',lambda m:m[1]+esc(item['image'],quote=True),card,count=1)
   card=re.sub(r'(<img\b[^>]*alt=")[^"]*',lambda m:m[1]+esc(item['alt'],quote=True),card,count=1)
   card=re.sub(r'<p class="eyebrow">.*?</p>',lambda m:'<p class="eyebrow">'+esc(item['discipline_label'])+'</p>',card,count=1)
   if name=='case-studies/index.html':card=re.sub(r'(<div class="case-study-card-topline">).*?(</div>)',lambda m:m[1]+'<span>'+esc(item['discipline_label'])+'</span><span>'+esc(item['year'])+'</span>'+m[2],card,flags=re.S)
   if slug=='capital-one-gesture-patent' and name=='index.html' and 'REVIEW: DS-2' not in card:card=card.replace('<article','<!-- REVIEW: DS-2 -->\n<article',1)
   return card
  s=re.sub(r'<!-- CASE_CARD:'+re.escape(slug)+r' -->.*?<!-- /CASE_CARD -->',render,s,flags=re.S);p.write_text(s)
 p=root/'case-studies'/slug/'index.html';s=p.read_text();s=re.sub(r'<h1>.*?</h1>','<h1>'+esc(item['title'])+'</h1>',s,count=1,flags=re.S);s=re.sub(r'<title>.*?</title>','<title>'+esc(item['title'])+' | Van Shea Creative</title>',s,count=1);p.write_text(s)
