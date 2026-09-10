(()=>{"use strict";const t="undefined"!=typeof window&&(window.SF?.smart||window.SF)||{},e="undefined"!=typeof window?window:{},i=t.SfBaseElement||e.SfBaseElement;if(!i)throw new Error("SF smart runtime is not loaded. Load smart-base before smart components.");const n=t.html||e.html,r=t.nothing||e.nothing,o=(t.render||e.render,t.litProps||e.litProps,t.toBoolean,t.toAttributeName,t.toNumber,t.normalizeEnum,t.parseJsonAttribute,i);function a(t){const e=function(...t){return t.flat().filter(Boolean).join(" ")}("sf-link","flex","flex-row","flex-nowrap","justify-start","items-center",t.disabled?"disabled":"",t.rootClass),i=t.text||t.href||"";return t.href||i?n`
    <a
      class=${e}
      :ref=${t.refs?.root}
      href=${t.disabled?r:t.href||r}
      target=${t.target||r}
      rel=${t.rel||r}
      aria-label=${t.ariaLabel||i||r}
      aria-disabled=${t.disabled?"true":r}
      tabindex=${t.disabled?"-1":r}
      @click=${e=>t.component?.handleClick?.(e)}
    >
      ${function(t){if("none"===t.iconMode)return r;const e="image"===t.iconMode&&t.iconSrc&&!t.iconFailed,i="favicon"===t.iconMode&&!1!==t.favicon&&t.faviconSrc&&!t.faviconFailed;return n`
    <span
      class="sf-link-image sf-link-icon flex flex-none  items-cross-center"
      :ref=${t.refs?.linkImage}
      aria-hidden="true"
    >
      ${e?n`
          <img
            src=${t.iconSrc}
            width=${String(t.faviconSize||32)}
            height=${String(t.faviconSize||32)}
            loading="lazy"
            decoding="async"
            class="w-full h-full object-contain"
            alt=""
            @error=${()=>t.component?.handleIconError?.()}
          />
        `:i?n`
          <img
            src=${t.faviconSrc}
            width=${String(t.faviconSize||32)}
            height=${String(t.faviconSize||32)}
            loading="lazy"
            decoding="async"
            class="w-full h-full object-contain"
            alt=""
            @error=${()=>t.component?.handleFaviconError?.()}
          />
        `:n`<sf-icon icon="${"symbol"===t.iconMode?t.iconValue:"link"}" size="1"></sf-icon>`}
    </span>
  `}(t)}
      <span class="sf-link-content flex-1 transition${t.component.noWrap?" overflow-hidden wrap-none t-ellipsis":""}">${i}</span>
    </a>
  `:r}(class extends o{constructor(){super(),this.refs={root:this.createRef(),linkImage:this.createRef()},this._faviconFailedSrc="",this._iconFailedSrc=""}static get props(){return{templateName:{attribute:"template",default:"default"},link:{default:""},href:{default:""},text:{default:""},label:{default:""},noWrap:{type:Boolean,default:!1},target:{default:"_blank"},rel:{default:"noopener noreferrer"},icon:{default:"link"},favicon:{type:Boolean,default:!0},faviconProvider:{attribute:"favicon-provider",default:"direct"},faviconUrl:{attribute:"favicon-url",default:""},faviconSize:{attribute:"favicon-size",type:Number,default:32},disabled:{type:Boolean,default:!1},ariaLabel:{attribute:"aria-label",default:""},rootClass:{default:""}}}get componentName(){return"link"}get templateName(){return this.getAttribute("template")||"default"}get link(){return this.getAttribute("link")||this.getAttribute("href")||""}get href(){return function(t=""){const e=String(t||"").trim();return e?/^(https?:|mailto:|tel:)/i.test(e)?e:`https://${e}`:""}(this.link)}get text(){return this.getAttribute("text")||this.getAttribute("label")||this.getAttribute("title")||this.link||this.href}get faviconSize(){const t=this.getNumberAttr("faviconSize",32);return t>0?t:32}get faviconSrc(){const t=this.getAttribute("favicon-url")||"";if(t)return t;const e=function(t=""){try{return new URL(t).origin}catch{return""}}(this.href);if(!e||this.href.startsWith("mailto:")||this.href.startsWith("tel:"))return"";if("google"===this.getAttribute("favicon-provider")){const t=new URL(this.href).hostname;return`https://www.google.com/s2/favicons?domain=${encodeURIComponent(t)}&sz=${this.faviconSize}`}return`${e}/favicon.ico`}get iconValue(){return this.hasAttribute("icon")&&this.getAttribute("icon")||""}get iconMode(){if(!this.hasAttribute("icon"))return"favicon";const t=this.iconValue;return!t||function(t=""){return["false","0","off","none","no"].includes(String(t).trim().toLowerCase())}(t)?"none":function(t=""){const e=String(t||"").trim();return!!e&&(/[:/]/.test(e)||/^www\./i.test(e))}(t)?"image":"symbol"}get iconSrc(){return"image"!==this.iconMode?"":function(t=""){const e=String(t||"").trim();return e?/^(https?:|data:|blob:)/i.test(e)||e.startsWith("/")||e.startsWith("./")||e.startsWith("../")?e:/^www\./i.test(e)?`https://${e}`:e:""}(this.iconValue)}get noWrap(){return this.getBooleanAttr("no-wrap",!1)}get disabled(){return this.getBooleanAttr("disabled",!1)||this.classList.contains("disabled")}handleFaviconError(){this._faviconFailedSrc=this.faviconSrc,this.requestComponentUpdate("favicon-error")}handleIconError(){this._iconFailedSrc=this.iconSrc,this.requestComponentUpdate("icon-error")}handleClick(t){this.disabled&&(t.preventDefault(),t.stopPropagation())}templateContext(){const t=this.getPropsContext(),e=this.faviconSrc,i=this.iconSrc;return this.createTemplateContext({...t,component:this,refs:this.refs,href:this.href,text:this.text,iconMode:this.iconMode,iconValue:this.iconValue,iconSrc:i,iconFailed:Boolean(i&&this._iconFailedSrc===i),faviconSrc:e,faviconFailed:Boolean(e&&this._faviconFailedSrc===e),faviconSize:this.faviconSize,rootClass:this.getRootClass(),disabled:this.disabled})}template(){return a(this.templateContext())}}).define("sf-link")})();