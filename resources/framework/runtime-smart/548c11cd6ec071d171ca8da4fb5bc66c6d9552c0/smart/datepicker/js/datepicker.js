/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "0845ef25b9de"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   normalizeEnum: () => (/* binding */ normalizeEnum),
/* harmony export */   parseJsonAttribute: () => (/* binding */ parseJsonAttribute),
/* harmony export */   toAttributeName: () => (/* binding */ toAttributeName),
/* harmony export */   toBoolean: () => (/* binding */ toBoolean),
/* harmony export */   toNumber: () => (/* binding */ toNumber)
/* harmony export */ });
/* harmony import */ var lit__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("fef8077ac919");
/* harmony import */ var lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("7fcbcc00731e");


function toBoolean(value, fallback = false) {
  if (value === undefined || value === null || value === "") return fallback;
  if (typeof value === "boolean") return value;
  return ["1", "true", "yes", "on", "checked", "disabled", "download", "indeterminate", "multiple"].includes(String(value).toLowerCase());
}
function toAttributeName(key) {
  if (key === "className") {
    return "class";
  }

  return String(key || "").replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`);
}
function toNumber(value, fallback = 0) {
  const number = Number(value);
  return Number.isFinite(number) ? number : fallback;
}
function normalizeEnum(value, allowed, fallback) {
  const normalized = String(value || fallback).trim().toLowerCase();
  return Array.isArray(allowed) && allowed.includes(normalized) ? normalized : fallback;
}
function parseJsonAttribute(element, name, fallback = null) {
  const rawValue = element?.getAttribute?.(name);

  if (!rawValue) {
    return fallback;
  }

  try {
    return JSON.parse(rawValue);
  } catch (error) {
    console.warn(`${element?.tagName?.toLowerCase?.() || "sf-element"}: invalid ${name} JSON`, error);
    return fallback;
  }
}

class SfBaseElement extends HTMLElement {
  static externalTemplateBasePath = "/local/smart/templates";
  static externalTemplateModules = new Map();
  static externalTemplateMisses = new Set();
  static externalTemplateChecks = new Map();
  static externalTemplateCssLoaded = new Set();
  static externalTemplateCssMisses = new Set();
  static waitForStylesBeforeRenderReady = true;
  static styleReadyTimeout = 3000;
  static styleReadinessCssId = "sf-smart-style-readiness";

  static get props() {
    return {};
  }

  static get observedAttributes() {
    return Array.from(new Set([...this.propsToAttributes(), "root-class", "root-style", "style"]));
  }

  static propsToAttributes(props = this.props) {
    return Object.entries(props || {}).map(([key, config]) => this.normalizePropConfig(key, config).attribute).filter(Boolean);
  }

  static normalizePropConfig(key, config = "") {
    const isConfigObject = config && typeof config === "object" && !Array.isArray(config) && (Object.prototype.hasOwnProperty.call(config, "type") || Object.prototype.hasOwnProperty.call(config, "default") || Object.prototype.hasOwnProperty.call(config, "attribute") || Object.prototype.hasOwnProperty.call(config, "parser") || Object.prototype.hasOwnProperty.call(config, "parse") || Object.prototype.hasOwnProperty.call(config, "values"));
    const propConfig = isConfigObject ? { ...config
    } : {
      default: config
    };
    const defaultValue = propConfig.default;
    const inferredType = propConfig.type || (Array.isArray(defaultValue) ? Array : defaultValue !== null && typeof defaultValue === "object" ? Object : typeof defaultValue === "boolean" ? Boolean : typeof defaultValue === "number" ? Number : String);
    return { ...propConfig,
      key,
      attribute: propConfig.attribute === false ? "" : propConfig.attribute || toAttributeName(key),
      default: defaultValue,
      type: inferredType
    };
  }

  static toBoolean(value, fallback = false) {
    return toBoolean(value, fallback);
  }

  static toAttributeName(key) {
    return toAttributeName(key);
  }

  static toNumber(value, fallback = 0) {
    return toNumber(value, fallback);
  }

  static normalizeEnum(value, allowed, fallback) {
    return normalizeEnum(value, allowed, fallback);
  }

  static parseJsonAttribute(element, name, fallback = null) {
    return parseJsonAttribute(element, name, fallback);
  }

  static get tagName() {
    return this.resolveTagName();
  }

  static resolveTagName(className = this.name) {
    const tagName = String(className || "").trim().replace(/([a-z0-9])([A-Z])/g, "$1-$2").replace(/([A-Z])([A-Z][a-z])/g, "$1-$2").replace(/_/g, "-").toLowerCase();

    if (!tagName) {
      return "";
    }

    return tagName.includes("-") ? tagName : `sf-${tagName}`;
  }

  static define(tagName) {
    const resolvedTagName = String(tagName || this.resolveTagName()).trim().toLowerCase();

    if (!resolvedTagName || !resolvedTagName.includes("-")) {
      throw new Error(`${this.name || "SfBaseElement"}.define(): cannot resolve custom element tag. Call define() on a named subclass or pass tagName explicitly.`);
    }

    const existing = customElements.get(resolvedTagName);

    if (existing) {
      if (existing !== this) {
        console.warn(`${this.name || "SfBaseElement"}.define(): ${resolvedTagName} is already defined`, existing);
      }

      return existing;
    }

    customElements.define(resolvedTagName, this);
    return this;
  }

  static ensureStyleReadinessCss() {
    if (typeof document === "undefined" || document.getElementById(this.styleReadinessCssId)) {
      return;
    }

    const style = document.createElement("style");
    style.id = this.styleReadinessCssId;
    style.textContent = "[data-sf-style-pending],[data-sf-style-pending] *{visibility:hidden!important;}";
    document.head.append(style);
  }

  constructor() {
    super();
    this._updateScheduled = false;
    this._changedAttributes = new Set();
    this._updateLoopCount = 0;
    this._updateLoopResetScheduled = false;
    this._updateLoopBlocked = false;
    this._updateLoopWarned = false;
    this._isMounted = false;
    this._hasRendered = false;
    this._renderToken = 0;
    this._externalTemplateModule = null;
    this._slotTemplates = new Map();
    this._liveSlotNodes = new Map();
    this._slotTemplatesCaptured = false;
    this._customTemplateProps = new Map();
    this._refEffects = new Map();
    this._activeRefEffects = null;
    this._hostStyle = "";
    this._syncingHostStyle = false;
    this._authorStyleElement = null;
    this._authorStyleObserver = null;
    this._styleReady = false;
    this._styleReadyPromise = null;
    this.__sfSmartElement = true;
    this.__sfSourceCaptured = false;
  }

  connectedCallback() {
    this.constructor.ensureStyleReadinessCss();
    this.captureHostStyle();
    this.applyHostDisplayStyle();
    this.captureSlotTemplates();
    this.__sfSourceCaptured = true;
    this._isMounted = true;
    this.emitComponentEvent("connected");
    this.requestComponentUpdate("connected");
  }

  disconnectedCallback() {
    this._isMounted = false;
    this.onDisconnected();
    this.emitComponentEvent("disconnected");
  }

  toBoolean(value, fallback = false) {
    return toBoolean(value, fallback);
  }

  toAttributeName(key) {
    return toAttributeName(key);
  }

  renderSmartElement(type, props = {}) {
    const tagName = String(type || "").startsWith("sf-") ? String(type) : `sf-${String(type || "")}`;

    if (!/^sf-[a-z0-9-]+$/.test(tagName)) {
      throw new Error(`Invalid SF tag name: ${tagName}`);
    }

    const element = document.createElement(tagName);
    Object.entries(props || {}).forEach(([key, value]) => {
      if (key === ":key" || typeof value === "undefined" || value === null) {
        return;
      }

      if (key === ":ref") {
        if (typeof value === "function") {
          value(element);
        } else if (value && typeof value === "object") {
          value.value = element;
        }

        return;
      }

      if (key.startsWith("@") && typeof value === "function") {
        element.addEventListener(key.slice(1), value);
        return;
      }

      if (/^on[A-Z]/.test(key) && typeof value === "function") {
        element.addEventListener(key.slice(2).toLowerCase(), value);
        return;
      }

      const attributeName = toAttributeName(key);

      if (typeof value === "boolean") {
        element.toggleAttribute(attributeName, value);
        return;
      }

      if (typeof value === "object" || typeof value === "function") {
        element[key] = value;
        return;
      }

      element.setAttribute(attributeName, String(value));
    });
    return element;
  }

  toNumber(value, fallback = 0) {
    return toNumber(value, fallback);
  }

  normalizeEnum(value, allowed, fallback) {
    return normalizeEnum(value, allowed, fallback);
  }

  parseJsonAttribute(name, fallback = null) {
    return parseJsonAttribute(this, name, fallback);
  }

  getBooleanAttr(name, fallback = false) {
    const attr = this.attributeName(name);

    if (!attr || !this.hasAttribute(attr)) {
      return fallback;
    }

    const value = this.getAttribute(attr);

    if (value === "") {
      return true;
    }

    return toBoolean(value, fallback);
  }

  getNumberAttr(name, fallback = 0) {
    const attr = this.attributeName(name);

    if (!attr || !this.hasAttribute(attr)) {
      return fallback;
    }

    const value = this.getAttribute(attr);

    if (value === "") {
      return fallback;
    }

    return toNumber(value, fallback);
  }

  getEnumAttr(name, allowed = [], fallback = "") {
    const attr = this.attributeName(name);
    const value = attr && this.hasAttribute(attr) ? this.getAttribute(attr) : undefined;
    return normalizeEnum(value, allowed, fallback);
  }

  hasDeclaredProps() {
    return Object.keys(this.constructor.props || {}).length > 0;
  }

  createRef() {
    const ref = (0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_1__.createRef)();
    ref.__sfOwner = this;
    return ref;
  }

  registerRefEffect(ref, callback, options = {}) {
    if (!ref || typeof callback !== "function") {
      return this;
    }

    const currentRecord = this._refEffects.get(ref) || {};
    const record = { ...currentRecord,
      callback,
      once: options.once === true
    };

    this._refEffects.set(ref, record);

    this._activeRefEffects?.add(ref);
    return this;
  }

  flushRefEffects() {
    if (!this._refEffects.size) {
      return this;
    }

    const activeRefs = this._activeRefEffects;

    this._refEffects.forEach((record, ref) => {
      if (activeRefs && !activeRefs.has(ref)) {
        if (typeof record.cleanup === "function") {
          record.cleanup();
        }

        this._refEffects.delete(ref);

        return;
      }

      const nextValue = ref?.value || null;

      if (Object.is(record.value, nextValue)) {
        return;
      }

      if (typeof record.cleanup === "function") {
        record.cleanup();
      }

      record.value = nextValue;
      record.cleanup = null;

      if (!nextValue) {
        return;
      }

      const cleanup = record.callback.call(this, nextValue, {
        component: this,
        ref,
        value: nextValue
      });
      record.cleanup = typeof cleanup === "function" ? cleanup : null;

      if (record.once) {
        this._refEffects.delete(ref);
      }
    });

    this._activeRefEffects = null;
    return this;
  }

  getPropsContext(props = this.constructor.props || {}) {
    return Object.fromEntries(Object.entries(props).map(([key, config]) => [key, this.getPropValue(key, config)]));
  }

  getProp(key, fallbackConfig = "") {
    const props = this.constructor.props || {};
    return this.getPropValue(key, props[key] ?? fallbackConfig);
  }

  getPropValue(key, config = "") {
    const propConfig = this.constructor.normalizePropConfig(key, config);
    const {
      attribute,
      type,
      default: defaultValue
    } = propConfig;
    const parser = propConfig.parser || propConfig.parse;
    const hasAttribute = attribute ? this.hasAttribute(attribute) : false;
    const rawValue = hasAttribute ? this.getAttribute(attribute) : undefined;

    if (!hasAttribute && Object.prototype.hasOwnProperty.call(this, key)) {
      return this.coercePropValue(this[key], propConfig);
    }

    if (typeof parser === "function") {
      return parser.call(this, rawValue, defaultValue, this);
    }

    if (!hasAttribute) {
      return this.clonePropDefault(defaultValue, type);
    }

    return this.coercePropValue(rawValue, propConfig);
  }

  coercePropValue(value, config = {}) {
    const {
      type,
      default: defaultValue,
      values
    } = config;

    if (type === Boolean) {
      if (value === "") {
        return true;
      }

      return toBoolean(value, Boolean(defaultValue));
    }

    if (type === Number) {
      return toNumber(value, Number(defaultValue || 0));
    }

    if (type === Array || type === Object) {
      if (typeof value !== "string") {
        return value ?? this.clonePropDefault(defaultValue, type);
      }

      try {
        return JSON.parse(value);
      } catch (error) {
        console.warn(`${this.tagName.toLowerCase()}: invalid ${config.attribute} JSON`, error);
        return this.clonePropDefault(defaultValue, type);
      }
    }

    if (Array.isArray(values)) {
      return normalizeEnum(value, values, defaultValue || values[0] || "");
    }

    return value ?? this.clonePropDefault(defaultValue, type);
  }

  clonePropDefault(defaultValue, type) {
    if (Array.isArray(defaultValue)) {
      return [...defaultValue];
    }

    if (defaultValue && typeof defaultValue === "object" && (type === Object || type === Array)) {
      return { ...defaultValue
      };
    }

    if (typeof defaultValue !== "undefined") {
      return defaultValue;
    }

    if (type === Boolean) {
      return false;
    }

    if (type === Number) {
      return 0;
    }

    if (type === Array) {
      return [];
    }

    if (type === Object) {
      return {};
    }

    return "";
  }

  get value() {
    return this.getAttribute("value") || "";
  }

  set value(nextValue) {
    if (nextValue && typeof nextValue === "object" && !Array.isArray(nextValue)) {
      this.setValueAttributes(nextValue);
      return;
    }

    this.setAttributeValue("value", nextValue);
  }

  getAttributeValue(name = "value", fallback = "") {
    return this.getAttribute(name) || fallback;
  }

  setAttributeValue(name = "value", nextValue = "") {
    const attr = toAttributeName(name);

    if (!attr) {
      return this;
    }

    if (nextValue === null || typeof nextValue === "undefined") {
      this.removeAttribute(attr);
      return this;
    }

    this.setAttribute(attr, String(nextValue));
    return this;
  }

  setValueAttributes(nextValue = {}, options = {}) {
    if (!nextValue || typeof nextValue !== "object") {
      return this;
    }

    const {
      stringifyObjects = true,
      removeEmptyString = true
    } = options;
    Object.entries(nextValue).forEach(([key, value]) => {
      const attr = toAttributeName(key);

      if (!attr) {
        return;
      }

      this.setCustomTemplateProp(key, value);

      if (value === false || value === null || typeof value === "undefined" || removeEmptyString && value === "") {
        this.removeAttribute(attr);
        return;
      }

      if (value === true) {
        this.setAttribute(attr, "");
        return;
      }

      if (stringifyObjects && (Array.isArray(value) || typeof value === "object")) {
        this.setAttribute(attr, JSON.stringify(value));
        return;
      }

      this.setAttribute(attr, String(value));
    });
    return this;
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (oldValue === newValue) {
      return;
    }

    if (name === "style") {
      if (this._syncingHostStyle) {
        return;
      }

      this._hostStyle = this.normalizeHostStyle(newValue);
      this.syncAuthorStyle();
      this.applyHostDisplayStyle();
    }

    this.requestComponentUpdate(name);
  }

  get updateLoopLimit() {
    const limit = Number(this.constructor.updateLoopLimit ?? 50);
    return Number.isFinite(limit) ? limit : 50;
  }

  resetUpdateLoopGuard() {
    this._updateLoopCount = 0;
    this._updateLoopResetScheduled = false;
    this._updateLoopBlocked = false;
    this._updateLoopWarned = false;
  }

  scheduleUpdateLoopGuardReset() {
    if (this._updateLoopResetScheduled) {
      return;
    }

    this._updateLoopResetScheduled = true;
    setTimeout(() => {
      this.resetUpdateLoopGuard();
    }, 0);
  }

  canScheduleComponentUpdate(reason = "unknown") {
    if (this._updateLoopBlocked) {
      return false;
    }

    const limit = this.updateLoopLimit;

    if (limit <= 0) {
      return true;
    }

    this.scheduleUpdateLoopGuardReset();
    this._updateLoopCount += 1;

    if (this._updateLoopCount <= limit) {
      return true;
    }

    this._updateLoopBlocked = true;

    this._changedAttributes.clear();

    if (!this._updateLoopWarned) {
      this._updateLoopWarned = true;
      console.warn(`${this.componentTagName || "sf-element"}: update loop limit reached`, {
        reason,
        limit,
        component: this
      });
    }

    return false;
  }

  requestComponentUpdate(reason = "unknown") {
    if (this._updateLoopBlocked) {
      return;
    }

    if (reason) {
      this._changedAttributes.add(reason);
    }

    if (this._updateScheduled) {
      return;
    }

    if (!this.canScheduleComponentUpdate(reason)) {
      return;
    }

    this._updateScheduled = true;
    Promise.resolve().then(() => {
      this._updateScheduled = false;

      if (!this._isMounted) {
        return;
      }

      const changedAttributes = Array.from(this._changedAttributes);

      this._changedAttributes.clear();

      void this.performComponentUpdate(changedAttributes);
    });
  }

  async performComponentUpdate(changedAttributes = []) {
    const mode = this.resolveUpdateMode(changedAttributes);

    if (mode === "dom" && this.updateDom(changedAttributes) !== false) {
      this.afterUpdate(changedAttributes, mode);
      this.emitComponentEvent("updated", {
        changedAttributes,
        updateMode: mode
      });
      this.emitComponentEvent("props-change", this.createPropChangeDetail(changedAttributes, mode));
      return;
    }

    await this.renderComponent(changedAttributes);
    this.afterUpdate(changedAttributes, "lit");
    this.emitComponentEvent("updated", {
      changedAttributes,
      updateMode: "lit"
    });
    this.emitComponentEvent("props-change", this.createPropChangeDetail(changedAttributes, "lit"));
  }

  async renderComponent(changedAttributes = []) {
    const renderToken = ++this._renderToken;

    if (!this._isMounted) {
      return;
    }

    this._activeRefEffects = new Set();
    this.beforeRender(changedAttributes);
    this.emitComponentEvent("before-render", {
      changedAttributes
    });
    this.runExternalHook("beforeRender", {
      changedAttributes,
      root: this
    });
    const templateResult = await this.resolveTemplateResult(changedAttributes);

    if (!this._isMounted || renderToken !== this._renderToken) {
      return;
    }

    this.prepareRenderContainer(changedAttributes);
    this.markStylePending(this);
    (0,lit__WEBPACK_IMPORTED_MODULE_0__.render)(templateResult, this);
    this.flushRefEffects();
    await this.whenRenderedStylesReady(this);

    if (!this._isMounted || renderToken !== this._renderToken) {
      return;
    }

    this._hasRendered = true;
    this.afterRender(changedAttributes);
    this._childrenDefinedPromise = null;
    this.runExternalHook("afterRender", {
      changedAttributes,
      root: this
    });
    this.emitComponentEvent("after-render", {
      changedAttributes
    });
  }

  shouldWaitForStylesReady() {
    return this.constructor.waitForStylesBeforeRenderReady !== false;
  }

  markStylePending(target = this) {
    if (this.shouldWaitForStylesReady() && !this._styleReady && target?.setAttribute) {
      target.setAttribute("data-sf-style-pending", "");
      target.removeAttribute("data-sf-style-ready");
    }

    return this;
  }

  markStyleReady(target = this) {
    this._styleReady = true;
    [target, this].forEach(element => {
      if (element?.removeAttribute && element?.setAttribute) {
        element.removeAttribute("data-sf-style-pending");
        element.setAttribute("data-sf-style-ready", "");
      }
    });
    return this;
  }

  async whenRenderedStylesReady(target = this) {
    if (!this.shouldWaitForStylesReady()) {
      return;
    }

    const loader = window.SF?.Loader;

    if (!loader || typeof loader.ensureStylesReady !== "function") {
      this.markStyleReady(target);
      return;
    }

    const readiness = Promise.resolve().then(() => loader.ensureStylesReady(target || this, {
      autoLoad: true
    }));
    const timeout = Number(this.constructor.styleReadyTimeout ?? 3000);
    this._styleReadyPromise = timeout > 0 ? Promise.race([readiness, new Promise(resolve => {
      setTimeout(resolve, timeout);
    })]) : readiness;
    await this._styleReadyPromise.catch(error => {
      console.warn(`${this.componentTagName || "sf-element"}: style readiness failed`, error);
    });
    this.markStyleReady(target);
  }

  getChildCustomElements() {
    return Array.from(this.querySelectorAll("*")).filter(node => {
      const tagName = node?.tagName?.toLowerCase?.() || "";
      return tagName.startsWith("sf-");
    });
  }

  async whenChildrenDefined() {
    if (this._childrenDefinedPromise) {
      return this._childrenDefinedPromise;
    }

    this._childrenDefinedPromise = Promise.resolve().then(async () => {
      const children = this.getChildCustomElements();
      await Promise.all(children.map(async child => {
        const tagName = child.tagName?.toLowerCase?.();

        if (!tagName || !window.customElements?.whenDefined) {
          return;
        }

        await window.customElements.whenDefined(tagName);

        if (child.updateComplete?.then) {
          await child.updateComplete;
        }

        if (child._updateScheduled) {
          await Promise.resolve();
        }
      }));
      return children;
    });
    return this._childrenDefinedPromise;
  }

  get componentTagName() {
    return this.tagName?.toLowerCase?.() || "";
  }

  get componentTemplateName() {
    return this.getAttribute("template") || "default";
  }

  get externalTemplateComponentName() {
    return this.componentTagName.startsWith("sf-") ? this.componentTagName.replace(/^sf-/, "") : this.componentTagName;
  }

  get externalTemplateBasePath() {
    return window.SF_SMART_TEMPLATE_PATH || window.SFSmartTemplatePath || this.constructor.externalTemplateBasePath;
  }

  emitComponentEvent(name, detail = {}) {
    const eventDetail = {
      component: this,
      tagName: this.componentTagName,
      template: this.componentTemplateName,
      ...detail
    };
    this.dispatchEvent(new CustomEvent(`sf-${name}`, {
      bubbles: true,
      composed: true,
      detail: eventDetail
    }));
  }

  attributeName(key) {
    return String(key || "").replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`);
  }

  shouldSerializeFalseAttribute(attr) {
    const attributes = this.constructor.defaultTrueAttributes;

    if (!attributes) {
      return false;
    }

    if (attributes instanceof Set) {
      return attributes.has(attr);
    }

    if (Array.isArray(attributes)) {
      return attributes.includes(attr);
    }

    return false;
  }

  setAttributes(nextAttributes = {}) {
    if (!nextAttributes || typeof nextAttributes !== "object") {
      return this;
    }

    Object.entries(nextAttributes).forEach(([key, value]) => {
      const attr = this.attributeName(key);

      if (!attr) {
        return;
      }

      this.setCustomTemplateProp(key, value);

      if (value === false && this.shouldSerializeFalseAttribute(attr)) {
        this.setAttribute(attr, "false");
        return;
      }

      if (value === false || value === null || value === undefined || value === "") {
        this.removeAttribute(attr);
        return;
      }

      if (value === true) {
        this.setAttribute(attr, "");
        return;
      }

      this.setAttribute(attr, String(value));
    });
    return this;
  }

  forwardHostAttributes(target, options = {}) {
    if (!target) {
      return this;
    }

    const {
      exclude = [],
      transferDataAttributes = true,
      storageKey = "__sfForwardedDataAttributes",
      targetStorageKey = "__sfForwardedAttributeKeys"
    } = options;
    const excludeSet = exclude instanceof Set ? exclude : new Set(exclude);
    const dataAttributes = this[storageKey] || new Map();
    const previous = target[targetStorageKey] || new Set();
    const dataAttributeNamesToRemove = [];
    const nextKeys = new Set();
    previous.forEach(name => {
      target.removeAttribute(name);
    });
    Array.from(this.attributes).forEach(attribute => {
      const {
        name,
        value
      } = attribute;

      if (excludeSet.has(name)) {
        return;
      }

      if (/^on[a-z]/i.test(name)) {
        return;
      }

      if (transferDataAttributes && name.startsWith("data-")) {
        dataAttributes.set(name, value);
        dataAttributeNamesToRemove.push(name);
        return;
      }

      nextKeys.add(name);
      target.setAttribute(name, value);
    });
    dataAttributes.forEach((value, name) => {
      nextKeys.add(name);
      target.setAttribute(name, value);
    });
    target[targetStorageKey] = nextKeys;
    this[storageKey] = dataAttributes;

    if (dataAttributeNamesToRemove.length) {
      this.__sfForwardingHostAttributes = true;
      dataAttributeNamesToRemove.forEach(name => this.removeAttribute(name));
      this.__sfForwardingHostAttributes = false;
    }

    return this;
  }

  observeForwardedHostAttributes(targetGetter, options = {}) {
    if (this._forwardHostAttributeObserver) {
      return this;
    }

    const resolveTarget = typeof targetGetter === "function" ? targetGetter : () => targetGetter;
    this._forwardHostAttributeObserver = new MutationObserver(() => {
      if (this.__sfForwardingHostAttributes) {
        return;
      }

      this.forwardHostAttributes(resolveTarget(), options);
    });

    this._forwardHostAttributeObserver.observe(this, {
      attributes: true
    });

    return this;
  }

  disconnectForwardedHostAttributes() {
    this._forwardHostAttributeObserver?.disconnect?.();
    this._forwardHostAttributeObserver = null;
    return this;
  }

  removeAttributes(attributeNames = []) {
    const names = Array.isArray(attributeNames) ? attributeNames : [attributeNames];
    names.filter(Boolean).forEach(name => this.removeAttribute(this.attributeName(name)));
    return this;
  }

  refresh(reason = "manual") {
    this.requestComponentUpdate(reason);
    return this;
  }

  get isRendered() {
    return this._hasRendered === true;
  }

  createRenderDetail(detail = {}) {
    return {
      component: this,
      tagName: this.componentTagName,
      template: this.componentTemplateName,
      ...detail
    };
  }

  runRenderedCallback(callback, detail = {}, event = null) {
    if (typeof callback !== "function") {
      return undefined;
    }

    return callback.call(this, this.createRenderDetail(detail), event);
  }

  whenRendered(callback = null, options = {}) {
    const waitNext = options.next === true;

    if (this.isRendered && !waitNext) {
      const detail = this.createRenderDetail({
        changedAttributes: [],
        immediate: true
      });
      return Promise.resolve().then(() => {
        this.runRenderedCallback(callback, detail);
        return detail;
      });
    }

    return new Promise(resolve => {
      const onAfterRender = event => {
        const detail = event?.detail || this.createRenderDetail();
        this.runRenderedCallback(callback, detail, event);
        resolve(detail);
      };

      this.addEventListener("sf-after-render", onAfterRender, {
        once: true
      });
    });
  }

  onAfterRender(callback, options = {}) {
    const immediate = options.immediate !== false;
    const once = options.once !== false;

    if (immediate && this.isRendered) {
      this.runRenderedCallback(callback, {
        changedAttributes: [],
        immediate: true
      });

      if (once) {
        return () => {};
      }
    }

    const onAfterRender = event => {
      this.runRenderedCallback(callback, event?.detail || {}, event);

      if (once) {
        this.removeEventListener("sf-after-render", onAfterRender);
      }
    };

    this.addEventListener("sf-after-render", onAfterRender);
    return () => this.removeEventListener("sf-after-render", onAfterRender);
  }

  getState() {
    if (typeof this.state !== "undefined") {
      return this.state;
    }

    if (this.hasDeclaredProps()) {
      return this.templateContext();
    }

    if (typeof this.value !== "undefined") {
      return this.value;
    }

    return this.templateContext();
  }

  normalizeStateCallbackArgs(reasonOrCallback, callback, fallbackReason = "state") {
    if (typeof reasonOrCallback === "function") {
      return {
        reason: fallbackReason,
        callback: reasonOrCallback
      };
    }

    return {
      reason: reasonOrCallback || fallbackReason,
      callback
    };
  }

  runStateCallback(callback, detail = {}) {
    if (typeof callback !== "function") {
      return this;
    }

    callback.call(this, {
      component: this,
      state: this.getState(),
      ...detail
    });
    return this;
  }

  scheduleStateCallback(callback, detail = {}) {
    if (typeof callback !== "function") {
      return this;
    }

    if (this._isMounted && this._updateScheduled) {
      this.whenRendered(renderDetail => {
        this.runStateCallback(callback, { ...detail,
          renderDetail
        });
      }, {
        next: true
      });
      return this;
    }

    Promise.resolve().then(() => {
      this.runStateCallback(callback, detail);
    });
    return this;
  }

  setState(nextState = {}, callback = null) {
    this.setAttributes(nextState);
    this.scheduleStateCallback(callback, {
      patch: nextState,
      reason: "attributes"
    });
    return this;
  }

  patchState(nextState = {}, reasonOrCallback = "state", callback = null) {
    const {
      reason,
      callback: stateCallback
    } = this.normalizeStateCallbackArgs(reasonOrCallback, callback, "state");
    const prevState = this.state && typeof this.state === "object" ? this.state : {};
    const patch = typeof nextState === "function" ? nextState(prevState) : nextState;

    if (!patch || typeof patch !== "object" || Object.is(patch, prevState)) {
      this.scheduleStateCallback(stateCallback, {
        patch,
        reason,
        changed: false,
        changedKeys: [],
        prevState,
        nextState: prevState
      });
      return this;
    }

    let changed = false;
    const changedKeys = [];
    const state = { ...prevState
    };
    Object.entries(patch).forEach(([key, value]) => {
      if (Object.is(state[key], value)) {
        return;
      }

      state[key] = value;
      changed = true;
      changedKeys.push(key);
    });

    if (!changed) {
      this.scheduleStateCallback(stateCallback, {
        patch,
        reason,
        changed: false,
        changedKeys,
        prevState,
        nextState: prevState
      });
      return this;
    }

    this.state = state;
    this.requestComponentUpdate(reason);
    this.scheduleStateCallback(stateCallback, {
      patch,
      reason,
      changed: true,
      changedKeys,
      prevState,
      nextState: state
    });
    return this;
  }

  set(nextState = {}, reasonOrCallback = "state", callback = null) {
    return this.patchState(nextState, reasonOrCallback, callback);
  }

  getRootClass() {
    return this.getAttribute("root-class") || "";
  }

  normalizeHostStyle(style = "") {
    const element = document.createElement("div");
    element.setAttribute("style", String(style || ""));
    element.style.removeProperty("display");
    return element.style.cssText;
  } // The host is deliberately boxless; author styles belong to the template
  // root. Keep a real, stable CSSStyleDeclaration for CSSOM edits rather than
  // exposing the internal display-only declaration and losing prior edits.


  get style() {
    if (!this._authorStyleElement) {
      this._authorStyleElement = document.createElement("div");
      this._hostStyle = this.normalizeHostStyle(this.getAttribute("style")) || this._hostStyle;
      this.syncAuthorStyle();
      this._authorStyleObserver = new MutationObserver(() => this.flushAuthorStyle());

      this._authorStyleObserver.observe(this._authorStyleElement, {
        attributes: true,
        attributeFilter: ["style"]
      });
    }

    return this._authorStyleElement.style;
  }

  set style(value) {
    this.style.cssText = value == null ? "" : String(value);
  }

  syncAuthorStyle() {
    if (!this._authorStyleElement) return;
    const next = `${this._hostStyle} display: contents;`.trim();

    if (this._authorStyleElement.style.cssText !== next) {
      this._authorStyleElement.style.cssText = next;
    }
  }

  flushAuthorStyle() {
    if (!this._authorStyleElement) return;
    const next = this.normalizeHostStyle(this._authorStyleElement.style.cssText);

    if (next !== this._hostStyle) {
      // Reuse the existing attribute -> render path. Attribute replacement and
      // removal supersede pending CSSOM edits just as they do for native style.
      this.setAttribute("style", next);
    } else if (this._authorStyleElement.style.display !== "contents") {
      this.syncAuthorStyle();
    }
  }

  captureHostStyle() {
    this.flushAuthorStyle();
    const normalized = this.normalizeHostStyle(this.getAttribute("style"));

    if (normalized || !this._hostStyle) {
      this._hostStyle = normalized;
    }

    this.syncAuthorStyle();
    return this;
  }

  applyHostDisplayStyle() {
    const nextStyle = "display: contents;";

    if (this.getAttribute("style") === nextStyle) {
      return this;
    }

    this._syncingHostStyle = true;
    this.setAttribute("style", nextStyle);
    this._syncingHostStyle = false;
    return this;
  }

  getRootStyle() {
    this.flushAuthorStyle();
    return [this._hostStyle, this.getAttribute("root-style")].filter(Boolean).join("; ");
  }

  setRootClass(nextValue = "") {
    const value = String(nextValue || "").trim();

    if (!value) {
      this.removeAttribute("root-class");
      return this;
    }

    this.setAttribute("root-class", value);
    return this;
  }

  addRootClass(...tokens) {
    const classes = new Set(this.getRootClass().split(/\s+/).filter(Boolean));
    tokens.flat().filter(Boolean).forEach(token => {
      String(token).split(/\s+/).filter(Boolean).forEach(part => classes.add(part));
    });
    return this.setRootClass(Array.from(classes).join(" "));
  }

  setRootStyle(nextValue = "") {
    const value = String(nextValue || "").trim();

    if (!value) {
      this.removeAttribute("root-style");
      return this;
    }

    this.setAttribute("root-style", value);
    return this;
  }

  removeRootClass(...tokens) {
    const classes = new Set(this.getRootClass().split(/\s+/).filter(Boolean));
    tokens.flat().filter(Boolean).forEach(token => {
      String(token).split(/\s+/).filter(Boolean).forEach(part => classes.delete(part));
    });
    return this.setRootClass(Array.from(classes).join(" "));
  }

  toggleRootClass(token, force) {
    const normalized = String(token || "").trim();

    if (!normalized) {
      return this;
    }

    const classes = new Set(this.getRootClass().split(/\s+/).filter(Boolean));
    const shouldAdd = typeof force === "boolean" ? force : !classes.has(normalized);

    if (shouldAdd) {
      classes.add(normalized);
    } else {
      classes.delete(normalized);
    }

    this.setRootClass(Array.from(classes).join(" "));
    return this;
  }

  setHidden(hidden = true) {
    const next = toBoolean(hidden, true);
    this.toggleAttribute("hidden", next);
    this.toggleRootClass("hidden", next);
    return this;
  }

  isHidden() {
    const rootClasses = new Set(this.getRootClass().split(/\s+/).filter(Boolean));
    return this.hasAttribute("hidden") || rootClasses.has("hidden");
  }

  createPropChangeDetail(changedAttributes = [], updateMode = "lit") {
    return {
      changedAttributes,
      updateMode,
      state: this.getState()
    };
  }

  onPropChange(handler, options) {
    if (typeof handler === "function") {
      this.addEventListener("sf-props-change", event => {
        handler(event.detail?.state, event.detail, event);
      }, options);
    }

    return this;
  }

  onUpdate(handler, options) {
    if (typeof handler === "function") {
      this.addEventListener("sf-updated", event => {
        handler(this.getState(), event.detail, event);
      }, options);
    }

    return this;
  }

  createTemplateContext(baseContext = {}) {
    const propsContext = this.hasDeclaredProps() ? this.getPropsContext() : {};
    const normalizedContext = {
      component: this,
      rootClass: this.getRootClass(),
      rootStyle: this.getRootStyle(),
      ...propsContext,
      ...baseContext
    };
    const custom = this.getCustomTemplateProps(normalizedContext);
    return { ...custom,
      ...normalizedContext,
      custom
    };
  }

  getCustomTemplateProps(baseContext = {}) {
    const reservedAttributes = this.getReservedTemplateAttributes();
    const reservedKeys = this.getReservedTemplateKeys(baseContext);
    const props = {};

    this._customTemplateProps.forEach((value, key) => {
      if (!this.isCustomTemplateProp(key, baseContext)) {
        return;
      }

      props[key] = value;
    });

    Object.entries(this.getOwnCustomTemplateProps(baseContext)).forEach(([key, value]) => {
      if (!key || reservedKeys.has(key) || Object.prototype.hasOwnProperty.call(props, key)) {
        return;
      }

      props[key] = value;
    });
    Array.from(this.attributes || []).forEach(({
      name,
      value
    }) => {
      if (reservedAttributes.has(name)) {
        return;
      }

      const key = this.attributeToPropertyName(name);

      if (!key || reservedKeys.has(key) || Object.prototype.hasOwnProperty.call(props, key)) {
        return;
      }

      props[key] = value;
    });
    return props;
  }

  getOwnCustomTemplateProps(baseContext = {}) {
    const props = {};
    const reservedKeys = this.getReservedTemplateKeys(baseContext);
    Object.keys(this).forEach(key => {
      if (key.startsWith("_") || reservedKeys.has(key) || typeof this[key] === "function") {
        return;
      }

      props[key] = this[key];
    });
    return props;
  }

  getReservedTemplateAttributes() {
    const reservedAttributes = new Set(["class", "style", "id", "slot", "template"]);
    const observedAttributes = this.constructor.observedAttributes || [];
    observedAttributes.forEach(name => {
      const attr = toAttributeName(name);

      if (attr) {
        reservedAttributes.add(attr);
      }
    });
    return reservedAttributes;
  }

  getReservedTemplateKeys(baseContext = {}) {
    const reservedKeys = new Set(["custom"]);
    Object.keys(baseContext || {}).forEach(key => {
      reservedKeys.add(key);
    });
    this.getReservedTemplateAttributes().forEach(attr => {
      const key = this.attributeToPropertyName(attr);

      if (key) {
        reservedKeys.add(key);
      }
    });
    return reservedKeys;
  }

  isCustomTemplateProp(key, baseContext = {}) {
    const prop = this.attributeToPropertyName(toAttributeName(key));

    if (!prop) {
      return false;
    }

    const attr = toAttributeName(prop);
    const reservedAttributes = this.getReservedTemplateAttributes();
    const reservedKeys = this.getReservedTemplateKeys(baseContext);
    return !reservedAttributes.has(attr) && !reservedKeys.has(prop);
  }

  setCustomTemplateProps(nextProps = {}, baseContext = {}) {
    if (!nextProps || typeof nextProps !== "object") {
      return this;
    }

    Object.entries(nextProps).forEach(([key, value]) => {
      this.setCustomTemplateProp(key, value, baseContext);
    });
    return this;
  }

  setCustomTemplateProp(key, value, baseContext = {}) {
    const prop = this.attributeToPropertyName(toAttributeName(key));

    if (!this.isCustomTemplateProp(prop, baseContext)) {
      return this;
    }

    if (value === false || value === null || typeof value === "undefined") {
      if (this._customTemplateProps.delete(prop)) {
        this.requestComponentUpdate(prop);
      }

      return this;
    }

    if (this._customTemplateProps.get(prop) === value) {
      return this;
    }

    this._customTemplateProps.set(prop, value);

    this.requestComponentUpdate(prop);
    return this;
  }

  attributeToPropertyName(name) {
    return String(name || "").replace(/-([a-z0-9])/g, (_, symbol) => symbol.toUpperCase());
  }

  resolveUpdateMode() {
    return "lit";
  }

  createPropsTemplateContext(extraContext = {}) {
    return this.createTemplateContext({
      component: this,
      ...this.getPropsContext(),
      ...extraContext
    });
  }

  templateContext() {
    return this.createPropsTemplateContext();
  }

  captureSlotTemplates() {
    if (this._slotTemplatesCaptured) {
      return;
    }

    this._slotTemplatesCaptured = true;
    Array.from(this.children).forEach(child => {
      if (!(child instanceof HTMLElement)) {
        return;
      }

      const slotName = child.getAttribute("slot");

      if (!slotName) {
        return;
      }

      if (child.tagName?.toLowerCase?.() === "template") {
        if (!this._slotTemplates.has(slotName)) {
          this._slotTemplates.set(slotName, []);
        }

        Array.from(child.content?.childNodes || []).forEach(node => {
          this._slotTemplates.get(slotName).push(node.cloneNode(true));
        });
        child.remove();
        return;
      }

      if (!this._liveSlotNodes.has(slotName)) {
        this._liveSlotNodes.set(slotName, []);
      }

      const shouldUnwrapSlotHost = child.getAttributeNames().length === 1 && child.hasAttribute("slot");
      const slotNodes = shouldUnwrapSlotHost ? Array.from(child.childNodes) : [child];

      this._liveSlotNodes.get(slotName).push(...slotNodes);

      child.remove();
    });
  }

  captureChildTemplates(name, matcher, options = {}) {
    if (!name) {
      return [];
    }

    const {
      append = true,
      remove = true
    } = options;
    const matches = typeof matcher === "function" ? matcher : node => node instanceof Element && typeof matcher === "string" && node.matches?.(matcher);
    const nodes = Array.from(this.childNodes || []).filter(child => matches(child));

    if (!nodes.length) {
      return [];
    }

    if (!append || !this._slotTemplates.has(name)) {
      this._slotTemplates.set(name, []);
    }

    const templates = nodes.map(node => node.cloneNode(true));
    templates.forEach(node => {
      this._slotTemplates.get(name).push(node);
    });

    if (remove) {
      nodes.forEach(node => node.remove());
    }

    return templates.map(node => node.cloneNode(true));
  }

  hasSlotContent(name) {
    if ((this._liveSlotNodes.get(name) || []).length > 0) {
      return true;
    }

    return (this._slotTemplates.get(name) || []).length > 0;
  }

  getSlotContent(name) {
    const liveSlotContent = this._liveSlotNodes.get(name) || [];

    if (liveSlotContent.length) {
      return liveSlotContent.length === 1 ? liveSlotContent[0] : liveSlotContent;
    }

    const slotContent = this._slotTemplates.get(name) || [];

    if (!slotContent.length) {
      return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
    }

    if (slotContent.length === 1) {
      return slotContent[0].cloneNode(true);
    }

    return slotContent.map(node => node.cloneNode(true));
  }

  setSlot(name, ...elements) {
    if (!name) {
      return this;
    } // Преобразуем строки в DOM-элементы


    const parsed = elements.map(el => {
      if (typeof el === "string") {
        const wrapper = document.createElement("div");
        wrapper.innerHTML = el;
        return Array.from(wrapper.childNodes);
      }

      return [el];
    }).flat().filter(node => node instanceof Node); // Обновляем _slotTemplates (Lit-рендеринг)

    this._slotTemplates.delete(name); // Обновляем _liveSlotNodes (если есть — для совместимости с sf-modal)


    this._liveSlotNodes.set(name, parsed); // Запрашиваем перерендер


    this.requestComponentUpdate("slot-change");
    return this;
  }

  copyRuntimeData(source, target) {
    if (!(source instanceof Node) || !(target instanceof Node)) {
      return;
    }

    if (source.__sfCreateEventHandlers) {
      Object.defineProperty(target, "__sfCreateEventHandlers", {
        configurable: true,
        enumerable: false,
        value: source.__sfCreateEventHandlers.slice()
      });
    }

    const sourceChildren = Array.from(source.childNodes || []);
    const targetChildren = Array.from(target.childNodes || []);
    sourceChildren.forEach((sourceChild, index) => {
      this.copyRuntimeData(sourceChild, targetChildren[index]);
    });
  }

  clearSlot(name) {
    if (!name) {
      return this;
    }

    this._slotTemplates.delete(name);

    if (this._liveSlotNodes) {
      this._liveSlotNodes.delete(name);
    }

    this.requestComponentUpdate("slot-change");
    return this;
  }

  async resolveTemplateResult(changedAttributes = []) {
    const templateName = this.componentTemplateName;

    if (this.hasBuiltInTemplate(templateName)) {
      this._externalTemplateModule = null;
      return this.template();
    }

    const externalModule = await this.resolveExternalTemplateModule(templateName);
    this._externalTemplateModule = externalModule || null;

    if (externalModule) {
      const rawContext = this.templateContext();
      const context = this.createTemplateContext(this.mapExternalTemplateContext(rawContext, externalModule));
      const renderFn = typeof externalModule.default === "function" ? externalModule.default : null;

      if (renderFn) {
        return renderFn({
          html: lit__WEBPACK_IMPORTED_MODULE_0__.html,
          nothing: lit__WEBPACK_IMPORTED_MODULE_0__.nothing,
          context,
          component: this,
          changedAttributes
        });
      }
    }

    this._externalTemplateModule = null;
    return this.template();
  }

  hasBuiltInTemplate(templateName = this.componentTemplateName) {
    return !templateName || templateName === "default";
  }

  mapExternalTemplateContext(context, externalModule) {
    if (typeof externalModule?.mapContext === "function") {
      return externalModule.mapContext({
        context,
        component: this,
        html: lit__WEBPACK_IMPORTED_MODULE_0__.html,
        nothing: lit__WEBPACK_IMPORTED_MODULE_0__.nothing
      }) || context;
    }

    return context;
  }

  async resolveExternalTemplateModule(templateName = this.componentTemplateName) {
    if (!templateName) {
      return null;
    }

    const moduleUrl = this.getExternalTemplateModuleUrl(templateName);

    if (!moduleUrl) {
      return null;
    }

    await this.loadExternalTemplateCss(templateName);

    if (this.constructor.externalTemplateModules.has(moduleUrl)) {
      return this.constructor.externalTemplateModules.get(moduleUrl);
    }

    if (this.constructor.externalTemplateMisses.has(moduleUrl)) {
      return null;
    }

    const exists = await this.checkExternalTemplateModule(moduleUrl);

    if (!exists) {
      this.constructor.externalTemplateMisses.add(moduleUrl);
      return null;
    }

    try {
      const externalModule = await import(
      /* webpackIgnore: true */
      moduleUrl);
      this.constructor.externalTemplateModules.set(moduleUrl, externalModule);
      return externalModule;
    } catch (error) {
      console.warn(error);
      this.constructor.externalTemplateMisses.add(moduleUrl);
      return null;
    }
  }

  async checkExternalTemplateModule(moduleUrl) {
    if (!moduleUrl) {
      return false;
    }

    if (this.constructor.externalTemplateChecks.has(moduleUrl)) {
      return this.constructor.externalTemplateChecks.get(moduleUrl);
    }

    const checkPromise = fetch(moduleUrl, {
      method: "GET",
      cache: "no-store"
    }).then(response => response.ok).catch(() => false);
    this.constructor.externalTemplateChecks.set(moduleUrl, checkPromise);
    const exists = await checkPromise;

    if (!exists) {
      this.constructor.externalTemplateChecks.delete(moduleUrl);
    }

    return exists;
  }

  getExternalTemplateModuleUrl(templateName = this.componentTemplateName) {
    const basePath = String(this.externalTemplateBasePath || "").replace(/\/$/, "");
    const componentName = this.externalTemplateComponentName;

    if (!basePath || !componentName || !templateName) {
      return "";
    }

    return `${basePath}/${componentName}/${templateName}/index.js`;
  }

  getExternalTemplateCssUrl(templateName = this.componentTemplateName) {
    const basePath = String(this.externalTemplateBasePath || "").replace(/\/$/, "");
    const componentName = this.externalTemplateComponentName;

    if (!basePath || !componentName || !templateName) {
      return "";
    }

    return `${basePath}/${componentName}/${templateName}/index.css`;
  }

  loadExternalTemplateCss(templateName = this.componentTemplateName) {
    const cssUrl = this.getExternalTemplateCssUrl(templateName);

    if (!cssUrl) {
      return Promise.resolve(false);
    }

    if (this.constructor.externalTemplateCssLoaded.has(cssUrl)) {
      return Promise.resolve(true);
    }

    if (this.constructor.externalTemplateCssMisses.has(cssUrl)) {
      return Promise.resolve(false);
    }

    const existing = document.querySelector(`link[data-sf-smart-css="${cssUrl}"]`);

    if (existing) {
      this.constructor.externalTemplateCssLoaded.add(cssUrl);
      return Promise.resolve(true);
    }

    return new Promise(resolve => {
      const link = document.createElement("link");
      link.rel = "stylesheet";
      link.href = cssUrl;
      link.dataset.sfSmartCss = cssUrl;

      link.onload = () => {
        this.constructor.externalTemplateCssLoaded.add(cssUrl);
        resolve(true);
      };

      link.onerror = () => {
        this.constructor.externalTemplateCssMisses.add(cssUrl);
        resolve(false);
      };

      document.head.append(link);
    });
  }

  runExternalHook(hookName, detail = {}) {
    if (typeof this._externalTemplateModule?.[hookName] !== "function") {
      return;
    }

    try {
      this._externalTemplateModule[hookName]({
        component: this,
        root: this,
        html: lit__WEBPACK_IMPORTED_MODULE_0__.html,
        nothing: lit__WEBPACK_IMPORTED_MODULE_0__.nothing,
        context: this.templateContext(),
        ...detail
      });
    } catch (error) {
      console.warn(error);
    }
  }

  updateDom() {
    return false;
  }

  shouldClearLightDomBeforeFirstRender() {
    return this.constructor.clearLightDomBeforeFirstRender === true;
  }

  prepareRenderContainer() {
    if (this._hasRendered || !this.shouldClearLightDomBeforeFirstRender()) {
      return;
    }

    this.replaceChildren();
  }

  template() {
    return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
  }

  beforeRender() {}

  afterRender(callback) {
    if (typeof callback === "function") {
      return this.whenRendered(callback);
    }

    return undefined;
  }

  afterUpdate() {}

  onDisconnected() {
    this.runExternalHook("destroy");
  }

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SfBaseElement); // Global access for Playground, console, and project-level components.

