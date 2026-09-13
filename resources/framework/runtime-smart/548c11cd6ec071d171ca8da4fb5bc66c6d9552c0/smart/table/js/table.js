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

/***/ "926043d18700"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   debounce: () => (/* binding */ debounce),
/* harmony export */   getElementRowGap: () => (/* binding */ getElementRowGap),
/* harmony export */   getParent: () => (/* binding */ getParent),
/* harmony export */   setParentSpace: () => (/* binding */ setParentSpace)
/* harmony export */ });
function getElementRowGap(element) {
  return parseFloat(getComputedStyle(element).rowGap) || 0;
}
function setParentSpace(items) {
  const firstRef = items.values().next().value;
  const firstValue = firstRef?.value;
  if (!firstValue) return;
  const parent = firstValue.parentNode;
  if (!parent) return;
  return {
    gap: getElementRowGap(parent),
    item: firstValue,
    parent: parent
  };
}
function debounce(func, wait, immediate) {
  let timeout;
  return function () {
    const context = this,
          args = arguments;

    const later = () => {
      timeout = null;

      if (!immediate) {
        func.apply(context, args);
      }
    };

    const callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);

    if (callNow) {
      func.apply(context, args);
    }
  };
}
function getParent(item) {
  let current = item;

  while (current) {
    if (current.nodeName.toLowerCase().startsWith('sf-')) {
      return current.parentNode;
    }

    current = current.parentNode;
  }

  return null;
}

/***/ },

/***/ "2d094259808e"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   bindSortableDrag: () => (/* binding */ bindSortableDrag),
/* harmony export */   getVerticalDragAfterElement: () => (/* binding */ getVerticalDragAfterElement),
/* harmony export */   getVerticalDragItemsLayout: () => (/* binding */ getVerticalDragItemsLayout)
/* harmony export */ });
/* harmony import */ var _loaderDragState__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("870b5dfddc25");

function getVerticalDragItemsLayout(container, options = {}) {
  const accept = typeof options.accept === "function" ? options.accept : () => true;
  return Array.from(container?.children || []).filter(item => item instanceof HTMLElement && !item.classList.contains("is-dragging") && !item.classList.contains("drag-placeholder") && accept(item)).map(item => {
    const box = item.getBoundingClientRect();
    return {
      item,
      top: box.top,
      height: box.height,
      middle: box.top + box.height / 2
    };
  });
}
function getVerticalDragAfterElement(container, y, options = {}) {
  const itemsLayout = Array.isArray(options.itemsLayout) ? options.itemsLayout : getVerticalDragItemsLayout(container, options);
  let closest = {
    offset: Number.NEGATIVE_INFINITY,
    element: null
  };

  for (const layout of itemsLayout) {
    const offset = y - layout.middle;

    if (offset < 0 && offset > closest.offset) {
      closest = {
        offset,
        element: layout.item
      };
    }
  }

  return closest.element;
}
function bindSortableDrag(options = {}) {
  const {
    handle,
    item,
    container,
    holdDelay = 0,
    boundAttr = "data-sf-sortable-drag-bound",
    draggingClass = "is-dragging",
    placeholderClass = "drag-placeholder",
    placeholderExtraClasses = [],
    shouldIgnorePointerDown,
    getAfterElement,
    getSortableItems,
    insertPlaceholder,
    onDragStart,
    onDrop,
    onCancel,
    dragContainer,
    minSortableItems = 2
  } = options;
  if (!handle || !item || !container) return null;
  if (handle.getAttribute(boundAttr) === "1") return null;
  handle.setAttribute(boundAttr, "1");
  let isDragging = false;
  let holdTimer = null;
  let suppressClick = false;
  let shiftX = 0;
  let shiftY = 0;
  let startRect = null;
  let containerRect = null;
  let itemsLayout = [];
  let lastAfterElement = undefined;
  let placeholder = null;
  let originalParent = null;
  let originalNextSibling = null;

  const clearHoldTimer = () => {
    if (!holdTimer) return;
    clearTimeout(holdTimer);
    holdTimer = null;
  };

  const getCurrentSortableItems = () => {
    if (typeof getSortableItems === "function") {
      const items = getSortableItems(container, item);
      return Array.isArray(items) ? items : Array.from(items || []);
    }

    return getVerticalDragItemsLayout(container, {
      accept: typeof getAfterElement === "function" ? () => true : undefined
    }).map(layout => layout.item);
  };

  const resetItemStyle = () => {
    Object.assign(item.style, {
      position: "",
      left: "",
      top: "",
      width: "",
      zIndex: "",
      pointerEvents: "",
      boxSizing: "",
      backgroundColor: ""
    });
  };

  const movePlaceholder = event => {
    const afterElement = typeof getAfterElement === "function" ? getAfterElement(container, event.clientY, event, itemsLayout) : getVerticalDragAfterElement(container, event.clientY, {
      itemsLayout
    });

    if (afterElement === lastAfterElement) {
      return;
    }

    lastAfterElement = afterElement;

    if (typeof insertPlaceholder === "function") {
      insertPlaceholder(container, placeholder, afterElement, event);
      return;
    }

    if (!afterElement) {
      if (placeholder.parentNode === container && !placeholder.nextSibling) {
        return;
      }

      container.appendChild(placeholder);
    } else {
      if (placeholder.nextSibling === afterElement) {
        return;
      }

      container.insertBefore(placeholder, afterElement);
    }
  };

  const startDrag = event => {
    if (isDragging) return;
    event.preventDefault?.();

    if (Number(minSortableItems) > 1) {
      const sortableItems = getCurrentSortableItems().filter(sortableItem => sortableItem instanceof HTMLElement && !sortableItem.classList.contains(draggingClass) && !sortableItem.classList.contains(placeholderClass));

      if (sortableItems.length < Number(minSortableItems)) {
        return;
      }
    }

    isDragging = true;
    suppressClick = true;
    (0,_loaderDragState__WEBPACK_IMPORTED_MODULE_0__.setLoaderDragState)(true);
    startRect = item.getBoundingClientRect();
    containerRect = container.getBoundingClientRect();
    shiftX = event.clientX - startRect.left;
    shiftY = event.clientY - startRect.top;
    placeholder = document.createElement(item.nodeName.toLowerCase());
    placeholder.className = placeholderClass;
    placeholder.style.width = `${startRect.width}px`;
    placeholder.style.minHeight = `${startRect.height}px`;
    placeholder.classList.add(...placeholderExtraClasses, ...item.classList);
    originalParent = item.parentNode;
    originalNextSibling = item.nextSibling;
    item.before(placeholder);
    (dragContainer || document.body).appendChild(item);
    item.classList.add(draggingClass);
    const styles = {
      position: "fixed",
      left: `${startRect.left}px`,
      top: `${startRect.top}px`,
      width: `${startRect.width}px`,
      zIndex: "9999",
      pointerEvents: "none",
      boxSizing: "border-box"
    };

    if (!item.style.backgroundColor) {
      styles.backgroundColor = 'var(--sf-primary-container)';
    }

    Object.assign(item.style, styles);
    itemsLayout = getVerticalDragItemsLayout(container, {
      accept: typeof getAfterElement === "function" ? () => true : undefined
    });
    lastAfterElement = undefined;
    handle.setPointerCapture?.(event.pointerId);
    onDragStart?.({
      event,
      item,
      container,
      placeholder
    });
    document.addEventListener("pointermove", onPointerMove);
  };

  const onPointerMove = event => {
    if (!isDragging) {
      return;
    }

    let left = event.clientX - shiftX;
    let top = event.clientY - shiftY;
    left = Math.max(containerRect.left, left);
    top = Math.max(containerRect.top, top);
    left = Math.min(containerRect.right - startRect.width, left);
    top = Math.min(containerRect.bottom - startRect.height, top);
    item.style.left = `${left}px`;
    item.style.top = `${top}px`;
    movePlaceholder(event);
  };

  const onPointerUp = event => {
    clearHoldTimer();
    document.removeEventListener("pointermove", onPointerMove);
    document.removeEventListener("pointercancel", onPointerCancel);

    if (!isDragging) {
      return;
    }

    isDragging = false;
    (0,_loaderDragState__WEBPACK_IMPORTED_MODULE_0__.setLoaderDragState)(false);
    handle.releasePointerCapture?.(event.pointerId);

    if (placeholder?.parentNode) {
      placeholder.replaceWith(item);
    }

    item.classList.remove(draggingClass);
    resetItemStyle();
    const dropPlaceholder = placeholder;
    placeholder = null;
    startRect = null;
    containerRect = null;
    itemsLayout = [];
    lastAfterElement = undefined;
    originalParent = null;
    originalNextSibling = null;
    onDrop?.({
      event,
      item,
      container,
      placeholder: dropPlaceholder
    });
  };

  const onPointerCancel = () => {
    clearHoldTimer();
    document.removeEventListener("pointermove", onPointerMove);
    document.removeEventListener("pointerup", onPointerUp);

    if (!isDragging) {
      return;
    }

    isDragging = false;
    (0,_loaderDragState__WEBPACK_IMPORTED_MODULE_0__.setLoaderDragState)(false);

    if (originalParent) {
      originalParent.insertBefore(item, originalNextSibling);
    }

    placeholder?.remove();
    item.classList.remove(draggingClass);
    resetItemStyle();
    const cancelPlaceholder = placeholder;
    placeholder = null;
    startRect = null;
    containerRect = null;
    itemsLayout = [];
    lastAfterElement = undefined;
    originalParent = null;
    originalNextSibling = null;
    onCancel?.({
      item,
      container,
      placeholder: cancelPlaceholder
    });
  };

  const onPointerDown = event => {
    if (event.button !== 0) return;
    if (shouldIgnorePointerDown?.(event)) return;
    clearHoldTimer();

    if (holdDelay > 0) {
      holdTimer = setTimeout(() => {
        holdTimer = null;
        startDrag(event);
      }, holdDelay);
    } else {
      startDrag(event);
    }

    document.addEventListener("pointerup", onPointerUp, {
      once: true
    });
    document.addEventListener("pointercancel", onPointerCancel, {
      once: true
    });
  };

  const onClick = event => {
    if (!suppressClick) return;
    suppressClick = false;
    event.preventDefault();
    event.stopImmediatePropagation();
  };

  handle.addEventListener("pointerdown", onPointerDown);
  handle.addEventListener("click", onClick, true);
  return () => {
    clearHoldTimer();
    document.removeEventListener("pointermove", onPointerMove);
    document.removeEventListener("pointerup", onPointerUp);
    document.removeEventListener("pointercancel", onPointerCancel);
    handle.removeEventListener("pointerdown", onPointerDown);
    handle.removeEventListener("click", onClick, true);
    handle.removeAttribute(boundAttr);
    (0,_loaderDragState__WEBPACK_IMPORTED_MODULE_0__.setLoaderDragState)(false);
  };
}

/***/ },

/***/ "870b5dfddc25"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   setLoaderDragState: () => (/* binding */ setLoaderDragState)
/* harmony export */ });
function setLoaderDragState(value) {
  if (typeof SF === "undefined" || !SF.Loader) return;
  const loader = SF.Loader;
  const currentCount = Number(loader.dragObserverLockCount || 0);
  loader.dragObserverLockCount = value ? currentCount + 1 : Math.max(currentCount - 1, 0);
  loader.stopObserver = loader.dragObserverLockCount > 0;
}

/***/ },

/***/ "b53fd744fdaf"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   renderTableTemplate: () => (/* binding */ renderTableTemplate)
/* harmony export */ });
/* harmony import */ var lit__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("fef8077ac919");
/* harmony import */ var lit_directives_keyed_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("707122d76f9d");
/* harmony import */ var lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("7fcbcc00731e");
/* harmony import */ var _default_css__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("7d99d770602e");
/* harmony import */ var _link_cell_contract_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("a17b6611091c");






function getEventInputValue(event, fallback = '') {
  const pathTarget = event?.composedPath?.()?.[0];
  const candidates = [pathTarget, event?.target, event?.currentTarget];

  for (const candidate of candidates) {
    if (!candidate) continue;

    if (typeof candidate.value !== 'undefined') {
      return candidate.value;
    }

    const input = candidate.querySelector?.('input, textarea');

    if (input && typeof input.value !== 'undefined') {
      return input.value;
    }
  }

  return fallback;
}

