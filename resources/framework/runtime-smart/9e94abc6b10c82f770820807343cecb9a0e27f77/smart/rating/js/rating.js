(()=>{"use strict";const t="undefined"!=typeof window&&(window.SF?.smart||window.SF)||{},e="undefined"!=typeof window?window:{},r=t.SfBaseElement||e.SfBaseElement;if(!r)throw new Error("SF smart runtime is not loaded. Load smart-base before smart components.");const a=t.html||e.html,i=t.nothing||e.nothing,n=(t.render||e.render,t.litProps||e.litProps,t.toBoolean,t.toAttributeName,t.toNumber,t.normalizeEnum,t.parseJsonAttribute,r);function s(t,e=5,r=5){const a=t/e*r;return Array.from({length:r},(t,e)=>function(t,e=0,r=100){return Math.min(r,Math.max(e,t))}(100*(a-e)))}function o(t){const{value:e,max:r,starsCount:n,showValue:o,rootClass:l,disabled:u,readonly:h,isInteractive:g,ariaLabel:m}=t,d=s(e,r,n),c=["sf-rating","inline-flex","items-cross-center","gap-1/4",g?"sf-rating--interactive":"sf-rating--readonly",u?"disabled":"",h?"readonly":"",t.error?"error":"",l].filter(Boolean),f=Array.from({length:n},(t,e)=>e+1);return a`
        <div
                class=${c.join(" ")}
                role=${g?"radiogroup":"img"}
                aria-label=${m||"Rating"}
                aria-valuenow=${String(e)}
                aria-valuemin="0"
                aria-valuemax=${String(r)}
                data-value=${String(e)}
                data-max=${String(r)}
        >
            ${f.map((e,r)=>function(t,e,r){const{component:i,value:n,max:s,size:o,icon:l,type:u,scheme:h,activeScheme:g,disabled:m,isInteractive:d}=t,c=e<=n,f=["sf-rating-item",c?"sf-rating-item--active":"sf-rating-item--inactive"].filter(Boolean);return a`
        <sf-icon-button
                class=${f.join(" ")}
                size=${o||"1"}
                type=${u||"link"}
                tightness="low"
                scheme=${c?g||"warning":h||"on-surface"}
                icon=${l||"star"}
                ?filled=${c}
                ?disabled=${m||!d}
                aria-label=${`${e}/${s}`}
                aria-checked=${c?"true":"false"}
                data-value=${String(e)}
                @click=${t=>i?.onRatingSelect?.(e,t)}
        >
        <span slot="icon">
    <span class="sf-rating-star relative items-cross-start">
      <svg
              width="18"
              height="17"
              viewBox="0 0 18 17"
              fill="currentColor"
              xmlns="http://www.w3.org/2000/svg"
      >
        <path
                d="M8.31178 0.308206C8.48257 -0.10244 9.0643 -0.10244 9.2351 0.308206L11.3012 5.27569C11.3732 5.4488 11.536 5.56709 11.7229 5.58207L17.0857 6.01201C17.529 6.04755 17.7088 6.6008 17.371 6.89013L13.2851 10.3901C13.1427 10.5121 13.0805 10.7035 13.1241 10.8859L14.3724 16.1191C14.4756 16.5517 14.0049 16.8936 13.6254 16.6618L9.03406 13.8574C8.87406 13.7597 8.67282 13.7597 8.51281 13.8574L3.9215 16.6618C3.54195 16.8936 3.07132 16.5517 3.17451 16.1191L4.42282 10.8859C4.46633 10.7035 4.40414 10.5121 4.26175 10.3901L0.175851 6.89013C-0.161918 6.6008 0.017845 6.04755 0.461172 6.01201L5.82398 5.58207C6.01088 5.56709 6.17368 5.4488 6.24569 5.27569L8.31178 0.308206Z"
        />
      </svg>

      <span
              style="--fill:${r}%"
              class="sf-rating-icon-background z-1 absolute flex inline-start-0 top-0"
      >
        <svg
                width="18"
                height="17"
                viewBox="0 0 18 17"
                fill="currentColor"
                xmlns="http://www.w3.org/2000/svg"
        >
          <path
                  d="M8.31178 0.308206C8.48257 -0.10244 9.0643 -0.10244 9.2351 0.308206L11.3012 5.27569C11.3732 5.4488 11.536 5.56709 11.7229 5.58207L17.0857 6.01201C17.529 6.04755 17.7088 6.6008 17.371 6.89013L13.2851 10.3901C13.1427 10.5121 13.0805 10.7035 13.1241 10.8859L14.3724 16.1191C14.4756 16.5517 14.0049 16.8936 13.6254 16.6618L9.03406 13.8574C8.87406 13.7597 8.67282 13.7597 8.51281 13.8574L3.9215 16.6618C3.54195 16.8936 3.07132 16.5517 3.17451 16.1191L4.42282 10.8859C4.46633 10.7035 4.40414 10.5121 4.26175 10.3901L0.175851 6.89013C-0.161918 6.6008 0.017845 6.04755 0.461172 6.01201L5.82398 5.58207C6.01088 5.56709 6.17368 5.4488 6.24569 5.27569L8.31178 0.308206Z"
          />
        </svg>
      </span>
    </span>
  </span>
        </sf-icon-button>
    `}(t,e,d[r]))}
            ${o?a`<span class="sf-rating-value">${e}/${r}</span>`:i}
        </div>
    `}function l(t,e=5){const r=Number(t);return!Number.isFinite(r)||r<0?0:Math.min(r,e)}(class extends n{static get props(){return{templateName:{attribute:"template",default:"default"},value:{default:0},max:{default:5},starsCount:{default:5},size:{default:"1"},icon:{default:"star"},type:{default:"link"},scheme:{default:"on-surface"},activeScheme:{attribute:"active-scheme",default:"warning"},readonly:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},required:{type:Boolean,default:!1},error:{type:Boolean,default:!1},showValue:{attribute:"show-value",type:Boolean,default:!1},rootClass:{default:""},ariaLabel:{default:"Rating"}}}get templateName(){return this.getAttribute("template")||"default"}get max(){return function(t){const e=Number.parseInt(t,10);return!Number.isFinite(e)||e<1?5:Math.min(e,10)}(this.getAttribute("max")||5)}get value(){return l(this.getAttribute("value")||0,this.max)}set value(t){this.setRating(t,{emit:!1})}get size(){return this.getAttribute("size")||"1"}get starsCount(){return this.getAttribute("stars-count")||5}get icon(){return this.getAttribute("icon")||"star"}get type(){return this.getAttribute("type")||"link"}get scheme(){return this.getAttribute("scheme")||"on-surface"}get activeScheme(){return this.getAttribute("active-scheme")||"warning"}get readonly(){return this.getBooleanAttr("readonly",!1)}get disabled(){return this.getBooleanAttr("disabled",!1)}get required(){return this.getBooleanAttr("required",!1)}get error(){return this.getBooleanAttr("error",!1)}get showValue(){return this.getBooleanAttr("show-value",!1)}get rootClass(){return this.getAttribute("root-class")||""}get ariaLabel(){return this.getAttribute("aria-label")||"Rating"}templateContext(){const t=this.max,e=l(this.value,t);return this.createTemplateContext({...this.getPropsContext(),value:e,max:t,isInteractive:!this.readonly&&!this.disabled,required:this.required,error:this.error,component:this})}template(){return o(this.templateContext())}setRating(t,e={}){const{emit:r=!0}=e,a=this.value,i=l(t,this.max);return this.setAttribute("value",String(i)),r&&i!==a&&(this.emitRatingEvent("input",i,a),this.emitRatingEvent("change",i,a)),this}onRatingSelect(t,e){this.disabled||this.readonly?e?.preventDefault?.():this.setRating(t)}emitRatingEvent(t,e,r){this.dispatchEvent(new CustomEvent(t,{bubbles:!0,composed:!0,detail:{value:e,previousValue:r,max:this.max,component:this}}))}reset(){return this.setRating(0),this}get stateValue(){return{value:this.value,max:this.max}}getFormName(){return this.getAttribute("name")||""}isFormRequired(){return this.required}getFormValue(){return this.value}isFormValueEmpty(t=this.getFormValue()){return Number(t)<=0}setFormError(t=!1){return t?this.setAttribute("error",""):this.removeAttribute("error"),this}}).define("sf-rating")})();