if (typeof window !== "undefined") {
  window.SfBaseElement = SfBaseElement;
  window.html = lit__WEBPACK_IMPORTED_MODULE_0__.html;
  window.nothing = lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
  window.render = lit__WEBPACK_IMPORTED_MODULE_0__.render;

  if (!window.SF) {
    window.SF = {};
  }

  window.SF.SfBaseElement = SfBaseElement;
  window.SF.html = lit__WEBPACK_IMPORTED_MODULE_0__.html;
  window.SF.nothing = lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
  window.SF.render = lit__WEBPACK_IMPORTED_MODULE_0__.render;
  window.SF.smart = { ...(window.SF.smart || {}),
    SfBaseElement,
    html: lit__WEBPACK_IMPORTED_MODULE_0__.html,
    nothing: lit__WEBPACK_IMPORTED_MODULE_0__.nothing,
    render: lit__WEBPACK_IMPORTED_MODULE_0__.render,
    toBoolean,
    toAttributeName,
    toNumber,
    normalizeEnum,
    parseJsonAttribute
  };
}

/***/ },

/***/ "77b2f4107e3b"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   renderDatepickerTemplate: () => (/* binding */ renderDatepickerTemplate)
/* harmony export */ });
/* harmony import */ var lit__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("fef8077ac919");
/* harmony import */ var lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("7fcbcc00731e");



