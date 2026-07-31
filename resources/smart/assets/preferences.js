(function(){
  var booted=false;
  function boot(){
    if(booted)return;
    var component=document.querySelector('[data-docara-smart="docara.preferences"]');
    var modal=document.querySelector('[data-docara-reader-settings-dialog]');
    var trigger=document.querySelector('[data-docara-reader-settings-trigger]');
    var store=window.DocaraReaderPreferences;
    if(!component||!modal||!trigger||!store)return;
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
    function sync(){
      options().forEach(function(option){
        option.checked=option.value===store.current(option.dataset.preferenceId);
      });
      document.querySelectorAll('[data-docara-reader-settings-reset]').forEach(function(reset){
        reset.hidden=!store.hasOverride();
      });
    }
    function open(){
      document.dispatchEvent(new CustomEvent('docara:open-transient',{detail:{id:modal.id}}));
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
    trigger.addEventListener('click',open);
    modal.addEventListener('modal:after-open',function(){
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
      if(!option||!current||!option.checked)return;
      var result=store.set(option.dataset.preferenceId,option.value);
      if(!result.applied)return;
      sync();
      var label=option.closest('label').querySelector('.sf-radio-button-text').textContent;
      var field=option.closest('[data-docara-preference-field]');
      var title=field&&field.querySelector('legend')?field.querySelector('legend').textContent:'';
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
  document.addEventListener('docara:preferences-ready',boot);
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});
  else boot();
})();
