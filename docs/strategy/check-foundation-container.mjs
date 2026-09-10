// Isolated architecture experiment, NOT a public utility API or a product build.
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
const deps=createRequire(process.env.SF_BUILDER_PACKAGE_JSON || path.resolve(process.cwd(),'../ui-builder/package.json'));
const runtime=createRequire(process.env.SF_NODE_PACKAGE_JSON || import.meta.url);
const webpack=deps('webpack'), sass=deps('sass');
const source=path.resolve(process.env.SF_TEST_SOURCE_ROOT || path.resolve(process.cwd(),'../ui-loader'));
const core=path.resolve(process.env.SF_TEST_CORE_ROOT || path.resolve(process.cwd(),'../ui/distr'));
const smartRoot=path.resolve(process.env.SF_TEST_SMART_ROOT || path.resolve(process.cwd(),'../ui-smart/distr'));
const utilityRoot=path.resolve(process.env.SF_TEST_UTILITY_ROOT || core);
const componentRoot=process.env.SF_TEST_COMPONENT_ROOT ? path.resolve(process.env.SF_TEST_COMPONENT_ROOT) : core;
const work=fs.mkdtempSync(path.join(os.tmpdir(),'sf-container-proof-'));
const external=process.argv.includes('--external-template');
const sharedDOM=process.argv.includes('--shared-dom');
const lifecycle=process.argv.includes('--lifecycle');
const races=process.argv.includes('--races');
const formScenario=process.argv.includes('--form');
const formView=process.argv.includes('--form-view');
if(formView)assert.ok(formScenario,'--form-view requires --form');
const raceRequests=new Set(), raceGates=new Map();
for(const name of ['race-slow-fast','race-slow-default','race-slow-disconnect']) {
  let release; const promise=new Promise(resolve=>{release=resolve;});
  raceGates.set(name,{promise,release});
}
const coreRuntime=process.argv.includes('--core-runtime');
const strictCandidate=process.argv.includes('--strict-candidate');
if(strictCandidate)assert.ok(coreRuntime&&process.env.SF_TEST_COMPONENT_ROOT,'Strict candidate requires Core runtime and an explicit Component root');
const requestedAssets=[];
const missingAssets=[];
const smartFile=process.env.SF_TEST_SMART_FILE || path.join(work,'smart.js');
if(!process.env.SF_TEST_SMART_FILE)await new Promise((resolve,reject)=>{
  const compiler=webpack({mode:'development',devtool:false,context:source,
    entry:path.join(source,'src/smart/list-item/index.js'),
    output:{path:work,filename:'smart.js'},optimization:{minimize:false},
    resolve:{modules:[path.join(source,'node_modules'),'node_modules']}});
  compiler.run((error,stats)=>compiler.close(()=>error?reject(error):stats.hasErrors()?reject(new Error(stats.toString({all:false,errors:true}))):resolve()));
});
const query=sass.renderSync({data:`@use "sass:map"; @use "${source}/src/core/scss/vars/sf_var__breakpoint" as bp;
  @layer sf.overrides {
    @container foundation-panel (min-width: #{map.get(bp.$breakpoint-array, sm)}) {
      .proof-item .sf-list-item-wrap {flex-direction:row;align-items:center;}
    }
  }`}).css.toString();