function joinClasses(...tokens) {
  return tokens.flat().filter(Boolean).join(' ');
}

function renderYearTop(context) {
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <div class="sf-datepicker-top flex content-main-between items-cross-center">
            <sf-icon-button type="link" scheme="on-surface" size="1"
                icon=${context.previousIcon} aria-label=${context.labels.previousYears}
                @click=${() => context.component.shiftYearPage(-1)}></sf-icon-button>
            <div class="sf-datepicker-range-title" aria-live="polite">
                ${context.yearPageStart}–${context.yearPageEnd}
            </div>
            <sf-icon-button type="link" scheme="on-surface" size="1"
                icon=${context.nextIcon} aria-label=${context.labels.nextYears}
                @click=${() => context.component.shiftYearPage(1)}></sf-icon-button>
        </div>`;
}

function renderTop(context) {
  if (context.context === 'year') return renderYearTop(context);
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <div class="sf-datepicker-top grid items-cross-center">
            <sf-icon-button type="link" scheme="on-surface" size="1"
                icon=${context.previousIcon} aria-label=${context.labels.previousMonth}
                @click=${() => context.component.shiftMonth(-1)}></sf-icon-button>
            <div class="sf-datepicker-period flex content-main-center items-cross-center gap-1/4">
                <sf-button class="sf-datepicker-month-toggle" type="link" scheme="on-surface" tightness="low"
                    text=${context.monthLabel}
                    aria-expanded=${context.context === 'month' ? 'true' : 'false'}
                    @click=${() => context.component.toggleContext('month')}></sf-button>
                <sf-button class="sf-datepicker-year-toggle" type="link" scheme="on-surface" tightness="low"
                    text=${String(context.year)}
                    aria-expanded="false"
                    @click=${() => context.component.toggleContext('year')}></sf-button>
            </div>
            <sf-icon-button type="link" scheme="on-surface" size="1"
                icon=${context.nextIcon} aria-label=${context.labels.nextMonth}
                @click=${() => context.component.shiftMonth(1)}></sf-icon-button>
        </div>`;
}