function renderToolbar(context) {
  const {
    state
  } = context.component;
  const {
    searchActive,
    value
  } = state.search;
  const filterTags = context.component.getCurrentFilterTags();
  const filterValues = context.component.getCurrentFilterValues();
  const hasUnsavedFilterDataChanges = context.component.hasUnsavedFilterDataChanges();
  const activeFilterTags = Object.entries(filterTags || {}).filter(([, tag]) => tag?.active !== false);
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <div
                class="sf-table-toolbar flex flex-wrap gap-1/2${!searchActive ? ' items-cross-start' : ''} content-main-between flex-none${searchActive && activeFilterTags.length ? ' flex-col-reverse' : ''}"
        >
            <div class="sf-table-toolbar-start flex items-cross-center gap-1/3 flex-wrap min-w-0 ${!activeFilterTags.length && searchActive ? 'hidden' : ''}">
                <div class="flex items-cross-center">
                    <sf-button
                            size="1"
                            type="default"
                            scheme="primary"
                            text=${context.filterText}
                            segment="start"
                            icon-left="filter_list"
                            @click=${e => {
    context.component.openContextMenu(e, {
      type: 'bottom',
      menu: 'filter-settings'
    });
  }}
                    ></sf-button>
                    <sf-icon-button
                            size="1"
                            type="default"
                            scheme="primary"
                            aria-label="Сохранить шаблон фильтра"
                            ?disabled=${!activeFilterTags.length}
                            segment="end"
                            icon="bookmark_add"
                            @click=${e => {
    context.component.openContextMenu(e, {
      type: 'bottom',
      menu: 'filter-favorites',
      parent: true
    });
  }}
                    ></sf-icon-button>
                </div>
                ${activeFilterTags.map(([k, tag]) => {
    const filterValue = filterValues?.[k] || {};
    const hasFilteredValues = Object.prototype.hasOwnProperty.call(filterValues || {}, k);
    const count = Array.isArray(filterValue.values) ? filterValue.values.length : tag.count || '';
    return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                        <div class="sf-table-tags">
                            <sf-tag
                                    type="default"
                                    size="1"
                                    text='${tag.label}'
                                    closable
                                    :key=${k}
                                    root-class="${hasFilteredValues ? 'active' : ''}"
                                    @close="${() => {
      context.component.removeFilterTag(k);
    }}"
                                    @click=${e => {
      context.component.openContextMenu(e, {
        type: 'bottom',
        menu: 'tag-settings',
        fieldKey: k
      });
    }}
                                    count='${count}'
                            ></sf-tag>
                        </div>`;
  })}
                ${activeFilterTags.length || hasUnsavedFilterDataChanges ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                    ${hasUnsavedFilterDataChanges ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                        <sf-button
                                size="1"
                                type="default"
                                scheme="primary"
                                text="Сохранить"
                                @click="${() => context.component.saveCurrentFilterTemplateData()}"
                        ></sf-button>
                    ` : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
                    ${activeFilterTags.length ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                        <sf-button
                                size="1"
                                type="outline"
                                scheme="on-surface"
                                text=${context.clearText}
                                @click="${() => context.component.removeFilterTag()}"
                        ></sf-button>
                    ` : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
                ` : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
            </div>

            <div class="sf-table-toolbar-end flex items-cross-center gap-1/3 flex-wrap flex-auto min-w-0">
                <sf-input
                        size="1"
                        root-class="sf-search-input transition-layout m-inline-start-auto${searchActive ? ' flex-1' : ''}"
                        type="bordered"
                        value="${value}"
                        placeholder=${context.searchPlaceholder}
                        name="sf_table_search"
                        @input=${e => {
    context.component.searchChange({
      value: getEventInputValue(e, value)
    });
  }}
                        @keydown=${e => {
    context.component.handleSearchKeyDown(e);
  }}
                        @keyup=${e => {
    context.component.handleSearchKeyUp(e);
  }}
                        @focusout=${e => {
    context.component.updateSearchState({
      searchActive: e.currentTarget.contains(e.relatedTarget)
    }, false);
  }}
                        @focusin=${() => {
    context.component.updateSearchState({
      searchActive: true
    }, false);
  }}
                        hint-icon="search"
                >
          <span slot="right">
                <sf-icon-button
                        size="1"
                        type="link"
                        scheme="on-surface"
                        icon=${value.length ? 'close' : 'search'}
                        aria-label=${value.length ? 'Очистить поиск' : 'Поиск'}
                        @click=${e => {
    e.preventDefault();
    e.stopPropagation();
    if (!value.length) return false;
    context.component.updateSearchState({
      value: ''
    });
  }}
                ></sf-icon-button>

          </span>
                </sf-input>

                ${context.actions ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                            <div class="flex items-cross-center">
                                <sf-button
                                        size="1"
                                        type="outline"
                                        scheme="on-surface"
                                        text=${context.createText}
                                        segment="start"
                                ></sf-button>
                                <sf-icon-button
                                        size="1"
                                        type="outline"
                                        scheme="primary"
                                        segment="end"
                                        icon="keyboard_arrow_down"
                                        aria-label="Дополнительные действия создания"
                                ></sf-icon-button>
                            </div>
                        ` : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}

                ${context.settings ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                            <sf-icon-button
                                    size="1"
                                    type="outline"
                                    scheme="primary"
                                    icon="settings"
                                    aria-label="Настройки таблицы"
                                    @click=${e => {
    context.component.openContextMenu(e, {
      menu: 'table-settings'
    });
  }}
                            ></sf-icon-button>
                        ` : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
            </div>
        </div>
    `;
}

function renderHead(context) {
  if (context.component?.hasSlotContent?.("head")) {
    return context.component.getSlotContent("head");
  }

  const columns = context.component?.getColumns?.() || [];
  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <thead class="sticky top-0 z-5">
        <tr>
            ${columns.map((column, k) => {
    return (0,lit_directives_keyed_js__WEBPACK_IMPORTED_MODULE_1__.keyed)(`head:${column.key}`, (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                            <th
                                    class="relative"
                                    data-key="${column.key}"
                                    data-label="${column.label || column.key || ''}"
                            >
                                ${column.system || k === columns.length - 1 ? column.component ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                                        ${context.component.renderSmartElement(column.component.type, column.component.props)}
                                    ` : (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                                        ${column.label} </th>` : (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`<div
                                        class="sidebar-resizer flex items-cross-center h-full select-none"
                                >
                                    ${column.component ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                                        ${context.component.renderSmartElement(column.component.type, column.component.props)}
                                    ` : (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                                        ${column.label}`}
                                    <sf-icon-button size="1/3"
                                                    radius="default"
                                                    root-class="sidebar-resizer-button absolute top-1/2 -translate-y-half translate-x-half inline-end-0 z-1 cursor-col-resize"
                                                    id="column_resizer_${column.key}"
                                                    aria-label="Изменить ширину столбца: ${column.label || column.key}"
                                                    aria-keyshortcuts="ArrowLeft ArrowRight"
                                                    type="link"
                                                    scheme="on-surface"
                                                    icon="drag_handle"
                                                    @keydown=${event => context.component.resizeColumnByKeyboard(event, column.key)}
                                    ></sf-icon-button>
                                </div>
                            </th>`}
                        `);
  })}
        </tr>
        </thead>
    `;
}

function toColumnSize(value) {
  if (value === null || typeof value === "undefined" || value === "") {
    return "";
  }

  return typeof value === "number" ? `${value}px` : String(value);
}

function renderColgroup(context) {
  const columns = context.component?.getColumns?.() || [];

  if (!columns.length) {
    return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
  }

  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <colgroup>
            ${columns.map(column => {
    const width = toColumnSize(column.colWidth ?? column.width ?? column.size);
    const minWidth = toColumnSize(column.minWidth);
    const maxWidth = toColumnSize(column.maxWidth);
    const style = [width ? `width: ${width}` : "", minWidth ? `min-width: ${minWidth}` : "", maxWidth ? `max-width: ${maxWidth}` : ""].filter(Boolean).join("; ");
    return (0,lit_directives_keyed_js__WEBPACK_IMPORTED_MODULE_1__.keyed)(`col:${column.key}`, (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                    <col
                            data-key=${column.key || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
                            style=${style || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
                    />
                `);
  })}
        </colgroup>
    `;
}

function renderComponent(component, owner) {
  if (!component?.type) {
    return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
  }

  const props = { ...(component.props || {})
  };
  return owner.renderSmartElement(component.type, props);
}

function renderCellItem(item, owner) {
  if (item === null || typeof item === "undefined") {
    return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
  }

  if (typeof item === "object") {
    const {
      component,
      props: itemProps,
      ...itemMeta
    } = item;

    if (!component?.type) {
      return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
    }

    return renderComponent({ ...component,
      props: { ...itemMeta,
        ...(itemProps || {}),
        ...(component.props || {})
      }
    }, owner);
  }

  return item;
}

function renderLinkCell(value) {
  const cell = (0,_link_cell_contract_js__WEBPACK_IMPORTED_MODULE_4__.normalizeTableLinkCell)(value);

  if (!cell) {
    return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
  }

  const content = (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <span class="sf-table-link-cell-content flex flex-col min-w-0">
            <span class="sf-table-link-cell-text">${cell.text}</span>
            ${cell.subtext === null ? lit__WEBPACK_IMPORTED_MODULE_0__.nothing : (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                <span class="sf-table-link-cell-subtext sf-text-1/2">${cell.subtext}</span>
            `}
        </span>
    `;
  return cell.href ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`<a class="sf-table-link-cell" href=${cell.href}>${content}</a>` : (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`<span class="sf-table-link-cell">${content}</span>`;
}

function renderTableTemplate(context) {
  const {
    state
  } = context.component;

  function renderBody() {
    if (context.component?.hasSlotContent?.("body")) {
      return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                <tbody>
                ${context.component.getSlotContent("body")}
                </tbody>`;
    }

    const {
      state
    } = context.component;
    const columns = context.component?.getColumns?.() || [];
    const {
      rows = []
    } = state.data || {};
    const dataState = context.dataState === 'auto' ? rows.length ? 'populated' : 'empty' : context.dataState;

    if (dataState !== 'populated') {
      const stateContent = {
        loading: (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                    <div class="flex items-cross-center gap-1/2" role="status">
                        <sf-spinner size="1" aria-hidden="true"></sf-spinner>
                        <span>${context.loadingText}</span>
                    </div>
                `,
        error: (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                    <div class="flex flex-col items-cross-start gap-1/2" role="alert">
                        <span>${context.errorText}</span>
                        <sf-button
                                type="outline"
                                scheme="on-surface"
                                size="1"
                                text=${context.retryText}
                                @click=${() => context.component.requestRetry()}
                        ></sf-button>
                    </div>
                `,
        empty: (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`<span>${context.emptyText}</span>`
      }[dataState] || (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`<span>${context.emptyText}</span>`;
      return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                <tbody data-state=${dataState}>
                    <tr>
                        <td colspan=${String(Math.max(columns.length, 1))}>
                            <div class="sf-table-state p-2">${stateContent}</div>
                        </td>
                    </tr>
                </tbody>
            `;
    }

    return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
            <tbody>
            ${rows.map(row => (0,lit_directives_keyed_js__WEBPACK_IMPORTED_MODULE_1__.keyed)(`row:${row.id ?? row.value ?? rows.indexOf(row)}`, (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                <tr>
                    ${columns.map(column => (0,lit_directives_keyed_js__WEBPACK_IMPORTED_MODULE_1__.keyed)(`cell:${row.id ?? row.value ?? rows.indexOf(row)}:${column.key}`, (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                        <td data-key="${column.key}" data-item="${row.id}_${column.key}">${renderCellValue(row, column)}</td>
                    `))}
                </tr>
            `))}
            </tbody>`;
  }

  function renderCellValue(row, column) {
    if (!column || !row) {
      return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
    }

    let value = row[column.key];

    if (column.renderer === "link") {
      return renderLinkCell(value);
    }

    if (column.key === 'actions' && (!Array.isArray(value) || !value.length) && context.component?.getActionRowItems) {
      value = context.component.getActionRowItems(row);
    }

    if (column.component) {
      if (value !== null && typeof value !== "undefined" && (!Array.isArray(value) || value.length)) {
        return renderCellItem(value, context.component);
      }

      if (column.key === "settings" && (!value || Array.isArray(value) && !value.length)) {
        return renderComponent({
          type: "icon-button",
          props: {
            size: "1",
            type: "link",
            scheme: "on-surface",
            icon: "menu",
            ariaLabel: "Меню",
            value: "menu"
          }
        }, context.component);
      }

      return renderComponent(column.rowComponent || column.component, context.component);
    }

    if (value === null || typeof value === "undefined") {
      return lit__WEBPACK_IMPORTED_MODULE_0__.nothing;
    }

    if (Array.isArray(value)) {
      if (column.key === 'actions') {
        value = value.filter(el => {
          const {
            actions
          } = state;
          const actionId = el.id || el.component?.props?.value;
          const action = actions.find(action => action.id === actionId);
          return action?.visible ?? true;
        });
      }

      return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                <div class="flex items-cross-center gap-1/3">
                    ${value.map(item => renderCellItem(item, context.component))}
                </div>
            `;
    }

    if (typeof value === "object") {
      return renderCellItem(value, context.component);
    }

    return value;
  }

  return (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
        <section
                class="sf-table min-w-0 flex flex-col gap-1/3 flex-1 min-h-0 ${context.rootClass || ""}"
                style=${context.rootStyle || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
                aria-label=${context.ariaLabel || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
                aria-busy=${context.dataState === 'loading' ? 'true' : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
        >
            ${renderToolbar(context)}

            <div class="table-wrap min-w-0 max-w-full flex flex-col flex-1 min-h-0">
                <div class="min-h-0 min-w-0 overflow-auto flex-1 relative m-bottom-2">
                    <table
                            ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_2__.ref)(context.component.refs.table)}
                            class="table border-separate border-spacing-0 min-w-full w-max table-fixed transition"
                            aria-label=${context.ariaLabel || lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
                    >
                        ${renderColgroup(context)}
                        ${renderHead(context)}
                        ${renderBody(context)}
                    </table>
                </div>

                ${context.pagin ? (0,lit__WEBPACK_IMPORTED_MODULE_0__.html)`
                            <sf-pagination
                                    current="1"
                                    total=${String(context.paginationTotal)}
                                    bottom="true"
                                    show-action-for-all
                                    page-size=${String(context.paginationPageSize)}
                                    page-sizes=${context.pageSizes}
                                    show-page-size
                                    top-class="content-main-center"
                                    main-class="items-cross-center content-main-between"
                                    bottom-class="items-cross-center gap-2"
                            ></sf-pagination>` : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
            </div>
        </section>
        ${state.contextMenu?.open ? context.component.renderContextMenu(context.state.contextMenu) : lit__WEBPACK_IMPORTED_MODULE_0__.nothing}
        <sf-modal @modal:after-close=${() => {
    context.component.set({
      modalOpen: false
    });
  }} ?open=${state.modalOpen} root-class="sf-modal-panel" id="modal" position="inline-end"
                  title="Ticket #183743503490">
            <div slot="content">This is modal</div>
        </sf-modal>
    `;
}

/***/ },

/***/ "a17b6611091c"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   normalizeTableLinkCell: () => (/* binding */ normalizeTableLinkCell),
/* harmony export */   normalizeTableLinkHref: () => (/* binding */ normalizeTableLinkHref)
/* harmony export */ });
const SAFE_RELATIVE_PREFIXES = ["/", "./", "../", "#", "?"];
function normalizeTableLinkHref(value) {
  if (typeof value !== "string") {
    return null;
  }

  const href = value.trim();

  if (!href) {
    return null;
  }

  if (href.startsWith("//")) {
    return null;
  }

  if (SAFE_RELATIVE_PREFIXES.some(prefix => href.startsWith(prefix))) {
    return href;
  }

  try {
    const url = new URL(href);
    return url.protocol === "http:" || url.protocol === "https:" ? href : null;
  } catch {
    return null;
  }
}
function normalizeTableLinkCell(value) {
  if (!value || typeof value !== "object" || Array.isArray(value)) {
    return null;
  }

  const text = value.text === null || typeof value.text === "undefined" ? "" : String(value.text);
  const subtext = value.subtext === null || typeof value.subtext === "undefined" ? null : String(value.subtext);
  return {
    href: normalizeTableLinkHref(value.href),
    subtext,
    text
  };
}

/***/ },

/***/ "7d99d770602e"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


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

/***/ "eff145474937"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   keyed: () => (/* binding */ i)
/* harmony export */ });
/* harmony import */ var _lit_html_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("f550d360fd36");
/* harmony import */ var _directive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("0e2a9b296d0b");
/* harmony import */ var _directive_helpers_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("a5a956c9f619");

/**
 * @license
 * Copyright 2021 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const i=(0,_directive_js__WEBPACK_IMPORTED_MODULE_1__.directive)(class extends _directive_js__WEBPACK_IMPORTED_MODULE_1__.Directive{constructor(){super(...arguments),this.key=_lit_html_js__WEBPACK_IMPORTED_MODULE_0__.nothing}render(r,t){return this.key=r,t}update(r,[t,e]){return t!==this.key&&((0,_directive_helpers_js__WEBPACK_IMPORTED_MODULE_2__.setCommittedValue)(r),this.key=t),e}});
//# sourceMappingURL=keyed.js.map


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

/***/ "707122d76f9d"
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   keyed: () => (/* reexport safe */ lit_html_directives_keyed_js__WEBPACK_IMPORTED_MODULE_0__.keyed)
/* harmony export */ });
/* harmony import */ var lit_html_directives_keyed_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("eff145474937");

//# sourceMappingURL=keyed.js.map


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
/* harmony import */ var _js_templates_default__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("b53fd744fdaf");
/* harmony import */ var lit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("fef8077ac919");
/* harmony import */ var lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("7fcbcc00731e");
/* harmony import */ var _helpers_dom__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("926043d18700");
/* harmony import */ var _helpers_draggable__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("2d094259808e");
/* harmony import */ var _helpers_loaderDragState__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("870b5dfddc25");








class SfTable extends _core_js_smart_base__WEBPACK_IMPORTED_MODULE_0__["default"] {
  static get props() {
    return {
      templateName: {
        attribute: "template",
        default: "default"
      },
      ariaLabel: {
        attribute: "aria-label",
        default: "Data table"
      },
      selectable: {
        type: Boolean,
        default: true
      },
      settings: {
        type: Boolean,
        default: true
      },
      pagin: {
        type: Boolean,
        default: false
      },
      actions: {
        type: Boolean,
        default: true
      },
      filterText: {
        attribute: "filter-text",
        default: "Фильтр"
      },
      clearText: {
        attribute: "clear-text",
        default: "Очистить"
      },
      searchPlaceholder: {
        attribute: "search-placeholder",
        default: "Поиск"
      },
      createText: {
        attribute: "create-text",
        default: "Создать"
      },
      paginationTotal: {
        attribute: "pagination-total",
        type: Number,
        default: 10
      },
      paginationPageSize: {
        attribute: "pagination-page-size",
        type: Number,
        default: 10
      },
      pageSizes: {
        attribute: "page-sizes",
        default: "10,20,30,40"
      },
      contextMenuColumns: {
        attribute: "context-menu-columns",
        type: Number,
        default: 2
      },
      tableSettingsKey: {
        attribute: "table-settings-key",
        default: "users"
      },
      dataState: {
        attribute: "data-state",
        default: "auto",
        values: ["auto", "loading", "populated", "empty", "error"]
      },
      loadingText: {
        attribute: "loading-text",
        default: "Загрузка данных"
      },
      emptyText: {
        attribute: "empty-text",
        default: "Нет данных"
      },
      errorText: {
        attribute: "error-text",
        default: "Не удалось загрузить данные"
      },
      retryText: {
        attribute: "retry-text",
        default: "Повторить"
      }
    };
  }

  constructor() {
    super();
    this._portalContainer = null;
    this._contextViewportBound = false;
    this._filterTemplateDragCleanups = [];
    this.contextKeyEvent = this.contextKeyEvent.bind(this);
    this.contextViewportEvent = this.contextViewportEvent.bind(this);
    this.ACTIONS_SETTINGS_ROW = [{
      name: 'Редактировать',
      id: 'edit',
      component: {
        type: 'icon-button',
        props: {
          variant: 'icon',
          size: '1',
          type: 'link',
          scheme: 'on-surface',
          icon: 'edit',
          ariaLabel: 'Редактировать',
          value: 'edit'
        }
      }
    }, {
      name: 'Посмотреть',
      id: 'view',
      component: {
        type: 'icon-button',
        props: {
          variant: 'icon',
          size: '1',
          type: 'link',
          scheme: 'on-surface',
          icon: 'visibility',
          ariaLabel: 'Посмотреть',
          value: 'view',
          "@click": () => {
            console.log("CLICK");
            this.set({
              modalOpen: true
            });
          }
        }
      }
    }, {
      name: 'Удалить',
      id: 'delete',
      component: {
        type: 'icon-button',
        props: {
          variant: 'icon',
          size: '1',
          type: 'link',
          scheme: 'on-surface',
          icon: 'delete',
          ariaLabel: 'Удалить',
          value: 'delete'
        }
      }
    }];
    this.TABLE_ACTIONS_COLUMN = {
      key: "actions",
      label: "Действия"
    };
    this.TABLE_SETTINGS_COLUMN = {
      key: "settings",
      label: "settings",
      system: true,
      width: 'calc(var(--sf-text-height-1) + (var(--sf-ui-1--space-y-tightness-default) * 2) + var(--sf-b6))',
      component: {
        type: "icon-button",
        props: {
          size: "1",
          type: "link",
          scheme: "on-surface",
          icon: "settings",
          ariaLabel: "Настройки",
          value: "settings",
          '@click': e => {
            this.openContextMenu(e, {
              menu: 'table-settings'
            });
          }
        }
      }
    };
    this.contextEvent = this.contextEvent.bind(this);
    this.hoverEvents = this.hoverEvents.bind(this);
    this.outEvent = this.outEvent.bind(this);
    this.onColumnResizePointerDown = this.onColumnResizePointerDown.bind(this);
    this.onColumnResizePointerMove = this.onColumnResizePointerMove.bind(this);
    this.onColumnResizePointerUp = this.onColumnResizePointerUp.bind(this);
    this.onColumnDragPointerDown = this.onColumnDragPointerDown.bind(this);
    this.onColumnDragPointerMove = this.onColumnDragPointerMove.bind(this);
    this.onColumnDragPointerUp = this.onColumnDragPointerUp.bind(this);
    this.onColumnDragPointerCancel = this.onColumnDragPointerCancel.bind(this);
    this._contextEventBound = false;
    this._hoverEventBound = false;
    this._columnResizeEventBound = false;
    this._columnDragEventBound = false;
    this._columnResizeState = null;
    this._columnDragState = null;
    this._columnAnimationToken = 0;
    this.searchChange = this.scheduleSearchChange.bind(this);
    this._searchDebounceTimer = null;
    this._searchPendingProps = null;
    this._searchHoldKey = '';
    this.refs = {
      contextMenu: this.createRef(),
      items: new Map(),
      templateForm: this.createRef(),
      renameInput: this.createRef(),
      table: this.createRef()
    };
    this._checkboxMeasureToken = 0;
    this._filterTemplatesLoaded = false;
    this.tempSettings = null;
    this.state = {
      settingsChecked: false,
      modalOpen: false,
      saveInputValue: '',
      search: {
        searchActive: false,
        value: ''
      },
      contextMenu: {
        open: false
      },
      actions: this.getActions().map(action => Object.assign(action, {
        visible: true
      })),
      actionActive: this.getProp('actions'),
      columnSettings: {},
      selectIndeterminate: false,
      filterDataDirty: false,
      filter: {
        selectedTemplateKey: 'default',
        fields: [],
        templates: [],
        edit: {
          rename: ''
        }
      }
    };
    this.TABLE_SELECT_COLUMN = {
      key: "select",
      label: "select",
      system: true,
      width: 'calc(var(--sf-text-height-1) + var(--sf-b6) + calc(var(--sf-px) * 2))',
      component: {
        type: "checkbox",
        props: {
          position: 'end',
          rootClass: 'content-main-between',
          size: 1,
          '@change': e => {
            const {
              checked
            } = e.target;
            this.set({
              selectIndeterminate: false,
              settingsChecked: checked
            });
            this.updateRows(row => ({ ...row,
              select: this.createRowComponentCell(this.TABLE_SELECT_COLUMN.rowComponent, {
                checked: checked,
                value: row.id ?? row.value ?? ""
              })
            }), "select-all");
          },
          indeterminate: this.state.selectIndeterminate,
          checked: this.state.settingsChecked
        }
      },
      rowComponent: {
        type: "checkbox",
        props: {
          position: 'end',
          rootClass: 'content-main-between',
          size: 1,
          "@change": e => {
            const {
              checked
            } = e.target;
            let key = e.target.closest('td').dataset.item;
            key = key.split('_');
            this.updateRowCellProps("id", +key[0], key[1], {
              checked
            });
            const indeterminate = this.setIndeterminate();
            this.set({
              settingsChecked: !indeterminate && this.state.settingsChecked ? false : this.state.settingsChecked,
              selectIndeterminate: indeterminate
            });
          }
        }
      }
    };
  }

  getActions() {
    if (this.getProp('actions')) {
      return this.ACTIONS_SETTINGS_ROW.map(action => {
        const {
          name,
          id
        } = action;
        return {
          name,
          id
        };
      });
    } else {
      return [];
    }
  }

