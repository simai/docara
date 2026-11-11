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
    var sheet=document.querySelector('body[data-docara-shell]>.docara-header [data-docara-sheet-content]');
    if(credit&&sheet&&!sheet.querySelector(':scope>[data-docara-sidebar-credit]')){sheet.appendChild(credit.cloneNode(true))}
  })();
  /* The sticky sidebar and outline start under the header and fill the rest of the window. The header's height depends on the brand size and what the header holds, so the shell measures it into --docara-header-block-size; until then the stylesheet's estimate stands. The header element has no box of its own (display: contents) and draws its bar anew in place of the printed one, so the bar is looked up again whenever its children change. */
  (function(){
    var host=document.querySelector('body[data-docara-shell]>.docara-header');
    if(!host)return;
    var observed=null,resize=typeof ResizeObserver==='function'?new ResizeObserver(measure):null;
    function measure(){
      var bar=host.querySelector(':scope>.sf-site-header');
      if(resize&&bar!==observed){if(observed)resize.unobserve(observed);if(bar)resize.observe(bar);observed=bar}
      var size=bar?bar.getBoundingClientRect().height:0;
      if(size>0)document.documentElement.style.setProperty('--docara-header-block-size',size+'px');
    }
    measure();
    if(typeof MutationObserver==='function'){new MutationObserver(measure).observe(host,{childList:true})}
    if(!resize){window.addEventListener('resize',measure)}
  })();
  var copyNode=document.getElementById('docara-runtime-copy'),messages={};
  try{messages=JSON.parse(copyNode?copyNode.textContent:'{}')}catch(error){messages={}}
  function message(id,parameters){
    var value=typeof messages[id]==='string'?messages[id]:id;
    Object.keys(parameters||{}).forEach(function(name){value=value.split('{'+name+'}').join(String(parameters[name]))});
    return value;
  }
  /* A block whose lines the build wrapped copies its code without line numbers, diff signs, prompts, removed lines or terminal output: the build marks every line the copy leaves out with data-copy-skip. */
  function codeCopyText(code){
    if(!code.hasAttribute('data-docara-code-lines'))return code.textContent||'';
    return Array.prototype.filter.call(code.children,function(line){return !line.hasAttribute('data-copy-skip')}).map(function(line){
      var content=line.querySelector(':scope>.docara-code-line__content');
      return content?content.textContent:'';
    }).join('\n');
  }
  /* The Framework highlighter reads a block's text and replaces its HTML, which would drop the lines the build wrapped, so those blocks arrive marked data-highlighted="lines". They are coloured here with the Framework's own engine: the whole source at once, so a comment or string that spans lines keeps its colour, then split back into the build's lines. */
  function splitHighlightedLines(html){
    var lines=[],open=[],current='',pattern=/(<span\b[^>]*>)|(<\/span>)|([^<]+)|(<)/g,match;
    while((match=pattern.exec(html))!==null){
      if(match[1]){open.push(match[1]);current+=match[1]}
      else if(match[2]){open.pop();current+=match[2]}
      else{
        var parts=(match[3]||match[4]).split('\n');
        for(var index=0;index<parts.length;index++){
          if(index>0){current+=open.map(function(){return '</span>'}).join('');lines.push(current);current=open.join('')}
          current+=parts[index];
        }
      }
    }
    lines.push(current);
    return lines;
  }
  function highlightCodeLines(root){
    var engine=window.hljs;
    if(!engine||typeof engine.highlight!=='function')return;
    var scope=root&&root.querySelectorAll?root:document;
    scope.querySelectorAll('code[data-docara-code-lines]:not([data-docara-code-coloured])').forEach(function(code){
      var contents=Array.prototype.map.call(code.children,function(line){return line.querySelector(':scope>.docara-code-line__content')});
      if(contents.indexOf(null)!==-1)return;
      var name=Array.prototype.filter.call(code.classList,function(item){return /^(?:language|lang)-/.test(item)})[0];
      var language=name?name.replace(/^(?:language|lang)-/,'').toLowerCase():'plaintext';
      if(typeof engine.getLanguage==='function'&&!engine.getLanguage(language))language='plaintext';
      var source=contents.map(function(content){return content.textContent}).join('\n');
      var coloured=splitHighlightedLines(engine.highlight(source,{language:language}).value);
      code.setAttribute('data-docara-code-coloured','');
      if(coloured.length!==contents.length)return;
      /* Each line's colours go in one element: the Framework highlighter redraws a block whose text node changes, which would join the lines, but it leaves an added element alone. */
      contents.forEach(function(content,index){if(!content.parentElement.hasAttribute('data-line-output'))content.innerHTML='<span>'+coloured[index]+'</span>'});
    });
  }
  highlightCodeLines(document);
  /* The Framework loads its highlighter after the page, when it finds code; a script's load event reaches the document in the capture phase. */
  document.addEventListener('load',function(event){
    if(event.target&&event.target.tagName==='SCRIPT'&&window.hljs)highlightCodeLines(document);
  },true);
  window.addEventListener('load',function(){highlightCodeLines(document)});
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
        // The highlighter may record the language it was asked for; a fence
        // rendered by Docara names it only in its language-* class.
        var classLanguage=code&&/(?:^|\s)language-([\w+-]+)/.exec(code.className||'');
        var language=(code&&code.dataset.requestedLang||classLanguage&&classLanguage[1]||'').trim().toLowerCase();
        var head=block.querySelector(':scope > .sf--highlight-head,:scope > [data-docara-code-fallback]');
        var title=head&&head.querySelector(':scope > span');
        var languageLabels={html:'HTML',xhtml:'HTML',css:'CSS',js:'JavaScript',javascript:'JavaScript',json:'JSON',php:'PHP',bash:'Bash',shell:'Shell',markdown:'Markdown',md:'Markdown',text:'Code',plaintext:'Code'};
        var languageLabel=block.dataset.docaraCodeTitle||languageLabels[language]||language||(title&&title.textContent.trim())||'Code';
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
          var value=codeCopyText(code),done=function(){
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
  /* The sidebar and the header's navigation panel keep their scroll across pages of the site. The position is saved for the tab on pagehide and on a link click, before the panel closes and loses it; the inline script after the sidebar restores the sidebar before the first paint, and the panel is restored when it opens. A current item still out of view is centred in its own scroller: only that box scrolls, at once, never the page, and nothing animates. */
  var navigationScroll=(function(){
    var key='docara.navigation.scroll';
    var sidebar=document.querySelector('aside[data-docara-region="sidebar"]>.docara-sidebar-panel>.docara-sidebar-scroll>.sf-scrollbar__viewport');
    var drawer=document.querySelector('body[data-docara-shell]>.docara-header');
    // The panel is the element's own; it is drawn with the element, so it is looked up when needed.
    function panel(){return drawer?drawer.querySelector('.sf-site-header-panel'):null}
    var stored=null,sameSite=false;
    try{sameSite=Boolean(document.referrer)&&new URL(document.referrer).origin===location.origin}catch(error){}
    try{stored=JSON.parse(sessionStorage.getItem(key)||'null')}catch(error){}
    if(!stored||typeof stored!=='object')stored={};
    var drawerTop=sameSite&&typeof stored.drawer==='number'?stored.drawer:0;
    function shown(element){return Boolean(element&&element.getClientRects().length)}
    function keepDrawer(){var content=panel();if(drawer&&drawer.isOpen&&shown(content))drawerTop=content.scrollTop}
    function save(){
      keepDrawer();
      var state={sidebar:shown(sidebar)?sidebar.scrollTop:(typeof stored.sidebar==='number'?stored.sidebar:0),drawer:drawerTop};
      try{sessionStorage.setItem(key,JSON.stringify(state))}catch(error){}
    }
    // The Framework sets scroll-behavior:smooth; the position is put at once instead.
    function scrollAt(scroller,top){
      var behavior=scroller.style.scrollBehavior;
      scroller.style.scrollBehavior='auto';
      scroller.scrollTop=top;
      scroller.style.scrollBehavior=behavior;
    }
    function reveal(scroller,item){
      if(!scroller||!item)return;
      var box=scroller.getBoundingClientRect(),rect=item.getBoundingClientRect();
      if(box.height<=0||rect.height<=0||(rect.top>=box.top&&rect.bottom<=box.bottom))return;
      scrollAt(scroller,scroller.scrollTop+rect.top-box.top-(box.height-rect.height)/2);
    }
    document.addEventListener('click',function(event){
      var link=event.target&&event.target.closest?event.target.closest('a[href]'):null;
      if(link&&((sidebar&&sidebar.contains(link))||(drawer&&drawer.contains(link))))save();
    },true);
    window.addEventListener('pagehide',save);
    return {
      keepDrawer:keepDrawer,
      openDrawer:function(){
        var content=panel();
        if(!content)return;
        scrollAt(content,drawerTop);
        // The panel lists the header links first: the current item of the sidebar copy is the last one.
        var current=content.querySelectorAll('[aria-current="page"]');
        reveal(content,current[current.length-1]);
      }
    };
  })();
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
  /* The header's navigation panel is the Framework sf-header-site's own: it opens, closes, traps focus and locks the page. The shell fills its sheet with the sidebar tree as the panel opens, and keeps the panel's scroll. */
  (function(){
    var header=document.querySelector('body[data-docara-shell]>.docara-header');
    var sheet=header&&header.querySelector('[data-docara-sheet-content]');
    if(!sheet)return;
    header.addEventListener('sf-header-site-open',function(){
      var template=sheet.querySelector('template[data-docara-sheet-template]');
      var target=sheet.querySelector('[data-docara-sheet-lazy-content]');
      if(template&&target){target.replaceWith(template.content.cloneNode(true));template.remove()}
    });
    header.addEventListener('sf-header-site-opened',function(){navigationScroll.openDrawer()});
    header.addEventListener('sf-header-site-close',function(){navigationScroll.keepDrawer()});
  })();
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
  function positionExampleIndicator(example,animate){
    positionTabIndicator(
      example.querySelector('[data-docara-example-tab][aria-selected="true"]'),
      example.querySelector('.docara-example-preview__indicator'),
      example.querySelector('.docara-example-preview__header'),
      animate
    );
  }
  function positionTabIndicator(selected,indicator,header,animate){
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
  /* A frame document starts in the page's language and direction: scripts inside it read <html lang> as they start (file sizes, dates, default labels), before the frame runtime hears from the page. */
  function docaraFrameSource(source){
    var lang=document.documentElement.lang||'',direction=document.documentElement.dir==='rtl'?'rtl':'';
    if(!/^<!doctype html><html[\s>]/i.test(source)||/^<!doctype html><html[^>]*\slang=/i.test(source))return source;
    var attributes=(/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/.test(lang)?' lang="'+lang+'"':'')+(direction?' dir="rtl"':'');
    return attributes?source.replace(/^(<!doctype html><html)/i,'$1'+attributes):source;
  }
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
      frame.setAttribute('srcdoc',absoluteBase(docaraFrameSource(source)));
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
    // A long line scrolls inside its block on every screen, as the docs
    // promise: wrapping on phones split tags and aligned comments.
    return false;
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
  /* The frame gets the page's design tokens, except those that follow the viewport width: the Framework switches its size ladder (text, control heights, spacing, containers) with width media queries, and a frame narrower than the page must take the ladder of its own width. A token declared under a width query, or declared from such a token, is left to the frame's own stylesheets; a token the page sets on its root element itself (a reader setting such as the UI radius) is sent as written, so the frame resolves it against its own ladder. */
  function collectExampleTokenNames(rules,names,declared,widthScoped,inWidth){
    Array.from(rules||[]).forEach(function(rule){
      var scoped=inWidth||Boolean(rule.media&&/width/i.test(rule.media.mediaText||''));
      if(rule.style){
        for(var index=0;index<rule.style.length;index++){
          var name=rule.style[index];
          if(typeof name==='string'&&name.indexOf('--sf-')===0&&/^--sf-[a-z0-9_\\/.-]+$/.test(name)){
            names.add(name);
            if(scoped)widthScoped.add(name);
            if(!declared[name])declared[name]=[];
            declared[name].push(rule.style.getPropertyValue(name));
          }
        }
      }
      try{if(rule.cssRules){collectExampleTokenNames(rule.cssRules,names,declared,widthScoped,scoped)}}catch(error){}
    });
  }
  function exampleDesignTokens(){
    var root=document.documentElement,computed=getComputedStyle(root),names=new Set(['--sf-text--size','--sf-text--height']),declared=Object.create(null),widthScoped=new Set();
    for(var index=0;index<computed.length;index++){
      var name=computed[index];
      if(typeof name==='string'&&name.indexOf('--sf-')===0&&/^--sf-[a-z0-9_\\/.-]+$/.test(name)){names.add(name)}
    }
    Array.from(document.styleSheets).forEach(function(sheet){try{collectExampleTokenNames(sheet.cssRules,names,declared,widthScoped,Boolean(sheet.media&&/width/i.test(sheet.media.mediaText||'')))}catch(error){}});
    var changed=true;
    while(changed){
      changed=false;
      Object.keys(declared).forEach(function(name){
        if(widthScoped.has(name))return;
        if(declared[name].some(function(value){var reference=/var\(\s*(--sf-[a-z0-9_\\/.-]+)/gi,match;while((match=reference.exec(value))!==null){if(widthScoped.has(match[1]))return true}return false})){widthScoped.add(name);changed=true}
      });
    }
    var tokens=Object.create(null);
    Array.from(names).sort().slice(0,4096).forEach(function(name){
      /* A breakout or viewport width the Framework measures on the page is the page's own; the frame measures its own. */
      if(/breakout|viewport/.test(name))return;
      var own=root.style.getPropertyValue(name).trim(),value=own||(widthScoped.has(name)?'':computed.getPropertyValue(name).trim());
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
    /* The Framework loader adds component stylesheets (highlight.css for code) while the page runs; the frame gets those too. */
    Array.from(document.querySelectorAll('link[data-docara-framework-asset][rel="stylesheet"],link[data-docara-declarative-shell-style][rel="stylesheet"],link[rel="stylesheet"][href*="/vendor/simai-framework/"]')).forEach(function(link){
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
    /* A page example shows a built page of the site: it keeps the frame height the stylesheet gives it. */
    if(!frame.contentWindow||frame.hasAttribute('data-docara-example-page'))return;
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
    /* A reloaded document has none of the page's styles yet. */
    frame.removeAttribute('data-docara-example-styled');
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
    if(!event.data||event.data.type!=='docara:example-styled')return;
    var frame=docaraExampleFrames.find(function(candidate){return candidate.contentWindow===event.source})||null;
    if(frame)frame.setAttribute('data-docara-example-styled','');
  });
  window.addEventListener('message',function(event){
    if(!event.data||event.data.type!=='docara:example-height')return;
    var frame=docaraExampleFrames.find(function(candidate){return candidate.contentWindow===event.source})||null;
    var height=Number(event.data.height);
    if(!frame||!Number.isFinite(height))return;
    var size=Math.max(32,Math.ceil(height)),flow=Number(event.data.flow);
    var boxes=Array.isArray(event.data.overlays)?event.data.overlays.filter(function(box){return Array.isArray(box)&&box.length===4&&box.every(Number.isFinite)}):[];
    var extra=Number.isFinite(flow)&&boxes.length?Math.max(0,size-Math.max(32,Math.ceil(flow))):0;
    var preview=frame.closest('.docara-example-preview');
    frame.style.blockSize=size+'px';
    /* An open menu's room hangs below the frame's flow height over what follows, as a menu does on a page: the page keeps its place, and only the frame's flow box and the menus stay visible and clickable. */
    if(extra>0){
      var width=frame.clientWidth,flowSize=size-extra;
      var path='M0 0H'+width+'V'+flowSize+'H0Z'+boxes.map(function(box){return 'M'+box[0]+' '+box[1]+'H'+box[2]+'V'+box[3]+'H'+box[0]+'Z'}).join('');
      frame.style.marginBlockEnd=(-extra)+'px';
      frame.style.position='relative';
      frame.style.zIndex='5';
      frame.style.clipPath="path('"+path+"')";
      if(preview)preview.setAttribute('data-docara-example-overlay','');
    }else{
      frame.style.marginBlockEnd='';
      frame.style.position='';
      frame.style.zIndex='';
      frame.style.clipPath='';
      if(preview)preview.removeAttribute('data-docara-example-overlay');
    }
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
    var responsiveFrame=owned('iframe[data-docara-example-responsive-frame]')[0]||null;
    /* An inline example's responsive frame stays hidden, so the lazy loader never reaches it. It loads when the viewer opens: a frame moved into the viewer dialog reloads, so loading it earlier would only load it twice. The viewer keeps the inline preview until the frame reports it is styled. */
    function loadResponsiveFrame(){
      if(!responsiveFrame||!responsiveFrame.hasAttribute('data-docara-srcdoc'))return;
      var source=responsiveFrame.getAttribute('data-docara-srcdoc');
      responsiveFrame.removeAttribute('data-docara-srcdoc');
      responsiveFrame.setAttribute('srcdoc',docaraFrameSource(source));
    }
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
      // The desktop width is the screen's when the screen is narrower; a
      // tablet or phone width stays exact and scrolls if it must.
      previewFrame.style.inlineSize=viewport==='desktop'?'min('+exampleViewportWidths[viewport]+'px, 100%)':exampleViewportWidths[viewport]+'px';
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
    /* A page example draws a desktop screen, 1280 by 800, scaled down to the article's width, so the page shows its wide layout in the article; the responsive viewer shows it at the real width. */
    var pageFrame=previewFrame&&previewFrame.hasAttribute('data-docara-example-page')?previewFrame:null;
    function fitPageFrame(){
      if(!pageFrame)return;
      var panel=pageFrame.parentElement;
      if(isViewerOpen()){
        pageFrame.style.removeProperty('transform');
        pageFrame.style.removeProperty('transform-origin');
        pageFrame.style.removeProperty('block-size');
        panel.style.removeProperty('block-size');
        return;
      }
      var width=panel.clientWidth;
      if(!width)return;
      /* A column as narrow as a phone would shrink the desktop past reading, so there the page shows at the column's own width, as a phone draws it. */
      if(width<exampleViewportWidths.tablet){
        pageFrame.style.inlineSize='100%';
        pageFrame.style.removeProperty('max-inline-size');
        pageFrame.style.removeProperty('transform');
        pageFrame.style.removeProperty('transform-origin');
        pageFrame.style.blockSize='640px';
        panel.style.overflow='hidden';
        panel.style.blockSize='640px';
        return;
      }
      var scale=Math.min(1,width/exampleViewportWidths.desktop);
      pageFrame.style.inlineSize=exampleViewportWidths.desktop+'px';
      pageFrame.style.maxInlineSize='none';
      pageFrame.style.blockSize='800px';
      pageFrame.style.transformOrigin='0 0';
      pageFrame.style.transform='scale('+scale+')';
      panel.style.overflow='hidden';
      panel.style.blockSize=Math.round(800*scale)+'px';
    }
    if(pageFrame&&typeof ResizeObserver==='function'){new ResizeObserver(fitPageFrame).observe(pageFrame.parentElement)}
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
      fitPageFrame();
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
      loadResponsiveFrame();
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
      button.addEventListener('click',function(){
        setViewport(button.dataset.docaraExampleViewport,true);
        // Safari does not focus a button it clicks, so focus stayed on the
        // width the viewer opened with and kept its ring beside the pressed one.
        if(document.activeElement!==button){button.focus()}
      });
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
        return codeCopyText(code);
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
  /* An example log (:::example {log="…"}) is a section of the example's frame under its panels: tabs for the example's events and, with log_console=true, its console, newest row first. An inline example is part of the page, so the shell listens on its root here; a framed example's runtime records the rows in its own document and posts them as docara:example-log messages, which count only from one of the page's example frames. */
  (function(){
    var exampleLogs=[];
    function logCut(text,limit){text=String(text);return text.length>limit?text.slice(0,limit-1)+'…':text}
    function logValue(item){
      if(item===null)return 'null';
      var type=typeof item;
      if(type==='string')return '"'+logCut(item,40)+'"';
      if(type==='number'||type==='boolean'||type==='bigint'||type==='undefined')return String(item);
      if(type==='function')return 'ƒ';
      if(item&&item.nodeType===1)return '<'+String(item.tagName).toLowerCase()+'>';
      if(Array.isArray(item))return '['+item.length+']';
      return '{…}';
    }
    /* A short detail: up to four keys of an object, each long value cut with an ellipsis. */
    function logDetail(item){
      if(item===undefined||item===null||item==='')return '';
      if(typeof item!=='object'||item.nodeType===1)return logValue(item);
      if(item instanceof Error)return logCut((item.name||'Error')+': '+item.message,120);
      if(Array.isArray(item))return '['+item.slice(0,4).map(logValue).join(', ')+(item.length>4?', …':'')+']';
      var keys=Object.keys(item);
      return '{'+keys.slice(0,4).map(function(key){return key+': '+logValue(item[key])}).join(', ')+(keys.length>4?', …':'')+'}';
    }
    /* A custom event shows its detail; any other event names the control it happened on and its text. */
    function logEventDetail(event){
      if(event.detail!==null&&typeof event.detail==='object')return logDetail(event.detail);
      if(typeof CustomEvent==='function'&&event instanceof CustomEvent)return logDetail(event.detail);
      var target=event.target;
      if(!target||target.nodeType!==1)return '';
      /* The control the reader used, not the text inside it. */
      target=target.closest('a,button,input,select,textarea,summary,label,[role],[tabindex]')||target;
      var text=(target.textContent||'').trim().replace(/\s+/g,' ');
      return '<'+String(target.tagName).toLowerCase()+'>'+(text?' '+logCut(text,40):'');
    }
    function logTime(){
      var now=new Date();
      return [now.getHours(),now.getMinutes(),now.getSeconds()].map(function(part){return part<10?'0'+part:String(part)}).join(':');
    }
    function logText(id,fallback){var value=message(id);return value===id?fallback:value}
    function logFor(element){
      var example=element&&element.nodeType===1?element.closest('[data-docara-example]'):null;
      return exampleLogs.find(function(entry){return entry.example===example})||null;
    }
    docaraExamples.forEach(function(example){
      var section=Array.from(example.querySelectorAll('[data-docara-example-log]')).find(function(candidate){return candidate.closest('[data-docara-example]')===example});
      if(!section)return;
      var head=section.querySelector('.docara-example-log__head');
      var indicator=section.querySelector('.docara-example-log__indicator');
      var tabs=Array.from(section.querySelectorAll('[data-docara-example-log-tab]'));
      var panels=section.querySelector('.docara-example-log__panels');
      var clearButton=section.querySelector('[data-docara-example-log-clear]');
      var toggleButton=section.querySelector('[data-docara-example-log-toggle]');
      var tablist=section.querySelector('[role="tablist"]');
      if(!tabs.length||!panels||!clearButton||!toggleButton)return;
      function panelOf(key){return section.querySelector('[data-docara-example-log-panel="'+key+'"]')}
      function selectedTab(){return tabs.find(function(tab){return tab.getAttribute('aria-selected')==='true'})||tabs[0]}
      function placeIndicator(animate){positionTabIndicator(selectedTab(),indicator,head,animate)}
      var title=logText('examples.log_title','Example log');
      section.setAttribute('aria-label',title);
      if(tablist)tablist.setAttribute('aria-label',title);
      var clearLabel=logText('examples.log_clear','Clear the log');
      clearButton.setAttribute('aria-label',clearLabel);
      clearButton.title=clearLabel;
      tabs.forEach(function(tab){
        var key=tab.dataset.docaraExampleLogTab;
        var label=tab.querySelector('[data-docara-example-log-label]');
        if(label)label.textContent=logText('examples.log_'+key,label.textContent);
        var panel=panelOf(key),empty=panel&&panel.querySelector('[data-docara-example-log-empty]');
        if(empty)empty.textContent=logText('examples.log_'+key+'_empty',empty.textContent);
      });
      function isCollapsed(){return section.dataset.docaraExampleLogState==='collapsed'}
      function setCollapsed(collapsed){
        section.dataset.docaraExampleLogState=collapsed?'collapsed':'expanded';
        panels.hidden=collapsed;
        var label=collapsed?logText('examples.log_expand','Expand the log'):logText('examples.log_collapse','Collapse the log');
        toggleButton.setAttribute('aria-expanded',collapsed?'false':'true');
        toggleButton.setAttribute('aria-label',label);
        toggleButton.title=label;
        var icon=toggleButton.querySelector('sf-icon');
        if(icon)icon.setAttribute('icon',collapsed?(toggleButton.dataset.expandIcon||'expand_more'):(toggleButton.dataset.collapseIcon||'expand_less'));
      }
      function selectTab(tab,moveFocus){
        tabs.forEach(function(candidate){
          var selected=candidate===tab,panel=panelOf(candidate.dataset.docaraExampleLogTab);
          candidate.setAttribute('aria-selected',selected?'true':'false');
          candidate.tabIndex=selected?0:-1;
          if(panel)panel.hidden=!selected;
        });
        placeIndicator(true);
        if(moveFocus)tab.focus();
      }
      function updateCount(key){
        var tab=tabs.find(function(candidate){return candidate.dataset.docaraExampleLogTab===key});
        var count=tab&&tab.querySelector('[data-docara-example-log-count]'),panel=panelOf(key);
        if(count&&panel)count.textContent=String(panel.querySelectorAll('.docara-example-log__row').length);
      }
      function write(key,level,name,detail){
        var panel=panelOf(key),rows=panel&&panel.querySelector('[role="log"]');
        if(!rows)return;
        var empty=panel.querySelector('[data-docara-example-log-empty]');
        if(empty)empty.hidden=true;
        var row=document.createElement('div');
        row.className='docara-example-log__row';
        if(level)row.dataset.level=level;
        [['time',logTime()],['name',name],['detail',detail]].forEach(function(part){
          var cell=document.createElement('span');
          cell.className='docara-example-log__'+part[0];
          cell.textContent=part[1];
          row.appendChild(cell);
        });
        rows.insertBefore(row,rows.querySelector('.docara-example-log__row'));
        var all=rows.querySelectorAll('.docara-example-log__row');
        if(all.length>200)all[all.length-1].remove();
        updateCount(key);
      }
      tabs.forEach(function(tab,index){
        tab.addEventListener('click',function(){selectTab(tab,false);if(isCollapsed())setCollapsed(false)});
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
      clearButton.addEventListener('click',function(){
        var key=selectedTab().dataset.docaraExampleLogTab,panel=panelOf(key);
        if(!panel)return;
        panel.querySelectorAll('.docara-example-log__row').forEach(function(row){row.remove()});
        var empty=panel.querySelector('[data-docara-example-log-empty]');
        if(empty)empty.hidden=false;
        updateCount(key);
      });
      toggleButton.addEventListener('click',function(){setCollapsed(!isCollapsed())});
      setCollapsed(isCollapsed());
      var events=(section.dataset.docaraExampleLogEvents||'').split(/\s+/).filter(Boolean);
      var root=Array.from(example.querySelectorAll('[data-docara-example-inline-preview]')).find(function(candidate){return candidate.closest('[data-docara-example]')===example})||null;
      if(root){
        events.forEach(function(name){
          root.addEventListener(name,function(event){write('events','',event.type,logEventDetail(event))},true);
        });
      }
      exampleLogs.push({example:example,write:write,place:placeIndicator,inline:Boolean(root),console:section.dataset.docaraExampleLogConsole==='true'});
      placeIndicator(false);
    });
    if(!exampleLogs.length)return;
    if(document.fonts&&document.fonts.ready){
      document.fonts.ready.then(function(){exampleLogs.forEach(function(entry){entry.place(false)})});
    }
    window.addEventListener('resize',function(){exampleLogs.forEach(function(entry){entry.place(false)})},{passive:true});
    window.addEventListener('message',function(event){
      var data=event.data;
      if(!data||data.type!=='docara:example-log')return;
      var frame=docaraExampleFrames.find(function(candidate){return candidate.contentWindow===event.source})||null;
      var entry=frame?logFor(frame):null;
      if(!entry||(data.tab!=='events'&&!(data.tab==='console'&&entry.console)))return;
      if(['','log','info','warn','error'].indexOf(data.level)===-1||typeof data.name!=='string'||data.name===''||typeof data.detail!=='string')return;
      entry.write(data.tab,data.level,logCut(data.name,80),logCut(data.detail,300));
    });
    /* docara.example.log(name, detail) writes the example's own line. In a frame the frame's runtime defines it. On the page the line goes to the example that holds the given element or the running script, else the example the reader last touched, else the only example with a log. */
    var lastLog=null;
    ['pointerdown','focusin','keydown'].forEach(function(type){
      document.addEventListener(type,function(event){var entry=logFor(event.target);if(entry)lastLog=entry},true);
    });
    var api=window.docara&&typeof window.docara==='object'?window.docara:(window.docara={});
    api.example=api.example&&typeof api.example==='object'?api.example:{};
    api.example.log=function(name,detail,target){
      var entry=logFor(target)||logFor(document.currentScript)||lastLog||(exampleLogs.length===1?exampleLogs[0]:null);
      if(entry)entry.write('events','',logCut(String(name),80),logDetail(detail));
    };
    /* An inline example runs no script of its own (a script sends it to a frame), so the page's console cannot be split by example: the Console tab of an inline example shows the page's console.log, info, warn and error while the page is open. The wrapper is installed only when such an example asks for it, and a call made by a script inside an example goes to that example alone. */
    var consoleLogs=exampleLogs.filter(function(entry){return entry.inline&&entry.console});
    if(!consoleLogs.length)return;
    var writingConsole=false;
    ['log','info','warn','error'].forEach(function(level){
      var original=console[level];
      if(typeof original!=='function')return;
      console[level]=function(){
        var result=original.apply(console,arguments);
        if(writingConsole)return result;
        writingConsole=true;
        try{
          var text=logCut(Array.prototype.map.call(arguments,function(item){return typeof item==='string'?item:logDetail(item)}).join(' '),300);
          var own=logFor(document.currentScript);
          (own&&own.inline&&own.console?[own]:consoleLogs).forEach(function(entry){entry.write('console',level,level,text)});
        }finally{writingConsole=false}
        return result;
      };
    });
  })();
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
  // Tabs with a sync name follow one another: picking "Linux" in one block
  // picks it in every block of that name, and the choice is remembered for
  // the next page. The Framework tabs script owns the switch itself, so a
  // click is the only thing passed on. A tab is matched by its own name
  // (data-docara-tab), never by its place, so blocks may list different tabs.
  (function(){
    var syncPrefix='docara.tabs.';
    var applying=false;
    function groups(name){
      return Array.prototype.filter.call(
        document.querySelectorAll('[data-docara-block="tabs"][data-docara-tabs-sync]'),
        function(group){return !name||group.getAttribute('data-docara-tabs-sync')===name}
      );
    }
    function owned(group,selector){
      return Array.prototype.filter.call(
        group.querySelectorAll(selector),
        function(element){return element.closest('[data-docara-block="tabs"]')===group}
      );
    }
    // The same switch the Framework tabs script makes on a click, so the
    // choice holds whichever script ran first: data-active-index is what the
    // Framework reads when it binds, and the classes are what the reader sees
    // if it has already bound.
    function select(group,key){
      var tabs=owned(group,'[role="tab"][data-docara-tab]');
      var panels=owned(group,'[role="tabpanel"][data-docara-tab]');
      var index=-1;
      tabs.forEach(function(tab,position){if(tab.getAttribute('data-docara-tab')===key){index=position}});
      if(index<0)return false;
      group.setAttribute('data-active-index',String(index));
      tabs.forEach(function(tab,position){
        var active=position===index;
        tab.classList.toggle('selected',active);
        tab.setAttribute('aria-selected',active?'true':'false');
        tab.setAttribute('tabindex',active?'0':'-1');
      });
      panels.forEach(function(panel,position){
        var active=position===index;
        panel.classList.toggle('selected',active);
        panel.toggleAttribute('hidden',!active);
      });
      return true;
    }
    function apply(name,key,except){
      applying=true;
      try{
        groups(name).forEach(function(group){
          if(group!==except)select(group,key);
        });
      }finally{applying=false}
    }
    function stored(name){
      try{return localStorage.getItem(syncPrefix+name)}catch(error){return null}
    }
    document.addEventListener('click',function(event){
      if(applying)return;
      var tab=event.target&&event.target.closest?event.target.closest('[role="tab"][data-docara-tab]'):null;
      if(!tab)return;
      var group=tab.closest('[data-docara-block="tabs"][data-docara-tabs-sync]');
      if(!group)return;
      var name=group.getAttribute('data-docara-tabs-sync');
      var key=tab.getAttribute('data-docara-tab');
      try{localStorage.setItem(syncPrefix+name,key)}catch(error){}
      apply(name,key,group);
    });
    window.addEventListener('storage',function(event){
      if(!event.key||event.key.indexOf(syncPrefix)!==0||!event.newValue)return;
      apply(event.key.slice(syncPrefix.length),event.newValue,null);
    });
    groups().forEach(function(group){
      var key=stored(group.getAttribute('data-docara-tabs-sync'));
      if(key){apply(group.getAttribute('data-docara-tabs-sync'),key,null)}
    });
  })();
  localizeCodeCopy(document);
  if(document.body){
    new MutationObserver(function(records){
      records.forEach(function(record){
        if(record.type==='characterData'){localizeCodeCopy(record.target.parentElement);return}
        record.addedNodes.forEach(localizeCodeCopy);
        record.addedNodes.forEach(function(node){if(node.nodeType===1&&node.querySelector&&node.querySelector('code[data-docara-code-lines]'))highlightCodeLines(node)});
      });
    }).observe(document.body,{childList:true,subtree:true,characterData:true});
  }
})();
