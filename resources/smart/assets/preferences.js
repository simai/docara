(function(){
  var booted=false;
  function boot(){
    if(booted)return;
    var component=document.querySelector('[data-docara-smart="docara.preferences"]');
    var modal=document.querySelector('[data-docara-reader-settings-dialog]');
    var trigger=document.querySelector('[data-docara-reader-settings-trigger]');
    var store=window.DocaraReaderPreferences;
    if(!component||!modal||!trigger||!store)return;
    if(modal.localName==='sf-settings'){bootMenu(modal,trigger,store);return}
    booted=true;
    component.dataset.docaraPreferencesRuntime='ready';
    var copyNode=document.getElementById('docara-runtime-copy'),messages={};
    try{messages=JSON.parse(copyNode?copyNode.textContent:'{}')}catch(error){messages={}}
    function message(id,parameters){
      var value=typeof messages[id]==='string'?messages[id]:id;
      Object.keys(parameters||{}).forEach(function(name){value=value.split('{'+name+'}').join(String(parameters[name]))});
      return value;
    }
    function currentComponent(){
      return document.querySelector('[data-docara-smart="docara.preferences"]');
    }
    function options(){
      return Array.from(document.querySelectorAll('[data-docara-preference-option]'));
    }
    function announce(value){
      var statuses=Array.from(document.querySelectorAll('[data-docara-reader-settings-status]'));
      statuses.forEach(function(status){status.textContent=''});
      requestAnimationFrame(function(){
        statuses.forEach(function(status){status.textContent=value});
      });
    }
    // Each choice is a Framework button in a segmented group: the selected one
    // uses the filled primary variant, the others the outline variant.
    function paint(option){
      var choice=option.closest('[data-docara-preference-choice]');
      if(!choice)return;
      var on=option.checked;
      choice.classList.toggle('sf-button--default',on);
      choice.classList.toggle('sf-button--primary',on);
      choice.classList.toggle('active',on);
      choice.classList.toggle('sf-button--outline',!on);
      choice.classList.toggle('sf-button--on-surface',!on);
    }
    // In the menu view each choice is a menu item radio: the selected one is
    // aria-checked and shows its check mark.
    function sync(){
      options().forEach(function(option){
        var on=option.value===store.current(option.dataset.preferenceId);
        if(option.tagName==='BUTTON'){
          option.setAttribute('aria-checked',on?'true':'false');
          // The menu row shows the chosen value next to the setting's name.
          var value=on&&option.closest('[data-docara-preference-field]');
          var slot=value&&value.querySelector('[data-docara-preference-value]');
          var text=option.querySelector('.sf-button-text-container');
          if(slot&&text)slot.textContent=text.textContent.trim();
          return;
        }
        option.checked=on;
        paint(option);
      });
    }
    var menu=modal.hasAttribute('data-docara-reader-settings-menu'),menuClosedAt=0;
    // The menu lists one row per setting; a row opens a submenu with its values.
    function topItems(){
      return Array.from(modal.querySelectorAll('[data-docara-preference-submenu-trigger],[data-docara-reader-settings-reset]'));
    }
    function submenuOf(row){
      return row&&document.getElementById(row.getAttribute('aria-controls'));
    }
    function closeSubmenus(except){
      modal.querySelectorAll('[data-docara-preference-submenu-trigger]').forEach(function(row){
        if(row===except)return;
        row.setAttribute('aria-expanded','false');
        var submenu=submenuOf(row);
        if(submenu)submenu.hidden=true;
      });
    }
    function openSubmenu(row,focus){
      closeSubmenus(row);
      var submenu=submenuOf(row);
      if(!submenu)return;
      submenu.hidden=false;
      row.setAttribute('aria-expanded','true');
      if(!focus)return;
      var items=Array.from(submenu.querySelectorAll('[role="menuitemradio"]'));
      var selected=items.find(function(item){return item.getAttribute('aria-checked')==='true'})||items[0];
      if(selected)selected.focus();
    }
    // The menu opens under the button, its end edge on the button's, and keeps
    // inside the window on a phone.
    function place(){
      var rect=trigger.getBoundingClientRect(),root=document.documentElement;
      var rtl=getComputedStyle(trigger).direction==='rtl';
      root.style.setProperty('--docara-preferences-top',Math.round(rect.bottom+4)+'px');
      root.style.setProperty('--docara-preferences-end',Math.max(8,Math.round(rtl?rect.left:window.innerWidth-rect.right))+'px');
    }
    function openMenu(){
      place();
      closeSubmenus(null);
      try{modal.showPopover()}catch(error){return}
      var first=topItems()[0];
      if(first)first.focus();
    }
    function open(){
      document.dispatchEvent(new CustomEvent('docara:open-transient',{detail:{id:modal.id}}));
      if(menu){
        // A press on the button first light-dismisses the open menu; the click
        // that follows must not open it again.
        if(Date.now()-menuClosedAt<400)return;
        if(modal.matches(':popover-open')){modal.hidePopover();return}
        openMenu();
        return;
      }
      var show=function(){
        if(typeof modal.open==='function')modal.open();
        else modal.setAttribute('open','');
      };
      if(window.customElements&&customElements.whenDefined){
        customElements.whenDefined('sf-modal').then(show);
      }else{
        show();
      }
    }
    // The Framework window names itself with a generic word; the panel heading is its name.
    function name(event){
      var root=event&&event.detail&&event.detail.root?event.detail.root:(typeof modal.getModalRoot==='function'?modal.getModalRoot():null);
      if(!root||!root.setAttribute)return;
      root.setAttribute('aria-labelledby','docara-reader-settings-title');
      root.removeAttribute('aria-label');
    }
    if(!menu){
      modal.addEventListener('modal:ready',name);
      modal.addEventListener('modal:update',name);
      name();
    }
    trigger.addEventListener('click',open);
    if(menu){
      trigger.setAttribute('aria-haspopup','menu');
      // beforetoggle fires at once; toggle follows only after the click that
      // light-dismissed the menu has already reached the button.
      modal.addEventListener('beforetoggle',function(event){
        if(event.newState==='closed')menuClosedAt=Date.now();
      });
      modal.addEventListener('toggle',function(event){
        var opened=event.newState==='open';
        trigger.setAttribute('aria-expanded',opened?'true':'false');
        if(opened)sync();
        if(!opened&&(modal.contains(document.activeElement)||document.activeElement===document.body))trigger.focus();
      });
      // A row opens its submenu on click, Enter, Space or the right arrow, and
      // on hover with a mouse.
      modal.addEventListener('click',function(event){
        var row=event.target.closest&&event.target.closest('[data-docara-preference-submenu-trigger]');
        if(!row)return;
        if(row.getAttribute('aria-expanded')==='true'){closeSubmenus(null);row.focus()}
        else openSubmenu(row,true);
      });
      modal.addEventListener('pointerover',function(event){
        if(event.pointerType!=='mouse')return;
        var row=event.target.closest&&event.target.closest('[data-docara-preference-submenu-trigger]');
        if(row&&row.getAttribute('aria-expanded')!=='true')openSubmenu(row,false);
      });
      // Up and down move within the menu or the open submenu, Home and End go
      // to its ends; left and Escape close a submenu and return to its row;
      // Tab leaves the menu like Escape does.
      modal.addEventListener('keydown',function(event){
        var submenu=event.target.closest&&event.target.closest('[data-docara-preference-submenu]');
        var items=submenu?Array.from(submenu.querySelectorAll('[role="menuitemradio"]')):topItems();
        var index=items.indexOf(document.activeElement),next=-1;
        if(event.key==='ArrowDown')next=(index+1)%items.length;
        else if(event.key==='ArrowUp')next=(index-1+items.length)%items.length;
        else if(event.key==='Home')next=0;
        else if(event.key==='End')next=items.length-1;
        else if(event.key==='ArrowRight'&&!submenu&&document.activeElement.hasAttribute('data-docara-preference-submenu-trigger')){
          event.preventDefault();openSubmenu(document.activeElement,true);return;
        }
        else if(submenu&&(event.key==='ArrowLeft'||event.key==='Escape')){
          event.preventDefault();event.stopPropagation();
          var row=modal.querySelector('[aria-controls="'+submenu.id+'"]');
          closeSubmenus(null);
          if(row)row.focus();
          return;
        }
        else if(event.key==='Tab'){modal.hidePopover();return}
        else return;
        event.preventDefault();
        if(items[next])items[next].focus();
      });
    }
    modal.addEventListener('modal:after-open',function(){
      name();
      trigger.setAttribute('aria-expanded','true');
      sync();
      var selected=options().find(function(option){return option.checked});
      if(selected)selected.focus();
    });
    modal.addEventListener('modal:after-close',function(){
      trigger.setAttribute('aria-expanded','false');
      trigger.focus();
    });
    function selectOption(event){
      var option=event.target&&event.target.closest
        ?event.target.closest('[data-docara-preference-option]')
        :null;
      var current=option&&option.closest('[data-docara-smart="docara.preferences"]');
      var item=option&&option.tagName==='BUTTON';
      if(!option||!current||(item?event.type!=='click':!option.checked))return;
      var result=store.set(option.dataset.preferenceId,option.value);
      if(!result.applied)return;
      sync();
      var label=(item?option.querySelector('.sf-button-text-container')||option:option.closest('label')).textContent.trim();
      var field=option.closest('[data-docara-preference-field]');
      var heading=field&&field.querySelector('.docara-preferences-label');
      if(item)option.focus();
      var title=heading?heading.textContent.trim():'';
      announce(result.persisted?message('reader.saved',{setting:title,value:label}):message('reader.applied_not_saved'));
      current.dispatchEvent(new CustomEvent('docara-preference-change',{
        bubbles:true,
        detail:{id:option.dataset.preferenceId,value:option.value,persisted:result.persisted}
      }));
    }
    document.addEventListener('click',selectOption,true);
    document.addEventListener('change',selectOption,true);
    document.addEventListener('click',function(event){
      var reset=event.target&&event.target.closest
        ?event.target.closest('[data-docara-reader-settings-reset]')
        :null;
      if(!reset)return;
      store.reset();
      sync();
      announce(message('reader.restored'));
      var current=currentComponent();
      if(current)current.dispatchEvent(new CustomEvent('docara-preferences-reset',{bubbles:true}));
    },true);
    window.addEventListener('storage',function(event){
      if(event.key===store.key){
        store.syncExternal();
        sync();
      }
    });
    sync();
  }
  // The menu view is the Framework sf-settings menu: it opens from the button
  // (its for), draws the rows and reports each change; the page keeps the
  // values (the store the bootstrap applied before first paint) and puts them
  // back before the menu opens.
  function bootMenu(menu,trigger,store){
    booted=true;
    menu.dataset.docaraPreferencesRuntime='ready';
    var copyNode=document.getElementById('docara-runtime-copy'),messages={};
    try{messages=JSON.parse(copyNode?copyNode.textContent:'{}')}catch(error){messages={}}
    function message(id,parameters){
      var value=typeof messages[id]==='string'?messages[id]:id;
      Object.keys(parameters||{}).forEach(function(name){value=value.split('{'+name+'}').join(String(parameters[name]))});
      return value;
    }
    // The settings are the element's items: the island while the element has
    // not started, its items setting once it has read it.
    var island=menu.querySelector('script[data-sf-settings]'),declared=[];
    try{declared=island?JSON.parse(island.textContent).items||[]:[]}catch(error){declared=[]}
    function fields(){
      var items=Array.isArray(menu.items)&&menu.items.length?menu.items:declared;
      return items.filter(function(item){return item&&item.type==='choice'&&item.id});
    }
    function values(){
      var map={};
      fields().forEach(function(field){map[field.id]=store.current(field.id)});
      return map;
    }
    function restore(){if(typeof menu.setAll==='function')menu.setAll(values())}
    function announce(value){
      var status=document.querySelector('[data-docara-reader-settings-status]');
      if(!status)return;
      status.textContent='';
      requestAnimationFrame(function(){status.textContent=value});
    }
    function textOf(field,value){
      var option=(field.options||[]).filter(function(option){return option.id===value})[0];
      return option?option.text:value;
    }
    // Before sf-settings opens the menu on this press, the rows take the
    // values the reader has now (another tab may have changed them).
    trigger.addEventListener('pointerdown',restore);
    trigger.addEventListener('keydown',function(event){if(event.key==='Enter'||event.key===' ')restore()});
    customElements.whenDefined('sf-settings').then(restore);
    menu.addEventListener('sf-menu-context-opened',function(){trigger.setAttribute('aria-expanded','true')});
    menu.addEventListener('sf-menu-context-closed',function(){trigger.setAttribute('aria-expanded','false')});
    menu.addEventListener('sf-settings-changed',function(event){
      var detail=event.detail||{};
      var field=fields().filter(function(field){return field.id===detail.id})[0];
      if(!field)return;
      var result=store.set(field.id,String(detail.value));
      if(!result.applied){restore();return}
      announce(result.persisted?message('reader.saved',{setting:field.text,value:textOf(field,String(detail.value))}):message('reader.applied_not_saved'));
      menu.dispatchEvent(new CustomEvent('docara-preference-change',{bubbles:true,detail:{id:field.id,value:String(detail.value),persisted:result.persisted}}));
    });
    window.addEventListener('storage',function(event){
      if(event.key===store.key){store.syncExternal();restore()}
    });
  }
  document.addEventListener('docara:preferences-ready',boot);
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});
  else boot();
})();
