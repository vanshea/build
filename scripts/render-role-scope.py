from pathlib import Path
import json,re,html,math
root=Path(__file__).resolve().parents[1]
for slug,fields in json.loads((root/'data/role-scope.json').read_text()).items():
 p=root/'case-studies'/slug/'index.html';s=p.read_text()
 block='<section class="role-scope" aria-label="Role and scope"><h2>Role and scope</h2><dl>'+''.join('<div><dt>'+k+'</dt><dd>'+html.escape(v)+'</dd></div>' for k,v in fields.items())+'</dl></section>'
 s=re.sub(r'<!-- ROLE_SCOPE_START -->.*?<!-- ROLE_SCOPE_END -->','<!-- ROLE_SCOPE_START -->'+block+'<!-- ROLE_SCOPE_END -->',s,flags=re.S)
 main=re.search(r'<main\b.*?</main>',s,re.S)[0];main=re.sub(r'<!--.*?-->','',main,flags=re.S);main=re.sub(r'<(?:script|style)\b.*?</(?:script|style)>','',main,flags=re.S)
 words=len(re.findall(r"\b[\w’'-]+\b",html.unescape(re.sub('<[^>]+>',' ',main))))
 s=re.sub(r'\d+ min read',str(max(1,math.ceil(words/230)))+' min read',s);p.write_text(s)