function renderMonthContext(context) {
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <div class="sf-datepicker-context absolute top-0 bottom-0 inline-start-0 inline-end-0 grid grid-col-3 content-cross-start items-cross-start overflow-auto gap-1/4"
             aria-label=${context.labels.chooseMonth}>
            ${context.months.map(month => (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                <button class=${joinClasses('sf-datepicker-text flex content-main-center items-cross-center', month.selected ? 'active' : '', month.disabled ? 'disabled' : '')}
                    ?disabled=${month.disabled} aria-pressed=${month.selected ? 'true' : 'false'}
                    type="button" @click=${() => context.component.selectMonth(month)}>
                    <span class="sf-datepicker-text-date">${month.label}</span>
                </button>`)}
        </div>`;
}

function renderYearContext(context) {
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <div class="sf-datepicker-context absolute top-0 bottom-0 inline-start-0 inline-end-0 grid grid-col-3 content-cross-start items-cross-start overflow-auto gap-1/4"
             aria-label=${context.labels.chooseYear}>
            ${context.years.map(year => (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                <button class=${joinClasses('sf-datepicker-text flex content-main-center items-cross-center', year.selected ? 'active' : '', year.disabled ? 'disabled' : '')}
                    type="button" ?disabled=${year.disabled} aria-pressed=${year.selected ? 'true' : 'false'}
                    @click=${() => context.component.selectYear(year)}>
                    <span class="sf-datepicker-text-date">${year.year}</span>
                </button>`)}
        </div>`;
}

function renderContext(context) {
  if (context.context === 'month') return renderMonthContext(context);
  if (context.context === 'year') return renderYearContext(context);
  return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
}

function renderDatepickerInput(context) {
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <sf-input class="sf-datepicker-input" ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_1__.ref)(context.component.refs.input)}
            type=${context.inputType} size=${context.inputSize}
            label=${context.label || lit__WEBPACK_IMPORTED_MODULE_0__.nothing} name=${context.inputName || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
            placeholder=${context.placeholder || lit__WEBPACK_IMPORTED_MODULE_0__.nothing} left-icon=${context.leftIcon || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
            root-class=${context.inputRootClass || lit__WEBPACK_IMPORTED_MODULE_0__.nothing} value=${context.displayValue || ''}
            ?disabled=${context.disabled} ?required=${context.required} ?mask=${context.mask}
            mask-pattern=${context.maskPattern || lit__WEBPACK_IMPORTED_MODULE_0__.nothing} mask-lazy=${String(context.maskLazy)}
            mask-placeholder-char=${context.maskPlaceholderChar || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
            mask-options=${context.maskOptions || lit__WEBPACK_IMPORTED_MODULE_0__.nothing} readonly
            @click=${event => context.component.handleInputClick(event)}
            @keydown=${event => context.component.handleInputKeydown(event)}></sf-input>`;
}

function renderDay(day, context) {
  const classes = joinClasses('sf-datepicker-day', day.weekend ? 'sf-datepicker-day-off' : '', day.selected ? 'active' : '', day.rangeStart ? 'sf-datepicker-day-start' : '', day.rangeSelected ? 'sf-datepicker-day-selected' : '', day.rangeEnd ? 'sf-datepicker-day-end' : '', day.currentMonth ? '' : 'sf-datepicker-another-month', day.disabled ? 'disabled' : '');
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <button class=${classes} type="button" ?disabled=${day.disabled}
            data-date=${day.value} aria-label=${day.label}
            aria-current=${day.today ? 'date' : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
            aria-pressed=${day.selected ? 'true' : 'false'} tabindex=${day.tabIndex}
            @click=${() => context.component.selectDate(day)}
            @focus=${() => context.component.setActiveDate(day.value)}
            @keydown=${event => context.component.handleDayKeydown(event, day)}>
            <span class="sf-datepicker-day-date">${day.day}</span>
        </button>`;
}

function renderDatepickerPanel(context) {
  const rootClasses = joinClasses('sf-datepicker inline-flex', context.rootClass);
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <div ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_1__.ref)(context.component.refs.datePickerPanel)} id=${context.panelId}
            class=${rootClasses} style=${context.rootStyle || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
            role=${context.input ? 'dialog' : 'group'} aria-label=${context.calendarLabel}
            @click=${event => context.component.handlePanelClick(event)}>
            <div class="sf-datepicker-container flex flex-col">
                ${renderTop(context)}
                <div class="sf-datepicker-weekdays grid grid-col-7" aria-hidden="true">
                    ${context.weekdays.map(weekday => (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`<span class="sf-datepicker-weekday">${weekday}</span>`)}
                </div>
                <div class="sf-datepicker-main relative">
                    ${renderContext(context)}
                    <div class="sf-datepicker-grid grid grid-col-7">
                        ${context.days.map(day => renderDay(day, context))}
                    </div>
                </div>
            </div>
        </div>`;
}

function renderDatepickerTemplate(context) {
  if (!context.input) return renderDatepickerPanel(context);
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <div class=${joinClasses('sf-datepicker-wrap inline-flex flex-col relative', context.open ? 'is-open' : '', context.openAbove ? 'sf-datepicker-wrap--drop-up' : 'sf-datepicker-wrap--drop-down')}
            ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_1__.ref)(context.component.refs.datePickerRoot)} data-portal=${context.portal ? 'true' : 'false'}>
            ${renderDatepickerInput(context)}
            ${context.open ? renderDatepickerPanel(context) : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
        </div>`;
}

/***/ },

/***/ "aa42666a7938"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CSSResult: () => (/* binding */ n),
/* harmony export */   adoptStyles: () => (/* binding */ S),
/* harmony export */   css: () => (/* binding */ i),
/* harmony export */   getCompatibleStyle: () => (/* binding */ c),
/* harmony export */   supportsAdoptingStyleSheets: () => (/* binding */ e),
/* harmony export */   unsafeCSS: () => (/* binding */ r)
/* harmony export */ });
/**
 * @license
 * Copyright 2019 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
const t=globalThis,e=t.ShadowRoot&&(void 0===t.ShadyCSS||t.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,s=Symbol(),o=new WeakMap;class n{constructor(t,e,o){if(this._$cssResult$=!0,o!==s)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=t,this.t=e}get styleSheet(){let t=this.o;const s=this.t;if(e&&void 0===t){const e=void 0!==s&&1===s.length;e&&(t=o.get(s)),void 0===t&&((this.o=t=new CSSStyleSheet).replaceSync(this.cssText),e&&o.set(s,t))}return t}toString(){return this.cssText}}const r=t=>new n("string"==typeof t?t:t+"",void 0,s),i=(t,...e)=>{const o=1===t.length?t[0]:e.reduce((e,s,o)=>e+(t=>{if(!0===t._$cssResult$)return t.cssText;if("number"==typeof t)return t;throw Error("Value passed to 'css' function must be a 'css' function result: "+t+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(s)+t[o+1],t[0]);return new n(o,t,s)},S=(s,o)=>{if(e)s.adoptedStyleSheets=o.map(t=>t instanceof CSSStyleSheet?t:t.styleSheet);else for(const e of o){const o=document.createElement("style"),n=t.litNonce;void 0!==n&&o.setAttribute("nonce",n),o.textContent=e.cssText,s.appendChild(o)}},c=e?t=>t:t=>t instanceof CSSStyleSheet?(t=>{let e="";for(const s of t.cssRules)e+=s.cssText;return r(e)})(t):t;
//# sourceMappingURL=css-tag.js.map


/***/ },

/***/ "ea86f8429d07"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CSSResult: () => (/* reexport safe */ _css_tag_js__WEBPACK_IMPORTED_MODULE_0__.CSSResult),
/* harmony export */   ReactiveElement: () => (/* binding */ y),
/* harmony export */   adoptStyles: () => (/* reexport safe */ _css_tag_js__WEBPACK_IMPORTED_MODULE_0__.adoptStyles),
/* harmony export */   css: () => (/* reexport safe */ _css_tag_js__WEBPACK_IMPORTED_MODULE_0__.css),
/* harmony export */   defaultConverter: () => (/* binding */ u),
/* harmony export */   getCompatibleStyle: () => (/* reexport safe */ _css_tag_js__WEBPACK_IMPORTED_MODULE_0__.getCompatibleStyle),
/* harmony export */   notEqual: () => (/* binding */ f),
/* harmony export */   supportsAdoptingStyleSheets: () => (/* reexport safe */ _css_tag_js__WEBPACK_IMPORTED_MODULE_0__.supportsAdoptingStyleSheets),
/* harmony export */   unsafeCSS: () => (/* reexport safe */ _css_tag_js__WEBPACK_IMPORTED_MODULE_0__.unsafeCSS)
/* harmony export */ });
/* harmony import */ var _css_tag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("aa42666a7938");

/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const{is:i,defineProperty:e,getOwnPropertyDescriptor:h,getOwnPropertyNames:r,getOwnPropertySymbols:o,getPrototypeOf:n}=Object,a=globalThis,c=a.trustedTypes,l=c?c.emptyScript:"",p=a.reactiveElementPolyfillSupport,d=(t,s)=>t,u={toAttribute(t,s){switch(s){case Boolean:t=t?l:null;break;case Object:case Array:t=null==t?t:JSON.stringify(t)}return t},fromAttribute(t,s){let i=t;switch(s){case Boolean:i=null!==t;break;case Number:i=null===t?null:Number(t);break;case Object:case Array:try{i=JSON.parse(t)}catch(t){i=null}}return i}},f=(t,s)=>!i(t,s),b={attribute:!0,type:String,converter:u,reflect:!1,useDefault:!1,hasChanged:f};Symbol.metadata??=Symbol("metadata"),a.litPropertyMetadata??=new WeakMap;class y extends HTMLElement{static addInitializer(t){this._$Ei(),(this.l??=[]).push(t)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(t,s=b){if(s.state&&(s.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(t)&&((s=Object.create(s)).wrapped=!0),this.elementProperties.set(t,s),!s.noAccessor){const i=Symbol(),h=this.getPropertyDescriptor(t,i,s);void 0!==h&&e(this.prototype,t,h)}}static getPropertyDescriptor(t,s,i){const{get:e,set:r}=h(this.prototype,t)??{get(){return this[s]},set(t){this[s]=t}};return{get:e,set(s){const h=e?.call(this);r?.call(this,s),this.requestUpdate(t,h,i)},configurable:!0,enumerable:!0}}static getPropertyOptions(t){return this.elementProperties.get(t)??b}static _$Ei(){if(this.hasOwnProperty(d("elementProperties")))return;const t=n(this);t.finalize(),void 0!==t.l&&(this.l=[...t.l]),this.elementProperties=new Map(t.elementProperties)}static finalize(){if(this.hasOwnProperty(d("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(d("properties"))){const t=this.properties,s=[...r(t),...o(t)];for(const i of s)this.createProperty(i,t[i])}const t=this[Symbol.metadata];if(null!==t){const s=litPropertyMetadata.get(t);if(void 0!==s)for(const[t,i]of s)this.elementProperties.set(t,i)}this._$Eh=new Map;for(const[t,s]of this.elementProperties){const i=this._$Eu(t,s);void 0!==i&&this._$Eh.set(i,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(s){const i=[];if(Array.isArray(s)){const e=new Set(s.flat(1/0).reverse());for(const s of e)i.unshift((0,_css_tag_js__WEBPACK_IMPORTED_MODULE_0__.getCompatibleStyle)(s))}else void 0!==s&&i.push((0,_css_tag_js__WEBPACK_IMPORTED_MODULE_0__.getCompatibleStyle)(s));return i}static _$Eu(t,s){const i=s.attribute;return!1===i?void 0:"string"==typeof i?i:"string"==typeof t?t.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise(t=>this.enableUpdating=t),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach(t=>t(this))}addController(t){(this._$EO??=new Set).add(t),void 0!==this.renderRoot&&this.isConnected&&t.hostConnected?.()}removeController(t){this._$EO?.delete(t)}_$E_(){const t=new Map,s=this.constructor.elementProperties;for(const i of s.keys())this.hasOwnProperty(i)&&(t.set(i,this[i]),delete this[i]);t.size>0&&(this._$Ep=t)}createRenderRoot(){const t=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return (0,_css_tag_js__WEBPACK_IMPORTED_MODULE_0__.adoptStyles)(t,this.constructor.elementStyles),t}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach(t=>t.hostConnected?.())}enableUpdating(t){}disconnectedCallback(){this._$EO?.forEach(t=>t.hostDisconnected?.())}attributeChangedCallback(t,s,i){this._$AK(t,i)}_$ET(t,s){const i=this.constructor.elementProperties.get(t),e=this.constructor._$Eu(t,i);if(void 0!==e&&!0===i.reflect){const h=(void 0!==i.converter?.toAttribute?i.converter:u).toAttribute(s,i.type);this._$Em=t,null==h?this.removeAttribute(e):this.setAttribute(e,h),this._$Em=null}}_$AK(t,s){const i=this.constructor,e=i._$Eh.get(t);if(void 0!==e&&this._$Em!==e){const t=i.getPropertyOptions(e),h="function"==typeof t.converter?{fromAttribute:t.converter}:void 0!==t.converter?.fromAttribute?t.converter:u;this._$Em=e;const r=h.fromAttribute(s,t.type);this[e]=r??this._$Ej?.get(e)??r,this._$Em=null}}requestUpdate(t,s,i,e=!1,h){if(void 0!==t){const r=this.constructor;if(!1===e&&(h=this[t]),i??=r.getPropertyOptions(t),!((i.hasChanged??f)(h,s)||i.useDefault&&i.reflect&&h===this._$Ej?.get(t)&&!this.hasAttribute(r._$Eu(t,i))))return;this.C(t,s,i)}!1===this.isUpdatePending&&(this._$ES=this._$EP())}C(t,s,{useDefault:i,reflect:e,wrapped:h},r){i&&!(this._$Ej??=new Map).has(t)&&(this._$Ej.set(t,r??s??this[t]),!0!==h||void 0!==r)||(this._$AL.has(t)||(this.hasUpdated||i||(s=void 0),this._$AL.set(t,s)),!0===e&&this._$Em!==t&&(this._$Eq??=new Set).add(t))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const t=this.scheduleUpdate();return null!=t&&await t,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[t,s]of this._$Ep)this[t]=s;this._$Ep=void 0}const t=this.constructor.elementProperties;if(t.size>0)for(const[s,i]of t){const{wrapped:t}=i,e=this[s];!0!==t||this._$AL.has(s)||void 0===e||this.C(s,void 0,i,e)}}let t=!1;const s=this._$AL;try{t=this.shouldUpdate(s),t?(this.willUpdate(s),this._$EO?.forEach(t=>t.hostUpdate?.()),this.update(s)):this._$EM()}catch(s){throw t=!1,this._$EM(),s}t&&this._$AE(s)}willUpdate(t){}_$AE(t){this._$EO?.forEach(t=>t.hostUpdated?.()),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(t)),this.updated(t)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(t){return!0}update(t){this._$Eq&&=this._$Eq.forEach(t=>this._$ET(t,this[t])),this._$EM()}updated(t){}firstUpdated(t){}}y.elementStyles=[],y.shadowRootOptions={mode:"open"},y[d("elementProperties")]=new Map,y[d("finalized")]=new Map,p?.({ReactiveElement:y}),(a.reactiveElementVersions??=[]).push("2.1.2");
//# sourceMappingURL=reactive-element.js.map


/***/ },

/***/ "7ab7aedf3cfc"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CSSResult: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.CSSResult),
/* harmony export */   LitElement: () => (/* binding */ i),
/* harmony export */   ReactiveElement: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.ReactiveElement),
/* harmony export */   _$LE: () => (/* binding */ n),
/* harmony export */   _$LH: () => (/* reexport safe */ lit_html__WEBPACK_IMPORTED_MODULE_1__._$LH),
/* harmony export */   adoptStyles: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.adoptStyles),
/* harmony export */   css: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.css),
/* harmony export */   defaultConverter: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.defaultConverter),
/* harmony export */   getCompatibleStyle: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.getCompatibleStyle),
/* harmony export */   html: () => (/* reexport safe */ lit_html__WEBPACK_IMPORTED_MODULE_1__.html),
/* harmony export */   mathml: () => (/* reexport safe */ lit_html__WEBPACK_IMPORTED_MODULE_1__.mathml),
/* harmony export */   noChange: () => (/* reexport safe */ lit_html__WEBPACK_IMPORTED_MODULE_1__.noChange),
/* harmony export */   notEqual: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.notEqual),
/* harmony export */   nothing: () => (/* reexport safe */ lit_html__WEBPACK_IMPORTED_MODULE_1__.nothing),
/* harmony export */   render: () => (/* reexport safe */ lit_html__WEBPACK_IMPORTED_MODULE_1__.render),
/* harmony export */   supportsAdoptingStyleSheets: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.supportsAdoptingStyleSheets),
/* harmony export */   svg: () => (/* reexport safe */ lit_html__WEBPACK_IMPORTED_MODULE_1__.svg),
/* harmony export */   unsafeCSS: () => (/* reexport safe */ _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.unsafeCSS)
/* harmony export */ });
/* harmony import */ var _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("ea86f8429d07");
/* harmony import */ var lit_html__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("f550d360fd36");

