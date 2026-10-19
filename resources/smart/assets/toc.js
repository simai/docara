(function(){
  var frame=0;
  function targetId(link){var hash=link.hash||link.getAttribute('href')||'';if(hash.charAt(0)!=='#')return'';try{return decodeURIComponent(hash.slice(1))}catch(error){return hash.slice(1)}}
  function links(){return Array.prototype.slice.call(document.querySelectorAll('[data-docara-smart="docara.toc"] a[href^="#"]'))}
  function headings(){var found=Object.create(null);return links().map(function(link){var id=targetId(link),heading=id?document.getElementById(id):null;if(!heading||found[id])return null;found[id]=true;return{id:id,heading:heading}}).filter(Boolean)}
  function select(id){links().forEach(function(link){var active=targetId(link)===id;link.toggleAttribute('aria-current',active);if(active)link.setAttribute('aria-current','location');var item=link.closest('.docara-outline-item');if(item)item.toggleAttribute('data-docara-active',active)})}
  // A heading is current once its top passes the reading line: 35% down the window, and never above the
  // heading's own scroll margin, so a heading an outline jump placed always counts. The jump target stays
  // current while it rests in the top band, even when the next heading is already above the line, until
  // the reader scrolls by hand. The jump may be smooth, so the pin waits for the heading to arrive.
  var pinned='';
  function line(heading){var margin=parseFloat(window.getComputedStyle(heading).scrollMarginTop)||0;return Math.max(margin+1,window.innerHeight*0.35)}
  function update(){frame=0;var items=headings();if(!items.length)return;var active=items[0],limit=line(items[0].heading);items.forEach(function(item){if(item.heading.getBoundingClientRect().top<=limit)active=item});if(window.innerHeight+window.scrollY>=document.documentElement.scrollHeight-2)active=items[items.length-1];var target=pinned?items.filter(function(item){return item.id===pinned})[0]:null,top=target?target.heading.getBoundingClientRect().top:-2;if(target&&top>=-1&&top<=limit)active=target;select(active.id)}
  function release(){if(pinned){pinned='';schedule()}}
  function pin(id){pinned=id||'';schedule()}
  function pinHash(){var hash=window.location.hash;if(!hash)return;try{pin(decodeURIComponent(hash.slice(1)))}catch(error){pin(hash.slice(1))}}
  function schedule(){if(!frame)frame=window.requestAnimationFrame(update)}
  function bind(root){(root||document).querySelectorAll('[data-docara-smart="docara.toc"] a[href^="#"]:not([data-docara-toc-bound])').forEach(function(link){link.dataset.docaraTocBound='1';link.addEventListener('click',function(){pin(targetId(link));var component=link.closest('[data-docara-smart="docara.toc"]');if(component){component.dispatchEvent(new CustomEvent('docara-toc-navigate',{bubbles:true,detail:{href:link.getAttribute('href')}}))}schedule()})})}
  function start(){bind(document);schedule();window.addEventListener('scroll',schedule,{passive:true});window.addEventListener('resize',schedule);window.addEventListener('hashchange',pinHash);pinHash();['wheel','touchmove','keydown','pointerdown'].forEach(function(type){window.addEventListener(type,release,{passive:true,capture:true})});new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(function(node){if(node.nodeType===1)bind(node)})});schedule()}).observe(document.body,{subtree:true,childList:true})}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
