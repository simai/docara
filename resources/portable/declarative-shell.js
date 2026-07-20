(function(){
  function protectNativeLink(link){
    if(link.dataset.docaraLinkBound)return;
    link.dataset.docaraLinkBound='1';
    link.addEventListener('click',function(event){event.stopPropagation()});
    link.addEventListener('keydown',function(event){if(event.key==='Enter'){event.stopPropagation()}});
  }
  function syncDisclosure(item){
    var button=item.querySelector(':scope > .sf-menu-element > [data-docara-disclosure]');
    if(!button)return;
    var open=item.classList.contains('open')||item.hasAttribute('expanded')||item.getAttribute('aria-expanded')==='true';
    if(button.getAttribute('aria-expanded')!==String(open)){button.setAttribute('aria-expanded',String(open))}
    var title=(item.querySelector(':scope > .sf-menu-element .sf-menu-element-text')||{}).textContent||'';
    var containsCurrent=button.dataset.docaraContainsCurrent==='true';
    button.setAttribute('aria-label',(open?'Свернуть: ':'Развернуть: ')+title.trim()+(containsCurrent?', содержит текущую страницу':''));
  }
  function bindShell(){
    document.querySelectorAll('[data-docara-menu-link]').forEach(protectNativeLink);
    document.querySelectorAll('.sf-menu-item').forEach(syncDisclosure);
  }
  function revealActiveNavigation(){
    var rail=document.querySelector('.docara-sidebar');
    var active=rail&&rail.querySelector('[aria-current="page"]');
    if(!rail||!active||rail.dataset.docaraActiveRevealed)return;
    var railRect=rail.getBoundingClientRect();
    var activeRect=active.getBoundingClientRect();
    if(railRect.width<=0||railRect.height<=0||activeRect.width<=0||activeRect.height<=0)return;
    rail.dataset.docaraActiveRevealed='1';
    var inset=8;
    if(activeRect.bottom>railRect.bottom-inset){rail.scrollTop+=activeRect.bottom-(railRect.bottom-inset)}
    else if(activeRect.top<railRect.top+inset){rail.scrollTop+=activeRect.top-(railRect.top+inset)}
    window.removeEventListener('resize',scheduleActiveReveal);
  }
  var activeRevealFrame=0;
  function scheduleActiveReveal(){
    if(activeRevealFrame)return;
    activeRevealFrame=requestAnimationFrame(function(){activeRevealFrame=0;revealActiveNavigation()});
  }
  bindShell();
  function revealWhenReady(){
    var fonts=document.fonts&&document.fonts.ready?document.fonts.ready:Promise.resolve();
    var icon=window.customElements&&window.customElements.whenDefined
      ?Promise.race([window.customElements.whenDefined('sf-icon'),new Promise(function(resolve){setTimeout(resolve,800)})])
      :Promise.resolve();
    Promise.all([fonts,icon]).then(function(){
      requestAnimationFrame(function(){requestAnimationFrame(revealActiveNavigation)});
    });
  }
  window.addEventListener('resize',scheduleActiveReveal,{passive:true});
  if(document.readyState==='complete'){revealWhenReady()}
  else{window.addEventListener('load',revealWhenReady,{once:true})}
  new MutationObserver(bindShell).observe(document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['class','expanded','aria-expanded']});
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
        var target=dialog.querySelector('[aria-current="page"]')||dialog.querySelector('.docara-outline-link')||closeButton;
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
        announceSettings(result.persisted?'Тема сохранена: '+label+'.':'Тема применена. Браузер не разрешил сохранить выбор.');
      });
    });
    if(settingsReset){
      settingsReset.addEventListener('click',function(){readerTheme.reset();syncReaderSettings();announceSettings('Восстановлена настройка темы сайта.')});
    }
    var systemTheme=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)');
    if(systemTheme){systemTheme.addEventListener('change',function(){if(document.documentElement.dataset.docaraThemePreference==='system'){readerTheme.apply('system',document.documentElement.dataset.docaraThemeSource||'site')}})}
    window.addEventListener('storage',function(event){if(event.key===readerTheme.key){var preference=readerTheme.syncExternal();readerTheme.apply(preference.mode,preference.source);syncReaderSettings()}});
    syncReaderSettings();
  }
})();