/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const s=globalThis;class i extends _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__.ReactiveElement{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const t=super.createRenderRoot();return this.renderOptions.renderBefore??=t.firstChild,t}update(t){const r=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(t),this._$Do=(0,lit_html__WEBPACK_IMPORTED_MODULE_1__.render)(r,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return lit_html__WEBPACK_IMPORTED_MODULE_1__.noChange}}i._$litElement$=!0,i["finalized"]=!0,s.litElementHydrateSupport?.({LitElement:i});const o=s.litElementPolyfillSupport;o?.({LitElement:i});const n={_$AK:(t,e,r)=>{t._$AK(e,r)},_$AL:t=>t._$AL};(s.litElementVersions??=[]).push("4.2.2");
//# sourceMappingURL=lit-element.js.map


/***/ },

/***/ "f6a1e423f201"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AsyncDirective: () => (/* binding */ f),
/* harmony export */   Directive: () => (/* reexport safe */ _directive_js__WEBPACK_IMPORTED_MODULE_1__.Directive),
/* harmony export */   PartType: () => (/* reexport safe */ _directive_js__WEBPACK_IMPORTED_MODULE_1__.PartType),
/* harmony export */   directive: () => (/* reexport safe */ _directive_js__WEBPACK_IMPORTED_MODULE_1__.directive)
/* harmony export */ });
/* harmony import */ var _directive_helpers_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("a5a956c9f619");
/* harmony import */ var _directive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("0e2a9b296d0b");

/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const s=(i,t)=>{const e=i._$AN;if(void 0===e)return!1;for(const i of e)i._$AO?.(t,!1),s(i,t);return!0},o=i=>{let t,e;do{if(void 0===(t=i._$AM))break;e=t._$AN,e.delete(i),i=t}while(0===e?.size)},r=i=>{for(let t;t=i._$AM;i=t){let e=t._$AN;if(void 0===e)t._$AN=e=new Set;else if(e.has(i))break;e.add(i),c(t)}};function h(i){void 0!==this._$AN?(o(this),this._$AM=i,r(this)):this._$AM=i}function n(i,t=!1,e=0){const r=this._$AH,h=this._$AN;if(void 0!==h&&0!==h.size)if(t)if(Array.isArray(r))for(let i=e;i<r.length;i++)s(r[i],!1),o(r[i]);else null!=r&&(s(r,!1),o(r));else s(this,i)}const c=i=>{i.type==_directive_js__WEBPACK_IMPORTED_MODULE_1__.PartType.CHILD&&(i._$AP??=n,i._$AQ??=h)};class f extends _directive_js__WEBPACK_IMPORTED_MODULE_1__.Directive{constructor(){super(...arguments),this._$AN=void 0}_$AT(i,t,e){super._$AT(i,t,e),r(this),this.isConnected=i._$AU}_$AO(i,t=!0){i!==this.isConnected&&(this.isConnected=i,i?this.reconnected?.():this.disconnected?.()),t&&(s(this,i),o(this))}setValue(t){if((0,_directive_helpers_js__WEBPACK_IMPORTED_MODULE_0__.isSingleExpression)(this._$Ct))this._$Ct._$AI(t,this);else{const i=[...this._$Ct._$AH];i[this._$Ci]=t,this._$Ct._$AI(i,this,0)}}disconnected(){}reconnected(){}}
//# sourceMappingURL=async-directive.js.map


/***/ },

/***/ "a5a956c9f619"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   TemplateResultType: () => (/* binding */ e),
/* harmony export */   clearPart: () => (/* binding */ j),
/* harmony export */   getCommittedValue: () => (/* binding */ M),
/* harmony export */   getDirectiveClass: () => (/* binding */ f),
/* harmony export */   insertPart: () => (/* binding */ v),
/* harmony export */   isCompiledTemplateResult: () => (/* binding */ d),
/* harmony export */   isDirectiveResult: () => (/* binding */ c),
/* harmony export */   isPrimitive: () => (/* binding */ n),
/* harmony export */   isSingleExpression: () => (/* binding */ r),
/* harmony export */   isTemplateResult: () => (/* binding */ l),
/* harmony export */   removePart: () => (/* binding */ h),
/* harmony export */   setChildPartValue: () => (/* binding */ u),
/* harmony export */   setCommittedValue: () => (/* binding */ p)
/* harmony export */ });
/* harmony import */ var _lit_html_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("f550d360fd36");

/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const{I:t}=_lit_html_js__WEBPACK_IMPORTED_MODULE_0__._$LH,i=o=>o,n=o=>null===o||"object"!=typeof o&&"function"!=typeof o,e={HTML:1,SVG:2,MATHML:3},l=(o,t)=>void 0===t?void 0!==o?._$litType$:o?._$litType$===t,d=o=>null!=o?._$litType$?.h,c=o=>void 0!==o?._$litDirective$,f=o=>o?._$litDirective$,r=o=>void 0===o.strings,s=()=>document.createComment(""),v=(o,n,e)=>{const l=o._$AA.parentNode,d=void 0===n?o._$AB:n._$AA;if(void 0===e){const i=l.insertBefore(s(),d),n=l.insertBefore(s(),d);e=new t(i,n,o,o.options)}else{const t=e._$AB.nextSibling,n=e._$AM,c=n!==o;if(c){let t;e._$AQ?.(o),e._$AM=o,void 0!==e._$AP&&(t=o._$AU)!==n._$AU&&e._$AP(t)}if(t!==d||c){let o=e._$AA;for(;o!==t;){const t=i(o).nextSibling;i(l).insertBefore(o,d),o=t}}}return e},u=(o,t,i=o)=>(o._$AI(t,i),o),m={},p=(o,t=m)=>o._$AH=t,M=o=>o._$AH,h=o=>{o._$AR(),o._$AA.remove()},j=o=>{o._$AR()};
//# sourceMappingURL=directive-helpers.js.map


/***/ },

/***/ "0e2a9b296d0b"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Directive: () => (/* binding */ i),
/* harmony export */   PartType: () => (/* binding */ t),
/* harmony export */   directive: () => (/* binding */ e)
/* harmony export */ });
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
const t={ATTRIBUTE:1,CHILD:2,PROPERTY:3,BOOLEAN_ATTRIBUTE:4,EVENT:5,ELEMENT:6},e=t=>(...e)=>({_$litDirective$:t,values:e});class i{constructor(t){}get _$AU(){return this._$AM._$AU}_$AT(t,e,i){this._$Ct=t,this._$AM=e,this._$Ci=i}_$AS(t,e){return this.update(t,e)}update(t,e){return this.render(...e)}}
//# sourceMappingURL=directive.js.map


/***/ },

/***/ "59df0b19b37c"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createRef: () => (/* binding */ e),
/* harmony export */   ref: () => (/* binding */ n)
/* harmony export */ });
/* harmony import */ var _lit_html_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("f550d360fd36");
/* harmony import */ var _async_directive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("f6a1e423f201");
/* harmony import */ var _directive_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("0e2a9b296d0b");

/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const e=()=>new h;class h{}const o=new WeakMap,n=(0,_directive_js__WEBPACK_IMPORTED_MODULE_2__.directive)(class extends _async_directive_js__WEBPACK_IMPORTED_MODULE_1__.AsyncDirective{render(i){return _lit_html_js__WEBPACK_IMPORTED_MODULE_0__.nothing}update(i,[s]){const e=s!==this.G;return e&&this.rt(void 0),(e||this.lt!==this.ct)&&(this.G=s,this.ht=i.options?.host,this.rt(this.ct=i.element)),_lit_html_js__WEBPACK_IMPORTED_MODULE_0__.nothing}rt(t){if(void 0!==this.G)if(this.isConnected||(t=void 0),"function"==typeof this.G){const i=this.ht??globalThis;let s=o.get(i);void 0===s&&(s=new WeakMap,o.set(i,s)),void 0!==s.get(this.G)&&this.G.call(this.ht,void 0),s.set(this.G,t),void 0!==t&&this.G.call(this.ht,t)}else this.G.value=t}get lt(){return"function"==typeof this.G?o.get(this.ht??globalThis)?.get(this.G):this.G?.value}disconnected(){this.lt===this.ct&&this.rt(void 0)}reconnected(){this.rt(this.ct)}});
//# sourceMappingURL=ref.js.map


/***/ },

/***/ "15e6f5e928bf"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isServer: () => (/* binding */ o)
/* harmony export */ });
/**
 * @license
 * Copyright 2022 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
const o=!1;
//# sourceMappingURL=is-server.js.map


/***/ },

