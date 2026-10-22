(function(){
  /* The documentation sheet shows the sidebar navigation. The page carries that tree once: before any other script can change it, the sheet template takes a copy of the sidebar as the server rendered it, without element ids. */
  document.querySelectorAll('template[data-docara-sheet-template][data-docara-sheet-source="sidebar"]').forEach(function(template){
    var source=document.querySelector('aside[data-docara-region="sidebar"]>.docara-sidebar-panel>.docara-sidebar-scroll>.sf-scrollbar__viewport');
    var section=template.content.firstElementChild;
    if(!source||!section)return;
    Array.prototype.forEach.call(source.childNodes,function(node){section.appendChild(node.cloneNode(true))});
    Array.prototype.forEach.call(section.querySelectorAll('[id]'),function(element){element.removeAttribute('id')});
  });
  /* The sidebar's "Made with Docara" footer stays under the menu in the sheet too: a copy closes the sheet, after its scrolling content. */
  (function(){
    var credit=document.querySelector('aside[data-docara-region="sidebar"]>.docara-sidebar-panel>[data-docara-sidebar-credit]');
    var sheet=document.getElementById('docara-mobile-navigation');
    if(credit&&sheet&&!sheet.querySelector(':scope>[data-docara-sidebar-credit]')){sheet.appendChild(credit.cloneNode(true))}
  })();
  /* The sticky sidebar and outline start under the header and fill the rest of the window. The header's height depends on the brand size and what the header holds, so the shell measures it into --docara-header-block-size; until then the stylesheet's estimate stands. */
  (function(){
    var header=document.querySelector('body[data-docara-shell]>.docara-header');
    if(!header)return;
    function measure(){
      var size=header.getBoundingClientRect().height;
      if(size>0)document.documentElement.style.setProperty('--docara-header-block-size',size+'px');
    }
    measure();
    if(typeof ResizeObserver==='function'){new ResizeObserver(measure).observe(header)}
    else{window.addEventListener('resize',measure)}
  })();
  var copyNode=document.getElementById('docara-runtime-copy'),messages={};
  try{messages=JSON.parse(copyNode?copyNode.textContent:'{}')}catch(error){messages={}}
  function message(id,parameters){
    var value=typeof messages[id]==='string'?messages[id]:id;
    Object.keys(parameters||{}).forEach(function(name){value=value.split('{'+name+'}').join(String(parameters[name]))});
    return value;
  }
  function localizeCodeCopy(root){
    var selector='[data-docara-code-block] button[data-clipboard-target],[data-docara-code-block] button[data-docara-code-copy]';
    var scope=root&&root.querySelectorAll?root:document,buttons=[];
    var direct=root&&root.nodeType===1&&root.closest?root.closest(selector):null;
    if(direct){buttons.push(direct)}
    scope.querySelectorAll(selector).forEach(function(button){if(buttons.indexOf(button)===-1){buttons.push(button)}});
    buttons.forEach(function(button){
      var block=button.closest('[data-docara-code-block]');
      if(block&&!block.closest('[data-docara-example-panel]')){
        var code=block.querySelector('code');
        var language=(code&&code.dataset.requestedLang||'').trim().toLowerCase();
        var head=block.querySelector(':scope > .sf--highlight-head,:scope > [data-docara-code-fallback]');
        var title=head&&head.querySelector(':scope > span');
        var languageLabels={html:'HTML',xhtml:'HTML',css:'CSS',js:'JavaScript',javascript:'JavaScript',json:'JSON',php:'PHP',bash:'Bash',shell:'Shell',markdown:'Markdown',md:'Markdown',text:'Code',plaintext:'Code'};
        var languageLabel=languageLabels[language]||language||'Code';
        if(head){head.classList.add('docara-code-header')}
        if(title&&title.textContent!==languageLabel){title.textContent=languageLabel}
        button.classList.remove('sf-button','sf-button--size-1/3','sf-button--on-surface','sf-button--link');
        button.classList.add('docara-code-copy','sf-icon-button','sf-icon-button--icon','sf-icon-button--on-surface','sf-icon-button--link','sf-icon-button--size-1','inline-grid','items-cross-center','content-main-center','m-0');
        var codeIcon=button.querySelector('.sf-icon');
        if(codeIcon){codeIcon.classList.add('sf-icon-regular')}
        block.dataset.docaraCodeLanguage=language||'code';
        var actions=head&&head.querySelector(':scope > [data-docara-code-actions]');
        if(head&&!actions){
          actions=document.createElement('div');
          actions.dataset.docaraCodeActions='';
          actions.className='docara-code-actions flex items-cross-center gap-1/3';
          head.appendChild(actions);
        }
        if(actions&&button.parentElement!==actions){actions.appendChild(button)}
        var wrapButton=actions&&actions.querySelector('[data-docara-code-wrap-toggle]');
        if(actions&&block.dataset.docaraCodeWrap!=='false'&&!wrapButton){
          wrapButton=document.createElement('button');
          wrapButton.type='button';
          wrapButton.dataset.docaraCodeWrapToggle='';
          wrapButton.dataset.wrapIcon='wrap_text';
          wrapButton.dataset.unwrapIcon='format_text_overflow';
          wrapButton.className='docara-code-wrap sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 inline-grid items-cross-center content-main-center m-0';
          wrapButton.innerHTML='<span aria-hidden="true" class="sf-icon sf-icon-regular">wrap_text</span><span class="sf-button-text-container sr-only"></span>';
          actions.insertBefore(wrapButton,button);
        }
        if(wrapButton){
          syncCodeWrap(block,wrapButton,sourceWrapActive);
          if(!wrapButton.dataset.docaraCodeWrapBound){
            wrapButton.dataset.docaraCodeWrapBound='1';
            wrapButton.addEventListener('click',function(){setSourceWrap(!sourceWrapActive,true)});
            document.addEventListener('docara:source-wrap-change',function(event){
              syncCodeWrap(block,wrapButton,Boolean(event.detail&&event.detail.active));
            });
          }
        }
      }
      var text=button.querySelector('.sf-button-text-container'),icon=button.querySelector('.sf-icon');
      var copied=(text&&(text.textContent||'').trim()===message('code.copied'))||(icon&&icon.textContent.trim()==='check');
      var label=message(copied?'code.copied':'code.copy');
      if(text&&text.textContent!==label){text.textContent=label}
      button.setAttribute('aria-label',label);
      if(button.dataset.docaraCodeCopy!==undefined&&!button.dataset.docaraCodeCopyBound){
        button.dataset.docaraCodeCopyBound='1';
        button.addEventListener('click',function(){
          var code=button.closest('[data-docara-code-block]')&&button.closest('[data-docara-code-block]').querySelector('code');
          if(!code)return;
          var value=code.textContent||'',done=function(){
            var text=button.querySelector('.sf-button-text-container'),icon=button.querySelector('.sf-icon');
            if(text){text.textContent=message('code.copied')}
            if(icon){icon.textContent='check'}
            button.setAttribute('aria-label',message('code.copied'));
            window.setTimeout(function(){
              if(text){text.textContent=message('code.copy')}
              if(icon){icon.textContent='content_copy'}
              button.setAttribute('aria-label',message('code.copy'));
            },1600);
          };
          if(navigator.clipboard&&typeof navigator.clipboard.writeText==='function'){
            navigator.clipboard.writeText(value).then(done).catch(function(){});
            return;
          }
          var input=document.createElement('textarea');input.value=value;input.setAttribute('readonly','');input.style.position='fixed';input.style.inset='0 auto auto -9999px';document.body.appendChild(input);input.select();
          try{if(document.execCommand('copy'))done()}finally{input.remove()}
        });
      }
    });
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
    var linksBound=false;
    function hydrateSheet(){
      var template=dialog.querySelector('template[data-docara-sheet-template]');
      var target=dialog.querySelector('[data-docara-sheet-lazy-content]');
      if(template&&target){target.replaceWith(template.content.cloneNode(true));template.remove()}
      if(!linksBound){
        linksBound=true;
        dialog.querySelectorAll('a[href]').forEach(function(link){link.addEventListener('click',closeSheet)});
      }
    }
    function closeSheet(){
      if(typeof dialog.close==='function'&&dialog.open){dialog.close()}
      else{dialog.removeAttribute('open');trigger.setAttribute('aria-expanded','false');trigger.focus()}
    }
    function openSheet(){
      hydrateSheet();
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
    dialog.addEventListener('click',function(event){if(event.target===dialog){closeSheet()}});
    dialog.addEventListener('cancel',function(event){event.preventDefault();closeSheet()});
    dialog.addEventListener('keydown',function(event){trapDialogTab(dialog,event)});
    dialog.addEventListener('close',function(){trigger.setAttribute('aria-expanded','false');trigger.focus()});
    window.addEventListener('resize',function(){
      var unavailable=(dialog.id==='docara-mobile-navigation'&&window.matchMedia('not all and (max-width: 50rem)').matches)
        ||(dialog.id==='docara-outline-dialog'&&window.matchMedia('not all and (max-width: 72rem)').matches);
      if(unavailable&&dialog.open){closeSheet()}
    },{passive:true});
  }
  document.querySelectorAll('dialog[data-docara-sheet]').forEach(bindSheet);
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
  document.querySelectorAll('[data-docara-language-menu]').forEach(function(menu){
    var trigger=menu.querySelector('[data-docara-language-trigger]');
    if(!trigger)return;
    function closeMenu(focus){
      menu.open=false;
      trigger.setAttribute('aria-expanded','false');
      if(focus)trigger.focus();
    }
    menu.addEventListener('toggle',function(){trigger.setAttribute('aria-expanded',menu.open?'true':'false')});
    menu.addEventListener('keydown',function(event){if(event.key==='Escape'&&menu.open){event.preventDefault();closeMenu(true)}});
    document.addEventListener('click',function(event){if(menu.open&&!menu.contains(event.target)){closeMenu(false)}});
  });
  document.querySelectorAll('[data-docara-tabs]').forEach(function(container){
    var tabs=Array.from(container.querySelectorAll(':scope > [role="tablist"] > [role="tab"]'));
    var panels=Array.from(container.querySelectorAll(':scope > [role="tabpanel"]'));
    function select(tab,focus){
      tabs.forEach(function(candidate){
        var active=candidate===tab;
        candidate.setAttribute('aria-selected',active?'true':'false');
        candidate.tabIndex=active?0:-1;
      });
      panels.forEach(function(panel){panel.hidden=panel.id!==tab.getAttribute('aria-controls')});
      if(focus){tab.focus()}
    }
    tabs.forEach(function(tab,index){
      tab.addEventListener('click',function(){select(tab,false)});
      tab.addEventListener('keydown',function(event){
        var next=index;
        if(event.key==='ArrowRight'){next=(index+1)%tabs.length}
        else if(event.key==='ArrowLeft'){next=(index-1+tabs.length)%tabs.length}
        else if(event.key==='Home'){next=0}
        else if(event.key==='End'){next=tabs.length-1}
        else{return}
        event.preventDefault();select(tabs[next],true);
      });
    });
  });
  document.querySelectorAll('[data-docara-embed-load]').forEach(function(button){
    button.addEventListener('click',function(){
      var embed=button.closest('[data-docara-block="embed"]');
      var template=embed&&embed.querySelector('template[data-docara-embed-template]');
      if(!embed||!template)return;
      var fragment=template.content.cloneNode(true);
      var iframe=fragment.querySelector('iframe[data-src]');
      if(!iframe)return;
      iframe.src=iframe.dataset.src;
      iframe.removeAttribute('data-src');
      embed.replaceChildren(fragment);
    });
  });
  document.querySelectorAll('[data-docara-tree-toggle]').forEach(function(button){
    var branch=button.nextElementSibling;
    var indicator=button.querySelector('[data-docara-tree-indicator]');
    if(!branch||branch.tagName!=='UL')return;
    function setExpanded(expanded){
      button.setAttribute('aria-expanded',expanded?'true':'false');
      branch.hidden=!expanded;
      if(indicator)indicator.textContent=expanded?'▾':'▸';
    }
    button.addEventListener('click',function(){setExpanded(button.getAttribute('aria-expanded')!=='true')});
    button.addEventListener('keydown',function(event){
      if(event.key==='ArrowLeft'&&button.getAttribute('aria-expanded')==='true'){
        event.preventDefault();setExpanded(false);
      }else if(event.key==='ArrowRight'&&button.getAttribute('aria-expanded')!=='true'){
        event.preventDefault();setExpanded(true);
      }
    });
  });
  document.querySelectorAll('[data-docara-math-source]').forEach(function(element){
    if(!window.katex||typeof window.katex.render!=='function')return;
    try{
      window.katex.render(element.textContent||'',element,{
        displayMode:element.dataset.display!=='inline',
        throwOnError:false,
        strict:'warn'
      });
    }catch(error){
      element.dataset.docaraRenderError='math';
    }
  });
  if(window.mermaid&&typeof window.mermaid.initialize==='function'){
    window.mermaid.initialize({startOnLoad:false,securityLevel:'strict',theme:'default'});
    var diagrams=Array.from(document.querySelectorAll('[data-docara-diagram-source]'));
    if(diagrams.length&&typeof window.mermaid.run==='function'){
      window.mermaid.run({nodes:diagrams}).catch(function(){
        diagrams.forEach(function(diagram){
          if(!diagram.querySelector('svg'))diagram.dataset.docaraRenderError='diagram';
        });
      });
    }
  }
  document.querySelectorAll('[data-docara-block="tree"]').forEach(function(tree){
    Array.from(tree.querySelectorAll('li')).forEach(function(item){
      var nested=Array.from(item.children).find(function(child){return child.tagName==='UL'})||null;
      var firstTextNode=Array.from(item.childNodes).find(function(node){
        return node.nodeType===Node.TEXT_NODE&&String(node.textContent||'').trim()!=='';
      })||null;
      if(!firstTextNode)return;
      var labelText=String(firstTextNode.textContent||'').trim();
      var label=document.createElement('span');
      label.className='inline-flex items-cross-center gap-1 min-w-0';
      var icon=document.createElement('sf-icon');
      var fileIcon=/\.(?:png|jpe?g|gif|webp|svg|avif)$/i.test(labelText)
        ?'image'
        :(/\.(?:json|ya?ml|xml|php|js|ts|css|scss|html?)$/i.test(labelText)?'data_object':'description');
      icon.setAttribute('icon',nested?'folder_open':fileIcon);
      icon.setAttribute('size','1');
      label.appendChild(icon);
      label.appendChild(document.createTextNode(labelText));
      firstTextNode.replaceWith(label);
      if(!nested||tree.dataset.interactive!=='true')return;
      var toggle=document.createElement('button');
      toggle.type='button';
      toggle.className='sf-button sf-button--link sf-button--on-surface sf-button--size-1 inline-flex items-cross-center content-main-center';
      toggle.setAttribute('aria-expanded','true');
      toggle.setAttribute('aria-label','Toggle folder');
      toggle.innerHTML='<sf-icon icon="expand_more" size="1"></sf-icon>';
      item.insertBefore(toggle,label);
      toggle.addEventListener('click',function(){
        var expanded=toggle.getAttribute('aria-expanded')==='true';
        toggle.setAttribute('aria-expanded',expanded?'false':'true');
        nested.hidden=expanded;
        var toggleIcon=toggle.querySelector('sf-icon');
        if(toggleIcon){toggleIcon.setAttribute('icon',expanded?'chevron_right':'expand_more')}
        icon.setAttribute('icon',expanded?'folder':'folder_open');
      });
    });
  });
  function positionExampleIndicator(example,animate){
    var selected=example.querySelector('[data-docara-example-tab][aria-selected="true"]');
    var indicator=example.querySelector('.docara-example-preview__indicator');
    var header=example.querySelector('.docara-example-preview__header');
    if(!selected||!indicator||!header)return;
    if(animate===false){indicator.style.transition='none'}
    var tabRect=selected.getBoundingClientRect();
    var headerRect=header.getBoundingClientRect();
    var inlineStart=document.documentElement.dir==='rtl'
      ?headerRect.right-tabRect.right
      :tabRect.left-headerRect.left;
    indicator.style.insetInlineStart=inlineStart+'px';
    indicator.style.inlineSize=tabRect.width+'px';
    if(animate===false){requestAnimationFrame(function(){indicator.style.removeProperty('transition')})}
  }
  var docaraExamples=Array.from(document.querySelectorAll('[data-docara-example]'));
  var docaraExampleFrames=Array.from(document.querySelectorAll('iframe[data-docara-example-frame]'));
  /* Example and HTML-block frames carry their document in data-docara-srcdoc: a browser loads a srcdoc frame with the page even when it is marked loading="lazy". The document moves to srcdoc when the frame comes within 600px of the screen; a frame in a hidden tab waits until the tab is shown. */
  (function(){
    var deferred=Array.from(document.querySelectorAll('iframe[data-docara-srcdoc]'));
    /* A project example's document names its folder with a path-only <base href="/…/_docara/examples/<id>/">. Chromium's preload scanner does not apply a path-only base in an about:srcdoc document and fetches every relative image against the page URL first (a 404, blocked by ORB in the sandboxed frame) before the parser applies the base. The page's origin makes the base absolute, which the scanner honours: one request per image. */
    function absoluteBase(source){
      var head=source.indexOf('</head>'),origin=window.location.origin;
      if(head===-1||!origin||origin==='null')return source;
      return source.slice(0,head).replace(/<base href="\/(?!\/)/,'<base href="'+origin+'/')+source.slice(head);
    }
    function load(frame){
      var source=frame.getAttribute('data-docara-srcdoc');
      if(source===null)return;
      frame.removeAttribute('data-docara-srcdoc');
      frame.setAttribute('srcdoc',absoluteBase(source));
    }
    if(!deferred.length)return;
    if(typeof IntersectionObserver!=='function'){deferred.forEach(load);return}
    var observer=new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(!entry.isIntersecting)return;
        observer.unobserve(entry.target);
        load(entry.target);
      });
    },{rootMargin:'600px 0px'});
    deferred.forEach(function(frame){observer.observe(frame)});
  })();
  /* An example without its own label is named with the localized "Example": the tab, its tab list, the frame's title attribute and, through the environment, the frame document's title. */
  (function(){
    var label=message('examples.example');
    if(label==='examples.example')return;
    document.querySelectorAll('[data-docara-example-default-label]').forEach(function(element){
      if(element.tagName==='IFRAME'){element.setAttribute('title',label);return}
      element.textContent=label;
      var list=element.closest('[role="tablist"]');
      if(list)list.setAttribute('aria-label',label);
    });
  })();
  /* An inline example may hold a demo form. Capturing on the example root cancels every submission that starts inside it before any other listener runs, so the form can never navigate or reload the documentation page. */
  Array.from(document.querySelectorAll('[data-docara-example-inline-preview]')).forEach(function(root){
    root.addEventListener('submit',function(event){event.preventDefault()},true);
  });
  var exampleViewportStorageKey='docara.example.viewport';
  var exampleViewportWidths={desktop:1280,tablet:768,mobile:390};
  function storedExampleViewport(){
    try{
      var stored=sessionStorage.getItem(exampleViewportStorageKey);
      if(Object.prototype.hasOwnProperty.call(exampleViewportWidths,stored)){return stored}
    }catch(error){}
    return'desktop';
  }
  var sharedExampleViewport=storedExampleViewport();
  var sourceWrapStorageKey='docara.source.wrap';
  function storedSourceWrap(){
    try{
      var stored=localStorage.getItem(sourceWrapStorageKey);
      if(stored==='true'||stored==='false')return stored==='true';
    }catch(error){}
    return Boolean(window.matchMedia&&window.matchMedia('(max-width: 40rem)').matches);
  }
  var sourceWrapActive=storedSourceWrap();
  function setSourceWrap(active,notify){
    sourceWrapActive=Boolean(active);
    try{localStorage.setItem(sourceWrapStorageKey,sourceWrapActive?'true':'false')}catch(error){}
    if(notify){document.dispatchEvent(new CustomEvent('docara:source-wrap-change',{detail:{active:sourceWrapActive}}))}
  }
  function syncSourceWrapIcon(button,active){
    var name=active?(button.dataset.unwrapIcon||'format_text_overflow'):(button.dataset.wrapIcon||'wrap_text');
    var customIcon=button.querySelector('sf-icon');
    if(customIcon){
      if(customIcon.getAttribute('icon')!==name){customIcon.setAttribute('icon',name)}
      return;
    }
    var icon=button.querySelector('.sf-icon');
    if(icon&&icon.textContent!==name){icon.textContent=name}
  }
  function syncCodeWrap(block,button,active){
    block.dataset.docaraCodeWrapActive=active?'true':'false';
    button.hidden=false;
    button.setAttribute('aria-pressed',active?'true':'false');
    button.setAttribute('aria-label',message(active?'code.unwrap':'code.wrap'));
    var text=button.querySelector('.sf-button-text-container');
    var label=message(active?'code.unwrap':'code.wrap');
    if(text&&text.textContent!==label){text.textContent=label}
    syncSourceWrapIcon(button,active);
  }
  var exampleFontAssets=Object.create(null),exampleStylesheets=Object.create(null);
  /* The page loads the same font files itself. Waiting for its own font loads and then reading through the HTTP cache lets the transfer reuse those responses instead of requesting each file again. */
  var exampleFontsSettled=document.fonts&&document.fonts.ready?document.fonts.ready.catch(function(){}):Promise.resolve();
  function exampleIconFace(rule){return /font-family\s*:\s*["']?Material (?:Symbols|Icons)/i.test(rule)}
  function exampleFontAsset(url,baseHref){
    var resolved;
    try{resolved=new URL(url,baseHref||document.baseURI)}catch(error){return Promise.resolve(null)}
    if(resolved.origin!==location.origin){return Promise.resolve(null)}
    if(!exampleFontAssets[resolved.href]){
      exampleFontAssets[resolved.href]=exampleFontsSettled.then(function(){return fetch(resolved.href,{credentials:'same-origin',cache:'force-cache'})}).then(function(response){
        if(!response.ok)throw new Error('Font request failed');
        return response.arrayBuffer().then(function(bytes){return{bytes:bytes,type:response.headers.get('content-type')||'font/woff2'}});
      }).catch(function(){return null});
    }
    return exampleFontAssets[resolved.href];
  }
  /* A face whose unicode-range covers none of the frame's characters is never used there, so it is dropped before its file is fetched or sent. */
  function exampleFaceNeeded(rule,codepoints){
    var range=/unicode-range\s*:\s*([^;}]+)/i.exec(rule);
    if(!range||!codepoints)return true;
    return range[1].split(',').some(function(part){
      var bounds=/^\s*U\+([0-9a-f?]{1,6})(?:-([0-9a-f]{1,6}))?\s*$/i.exec(part);
      if(!bounds)return true;
      var low=parseInt(bounds[1].replace(/\?/g,'0'),16),high=parseInt((bounds[2]||bounds[1]).replace(/\?/g,'f'),16);
      return codepoints.some(function(codepoint){return codepoint>=low&&codepoint<=high});
    });
  }
  function exampleCodepoints(text){
    var seen=Object.create(null),codepoints=[];
    for(var index=0;index<text.length;index++){
      var codepoint=text.codePointAt(index);
      if(codepoint>0xffff)index++;
      if(!seen[codepoint]){seen[codepoint]=true;codepoints.push(codepoint)}
    }
    return codepoints;
  }
  /* Font files reach the opaque-origin frame as bytes, so no host has to send CORS headers. A same-origin face whose file cannot be transferred is dropped, so the family stack falls back to its local faces; a cross-origin face keeps its URL for hosts that serve fonts with CORS. */
  /* faces='text' removes icon faces from a stylesheet and faces='icons' keeps only them, so the large icon fonts travel apart from the stylesheet that declares them. */
  function portableExampleStyle(content,baseHref,codepoints,faces){
    if(faces==='icons'){content=(content.match(/@font-face\s*\{[^}]*\}/gi)||[]).filter(exampleIconFace).join('\n')}
    content=content.replace(/@font-face\s*\{[^}]*\}/gi,function(rule){return (faces==='text'&&exampleIconFace(rule))||!exampleFaceNeeded(rule,codepoints)?'':rule});
    var matches=[],pattern=/url\(\s*(["']?)([^"')]+)\1\s*\)/g,match;
    while((match=pattern.exec(content))!==null){matches.push({token:match[0],url:match[2]})}
    return Promise.all(matches.map(function(item){return exampleFontAsset(item.url,baseHref)})).then(function(assets){
      var result=content,fonts=[],missing=[];
      matches.forEach(function(item,index){
        if(!assets[index]){
          try{if(new URL(item.url,baseHref||document.baseURI).origin===location.origin)missing.push(item.token)}catch(error){}
          return;
        }
        var token='__DOCARA_FONT_'+index+'__';
        result=result.split(item.token).join('url("'+token+'")');
        fonts.push({token:token,bytes:assets[index].bytes,type:assets[index].type});
      });
      if(missing.length){
        result=result.replace(/@font-face\s*\{[^}]*\}/gi,function(rule){
          return missing.some(function(token){return rule.indexOf(token)!==-1})?'':rule;
        });
      }
      return{content:result,fonts:fonts};
    });
  }
  function portableExampleStylesheet(link,codepoints,faces){
    if(!link||typeof link.href!=='string')return Promise.resolve(null);
    var resolved;
    try{resolved=new URL(link.href,document.baseURI)}catch(error){return Promise.resolve(null)}
    if(resolved.origin!==location.origin)return Promise.resolve(null);
    if(!exampleStylesheets[resolved.href]){
      exampleStylesheets[resolved.href]=fetch(resolved.href,{credentials:'same-origin',cache:'force-cache'}).then(function(response){
        if(!response.ok)throw new Error('Stylesheet request failed');
        return response.text();
      }).catch(function(){return null});
    }
    return exampleStylesheets[resolved.href].then(function(content){
      return content===null?null:portableExampleStyle(content,resolved.href,codepoints,faces);
    });
  }
  function collectExampleTokenNames(rules,names){
    Array.from(rules||[]).forEach(function(rule){
      if(rule.style){
        for(var index=0;index<rule.style.length;index++){
          var name=rule.style[index];
          if(typeof name==='string'&&name.indexOf('--sf-')===0&&/^--sf-[a-z0-9_\\/.-]+$/.test(name)){names.add(name)}
        }
      }
      try{if(rule.cssRules){collectExampleTokenNames(rule.cssRules,names)}}catch(error){}
    });
  }
  function exampleDesignTokens(){
    var computed=getComputedStyle(document.documentElement),names=new Set(['--sf-text--size','--sf-text--height']);
    for(var index=0;index<computed.length;index++){
      var name=computed[index];
      if(typeof name==='string'&&name.indexOf('--sf-')===0&&/^--sf-[a-z0-9_\\/.-]+$/.test(name)){names.add(name)}
    }
    Array.from(document.styleSheets).forEach(function(sheet){try{collectExampleTokenNames(sheet.cssRules,names)}catch(error){}});
    var tokens=Object.create(null);
    Array.from(names).sort().slice(0,4096).forEach(function(name){
      var value=computed.getPropertyValue(name).trim();
      if(!value||value.length>512||/url\s*\(|@import|expression\s*\(/i.test(value))return;
      tokens[name]=value;
    });
    return tokens;
  }
  /* Per frame: which environment entries it already holds (so font bytes are sent once, not with every theme or resize update) and which icon styles it shows. */
  var exampleFrameStates=new WeakMap();
  function exampleFrameState(frame){
    var state=exampleFrameStates.get(frame);
    if(!state){
      state={sent:Object.create(null),icons:Object.create(null),faces:Object.create(null),generation:0,queue:Promise.resolve()};
      (frame.getAttribute('data-docara-example-icons')||'').split(/\s+/).forEach(function(token){if(token)state.icons[token]=true});
      exampleFrameStates.set(frame,state);
    }
    return state;
  }
  function exampleEnvironment(frame){
    var state=exampleFrameState(frame),source=frame.getAttribute('srcdoc')||frame.getAttribute('data-docara-srcdoc')||'',codepoints=exampleCodepoints(source);
    var icons=Boolean(state.icons.outlined||state.icons.rounded||state.icons.shape||state.icons.full||state.icons['rounded-full']||state.icons['shape-full']||Object.keys(state.faces).length);
    function fresh(key){
      if(!key||state.sent[key])return false;
      state.sent[key]=true;
      return true;
    }
    var keyOf=function(element){return element.getAttribute('data-docara-framework-asset')||''};
    var inlineScripts=icons?Array.from(document.querySelectorAll('script[data-docara-framework-asset="simai.framework.icon_font.ready"]:not([src])')):[];
    var bootScripts=Array.from(document.querySelectorAll('script[data-docara-framework-asset="docara.framework.storage.compatibility"]:not([src]),script[data-docara-framework-asset="simai.framework.boot"]:not([src]),script[data-docara-framework-asset="simai.framework.preloaded"]:not([src])'));
    /* Like the page, a frame gets the icon subset and the catalogue's family rules with its first icon, then only the catalogue faces its icon runtime asks for, and a variable full font only for an icon whose axes no catalogue face draws. */
    var subsetStyle=document.querySelector('style[data-docara-framework-asset="simai.framework.icon_font.css"]');
    var inlineStyles=icons?Array.from(document.querySelectorAll(state.icons.full?'style[data-docara-framework-asset="simai.framework.icon_font.css"],style[data-docara-framework-asset="simai.framework.icon_fallback_font.css"]':'style[data-docara-framework-asset="simai.framework.icon_font.css"]')):[];
    if(state.icons['rounded-full']||state.icons['shape-full']){
      document.querySelectorAll('style[data-docara-framework-asset="simai.framework.icon_variant_fonts.css"]').forEach(function(style){inlineStyles.push(style)});
    }
    inlineStyles=inlineStyles.map(function(style){return{key:keyOf(style),content:style.textContent||''}});
    var catalogStyle=icons?document.querySelector('style[data-docara-framework-asset="simai.framework.icon_catalog.css"]'):null;
    if(catalogStyle){
      var catalog=catalogStyle.textContent||'',catalogFace=/\/\*docara-icon-face:([a-z0-9-]+)\*\/@font-face\s*\{[^}]*\}/g;
      inlineStyles.push({key:'simai.framework.icon_catalog.css',content:catalog.replace(catalogFace,'')});
      Object.keys(state.faces).forEach(function(id){
        var rules=[],match;
        catalogFace.lastIndex=0;
        while((match=catalogFace.exec(catalog))!==null){if(match[1]===id)rules.push(match[0])}
        if(rules.length)inlineStyles.push({key:'simai.framework.icon_catalog.css#'+id,content:rules.join('')});
      });
    }
    inlineStyles=inlineStyles.filter(function(item){return fresh(item.key)});
    inlineScripts=inlineScripts.filter(function(script){return fresh(keyOf(script))});
    bootScripts=bootScripts.filter(function(script){return fresh(keyOf(script))});
    var linked=[];
    Array.from(document.querySelectorAll('link[data-docara-framework-asset][rel="stylesheet"],link[data-docara-declarative-shell-style][rel="stylesheet"]')).forEach(function(link){
      if(fresh('stylesheet:'+link.href))linked.push({link:link,key:'stylesheet:'+link.href,faces:'text'});
      /* Where the subset styles every icon, the page never uses the icon faces its stylesheets declare, so neither does the frame. */
      if(icons&&!subsetStyle&&fresh('icon-faces:'+link.href))linked.push({link:link,key:'icon-faces:'+link.href,faces:'icons'});
    });
    var environment={
      stylesheets:[],
      bootScripts:bootScripts.map(function(script){return{key:keyOf(script),content:script.textContent||''}}),
      scripts:Array.from(document.querySelectorAll('script[data-docara-framework-asset][src]')).map(function(script){return script.src}),
      inlineScripts:[],
      theme:document.documentElement.classList.contains('theme-dark')?'dark':'light',
      direction:document.documentElement.dir==='rtl'?'rtl':'ltr',
      lang:document.documentElement.lang||'',
      title:frame.getAttribute('title')||'',
      rootFontSize:getComputedStyle(document.documentElement).fontSize,
      designTokens:exampleDesignTokens()
    };
    return Promise.all(inlineStyles.map(function(item){return portableExampleStyle(item.content,document.baseURI,codepoints).then(function(portable){
      return{key:item.key,content:portable.content,fonts:portable.fonts};
    })})).then(function(styles){return Promise.all(inlineScripts.map(function(script){return portableExampleStyle(script.textContent||'',document.baseURI).then(function(portable){
      return{key:keyOf(script),content:portable.content,fonts:portable.fonts};
    })})).then(function(scripts){environment.inlineScripts=scripts;return styles})}).then(function(styles){
      return Promise.all(linked.map(function(item){return portableExampleStylesheet(item.link,codepoints,item.faces).then(function(portable){
        if(!portable){if(item.faces==='text')environment.stylesheets.push(item.link.href);return null}
        if(portable.content.trim()==='')return null;
        return{key:item.key,content:portable.content,fonts:portable.fonts};
      })})).then(function(linkedStyles){environment.inlineStyles=styles.concat(linkedStyles.filter(Boolean));return environment});
    });
  }
  function requestExampleHeight(frame){
    if(!frame.contentWindow)return;
    var state=exampleFrameState(frame),generation=state.generation;
    /* Environments reach a frame in the order they were requested: boot state goes out once, in the first one, and must arrive before any later update loads Core. */
    state.queue=state.queue.then(function(){
      if(state.generation!==generation)return null;
      return exampleEnvironment(frame).then(function(environment){
        /* An environment prepared for an earlier frame document is dropped; the newer one carries the same entries. */
        if(state.generation!==generation)return;
        if(frame.contentWindow){frame.contentWindow.postMessage(Object.assign({type:'docara:example-measure'},environment),'*')}
      });
    }).catch(function(){});
  }
  /* A frame document announces itself when its runtime starts, after a first load or after the frame was moved and reloaded. It holds nothing yet, so everything is sent to it again; entries sent before it could listen were lost. */
  window.addEventListener('message',function(event){
    if(!event.data||event.data.type!=='docara:example-ready')return;
    var frame=docaraExampleFrames.find(function(candidate){return candidate.contentWindow===event.source})||null;
    if(!frame)return;
    var state=exampleFrameState(frame);
    state.sent=Object.create(null);
    state.generation++;
    requestExampleHeight(frame);
  });
  window.addEventListener('message',function(event){
    if(!event.data||event.data.type!=='docara:example-icons'||!Array.isArray(event.data.icons))return;
    var frame=docaraExampleFrames.find(function(candidate){return candidate.contentWindow===event.source})||null;
    if(!frame)return;
    var state=exampleFrameState(frame),changed=false;
    event.data.icons.forEach(function(token){
      if(['outlined','rounded','shape','full','rounded-full','shape-full'].indexOf(token)!==-1&&!state.icons[token]){state.icons[token]=true;changed=true}
      var face=typeof token==='string'?/^face:((?:outlined|rounded|sharp)-(?:300|400|500)-[01])$/.exec(token):null;
      if(face&&!state.faces[face[1]]){state.faces[face[1]]=true;changed=true}
      if(token==='full'&&!state.icons.outlined){state.icons.outlined=true;changed=true}
    });
    if(changed)requestExampleHeight(frame);
  });
  window.addEventListener('message',function(event){
    if(!event.data||event.data.type!=='docara:example-height')return;
    var frame=docaraExampleFrames.find(function(candidate){return candidate.contentWindow===event.source})||null;
    var height=Number(event.data.height);
    if(!frame||!Number.isFinite(height))return;
    frame.style.blockSize=Math.max(32,Math.ceil(height))+'px';
  });
  docaraExampleFrames.forEach(function(frame){
    frame.addEventListener('load',function(){requestExampleHeight(frame)});
    requestAnimationFrame(function(){requestExampleHeight(frame)});
  });
  if(docaraExampleFrames.length&&typeof MutationObserver==='function'){
    new MutationObserver(function(){docaraExampleFrames.forEach(requestExampleHeight)}).observe(document.documentElement,{attributes:true,attributeFilter:['class','dir']});
  }
  if(docaraExampleFrames.length){
    var exampleEnvironmentScheduled=false;
    addEventListener('resize',function(){
      if(exampleEnvironmentScheduled)return;
      exampleEnvironmentScheduled=true;
      requestAnimationFrame(function(){
        exampleEnvironmentScheduled=false;
        docaraExampleFrames.forEach(requestExampleHeight);
      });
    });
  }
  docaraExamples.forEach(function(example){
    function owned(selector){
      return Array.from(example.querySelectorAll(selector)).filter(function(candidate){
        return candidate.closest('[data-docara-example]')===example;
      });
    }
    var tabs=owned('[data-docara-example-tab]');
    var panels=owned('[data-docara-example-panel]');
    var copyButton=owned('[data-docara-example-copy]')[0]||null;
    var viewerButton=owned('[data-docara-example-viewer]')[0]||null;
    var viewportControls=owned('[data-docara-example-viewports]')[0]||null;
    var viewportButtons=owned('[data-docara-example-viewport]');
    var previewFrame=owned('iframe[data-docara-example-frame]')[0]||null;
    var wrapButton=owned('[data-docara-example-wrap]')[0]||null;
    if(!tabs.length||!panels.length)return;
    var viewerDialog=null,viewerPlaceholder=null;
    var activeViewport=sharedExampleViewport;
    function isViewerOpen(){return Boolean(viewerDialog&&viewerDialog.open&&example.parentElement===viewerDialog)}
    function setViewport(viewport,persist){
      if(!previewFrame||!Object.prototype.hasOwnProperty.call(exampleViewportWidths,viewport))return;
      activeViewport=viewport;
      sharedExampleViewport=viewport;
      example.dataset.docaraExampleViewport=viewport;
      previewFrame.style.inlineSize=exampleViewportWidths[viewport]+'px';
      previewFrame.style.maxInlineSize='none';
      viewportButtons.forEach(function(button){
        var active=button.dataset.docaraExampleViewport===viewport;
        var label=message('examples.viewport_'+button.dataset.docaraExampleViewport)+' · '+exampleViewportWidths[button.dataset.docaraExampleViewport]+' px';
        button.setAttribute('aria-pressed',active?'true':'false');
        button.setAttribute('aria-label',label);
        button.title=label;
        button.classList.toggle('active',active);
      });
      if(persist){
        try{sessionStorage.setItem(exampleViewportStorageKey,viewport)}catch(error){}
        document.dispatchEvent(new CustomEvent('docara:example-viewport-change',{detail:{viewport:viewport}}));
      }
      requestExampleHeight(previewFrame);
    }
    function resetViewport(){
      if(!previewFrame)return;
      delete example.dataset.docaraExampleViewport;
      previewFrame.style.removeProperty('inline-size');
      previewFrame.style.removeProperty('max-inline-size');
      requestExampleHeight(previewFrame);
    }
    function syncViewer(){
      if(!viewerButton)return;
      var active=isViewerOpen();
      var selected=example.dataset.sourceActive!=='true';
      viewerButton.hidden=!active&&!selected;
      viewerButton.setAttribute('aria-pressed',active?'true':'false');
      viewerButton.setAttribute('aria-label',message(active?'examples.viewer_exit':'examples.viewer'));
      if(viewportControls){
        viewportControls.hidden=!active||!selected;
        viewportControls.setAttribute('aria-label',message('examples.viewport_group'));
      }
      if(active){setViewport(activeViewport,false)}else{resetViewport()}
      var icon=viewerButton.querySelector('sf-icon');
      if(icon){icon.setAttribute('icon',active
        ?(viewerButton.dataset.viewerExitIcon||'close')
        :(viewerButton.dataset.viewerIcon||'devices'))}
    }
    function restoreViewer(){
      delete example.dataset.docaraExampleViewerActive;
      if(viewerPlaceholder&&viewerPlaceholder.parentNode){viewerPlaceholder.parentNode.insertBefore(example,viewerPlaceholder);viewerPlaceholder.remove()}
      viewerPlaceholder=null;
      if(viewerDialog&&viewerDialog.parentNode){viewerDialog.remove()}
      syncViewer();
      if(viewerButton){viewerButton.focus()}
    }
    function openViewer(){
      if(!viewerDialog||isViewerOpen())return;
      viewerPlaceholder=document.createComment('docara-example-viewer');
      example.parentNode.insertBefore(viewerPlaceholder,example);
      document.body.appendChild(viewerDialog);
      viewerDialog.appendChild(example);
      example.dataset.docaraExampleViewerActive='true';
      viewerDialog.setAttribute('aria-label',message('examples.viewer_dialog'));
      try{viewerDialog.showModal()}catch(error){restoreViewer();return}
      syncViewer();
      var selectedViewport=viewportButtons.find(function(button){return button.dataset.docaraExampleViewport===activeViewport});
      if(selectedViewport){selectedViewport.focus()}
    }
    function syncWrap(active){
      if(!wrapButton)return;
      example.dataset.docaraExampleWrapActive=active?'true':'false';
      wrapButton.hidden=example.dataset.sourceActive!=='true';
      wrapButton.setAttribute('aria-pressed',active?'true':'false');
      wrapButton.setAttribute('aria-label',message(active?'code.unwrap':'code.wrap'));
      syncSourceWrapIcon(wrapButton,active);
    }
    function selectTab(tab,moveFocus){
      var key=tab.dataset.docaraExampleTab;
      tabs.forEach(function(candidate){
        var selected=candidate===tab;
        candidate.setAttribute('aria-selected',selected?'true':'false');
        candidate.tabIndex=selected?0:-1;
      });
      panels.forEach(function(panel){
        var selected=panel.dataset.docaraExamplePanel===key;
        panel.classList.toggle('is-active',selected);
        panel.setAttribute('aria-hidden',selected?'false':'true');
      });
      example.dataset.sourceActive=key==='example'?'false':'true';
      if(copyButton){copyButton.hidden=key==='example'}
      syncViewer();
      syncWrap(sourceWrapActive);
      positionExampleIndicator(example,true);
      if(moveFocus){tab.focus()}
    }
    tabs.forEach(function(tab,index){
      tab.addEventListener('click',function(){selectTab(tab,false)});
      tab.addEventListener('keydown',function(event){
        var next=index;
        if(event.key==='ArrowRight'){next=(index+1)%tabs.length}
        else if(event.key==='ArrowLeft'){next=(index-1+tabs.length)%tabs.length}
        else if(event.key==='Home'){next=0}
        else if(event.key==='End'){next=tabs.length-1}
        else{return}
        event.preventDefault();selectTab(tabs[next],true);
      });
    });
    viewportButtons.forEach(function(button){
      button.addEventListener('click',function(){setViewport(button.dataset.docaraExampleViewport,true)});
    });
    document.addEventListener('docara:example-viewport-change',function(event){
      var viewport=event.detail&&event.detail.viewport;
      if(!Object.prototype.hasOwnProperty.call(exampleViewportWidths,viewport))return;
      activeViewport=viewport;
      if(isViewerOpen()){setViewport(viewport,false)}
    });
    if(viewerButton){
      viewerDialog=document.createElement('dialog');
      viewerDialog.className='docara-example-viewer-dialog';
      if(typeof viewerDialog.showModal!=='function'){
        if(viewportControls){viewportControls.remove();viewportControls=null}
        viewerButton.remove();
        viewerButton=null;
      }else{
        viewerButton.addEventListener('click',function(){
          if(isViewerOpen()){viewerDialog.close()}else{openViewer()}
        });
        viewerDialog.addEventListener('close',restoreViewer);
      }
    }
    if(wrapButton){
      wrapButton.addEventListener('click',function(){setSourceWrap(!sourceWrapActive,true)});
      document.addEventListener('docara:source-wrap-change',function(event){
        syncWrap(Boolean(event.detail&&event.detail.active));
      });
    }
    if(copyButton){
      copyButton.dataset.copyLabel=message('code.copy');
      copyButton.dataset.copiedLabel=message('code.copied');
      var copyResetTimer=0;
      function sourceText(code){
        var lines=Array.from(code.querySelectorAll('.hljs-ln-code'));
        if(lines.length){return lines.map(function(line){return line.textContent||''}).join('\n')}
        return code.textContent||'';
      }
      function writeClipboard(value){
        if(navigator.clipboard&&typeof navigator.clipboard.writeText==='function'){
          return navigator.clipboard.writeText(value);
        }
        return new Promise(function(resolve,reject){
          var input=document.createElement('textarea');
          input.value=value;
          input.setAttribute('readonly','');
          input.style.position='fixed';
          input.style.inset='0 auto auto -9999px';
          document.body.appendChild(input);
          input.select();
          try{
            if(!document.execCommand('copy')){throw new Error('Clipboard copy was rejected')}
            resolve();
          }catch(error){reject(error)}finally{input.remove()}
        });
      }
      function showCopyState(copied){
        var icon=copyButton.querySelector('sf-icon');
        copyButton.dataset.copyState=copied?'copied':'idle';
        copyButton.setAttribute('aria-label',copied
          ?(copyButton.dataset.copiedLabel||'Copied')
          :(copyButton.dataset.copyLabel||'Copy'));
        if(icon){icon.setAttribute('icon',copied
          ?(copyButton.dataset.copiedIcon||'check')
          :(copyButton.dataset.copyIcon||'content_copy'))}
      }
      showCopyState(false);
      copyButton.addEventListener('click',function(){
        var panel=panels.find(function(candidate){return candidate.classList.contains('is-active')})||null;
        var code=panel&&panel.querySelector('code');
        if(!code)return;
        writeClipboard(sourceText(code)).then(function(){
          window.clearTimeout(copyResetTimer);
          showCopyState(true);
          copyResetTimer=window.setTimeout(function(){showCopyState(false)},1600);
        }).catch(function(){showCopyState(false)});
      });
    }
    syncViewer();
    syncWrap(sourceWrapActive);
    positionExampleIndicator(example,false);
  });
  if(document.fonts&&document.fonts.ready){
    document.fonts.ready.then(function(){docaraExamples.forEach(function(example){positionExampleIndicator(example,false)})});
  }
  window.addEventListener('resize',function(){
    docaraExamples.forEach(function(example){positionExampleIndicator(example,false)});
  },{passive:true});
  // Reading layout: hide the outline rail and the reading (focus) mode. The
  // preferences boot script restores both as root attributes before first
  // paint; this binds the toolbar buttons, keeps aria-pressed in sync and
  // stores the state per site so it follows the reader across pages.
  (function(){
    var outlineButton=document.querySelector('[data-docara-outline-toggle]');
    var focusButton=document.querySelector('[data-docara-focus-toggle]');
    var exitButton=document.querySelector('[data-docara-focus-exit]');
    // The 404 page has no reading mode toggle but keeps the exit button.
    if(!outlineButton&&!focusButton&&!exitButton)return;
    var root=document.documentElement;
    var store=window.DocaraReadingLayout||null;
    function state(){return{outline:root.hasAttribute('data-docara-outline-hidden')?'hidden':'shown',focus:root.hasAttribute('data-docara-focus-mode')}}
    function save(next){
      if(store){store.write(next);return}
      root.toggleAttribute('data-docara-outline-hidden',next.outline==='hidden');
      root.toggleAttribute('data-docara-focus-mode',next.focus===true);
    }
    function sync(){
      var current=state();
      if(outlineButton)outlineButton.setAttribute('aria-pressed',current.outline==='hidden'?'true':'false');
      if(focusButton)focusButton.setAttribute('aria-pressed',current.focus?'true':'false');
    }
    if(outlineButton){
      outlineButton.addEventListener('click',function(){
        var next=state();
        next.outline=next.outline==='hidden'?'shown':'hidden';
        save(next);
        sync();
      });
    }
    // The toolbar toggle hides in reading mode and the exit button shows, so
    // focus moves to whichever of the two is visible.
    function setFocus(active){
      var next=state();
      next.focus=active;
      save(next);
      sync();
      var target=active?exitButton:focusButton;
      if(target)target.focus({preventScroll:true});
    }
    if(focusButton){
      focusButton.addEventListener('click',function(){setFocus(true)});
    }
    if(exitButton){
      exitButton.addEventListener('click',function(){setFocus(false)});
    }
    document.addEventListener('keydown',function(event){
      if(event.key!=='Escape'||event.defaultPrevented||!state().focus)return;
      var target=event.target;
      // Dialogs, menus and modals close themselves first.
      if(target&&target.closest&&target.closest('dialog,sf-modal,[role="dialog"],details[open]'))return;
      setFocus(false);
    });
    window.addEventListener('storage',function(event){
      if(!store||event.key!==store.key)return;
      store.apply(store.read());
      sync();
    });
    sync();
  })();
  /* A hero's background effect keeps moving, so the reader can pause it (WCAG 2.2.2). The Framework does not hand out the handle of an effect it mounts from data-sf-effect, so Docara places the hero's effect itself with SF.Effects.placeEffect and the same attributes: this script runs before the deferred core.js, so its load listener runs before the Framework's declarative scan, which then skips the placed hero. The handle's pause() and resume() drive the toggle. The choice is kept for the site. With reduced motion the Framework draws one still frame and the toggle is hidden. */
  (function(){
    var heroes=document.querySelectorAll('[data-docara-block="hero"][data-sf-effect]');
    if(!heroes.length)return;
    var storageKey='docara.effects.paused';
    var paused=false;
    try{paused=localStorage.getItem(storageKey)==='true'}catch(error){}
    var reducedMotion=window.matchMedia?window.matchMedia('(prefers-reduced-motion: reduce)'):null;
    var entries=[];
    function effects(){
      var api=window.SF&&window.SF.Effects;
      return api&&typeof api.placeEffect==='function'?api:null;
    }
    function options(hero){
      var settings={};
      try{
        var parsed=JSON.parse(hero.getAttribute('data-sf-effect-settings')||'{}');
        if(parsed&&typeof parsed==='object'&&!Array.isArray(parsed))settings=parsed;
      }catch(error){}
      return {effect:hero.getAttribute('data-sf-effect'),settings:settings,themeMode:hero.getAttribute('data-sf-effect-theme')||'auto'};
    }
    function sync(entry){
      var running=entry.hero.getAttribute('data-sf-effect-state')==='running';
      var reduced=Boolean(reducedMotion&&reducedMotion.matches);
      entry.button.hidden=!running||reduced||!(entry.handle||effects());
      var label=message(paused?'effects.play':'effects.pause');
      if(entry.button.getAttribute('aria-label')!==label){entry.button.setAttribute('aria-label',label)}
      if(entry.button.title!==label){entry.button.title=label}
      var name=paused?'play_arrow':'pause';
      if(entry.icon.textContent!==name){entry.icon.textContent=name}
      if(entry.handle){
        if(paused){entry.handle.pause()}else{entry.handle.resume()}
      }
    }
    function place(entry){
      var api=effects();
      if(!api)return;
      if(!entry.placing){
        entry.placing=Promise.resolve(api.placeEffect(entry.hero,options(entry.hero))).then(function(handle){
          entry.handle=handle||null;
          sync(entry);
        },function(){entry.placing=null});
      }
    }
    Array.prototype.forEach.call(heroes,function(hero){
      var button=document.createElement('button');
      button.type='button';
      button.hidden=true;
      button.dataset.docaraEffectToggle='';
      button.className='docara-effect-toggle sf-icon-button sf-icon-button--icon sf-icon-button--surface sf-icon-button--on-surface sf-icon-button--size-1 inline-grid items-cross-center content-main-center m-0';
      button.innerHTML='<span aria-hidden="true" class="sf-icon sf-icon-regular">pause</span>';
      hero.appendChild(button);
      var entry={hero:hero,button:button,icon:button.firstChild,handle:null,placing:null};
      entries.push(entry);
      button.addEventListener('click',function(){
        paused=!paused;
        try{localStorage.setItem(storageKey,paused?'true':'false')}catch(error){}
        entries.forEach(function(item){
          // Mounted by the Framework before Docara could place it: placed again to get its handle.
          if(!item.handle)place(item);
          sync(item);
        });
      });
      new MutationObserver(function(){sync(entry)}).observe(hero,{attributes:true,attributeFilter:['data-sf-effect-state']});
      sync(entry);
    });
    function start(){
      entries.forEach(function(entry){
        if(!entry.hero.hasAttribute('data-sf-effect-state')||paused)place(entry);
        sync(entry);
      });
    }
    if(document.readyState==='complete'){start()}else{window.addEventListener('load',start,{once:true})}
    if(reducedMotion){
      var onMotion=function(){entries.forEach(sync)};
      if(reducedMotion.addEventListener){reducedMotion.addEventListener('change',onMotion)}else if(reducedMotion.addListener){reducedMotion.addListener(onMotion)}
    }
    window.addEventListener('storage',function(event){
      if(event.key!==storageKey)return;
      paused=event.newValue==='true';
      entries.forEach(function(entry){
        if(!entry.handle&&paused)place(entry);
        sync(entry);
      });
    });
  })();
  localizeCodeCopy(document);
  if(document.body){
    new MutationObserver(function(records){
      records.forEach(function(record){
        if(record.type==='characterData'){localizeCodeCopy(record.target.parentElement);return}
        record.addedNodes.forEach(localizeCodeCopy);
      });
    }).observe(document.body,{childList:true,subtree:true,characterData:true});
  }
})();
