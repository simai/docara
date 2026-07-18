(()=>{"use strict";const t="undefined"!=typeof window&&(window.SF?.smart||window.SF)||{},e="undefined"!=typeof window?window:{},n=t.SfBaseElement||e.SfBaseElement;if(!n)throw new Error("SF smart runtime is not loaded. Load smart-base before smart components.");const o=t.html||e.html,s=t.nothing||e.nothing,i=(t.render||e.render,t.litProps||e.litProps,t.toBoolean,t.toAttributeName,t.toNumber,t.normalizeEnum,t.parseJsonAttribute,n);function r(t){const e=function(...t){return t.flat().filter(Boolean).join(" ")}("sf-alert",`sf-alert--${t.type}`,`sf-alert--${t.variant}`,"flex","items-start",t.rootClass),n=function(t){return void 0!==t.icon&&null!==t.icon&&""!==t.icon?t.icon:{clear:"info",info:"info",danger:"error",warning:"warning",success:"check_circle"}[t.type]||"info"}(t),i=t.component?.hasSlotContent?.("title")?t.component.getSlotContent("title"):t.title,r=t.component?.hasSlotContent?.("supporting-text")?t.component.getSlotContent("supporting-text"):t.supportingText;return o`
    <div class=${e}>
      ${n?o`<sf-icon icon="${n}" aria-hidden="true"></sf-icon>`:s}

      <div class="sf-alert-wrap flex flex-col flex-1">
        <div class="sf-alert-content flex flex-col flex-1">
          ${i?o`<div class="sf-alert-text">${i}</div>`:s}
          ${r?o`<div class="sf-alert-supporting-text">${r}</div>`:s}
        </div>

        ${t.actionText||t.secondaryActionText?o`
              <div class="sf-alert-buttons flex items-center">
                ${t.actionText?o`
                      <button
                        type="button"
                        class="sf-button sf-button--default sf-button--on-surface sf-button--size-1/2"
                        data-alert-action=${t.action||"action"}
                      >
                        <span class="sf-button-text-container">${t.actionText}</span>
                      </button>
                    `:s}
                ${t.secondaryActionText?o`
                      <button
                        type="button"
                        class="sf-button sf-button--on-surface sf-button--outline sf-button--size-1/2"
                        data-alert-action=${t.secondaryAction||"secondary"}
                      >
                        <span class="sf-button-text-container">${t.secondaryActionText}</span>
                      </button>
                    `:s}
              </div>
            `:s}
      </div>

      ${t.closable?o`
                  <sf-icon-button
                          variant="close"
                          size="1"
                          type="link"
                          scheme="on-surface"
                  ></sf-icon-button>
          `:s}
    </div>
  `}(class extends i{static get props(){return{templateName:{attribute:"template",default:"default"},type:{default:"clear"},variant:{default:"default"},icon:{default:""},title:{default:""},supportingText:{default:""},actionText:{default:""},action:{default:"action"},secondaryActionText:{default:""},secondaryAction:{default:"secondary"},closable:{type:Boolean,default:!1},rootClass:{default:""}}}constructor(){super(),this._boundRoot=null}get type(){return this.getAttribute("type")||"clear"}get variant(){return this.getAttribute("variant")||"default"}get icon(){return this.getAttribute("icon")||""}get title(){return this.getAttribute("title")||""}get supportingText(){return this.getAttribute("supporting-text")||""}get actionText(){return this.getAttribute("action-text")||""}get action(){return this.getAttribute("action")||"action"}get secondaryActionText(){return this.getAttribute("secondary-action-text")||""}get secondaryAction(){return this.getAttribute("secondary-action")||"secondary"}get closable(){return this.getBooleanAttr("closable",!1)}templateContext(){const t=this.getPropsContext();return this.createTemplateContext({...t,component:this,rootClass:this.getRootClass()})}template(){return r(this.templateContext())}afterRender(){this._boundRoot&&(this.removeAlertListeners(this._boundRoot),this._boundRoot=null),this._boundRoot=this.querySelector(".sf-alert"),this._boundRoot&&this.addAlertListeners(this._boundRoot)}onDisconnected(){this._boundRoot&&(this.removeAlertListeners(this._boundRoot),this._boundRoot=null)}addAlertListeners(t){t.querySelectorAll("[data-alert-close]").forEach(t=>{t.addEventListener("click",this._handleCloseClick)}),t.querySelectorAll("[data-alert-action]").forEach(t=>{t.addEventListener("click",this._handleActionClick)})}removeAlertListeners(t){t.querySelectorAll("[data-alert-close]").forEach(t=>{t.removeEventListener("click",this._handleCloseClick)}),t.querySelectorAll("[data-alert-action]").forEach(t=>{t.removeEventListener("click",this._handleActionClick)})}_handleCloseClick=t=>{t.preventDefault(),this.dismiss()};_handleActionClick=t=>{this.dispatchEvent(new CustomEvent("sf-alert-action",{bubbles:!0,composed:!0,detail:{action:t.currentTarget?.getAttribute("data-alert-action")||"",alert:this}}))};dismiss(){return this.dispatchEvent(new CustomEvent("sf-alert-close",{bubbles:!0,composed:!0,detail:{alert:this}})),this.isConnected&&this.remove(),this}close(){return this.dismiss()}onAction(t,e){return"function"==typeof t&&this.addEventListener("sf-alert-action",e=>{t(e.detail?.action,e.detail,e)},e),this}onClose(t,e){return"function"==typeof t&&this.addEventListener("sf-alert-close",e=>{t(e.detail,e)},e),this}}).define("sf-alert")})();