/***/ "f550d360fd36"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _$LH: () => (/* binding */ j),
/* harmony export */   html: () => (/* binding */ b),
/* harmony export */   mathml: () => (/* binding */ T),
/* harmony export */   noChange: () => (/* binding */ E),
/* harmony export */   nothing: () => (/* binding */ A),
/* harmony export */   render: () => (/* binding */ D),
/* harmony export */   svg: () => (/* binding */ w)
/* harmony export */ });
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
const t=globalThis,i=t=>t,s=t.trustedTypes,e=s?s.createPolicy("lit-html",{createHTML:t=>t}):void 0,h="$lit$",o=`lit$${Math.random().toFixed(9).slice(2)}$`,n="?"+o,r=`<${n}>`,l=document,c=()=>l.createComment(""),a=t=>null===t||"object"!=typeof t&&"function"!=typeof t,u=Array.isArray,d=t=>u(t)||"function"==typeof t?.[Symbol.iterator],f="[ \t\n\f\r]",v=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,_=/-->/g,m=/>/g,p=RegExp(`>|${f}(?:([^\\s"'>=/]+)(${f}*=${f}*(?:[^ \t\n\f\r"'\`<>=]|("|')|))|$)`,"g"),g=/'/g,$=/"/g,y=/^(?:script|style|textarea|title)$/i,x=t=>(i,...s)=>({_$litType$:t,strings:i,values:s}),b=x(1),w=x(2),T=x(3),E=Symbol.for("lit-noChange"),A=Symbol.for("lit-nothing"),C=new WeakMap,P=l.createTreeWalker(l,129);function V(t,i){if(!u(t)||!t.hasOwnProperty("raw"))throw Error("invalid template strings array");return void 0!==e?e.createHTML(i):i}const N=(t,i)=>{const s=t.length-1,e=[];let n,l=2===i?"<svg>":3===i?"<math>":"",c=v;for(let i=0;i<s;i++){const s=t[i];let a,u,d=-1,f=0;for(;f<s.length&&(c.lastIndex=f,u=c.exec(s),null!==u);)f=c.lastIndex,c===v?"!--"===u[1]?c=_:void 0!==u[1]?c=m:void 0!==u[2]?(y.test(u[2])&&(n=RegExp("</"+u[2],"g")),c=p):void 0!==u[3]&&(c=p):c===p?">"===u[0]?(c=n??v,d=-1):void 0===u[1]?d=-2:(d=c.lastIndex-u[2].length,a=u[1],c=void 0===u[3]?p:'"'===u[3]?$:g):c===$||c===g?c=p:c===_||c===m?c=v:(c=p,n=void 0);const x=c===p&&t[i+1].startsWith("/>")?" ":"";l+=c===v?s+r:d>=0?(e.push(a),s.slice(0,d)+h+s.slice(d)+o+x):s+o+(-2===d?i:x)}return[V(t,l+(t[s]||"<?>")+(2===i?"</svg>":3===i?"</math>":"")),e]};class S{constructor({strings:t,_$litType$:i},e){let r;this.parts=[];let l=0,a=0;const u=t.length-1,d=this.parts,[f,v]=N(t,i);if(this.el=S.createElement(f,e),P.currentNode=this.el.content,2===i||3===i){const t=this.el.content.firstChild;t.replaceWith(...t.childNodes)}for(;null!==(r=P.nextNode())&&d.length<u;){if(1===r.nodeType){if(r.hasAttributes())for(const t of r.getAttributeNames())if(t.endsWith(h)){const i=v[a++],s=r.getAttribute(t).split(o),e=/([.?@])?(.*)/.exec(i);d.push({type:1,index:l,name:e[2],strings:s,ctor:"."===e[1]?I:"?"===e[1]?L:"@"===e[1]?z:H}),r.removeAttribute(t)}else t.startsWith(o)&&(d.push({type:6,index:l}),r.removeAttribute(t));if(y.test(r.tagName)){const t=r.textContent.split(o),i=t.length-1;if(i>0){r.textContent=s?s.emptyScript:"";for(let s=0;s<i;s++)r.append(t[s],c()),P.nextNode(),d.push({type:2,index:++l});r.append(t[i],c())}}}else if(8===r.nodeType)if(r.data===n)d.push({type:2,index:l});else{let t=-1;for(;-1!==(t=r.data.indexOf(o,t+1));)d.push({type:7,index:l}),t+=o.length-1}l++}}static createElement(t,i){const s=l.createElement("template");return s.innerHTML=t,s}}function M(t,i,s=t,e){if(i===E)return i;let h=void 0!==e?s._$Co?.[e]:s._$Cl;const o=a(i)?void 0:i._$litDirective$;return h?.constructor!==o&&(h?._$AO?.(!1),void 0===o?h=void 0:(h=new o(t),h._$AT(t,s,e)),void 0!==e?(s._$Co??=[])[e]=h:s._$Cl=h),void 0!==h&&(i=M(t,h._$AS(t,i.values),h,e)),i}class R{constructor(t,i){this._$AV=[],this._$AN=void 0,this._$AD=t,this._$AM=i}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(t){const{el:{content:i},parts:s}=this._$AD,e=(t?.creationScope??l).importNode(i,!0);P.currentNode=e;let h=P.nextNode(),o=0,n=0,r=s[0];for(;void 0!==r;){if(o===r.index){let i;2===r.type?i=new k(h,h.nextSibling,this,t):1===r.type?i=new r.ctor(h,r.name,r.strings,this,t):6===r.type&&(i=new Z(h,this,t)),this._$AV.push(i),r=s[++n]}o!==r?.index&&(h=P.nextNode(),o++)}return P.currentNode=l,e}p(t){let i=0;for(const s of this._$AV)void 0!==s&&(void 0!==s.strings?(s._$AI(t,s,i),i+=s.strings.length-2):s._$AI(t[i])),i++}}class k{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(t,i,s,e){this.type=2,this._$AH=A,this._$AN=void 0,this._$AA=t,this._$AB=i,this._$AM=s,this.options=e,this._$Cv=e?.isConnected??!0}get parentNode(){let t=this._$AA.parentNode;const i=this._$AM;return void 0!==i&&11===t?.nodeType&&(t=i.parentNode),t}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(t,i=this){t=M(this,t,i),a(t)?t===A||null==t||""===t?(this._$AH!==A&&this._$AR(),this._$AH=A):t!==this._$AH&&t!==E&&this._(t):void 0!==t._$litType$?this.$(t):void 0!==t.nodeType?this.T(t):d(t)?this.k(t):this._(t)}O(t){return this._$AA.parentNode.insertBefore(t,this._$AB)}T(t){this._$AH!==t&&(this._$AR(),this._$AH=this.O(t))}_(t){this._$AH!==A&&a(this._$AH)?this._$AA.nextSibling.data=t:this.T(l.createTextNode(t)),this._$AH=t}$(t){const{values:i,_$litType$:s}=t,e="number"==typeof s?this._$AC(t):(void 0===s.el&&(s.el=S.createElement(V(s.h,s.h[0]),this.options)),s);if(this._$AH?._$AD===e)this._$AH.p(i);else{const t=new R(e,this),s=t.u(this.options);t.p(i),this.T(s),this._$AH=t}}_$AC(t){let i=C.get(t.strings);return void 0===i&&C.set(t.strings,i=new S(t)),i}k(t){u(this._$AH)||(this._$AH=[],this._$AR());const i=this._$AH;let s,e=0;for(const h of t)e===i.length?i.push(s=new k(this.O(c()),this.O(c()),this,this.options)):s=i[e],s._$AI(h),e++;e<i.length&&(this._$AR(s&&s._$AB.nextSibling,e),i.length=e)}_$AR(t=this._$AA.nextSibling,s){for(this._$AP?.(!1,!0,s);t!==this._$AB;){const s=i(t).nextSibling;i(t).remove(),t=s}}setConnected(t){void 0===this._$AM&&(this._$Cv=t,this._$AP?.(t))}}class H{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(t,i,s,e,h){this.type=1,this._$AH=A,this._$AN=void 0,this.element=t,this.name=i,this._$AM=e,this.options=h,s.length>2||""!==s[0]||""!==s[1]?(this._$AH=Array(s.length-1).fill(new String),this.strings=s):this._$AH=A}_$AI(t,i=this,s,e){const h=this.strings;let o=!1;if(void 0===h)t=M(this,t,i,0),o=!a(t)||t!==this._$AH&&t!==E,o&&(this._$AH=t);else{const e=t;let n,r;for(t=h[0],n=0;n<h.length-1;n++)r=M(this,e[s+n],i,n),r===E&&(r=this._$AH[n]),o||=!a(r)||r!==this._$AH[n],r===A?t=A:t!==A&&(t+=(r??"")+h[n+1]),this._$AH[n]=r}o&&!e&&this.j(t)}j(t){t===A?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,t??"")}}class I extends H{constructor(){super(...arguments),this.type=3}j(t){this.element[this.name]=t===A?void 0:t}}class L extends H{constructor(){super(...arguments),this.type=4}j(t){this.element.toggleAttribute(this.name,!!t&&t!==A)}}class z extends H{constructor(t,i,s,e,h){super(t,i,s,e,h),this.type=5}_$AI(t,i=this){if((t=M(this,t,i,0)??A)===E)return;const s=this._$AH,e=t===A&&s!==A||t.capture!==s.capture||t.once!==s.once||t.passive!==s.passive,h=t!==A&&(s===A||e);e&&this.element.removeEventListener(this.name,this,s),h&&this.element.addEventListener(this.name,this,t),this._$AH=t}handleEvent(t){"function"==typeof this._$AH?this._$AH.call(this.options?.host??this.element,t):this._$AH.handleEvent(t)}}class Z{constructor(t,i,s){this.element=t,this.type=6,this._$AN=void 0,this._$AM=i,this.options=s}get _$AU(){return this._$AM._$AU}_$AI(t){M(this,t)}}const j={M:h,P:o,A:n,C:1,L:N,R,D:d,V:M,I:k,H,N:L,U:z,B:I,F:Z},B=t.litHtmlPolyfillSupport;B?.(S,k),(t.litHtmlVersions??=[]).push("3.3.3");const D=(t,i,s)=>{const e=s?.renderBefore??i;let h=e._$litPart$;if(void 0===h){const t=s?.renderBefore??null;e._$litPart$=h=new k(i.insertBefore(c(),t),t,void 0,s??{})}return h._$AI(t),h};
//# sourceMappingURL=lit-html.js.map


/***/ },

/***/ "7fcbcc00731e"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createRef: () => (/* reexport safe */ lit_html_directives_ref_js__WEBPACK_IMPORTED_MODULE_0__.createRef),
/* harmony export */   ref: () => (/* reexport safe */ lit_html_directives_ref_js__WEBPACK_IMPORTED_MODULE_0__.ref)
/* harmony export */ });
/* harmony import */ var lit_html_directives_ref_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("59df0b19b37c");

//# sourceMappingURL=ref.js.map


/***/ },

/***/ "fef8077ac919"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CSSResult: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.CSSResult),
/* harmony export */   LitElement: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.LitElement),
/* harmony export */   ReactiveElement: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.ReactiveElement),
/* harmony export */   _$LE: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__._$LE),
/* harmony export */   _$LH: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__._$LH),
/* harmony export */   adoptStyles: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.adoptStyles),
/* harmony export */   css: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.css),
/* harmony export */   defaultConverter: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.defaultConverter),
/* harmony export */   getCompatibleStyle: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.getCompatibleStyle),
/* harmony export */   html: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.html),
/* harmony export */   isServer: () => (/* reexport safe */ lit_html_is_server_js__WEBPACK_IMPORTED_MODULE_3__.isServer),
/* harmony export */   mathml: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.mathml),
/* harmony export */   noChange: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.noChange),
/* harmony export */   notEqual: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.notEqual),
/* harmony export */   nothing: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.nothing),
/* harmony export */   render: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.render),
/* harmony export */   supportsAdoptingStyleSheets: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.supportsAdoptingStyleSheets),
/* harmony export */   svg: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.svg),
/* harmony export */   unsafeCSS: () => (/* reexport safe */ lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__.unsafeCSS)
/* harmony export */ });
/* harmony import */ var _lit_reactive_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("ea86f8429d07");
/* harmony import */ var lit_html__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("f550d360fd36");
/* harmony import */ var lit_element_lit_element_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("7ab7aedf3cfc");
/* harmony import */ var lit_html_is_server_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("15e6f5e928bf");

//# sourceMappingURL=index.js.map


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	const __webpack_module_cache__ = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		const cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		const module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			const e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter/value functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			if(Array.isArray(definition)) {
/******/ 				var i = 0;
/******/ 				while(i < definition.length) {
/******/ 					var key = definition[i++];
/******/ 					var binding = definition[i++];
/******/ 					if(!__webpack_require__.o(exports, key)) {
/******/ 						if(binding === 0) {
/******/ 							Object.defineProperty(exports, key, { enumerable: true, value: definition[i++] });
/******/ 						} else {
/******/ 							Object.defineProperty(exports, key, { enumerable: true, get: binding });
/******/ 						}
/******/ 					} else if(binding === 0) { i++; }
/******/ 				}
/******/ 			} else {
/******/ 				for(var key in definition) {
/******/ 					if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 						Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 					}
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/
/************************************************************************/
let __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _core_js_smart_base__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("0845ef25b9de");
/* harmony import */ var _js_templates_default__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("77b2f4107e3b");


const ISO_DATE_RE = /^(\d{4})-(\d{2})-(\d{2})$/;
const YEARS_PAGE_SIZE = 18;
let datepickerSequence = 0;

function padDatePart(value) {
  return String(value).padStart(2, '0');
}

function formatIsoDate(date) {
  return `${date.getFullYear()}-${padDatePart(date.getMonth() + 1)}-${padDatePart(date.getDate())}`;
}

function formatDateValue(date, format = 'YYYY.MM.DD') {
  if (!date) {
    return '';
  }

  const tokens = {
    YYYY: String(date.getFullYear()),
    YY: String(date.getFullYear()).slice(-2),
    MM: padDatePart(date.getMonth() + 1),
    DD: padDatePart(date.getDate())
  };
  return String(format || 'YYYY.MM.DD').replace(/YYYY|YY|MM|DD/g, token => tokens[token] || token);
}

function isValidDate(date) {
  return date instanceof Date && !Number.isNaN(date.getTime());
}

function parseDate(value) {
  if (!value) {
    return null;
  }

  const match = String(value).match(ISO_DATE_RE);

  if (!match) {
    return null;
  }

  const [, year, month, day] = match;
  const date = new Date(Number(year), Number(month) - 1, Number(day));

  if (date.getFullYear() !== Number(year) || date.getMonth() !== Number(month) - 1 || date.getDate() !== Number(day)) {
    return null;
  }

  return date;
}

function parseDateValue(value, format = 'DD.MM.YYYY') {
  if (!value) {
    return null;
  }

  if (value instanceof Date) {
    return isValidDate(value) ? new Date(value.getFullYear(), value.getMonth(), value.getDate()) : null;
  }

  const text = String(value).trim();
  const isoDate = parseDate(text);

  if (isoDate) {
    return isoDate;
  }

  const parts = text.match(/\d+/g);
  const tokens = String(format || 'DD.MM.YYYY').match(/YYYY|YY|MM|DD/g);

  if (!parts || !tokens || parts.length < 3 || tokens.length < 3) {
    return null;
  }

  const values = {};
  tokens.slice(0, 3).forEach((token, index) => {
    values[token] = Number(parts[index]);
  });
  let year = values.YYYY;

  if (!year && values.YY) {
    year = values.YY + 2000;
  }

  const month = values.MM;
  const day = values.DD;

  if (!year || !month || !day) {
    return null;
  }

  const date = new Date(year, month - 1, day);

  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
    return null;
  }

  return date;
}

function normalizeMonth(value, fallback = 0) {
  const month = (0,_core_js_smart_base__WEBPACK_IMPORTED_MODULE_0__.toNumber)(value, fallback);

  if (!Number.isFinite(month)) {
    return fallback;
  }

  return Math.max(0, Math.min(11, month));
}