  isSearchHoldKey(key = '') {
    return key === 'Backspace' || key === 'Delete';
  }

  clearSearchDebounce() {
    if (this._searchDebounceTimer) {
      clearTimeout(this._searchDebounceTimer);
      this._searchDebounceTimer = null;
    }
  }

  scheduleSearchChange(props = {}) {
    this._searchPendingProps = props;
    this.clearSearchDebounce();

    if (this._searchHoldKey) {
      return this;
    }

    this._searchDebounceTimer = setTimeout(() => {
      const pendingProps = this._searchPendingProps || {};
      this._searchPendingProps = null;
      this._searchDebounceTimer = null;
      this.updateSearchState(pendingProps);
    }, 500);
    return this;
  }

  handleSearchKeyDown(event) {
    if (!this.isSearchHoldKey(event?.key)) {
      return this;
    }

    this._searchHoldKey = event.key;
    this.clearSearchDebounce();
    return this;
  }

  handleSearchKeyUp(event) {
    if (!this._searchHoldKey || event?.key !== this._searchHoldKey) {
      return this;
    }

    this._searchHoldKey = '';

    if (this._searchPendingProps) {
      this.scheduleSearchChange(this._searchPendingProps);
    }

    return this;
  }

  updateSearchState(props = {}, isActiveState = true) {
    const currentSearch = this.state.search || {};
    let needUpdate = false;

    for (const [key, value] of Object.entries(props)) {
      if (!Object.is(currentSearch[key], value)) {
        needUpdate = true;
        break;
      }
    }

    if (!needUpdate) return this;
    this.set(prevState => {
      return {
        search: { ...prevState.search,
          ...props
        }
      };
    }, () => {
      if (!isActiveState) return false;
      this.dispatchEvent(new CustomEvent('onSearchEnd', {
        bubbles: true,
        composed: true,
        detail: this.state.search.value
      }));
      this.dispatchTableEvent('sf-table-search-change', this.state.search.value);
    });
  }

  dispatchTableEvent(name, detail = null) {
    this.dispatchEvent(new CustomEvent(name, {
      bubbles: true,
      composed: true,
      detail
    }));
    return this;
  }

  getItemRef(id) {
    if (!this.refs.items.has(id)) {
      this.refs.items.set(id, this.createRef());
    }

    return this.refs.items.get(id);
  }

  renderSmartElement(type, props = {}) {
    const tagName = String(type || '').startsWith('sf-') ? String(type) : `sf-${String(type || '')}`;

    if (!/^sf-[a-z0-9-]+$/.test(tagName)) {
      throw new Error(`Invalid SF tag name: ${tagName}`);
    }

    const element = document.createElement(tagName);
    Object.entries(props || {}).forEach(([key, value]) => {
      if (key === ':key' || typeof value === 'undefined' || value === null) {
        return;
      }

      if (key === ':ref') {
        if (typeof value === 'function') {
          value(element);
        } else if (value && typeof value === 'object') {
          value.value = element;
        }

        return;
      }

      if (key.startsWith('@') && typeof value === 'function') {
        element.addEventListener(key.slice(1), value);
        return;
      }

      if (/^on[A-Z]/.test(key) && typeof value === 'function') {
        element.addEventListener(key.slice(2).toLowerCase(), value);
        return;
      }

      const attributeName = (0,_core_js_smart_base__WEBPACK_IMPORTED_MODULE_0__.toAttributeName)(key);

      if (typeof value === 'boolean') {
        element.toggleAttribute(attributeName, value);
        return;
      }

      if (typeof value === 'object' || typeof value === 'function') {
        element[key] = value;
        return;
      }

      element.setAttribute(attributeName, String(value));
    });
    return element;
  }

  setIndeterminate() {
    const checked = this.state.data.rows.filter(row => {
      return row.select.component.props.checked;
    });
    return checked.length;
  }

  getTableData() {
    const data = this.state?.data;
    return data && typeof data === "object" ? data : {
      columns: [],
      rows: []
    };
  }

  isContextMenuEvent(event) {
    const target = event?.target;

    if (!(target instanceof Node)) {
      return false;
    }

    const portal = this._portalContainer;

    if (portal instanceof Node && (portal === target || portal.contains(target))) {
      return true;
    }

    const contextMenu = this.refs?.contextMenu?.value;

    if (!(contextMenu instanceof Node)) {
      return false;
    }

    if (contextMenu === target || contextMenu.contains(target)) {
      return true;
    }

    return event.composedPath?.().includes(contextMenu) || false;
  }

  contextEvent(event) {
    if (this.isContextMenuEvent(event)) {
      return;
    }

    this.closeContextMenu(false);
  }

  splitItemsByColumns(items = [], columnCount = 1) {
    const normalizedItems = Array.isArray(items) ? items : [];
    const normalizedColumnCount = Math.max(1, Number(columnCount) || 1);
    const itemsPerColumn = Math.ceil(normalizedItems.length / normalizedColumnCount);

    if (!itemsPerColumn) {
      return [];
    }

    return Array.from({
      length: normalizedColumnCount
    }, (_, index) => normalizedItems.slice(index * itemsPerColumn, (index + 1) * itemsPerColumn)).filter(columnItems => columnItems.length);
  }

  getPendingFilterState(filter = this.state?.filter || {}) {
    const tempFilter = this.tempSettings?.filter || {};
    const hasTempSelectedTemplateKey = Object.prototype.hasOwnProperty.call(tempFilter, "selectedTemplateKey");
    return { ...filter,
      ...tempFilter,
      selectedTemplateKey: hasTempSelectedTemplateKey ? tempFilter.selectedTemplateKey : filter.selectedTemplateKey,
      templates: tempFilter.templates || filter.templates || []
    };
  }

  getSortedFilterTemplates(filter = this.state?.filter || {}) {
    const templates = this.getFilterTemplates(filter);
    return templates.map((template, sourceIndex) => ({ ...template,
      sourceIndex,
      order: typeof template.order === "number" ? template.order : sourceIndex
    })).sort((left, right) => {
      if (Boolean(left.pinned) !== Boolean(right.pinned)) {
        return left.pinned ? -1 : 1;
      }

      if (left.order !== right.order) {
        return left.order - right.order;
      }

      return left.sourceIndex - right.sourceIndex;
    });
  }

  changeTempFilterTemplateSettings(key, patch = {}) {
    if (!key) return;
    const currentFilter = this.getPendingFilterState();
    const templates = this.getFilterTemplates(currentFilter).map(template => {
      for (const [k, v] of Object.entries(patch)) {
        template[k] = key === template.key ? v : !v;
      }

      return template;
    });
    this.tempSettings = { ...(this.tempSettings || {}),
      filter: { ...(this.tempSettings?.filter || {}),
        selectedTemplateKey: currentFilter.selectedTemplateKey,
        templates
      }
    };
    this.requestComponentUpdate("temp-filter-template");
  }

  patchTempFilterTemplateSettings(key, patch = {}) {
    if (!key) return;
    const currentFilter = this.getPendingFilterState();
    const templates = this.getFilterTemplates(currentFilter).map(template => template.key === key ? { ...template,
      ...(typeof patch === "function" ? patch(template) : patch)
    } : template);
    this.tempSettings = { ...(this.tempSettings || {}),
      filter: { ...(this.tempSettings?.filter || {}),
        selectedTemplateKey: currentFilter.selectedTemplateKey,
        templates
      }
    };
    this.requestComponentUpdate("temp-filter-template");
  }

  selectTempFilterTemplate(key) {
    if (!key) return;
    const currentFilter = this.getPendingFilterState();
    const templates = this.getFilterTemplates(currentFilter).map(template => ({ ...template,
      selected: template.key === key
    }));
    this.tempSettings = { ...(this.tempSettings || {}),
      filter: { ...(this.tempSettings?.filter || {}),
        selectedTemplateKey: key,
        templates
      }
    };
    this.requestComponentUpdate("temp-filter-template");
  }

  normalizeFilterTemplate(template = {}, sourceIndex = 0) {
    const key = template.key || `filter_template_${sourceIndex + 1}`;
    const data = template.data && typeof template.data === "object" ? template.data : {};
    return { ...template,
      key,
      label: template.label || data.template_name || key,
      data: { ...data,
        values: { ...(data.values || {})
        },
        tags: { ...(data.tags || {})
        }
      }
    };
  }

  getFilterTemplates(filter = this.state?.filter || {}) {
    const templates = (filter.templates || []).map((template, index) => this.normalizeFilterTemplate(template, index));

    if (templates.length) {
      return templates;
    }

    return [this.normalizeFilterTemplate({
      key: filter.selectedTemplateKey || "default",
      label: "По умолчанию",
      selected: true,
      order: 0,
      data: {}
    })];
  }

  getFilterTemplateByKey(key, filter = this.state?.filter || {}) {
    if (!key || !filter) return null;
    return this.getFilterTemplates(filter).find(template => template.key === key) || null;
  }

  getSelectedFilterTemplate(filter = this.state?.filter || {}) {
    const templates = this.getFilterTemplates(filter);
    const hasSelectedTemplateKey = Object.prototype.hasOwnProperty.call(filter, "selectedTemplateKey");

    if (hasSelectedTemplateKey && filter.selectedTemplateKey === "") {
      return null;
    }

    const selectedKey = filter.selectedTemplateKey || templates.find(template => template.selected)?.key || templates[0]?.key || "";
    return this.getFilterTemplateByKey(selectedKey, { ...filter,
      templates
    }) || templates[0] || null;
  }

  getCurrentFilterValues(filter = this.state?.filter || {}) {
    const selectedTemplate = this.getSelectedFilterTemplate(filter);
    return { ...(selectedTemplate?.data?.values || {})
    };
  }

  getCurrentFilterTags(filter = this.state?.filter || {}) {
    const selectedTemplate = this.getSelectedFilterTemplate(filter);
    return { ...(selectedTemplate?.data?.tags || {})
    };
  }

  normalizeFilterDataForCompare(value) {
    if (Array.isArray(value)) {
      return value.map(item => this.normalizeFilterDataForCompare(item));
    }

    if (value && typeof value === "object") {
      return Object.keys(value).sort().reduce((result, key) => ({ ...result,
        [key]: this.normalizeFilterDataForCompare(value[key])
      }), {});
    }

    return value;
  }

  isSameFilterData(current, next) {
    return JSON.stringify(this.normalizeFilterDataForCompare(current || {})) === JSON.stringify(this.normalizeFilterDataForCompare(next || {}));
  }

  getMergedFilterDraftData(filter = this.state?.filter || {}, tempFilter = this.tempSettings?.filter || {}) {
    const baseFilter = this.getPendingFilterState({ ...filter,
      selectedTemplateKey: Object.prototype.hasOwnProperty.call(tempFilter, "selectedTemplateKey") ? tempFilter.selectedTemplateKey : filter.selectedTemplateKey,
      templates: tempFilter.templates || filter.templates || []
    });
    const currentTags = this.getCurrentFilterTags(baseFilter);
    const currentValues = this.getCurrentFilterValues(baseFilter);
    const tags = tempFilter.replaceTags ? { ...(tempFilter.tags || {})
    } : { ...currentTags,
      ...(tempFilter.tags || {})
    };
    const values = tempFilter.replaceValues ? { ...(tempFilter.values || {})
    } : { ...currentValues,
      ...(tempFilter.values || {})
    };
    Object.entries(tempFilter.values || {}).forEach(([key, value]) => {
      if (!this.isEmptyFilterEntry(value)) {
        return;
      }

      delete values[key];
      delete tags[key];
    });
    return {
      filter: baseFilter,
      tags,
      values
    };
  }

  hasPendingFilterDataChanges(filter = this.state?.filter || {}) {
    const tempFilter = this.tempSettings?.filter;

    if (!tempFilter || !Object.prototype.hasOwnProperty.call(tempFilter, "tags") && !Object.prototype.hasOwnProperty.call(tempFilter, "values") && !tempFilter.replaceTags && !tempFilter.replaceValues) {
      return false;
    }

    const draft = this.getMergedFilterDraftData(filter, tempFilter);
    return !this.isSameFilterData(this.getCurrentFilterTags(draft.filter), draft.tags) || !this.isSameFilterData(this.getCurrentFilterValues(draft.filter), draft.values);
  }

  hasUnsavedFilterDataChanges() {
    return Boolean(this.state?.filterDataDirty && this.getSelectedFilterTemplate(this.state?.filter || {}));
  }

  saveCurrentFilterTemplateData() {
    const template = this.getSelectedFilterTemplate(this.state?.filter || {});

    if (!template) {
      return this;
    }

    return this.set({
      filterDataDirty: false
    }, () => {
      this.dispatchFilterTemplateSave(template.key, this.state.filter, this.getFilterTemplateByKey(template.key));
    });
  }

  mergeObjectDeep(target = {}, patch = {}) {
    return Object.entries(patch || {}).reduce((result, [key, value]) => {
      const currentValue = result[key];
      const shouldMerge = value && typeof value === "object" && !Array.isArray(value) && currentValue && typeof currentValue === "object" && !Array.isArray(currentValue);
      return { ...result,
        [key]: shouldMerge ? this.mergeObjectDeep(currentValue, value) : value
      };
    }, { ...(target || {})
    });
  }

  patchFilterTemplateByKey(key, filter = this.state.filter, props = {}) {
    if (!key || !filter) return {
      filter,
      template: null
    };
    let patchedTemplate = null;
    const nextFilter = { ...filter,
      templates: this.getFilterTemplates(filter).map(template => {
        if (key !== template.key) {
          return template;
        }

        const nextPatch = typeof props === "function" ? props(template) : props;
        patchedTemplate = this.mergeObjectDeep(template, nextPatch);
        return patchedTemplate;
      })
    };
    return {
      filter: nextFilter,
      template: patchedTemplate
    };
  }

  createFilterTemplatePatchState(filter, options = {}) {
    const nextState = {
      filter
    };

    if (Object.prototype.hasOwnProperty.call(options, "saveInputValue")) {
      nextState.saveInputValue = options.saveInputValue;
    }

    return nextState;
  }

  dispatchFilterTemplateSave(key, filter, template = null) {
    const detail = template || this.getFilterTemplateByKey(key, filter);
    this.dispatchEvent(new CustomEvent("onTemplateSave", {
      bubbles: true,
      composed: true,
      detail
    }));
    this.dispatchTableEvent('sf-table-template-save', detail);
    return this;
  }

  getChangedFilterTemplates(prevFilter = {}, nextFilter = {}, keys = []) {
    const prevTemplates = new Map(this.getFilterTemplates(prevFilter).map(template => [template.key, template]));
    return this.getFilterTemplates(nextFilter).filter(template => {
      const prevTemplate = prevTemplates.get(template.key);

      if (!prevTemplate) {
        return true;
      }

      return keys.some(key => !Object.is(prevTemplate[key], template[key]));
    });
  }

  dispatchFilterTemplatesSave(templates = []) {
    const filteredTemplates = templates.filter(template => template?.key);

    if (!filteredTemplates.length) {
      return this;
    }

    const detail = filteredTemplates.length === 1 ? filteredTemplates[0] : filteredTemplates;
    this.dispatchEvent(new CustomEvent("onTemplateSave", {
      bubbles: true,
      composed: true,
      detail
    }));
    this.dispatchTableEvent('sf-table-template-save', detail);
    return this;
  }

  saveFilterTemplatePatch(key, props = {}, options = {}, filter = this.state.filter) {
    if (!key || !filter) return this;
    const patched = this.patchFilterTemplateByKey(key, filter, props);
    const nextFilter = { ...patched.filter,
      ...(options.filterPatch || {})
    };
    return this.set(() => this.createFilterTemplatePatchState(nextFilter, options), () => {
      if (options.dispatch !== false) {
        this.dispatchFilterTemplateSave(key, nextFilter, patched.template);
      }
    });
  }

  setDefaultFilterTemplate(key) {
    if (!key) return this;
    return this.set(prevState => {
      const currentFilter = prevState.filter || {};
      const filter = { ...currentFilter,
        templates: this.getFilterTemplates(currentFilter).map(template => ({ ...template,
          default: template.key === key
        }))
      };
      return {
        filter
      };
    });
  }

  patchFilterTemplateData(filter = {}, patch = {}) {
    const filterRest = { ...filter
    };
    delete filterRest.tags;
    delete filterRest.values;
    const templates = this.getFilterTemplates(filterRest);
    const selectedTemplate = this.getSelectedFilterTemplate({ ...filterRest,
      templates
    });

    if (!selectedTemplate) {
      return { ...filterRest,
        templates
      };
    }

    const nextValues = Object.prototype.hasOwnProperty.call(patch, "values") ? patch.values || {} : selectedTemplate.data?.values || {};
    const nextTags = Object.prototype.hasOwnProperty.call(patch, "tags") ? patch.tags || {} : selectedTemplate.data?.tags || {};
    const nextTemplates = templates.map(template => template.key === selectedTemplate.key ? { ...template,
      data: { ...(template.data || {}),
        ...(patch.data || {}),
        values: nextValues,
        tags: nextTags
      }
    } : template);
    return { ...filterRest,
      selectedTemplateKey: selectedTemplate.key,
      templates: nextTemplates
    };
  }

  getFilterPayload(filter = this.state?.filter || {}) {
    const selectedTemplate = this.getSelectedFilterTemplate(filter);
    return { ...(selectedTemplate?.data || {}),
      templateKey: selectedTemplate?.key || "",
      templateLabel: selectedTemplate?.label || "",
      values: this.getCurrentFilterValues(filter),
      tags: this.getCurrentFilterTags(filter)
    };
  }

  selectFilterTemplate(key) {
    if (!key || this.state?.filter?.selectedTemplateKey === key) {
      return this;
    }

    this.set(prevState => {
      const currentFilter = prevState.filter || {};
      const nextFilter = { ...currentFilter,
        selectedTemplateKey: key,
        templates: (prevState.filter?.templates || []).map(template => ({ ...template,
          selected: template.key === key
        }))
      };
      const selectedTemplate = this.getFilterTemplateByKey(key, nextFilter) || nextFilter.templates[0] || null;
      return {
        filter: nextFilter,
        saveInputValue: selectedTemplate?.label || ""
      };
    });
    return this;
  }

