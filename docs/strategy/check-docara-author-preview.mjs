import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
const require=createRequire(process.env.SF_NODE_PACKAGE_JSON || import.meta.url);
const root=path.resolve(process.env.DOCARA_PREVIEW_ROOT||'docs/site/.docara-preview/output/page');
const routePath=process.env.DOCARA_PREVIEW_ROUTE||'/ru/';
const expectedTitle=process.env.DOCARA_PREVIEW_TITLE||'Документация';
const manifest=JSON.parse(fs.readFileSync(path.join(root,'preview.json'),'utf8'));
assert.equal(manifest.accepted_build_receipt,false);
const origin='https://docara-preview.invalid';
const report={root,routePath,expectedTitle,scope:'Local page preview only; not published site or new Framework candidate',browser:null,missing:[],errors:[],screens:[],pass:false};
const browser=await require('playwright').chromium.launch({headless:true});
report.browser=browser.version();
try {
  const page=await browser.newPage();
  page.on('pageerror',error=>report.errors.push(error.message));
  await page.route('**/*',route=>{
    const url=new URL(route.request().url());
    if(url.origin!==origin){report.missing.push({url:url.href,reason:'external'});return route.abort();}
    let relative=decodeURIComponent(url.pathname).replace(/^\//,'');
    if(relative===''||url.pathname===routePath)relative='index.html';
    const file=path.resolve(root,relative);
    if(!file.startsWith(root+path.sep)||!fs.existsSync(file)||!fs.statSync(file).isFile()){
      report.missing.push({url:url.pathname,reason:'absent'});return route.fulfill({status:404,body:''});
    }
    const mime={'.js':'text/javascript','.css':'text/css','.html':'text/html','.json':'application/json','.svg':'image/svg+xml','.png':'image/png','.woff2':'font/woff2'}[path.extname(file)];
    return route.fulfill({path:file,...(mime?{contentType:mime}:{})});
  });
  for(const width of [1280,390]){
    await page.setViewportSize({width,height:900});
    await page.goto(origin+routePath,{waitUntil:'networkidle'});
    await page.evaluate(()=>document.fonts.ready);
    // Lazy images below the fold are not failures until actually visited.
    for(const image of await page.locator('img').all()) {
      await image.scrollIntoViewIfNeeded();
      await image.evaluate(el=>el.complete?undefined:new Promise(resolve=>{
        el.addEventListener('load',resolve,{once:true});
        el.addEventListener('error',resolve,{once:true});
      }));
    }
    await page.evaluate(()=>scrollTo(0,0));
    const screen=await page.evaluate(()=>({viewport:innerWidth,scroll:document.documentElement.scrollWidth,
      h1:[...document.querySelectorAll('h1')].map(el=>el.textContent.trim()),
      brokenImages:[...document.images].filter(el=>!el.complete||el.naturalWidth===0).map(el=>el.getAttribute('src')),
      framework:document.querySelector('[data-docara-framework-boot]')?.getAttribute('data-docara-framework-boot')}));
    report.screens.push(screen);
    assert.equal(screen.h1.length,1);assert.ok(screen.h1[0].includes(expectedTitle));
    assert.ok(screen.scroll<=width+1,JSON.stringify(screen));assert.deepEqual(screen.brokenImages,[]);
  }
  assert.deepEqual(report.errors,[]);assert.deepEqual(report.missing,[]);
  report.pass=true;
}finally{
  await browser.close();
  if(process.argv[2])fs.writeFileSync(process.argv[2],JSON.stringify(report,null,2)+'\n');
  console.log(JSON.stringify(report));
}
