(function(){
  function bind(root){(root||document).querySelectorAll('[data-docara-smart="docara.toc"] a[href^="#"]:not([data-docara-toc-bound])').forEach(function(link){link.dataset.docaraTocBound='1';link.addEventListener('click',function(){var component=link.closest('[data-docara-smart="docara.toc"]');if(component){component.dispatchEvent(new CustomEvent('docara-toc-navigate',{bubbles:true,detail:{href:link.getAttribute('href')}}))}})})}
  function start(){bind(document);new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(function(node){if(node.nodeType===1)bind(node)})})}).observe(document.body,{subtree:true,childList:true})}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