const css=fs.readFileSync(path.join(core,'core/css/core.css'),'utf8')+'\n'+
  fs.readFileSync(path.join(source,'src/component/dropdown/scss/_css__list-item.scss'),'utf8')+`
  @layer sf.overrides {
    body {margin:0;padding:var(--sf-space-2);background:var(--sf-surface-0);color:var(--sf-on-surface);}
    .proof-panel {container:foundation-panel / inline-size;max-inline-size:100%;}
    .wide {inline-size:800px;} .narrow {inline-size:320px;}
    .proof-item .sf-list-item-wrap {display:flex;flex-direction:column;align-items:stretch;gap:var(--sf-space-1);min-inline-size:0;}
    .proof-item .sf-list-item-container {min-inline-size:0;overflow-wrap:anywhere;flex:1;}
    .proof-item .proof-meta {flex-shrink:0;color:var(--sf-on-surface-variant);}
    h2 {font-size:var(--sf-text-size-2);margin-block:var(--sf-space-2) var(--sf-space-1);}
  }
`+query;
const text='Согласование обновлённой документации и параметров отображения пользовательского интерфейса';
const item=id=>`<sf-list-item id="${id}" class="proof-item" text="${text}" size="1"><span slot="trailing" class="proof-meta">Готово к проверке</span></sf-list-item>`;
const runtimeScripts=coreRuntime?`<script>window.sfPath='https://sf-container.invalid/distr/';window.SF_BOOT_CONFIG={theme:false,preloader:{preloaderActive:false}};</script><script defer src="/distr/core/js/core.js"></script>`:'<script defer src="/smart.js"></script>';
const html=`<!doctype html><html lang="ru" class="theme-light"><head><meta charset="utf-8"><link rel="stylesheet" href="/proof.css">${runtimeScripts}</head><body>
<h1>Один Smart-компонент — разные контейнеры</h1>
<h2>Широкая область</h2><section class="proof-panel wide" id="wide-panel">${item('wide')}</section>
<h2>Боковая панель</h2><section class="proof-panel narrow">${item('narrow')}</section>
<h2>Узкий контейнер внутри широкого</h2><section class="proof-panel wide"><div class="proof-panel narrow">${item('nested')}</div></section>
${formScenario?'<form id="proof-form"><sf-input id="proof-input" name="title" label="Название" required hint="Укажите название"></sf-input><button type="submit">Сохранить</button></form>':''}
</body></html>`;
// Deliberately use a different DOM: component-specific DOM updates must fall
// back to full rendering rather than silently leave stale props in this view.
const externalModule=`export default function({html,context,component}) {
  return html\`<article class="alternative-view ${sharedDOM?'sf-list-item':''}" data-selected=\${String(context.selected)}
    aria-disabled=\${String(context.disabled)} tabindex=\${context.disabled ? '-1' : '0'}>
    <strong class="alternative-text ${sharedDOM?'sf-list-item-container':''}">\${context.text}</strong>
    <span class="mirror-text">\${context.text}</span>
    <aside>\${component.getSlotContent('trailing')}</aside>
  </article>\`;
}
const owners=new WeakSet();
export function afterRender({component}) {
  if(!owners.has(component)) {owners.add(component);window.proofResources=(window.proofResources||0)+1;}
}
export function destroy({component}) {
  window.proofDestroyCalls=(window.proofDestroyCalls||0)+1;
  if(owners.delete(component))window.proofResources--;
}`;
fs.writeFileSync(path.join(work,'proof.css'),css);
fs.writeFileSync(path.join(work,'index.html'),html);
const browserName=process.env.SF_TEST_BROWSER || 'chromium';
assert.ok(['chromium','firefox','webkit'].includes(browserName));
const browser=await runtime('playwright')[browserName].launch({headless:true});
const report={scope:coreRuntime?'Core Loader + Smart integration with explicit local asset roots; local container CSS, not release acceptance':'Smart behavior and local container CSS; no Loader or release acceptance claim',coreRuntime,strictCandidate,requestedAssets,missingAssets,smartFile,productSmart:Boolean(process.env.SF_TEST_SMART_FILE),external,sharedDOM,lifecycle,source,core,work,browserName,browser:browser.version(),query,checks:[],errors:[],pass:false};
try {
  const page=await browser.newPage({viewport:{width:1200,height:900}});
  page.on('pageerror',error=>report.errors.push(error.message));
  await page.route('**/*',async route=>{
    const url=new URL(route.request().url());
    if(url.origin!=='https://sf-container.invalid')return route.abort();
    if(url.pathname==='/local/smart/templates/table/lifecycle-proof/index.css')return route.fulfill({body:'',contentType:'text/css'});
    if(url.pathname==='/local/smart/templates/table/lifecycle-proof/index.js')return route.fulfill({body:`
      const owners=new WeakSet();
      export default function({html,component}) {
        if(!owners.has(component)){owners.add(component);window.tableViewResources=(window.tableViewResources||0)+1;}
        return html\`<section class="table-lifecycle-proof">Внешняя таблица</section>\`;
      }
      export function destroy({component}) {
        if(owners.delete(component)){window.tableViewResources--;window.tableViewDestroyed=(window.tableViewDestroyed||0)+1;}
      }`,contentType:'text/javascript'});
    if(url.pathname==='/local/smart/templates/textarea/form-proof/index.css')return route.fulfill({body:'',contentType:'text/css'});
    if(url.pathname==='/local/smart/templates/textarea/form-proof/index.js')return route.fulfill({body:`export default function({html,context}) {return html\`<section class="textarea-alternative"><label>\${context.label}<textarea name=\${context.name} .value=\${context.value}></textarea></label></section>\`;}`,contentType:'text/javascript'});
    if(url.pathname==='/local/smart/templates/input/form-proof/index.css')return route.fulfill({body:'',contentType:'text/css'});
    if(url.pathname==='/local/smart/templates/input/form-proof/index.js')return route.fulfill({body:`export default function({html,context}) {
      return html\`<section class="form-alternative"><label>\${context.label}<input name=\${context.name} .value=\${context.value} ?required=\${context.required} ?disabled=\${context.disabled} ?readonly=\${context.readonly}></label></section>\`;
    }`,contentType:'text/javascript'});
    const raceMatch=url.pathname.match(/^\/local\/smart\/templates\/list-item\/(race-[a-z-]+)\/index\.(js|css)$/);
    if(raceMatch) {
      const [,name,extension]=raceMatch;
      if(extension==='css')return route.fulfill({body:'',contentType:'text/css'});
      if(raceGates.has(name)) {raceRequests.add(name);await raceGates.get(name).promise;}
      return route.fulfill({body:`export const proofName=${JSON.stringify(name)}; export default function({html}) {window.raceRenders.push(proofName);return html\`<span class="race-view">\${proofName}</span>\`;}`,contentType:'text/javascript'});
    }
    if(url.pathname.startsWith('/distr/')) {
      const relative=decodeURIComponent(url.pathname.slice('/distr/'.length));
      if(relative.includes('..')||path.isAbsolute(relative))return route.abort();
      const roots=[core,smartRoot,utilityRoot,componentRoot].filter(Boolean);
      const file=roots.map(root=>path.join(root,relative)).find(file=>fs.existsSync(file)&&fs.statSync(file).isFile());
      requestedAssets.push({relative,file:file||null});
      if(!file){missingAssets.push(relative);return route.fulfill({status:404,body:''});}
      const contentType={'.js':'text/javascript','.css':'text/css','.json':'application/json','.woff2':'font/woff2'}[path.extname(file)]||'application/octet-stream';
      return route.fulfill({path:file,contentType});
    }
    if(url.pathname==='/smart.js')return route.fulfill({path:smartFile,contentType:'text/javascript'});
    if(url.pathname==='/local/smart/templates/list-item/foundation-proof/index.js')
      return route.fulfill({body:externalModule,contentType:'text/javascript'});
    if(url.pathname==='/local/smart/templates/list-item/foundation-proof/index.css')
      return route.fulfill({body:'@layer sf.overrides {.alternative-view {padding:var(--sf-space-1);color:var(--sf-on-surface);background:var(--sf-surface-0);}}',contentType:'text/css'});
    const name={'/':'index.html','/smart.js':'smart.js','/proof.css':'proof.css'}[url.pathname];
    if(!name)return route.fulfill({status:404,body:''});
    return route.fulfill({path:path.join(work,name),contentType:name.endsWith('.js')?'text/javascript':name.endsWith('.css')?'text/css':'text/html'});
  });
  await page.goto('https://sf-container.invalid');
  await page.waitForFunction(()=>document.querySelectorAll('.sf-list-item-wrap').length===3);
  const inspect=()=>page.evaluate(()=>['wide','narrow','nested'].map(id=>{
    const host=document.getElementById(id), wrap=host.querySelector('.sf-list-item-wrap');
    return {id,direction:getComputedStyle(wrap).flexDirection,width:wrap.clientWidth,scroll:wrap.scrollWidth,
      text:host.querySelector('.sf-list-item-container').textContent.trim(),meta:wrap.querySelector('.proof-meta')?.textContent.trim()};
  }));
  for(const [theme,dir] of [['theme-light','ltr'],['theme-dark','rtl']]) {
    await page.evaluate(({theme,dir})=>{document.documentElement.className=theme;document.documentElement.dir=dir;},{theme,dir});
    const rows=await inspect();
    assert.deepEqual(rows.map(row=>row.direction),['row','column','column']);
    for(const row of rows){assert.ok(row.scroll<=row.width+1,JSON.stringify(row));assert.equal(row.text,text);assert.equal(row.meta,'Готово к проверке');}
    const screenshot=path.join(work,`container-${dir}.png`);
    await page.screenshot({path:screenshot,fullPage:true});
    report.checks.push({theme,dir,rows,screenshot});
  }
  // Stress the same public size/density tokens without changing the template.
  const before=await page.locator('#narrow .sf-list-item-container').evaluate(el=>getComputedStyle(el).fontSize);
  await page.evaluate(()=>{
    document.documentElement.style.setProperty('--sf-text-size-1','calc(var(--sf-text--size-1) * 2)');
    document.documentElement.style.setProperty('--sf-ui-1--space-y-tightness-default','var(--sf-space-1)');
  });
  const scaled=await inspect();
  const after=await page.locator('#narrow .sf-list-item-container').evaluate(el=>getComputedStyle(el).fontSize);
  assert.equal(parseFloat(after),2*parseFloat(before));
  for(const row of scaled)assert.ok(row.scroll<=row.width+1,JSON.stringify(row));
  report.checks.push({textScale:2,fontBefore:before,fontAfter:after,rows:scaled});
  await page.evaluate(()=>document.documentElement.removeAttribute('style'));
  // Change only the local available width: same viewport, same element instance.
  await page.evaluate(()=>{window.proofOriginal=document.getElementById('wide');document.getElementById('wide-panel').style.inlineSize='320px';});
  const resized=await inspect();assert.equal(resized[0].direction,'column');
  assert.equal(await page.evaluate(()=>window.proofOriginal===document.getElementById('wide')),true);
  report.checks.push({localResize:resized});
  if(external) {
    await page.evaluate(()=>document.getElementById('wide').setAttribute('template','foundation-proof'));
    await page.waitForSelector('#wide .alternative-view');
    assert.equal(await page.locator('#wide .alternative-text').textContent(),text);
    assert.equal(await page.locator('#wide .proof-meta').textContent(),'Готово к проверке');
    // Reach the external view by keyboard, then update props without replacing
    // the template or making the focused control disabled.
    await page.evaluate(()=>{
      const host=document.getElementById('wide');
      const before=document.createElement('button');before.id='focus-start';before.textContent='Начало проверки фокуса';
      host.before(before);before.focus();
    });
    await page.keyboard.press('Tab');
    assert.equal(await page.locator('#wide .alternative-view').evaluate(el=>document.activeElement===el),true);
    await page.evaluate(()=>{
      window.proofFocusedNode=document.activeElement;
      document.getElementById('wide').setAttribute('text','Обновление без потери фокуса');
    });
    await page.waitForFunction(()=>document.querySelector('#wide .mirror-text')?.textContent==='Обновление без потери фокуса');
    const focus=await page.locator('#wide .alternative-view').evaluate(el=>({sameNode:el===window.proofFocusedNode,active:document.activeElement===el,visible:el.matches(':focus-visible')}));
    assert.deepEqual(focus,{sameNode:true,active:true,visible:true});
    report.checks.push({keyboardFocusAfterExternalProps:focus});
    await page.locator('#focus-start').evaluate(el=>el.remove());
    await page.evaluate(()=>{
      const host=document.getElementById('wide');
      host.setAttribute('text','Изменённый текст');host.setAttribute('selected','true');host.setAttribute('disabled','true');
    });
    await page.waitForFunction(()=>document.querySelector('#wide .alternative-text')?.textContent==='Изменённый текст');
    const mirror=await page.locator('#wide .mirror-text').textContent();
    report.checks.push({externalText:await page.locator('#wide .alternative-text').textContent(),mirror});
    assert.equal(mirror,'Изменённый текст');
    const state=await page.locator('#wide .alternative-view').evaluate(el=>({selected:el.dataset.selected,disabled:el.getAttribute('aria-disabled'),tabindex:el.tabIndex}));
    assert.deepEqual(state,{selected:'true',disabled:'true',tabindex:-1});
    await page.evaluate(()=>{
      const host=document.getElementById('wide'),parent=host.parentNode;
      window.proofRemountEvents=[];
      for(const name of ['sf-disconnected','sf-connected','sf-after-render'])
        host.addEventListener(name,()=>window.proofRemountEvents.push(name),{once:true});
      host.remove();parent.append(host);
    });
    await page.waitForFunction(()=>window.proofRemountEvents.includes('sf-after-render'));
    assert.deepEqual(await page.evaluate(()=>window.proofRemountEvents),['sf-disconnected','sf-connected','sf-after-render']);
    assert.equal(await page.locator('#wide .alternative-text').textContent(),'Изменённый текст');
    assert.equal(await page.locator('#wide .proof-meta').textContent(),'Готово к проверке');
    await page.evaluate(()=>document.getElementById('wide').setAttribute('template','default'));
    await page.waitForSelector('#wide .sf-list-item-container');
    assert.equal(await page.locator('#wide .sf-list-item-container').textContent(),'Изменённый текст');
    assert.equal(await page.locator('#wide .sf-list-item').getAttribute('aria-disabled'),'true');
    if(lifecycle) {
      await page.waitForFunction(()=>document.getElementById('wide')._externalTemplateModule===null);
      const resources=await page.evaluate(()=>({active:window.proofResources,destroyCalls:window.proofDestroyCalls}));
      report.checks.push({resourcesAfterDefault:resources});
      assert.equal(resources.active,0);assert.equal(resources.destroyCalls,2);
      // A component-specific disconnect callback must not bypass base cleanup.
      await page.evaluate(()=>document.getElementById('wide').setAttribute('template','foundation-proof'));
      await page.waitForFunction(()=>window.proofResources===1);
      await page.evaluate(()=>{const host=document.getElementById('wide');host.onDisconnected=()=>{};host.remove();});
      assert.equal(await page.evaluate(()=>window.proofResources),0);
    }
    report.checks.push({externalTemplate:{differentDOM:true,props:state,slotPreserved:true,remount:true,returnToDefault:true}});
  }
  if(formScenario) {
    await page.waitForSelector('#proof-input input');
    const input=page.locator('#proof-input input');
    assert.equal(await page.locator('#proof-form').evaluate(form=>form.checkValidity()),false);
    await page.evaluate(()=>{window.formEvents=0;window.formHost=document.getElementById('proof-input');window.formHost.onInput(()=>window.formEvents++);});
    await input.focus(); await page.keyboard.type('Документация');
    assert.equal(await page.locator('#proof-form').evaluate(form=>new FormData(form).get('title')),'Документация');
    await input.evaluate(el=>{window.originalField=el;el.setSelectionRange(2,5);});
    await page.evaluate(()=>{window.formHost.setAttribute('invalid','true');window.formHost.setAttribute('error-message','Исправьте название');});
    await page.waitForFunction(()=>document.querySelector('#proof-input input').getAttribute('aria-invalid')==='true');
    const errorState=await input.evaluate(el=>({same:el===window.originalField,value:el.value,focused:document.activeElement===el,start:el.selectionStart,end:el.selectionEnd,error:document.getElementById(el.getAttribute('aria-errormessage'))?.textContent.trim()}));
    assert.deepEqual(errorState,{same:true,value:'Документация',focused:true,start:2,end:5,error:'Исправьте название'});
    await page.evaluate(()=>window.formHost.setAttribute('disabled','true'));
    await page.waitForFunction(()=>document.querySelector('#proof-input input').disabled);
    assert.equal(await page.locator('#proof-form').evaluate(form=>new FormData(form).has('title')),false);
    await page.evaluate(()=>{window.formHost.removeAttribute('disabled');window.formHost.setAttribute('readonly','true');});
    await page.waitForFunction(()=>{const el=document.querySelector('#proof-input input');return el.readOnly&&!el.disabled;});
    assert.equal(await page.locator('#proof-form').evaluate(form=>new FormData(form).get('title')),'Документация');
    await page.evaluate(()=>{window.formHost.removeAttribute('readonly');window.formHost.removeAttribute('invalid');window.formHost.remove();document.getElementById('proof-form').prepend(window.formHost);});
    await page.waitForSelector('#proof-input input');
    const beforeEvents=await page.evaluate(()=>window.formEvents);
    await input.focus();await page.keyboard.press('End');await page.keyboard.type('!');
    assert.equal(await page.evaluate(()=>window.formEvents),beforeEvents+1);
    report.checks.push({form:{nativeRequired:true,submission:true,errorState,disabledExcluded:true,readonlyIncluded:true,remountEventOnce:true}});
    if(formView) {
      const typed=await input.inputValue();
      await page.evaluate(()=>window.formHost.setAttribute('template','form-proof'));
      await page.waitForSelector('#proof-input .form-alternative input');
      assert.equal(await input.inputValue(),typed,'External view must receive current user-entered value');
      await input.fill('Изменено во внешнем виде');
      await page.evaluate(()=>window.formHost.setAttribute('template','default'));
      await page.waitForSelector('#proof-input .sf-input-field input');
      assert.equal(await input.inputValue(),'Изменено во внешнем виде','Built-in view must retain external user edits');
      await page.locator('#proof-form').evaluate(form=>form.reset());
      assert.equal(await input.inputValue(),'','Native reset must still use the original default');
      await page.evaluate(()=>window.formHost.setAttribute('value','Программное значение'));
      await page.waitForFunction(()=>document.querySelector('#proof-input input').value==='Программное значение');
      await page.evaluate(()=>window.formHost.setAttribute('template','form-proof'));
      await page.waitForSelector('#proof-input .form-alternative input');
      assert.equal(await input.inputValue(),'Программное значение');
      report.checks.push({formView:{valuePreservedBothDirections:true}});
    }
  }
  if(process.argv.includes('--textarea-view')) {
    await page.evaluate(()=>{const form=document.createElement('form');form.id='textarea-form';const host=document.createElement('sf-textarea');host.id='proof-textarea';host.setAttribute('label','Описание');host.setAttribute('name','description');form.append(host);document.body.append(form);});
    const field=page.locator('#proof-textarea textarea');
    await field.waitFor();await field.fill('Первая строка\nВторая строка');
    await page.evaluate(()=>document.getElementById('proof-textarea').setAttribute('template','form-proof'));
    await page.waitForSelector('#proof-textarea .textarea-alternative textarea');
    assert.equal(await field.inputValue(),'Первая строка\nВторая строка','Textarea external context preserves typed multiline value');
    await field.fill('Новый текст\nИз внешнего вида');
    await page.evaluate(()=>document.getElementById('proof-textarea').setAttribute('template','default'));
    await page.waitForSelector('#proof-textarea .sf-textarea textarea');
    assert.equal(await field.inputValue(),'Новый текст\nИз внешнего вида');
    assert.equal(await page.locator('#textarea-form').evaluate(form=>new FormData(form).get('description')),'Новый текст\nИз внешнего вида');
    await page.locator('#textarea-form').evaluate(form=>form.reset());
    assert.equal(await field.inputValue(),'');
    await page.evaluate(()=>document.getElementById('proof-textarea').setAttribute('value','Программный текст'));
    await page.waitForFunction(()=>document.querySelector('#proof-textarea textarea').value==='Программный текст');
    await page.evaluate(()=>document.getElementById('proof-textarea').setAttribute('template','form-proof'));
    await page.waitForSelector('#proof-textarea .textarea-alternative textarea');
    assert.equal(await field.inputValue(),'Программный текст');
    report.checks.push({textareaView:{roundtrip:true,formData:true,nativeReset:true,controlledValue:true}});
  }
  if(process.argv.includes('--table')) {
    await page.evaluate(()=>{const table=document.createElement('sf-table');table.id='proof-table';table.setAttribute('table-settings-key','foundation-test');document.body.append(table);});
    await page.waitForFunction(()=>typeof document.getElementById('proof-table').setRows==='function');
    await page.evaluate(()=>{const table=document.getElementById('proof-table');table.setColumns([{key:'name',label:'Название'}]);table.setRows([{id:1,name:'Документация'},{id:2,name:'Компоненты'}]);});
    await page.waitForFunction(()=>document.getElementById('proof-table').textContent.includes('Документация'));
    const data=await page.evaluate(()=>{const d=document.getElementById('proof-table').getTableData();return {names:d.rows.map(r=>r.name),columns:d.columns.map(c=>c.key)};});
    assert.deepEqual(data.names,['Документация','Компоненты']);
    report.checks.push({tableData:data});
    const view=page.locator('#proof-table').getByRole('button',{name:'Посмотреть',exact:true}).first();
    await view.evaluate(el=>window.tableDialogTrigger=el);
    // WebKit does not focus a button on pointer click. Test keyboard focus return
    // with an actual keyboard origin, not an assumption about mouse behavior.
    const tabTrace=[];
    const tableTabKey=process.env.SF_TEST_TABLE_TAB_KEY || 'Tab';
    assert.ok(['Tab','Alt+Tab'].includes(tableTabKey));
    for(let step=0;step<80 && !await view.evaluate(el=>document.activeElement===el);step++) {
      await page.keyboard.press(tableTabKey);
      tabTrace.push(await page.evaluate(()=>({tag:document.activeElement?.tagName,label:document.activeElement?.getAttribute('aria-label')})));
    }
    report.checks.push({tableTabNavigation:{key:tableTabKey,trace:tabTrace,target:await view.evaluate(el=>({tag:el.tagName,tabIndex:el.tabIndex,disabled:el.disabled}))}});
    assert.equal(await view.evaluate(el=>document.activeElement===el),true,'Row action must be reachable by Tab');
    await page.keyboard.press('Enter');
    await page.waitForFunction(()=>document.querySelector('#proof-table sf-modal')?.openState===true);
    report.checks.push({tableFocusAtOpen:await page.evaluate(()=>{
      const modal=document.querySelector('#proof-table sf-modal'),trigger=window.tableDialogTrigger;
      return {triggerConnected:trigger.isConnected,savedIsTrigger:modal._lastActive===trigger,savedTag:modal._lastActive?.tagName,savedConnected:modal._lastActive?.isConnected};
    })});
    await page.keyboard.press('Escape');
    await page.waitForFunction(()=>document.querySelector('#proof-table sf-modal')?.openState===false);
    try {await page.waitForFunction(()=>document.activeElement===window.tableDialogTrigger,{},{timeout:3000});}
    catch(error) {
      report.checks.push({tableFocusAfterClose:await page.evaluate(()=>{
        const modal=document.querySelector('#proof-table sf-modal');
        return {triggerConnected:window.tableDialogTrigger.isConnected,activeTag:document.activeElement?.tagName,activeLabel:document.activeElement?.getAttribute('aria-label'),savedConnected:modal?._lastActive?.isConnected,modalState:modal?._modalState};
      })});throw error;
    }
    for(let repeat=0;repeat<2;repeat++) {
      assert.equal(await view.evaluate(el=>el===window.tableDialogTrigger),true);
      await page.keyboard.press('Enter');
      await page.waitForFunction(()=>document.querySelector('#proof-table sf-modal')?.openState===true);
      await page.keyboard.press('Escape');
      await page.waitForFunction(()=>document.querySelector('#proof-table sf-modal')?.openState===false && document.activeElement===window.tableDialogTrigger);
    }
    report.checks.push({tableDialog:{openedByRowAction:true,tabReachable:true,tabKey:tableTabKey,openedWithEnter:true,escapeClosed:true,originalTriggerFocusRestored:true,keyboardReopenCycles:2}});
    if(process.argv.includes('--table-modal-detach')) {
      await page.keyboard.press('Enter');
      await page.waitForFunction(()=>document.querySelector('#proof-table sf-modal')?.openState===true);
      const opened=await page.evaluate(()=>{const modal=document.querySelector('#proof-table sf-modal');return {openCount:modal.constructor.openCount,bodyLocked:document.body.classList.contains('overflow-hidden')};});
      assert.equal(opened.openCount,1);assert.equal(opened.bodyLocked,true);
      const removed=await page.evaluate(()=>{
        const table=document.getElementById('proof-table'),modal=table.querySelector('sf-modal');window.modalDetachedTable=table;
        table.remove();
        return {tableMounted:table._isMounted,modalMounted:modal._isMounted,openCount:modal.constructor.openCount,stackLength:modal.constructor.stack.length,registered:modal.constructor.registry.has(modal.modalId),bodyLocked:document.body.classList.contains('overflow-hidden'),htmlLocked:document.documentElement.classList.contains('overflow-hidden')};
      });
      report.checks.push({tableModalDetach:{opened,removed}});
      assert.equal(removed.tableMounted,false);assert.equal(removed.modalMounted,false);assert.equal(removed.openCount,0);assert.equal(removed.stackLength,0);assert.equal(removed.registered,false);assert.equal(removed.bodyLocked,false);assert.equal(removed.htmlLocked,false);
      await page.evaluate(()=>document.body.append(window.modalDetachedTable));
      await page.waitForFunction(()=>document.getElementById('proof-table')?._isMounted===true);
      await page.waitForFunction(()=>document.getElementById('proof-table').state.modalOpen===false && document.querySelector('#proof-table sf-modal')?.openState===false);
      await view.click();
      await page.waitForFunction(()=>document.querySelector('#proof-table sf-modal')?.openState===true);
      await page.keyboard.press('Escape');
      await page.waitForFunction(()=>document.querySelector('#proof-table sf-modal')?.constructor.openCount===0);
      report.checks.push({tableModalRemount:{reopened:true,escapeReleasedStack:true}});
    }
    if(process.argv.includes('--table-filter')) {
      await page.evaluate(()=>{
        const table=document.getElementById('proof-table');
        table.setFilterFields([{key:'name',label:'Название',filter:{enabled:true,control:'text',defaultOperator:'contains'}}]);
        table.setTemplates([{key:'default',label:'Все',selected:true,default:true,data:{tags:{name:{label:'Название',active:true,control:'text'}},values:{}}}]);
        window.tableFilterEvents=[];
        table.addEventListener('onFilterUpdate',event=>window.tableFilterEvents.push(structuredClone(event.detail)));
      });
      // The custom-element host is display:contents; interact with its real control.
      const tag=page.locator('#proof-table sf-tag').filter({hasText:'Название'}).first().locator(':scope > [role="button"]');
      await tag.waitFor({state:'visible'});
      report.checks.push({tableFilterTagGeometry:await tag.evaluate(el=>({html:el.innerHTML,host:{x:el.getBoundingClientRect().x,y:el.getBoundingClientRect().y,width:el.getBoundingClientRect().width,height:el.getBoundingClientRect().height},display:getComputedStyle(el).display,viewport:{width:innerWidth,height:innerHeight},scroll:{x:scrollX,y:scrollY}}))});
      await tag.click();
      const input=page.locator('input[name="sf_filter_name"]');
      await input.fill('Отменить');
      await input.press('Tab');
      const snapshot=()=>page.evaluate(()=>({values:document.getElementById('proof-table').getCurrentFilterValues(),events:window.tableFilterEvents}));
      const pending=await snapshot();
      assert.deepEqual(pending.values,{}); assert.equal(pending.events.length,0);
      await page.getByRole('button',{name:'Сбросить',exact:true}).click();
      await page.waitForFunction(()=>document.querySelector('input[name="sf_filter_name"]')?.value==='');
      const reset=await snapshot();
      assert.deepEqual(reset.values,{}); assert.equal(reset.events.length,0);
      await input.fill('Документация'); await input.press('Tab');
      await page.getByRole('button',{name:'Применить',exact:true}).click();
      await page.waitForFunction(()=>window.tableFilterEvents.length===1);
      const applied=await snapshot();
      assert.equal(applied.values.name.value,'Документация');
      assert.equal(applied.events.length,1);
      assert.equal(applied.events[0].values.name.value,'Документация');
      report.checks.push({tableFilter:{pending,reset,applied}});
      await tag.click();
      await input.fill('Не применять'); await input.press('Tab');
      await page.locator('body').click({position:{x:1,y:1}});
      await page.waitForFunction(()=>document.getElementById('proof-table').state.contextMenu.open===false);
      const canceled=await snapshot();
      assert.equal(canceled.values.name.value,'Документация'); assert.equal(canceled.events.length,1);
      await tag.click();
      await input.waitFor({state:'visible'});
      const reopened=await input.inputValue();
      report.checks.push({tableFilterCancel:{canceled,reopened}});
      assert.equal(reopened,'Документация','Dismissed draft must not reappear when reopening the filter');
      await input.fill('Компоненты'); await input.press('Tab');
      await page.getByRole('button',{name:'Применить',exact:true}).click();
      await page.waitForFunction(()=>window.tableFilterEvents.length>=2);
      const reapplied=await snapshot();
      assert.equal(reapplied.events.length,2);
      assert.equal(reapplied.values.name.value,'Компоненты');
      assert.equal(reapplied.events[1].values.name.value,'Компоненты');
      report.checks.push({tableFilterReapply:reapplied});
      if(process.argv.includes('--table-lifecycle')) {
        await tag.click(); await input.waitFor({state:'visible'});
        await page.evaluate(()=>{
          const sibling=document.createElement('sf-table');sibling.id='proof-table-sibling';sibling.setAttribute('table-settings-key','foundation-sibling');document.body.append(sibling);
        });
        await page.waitForFunction(()=>typeof document.getElementById('proof-table-sibling')?.setRows==='function');
        await page.evaluate(()=>{const sibling=document.getElementById('proof-table-sibling');sibling.setColumns([{key:'name',label:'Название'}]);sibling.setRows([{id:9,name:'Независимая таблица'}]);});
        await page.waitForFunction(()=>document.getElementById('proof-table-sibling')?.textContent.includes('Независимая таблица'));
        assert.equal(await input.isVisible(),true,'Rendering another table must not clear the active portal');
        await page.evaluate(()=>document.getElementById('proof-table-sibling').remove());
        assert.equal(await input.isVisible(),true,'Removing another table must not clear the active portal');
        report.checks.push({tablePortalIsolation:{siblingRenderPreservedMenu:true,siblingRemovalPreservedMenu:true}});
        const detached=await page.evaluate(()=>{
          const table=document.getElementById('proof-table');window.detachedTable=table;
          table.remove();
          return {mounted:table._isMounted,contextBound:table._contextEventBound,portalControls:document.querySelectorAll('#sf-table-portal sf-context-menu').length};
        });
        report.checks.push({tableDetached:detached});
        assert.equal(detached.mounted,false); assert.equal(detached.contextBound,false); assert.equal(detached.portalControls,0);
        await page.evaluate(()=>document.body.append(window.detachedTable));
        await page.waitForFunction(()=>document.getElementById('proof-table')?._isMounted===true);
        await tag.click(); await input.waitFor({state:'visible'});
        assert.equal(await input.inputValue(),'Компоненты');
        await input.fill('После подключения'); await input.press('Tab');
        await page.getByRole('button',{name:'Применить',exact:true}).click();
        await page.waitForFunction(()=>window.tableFilterEvents.length>=3);
        const remounted=await snapshot();
        assert.equal(remounted.events.length,3);assert.equal(remounted.values.name.value,'После подключения');
        report.checks.push({tableRemounted:remounted});
        await page.evaluate(()=>document.getElementById('proof-table').setAttribute('template','lifecycle-proof'));
        await page.waitForFunction(()=>document.querySelector('#proof-table .table-lifecycle-proof') && window.tableViewResources===1);
        for(let cycle=0;cycle<2;cycle++) {
          const released=await page.evaluate(()=>{const table=document.getElementById('proof-table');table.remove();return {mounted:table._isMounted,moduleReleased:table._externalTemplateModule===null,resources:window.tableViewResources,destroyed:window.tableViewDestroyed};});
          report.checks.push({tableExternalReleased:{cycle,...released}});
          assert.equal(released.mounted,false);assert.equal(released.moduleReleased,true);assert.equal(released.resources,0);assert.equal(released.destroyed,cycle+1);
          if(cycle===0){await page.evaluate(()=>document.body.append(window.detachedTable));await page.waitForFunction(()=>window.tableViewResources===1 && document.querySelector('#proof-table .table-lifecycle-proof'));}
        }
      }
    }
  }
  if(races) {
    for(const target of ['fast','default','disconnect']) {
      const slow='race-slow-'+target;
      await page.evaluate(({slow,target})=>{
        window.raceRenders=[]; window.raceDone=false;
        const host=document.createElement('sf-list-item');host.id='race-host';host.setAttribute('text','Race');
        const original=host.resolveTemplateResult;
        host.resolveTemplateResult=async function(...args) {
          const name=this.componentTemplateName;
          try{return await original.apply(this,args);}finally{if(name===slow)window.raceDone=true;}
        };
        host.setAttribute('template',slow);document.body.append(host);window.raceHost=host;
      },{slow,target});
      await new Promise((resolve,reject)=>{
        const started=Date.now(); const timer=setInterval(()=>{
          if(raceRequests.has(slow)){clearInterval(timer);resolve();}
          else if(Date.now()-started>10000){clearInterval(timer);reject(new Error('Delayed request not reached: '+slow));}
        },10);
      });
      await page.evaluate(target=>{
        if(target==='disconnect')window.raceHost.remove();
        else window.raceHost.setAttribute('template',target==='fast'?'race-fast':'default');
      },target);
      if(target==='fast')await page.waitForSelector('#race-host .race-view');
      if(target==='default')await page.waitForSelector('#race-host .sf-list-item-wrap');
      raceGates.get(slow).release();
      await page.waitForFunction(()=>window.raceDone);
      const state=await page.evaluate(()=>({owner:window.raceHost._externalTemplateModule?.proofName||null,renders:window.raceRenders,connected:window.raceHost.isConnected}));
      assert.equal(state.owner,target==='fast'?'race-fast':null);
      assert.ok(!state.renders.includes(slow),JSON.stringify(state));
      assert.equal(state.connected,target!=='disconnect');
      report.checks.push({delayedNetworkRace:target,state});
      await page.evaluate(()=>window.raceHost.remove());
    }
  }
  assert.deepEqual(report.errors,[]);
  const screenshot=path.join(work,'container-proof.png');await page.screenshot({path:screenshot,fullPage:true});
  assert.deepEqual(missingAssets,[]);
  assert.deepEqual(report.errors,[]);
  if(coreRuntime) {
    assert.ok(requestedAssets.some(asset=>asset.relative==='core/js/core.js'&&asset.file.startsWith(core+'/')));
    assert.ok(requestedAssets.some(asset=>/^smart\/list-item\/js\/list-item(?:\.min)?\.js$/.test(asset.relative)&&asset.file.startsWith(smartRoot+path.sep)));
    assert.equal(await page.evaluate(()=>Boolean(window.SF?.Loader)),true);
  }
  report.screenshot=screenshot;report.pass=true;
} finally {
  await browser.close();
  if(process.argv[2])fs.writeFileSync(process.argv[2],JSON.stringify(report,null,2)+'\n');
  console.log(JSON.stringify(report));
}
