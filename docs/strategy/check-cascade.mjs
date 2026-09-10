import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
const require=createRequire(process.env.SF_NODE_PACKAGE_JSON || import.meta.url);
const {chromium}=require('playwright');
const input=process.argv[3] || process.env.SF_CORE_CSS || path.resolve(process.cwd(),'../ui/distr/core/css/core.css');
const css=fs.readFileSync(input,'utf8');
const declaration=css.match(/@layer sf\.reset[^;]+;/)?.[0];
if(!declaration)throw new Error('Expected declaration not found');
const browser=await chromium.launch({headless:true});
try{
 const page=await browser.newPage();
 await page.route('**/*',route=>route.abort());
 const probe='@layer sf.base {.foundation-probe {color:rgb(255,0,0)}} @layer sf.tokens {.foundation-probe {color:rgb(0,0,255)}}';
 const results=[];
 for(const [name,content] of [['input-order',css],['declaration-first',declaration+css]]){
  await page.goto('about:blank');
  await page.setContent(`<style>${content}</style><style>${probe}</style><span class="foundation-probe">Probe</span>`);
  results.push({name,color:await page.locator('.foundation-probe').evaluate(el=>getComputedStyle(el).color)});
 }
 const report={browser:browser.version(),scope:'Isolated document; no live site mutation',input,declaration,results};
 if(process.argv[2])fs.writeFileSync(process.argv[2],JSON.stringify(report,null,2)+'\n');
 console.log(JSON.stringify(report));
}finally{await browser.close();}
