(function(){
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
      }
      var text=button.querySelector('.sf-button-text-container'),icon=button.querySelector('.sf-icon');
      var copied=(text&&/copied|скопировано/i.test(text.textContent||''))||(icon&&icon.textContent.trim()==='check');
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
      var unavailable=(dialog.id==='docara-mobile-navigation'&&window.matchMedia('(min-width: 801px)').matches)
        ||(dialog.id==='docara-outline-dialog'&&window.matchMedia('(min-width: 1153px)').matches);
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
      toggle.className='sf-button sf-button--text sf-button--on-surface sf-button--size-1 inline-flex items-cross-center content-main-center';
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
  var exampleFontAssets=Object.create(null);
  function exampleFontAsset(url){
    var resolved;
    try{resolved=new URL(url,document.baseURI)}catch(error){return Promise.resolve(null)}
    if(resolved.origin!==location.origin){return Promise.resolve(null)}
    if(!exampleFontAssets[resolved.href]){
      exampleFontAssets[resolved.href]=fetch(resolved.href,{credentials:'same-origin'}).then(function(response){
        if(!response.ok)throw new Error('Font request failed');
        return response.arrayBuffer().then(function(bytes){return{bytes:bytes,type:response.headers.get('content-type')||'font/woff2'}});
      }).catch(function(){return null});
    }
    return exampleFontAssets[resolved.href];
  }
  function portableExampleStyle(content){
    var matches=[],pattern=/url\(\s*(["']?)([^"')]+)\1\s*\)/g,match;
    while((match=pattern.exec(content))!==null){matches.push({token:match[0],url:match[2]})}
    return Promise.all(matches.map(function(item){return exampleFontAsset(item.url)})).then(function(assets){
      var result=content,fonts=[];
      matches.forEach(function(item,index){
        if(!assets[index])return;
        var token='__DOCARA_FONT_'+index+'__';
        result=result.split(item.token).join('url("'+token+'")');
        fonts.push({token:token,bytes:assets[index].bytes,type:assets[index].type});
      });
      return{content:result,fonts:fonts};
    });
  }
  function exampleEnvironment(frame){
    var source=frame.getAttribute('srcdoc')||'',needsIcons=source.indexOf('sf-icon')!==-1;
    var inlineStyles=needsIcons?Array.from(document.querySelectorAll('style[data-docara-framework-asset="simai.framework.icon_font.css"]')):[];
    if(source.indexOf('sf-icon-rounded')!==-1||source.indexOf('sf-icon-shape')!==-1){
      document.querySelectorAll('style[data-docara-framework-asset="simai.framework.icon_variant_fonts.css"]').forEach(function(style){inlineStyles.push(style)});
    }
    var environment={
      stylesheets:Array.from(document.querySelectorAll('link[data-docara-framework-asset][rel="stylesheet"],link[data-docara-declarative-shell-style][rel="stylesheet"]')).map(function(link){return link.href}),
      scripts:Array.from(document.querySelectorAll('script[data-docara-framework-asset^="simai.framework.preloaded.component."][src]')).map(function(script){return script.src}),
      inlineScripts:Array.from(document.querySelectorAll('script[data-docara-framework-asset="simai.framework.icon_font.ready"]:not([src])')).map(function(script){return{key:script.getAttribute('data-docara-framework-asset'),content:script.textContent||''}}),
      theme:document.documentElement.classList.contains('theme-dark')?'dark':'light',
      direction:document.documentElement.dir==='rtl'?'rtl':'ltr'
    };
    return Promise.all(inlineStyles.map(function(style){return portableExampleStyle(style.textContent||'').then(function(portable){
      return{key:style.getAttribute('data-docara-framework-asset')||'',content:portable.content,fonts:portable.fonts};
    })})).then(function(styles){environment.inlineStyles=styles;return environment});
  }
  function requestExampleHeight(frame){
    if(!frame.contentWindow)return;
    exampleEnvironment(frame).then(function(environment){
      if(frame.contentWindow){frame.contentWindow.postMessage(Object.assign({type:'docara:example-measure'},environment),'*')}
    });
  }
  window.addEventListener('message',function(event){
    if(!event.data||event.data.type!=='docara:example-height')return;
    var frame=docaraExampleFrames.find(function(candidate){return candidate.contentWindow===event.source})||null;
    var height=Number(event.data.height);
    if(!frame||!Number.isFinite(height))return;
    frame.style.blockSize=Math.max(32,Math.min(4096,Math.ceil(height)))+'px';
  });
  docaraExampleFrames.forEach(function(frame){
    frame.addEventListener('load',function(){requestExampleHeight(frame)});
    requestAnimationFrame(function(){requestExampleHeight(frame)});
  });
  if(docaraExampleFrames.length&&typeof MutationObserver==='function'){
    new MutationObserver(function(){docaraExampleFrames.forEach(requestExampleHeight)}).observe(document.documentElement,{attributes:true,attributeFilter:['class','dir']});
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
    if(!tabs.length||!panels.length)return;
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
    positionExampleIndicator(example,false);
  });
  if(document.fonts&&document.fonts.ready){
    document.fonts.ready.then(function(){docaraExamples.forEach(function(example){positionExampleIndicator(example,false)})});
  }
  window.addEventListener('resize',function(){
    docaraExamples.forEach(function(example){positionExampleIndicator(example,false)});
  },{passive:true});
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