  bindFilterTemplateDrag() {
    this.clearFilterTemplateDragBindings();
    const contextMenu = this.refs?.contextMenu?.value;
    const container = contextMenu?.querySelector?.("[data-filter-template-list]");
    if (!container) return;
    container.querySelectorAll("[data-filter-template-key]").forEach(item => {
      const handle = item.querySelector("[data-move]");
      const cleanup = (0,_helpers_draggable__WEBPACK_IMPORTED_MODULE_5__.bindSortableDrag)({
        handle,
        item,
        container,
        boundAttr: "data-sf-table-template-drag-bound",
        getSortableItems: (currentContainer, currentItem) => this.getFilterTemplateSortableItems(currentContainer, currentItem?.dataset?.pinned === "1"),
        getAfterElement: (currentContainer, y) => this.getFilterTemplateDragAfterElement(currentContainer, y, item.dataset.pinned === "1"),
        insertPlaceholder: (currentContainer, placeholder, afterElement) => {
          this.insertFilterTemplatePlaceholder(currentContainer, placeholder, afterElement, item.dataset.pinned === "1");
        },
        onDrop: () => {
          this.syncFilterTemplateOrderFromContainer(container);
        }
      });

      if (cleanup) {
        this._filterTemplateDragCleanups.push(cleanup);
      }
    });
  }

  clearFilterTemplateDragBindings() {
    this._filterTemplateDragCleanups.forEach(cleanup => cleanup());

    this._filterTemplateDragCleanups = [];
  }

  getFilterTemplateSortableItems(container, pinned = false) {
    return Array.from(container?.children || []).filter(item => item instanceof HTMLElement && item.dataset.filterTemplateKey && item.dataset.pinned === (pinned ? "1" : "0") && !item.classList.contains("hidden") && !item.classList.contains("is-dragging") && !item.classList.contains("drag-placeholder"));
  }

  getFilterTemplateDragAfterElement(container, y, pinned = false, itemsLayout = null) {
    return (0,_helpers_draggable__WEBPACK_IMPORTED_MODULE_5__.getVerticalDragAfterElement)(container, y, {
      itemsLayout: Array.isArray(itemsLayout) ? itemsLayout.filter(layout => layout.item?.dataset?.filterTemplateKey && layout.item?.dataset?.pinned === (pinned ? "1" : "0") && !layout.item.classList.contains("hidden")) : null,
      accept: item => item.dataset.filterTemplateKey && item.dataset.pinned === (pinned ? "1" : "0") && !item.classList.contains("hidden")
    });
  }

  insertFilterTemplatePlaceholder(container, placeholder, afterElement, pinned = false) {
    if (afterElement) {
      container.insertBefore(placeholder, afterElement);
      return;
    }

    const group = pinned ? "1" : "0";
    const groupItems = Array.from(container?.children || []).filter(item => item instanceof HTMLElement && item.dataset.filterTemplateKey && item.dataset.pinned === group && !item.classList.contains("is-dragging") && !item.classList.contains("drag-placeholder"));
    const lastGroupItem = groupItems.at(-1);

    if (lastGroupItem?.nextSibling) {
      container.insertBefore(placeholder, lastGroupItem.nextSibling);
      return;
    }

    if (pinned) {
      const firstUnpinnedItem = Array.from(container?.children || []).find(item => item instanceof HTMLElement && item.dataset.filterTemplateKey && item.dataset.pinned === "0" && !item.classList.contains("is-dragging") && !item.classList.contains("drag-placeholder"));

      if (firstUnpinnedItem) {
        container.insertBefore(placeholder, firstUnpinnedItem);
        return;
      }
    }

    container.appendChild(placeholder);
  }

  syncFilterTemplateOrderFromContainer(container) {
    const orderedByGroup = {
      pinned: new Map(),
      normal: new Map()
    };
    Array.from(container?.children || []).filter(item => item instanceof HTMLElement && item.dataset.filterTemplateKey && !item.classList.contains("drag-placeholder")).forEach(item => {
      const group = item.dataset.pinned === "1" ? "pinned" : "normal";
      orderedByGroup[group].set(item.dataset.filterTemplateKey, orderedByGroup[group].size);
    });
    const currentFilter = this.getPendingFilterState();
    const templates = this.getFilterTemplates(currentFilter).map((template, sourceIndex) => {
      const group = template.pinned ? "pinned" : "normal";
      const nextOrder = orderedByGroup[group].get(template.key);

      if (typeof nextOrder !== "number") {
        return template;
      }

      return { ...template,
        order: nextOrder,
        sourceIndex
      };
    });
    this.tempSettings = { ...(this.tempSettings || {}),
      filter: { ...(this.tempSettings?.filter || {}),
        selectedTemplateKey: currentFilter.selectedTemplateKey,
        templates: templates.sort((left, right) => {
          if (Boolean(left.pinned) !== Boolean(right.pinned)) {
            return left.pinned ? -1 : 1;
          }

          if (left.order !== right.order) {
            return left.order - right.order;
          }

          return left.sourceIndex - right.sourceIndex;
        })
      }
    };
    this.requestComponentUpdate("temp-filter-template-order");
  }

  getSystemColumns() {
    const columns = [];
    const props = this.getPropsContext();

    if (props.selectable) {
      columns.push(this.getSelectColumn());
    }

    if (props.settings) {
      columns.push(this.TABLE_SETTINGS_COLUMN);
    }

    if (props.actions && this.state.actionActive) {
      columns.push(this.TABLE_ACTIONS_COLUMN);
    }

    return columns;
  }

  setTemplates(data) {
    const nextTemplates = Object.values(data || {}).map((template, index) => this.normalizeFilterTemplate(template, index));
    return this.set(prevState => {
      const currentFilter = prevState.filter || {};
      const templatesByKey = new Map();
      this.getFilterTemplates(currentFilter).forEach(template => {
        templatesByKey.set(template.key, template);
      });
      nextTemplates.forEach(template => {
        templatesByKey.set(template.key, { ...(templatesByKey.get(template.key) || {}),
          ...template
        });
      });
      const templates = Array.from(templatesByKey.values());
      const hasCurrentSelectedTemplateKey = Object.prototype.hasOwnProperty.call(currentFilter, "selectedTemplateKey");
      const defaultTemplate = templates.find(template => template.default === true);
      const incomingSelectedKey = nextTemplates.find(template => template?.selected)?.key;
      const fallbackSelectedKey = defaultTemplate?.key || templates[0]?.key || "";
      const selectedTemplateKey = this._filterTemplatesLoaded ? incomingSelectedKey ?? (hasCurrentSelectedTemplateKey ? currentFilter.selectedTemplateKey : fallbackSelectedKey) : incomingSelectedKey ?? fallbackSelectedKey;
      const nextFilter = { ...currentFilter,
        selectedTemplateKey,
        templates: templates.map(template => ({ ...template,
          selected: template.key === selectedTemplateKey
        }))
      };
      const selectedTemplate = selectedTemplateKey === "" ? null : this.getFilterTemplateByKey(selectedTemplateKey, { ...nextFilter,
        templates
      }) || templates[0] || null;
      this._filterTemplatesLoaded = true;
      return {
        filter: nextFilter,
        saveInputValue: selectedTemplate?.label || ""
      };
    });
  }

  getTableSettingsKey() {
    return this.tableSettingsKey || this.getAttribute?.("table-settings-key") || this.id || "users";
  }

  normalizeTableSettings(data = {}, tableSettingsKey = this.getTableSettingsKey()) {
    const settingsMap = data?.tableSettings && typeof data.tableSettings === "object" && !Array.isArray(data.tableSettings) && !data.tableSettings.columnSettings ? data.tableSettings : null;
    const source = data?.item || settingsMap?.[tableSettingsKey] || data?.tableSettings || data?.settings || data || {};
    const columnSettings = source?.columnSettings || data?.columnSettings || {};
    return { ...source,
      columnSettings: columnSettings && typeof columnSettings === "object" && !Array.isArray(columnSettings) ? columnSettings : {}
    };
  }

  setTableSettings(data = {}, reason = "table-settings-load", tableSettingsKey = this.getTableSettingsKey()) {
    const settings = this.normalizeTableSettings(data, tableSettingsKey);
    return this.setColumnSettings(settings.columnSettings, reason, false);
  }

  getContextMenuLabel(data = {}) {
    switch (data.menu || data.type) {
      case 'filter-favorites':
        return 'Сохранение шаблона фильтра';

      case 'filter-settings':
        return 'Настройки фильтра';

      case 'filter-template-actions':
        return `Действия с шаблоном: ${data.template?.label || data.template?.key || ''}`.trim();

      case 'tag-settings':
        {
          const fieldKey = data.fieldKey || '';
          const field = this.getFilterField(fieldKey);
          const label = field?.label || this.getCurrentFilterTags()?.[fieldKey]?.label || fieldKey;
          return `Настройка фильтра: ${label || 'поле'}`;
        }

      case 'row-settings':
        return 'Действия со строкой';

      case 'table-settings':
      default:
        return 'Настройки таблицы';
    }
  }

