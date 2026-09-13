// Read-only repository inventory; optional generated JSON report is the only write.
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {execFileSync} from 'node:child_process';
import vm from 'node:vm';
const output = process.argv[2];
const gitRoot = path.resolve(process.env.SIMAI_GIT_ROOT || path.resolve(process.cwd(),'..'));
const releaseSource = path.resolve(process.env.SF_RELEASE_SOURCE_ROOT || path.join(gitRoot,'ui-loader'));
const distribution = path.resolve(process.env.SF_DISTRIBUTION_ROOT || path.join(gitRoot,'ui'));
const uiDocRelease = path.resolve(process.env.SF_UI_DOC_RELEASE_ROOT || path.join(gitRoot,'ui-doc'));
const targets = {
  source_main: `${gitRoot}/ui-loader/src`,
  source_release: path.join(releaseSource,'src'),
  distribution_release: path.join(distribution,'distr'),
};
const patterns = {
  container_queries: /@container\s|container-type\s*:/,
  subgrid: /:\s*[^;{}]*\bsubgrid\b/,
  cascade_layers: /@layer\s/,
  registered_properties: /@property\s/,
  color_mix: /color-mix\(/,
  oklch: /oklch\(/,
  logical_layout: /(?:margin|padding|inset|border)-(?:inline|block)|(?:inline|block)-size\s*:/,
  relational_has: /:has\(/,
  viewport_units: /[\d.)](?:sv|dv|lv)[whib]\b/,
  text_wrap: /(?:^|[;{\s])text-wrap\s*:/,
  scrollbar_gutter: /scrollbar-gutter\s*:/,
  reduced_motion: /prefers-reduced-motion/,
  forced_colors: /forced-colors/,
  focus_visible: /:focus-visible/,
  supports: /@supports\s/,
  anchor_positioning: /(?:anchor-name|position-anchor|position-area)\s*:/,
  view_transitions: /view-transition-name\s*:|::view-transition/,
  scroll_timeline: /(?:animation-timeline|scroll-timeline)\s*:/,
};
function walk(root) {
  return fs.readdirSync(root, {withFileTypes:true}).sort((a,b)=>a.name.localeCompare(b.name)).flatMap(e => {
    const p=path.join(root,e.name);
    return e.isDirectory()?walk(p):e.isFile()&&/\.(?:scss|css)$/.test(p)&&!p.endsWith('.min.css')?[p]:[];
  });
}
const report={date:'2026-09-09',method:'literal CSS/SCSS evidence, comments stripped; no claim of runtime support or exhaustive generated API',targets:{},repos:{},locks:{}};
for(const [name,root] of Object.entries(targets)) {
  const rows=Object.fromEntries(Object.keys(patterns).map(k=>[k,{files:0,utilityFiles:0,samples:[]}]));
  const digest=crypto.createHash('sha256');
  const files=walk(root);
  for(const file of files) {
    const relative=path.relative(root,file); const raw=fs.readFileSync(file,'utf8');
    digest.update(relative+'\0'+raw+'\0');
    const css=raw.replace(/\/\*[\s\S]*?\*\//g,'');
    for(const [key,re] of Object.entries(patterns)) if(re.test(css)) {
      const row=rows[key]; row.files++; if(relative.startsWith('utility/')) row.utilityFiles++;
      if(row.samples.length<3)row.samples.push(relative);
    }
  }
  report.targets[name]={root,fileCount:files.length,contentDigest:digest.digest('hex'),features:rows};
}
for(const [name,root] of Object.entries({source:`${gitRoot}/ui-loader`,releaseSource,distribution,builder:`${gitRoot}/ui-builder`,docara:`${gitRoot}/docara`,uiDoc:`${gitRoot}/ui-doc`,uiDocRelease})) {
 const git=(...args)=>execFileSync('git',['-C',root,...args],{encoding:'utf8'}).trim();
 const status=git('status','--porcelain');
 report.repos[name]={root,head:git('rev-parse','HEAD'),dirtyEntries:status?status.split('\n').length:0};
}
for(const [name,file] of Object.entries({docara:`${gitRoot}/docara/docs/site/simai-framework.lock.json`,uiDoc:`${gitRoot}/ui-doc/simai-framework.lock.json`,uiDocRelease:path.join(uiDocRelease,'simai-framework.lock.json')})) {
 const raw=fs.readFileSync(file,'utf8'); const runtime=JSON.parse(raw).runtime;
 report.locks[name]={file,sha256:crypto.createHash('sha256').update(raw).digest('hex'),tag:runtime.tag,ui:runtime.ui.commit,smart:runtime.ui_smart.commit};
}
const ruleRoot=path.join(releaseSource,'src/utility');
const ruleFiles=fs.readdirSync(ruleRoot,{recursive:true}).filter(p=>p.endsWith('/rule.js')).sort();
const context=vm.createContext({SF:{RuleLoader:{}}});
for(const file of ruleFiles)vm.runInContext(fs.readFileSync(path.join(ruleRoot,file),'utf8'),context);
const compiledRuleFile=path.join(distribution,'distr/rule/rule.json');
const compiledRules=new Map(JSON.parse(fs.readFileSync(compiledRuleFile,'utf8')).map(r=>[r.name,r]));
const mismatches=[];
for(const [key,rule] of Object.entries(context.SF.RuleLoader)){
 const regex=rule.regex??rule;
 if(String(regex)!==compiledRules.get(key)?.regex)mismatches.push(key);
}
report.ruleParity={sourceRules:Object.keys(context.SF.RuleLoader).length,distribution:compiledRuleFile,mismatches};
const text=JSON.stringify(report,null,2)+'\n';
if(output) fs.writeFileSync(output,text); else process.stdout.write(text);
