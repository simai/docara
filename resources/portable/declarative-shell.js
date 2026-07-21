(function(){
  var copyNode=document.getElementById('docara-runtime-copy'),messages={};
  try{messages=JSON.parse(copyNode?copyNode.textContent:'{}')}catch(error){messages={}}
  function message(id,parameters){
    var value=typeof messages[id]==='string'?messages[id]:id;
    Object.keys(parameters||{}).forEach(function(name){value=value.split('{'+name+'}').join(String(parameters[name]))});
    return value;
  }
  function closeTransientExcept(id){
    document.querySelectorAll('dialog[data-docara-transient-dialog][open]').forEach(function(dialog){
      if(dialog.id===id)return;
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
  function trapDialogTab(dialog,event){
    if(event.key!=='Tab')return;
    var focusable=Array.from(dialog.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])'))
      .filter(function(element){
        if(element.hidden||element.getClientRects().length===0)return false;
        return !(element.matches('input[type="radio"]')&&!element.checked);
      });
    if(!focusable.length)return;
    var first=focusable[0],last=focusable[focusable.length-1];
    if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus()}
    else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus()}
    else if(!dialog.contains(document.activeElement)){event.preventDefault();(event.shiftKey?last:first).focus()}
  }
  function bindSheet(dialog){
    var trigger=document.querySelector('[data-docara-sheet-trigger][aria-controls="'+dialog.id+'"]');
    var closeButton=dialog.querySelector('[data-docara-sheet-close]');
    if(!trigger||!closeButton)return;
    function closeSheet(){
      if(typeof dialog.close==='function'&&dialog.open){dialog.close()}
      else{dialog.removeAttribute('open');trigger.setAttribute('aria-expanded','false');trigger.focus()}
    }
    function openSheet(){
      requestTransient(dialog);
      if(!dialog.open){
        if(typeof dialog.showModal==='function'){dialog.showModal()}
        else{dialog.setAttribute('open','')}
      }
      trigger.setAttribute('aria-expanded','true');
      requestAnimationFrame(function(){
        var target=dialog.querySelector('[aria-current="page"]')||dialog.querySelector('a[href]')||closeButton;
        target.focus();
      });
    }
    trigger.addEventListener('click',openSheet);
    closeButton.addEventListener('click',closeSheet);
    dialog.querySelectorAll('a[href]').forEach(function(link){link.addEventListener('click',closeSheet)});
    dialog.addEventListener('click',function(event){if(event.target===dialog){closeSheet()}});
    dialog.addEventListener('cancel',function(event){event.preventDefault();closeSheet()});
    dialog.addEventListener('keydown',function(event){trapDialogTab(dialog,event)});
    dialog.addEventListener('close',function(){trigger.setAttribute('aria-expanded','false');trigger.focus()});
    window.addEventListener('resize',function(){
      var unavailable=(dialog.id==='docara-mobile-navigation'&&window.matchMedia('(min-width: 801px)').matches)
        ||(dialog.id==='docara-outline-dialog'&&window.matchMedia('(min-width: 1153px)').matches);
      if(unavailable&&dialog.open){closeSheet()}
    },{passive:true});
  }
  document.querySelectorAll('dialog[data-docara-sheet]').forEach(bindSheet);
  document.querySelectorAll('[data-docara-language-switcher]').forEach(function(select){
    select.addEventListener('change',function(){
      var url=select.value;
      if(typeof url==='string'&&/^\/(?:(?!\.{1,2}\/)[A-Za-z0-9._~%-]+\/)*$/u.test(url)){window.location.assign(url)}
    });
  });
  var componentFilter=document.querySelector('[data-docara-component-filter]');
  if(componentFilter){
    var componentQuery=componentFilter.querySelector('[data-docara-component-filter-query]');
    var componentFamily=componentFilter.querySelector('[data-docara-component-filter-family]');
    var componentAvailability=componentFilter.querySelector('[data-docara-component-filter-availability]');
    var componentStatus=componentFilter.querySelector('[data-docara-component-filter-status]');
    var componentReset=componentFilter.querySelector('[data-docara-component-filter-reset]');
    var componentItems=Array.from(document.querySelectorAll('[data-docara-component-item]'));
    var componentSections=Array.from(document.querySelectorAll('[data-docara-component-section]'));
    var componentEmpty=document.querySelector('[data-docara-component-filter-empty]');
    var componentTotal=componentItems.length;
    function normalizeComponentFilter(value){
      value=String(value||'').trim().toLocaleLowerCase();
      return value.normalize?value.normalize('NFKC'):value;
    }
    function syncComponentFilter(){
      var query=normalizeComponentFilter(componentQuery.value);
      var family=componentFamily.value;
      var availability=componentAvailability.value;
      var visible=0;
      componentItems.forEach(function(item){
        var matchesQuery=!query||normalizeComponentFilter(item.dataset.docaraComponentSearch).includes(query);
        var matchesFamily=!family||item.dataset.docaraComponentFamily===family;
        var matchesAvailability=!availability||item.dataset.docaraComponentAvailability===availability;
        item.hidden=!(matchesQuery&&matchesFamily&&matchesAvailability);
        if(!item.hidden)visible++;
      });
      componentSections.forEach(function(section){
        section.hidden=!section.querySelector('[data-docara-component-item]:not([hidden])');
      });
      componentStatus.textContent=componentFilter.dataset.docaraComponentStatusLabel+': '+visible+' / '+componentTotal;
      componentReset.hidden=!(query||family||availability);
      componentEmpty.hidden=visible!==0;
    }
    componentFilter.addEventListener('submit',function(event){event.preventDefault()});
    componentQuery.addEventListener('input',syncComponentFilter);
    componentFamily.addEventListener('change',syncComponentFilter);
    componentAvailability.addEventListener('change',syncComponentFilter);
    componentReset.addEventListener('click',function(){
      componentQuery.value='';
      componentFamily.value='';
      componentAvailability.value='';
      syncComponentFilter();
      componentQuery.focus();
    });
    componentQuery.addEventListener('keydown',function(event){
      if(event.key==='Escape'&&(componentQuery.value||componentFamily.value||componentAvailability.value)){
        event.preventDefault();
        componentReset.click();
      }
    });
    syncComponentFilter();
  }
  var readerTheme=window.DocaraReaderTheme;
  var settingsTrigger=document.querySelector('[data-docara-reader-settings-trigger]');
  var settingsDialog=document.querySelector('[data-docara-reader-settings-dialog]');
  var settingsReset=document.querySelector('[data-docara-reader-settings-reset]');
  var settingsStatus=document.querySelector('[data-docara-reader-settings-status]');
  var themeOptions=Array.from(document.querySelectorAll('[data-docara-theme-option]'));
  function announceSettings(message){if(settingsStatus){settingsStatus.textContent='';requestAnimationFrame(function(){settingsStatus.textContent=message})}}
  function syncReaderSettings(){
    if(!readerTheme)return;
    var preference=readerTheme.preference();
    themeOptions.forEach(function(option){option.checked=option.value===preference.mode});
    if(settingsReset){settingsReset.hidden=!readerTheme.hasOverride()}
  }
  if(settingsTrigger&&settingsDialog&&readerTheme){
    settingsTrigger.addEventListener('click',function(){
      requestTransient(settingsDialog);
      if(!settingsDialog.open){settingsDialog.showModal()}
      settingsTrigger.setAttribute('aria-expanded','true');
      syncReaderSettings();
      requestAnimationFrame(function(){var selected=themeOptions.find(function(option){return option.checked});if(selected){selected.focus()}});
    });
    settingsDialog.addEventListener('close',function(){settingsTrigger.setAttribute('aria-expanded','false');settingsTrigger.focus()});
    settingsDialog.addEventListener('keydown',function(event){trapDialogTab(settingsDialog,event)});
    themeOptions.forEach(function(option){
      option.addEventListener('change',function(){
        if(!option.checked)return;
        var result=readerTheme.set(option.value);
        if(!result.applied)return;
        syncReaderSettings();
        var label=option.closest('label').querySelector('.sf-radio-button-text').textContent;
        announceSettings(result.persisted?message('reader.saved',{theme:label}):message('reader.applied_not_saved'));
      });
    });
    if(settingsReset){
      settingsReset.addEventListener('click',function(){readerTheme.reset();syncReaderSettings();announceSettings(message('reader.restored'))});
    }
    var systemTheme=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)');
    if(systemTheme){systemTheme.addEventListener('change',function(){if(document.documentElement.dataset.docaraThemePreference==='system'){readerTheme.apply('system',document.documentElement.dataset.docaraThemeSource||'site')}})}
    window.addEventListener('storage',function(event){if(event.key===readerTheme.key){var preference=readerTheme.syncExternal();readerTheme.apply(preference.mode,preference.source);syncReaderSettings()}});
    syncReaderSettings();
  }
})();