function sameDay(first, second) {
  return first && second && first.getFullYear() === second.getFullYear() && first.getMonth() === second.getMonth() && first.getDate() === second.getDate();
}

function compareDates(first, second) {
  if (!first || !second) {
    return 0;
  }

  const firstTime = new Date(first.getFullYear(), first.getMonth(), first.getDate()).getTime();
  const secondTime = new Date(second.getFullYear(), second.getMonth(), second.getDate()).getTime();
  return firstTime - secondTime;
}

function shiftDateByMonth(date, direction) {
  const targetMonth = new Date(date.getFullYear(), date.getMonth() + direction, 1);
  const lastDay = new Date(targetMonth.getFullYear(), targetMonth.getMonth() + 1, 0).getDate();
  targetMonth.setDate(Math.min(date.getDate(), lastDay));
  return targetMonth;
}

function isBetweenDates(date, startDate, endDate) {
  if (!date || !startDate || !endDate) {
    return false;
  }

  return compareDates(date, startDate) > 0 && compareDates(date, endDate) < 0;
}

function normalizeLocale(value = '') {
  const locale = String(value || '').trim();

  if (locale) {
    return locale;
  }

  if (typeof document !== 'undefined' && document.documentElement?.lang) {
    return document.documentElement.lang;
  }

  if (typeof navigator !== 'undefined' && navigator.language) {
    return navigator.language;
  }

  return 'ru-RU';
}

function capitalizeFirstLetter(value = '') {
  const text = String(value || '');

  if (!text) {
    return '';
  }

  return `${text.charAt(0).toLocaleUpperCase()}${text.slice(1)}`;
}

function getMonthLabel(month, locale) {
  try {
    return capitalizeFirstLetter(new Intl.DateTimeFormat(locale, {
      month: 'long'
    }).format(new Date(2026, month, 1)));
  } catch {
    return String(month + 1);
  }
}