  renderFilterFavoritesContextMenu(data) {
    const {
      x,
      y,
      position
    } = data;
    const template = this.getSelectedFilterTemplate();
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <sf-context-menu
                    :key=${x + y + this._renderToken}
                    aria-label=${this.getContextMenuLabel({ ...data,
      menu: 'filter-favorites'
    })}
                    style="
            position: fixed;
            inset-inline-start: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    root-class="sf-table-context-menu flex flex-col"
                    data-context-main-menu
                    position="${position || ""}"
            >
                <div class="flex flex-col flex-1 gap-2" slot="content">
                    <div class="sf-table-context-top"><span class="sf-text-2 bold">Сохранение шаблона</span></div>
                    <div class="sf-table-context-main">
                        <form ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_3__.ref)(this.refs.templateForm)} id="save_template" class="flex flex-col gap-1/2">
                            <sf-input
                                    size="1"
                                    type="filled"
                                    placeholder="Название"
                                    value="${this.state.saveInputValue}"
                                    name="template_name"
                                    @change=${e => {
      this.set({
        saveInputValue: e.target.value
      });
    }}
                                    @input=${e => {
      this.set({
        saveInputValue: e.target.value
      });
    }}
                            ></sf-input>
                            <div class="flex flex-col gap-1/2">
                                <sf-checkbox
                                        name="template_default"
                                        position="end"
                                        root-class="content-main-between"
                                        value="1"
                                        ?checked=${template.data.template_default}
                                        label="Сделать по умолчанию"
                                ></sf-checkbox>
                                <sf-checkbox
                                        name="template_save_for_all"
                                        position="end"
                                        root-class="content-main-between"
                                        value="1"
                                        ?checked=${template.data.template_save_for_all}
                                        label="Сохранить для всех"
                                ></sf-checkbox>
                        </form>
                    </div>
                </div>
                <div class="sf-table-context-bottom flex gap-1/3">
                    <sf-button @click="${() => {
      this.tempSettings = null;
      this.requestComponentUpdate('table');
    }}" type="outline" scheme="on-surface" root-class="flex-1"
                               text="Сбросить"></sf-button>
                    <sf-button
                            ?disabled=${this.state.saveInputValue.length === 0}
                            @click="${() => {
      this.saveFilterTemplate();
    }}" root-class="flex-1" text="Применить"></sf-button>
                </div>
                </div>
            </sf-context-menu>
        `;
  }

  saveFilterTemplate() {
    const form = this.refs.templateForm.value;
    if (!form) return false;
    const formData = Object.fromEntries(new FormData(form));
    const {
      templates
    } = this.state.filter;
    const prevTemplate = templates.filter(template => {
      return template.label === formData.template_name;
    })?.[0];
    const values = this.getCurrentFilterValues();
    const tags = this.getCurrentFilterTags();
    let template = {
      key: prevTemplate ? prevTemplate.key : `filter_template_${templates.length + 1}`,
      label: formData.template_name,
      selected: true,
      data: { ...formData,
        values,
        tags
      }
    };
    this.dispatchEvent(new CustomEvent('onTemplateSave', {
      bubbles: true,
      composed: true,
      detail: template
    }));
    this.dispatchTableEvent('sf-table-template-save', template);
    this.tempSettings = null;
    this.closeContextMenu();
  }

  renderRowSettingsContextMenu(data) {
    const {
      x,
      y,
      position,
      tempVisible,
      transform,
      items = [{
        component: "sf-button",
        props: {
          type: "link",
          scheme: "on-surface",
          iconLeft: "save",
          text: "Сохранить",
          "@click": () => {// this.saveMenuSettings();
          }
        }
      }, {
        component: "sf-button",
        props: {
          type: "link",
          scheme: "on-surface",
          iconLeft: "edit",
          text: "Изменить"
        }
      }, {
        component: "sf-button",
        props: {
          type: "link",
          scheme: "on-surface",
          iconLeft: `visibility${!tempVisible ? "_off" : ""}`,
          text: `${!tempVisible ? "Скрыть" : "Показать"}`,
          "@click": () => {// this.toggleItemVisibility(id, tempVisible);
          }
        }
      }, {
        component: "sf-button",
        props: {
          type: "link",
          scheme: "on-surface",
          iconLeft: "delete",
          text: "Удалить",
          "@click": () => {// this.deleteItem(id);
          }
        }
      }]
    } = data;
    let pos = `translateY(calc(${transform !== undefined ? transform : -50}%))`;

    if (position) {
      switch (position) {
        case "left-bottom":
        case "right-bottom":
          pos = `translateY(calc(${transform !== undefined ? transform : -100}% + var(--sf-context-menu-tail-corner-offset)))`;
          break;
      }
    }

    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <sf-context-menu
                    :key=${x + y}
                    aria-label=${this.getContextMenuLabel({ ...data,
      menu: 'row-settings'
    })}
                    style="
            position: fixed;
            inset-inline-start: calc(${x}px + var(--sf-context-menu-tail-corner-offset));
            inset-block-start: ${y}px;
            transform: ${pos};
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    .items=${items}
                    position="${position || "left"}"
            >
            </sf-context-menu>
        `;
  }

  getColumnItems() {
    const dataColumns = this.getTableData().columns || [];
    const originalIndexByKey = new Map(dataColumns.map((column, index) => [column?.key, index]));

    const getOrder = column => {
      const order = column?.order;
      return order !== null && typeof order !== "undefined" && order !== "" && Number.isFinite(Number(order)) ? Number(order) : originalIndexByKey.get(column?.key) ?? 0;
    };

    return dataColumns.map(column => {
      const settings = this.getColumnSetting(column?.key);
      const nextColumn = { ...column,
        ...settings
      };

      if (settings.width) {
        nextColumn.colWidth = settings.width;
      }

      return nextColumn;
    }).sort((left, right) => {
      const leftOrder = getOrder(left);
      const rightOrder = getOrder(right);

      if (leftOrder === rightOrder) {
        return (originalIndexByKey.get(left.key) ?? 0) - (originalIndexByKey.get(right.key) ?? 0);
      }

      return leftOrder - rightOrder;
    });
  }

  getColumnSettings() {
    const dataSettings = this.getTableData().columnSettings;
    const stateSettings = this.state?.columnSettings;
    const normalizedDataSettings = dataSettings && typeof dataSettings === "object" && !Array.isArray(dataSettings) ? dataSettings : {};
    const normalizedStateSettings = stateSettings && typeof stateSettings === "object" && !Array.isArray(stateSettings) ? stateSettings : {};
    return { ...normalizedDataSettings,
      ...normalizedStateSettings
    };
  }

  getColumnSetting(columnKey) {
    if (!columnKey) return {};
    const settings = this.getColumnSettings()?.[columnKey];
    return settings && typeof settings === "object" && !Array.isArray(settings) ? settings : {};
  }

  normalizeColumnSetting(settings = {}) {
    const nextSettings = {};
    Object.entries(settings || {}).forEach(([key, value]) => {
      if (typeof value === "undefined") {
        return;
      }

      if (key === "colWidth") {
        nextSettings.width = value;
        return;
      }

      nextSettings[key] = value;
    });

    if (typeof nextSettings.order !== "undefined" && nextSettings.order !== null && nextSettings.order !== "") {
      nextSettings.order = Number(nextSettings.order);
    }

    return nextSettings;
  }

  mergeColumnSettings(currentSettings = {}, patch = {}) {
    const nextSettings = { ...(currentSettings || {})
    };
    Object.entries(patch || {}).forEach(([columnKey, columnPatch]) => {
      if (!columnKey || !columnPatch || typeof columnPatch !== "object") {
        return;
      }

      nextSettings[columnKey] = this.normalizeColumnSetting({ ...(nextSettings[columnKey] || {}),
        ...columnPatch
      });
    });
    return nextSettings;
  }

  emitColumnSettingsChange(reason = "column-settings") {
    const detail = {
      reason,
      columnSettings: this.getColumnSettings(),
      tableSettingsKey: this.getTableSettingsKey()
    };
    this.dispatchEvent(new CustomEvent("sf-table:column-settings-change", {
      bubbles: true,
      composed: true,
      detail
    }));
    this.dispatchTableEvent('sf-table-column-settings-change', detail);
    this.dispatchEvent(new CustomEvent("onColumnSettingsChange", {
      bubbles: true,
      composed: true,
      detail
    }));
    return this;
  }

  setColumnSettings(settings = {}, reason = "column-settings", emit = true) {
    const nextSettings = typeof settings === "function" ? settings(this.getColumnSettings()) : settings;
    return this.set({
      columnSettings: nextSettings && typeof nextSettings === "object" ? nextSettings : {}
    }, reason, () => emit ? this.emitColumnSettingsChange(reason) : false);
  }

  patchColumnSettings(columnKey, patch = {}, reason = "column-settings") {
    if (!columnKey) return this;
    const currentSetting = this.getColumnSetting(columnKey);
    const nextPatch = typeof patch === "function" ? patch(currentSetting) : patch;
    return this.patchColumnSettingsBatch({
      [columnKey]: nextPatch || {}
    }, reason);
  }

  patchColumnSettingsBatch(patch = {}, reason = "column-settings") {
    const currentSettings = this.getColumnSettings();
    const nextSettings = this.mergeColumnSettings(currentSettings, patch);

    if (JSON.stringify(currentSettings) === JSON.stringify(nextSettings)) {
      return this;
    }

    return this.setColumnSettings(nextSettings, reason);
  }

  getFilterFields() {
    const fields = this.state?.filter?.fields;
    return Array.isArray(fields) ? fields : [];
  }

  getFilterField(fieldKey) {
    if (!fieldKey) return null;
    return this.getFilterFields().find(field => field?.key === fieldKey) || null;
  }

  setFilterFields(fields = [], reason = "filter-fields") {
    return this.set(prevState => ({
      filter: { ...(prevState.filter || {}),
        fields: Array.isArray(fields) ? fields : []
      }
    }), reason);
  }

  patchTempColumnSettings(columnKey, patch = {}) {
    if (!columnKey) return;
    this.tempSettings = { ...(this.tempSettings || {}),
      cols: { ...(this.tempSettings?.cols || {}),
        [columnKey]: { ...(this.tempSettings?.cols?.[columnKey] || {}),
          ...(typeof patch === "function" ? patch() : patch)
        }
      }
    };
  }

  patchTempFilterTagSettings(columnKey, patch = {}) {
    if (!columnKey) return;
    this.tempSettings = { ...(this.tempSettings || {}),
      filter: { ...(this.tempSettings?.filter || {}),
        tags: { ...(this.tempSettings?.filter?.tags || {}),
          [columnKey]: { ...(this.tempSettings?.filter?.tags?.[columnKey] || {}),
            ...(typeof patch === "function" ? patch() : patch)
          }
        }
      }
    };
  }

  patchTempFilterValue(fieldKey, patch = {}) {
    if (!fieldKey) return;
    const currentValues = this.getCurrentFilterValues(this.getPendingFilterState());
    const currentValue = this.tempSettings?.filter?.values?.[fieldKey] || currentValues[fieldKey] || {};
    const nextPatch = typeof patch === "function" ? patch(currentValue) : patch;
    const nextValue = { ...currentValue,
      ...(nextPatch || {})
    };

    const isSameValue = (current, next) => {
      if (!Array.isArray(current) || !Array.isArray(next)) {
        return Object.is(current, next);
      }

      if (current.length !== next.length) {
        return false;
      }

      return current.every((item, index) => Object.is(item, next[index]));
    };

    const changed = Object.keys(nextValue).some(key => !isSameValue(currentValue[key], nextValue[key]));

    if (!changed) {
      return;
    }

    this.tempSettings = { ...(this.tempSettings || {}),
      filter: { ...(this.tempSettings?.filter || {}),
        values: { ...(this.tempSettings?.filter?.values || {}),
          [fieldKey]: nextValue
        }
      }
    };
    this.requestComponentUpdate('temp-filter-value');
  }

  isEmptyFilterValue(value) {
    if (value === null || typeof value === "undefined") {
      return true;
    }

    if (Array.isArray(value)) {
      return value.every(item => this.isEmptyFilterValue(item));
    }

    if (typeof value === "string") {
      return value.trim() === "";
    }

    if (value && typeof value === "object") {
      return Object.values(value).every(item => this.isEmptyFilterValue(item));
    }

    return false;
  }

  isEmptyFilterEntry(entry) {
    if (!entry || typeof entry !== "object") {
      return this.isEmptyFilterValue(entry);
    }

    if (Object.prototype.hasOwnProperty.call(entry, "values")) {
      return this.isEmptyFilterValue(entry.values);
    }

    if (Object.prototype.hasOwnProperty.call(entry, "value")) {
      return this.isEmptyFilterValue(entry.value);
    }

    return Object.entries(entry).filter(([key]) => !["operator", "control", "type"].includes(key)).every(([, value]) => this.isEmptyFilterValue(value));
  }

  mergeFilterState(prevFilter = {}, tempFilter = {}) {
    const {
      filter,
      tags,
      values
    } = this.getMergedFilterDraftData(prevFilter, tempFilter);
    return this.patchFilterTemplateData(filter, {
      tags,
      values
    });
  }

  getColumnCheckboxItems(options = {}) {
    const {
      checked,
      onChange,
      refPrefix = "column"
    } = options;
    return this.getColumnItems().map(col => {
      const itemRef = this.getItemRef(`${refPrefix}:${col.key}`) || false;
      const isChecked = typeof checked === "function" ? Boolean(checked(col)) : Boolean(checked);
      return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                <sf-checkbox
                    size="1"
                    label=${col.label}
                    position="end"
                    root-class="content-main-between"
                    ?checked=${isChecked}
                    ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_3__.ref)(itemRef)}
                    @change=${event => onChange?.(col, event)}
                ></sf-checkbox>
            `;
    });
  }

  getTableColsData() {
    return this.getColumnCheckboxItems({
      refPrefix: "table-column",
      checked: col => {
        const pending = this.tempSettings?.cols?.[col.key];
        const settings = this.getColumnSetting(col.key);
        return pending?.visible ?? settings.visible ?? col.visible !== false;
      },
      onChange: (col, event) => {
        this.patchTempColumnSettings(col.key, {
          visible: Boolean(event.target?.checked)
        });
      }
    });
  }

  getFilterTagColsData() {
    const fields = this.getFilterFields().filter(field => field?.filter?.enabled !== false);
    const items = fields.length ? fields : this.getColumnItems();
    return items.map(field => {
      const pending = this.tempSettings?.filter?.tags?.[field.key];
      const current = this.getCurrentFilterTags(this.getPendingFilterState())?.[field.key];
      const data = {
        type: "checkbox",
        props: {
          size: 1,
          label: field.label,
          position: 'end',
          rootClass: 'content-main-between',
          checked: pending?.active ?? current?.active ?? false,
          ':ref': this.getItemRef(`filter-tag-column:${field.key}`) || false,
          '@change': event => {
            this.patchTempFilterTagSettings(field.key, {
              active: Boolean(event.target?.checked),
              label: field.label,
              control: field.filter?.control || field.type || field.dataType || 'text'
            });
          }
        }
      };
      return this.renderSmartElement(data.type, data.props);
    });
  }

  getFilterControlValue(fieldKey) {
    return { ...(this.getCurrentFilterValues()?.[fieldKey] || {}),
      ...(this.tempSettings?.filter?.values?.[fieldKey] || {})
    };
  }

  getFilterOptions(field = {}) {
    const filter = field.filter || {};
    const source = filter.optionsSource || {};

    if (Array.isArray(filter.options)) {
      return filter.options;
    }

    if (Array.isArray(source.items)) {
      return source.items;
    }

    return [];
  }

  getFilterOptionSearch(fieldKey) {
    if (!fieldKey) return '';
    return this.tempSettings?.filterSearch?.[fieldKey] || '';
  }

  patchTempFilterOptionSearch(fieldKey, value = '') {
    if (!fieldKey) return;
    this.tempSettings = { ...(this.tempSettings || {}),
      filterSearch: { ...(this.tempSettings?.filterSearch || {}),
        [fieldKey]: String(value || '')
      }
    };
    this.requestComponentUpdate('filter-option-search');
  }

  filterUpdate() {
    return this.getFilterPayload();
  }

  getFilterOptionSearchKeys(field = {}) {
    const keys = field.filter?.searchKeys || field.filter?.optionSearchKeys;

    if (Array.isArray(keys) && keys.length) {
      return keys;
    }

    return ['label', 'title', 'name', 'text', 'value', 'id'];
  }

  getObjectPathValue(source = {}, path = '') {
    return String(path || '').split('.').filter(Boolean).reduce((value, key) => {
      if (value === null || typeof value === 'undefined') {
        return '';
      }

      return value[key];
    }, source);
  }

  normalizeFilterSearchValue(value = '') {
    return String(value || '').trim().toLowerCase();
  }

  optionMatchesSearch(option = {}, search = '', keys = []) {
    const query = this.normalizeFilterSearchValue(search);
    if (!query) return true;
    return keys.some(key => {
      const value = this.getObjectPathValue(option, key);
      return this.normalizeFilterSearchValue(value).includes(query);
    });
  }

  getVisibleFilterOptions(field = {}) {
    const options = this.getFilterOptions(field);
    const search = this.getFilterOptionSearch(field.key);

    if (!search) {
      return options;
    }

    const keys = this.getFilterOptionSearchKeys(field);
    return options.filter(option => this.optionMatchesSearch(option, search, keys));
  }

  renderFilterControl(field = {}) {
    const control = field.filter?.control || field.type || field.dataType || 'text';
    const renderers = {
      text: () => this.renderTextFilterControl(field),
      number: () => this.renderNumberFilterControl(field),
      date: () => this.renderDateFilterControl(field),
      'range': () => this.renderRangeFilterControl(field),
      'date-range': () => this.renderDateFilterControl(field),
      'multi-select': () => this.renderMultiSelectFilterControl(field),
      'single-select': () => this.renderMultiSelectFilterControl(field),
      'entity-multi-select': () => this.renderEntityMultiSelectFilterControl(field),
      'entity-single-select': () => this.renderEntityMultiSelectFilterControl(field)
    };
    return (renderers[control] || renderers.text)();
  }

  renderTextFilterControl(field = {}) {
    const value = this.getFilterControlValue(field.key);
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <sf-input
                    size="1"
                    type="filled"
                    placeholder="Найти в списке"
                    value="${value.value || ''}"
                    name="sf_filter_${field.key || 'text'}"
                    @change=${event => {
      this.patchTempFilterValue(field.key, {
        value: event.target?.value || '',
        operator: value.operator || field.filter?.defaultOperator || 'contains'
      });
    }}
            ></sf-input>
        `;
  }

  renderRangeFilterControl(field = {}) {
    const control = this.getFilterControlValue(field.key);
    const operators = field.filter?.operators || ['eq'];
    const selectedOperator = control.operator || field.filter?.defaultOperator || operators[0];
    const isBetween = selectedOperator === 'between';
    const rangeValue = isBetween ? control.value : Array.isArray(control.value) ? control.value[0] : control.value;
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <div class="flex gap-1/3 flex-col">
                <div class="sf-filter-operator-range flex gap-1/3 items-cross-center">
                    <sf-range-slider
                            id="smart-range-slider-single"
                            min="0"
                            label="tooltip"
                            suffix="%"
                            max="100"
                            multiple=${String(isBetween)}
                            .value=${rangeValue}
                            @onSliderEnd=${e => {
      this.patchTempFilterValue(field.key, {
        value: isBetween ? e.detail.values || [] : e.detail.value,
        operator: selectedOperator
      });
    }}
                    ></sf-range-slider>
                </div>
                <sf-dropdown
                        size="1"
                        type="outlined"
                        mode="select"
                        placeholder="Оператор"
                        root-class="flex-1"
                        search="false"
                        @sf-dropdown:change=${event => {
      const operator = event.detail?.values?.[0] || event.detail?.value || selectedOperator;
      this.patchTempFilterValue(field.key, {
        operator
      });
    }}
                >
                    ${operators.map(operator => (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                        <sf-list-item
                                type="text"
                                size="1"
                                text="${this.getFilterOperatorLabel(operator)}"
                                value="${operator}"
                                ?selected=${operator === selectedOperator}
                        ></sf-list-item>
                    `)}
                </sf-dropdown>
            </div>
        `;
  }

  renderNumberFilterControl(field = {}) {
    const control = this.getFilterControlValue(field.key);
    const operators = field.filter?.operators || ['eq'];
    const selectedOperator = control.operator || field.filter?.defaultOperator || operators[0];
    const isBetween = selectedOperator === 'between';
    const rawValue = control.value;
    const rangeValue = Array.isArray(rawValue) ? rawValue : [rawValue || '', ''];

    const patchNumberValue = (index, nextInputValue) => {
      if (!isBetween) {
        this.patchTempFilterValue(field.key, {
          value: nextInputValue || '',
          operator: selectedOperator
        });
        return;
      }

      const nextValue = [...rangeValue];
      nextValue[index] = nextInputValue || '';
      this.patchTempFilterValue(field.key, {
        value: nextValue,
        operator: selectedOperator
      });
    };

    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <div class="flex gap-1/3 flex-col">
                <div class="sf-filter-operator-inputs flex gap-1/3 items-cross-center">
                    <sf-input
                            size="1"
                            type="bordered"
                            root-class="flex-1 min-w-0"
                            label="${isBetween ? 'От' : this.getFilterOperatorLabel(selectedOperator)}"
                            value="${isBetween ? rangeValue[0] || '' : rawValue || ''}"
                            name="sf_filter_${field.key || 'number'}"
                            @change=${event => {
      patchNumberValue(0, event.target?.value);
    }}
                    ></sf-input>
                    ${isBetween ? (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                        <sf-input
                                size="1"
                                type="bordered"
                                label="До"
                                root-class="flex-1 min-w-0"
                                value="${rangeValue[1] || ''}"
                                name="sf_filter_${field.key || 'number'}_second"
                                @change=${event => {
      patchNumberValue(1, event.target?.value);
    }}
                        ></sf-input>` : lit__WEBPACK_IMPORTED_MODULE_2__.nothing}
                </div>
                <sf-dropdown
                        size="1"
                        type="outlined"
                        mode="select"
                        placeholder="Оператор"
                        root-class="flex-1"
                        search="false"
                        @sf-dropdown:change=${event => {
      const operator = event.detail?.values?.[0] || event.detail?.value || selectedOperator;
      this.patchTempFilterValue(field.key, {
        operator
      });
    }}
                >
                    ${operators.map(operator => (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                        <sf-list-item
                                type="text"
                                size="1"
                                text="${this.getFilterOperatorLabel(operator)}"
                                value="${operator}"
                                ?selected=${operator === selectedOperator}
                        ></sf-list-item>
                    `)}
                </sf-dropdown>
            </div>
        `;
  }

  renderDateFilterControl(field = {}) {
    const control = this.getFilterControlValue(field.key);
    const operators = field.filter?.operators || ['eq'];
    const selectedOperator = control.operator || field.filter?.defaultOperator || operators[0];
    const isBetween = selectedOperator === 'between';
    const dateValue = isBetween && Array.isArray(control.value) ? control.value : isBetween ? ['', ''] : control.value || '';
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <div class="flex flex-col gap-1/3">
                <sf-datepicker
                        .value=${dateValue}
                        ?range=${isBetween}
                        name="sf_filter_${field.key || 'date'}"
                        locale="ru-Ru"
                        @change=${event => {
      this.patchTempFilterValue(field.key, {
        value: event.detail.value,
        operator: selectedOperator
      });
    }}
                ></sf-datepicker>
                <sf-dropdown
                        size="1"
                        type="outlined"
                        mode="select"
                        placeholder="Оператор"
                        root-class="flex-1"
                        search="false"
                        @sf-dropdown:change=${event => {
      const operator = event.detail?.values?.[0] || event.detail?.value || selectedOperator;
      this.patchTempFilterValue(field.key, {
        operator
      });
    }}
                >
                    ${operators.map(operator => (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                        <sf-list-item
                                type="text"
                                size="1"
                                text="${this.getFilterOperatorLabel(operator)}"
                                value="${operator}"
                                ?selected=${operator === selectedOperator}
                        ></sf-list-item>
                    `)}
                </sf-dropdown>
            </div>
        `;
  }

  renderMultiSelectFilterControl(field = {}) {
    const options = this.getVisibleFilterOptions(field);
    const value = this.getFilterControlValue(field.key);
    const search = this.getFilterOptionSearch(field.key);
    const selected = new Set(value.values || []);
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <div class="flex flex-col gap-1/2">
                ${field.filter?.searchable ? (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                    <sf-input
                            size="1"
                            type="filled"
                            placeholder="Найти в списке"
                            value="${search}"
                            name="sf_filter_${field.key || 'list'}_search"
                            @input=${event => {
      this.patchTempFilterOptionSearch(field.key, event.target?.value || '');
    }}
                            @change=${event => {
      this.patchTempFilterOptionSearch(field.key, event.target?.value || '');
    }}
                    ></sf-input>
                ` : ''}
                <div class="flex flex-col gap-1/2" data-size="4.5">
                    ${options.map(option => this.renderFilterOption(field, option, selected))}
                </div>
            </div>
        `;
  }

  renderEntityMultiSelectFilterControl(field = {}) {
    return this.renderMultiSelectFilterControl(field);
  }

  renderFilterOption(field = {}, option = {}, selected = new Set()) {
    const value = option.value ?? option.id ?? option.key ?? option.label ?? option.title;
    const label = option.label || option.title || String(value || '');
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <div
                    ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_3__.ref)(this.getItemRef(option.label))}
                    class="flex items-cross-center gap-1/2 content-main-between">
                <div class="flex items-cross-center gap-1/2 min-w-0">
                    ${this.renderFilterOptionVisual(field, option)}
                    <span class="sf-text-1 truncate">${label}</span>
                </div>
                <sf-checkbox
                        size="1"
                        position="end"
                        root-class="content-main-between"
                        ?checked=${selected.has(value)}
                        @change=${event => {
      const nextSelected = new Set(selected);

      if (event.target?.checked) {
        nextSelected.add(value);
      } else {
        nextSelected.delete(value);
      }

      this.patchTempFilterValue(field.key, {
        values: Array.from(nextSelected),
        operator: field.filter?.defaultOperator || 'in'
      });
    }}
                ></sf-checkbox>
            </div>
        `;
  }

  renderFilterOptionVisual(field = {}, option = {}) {
    if (field.filter?.optionRenderer !== 'avatar-checkbox') {
      return '';
    }

    if (option.imageUrl) {
      return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                <sf-avatar
                        size="1"
                        image-url="${option.imageUrl}"
                        title="${option.title || option.label || ''}"
                ></sf-avatar>
            `;
    }

    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <sf-avatar
                    size="1"
                    title="${option.avatarText || option.title || option.label || ''}"
            ></sf-avatar>
        `;
  }

  getFilterOperatorLabel(operator) {
    const labels = {
      eq: 'Точно',
      gt: 'Больше чем',
      gte: 'Больше или равно',
      lt: 'Меньше чем',
      lte: 'Меньше или равно',
      between: 'Диапазон',
      contains: 'Содержит',
      startsWith: 'Начинается с',
      any: 'Любая дата',
      today: 'Сегодня',
      week: 'Неделя',
      month: 'Месяц',
      in: 'В списке'
    };
    return labels[operator] || operator;
  }

  renderFilterSettingsContextMenu(data) {
    const {
      x,
      y,
      position,
      columnCount,
      columnsCount,
      transform
    } = data;
    let pos = `translateX(${transform !== undefined ? transform : 0}%)`;
    const pendingFilter = this.getPendingFilterState();
    const selectedTemplateKey = pendingFilter.selectedTemplateKey;
    const sortTemplates = this.getSortedFilterTemplates(pendingFilter).map(col => {
      let component;
      const isActiveEdit = this.state.filter.rename === col.key;

      if (this.state.filter.rename !== col.key) {
        const data = {
          type: "radio",
          props: {
            size: 1,
            label: col.label,
            name: 'filter_templates',
            checked: selectedTemplateKey ? selectedTemplateKey === col.key : col.selected || false,
            '@change': () => {
              this.selectTempFilterTemplate(col.key);
            }
          }
        };
        component = this.renderSmartElement(data.type, data.props);
      } else {
        component = (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                    <div class="flex items-cross-center gap-1/4 flex-1">
                        <sf-input ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_3__.ref)(this.refs.renameInput)} root-class="flex-1"
                                  default-value="${col.label}"></sf-input>
                        <div class="flex items-cross-center flex-none">
                            <sf-icon-button segment="start" type="outline" scheme="on-surface" @click="${() => {
          this.set(prevState => ({
            filter: { ...(prevState.filter || {}),
              rename: ''
            }
          }));
        }}"
                                            icon="close"></sf-icon-button>
                            <sf-icon-button segment="end" icon="check" @click="${() => {
          const name = this.refs.renameInput.value?.value;
          this.saveFilterTemplatePatch(col.key, {
            label: name
          }, {
            filterPatch: {
              rename: ''
            },
            saveInputValue: name
          });
        }}"></sf-icon-button>
                        </div>
                    </div>`;
      }

      return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                <div
                        :key=${col.key}
                        ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_3__.ref)(this.getItemRef(col.label))}
                        class="sf-settings-context-menu-item flex items-cross-center flex-1 gap-1/3 ${col.deleted ? 'opacity-3' : ''}"
                        data-filter-template-key="${col.key}"
                        data-pinned="${col.pinned ? "1" : "0"}"
                >
                    ${!isActiveEdit ? (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                        <sf-icon-button
                                id="${col.label}_move"
                                type="link"
                                data-move
                                scheme="on-surface"
                                aria-label="Set order"
                                icon="drag_indicator"
                                root-class="cursor-move"
                        ></sf-icon-button>` : lit__WEBPACK_IMPORTED_MODULE_2__.nothing}
                    ${component}
                    ${!isActiveEdit ? (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                        <div class="flex items-cross-center m-inline-start-auto gap-1/3">
                            <sf-icon-button
                                    type="link"
                                    data-keep
                                    size="1/3"
                                    scheme="on-surface"
                                    aria-label="Keep item"
                                    ?filled="${col.pinned}"
                                    root-class="${col.pinned ? 'sf-button-pinned' : ''}"
                                    icon="keep"
                                    @click=${() => {
        this.patchTempFilterTemplateSettings(col.key, {
          pinned: !col.pinned
        });
      }}
                            ></sf-icon-button>
                            <sf-icon-button
                                    type="link"
                                    data-settings
                                    data-context-submenu-anchor="${col.key}"
                                    scheme="on-surface"
                                    aria-label="Item Settings"
                                    icon="more_vert"
                                    @click=${event => {
        this.openContextSubmenu(event, {
          type: 'filter-template-actions',
          template: col
        });
      }}
                            ></sf-icon-button>
                        </div>` : lit__WEBPACK_IMPORTED_MODULE_2__.nothing}
                </div>`;
    });
    const menuColumnCount = columnCount ?? columnsCount ?? this.contextMenuColumns ?? 1;
    const colsData = this.getFilterTagColsData();
    const columnGroups = this.splitItemsByColumns(colsData, menuColumnCount);
    let contextItems = 'Шаблоны|Поля';
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <sf-context-menu
                    :key=${x + y + this._renderToken}
                    aria-label=${this.getContextMenuLabel({ ...data,
      menu: 'filter-settings'
    })}
                    style="
            position: fixed;
            inset-inline-start: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            transform: ${pos};
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    root-class="sf-table-context-menu flex flex-col"
                    position="${position || ""}"
            >
                <div class="flex flex-col flex-1" slot="content">
                    <sf-tabs items="${contextItems}" type="underline">
                        <div slot="panel-0" class="flex">
                            <div
                                    class="sf-context-menu-columns flex flex-col gap-1/4 overflow-auto flex-1"
                                    data-filter-template-list
                            >
                                ${sortTemplates}
                            </div>
                        </div>
                        <div slot="panel-1">
                            <div class="sf-context-menu-columns flex flex-1 gap-1 overflow-auto">
                                ${columnGroups.map(group => (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                                            <div class="flex flex-col gap-1 flex-1">
                                                ${group}
                                            </div>
                                        `)}
                            </div>
                        </div>

                    </sf-tabs>
                    <div class="sf-table-context-bottom flex gap-1/3">
                        <sf-button @click="${() => {
      this.tempSettings = null;
      this.requestComponentUpdate('table');
    }}" type="outline" scheme="on-surface" root-class="flex-1"
                                   text="Сбросить"></sf-button>
                        <sf-button @click="${() => {
      const tempFilter = this.tempSettings?.filter;

      if (tempFilter) {
        let changedTemplates = [];
        this.set(prevState => {
          const hasDataChanges = this.hasPendingFilterDataChanges(prevState.filter || {});
          const filter = this.mergeFilterState(prevState.filter || {}, tempFilter);
          const selectedTemplate = this.getSelectedFilterTemplate(filter);
          changedTemplates = tempFilter.templates ? this.getChangedFilterTemplates(prevState.filter || {}, filter, ['order', 'pinned', 'deleted', 'selected', 'default']) : [];
          filter.templates = filter.templates.filter(el => !el.deleted);
          return {
            filter,
            filterDataDirty: prevState.filterDataDirty || hasDataChanges,
            saveInputValue: selectedTemplate?.label || ""
          };
        }, () => {
          if (changedTemplates.length) {
            this.dispatchFilterTemplatesSave(changedTemplates);
          }
        });
      }

      this.tempSettings = null;
      this.closeContextMenu();
    }}" root-class="flex-1" text="Применить"></sf-button>
                    </div>
                </div>
            </sf-context-menu>
        `;
  }

  renderTagsSettingsContextMenu(data) {
    const {
      x,
      y,
      position,
      fieldKey
    } = data;
    const field = this.getFilterField(fieldKey) || {
      key: fieldKey,
      label: this.getCurrentFilterTags()?.[fieldKey]?.label || fieldKey || '',
      filter: {
        control: this.getCurrentFilterTags()?.[fieldKey]?.control || 'text'
      }
    };
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <sf-context-menu
                    :key=${x + y + this._renderToken}
                    aria-label=${this.getContextMenuLabel({ ...data,
      menu: 'tag-settings'
    })}
                    style="
            position: fixed;
            inset-inline-start: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    root-class="sf-table-context-menu flex flex-col"
                    position="${position || ""}"
            >
                <div class="flex flex-col flex-1 p-top-3" slot="content">
                    <div class="sf-table-context-main">
                        <div class="sf-table-tags-search flex flex-col">
                            <div class="flex flex-col p-bottom-3">
                                ${this.renderFilterControl(field)}
                            </div>
                        </div>
                    </div>
                    <div class="sf-table-context-bottom flex gap-1/3">
                        <sf-button @click="${() => {
      this.tempSettings = null;
      this.requestComponentUpdate('table');
    }}" type="outline" scheme="on-surface" root-class="flex-1"
                                   text="Сбросить"></sf-button>
                        <sf-button @click="${() => {
      const tempFilter = this.tempSettings?.filter;

      if (tempFilter) {
        this.set(prevState => {
          const hasDataChanges = this.hasPendingFilterDataChanges(prevState.filter || {});
          return {
            filter: this.mergeFilterState(prevState.filter || {}, tempFilter),
            filterDataDirty: prevState.filterDataDirty || hasDataChanges
          };
        }, () => {
          const detail = this.filterUpdate();
          this.dispatchEvent(new CustomEvent('onFilterUpdate', {
            bubbles: true,
            composed: true,
            detail
          }));
          this.dispatchTableEvent('sf-table-filter-change', detail);
        });
      }

      this.tempSettings = null;
      this.closeContextMenu();
    }}" root-class="flex-1" text="Применить"></sf-button>
                    </div>
                </div>
            </sf-context-menu>
        `;
  }

  removeFilterTag(id = null) {
    if (id) {
      this.tempSettings = null;
      this.set(prevState => {
        const prevFilter = prevState.filter || {};
        const tags = { ...this.getCurrentFilterTags(prevFilter)
        };
        const values = { ...this.getCurrentFilterValues(prevFilter)
        };
        delete tags[id];
        delete values[id];
        return {
          filter: this.patchFilterTemplateData(prevFilter, {
            tags,
            values
          }),
          filterDataDirty: true
        };
      }, () => {
        const detail = this.filterUpdate();
        this.dispatchEvent(new CustomEvent('onFilterUpdate', {
          bubbles: true,
          composed: true,
          detail
        }));
        this.dispatchTableEvent('sf-table-filter-change', detail);
      });
      return this;
    }

    this.tempSettings = null;
    this.set(prevState => {
      const prevFilter = prevState.filter || {};
      return {
        filter: { ...prevFilter,
          selectedTemplateKey: "",
          templates: this.getFilterTemplates(prevFilter).map(template => ({ ...template,
            selected: false
          }))
        },
        saveInputValue: "",
        filterDataDirty: false
      };
    }, () => {
      const detail = this.filterUpdate();
      this.dispatchEvent(new CustomEvent('onFilterUpdate', {
        bubbles: true,
        composed: true,
        detail
      }));
      this.dispatchTableEvent('sf-table-filter-change', detail);
    });
    return this;
  }

  renderTableSettingsContextMenu(data) {
    const {
      x,
      y,
      position,
      transform,
      columnCount,
      columnsCount
    } = data;
    let pos = `translateX(${transform !== undefined ? transform : 0}%)`;
    const menuColumnCount = columnCount ?? columnsCount ?? this.contextMenuColumns ?? 1;
    const cols = this.getTableColsData();
    const columnGroups = this.splitItemsByColumns(cols, menuColumnCount);
    const actionEnable = this.state.actions.length > 0;
    let contextItems = 'Поля';
    let actions = [];

    if (actionEnable) {
      contextItems += '|Действия';
      actions = this.getActionItems();
    }

    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <sf-context-menu
                    :key=${x + y + this._renderToken}
                    aria-label=${this.getContextMenuLabel({ ...data,
      menu: 'table-settings'
    })}
                    style="
            position: fixed;
            inset-inline-start: ${x}px;
            inset-block-start: calc(${y}px + calc(var(--sf-focus-outline-width) * 2));
            transform: ${pos};
            z-index: var(--sf-table-context-menu--z-index, var(--sf-z-index-9));
          "
                    root-class="sf-table-context-menu flex flex-col"
                    position="${position || ""}"
            >
                <div class="flex flex-col flex-1" slot="content">
                    <sf-tabs items="${contextItems}" type="underline">
                        <div slot="panel-0" class="flex gap-1">
                            <div class="sf-context-menu-columns flex flex-1 gap-1 overflow-auto">
                                ${columnGroups.map(group => (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                                            <div class="flex flex-col gap-1 flex-1">
                                                ${group}
                                            </div>
                                        `)}
                            </div>
                        </div>
                        ${actionEnable ? (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                                    <div slot="panel-1">
                                        <div class="flex flex-col gap-1 flex-1 p-2">
                                            ${actions.map(action => (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                                                        ${action}
                                                    `)}
                                        </div>
                                    </div>` : ''}
                    </sf-tabs>
                    <div class="sf-table-context-bottom flex gap-1/3">
                        <sf-button @click="${() => {
      this.tempSettings = null;
      this.requestComponentUpdate('table');
    }}" type="outline" scheme="on-surface" root-class="flex-1"
                                   text="Сбросить"></sf-button>
                        <sf-button @click="${() => {
      const tempSettings = this.tempSettings || {};
      const {
        cols: tempColumns,
        ...nextTempSettings
      } = tempSettings;
      const nextState = { ...nextTempSettings
      };

      if (tempColumns) {
        nextState.columnSettings = this.mergeColumnSettings(this.getColumnSettings(), tempColumns);
      }

      this.set(prevState => {
        return { ...prevState,
          ...nextState
        };
      }, 'table-settings', () => {
        if (tempColumns) {
          this.emitColumnSettingsChange('table-settings');
        }
      });
      this.tempSettings = null;
    }}" root-class="flex-1" text="Применить"></sf-button>
                    </div>
                </div>
            </sf-context-menu>
        `;
  }

  renderMainContextMenu(data) {
    const menu = data.menu || data.menuType || data.contextMenu;

    switch (menu) {
      case 'row-settings':
        return this.renderRowSettingsContextMenu(data);

      case 'filter-favorites':
        return this.renderFilterFavoritesContextMenu(data);

      case 'filter-settings':
        return this.renderFilterSettingsContextMenu(data);

      case 'table-settings':
        return this.renderTableSettingsContextMenu(data);

      case 'tag-settings':
        return this.renderTagsSettingsContextMenu(data);

      default:
        return data.type === 'left' ? this.renderRowSettingsContextMenu(data) : this.renderTableSettingsContextMenu(data);
    }
  }

  renderTemplateActionsContextSubmenu(data) {
    const {
      x,
      y,
      position,
      transform,
      template = {}
    } = data;
    let pos = `translateY(${transform !== undefined ? transform : 0}%)`;
    const temp = this.getFilterTemplateByKey(template.key, this.tempSettings?.filter || null);
    let isDeleted = (temp || template).deleted;
    const isDefaultTemplate = (temp || template).default === true;
    return (0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
            <sf-context-menu
                    :key=${`${template.key || ''}-${x}-${y}`}
                    aria-label=${this.getContextMenuLabel({ ...data,
      type: 'filter-template-actions'
    })}
                    style="
          position: fixed;
        inset-inline-start: calc(${x}px + var(--sf-context-menu-tail-corner-offset));
            inset-block-start: ${y}px;
          z-index: var(--sf-table-context-submenu--z-index, var(--sf-z-index-9));
          transform: ${pos}
        "
                    data-context-submenu
                    root-class="sf-table-context-submenu flex flex-col"
                    position="${position || "right"}"
            >
                <div class="flex flex-col flex-1" slot="content">
                    <sf-button type="link" scheme="on-surface" icon-left="edit" text="Переименовать" @click="${() => {
      this.set(prevState => ({
        filter: { ...(prevState.filter || {}),
          rename: template.key
        },
        contextMenu: { ...(prevState.contextMenu || {}),
          submenu: null
        }
      }));
    }}"></sf-button>
                    <sf-button type="link" scheme="on-surface" icon-left="${isDefaultTemplate ? 'remove' : 'check'}"
                               text="${isDefaultTemplate ? 'По умолчанию' : 'Сделать по умолчанию'}" @click="${() => {
      this.changeTempFilterTemplateSettings(template.key, {
        default: !template.default
      });
      this.closeContextSubmenu();
    }}"></sf-button>
                    <sf-button type="link" scheme="on-surface" icon-left="delete" @click="${() => {
      this.patchTempFilterTemplateSettings(template.key, {
        deleted: !isDeleted
      });
    }}" text="${isDeleted ? 'Восстановить' : 'Удалить'}"></sf-button>
                </div>
            </sf-context-menu>
        `;
  }

  renderContextSubmenu(data) {
    switch (data?.type) {
      case 'filter-template-actions':
        return this.renderTemplateActionsContextSubmenu(data);

      default:
        return lit__WEBPACK_IMPORTED_MODULE_2__.nothing;
    }
  }

  renderContextMenu(data) {
    if (!data?.open) {
      (0,lit__WEBPACK_IMPORTED_MODULE_2__.render)(null, this.getPortalContainer());
      return lit__WEBPACK_IMPORTED_MODULE_2__.nothing;
    }

    (0,lit__WEBPACK_IMPORTED_MODULE_2__.render)((0,lit__WEBPACK_IMPORTED_MODULE_2__.html)`
                <div
                        class="sf-table-context-layer"
                        ${(0,lit_directives_ref_js__WEBPACK_IMPORTED_MODULE_3__.ref)(this.refs.contextMenu)}
                        @click=${event => this.handleContextLayerClick(event)}
                >
                    ${this.renderMainContextMenu(data)}
                    ${data.submenu?.open ? this.renderContextSubmenu(data.submenu) : lit__WEBPACK_IMPORTED_MODULE_2__.nothing}
                </div>
            `, this.getPortalContainer());
    this.bindContextEvent();
    this.bindContextViewport();
    requestAnimationFrame(() => {
      this.bindFilterTemplateDrag();
      this.clampContextMenusToViewport();
    });
    return lit__WEBPACK_IMPORTED_MODULE_2__.nothing;
  }

  getActionItems() {
    return this.state.actions.map(action => {
      const data = {
        type: "checkbox",
        props: {
          size: 1,
          label: action.name,
          position: 'end',
          rootClass: 'content-main-between',
          checked: action.visible,
          "@change": e => {
            let actionActive = false;
            let arr = this.tempSettings && this.tempSettings.actions ? this.tempSettings.actions : this.state.actions;
            const items = arr.map(item => {
              let visible = item.id === action.id ? e.target.checked : item.visible;

              if (visible) {
                actionActive = true;
              }

              return { ...item,
                visible: visible
              };
            });
            this.tempSettings = { ...(this.tempSettings || {}),
              actionActive,
              actions: items
            };
          }
        }
      };
      return this.renderSmartElement(data.type, data.props);
    });
  }

  hoverEvents(e) {
    if (this._columnResizeState || this._columnDragState?.started) {
      return;
    }

    const th = e.target.closest('th');

    if (!th) {
      return;
    }

    const wrap = th.querySelector('.sidebar-resizer');

    if (!wrap) {
      return;
    }

    const button = th.querySelector(`#column_resizer_${th.dataset.key}`);

    if (button) {
      button.setHidden(false);
    }

    th.classList.add('hover');
  }

  outEvent(e) {
    if (this._columnResizeState || this._columnDragState?.started) {
      return;
    }

    const th = e.target.closest('th');

    if (!th) {
      return;
    }

    const wrap = th.querySelector('.sidebar-resizer');

    if (!wrap) {
      return;
    }

    const button = th.querySelector(`#column_resizer_${th.dataset.key}`);

    if (button) {
      button.setHidden(true);
    }

    th.classList.remove('hover');
  }

  bindHoverEvent() {
    if (this._hoverEventBound) return;
    this._hoverEventBound = true;
    const table = this.refs.table?.value;
    table?.addEventListener('mouseover', this.hoverEvents);
    table?.addEventListener('mouseout', this.outEvent); //
    // this.addEventListener('mouseout', () => {
    //     this.querySelectorAll('.hover')
    //         .forEach(el => el.classList.remove('hover'));
    // });
  }

  unBindHoverEvent() {
    if (!this._hoverEventBound) {
      return;
    }

    const table = this.refs.table?.value;
    table?.removeEventListener('mouseover', this.hoverEvents);
    table?.removeEventListener('mouseout', this.outEvent);
    this._hoverEventBound = false;
  }

  bindColumnResizeEvent() {
    if (this._columnResizeEventBound) return;
    const table = this.refs.table?.value;
    if (!table) return;
    table.addEventListener('pointerdown', this.onColumnResizePointerDown);
    this._columnResizeEventBound = true;
  }

  unbindColumnResizeEvent() {
    if (!this._columnResizeEventBound) {
      return;
    }

    const table = this.refs.table?.value;
    table?.removeEventListener('pointerdown', this.onColumnResizePointerDown);
    this._columnResizeEventBound = false;
  }

  bindColumnDragEvent() {
    if (this._columnDragEventBound) return;
    const table = this.refs.table?.value;
    if (!table) return;
    table.addEventListener('pointerdown', this.onColumnDragPointerDown);
    this._columnDragEventBound = true;
  }

  unbindColumnDragEvent() {
    if (!this._columnDragEventBound) {
      return;
    }

    const table = this.refs.table?.value;
    table?.removeEventListener('pointerdown', this.onColumnDragPointerDown);
    this._columnDragEventBound = false;
  }

  getColumnResizeHandle(target) {
    if (!(target instanceof Element)) {
      return null;
    }

    return target.closest('[id^="column_resizer_"]');
  }

  getColumnDragHeader(target) {
    if (!(target instanceof Element)) {
      return null;
    }

    if (this.getColumnResizeHandle(target)) {
      return null;
    }

    return target.closest('th[data-key]');
  }

  parseColumnSize(value, fallback = null) {
    if (typeof value === 'number' && Number.isFinite(value)) {
      return value;
    }

    if (typeof value === 'string') {
      const normalized = value.trim();

      if (/^-?\d+(\.\d+)?px$/.test(normalized)) {
        return parseFloat(normalized);
      }
    }

    return fallback;
  }

  resolveCssLength(value, fallback = 0) {
    const normalized = String(value || '').trim();
    const match = normalized.match(/^(-?\d+(?:\.\d+)?)(px|rem|em)$/);

    if (!match) {
      return fallback;
    }

    const amount = Number(match[1]);
    const unit = match[2];

    if (unit === 'px') {
      return amount;
    }

    const reference = unit === 'rem' ? document.documentElement : this;
    const fontSize = parseFloat(getComputedStyle(reference).fontSize);
    return Number.isFinite(fontSize) ? amount * fontSize : fallback;
  }

  getColumnResizeLimits(column = {}, fallbackWidth = 0) {
    const semanticMinWidth = this.resolveCssLength(getComputedStyle(this).getPropertyValue('--sf-c4'), fallbackWidth);
    const minWidth = this.parseColumnSize(column.minWidth, null) ?? semanticMinWidth;
    const maxWidth = this.parseColumnSize(column.maxWidth, Infinity);
    return {
      minWidth,
      maxWidth: Math.max(maxWidth, minWidth)
    };
  }

  resizeColumnByKeyboard(event, key) {
    if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) {
      return;
    }

    const table = this.refs.table?.value;
    const escapedKey = this.escapeColumnKey(key);
    const th = table?.querySelector(`th[data-key="${escapedKey}"]`);
    const column = this.getColumns().find(item => item?.key === key);

    if (!th || !column || column.system || column.resizable === false) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    const currentWidth = th.getBoundingClientRect().width;
    const {
      minWidth,
      maxWidth
    } = this.getColumnResizeLimits(column, currentWidth);
    const step = this.resolveCssLength(getComputedStyle(this).getPropertyValue('--sf-space-1'), 1);
    const direction = getComputedStyle(table).direction === 'rtl' ? -1 : 1;
    const keyDirection = event.key === 'ArrowRight' ? 1 : -1;
    const nextWidth = Math.min(Math.max(currentWidth + step * direction * keyDirection, minWidth), maxWidth);
    this.applyColumnResizeWidths(key, nextWidth);
    requestAnimationFrame(() => {
      const handle = this.querySelector(`#column_resizer_${escapedKey}`);
      const focusTarget = handle?.matches?.('button, [href], input, select, textarea, [tabindex]') ? handle : handle?.querySelector?.('button, [href], input, select, textarea, [tabindex]');
      focusTarget?.focus?.();
    });
  }

  hasColumnSize(column = {}) {
    return [column.colWidth, column.width, column.size].some(value => value !== null && typeof value !== 'undefined' && String(value).trim() !== '');
  }

  escapeColumnKey(key) {
    const value = String(key || '');

    if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
      return CSS.escape(value);
    }

    return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
  }

  freezeColumnsWidthForResize(table) {
    const frozenWidths = {};
    const columns = this.getColumns();
    columns.forEach(column => {
      if (!column?.key) {
        return;
      }

      const key = String(column.key);
      const escapedKey = this.escapeColumnKey(key);
      const th = table?.querySelector(`th[data-key="${escapedKey}"]`);
      const col = table?.querySelector(`col[data-key="${escapedKey}"]`);
      const width = th?.getBoundingClientRect?.().width || 0;

      if (!width) {
        return;
      }

      const nextWidth = `${Math.round(width)}px`;

      if (col) {
        col.style.width = nextWidth;
      }

      if (!column.system && !this.hasColumnSize(column)) {
        frozenWidths[key] = nextWidth;
      }
    });
    return frozenWidths;
  }

  applyColumnResizeWidths(key, width, frozenWidths = {}) {
    if (!key) {
      return this;
    }

    const nextWidths = { ...(frozenWidths || {}),
      [key]: `${Math.round(width)}px`
    };
    const settingsPatch = Object.fromEntries(Object.entries(nextWidths).map(([columnKey, columnWidth]) => [columnKey, {
      width: columnWidth
    }]));
    return this.patchColumnSettingsBatch(settingsPatch, 'column-resize');
  }

  getColumnDragLayout(table) {
    const columns = this.getColumns();
    return columns.filter(column => column?.key && !column.system && column.draggable !== false).map(column => {
      const key = String(column.key);
      const escapedKey = this.escapeColumnKey(key);
      const th = table?.querySelector(`th[data-key="${escapedKey}"]`);
      const rect = th?.getBoundingClientRect?.();

      if (!th || !rect) {
        return null;
      }

      return {
        key,
        column,
        th,
        left: rect.left,
        right: rect.right,
        width: rect.width,
        middle: rect.left + rect.width / 2,
        cellIndex: th.cellIndex
      };
    }).sort((left, right) => left.cellIndex - right.cellIndex).filter(Boolean);
  }

  getColumnDragDropIndex(layout, x, draggingKey) {
    const items = layout.filter(item => item.key !== draggingKey);

    for (let index = 0; index < items.length; index += 1) {
      if (x < items[index].middle) {
        return index;
      }
    }

    return items.length;
  }

  getVisibleUserColumnKeysFromDom(table) {
    const systemKeys = new Set(this.getSystemColumns().map(column => String(column.key)));
    const headerCells = Array.from(table?.tHead?.rows?.[0]?.cells || []);
    return headerCells.map(cell => cell?.dataset?.key).filter(key => key && !systemKeys.has(String(key)));
  }

  reorderColumnDomByKeys(table, userKeys = []) {
    if (!table || !Array.isArray(userKeys) || !userKeys.length) {
      return;
    }

    const moveKeyedChildren = (container, selector) => {
      if (!container) {
        return;
      }

      const userKeySet = new Set(userKeys);
      let insertAfter = null;

      for (const child of Array.from(container.children || [])) {
        const childKey = child?.dataset?.key;

        if (childKey && userKeySet.has(childKey)) {
          break;
        }

        if (childKey) {
          insertAfter = child;
        }
      }

      userKeys.forEach(key => {
        const escapedKey = this.escapeColumnKey(key);
        const child = container.querySelector(`${selector}[data-key="${escapedKey}"]`);

        if (child) {
          container.insertBefore(child, insertAfter?.nextSibling || container.firstChild);
          insertAfter = child;
        }
      });
    };

    moveKeyedChildren(table.querySelector('colgroup'), 'col');
    moveKeyedChildren(table.tHead?.rows?.[0], 'th');
    Array.from(table.tBodies || []).forEach(tbody => {
      Array.from(tbody.rows || []).forEach(row => {
        moveKeyedChildren(row, 'td');
      });
    });
  }

  getColumnAnimationCells(table) {
    return Array.from(table?.querySelectorAll('thead th[data-key], tbody td[data-key]') || []).filter(cell => cell instanceof HTMLElement);
  }

  snapshotColumnCellRects(table) {
    const rects = new Map();
    this.getColumnAnimationCells(table).forEach(cell => {
      rects.set(cell, cell.getBoundingClientRect());
    });
    return rects;
  }

  clearColumnAnimationStyles(table = this.refs.table?.value) {
    this._columnAnimationToken += 1;
    this.getColumnAnimationCells(table).forEach(cell => {
      cell.style.transition = '';
      cell.style.transform = '';
      cell.style.willChange = '';
    });
  }

  clearColumnDragDom(table = this.refs.table?.value) {
    table?.querySelectorAll(['.is-column-dragging', '.is-column-drag-placeholder-cell', '.is-column-drag-before', '.is-column-drag-after'].join(',')).forEach(cell => {
      cell.classList.remove('is-column-dragging', 'is-column-drag-placeholder-cell', 'is-column-drag-before', 'is-column-drag-after');
    });
    document.querySelectorAll('.sf-table-column-drag-ghost').forEach(ghost => ghost.remove());
    document.documentElement.classList.remove('sf-table-column-dragging');
  }

  animateColumnReorder(table, beforeRects) {
    if (!table || !(beforeRects instanceof Map)) {
      return;
    }

    const animationToken = ++this._columnAnimationToken;
    const cells = this.getColumnAnimationCells(table);
    cells.forEach(cell => {
      const before = beforeRects.get(cell);

      if (!before) {
        return;
      }

      const after = cell.getBoundingClientRect();
      const deltaX = before.left - after.left;

      if (Math.abs(deltaX) < 1) {
        return;
      }

      cell.style.transition = 'none';
      cell.style.transform = `translate3d(${deltaX}px, 0, 0)`;
      cell.style.willChange = 'transform';
    });
    requestAnimationFrame(() => {
      if (animationToken !== this._columnAnimationToken) {
        return;
      }

      cells.forEach(cell => {
        if (!cell.style.transform) {
          return;
        }

        cell.style.transition = 'transform 140ms ease';
        cell.style.transform = 'translate3d(0, 0, 0)';
      });
      window.setTimeout(() => {
        if (animationToken !== this._columnAnimationToken) {
          return;
        }

        cells.forEach(cell => {
          cell.style.transition = '';
          cell.style.transform = '';
          cell.style.willChange = '';
        });
      }, 160);
    });
  }

  updateColumnDragPreview(state, dropIndex) {
    if (!state || state.dropIndex === dropIndex) {
      return;
    }

    state.layout.forEach(item => {
      item.th.classList.remove('is-column-drag-before', 'is-column-drag-after');
    });
    state.dropIndex = dropIndex;
    const items = state.layout.filter(item => item.key !== state.key);
    const nextKeys = items.map(item => item.key);
    nextKeys.splice(Math.max(0, Math.min(dropIndex, nextKeys.length)), 0, state.key);

    if (nextKeys.join('|') === state.currentKeys?.join('|')) {
      return;
    }

    state.currentKeys = nextKeys;
    const beforeRects = this.snapshotColumnCellRects(state.table);
    this.reorderColumnDomByKeys(state.table, nextKeys);
    this.animateColumnReorder(state.table, beforeRects);
    state.layout = this.getColumnDragLayout(state.table);
    this.setColumnDragPlaceholderClass(state.key, true, state.table);
    const target = state.layout.find(item => item.key === state.key);

    if (!target) {
      return;
    }

    target.th.classList.add('is-column-dragging');
  }

  setColumnDragPlaceholderClass(key, active, table = this.refs.table?.value) {
    if (!key || !table) {
      return;
    }

    const escapedKey = this.escapeColumnKey(key);
    table.querySelectorAll(`th[data-key="${escapedKey}"], td[data-key="${escapedKey}"]`).forEach(cell => {
      cell.classList.toggle('is-column-dragging', Boolean(active));
      cell.classList.toggle('is-column-drag-placeholder-cell', Boolean(active));
    });
  }

  createColumnDragGhost(th) {
    const rect = th.getBoundingClientRect();
    const ghost = document.createElement('div');
    const label = th?.dataset?.label || th?.textContent?.replace(/\s+/g, ' ').trim() || '';
    ghost.textContent = label;
    ghost.classList.add('sf-table-column-drag-ghost');
    Object.assign(ghost.style, {
      position: 'fixed',
      left: `${rect.left}px`,
      top: `${rect.top}px`,
      width: `${rect.width}px`,
      height: `${rect.height}px`,
      zIndex: 'var(--sf-table-drag-ghost--z-index, var(--sf-z-index-9))',
      pointerEvents: 'none',
      boxSizing: 'border-box'
    });
    ghost.setAttribute('aria-hidden', 'true');
    document.body.append(ghost);
    return ghost;
  }

  clearColumnDragState(commit = false, event = null) {
    const state = this._columnDragState;
    document.removeEventListener('pointermove', this.onColumnDragPointerMove);
    document.removeEventListener('pointerup', this.onColumnDragPointerUp);
    document.removeEventListener('pointercancel', this.onColumnDragPointerCancel);
    document.documentElement.classList.remove('sf-table-column-dragging');

    if (!state) {
      this.clearColumnAnimationStyles();
      this.clearColumnDragDom();
      (0,_helpers_loaderDragState__WEBPACK_IMPORTED_MODULE_6__.setLoaderDragState)(false);
      return;
    }

    if (state.frame) {
      cancelAnimationFrame(state.frame);
    }

    state.handle?.releasePointerCapture?.(event?.pointerId);
    state.ghost?.remove();
    this.clearColumnAnimationStyles(state.table);
    this.setColumnDragPlaceholderClass(state.key, false, state.table);
    state.th?.classList.remove('is-column-dragging');
    state.layout?.forEach(item => {
      item.th.classList.remove('is-column-drag-before', 'is-column-drag-after');
    });
    const nextKeys = this.getVisibleUserColumnKeysFromDom(state.table);
    const shouldCommit = commit && state.started && nextKeys.length && nextKeys.join('|') !== state.originalKeys?.join('|');
    this._columnDragState = null;
    this.clearColumnDragDom(state.table);
    (0,_helpers_loaderDragState__WEBPACK_IMPORTED_MODULE_6__.setLoaderDragState)(false);

    if (!shouldCommit) {
      if (state.started && !commit && state.originalKeys?.length) {
        this.reorderColumnDomByKeys(state.table, state.originalKeys);
      }

      return;
    }

    this.applyColumnOrder(nextKeys, state.frozenWidths);
  }

  applyColumnOrder(userKeys = [], frozenWidths = {}) {
    if (!Array.isArray(userKeys) || !userKeys.length) {
      return this;
    }

    const settingsPatch = {};
    userKeys.forEach((key, index) => {
      settingsPatch[key] = {
        order: (index + 1) * 10
      };

      if (frozenWidths?.[key]) {
        settingsPatch[key].width = frozenWidths[key];
      }
    });
    return this.patchColumnSettingsBatch(settingsPatch, 'column-drag');
  }

  setColumnResizeClasses(th, delta = 0) {
    if (!th) return;
    th.classList.add('is-resizing');
    th.classList.toggle('is-resizing-forward', delta >= 0);
    th.classList.toggle('is-resizing-backward', delta < 0);
  }

  clearColumnResizeClasses(th) {
    if (!th) return;
    th.classList.remove('is-resizing', 'is-resizing-forward', 'is-resizing-backward');
  }

  onColumnDragPointerDown(event) {
    if (event.button !== 0 || this._columnResizeState) {
      return;
    }

    const th = this.getColumnDragHeader(event.target);
    const key = th?.dataset?.key;

    if (!th || !key) {
      return;
    }

    const table = this.refs.table?.value;
    this.clearColumnAnimationStyles(table);
    this.clearColumnDragDom(table);
    const layout = this.getColumnDragLayout(table);
    const column = layout.find(item => item.key === key);
    const userKeys = layout.map(item => item.key);

    if (!column || layout.length < 2) {
      return;
    }

    this._columnDragState = {
      key,
      th,
      table,
      handle: th,
      startRect: th.getBoundingClientRect(),
      startX: event.clientX,
      startY: event.clientY,
      shiftX: event.clientX - column.left,
      shiftY: event.clientY - th.getBoundingClientRect().top,
      originalIndex: layout.findIndex(item => item.key === key),
      originalKeys: userKeys,
      currentKeys: userKeys,
      dropIndex: null,
      layout,
      started: false,
      ghost: null,
      frozenWidths: null
    };
    th.setPointerCapture?.(event.pointerId);
    document.addEventListener('pointermove', this.onColumnDragPointerMove);
    document.addEventListener('pointerup', this.onColumnDragPointerUp, {
      once: true
    });
    document.addEventListener('pointercancel', this.onColumnDragPointerCancel, {
      once: true
    });
  }

  onColumnDragPointerMove(event) {
    const state = this._columnDragState;

    if (!state) {
      return;
    }

    const deltaX = event.clientX - state.startX;
    const deltaY = event.clientY - state.startY;

    if (!state.started) {
      if (Math.abs(deltaX) < 4 && Math.abs(deltaY) < 4) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      state.started = true;
      (0,_helpers_loaderDragState__WEBPACK_IMPORTED_MODULE_6__.setLoaderDragState)(true);
      state.frozenWidths = this.freezeColumnsWidthForResize(state.table);
      state.ghost = this.createColumnDragGhost(state.th);
      this.setColumnDragPlaceholderClass(state.key, true, state.table);
      document.documentElement.classList.add('sf-table-column-dragging');
    }

    if (state.frame) {
      state.latestEvent = event;
      return;
    }

    state.latestEvent = event;
    state.frame = requestAnimationFrame(() => {
      const latestEvent = state.latestEvent;
      state.frame = null;

      if (!latestEvent || !state.ghost) {
        return;
      }

      state.ghost.style.left = `${latestEvent.clientX - state.shiftX}px`;
      state.ghost.style.top = `${latestEvent.clientY - state.shiftY}px`;
      state.layout = this.getColumnDragLayout(state.table);
      const dropIndex = this.getColumnDragDropIndex(state.layout, latestEvent.clientX, state.key);
      this.updateColumnDragPreview(state, dropIndex);
    });
  }

  onColumnDragPointerUp(event) {
    this.clearColumnDragState(true, event);
  }

  onColumnDragPointerCancel(event) {
    this.clearColumnDragState(false, event);
  }

  onColumnResizePointerDown(event) {
    const handle = this.getColumnResizeHandle(event.target);

    if (!handle) {
      return;
    }

    const th = handle.closest('th');
    const key = th?.dataset?.key;

    if (!th || !key) {
      return;
    }

    const column = this.getColumns().find(item => item?.key === key);

    if (!column || column.system || column.resizable === false) {
      return;
    }

    const table = this.refs.table?.value;
    const escapedKey = this.escapeColumnKey(key);
    const col = table?.querySelector(`col[data-key="${escapedKey}"]`);
    const startWidth = th.getBoundingClientRect().width;
    const limits = this.getColumnResizeLimits(column, startWidth);
    const frozenWidths = this.freezeColumnsWidthForResize(table);
    event.preventDefault();
    event.stopPropagation();
    this._columnResizeState = {
      key,
      th,
      col,
      startX: event.clientX,
      startWidth,
      width: startWidth,
      frozenWidths,
      ...limits
    }; // this.setColumnResizeClasses(th);

    (0,_helpers_loaderDragState__WEBPACK_IMPORTED_MODULE_6__.setLoaderDragState)(true);
    document.documentElement.classList.add('sf-table-column-resizing');
    document.addEventListener('pointermove', this.onColumnResizePointerMove);
    document.addEventListener('pointerup', this.onColumnResizePointerUp, {
      once: true
    });
    document.addEventListener('pointercancel', this.onColumnResizePointerUp, {
      once: true
    });
  }

  onColumnResizePointerMove(event) {
    const state = this._columnResizeState;

    if (!state) {
      return;
    }

    const delta = event.clientX - state.startX;
    state.width = Math.min(Math.max(state.startWidth + delta, state.minWidth), state.maxWidth);
    state.delta = delta;

    if (state.frame) {
      return;
    }

    state.frame = requestAnimationFrame(() => {
      state.frame = null; // this.setColumnResizeClasses(state.th, state.delta);

      if (state.col) {
        state.col.style.width = `${state.width}px`;
      }

      state.th.style.width = `${state.width}px`;
    });
  }

  onColumnResizePointerUp() {
    const state = this._columnResizeState;
    document.removeEventListener('pointermove', this.onColumnResizePointerMove);
    document.removeEventListener('pointerup', this.onColumnResizePointerUp);
    document.removeEventListener('pointercancel', this.onColumnResizePointerUp);
    document.documentElement.classList.remove('sf-table-column-resizing');

    if (!state) {
      return;
    }

    (0,_helpers_loaderDragState__WEBPACK_IMPORTED_MODULE_6__.setLoaderDragState)(false); // this.clearColumnResizeClasses(state.th);

    this._columnResizeState = null;
    this.outEvent({
      target: state.th
    });
    state.th.style.width = '';
    this.applyColumnResizeWidths(state.key, state.width, state.frozenWidths);
  }

  bindContextEvent() {
    if (this._contextEventBound) {
      return;
    }

    document.addEventListener("click", this.contextEvent);
    document.addEventListener("keydown", this.contextKeyEvent);
    this._contextEventBound = true;
  }

  unbindContextEvent() {
    if (!this._contextEventBound) {
      return;
    }

    document.removeEventListener("click", this.contextEvent);
    document.removeEventListener("keydown", this.contextKeyEvent);
    this._contextEventBound = false;
  }

  contextKeyEvent(event) {
    if (event.key !== 'Escape' || !this.state.contextMenu?.open) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    this.closeContextMenu(true);
  }

  getPortalContainer() {
    if (this._portalContainer?.isConnected) {
      return this._portalContainer;
    }

    const portal = document.createElement("div");
    portal.className = "sf-table-portal";
    portal.dataset.tableSettingsKey = this.getTableSettingsKey();
    document.body.append(portal);
    this._portalContainer = portal;
    return portal;
  }

  getVisualViewportRect() {
    const viewport = window.visualViewport;
    return {
      left: viewport?.offsetLeft || 0,
      top: viewport?.offsetTop || 0,
      right: (viewport?.offsetLeft || 0) + (viewport?.width || window.innerWidth),
      bottom: (viewport?.offsetTop || 0) + (viewport?.height || window.innerHeight)
    };
  }

  clampContextMenusToViewport() {
    const portal = this._portalContainer;

    if (!portal?.isConnected) {
      return;
    }

    const viewport = this.getVisualViewportRect();
    const rootStyle = getComputedStyle(document.documentElement);
    const inset = parseFloat(rootStyle.getPropertyValue('--sf-space-1/3')) || 0;
    portal.querySelectorAll('sf-context-menu').forEach(menu => {
      const availableInlineSize = Math.max(0, viewport.right - viewport.left - inset * 2);
      const availableBlockSize = Math.max(0, viewport.bottom - viewport.top - inset * 2);
      menu.style.setProperty('--sf-table-context-menu--available-inline-size', `${availableInlineSize}px`);
      menu.style.setProperty('--sf-context-menu-available-block-size', `${availableBlockSize}px`);
      menu.style.translate = '';
      const surface = menu.querySelector(':scope > .sf-context-menu') || menu;
      const rect = surface.getBoundingClientRect();
      let offsetX = 0;
      let offsetY = 0;

      if (rect.left < viewport.left + inset) {
        offsetX = viewport.left + inset - rect.left;
      } else if (rect.right > viewport.right - inset) {
        offsetX = viewport.right - inset - rect.right;
      }

      if (rect.top < viewport.top + inset) {
        offsetY = viewport.top + inset - rect.top;
      } else if (rect.bottom > viewport.bottom - inset) {
        offsetY = viewport.bottom - inset - rect.bottom;
      }

      if (offsetX || offsetY) {
        menu.style.translate = `${offsetX}px ${offsetY}px`;
      }
    });
  }

  contextViewportEvent() {
    requestAnimationFrame(() => this.clampContextMenusToViewport());
  }

  bindContextViewport() {
    if (this._contextViewportBound) {
      return;
    }

    window.addEventListener('resize', this.contextViewportEvent);
    window.visualViewport?.addEventListener('resize', this.contextViewportEvent);
    window.visualViewport?.addEventListener('scroll', this.contextViewportEvent);
    this._contextViewportBound = true;
  }

  unbindContextViewport() {
    if (!this._contextViewportBound) {
      return;
    }

    window.removeEventListener('resize', this.contextViewportEvent);
    window.visualViewport?.removeEventListener('resize', this.contextViewportEvent);
    window.visualViewport?.removeEventListener('scroll', this.contextViewportEvent);
    this._contextViewportBound = false;
  }

  openContextMenu(event, opts = {}) {
    this.closeContextMenu(false);
    this.contextTarget?.classList.remove("active");
    const target = event.currentTarget instanceof Element ? event.currentTarget : event.target instanceof Element ? event.target : null;

    if (!target) {
      return;
    }

    this.contextTarget = target;
    this.contextTarget.classList.add("active");
    let pos = this.contextTarget.getBoundingClientRect();
    event.preventDefault();
    event.stopPropagation();
    let data = {
      open: true,
      id: this.contextTarget,
      ...opts
    };
    let tempData = {};

    switch (opts.type) {
      case 'left':
        tempData = {
          x: pos.left + pos.width,
          y: pos.y + pos.height / 2
        };
        break;

      case 'bottom':
        {
          let parent = null;

          if (opts.parent) {
            parent = (0,_helpers_dom__WEBPACK_IMPORTED_MODULE_4__.getParent)(event.target);
            pos = parent.getBoundingClientRect();
          }

          tempData = {
            x: pos.left,
            y: pos.y + pos.height
          };
          break;
        }

      default:
        tempData = {
          x: pos.left,
          y: pos.y + pos.height
        };
    }

    data = { ...data,
      ...tempData
    };
    this.set({
      contextMenu: { ...data
      }
    });
  }

  openContextSubmenu(event, opts = {}) {
    event.preventDefault();
    event.stopPropagation();
    const target = event.target instanceof Element ? event.target : null;
    const anchorTarget = event.currentTarget instanceof Element ? event.currentTarget : target;

    if (!anchorTarget) {
      return;
    }

    const rect = anchorTarget.getBoundingClientRect();
    const anchorKey = opts.anchorKey || opts.template?.key || anchorTarget.dataset?.contextSubmenuAnchor || "";

    if (anchorKey && this.state.contextMenu?.submenu?.open && this.state.contextMenu.submenu.anchorKey === anchorKey) {
      this.closeContextSubmenu();
      return;
    }

    const data = {
      open: true,
      type: opts.type,
      anchorKey,
      x: rect.left + rect.width,
      y: rect.y + rect.height / 2,
      position: opts.position || 'left',
      transform: -50,
      ...opts
    };
    this.set(state => ({
      contextMenu: { ...(state.contextMenu || {}),
        submenu: data
      }
    }));
  }

  handleContextLayerClick(event) {
    const target = event.target;

    if (!(target instanceof Element)) {
      return;
    }

    if (target.closest("[data-context-submenu]")) {
      return;
    }

    if (target.closest("[data-context-submenu-anchor]")) {
      return;
    }

    if (this.state.contextMenu?.submenu?.open && !target.closest("[data-context-main-menu]")) {
      this.closeContextSubmenu();
    }
  }

  closeContextSubmenu() {
    this.set(state => ({
      contextMenu: { ...(state.contextMenu || {}),
        submenu: null
      }
    }));
  }

  getSelectColumn() {
    return { ...this.TABLE_SELECT_COLUMN,
      component: { ...this.TABLE_SELECT_COLUMN.component,
        props: { ...(this.TABLE_SELECT_COLUMN.component?.props || {}),
          checked: Boolean(this.state?.settingsChecked),
          indeterminate: Boolean(this.state?.selectIndeterminate)
        }
      }
    };
  }

  getColumns() {
    const dataColumns = this.getTableData().columns || [];
    const systemColumns = this.getSystemColumns();
    const systemKeys = new Set(systemColumns.map(column => column.key));
    const rightSystemKeys = new Set(['actions']);
    const originalIndexByKey = new Map(dataColumns.map((column, index) => [column?.key, index]));
    const userColumns = dataColumns.filter(column => column?.key && !systemKeys.has(column.key)).map(column => {
      const settings = this.getColumnSetting(column.key);
      const nextColumn = { ...column,
        ...settings
      };

      if (settings.width) {
        nextColumn.colWidth = settings.width;
      }

      return nextColumn;
    }).sort((left, right) => {
      const getOrder = column => {
        const order = column?.order;
        return order !== null && typeof order !== "undefined" && order !== "" && Number.isFinite(Number(order)) ? Number(order) : originalIndexByKey.get(column?.key) ?? 0;
      };

      const leftOrder = getOrder(left);
      const rightOrder = getOrder(right);

      if (leftOrder === rightOrder) {
        return (originalIndexByKey.get(left.key) ?? 0) - (originalIndexByKey.get(right.key) ?? 0);
      }

      return leftOrder - rightOrder;
    });
    const leftSystemColumns = systemColumns.filter(column => !rightSystemKeys.has(column?.key));
    const rightSystemColumns = systemColumns.filter(column => rightSystemKeys.has(column?.key));
    return [...leftSystemColumns, ...userColumns, ...rightSystemColumns].filter(column => column?.visible !== false);
  }

  createRowComponentCell(component, props = {}) {
    if (!component?.type) {
      return null;
    }

    return {
      component: {
        type: component.type,
        props: { ...(component.props || {}),
          ...props
        }
      }
    };
  }

  getActionRowItems(row = {}) {
    return this.ACTIONS_SETTINGS_ROW.map(action => {
      const component = action.component || {};
      const props = { ...(action.props || {}),
        ...(component.props || {})
      };
      const menu = action.menu || props.menu;
      const hasClickHandler = typeof props["@click"] === "function" || typeof props.onClick === "function";

      if (menu && !hasClickHandler) {
        props["@click"] = event => {
          const menuOptions = menu && typeof menu === "object" ? menu : {
            menu
          };
          this.openContextMenu(event, {
            type: "left",
            row,
            action,
            ...menuOptions
          });
        };
      }

      return { ...action,
        component: { ...component,
          props
        }
      };
    });
  }

  getDefaultRowCell(column, row) {
    if (!column?.key) {
      return null;
    }

    if (column.key === "select") {
      return this.createRowComponentCell(column.rowComponent, {
        checked: this.state.settingsChecked ? true : Boolean(row?.selected || row?.checked),
        value: row?.id ?? row?.value ?? ""
      });
    }

    if (column.key === "settings") {
      return this.createRowComponentCell(column.rowComponent || {
        type: "icon-button",
        props: {
          size: "1",
          type: "link",
          scheme: "on-surface",
          icon: "menu",
          ariaLabel: "Меню",
          value: "menu",
          "@click": e => {
            this.openContextMenu(e, {
              type: 'left',
              menu: 'row-settings'
            });
          }
        }
      });
    }

    if (column.key === 'actions') {
      return this.getActionRowItems(row);
    }

    return null;
  }

  normalizeRow(row = {}) {
    if (!row || typeof row !== "object" || Array.isArray(row)) {
      return row;
    }

    const nextRow = { ...row
    };
    this.getSystemColumns().forEach(column => {
      if (!column?.key || typeof nextRow[column.key] !== "undefined" || column.visible === false) {
        return;
      }

      const cell = this.getDefaultRowCell(column, nextRow);

      if (cell) {
        nextRow[column.key] = cell;
      }
    });
    return nextRow;
  }

  normalizeRows(rows = []) {
    return Array.isArray(rows) ? rows.map(row => this.normalizeRow(row)) : [];
  }

  setTableData(patch = {}, reason = "data") {
    const currentData = this.getTableData();
    const nextPatch = typeof patch === "function" ? patch(currentData) : patch;

    if (!nextPatch || typeof nextPatch !== "object") {
      return this;
    }

    return this.set({
      data: { ...currentData,
        ...nextPatch
      }
    }, reason);
  }

  setColumns(columns = [], reason = "columns") {
    const currentColumns = this.getTableData().columns || [];
    const nextColumns = typeof columns === "function" ? columns(currentColumns) : columns;
    return this.setTableData({
      columns: Array.isArray(nextColumns) ? nextColumns : []
    }, reason);
  }

  addColumns(columns = []) {
    const nextColumns = Array.isArray(columns) ? columns : [columns];
    return this.setColumns(currentColumns => [...currentColumns, ...nextColumns.filter(Boolean)]);
  }

  updateColumn(columnKey, patch = {}, reason = "columns") {
    if (!columnKey) {
      return this;
    }

    return this.setColumns(columns => columns.map((column, index) => {
      if (!column || column.key !== columnKey) {
        return column;
      }

      const nextPatch = typeof patch === "function" ? patch(column, index) : patch;
      return { ...column,
        ...(nextPatch || {})
      };
    }), reason);
  }

  setRows(rows = [], reason = "rows") {
    const currentRows = this.getTableData().rows || [];
    const nextRows = typeof rows === "function" ? rows(currentRows) : rows;
    return this.setTableData({
      rows: this.normalizeRows(nextRows)
    }, reason);
  }

  addRows(rows = [], reason = "rows") {
    const nextRows = Array.isArray(rows) ? rows : [rows];
    return this.setRows(currentRows => [...currentRows, ...nextRows.filter(Boolean)], reason);
  }

  updateRowsByKey(key, value, patch = {}, reason = "rows") {
    if (!key) {
      return this;
    }

    const patchRow = typeof patch === "function" ? patch : row => ({ ...row,
      ...(patch || {})
    });
    return this.setRows(rows => rows.map((row, index) => {
      if (!row || row[key] !== value) {
        return row;
      }

      const nextRow = patchRow(row, index);
      return nextRow && typeof nextRow === "object" ? nextRow : row;
    }), reason);
  }

  updateRowByKey(key, value, patch = {}, reason = "row") {
    return this.updateRowsByKey(key, value, patch, reason);
  }

  updateRows(patch = {}, reason = "rows") {
    const patchRow = typeof patch === "function" ? patch : row => ({ ...row,
      ...(patch || {})
    });
    return this.setRows(rows => rows.map((row, index) => {
      const nextRow = patchRow(row, index);
      return nextRow && typeof nextRow === "object" ? nextRow : row;
    }), reason);
  }

  updateRowCell(rowKey, rowValue, cellKey, patch = {}, reason = "cell") {
    if (!cellKey) {
      return this;
    }

    return this.updateRowsByKey(rowKey, rowValue, row => {
      const currentCell = row && row[cellKey] && typeof row[cellKey] === "object" ? row[cellKey] : {};
      const nextCellPatch = typeof patch === "function" ? patch(currentCell, row) : patch;
      return { ...row,
        [cellKey]: { ...currentCell,
          ...(nextCellPatch || {})
        }
      };
    }, reason);
  }

  updateRowCellProps(rowKey, rowValue, cellKey, props = {}, reason = "cell-props") {
    return this.updateRowCell(rowKey, rowValue, cellKey, cell => ({
      component: { ...(cell.component || {}),
        props: { ...(cell.component?.props || {}),
          ...(typeof props === "function" ? props(cell.component?.props || {}, cell) : props || {})
        }
      }
    }), reason);
  }

  closeContextMenu(restoreFocus = true) {
    const trigger = this.contextTarget;
    this.set({
      contextMenu: {
        open: false
      }
    });
    (0,lit__WEBPACK_IMPORTED_MODULE_2__.render)(null, this.getPortalContainer());

    if (this.refs.contextMenu) {
      this.refs.contextMenu.value = null;
    }

    this.unbindContextEvent();
    this.unbindContextViewport();
    this.contextTarget?.classList.remove("active");
    this.contextTarget = null;

    if (restoreFocus && trigger?.isConnected && typeof trigger.focus === 'function') {
      requestAnimationFrame(() => trigger.focus());
    }
  }

  get templateName() {
    return this.getAttribute("template") || "default";
  }

  get dataState() {
    return this.getEnumAttr('data-state', ['loading', 'populated', 'empty', 'error'], 'auto');
  }

  setDataState(state = 'auto') {
    const normalized = ['loading', 'populated', 'empty', 'error'].includes(state) ? state : 'auto';

    if (normalized === 'auto') {
      this.removeAttribute('data-state');
    } else {
      this.setAttribute('data-state', normalized);
    }

    return this;
  }

  requestRetry() {
    return this.dispatchTableEvent('sf-table-retry');
  }

  disconnectedCallback() {
    this.clearSearchDebounce();
    this._searchPendingProps = null;
    this._searchHoldKey = '';
    this.clearFilterTemplateDragBindings();
    this.unbindContextEvent();
    this.unbindContextViewport();
    this.unBindHoverEvent();
    this.unbindColumnResizeEvent();
    this.unbindColumnDragEvent();
    this.clearColumnDragState(false);
    this.onColumnResizePointerUp();

    if (this._portalContainer) {
      (0,lit__WEBPACK_IMPORTED_MODULE_2__.render)(null, this._portalContainer);

      this._portalContainer.remove();

      this._portalContainer = null;
    }

    super.disconnectedCallback();
  }

  templateContext() {
    const props = this.getPropsContext();
    return this.createTemplateContext({ ...props,
      component: this,
      rootClass: this.getRootClass(),
      rootStyle: this.getRootStyle()
    });
  }

  setCheckboxHeight() {
    if (!this.refs.items.size) return;
    const measureToken = ++this._checkboxMeasureToken;
    requestAnimationFrame(() => {
      if (!this.isConnected || measureToken !== this._checkboxMeasureToken) {
        return;
      }

      const parentSpace = (0,_helpers_dom__WEBPACK_IMPORTED_MODULE_4__.setParentSpace)(this.refs.items);

      if (!parentSpace) {
        return;
      }

      const {
        item,
        gap,
        parent
      } = parentSpace;
      this.parentGap = gap;
      const itemHeight = item.tagName.toLowerCase().startsWith('sf-') ? item.children[0]?.clientHeight : item.clientHeight;

      if (!itemHeight) {
        return;
      }

      const itemSize = parent.dataset.size || 8;
      const parentHeight = (itemHeight + gap) * itemSize;

      if (parent.clientHeight <= parentHeight) {
        return false;
      }

      parent.setAttribute('style', `height: ${parentHeight}px; overflow: auto;`);
    });
  }

  beforeRender() {
    super.beforeRender();
    this._checkboxMeasureToken++;
    this.refs.items = new Map();
  }

  afterRender() {
    this.setCheckboxHeight();
    this.bindHoverEvent();
    this.bindColumnResizeEvent();
    this.bindColumnDragEvent();
  }

  template() {
    return (0,_js_templates_default__WEBPACK_IMPORTED_MODULE_1__.renderTableTemplate)(this.templateContext());
  }

}

SfTable.define("sf-table");
})();

/******/ })()
;