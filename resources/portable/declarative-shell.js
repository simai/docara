(function(){
  var copyNode=document.getElementById('docara-runtime-copy'),messages={};
  try{messages=JSON.parse(copyNode?copyNode.textContent:'{}')}catch(error){messages={}}
  function message(id,parameters){
    var value=typeof messages[id]==='string'?messages[id]:id;
    Object.keys(parameters||{}).forEach(function(name){value=value.split('{'+name+'}').join(String(parameters[name]))});
    return value;
  }
  function closeTransientExcept(id){
    document.querySelectorAll('[data-docara-transient-dialog]').forEach(function(dialog){
      if(dialog.id===id)return;
      var isNativeDialog=dialog.tagName&&dialog.tagName.toLowerCase()==='dialog';
      var isOpen=isNativeDialog?dialog.hasAttribute('open'):Boolean(dialog.openState||dialog.hasAttribute('open'));
      if(!isOpen)return;
      if(typeof dialog.close==='function'){dialog.close()}
      else{
        dialog.removeAttribute('open');
        var trigger=document.querySelector('[aria-controls="'+dialog.id+'"]');
        if(trigger){trigger.setAttribute('aria-expanded','false')}
      }
    });
  }
  document.addEventListener('docara:open-transient',function(event){
    closeTransientExcept(event.detail&&event.detail.id||'');
  });
  function requestTransient(dialog){
    document.dispatchEvent(new CustomEvent('docara:open-transient',{detail:{id:dialog.id}}));
  }
  function bindSheet(dialog){
    var trigger=document.querySelector('[data-docara-sheet-trigger][aria-controls="'+dialog.id+'"]');
    if(!trigger)return;
    function closeSheet(){
      if(typeof dialog.close==='function')dialog.close();
    }
    dialog.addEventListener('modal:before-open',function(){requestTransient(dialog)});
    dialog.addEventListener('modal:after-open',function(){trigger.setAttribute('aria-expanded','true')});
    dialog.addEventListener('modal:after-close',function(){trigger.setAttribute('aria-expanded','false')});
    dialog.querySelectorAll('a[href]').forEach(function(link){link.addEventListener('click',closeSheet)});
    window.addEventListener('resize',function(){
      var unavailable=(dialog.id==='docara-mobile-navigation'&&window.matchMedia('(min-width: 801px)').matches)
        ||(dialog.id==='docara-outline-dialog'&&window.matchMedia('(min-width: 1153px)').matches);
      if(unavailable&&dialog.openState){closeSheet()}
    },{passive:true});
  }
  document.querySelectorAll('sf-modal[data-docara-sheet]').forEach(bindSheet);
  document.querySelectorAll('[data-docara-breadcrumbs]').forEach(function(breadcrumbs){
    var ellipsisLabel=breadcrumbs.dataset.docaraBreadcrumbsEllipsisLabel;
    if(!ellipsisLabel)return;
    function localizeBreadcrumbEllipsis(){
      breadcrumbs.querySelectorAll('[data-sf-breadcrumbs-generated="ellipsis"][aria-label]').forEach(function(button){
        button.setAttribute('aria-label',ellipsisLabel);
      });
    }
    localizeBreadcrumbEllipsis();
    new MutationObserver(localizeBreadcrumbEllipsis).observe(breadcrumbs,{childList:true});
  });
  document.querySelectorAll('[data-docara-language-switcher]').forEach(function(select){
    select.addEventListener('change',function(){
      var url=select.value;
      if(typeof url==='string'&&/^\/(?:(?!\.{1,2}\/)[A-Za-z0-9._~%-]+\/)*$/u.test(url)){window.location.assign(url)}
    });
  });
})();