function getDateLabel(date, locale) {
  try {
    return new Intl.DateTimeFormat(locale, {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(date);
  } catch {
    return formatIsoDate(date);
  }
}

function getWeekdays(locale, weekStart = 1) {
  const sunday = new Date(2026, 0, 4);
  return Array.from({
    length: 7
  }, (_, index) => {
    const day = new Date(sunday);
    day.setDate(sunday.getDate() + (weekStart + index) % 7);

    try {
      return new Intl.DateTimeFormat(locale, {
        weekday: 'short'
      }).format(day);
    } catch {
      return String((weekStart + index) % 7);
    }
  });
}

function getLabels(locale) {
  return String(locale || '').toLowerCase().startsWith('ru') ? {
    previousMonth: 'Предыдущий месяц',
    nextMonth: 'Следующий месяц',
    previousYears: 'Предыдущие годы',
    nextYears: 'Следующие годы',
    chooseMonth: 'Выберите месяц',
    chooseYear: 'Выберите год'
  } : {
    previousMonth: 'Previous month',
    nextMonth: 'Next month',
    previousYears: 'Previous years',
    nextYears: 'Next years',
    chooseMonth: 'Choose month',
    chooseYear: 'Choose year'
  };
}

class SfDatepicker extends _core_js_smart_base__WEBPACK_IMPORTED_MODULE_0__["default"] {
  constructor() {
    super();
    this._input = null;
    this._documentClickHandler = this.handleDocumentClick.bind(this);
    this._documentKeydownHandler = this.handleDocumentKeydown.bind(this);
    this._viewportHandler = this.positionPanel.bind(this);
    this._focusCalendarOnOpen = false;
    this._focusDateAfterRender = false;
    this._panelId = `sf-datepicker-panel-${++datepickerSequence}`;
    this.state = {
      openAbove: false,
      yearPageStart: null,
      activeDate: ''
    };
    this.refs = {
      input: this.createRef(),
      datePickerRoot: this.createRef(),
      datePickerPanel: this.createRef()
    };
  }

  static get props() {
    return {
      templateName: {
        attribute: 'template',
        default: 'default'
      },
      value: {
        default: ''
      },
      date: {
        default: ''
      },
      startValue: {
        attribute: 'start-value',
        default: ''
      },
      endValue: {
        attribute: 'end-value',
        default: ''
      },
      maxDate: {
        default: ''
      },
      minDate: {
        default: ''
      },
      range: {
        type: Boolean,
        default: false
      },
      month: {
        type: Number,
        default: null
      },
      year: {
        type: Number,
        default: null
      },
      context: {
        default: ''
      },
      weekStart: {
        type: Number,
        default: 1
      },
      locale: {
        default: ''
      },
      format: {
        default: 'DD.MM.YYYY'
      },
      rootClass: {
        default: ''
      },
      closeOnSelect: {
        type: Boolean,
        default: true
      },
      input: {
        type: Boolean,
        default: true
      },
      open: {
        type: Boolean,
        default: false
      },
      portal: {
        type: Boolean,
        default: true
      },
      inputType: {
        attribute: 'input-type',
        default: 'bordered'
      },
      inputSize: {
        attribute: 'input-size',
        default: '1'
      },
      inputName: {
        attribute: 'input-name',
        default: ''
      },
      placeholder: {
        default: ''
      },
      label: {
        default: ''
      },
      disabled: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      leftIcon: {
        attribute: 'left-icon',
        default: 'calendar_today'
      },
      inputRootClass: {
        attribute: 'input-root-class',
        default: ''
      },
      mask: {
        type: Boolean,
        default: false
      },
      maskPattern: {
        attribute: 'mask-pattern',
        default: ''
      },
      maskLazy: {
        attribute: 'mask-lazy',
        default: false
      },
      maskPlaceholderChar: {
        attribute: 'mask-placeholder-char',
        default: '_'
      },
      maskOptions: {
        attribute: 'mask-options',
        default: ''
      }
    };
  }

  connectedCallback() {
    this.upgradeProperty('value');
    super.connectedCallback();
  }

  upgradeProperty(name) {
    if (!Object.prototype.hasOwnProperty.call(this, name)) {
      return;
    }

    const value = this[name];
    delete this[name];
    this[name] = value;
  }

  get componentName() {
    return 'datepicker';
  }

  get templateName() {
    return this.getAttribute('template') || 'default';
  }

  get selectedDate() {
    return parseDateValue(this.date, this.format);
  }

  get primaryDate() {
    return this.selectedDate || this.startDate;
  }

  get startDate() {
    return parseDateValue(this.startValue, this.format);
  }

  get endDate() {
    return parseDateValue(this.endValue, this.format);
  }

  get date() {
    return this.getAttribute('date') || this.getAttribute('value') || '';
  }

  get value() {
    return this.range ? [this.startValue, this.endValue] : this.date;
  }

  set value(nextValue) {
    if (Array.isArray(nextValue)) {
      const [startValue = '', endValue = ''] = nextValue;
      this.setState({
        date: startValue || '',
        startValue: startValue || '',
        endValue: endValue || ''
      });
      return;
    }

    if (nextValue && typeof nextValue === 'object') {
      const startValue = nextValue.start || nextValue.startValue || nextValue.date || '';
      const endValue = nextValue.end || nextValue.endValue || '';
      this.setState({
        date: nextValue.date || startValue || '',
        startValue,
        endValue
      });
      return;
    }

    this.setState({
      date: nextValue ? String(nextValue) : '',
      startValue: '',
      endValue: ''
    });
  }

  setMaxDate() {
    return parseDateValue(this.maxDate, this.format);
  }

  setMinDate() {
    return parseDateValue(this.minDate, this.format);
  }

  get maxDate() {
    return this.getAttribute('max-date') || '';
  }

  get minDate() {
    return this.getAttribute('min-date') || '';
  }

  set date(nextValue) {
    this.setState({
      date: nextValue ? String(nextValue) : ''
    });
  }

  get startValue() {
    return this.getAttribute('start-value') || '';
  }

  set startValue(nextValue) {
    this.setState({
      startValue: nextValue ? String(nextValue) : ''
    });
  }

  get endValue() {
    return this.getAttribute('end-value') || '';
  }

  set endValue(nextValue) {
    this.setState({
      endValue: nextValue ? String(nextValue) : ''
    });
  }

  get range() {
    return this.getBooleanAttr('range', false);
  }

  get input() {
    return this.getBooleanAttr('input', true);
  }

  get open() {
    return this.getBooleanAttr('open', false);
  }

  get portal() {
    return this.getBooleanAttr('portal', true);
  }

  get inputType() {
    return this.getAttribute('input-type') || 'bordered';
  }

  get inputSize() {
    return this.getAttribute('input-size') || '1';
  }

  get inputName() {
    return this.getAttribute('input-name') || this.getAttribute('name') || '';
  }

  get placeholder() {
    return this.getAttribute('placeholder') || '';
  }

  get label() {
    return this.getAttribute('label') || '';
  }

  get disabled() {
    return this.getBooleanAttr('disabled', false);
  }

  get required() {
    return this.getBooleanAttr('required', false);
  }

  get closeOnSelect() {
    return this.getBooleanAttr('close-on-select', true);
  }

  get leftIcon() {
    return this.getAttribute('left-icon') || 'calendar_today';
  }

  get inputRootClass() {
    return this.getAttribute('input-root-class') || '';
  }

  get mask() {
    return this.getBooleanAttr('mask', false);
  }

  get maskPattern() {
    return this.getAttribute('mask-pattern') || this.formatToMask(this.format);
  }

  get maskLazy() {
    return this.hasAttribute('mask-lazy') ? this.getBooleanAttr('mask-lazy', false) : false;
  }

  get maskPlaceholderChar() {
    return this.getAttribute('mask-placeholder-char') || '_';
  }

  get maskOptions() {
    return this.getAttribute('mask-options') || '';
  }

  get year() {
    const primaryDate = this.primaryDate;
    const fallback = primaryDate ? primaryDate.getFullYear() : new Date().getFullYear();
    return this.hasAttribute('year') ? this.getNumberAttr('year', fallback) : fallback;
  }

  set year(nextValue) {
    this.setState({
      year: nextValue === null || typeof nextValue === 'undefined' || nextValue === '' ? '' : String((0,_core_js_smart_base__WEBPACK_IMPORTED_MODULE_0__.toNumber)(nextValue, new Date().getFullYear()))
    });
  }

  get month() {
    const primaryDate = this.primaryDate;
    const fallback = primaryDate ? primaryDate.getMonth() : new Date().getMonth();
    return this.hasAttribute('month') ? normalizeMonth(this.getAttribute('month'), fallback) : fallback;
  }

  set month(nextValue) {
    this.setState({
      month: nextValue === null || typeof nextValue === 'undefined' || nextValue === '' ? '' : String(normalizeMonth(nextValue, new Date().getMonth()))
    });
  }

  get contextMode() {
    return this.getEnumAttr('context', ['month', 'year', ''], '');
  }

  get weekStart() {
    const value = this.getNumberAttr('week-start', 1);
    return value === 0 ? 0 : 1;
  }

  get locale() {
    return normalizeLocale(this.getAttribute('locale'));
  }

  get format() {
    return this.getAttribute('format') || 'DD.MM.YYYY';
  }

  get formattedValue() {
    return formatDateValue(this.selectedDate, this.format);
  }

  get formattedStartValue() {
    return formatDateValue(this.startDate, this.format);
  }

  get formattedEndValue() {
    return formatDateValue(this.endDate, this.format);
  }

  get displayValue() {
    if (!this.range) {
      return this.formattedValue;
    }

    if (this.formattedStartValue && this.formattedEndValue) {
      return `${this.formattedStartValue} - ${this.formattedEndValue}`;
    }

    return this.formattedStartValue || this.formattedEndValue || '';
  }

  get displayDate() {
    return new Date(this.year, this.month, 1);
  }

  get isRtl() {
    return (this.closest('[dir]')?.getAttribute('dir') || document.documentElement?.dir) === 'rtl';
  }

  get labels() {
    return getLabels(this.locale);
  }

  get weekdays() {
    return getWeekdays(this.locale, this.weekStart);
  }

  get calendarLabel() {
    try {
      return new Intl.DateTimeFormat(this.locale, {
        month: 'long',
        year: 'numeric'
      }).format(this.displayDate);
    } catch {
      return `${this.month + 1}.${this.year}`;
    }
  }

  get activeDate() {
    const candidate = parseDate(this.state.activeDate) || this.primaryDate || this.displayDate;
    const minDate = this.setMinDate();
    const maxDate = this.setMaxDate();
    if (minDate && compareDates(candidate, minDate) < 0) return minDate;
    if (maxDate && compareDates(candidate, maxDate) > 0) return maxDate;
    return candidate;
  }

  get normalizedRange() {
    const startDate = this.startDate;
    const endDate = this.endDate;

    if (!startDate || !endDate) {
      return {
        startDate,
        endDate
      };
    }

    if (compareDates(startDate, endDate) <= 0) {
      return {
        startDate,
        endDate
      };
    }

    return {
      startDate: endDate,
      endDate: startDate
    };
  }

  get days() {
    const firstDay = new Date(this.year, this.month, 1);
    const firstWeekDay = firstDay.getDay();
    const offset = (firstWeekDay - this.weekStart + 7) % 7;
    const gridStartDate = new Date(this.year, this.month, 1 - offset);
    const {
      startDate,
      endDate
    } = this.normalizedRange;
    const hasRangeEnd = Boolean(this.startValue && this.endValue);
    const minDate = this.setMinDate();
    const maxDate = this.setMaxDate();
    const activeDate = this.activeDate;
    const today = new Date();
    return Array.from({
      length: 42
    }).map((_, index) => {
      const date = new Date(gridStartDate.getFullYear(), gridStartDate.getMonth(), gridStartDate.getDate() + index);
      const time = date.getTime();
      const disabled = Boolean(minDate && time < minDate.getTime() || maxDate && time > maxDate.getTime());
      return {
        date,
        value: formatIsoDate(date),
        formattedValue: formatDateValue(date, this.format),
        label: getDateLabel(date, this.locale),
        day: date.getDate(),
        today: sameDay(date, today),
        currentMonth: date.getMonth() === this.month,
        weekend: [0, 6].includes(date.getDay()),
        disabled: disabled,
        selected: this.range ? sameDay(date, startDate) || sameDay(date, endDate) : sameDay(date, this.selectedDate),
        rangeStart: this.range && hasRangeEnd && sameDay(date, startDate),
        rangeEnd: this.range && hasRangeEnd && sameDay(date, endDate),
        rangeSelected: this.range && hasRangeEnd && isBetweenDates(date, startDate, endDate),
        tabIndex: !disabled && sameDay(date, activeDate) ? 0 : -1
      };
    });
  }

  get months() {
    const minDate = this.setMinDate();
    const maxDate = this.setMaxDate();
    const minMonth = minDate ? new Date(minDate.getFullYear(), minDate.getMonth(), 1) : null;
    const maxMonth = maxDate ? new Date(maxDate.getFullYear(), maxDate.getMonth(), 1) : null;
    return Array.from({
      length: 12
    }).map((_, index) => {
      const date = new Date(this.year, index, 1);
      const time = date.getTime();
      const disabled = Boolean(minMonth && time < minMonth.getTime() || maxMonth && time > maxMonth.getTime());
      return {
        index,
        label: getMonthLabel(index, this.locale),
        selected: index === this.month,
        disabled
      };
    });
  }

  get years() {
    const minDate = this.setMinDate();
    const maxDate = this.setMaxDate();
    const start = this.yearPageStart;
    return Array.from({
      length: YEARS_PAGE_SIZE
    }).map((_, index) => {
      const year = start + index;
      const disabled = Boolean(minDate && year < minDate.getFullYear() || maxDate && year > maxDate.getFullYear());
      return {
        year,
        disabled: disabled,
        selected: year === this.year
      };
    });
  }

  get yearPageStart() {
    return this.state.yearPageStart ?? this.getYearPageStart(this.year);
  }

  get yearPageEnd() {
    return this.yearPageStart + YEARS_PAGE_SIZE - 1;
  }

  getYearPageStart(year = this.year) {
    const normalizedYear = (0,_core_js_smart_base__WEBPACK_IMPORTED_MODULE_0__.toNumber)(year, new Date().getFullYear());
    return normalizedYear - normalizedYear % YEARS_PAGE_SIZE;
  }

  formatToMask = format => {
    let mask = String(format).replace(/[A-Za-z]/g, '0');

    if (this.range) {
      mask += ` - ${mask}`;
    }

    return mask;
  };

  setContext(nextContext = '') {
    this.setState({
      context: nextContext || ''
    });
    return this;
  }

  toggleContext(nextContext) {
    const nextMode = this.contextMode === nextContext ? '' : nextContext;
    const nextState = {
      context: nextMode
    };

    if (nextMode === 'year') {
      nextState.yearPageStart = this.getYearPageStart(this.year);
    }

    this.setState(nextState);
    return this;
  }

  shiftYearPage(direction = 1) {
    this.set({
      yearPageStart: this.yearPageStart + YEARS_PAGE_SIZE * direction
    });
    return this;
  }

  shiftMonth(direction = 1) {
    const next = new Date(this.year, this.month + direction, 1);
    const minDate = this.setMinDate();
    const maxDate = this.setMaxDate();
    if (minDate && next < new Date(minDate.getFullYear(), minDate.getMonth(), 1)) return this;
    if (maxDate && next > new Date(maxDate.getFullYear(), maxDate.getMonth(), 1)) return this;
    this.setState({
      year: next.getFullYear(),
      month: next.getMonth(),
      context: ''
    });
    this.set({
      activeDate: formatIsoDate(next)
    });
    return this;
  }

  setActiveDate(value) {
    const date = parseDate(value);
    if (!date || this.state.activeDate === value) return this;
    this.set({
      activeDate: formatIsoDate(date)
    });
    return this;
  }

  moveActiveDate(days) {
    const next = new Date(this.activeDate);
    next.setDate(next.getDate() + days);
    const minDate = this.setMinDate();
    const maxDate = this.setMaxDate();
    if (minDate && compareDates(next, minDate) < 0) return this;
    if (maxDate && compareDates(next, maxDate) > 0) return this;
    this._focusDateAfterRender = true;
    this.setState({
      year: next.getFullYear(),
      month: next.getMonth(),
      context: ''
    });
    this.set({
      activeDate: formatIsoDate(next)
    });
    return this;
  }

  openPanel({
    focusCalendar = false
  } = {}) {
    if (this.disabled) return this;
    this._focusCalendarOnOpen = focusCalendar;

    if (!this.state.activeDate) {
      this.set({
        activeDate: formatIsoDate(this.activeDate)
      });
    }

    if (this.open) {
      this.positionPanel();
      return this;
    }

    this.setState({
      open: true
    });
    return this;
  }

  closePanel({
    restoreFocus = false
  } = {}) {
    if (!this.open) {
      return this;
    }

    this.setState({
      open: false,
      context: ''
    });
    if (restoreFocus) requestAnimationFrame(() => this._input?.focus?.());
    return this;
  }

  togglePanel() {
    return this.open ? this.closePanel() : this.openPanel();
  }

  handleInputClick(event) {
    event?.preventDefault?.();
    event?.stopPropagation?.();
    this.openPanel();
  }

  handleInputKeydown(event) {
    if (!['ArrowDown', 'Enter', ' '].includes(event.key)) return;
    event.preventDefault();
    this.openPanel({
      focusCalendar: true
    });
  }

  handleDayKeydown(event, day) {
    const horizontal = this.isRtl ? -1 : 1;
    const keySteps = {
      ArrowLeft: -horizontal,
      ArrowRight: horizontal,
      ArrowUp: -7,
      ArrowDown: 7
    };

    if (Object.prototype.hasOwnProperty.call(keySteps, event.key)) {
      event.preventDefault();
      this.moveActiveDate(keySteps[event.key]);
      return;
    }

    if (event.key === 'Home' || event.key === 'End') {
      event.preventDefault();
      const date = day?.date || this.activeDate;
      const offset = (date.getDay() - this.weekStart + 7) % 7;
      this.moveActiveDate(event.key === 'Home' ? -offset : 6 - offset);
      return;
    }

    if (event.key === 'PageUp' || event.key === 'PageDown') {
      event.preventDefault();
      const direction = event.key === 'PageUp' ? -1 : 1;
      let next = shiftDateByMonth(this.activeDate, direction);
      const minDate = this.setMinDate();
      const maxDate = this.setMaxDate();
      if (minDate && compareDates(next, minDate) < 0) next = minDate;
      if (maxDate && compareDates(next, maxDate) > 0) next = maxDate;
      this._focusDateAfterRender = true;
      this.setState({
        year: next.getFullYear(),
        month: next.getMonth()
      });
      this.set({
        activeDate: formatIsoDate(next)
      });
    }
  }

  handlePanelClick(event) {
    event?.stopPropagation?.();
  }

  handleDocumentClick(event) {
    if (!this.open) {
      return;
    }

    const target = event.target;

    if (this.contains(target) || this.refs.datePickerPanel.value?.contains?.(target)) {
      return;
    }

    this.closePanel();
  }

  handleDocumentKeydown(event) {
    if (!this.open || event.key !== 'Escape') {
      return;
    }

    event.preventDefault();

    if (this.contextMode) {
      this.setContext('');
      return;
    }

    this.closePanel();
    requestAnimationFrame(() => this._input?.focus?.());
  }

  syncOverlayListeners() {
    if (this.input && this.open) {
      document.addEventListener('click', this._documentClickHandler);
      document.addEventListener('keydown', this._documentKeydownHandler);
      window.addEventListener('resize', this._viewportHandler);
      document.addEventListener('scroll', this._viewportHandler, true);
      window.visualViewport?.addEventListener('resize', this._viewportHandler);
      window.visualViewport?.addEventListener('scroll', this._viewportHandler);
      return;
    }

    this.removeOverlayListeners();
  }

  removeOverlayListeners() {
    document.removeEventListener('click', this._documentClickHandler);
    document.removeEventListener('keydown', this._documentKeydownHandler);
    window.removeEventListener('resize', this._viewportHandler);
    document.removeEventListener('scroll', this._viewportHandler, true);
    window.visualViewport?.removeEventListener('resize', this._viewportHandler);
    window.visualViewport?.removeEventListener('scroll', this._viewportHandler);
  }

  positionPanel() {
    const panel = this.refs.datePickerPanel.value;

    if (!this.input || !this.portal || !this.open || !panel) {
      return;
    }

    const trigger = this._input || this.querySelector('sf-input');

    if (!trigger) {
      return;
    }

    const rect = trigger.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();
    const viewport = window.visualViewport;
    const viewportTop = viewport?.offsetTop || 0;
    const viewportLeft = viewport?.offsetLeft || 0;
    const viewportHeight = viewport?.height || window.innerHeight || 0;
    const viewportWidth = viewport?.width || window.innerWidth || 0;
    const styles = getComputedStyle(document.documentElement);

    const readToken = name => parseFloat(styles.getPropertyValue(name)) || 0;

    const viewportMargin = readToken('--sf-space-1/2');
    const offset = readToken('--sf-space-1/4');
    const availableWidth = Math.max(0, viewportWidth - viewportMargin * 2);
    const availableHeight = Math.max(0, viewportHeight - viewportMargin * 2);
    const panelHeight = Math.min(panelRect.height, availableHeight);
    const panelWidth = Math.min(panelRect.width, availableWidth);
    const viewportBottom = viewportTop + viewportHeight;
    const viewportRight = viewportLeft + viewportWidth;
    const spaceBelow = viewportBottom - rect.bottom - viewportMargin;
    const spaceAbove = rect.top - viewportTop - viewportMargin;
    const openAbove = panelHeight > spaceBelow && spaceAbove > spaceBelow;
    const top = openAbove ? Math.max(viewportTop + viewportMargin, rect.top - panelHeight - offset) : Math.min(rect.bottom + offset, viewportBottom - panelHeight - viewportMargin);
    const left = Math.min(Math.max(rect.left, viewportLeft + viewportMargin), viewportRight - panelWidth - viewportMargin);
    Object.assign(panel?.style, {
      position: 'fixed',
      top: `${Math.round(top)}px`,
      left: `${Math.round(left)}px`,
      maxInlineSize: `${Math.round(availableWidth)}px`,
      maxBlockSize: `${Math.round(availableHeight)}px`,
      overflowY: 'auto',
      zIndex: 'var(--sf-z-index-9)'
    });
    this.set({
      openAbove: openAbove
    });
  }

  selectDate(day) {
    if (!day?.date || day.disabled) {
      return this;
    }

    if (this.range) {
      this.selectRangeDate(day);
      return this;
    }

    this.setState({
      year: day.date.getFullYear(),
      month: day.date.getMonth(),
      date: day.value,
      context: ''
    });
    this.set({
      activeDate: day.value
    });
    this.emitChange();
    if (this.closeOnSelect) this.closePanel({
      restoreFocus: true
    });
    return this;
  }

  selectRangeDate(day) {
    if (!this.startValue || this.startValue && this.endValue) {
      this.setState({
        year: day.date.getFullYear(),
        month: day.date.getMonth(),
        startValue: day.value,
        endValue: '',
        date: day.value,
        context: ''
      });
      this.set({
        activeDate: day.value
      });
      this.emitChange();
      return this;
    }

    const startDate = this.startDate;
    const nextState = {
      year: day.date.getFullYear(),
      month: day.date.getMonth(),
      context: ''
    };

    if (startDate && compareDates(day.date, startDate) < 0) {
      nextState.startValue = day.value;
      nextState.endValue = this.startValue;
      nextState.date = day.value;
    } else {
      nextState.endValue = day.value;
      nextState.date = this.startValue;
    }

    this.setState(nextState);
    this.set({
      activeDate: day.value
    });
    this.emitChange();
    if (this.closeOnSelect) this.closePanel({
      restoreFocus: true
    });
    return this;
  }

  selectMonth(month) {
    if (!month || month.disabled) {
      return this;
    }

    this.setState({
      month: month.index,
      context: ''
    });
    this.set({
      activeDate: formatIsoDate(new Date(this.year, month.index, 1))
    });
    return this;
  }

  selectYear(year) {
    if (!year || year.disabled) {
      return this;
    }

    const nextState = {
      year: year.year,
      context: ''
    };
    const nextMonth = this.getClosestEnabledMonth(year.year, this.month);

    if (nextMonth !== this.month) {
      nextState.month = nextMonth;
    }

    this.setState(nextState);
    this.set({
      activeDate: formatIsoDate(new Date(year.year, nextMonth, 1))
    });
    return this;
  }

  getClosestEnabledMonth(year, month) {
    const minDate = this.setMinDate();
    const maxDate = this.setMaxDate();

    if (minDate && year === minDate.getFullYear() && month < minDate.getMonth()) {
      return minDate.getMonth();
    }

    if (maxDate && year === maxDate.getFullYear() && month > maxDate.getMonth()) {
      return maxDate.getMonth();
    }

    return month;
  }

  emitChange() {
    const detail = this.range ? {
      value: [this.startValue, this.endValue],
      date: this.formattedValue,
      startValue: this.startValue,
      endValue: this.endValue,
      formattedValue: this.displayValue,
      formattedStartValue: this.formattedStartValue,
      formattedEndValue: this.formattedEndValue,
      isoValue: this.date,
      startIsoValue: this.startValue,
      endIsoValue: this.endValue,
      range: true,
      format: this.format
    } : {
      value: this.date,
      date: this.date,
      formattedValue: this.formattedValue,
      isoValue: this.date,
      range: false,
      format: this.format
    };
    this.dispatchEvent(new CustomEvent('change', {
      bubbles: true,
      composed: true,
      detail
    }));
    this.dispatchEvent(new CustomEvent('sf-datepicker-change', {
      bubbles: true,
      composed: true,
      detail
    }));
    this.emitComponentEvent('change', this.data);
  }

  templateContext() {
    const props = this.getPropsContext();
    return this.createTemplateContext({ ...props,
      component: this,
      date: this.date,
      formattedValue: this.formattedValue,
      displayValue: this.displayValue,
      startValue: this.startValue,
      formattedStartValue: this.formattedStartValue,
      endValue: this.endValue,
      formattedEndValue: this.formattedEndValue,
      range: this.range,
      format: this.format,
      selectedDate: this.selectedDate,
      startDate: this.startDate,
      endDate: this.endDate,
      year: this.year,
      month: this.month,
      monthLabel: getMonthLabel(this.month, this.locale),
      calendarLabel: this.calendarLabel,
      labels: this.labels,
      weekdays: this.weekdays,
      panelId: this._panelId,
      previousIcon: this.isRtl ? 'chevron_right' : 'chevron_left',
      nextIcon: this.isRtl ? 'chevron_left' : 'chevron_right',
      context: this.contextMode,
      locale: this.locale,
      days: this.days,
      months: this.months,
      years: this.years,
      yearPageStart: this.yearPageStart,
      yearPageEnd: this.yearPageEnd,
      rootClass: this.getRootClass(),
      rootStyle: this.getRootStyle(),
      openAbove: Boolean(this.state.openAbove),
      input: this.input,
      open: this.open,
      portal: this.portal,
      inputType: this.inputType,
      inputSize: this.inputSize,
      inputName: this.inputName,
      placeholder: this.placeholder,
      label: this.label,
      disabled: this.disabled,
      required: this.required,
      leftIcon: this.leftIcon,
      inputRootClass: this.inputRootClass,
      mask: this.mask,
      maskPattern: this.maskPattern,
      maskLazy: this.maskLazy,
      maskPlaceholderChar: this.maskPlaceholderChar,
      maskOptions: this.maskOptions
    });
  }

  template() {
    return (0,_js_templates_default__WEBPACK_IMPORTED_MODULE_1__.renderDatepickerTemplate)(this.templateContext());
  }

  afterRender() {
    this._input = this.refs.input?.value?.querySelector('input') || null;

    if (this._input) {
      this._input.setAttribute('aria-haspopup', 'dialog');

      this._input.setAttribute('aria-expanded', this.open ? 'true' : 'false');

      this._input.setAttribute('aria-controls', this._panelId);
    }

    const monthToggle = this.querySelector('.sf-datepicker-month-toggle button');
    const yearToggle = this.querySelector('.sf-datepicker-year-toggle button');
    monthToggle?.setAttribute('aria-expanded', this.contextMode === 'month' ? 'true' : 'false');
    yearToggle?.setAttribute('aria-expanded', this.contextMode === 'year' ? 'true' : 'false');
    this.syncOverlayListeners();
    requestAnimationFrame(() => {
      this.positionPanel();

      if (this._focusCalendarOnOpen || this._focusDateAfterRender) {
        const active = this.querySelector(`.sf-datepicker-day[data-date="${formatIsoDate(this.activeDate)}"]`);
        active?.focus?.();
        this._focusCalendarOnOpen = false;
        this._focusDateAfterRender = false;
      }
    });
  }

  onDisconnected() {
    this.removeOverlayListeners();
    super.onDisconnected();
  }

  get data() {
    return {
      value: this.value,
      date: this.date,
      formattedValue: this.formattedValue,
      displayValue: this.displayValue,
      startValue: this.startValue,
      formattedStartValue: this.formattedStartValue,
      endValue: this.endValue,
      formattedEndValue: this.formattedEndValue,
      range: this.range,
      format: this.format,
      year: this.year,
      month: this.month,
      context: this.contextMode,
      maxDate: this.maxDate,
      minDate: this.minDate,
      locale: this.locale,
      input: this.input,
      open: this.open,
      portal: this.portal,
      mask: this.mask,
      maskPattern: this.maskPattern,
      maskOptions: this.maskOptions
    };
  }

}

SfDatepicker.define('sf-datepicker');
})();

/******/ })()
;