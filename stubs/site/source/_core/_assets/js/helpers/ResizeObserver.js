import setReadModePosition from './functions';

export class SizeObserver {
  constructor() {
    this.lastWidth = null;
    this.setTopMenu = false;
    this.setCollapsed = false;
    this.menuResizeQueued = false;
    this.menuResizeWidth = null;
    this.mainResizeQueued = false;
    this.mainResizeWidth = null;
    this.menuWrap = document.querySelector('.sf-menu-container');
    this.menu = this.menuWrap.querySelector('#top_menu');
    this.readState = false;
    this.headerWrap = document.querySelector('.header--wrap');
    this.body = document.querySelector('body');
    this.main = document.querySelector('main');
    this.readMode = document.getElementById('read_mode');
    this.navMenu = document.getElementById('main_menu');
    if (this.headerWrap) {
      this.headerRight = this.headerWrap.querySelector('.header--right');
      this.logo = this.headerWrap.querySelector('a.logo');
    }
    this.setObserver();
  }

  mutate = (fn) => requestAnimationFrame(fn);
  applyMenuState = (width) => {
    if (!this.menuWrap) return;

    // Calculate available width vs required width of the menu content.
    const fallbackWidth = width > 0 ? width : window.innerWidth;
    const available =
      this.menuWrap.getBoundingClientRect().width ??
      fallbackWidth;
    const required = this.menu.scrollWidth || fallbackWidth;
    const shouldCollapse = required > available;
    if (shouldCollapse) {
      if (this.setCollapsed) return;
      this.mutate(() => {
        this.menuWrap.classList.add('menu--collapsed', 'p-right-5');
        window.updateMenuScrollButtons?.();
      });
      this.setCollapsed = true;
    } else {
      if (!this.setCollapsed) return;
      this.mutate(() => {
        this.menuWrap.classList.remove('menu--collapsed', 'p-right-5');
        window.updateMenuScrollButtons?.();
      });
      this.setCollapsed = false;
    }
  };
  applyMainWidth = (width) => {
    const w = width | 0;
    if (w === this.lastWidth) return;
    this.lastWidth = w;
    this.placeReadModeForWidth(w);

    if (w < 980 && w > 768) {
      if (this.setTopMenu && this.menuWrap) {
        this.setTopMenu = false;
        this.mutate(() => this.logo.after(this.menuWrap));
      }
    } else if (w <= 768) {
      if (!this.setTopMenu && this.menuWrap) {
        this.setTopMenu = true;
        this.mutate(() => this.navMenu.prepend(this.menuWrap));
      }
    } else {
      if (this.setTopMenu && this.menuWrap) {
        this.setTopMenu = false;
        this.mutate(() => this.logo.after(this.menuWrap));
      }
    }
  };
  placeReadModeForWidth = (width) => {
    if (!this.readMode) return;
    if (width < 980) {
      this.mutate(() => {
        requestAnimationFrame(() => {
          if (!this.main || !this.readMode) return;
          setReadModePosition(this.main, this.readMode);
        });
        if (!this.readState) {
          this.readState = true;
        }
      });
    } else {
      this.mutate(() => {
        requestAnimationFrame(() => {
          if (!this.main || !this.readMode || !this.readState) return;
          setReadModePosition(this.main, this.readMode);
        });
        if (this.readState) {
          this.readState = false;
        }
      });
    }
  };
  setObserver = () => {
    if (this.menuWrap) {
      this.menuObserver = new ResizeObserver((entries) => {
        for (const entry of entries) {
          this.scheduleMenuResize(entry.contentRect.width);
        }
      });
      this.menuObserver.observe(this.menuWrap);
    }

    this.mainObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        this.scheduleMainResize(entry.contentRect.width);
      }
    });
  };

  scheduleMenuResize = (width) => {
    this.menuResizeWidth = width;
    if (this.menuResizeQueued) return;
    this.menuResizeQueued = true;
    requestAnimationFrame(() => {
      this.menuResizeQueued = false;
      this.applyMenuState(this.menuResizeWidth);
    });
  };

  scheduleMainResize = (width) => {
    this.mainResizeWidth = width;
    if (this.mainResizeQueued) return;
    this.mainResizeQueued = true;
    requestAnimationFrame(() => {
      this.mainResizeQueued = false;
      this.applyMainWidth(this.mainResizeWidth);
    });
  };

  init() {
    const initialWidth =
      this.body?.getBoundingClientRect().width ?? window.innerWidth;
    this.applyMainWidth(initialWidth);
    // Apply menu state once layout is painted to ensure width is available.
    requestAnimationFrame(() => {
      const menuWidth =
        this.menuWrap?.getBoundingClientRect().width ?? window.innerWidth;
      this.applyMenuState(menuWidth);
    });
    this.mainObserver.observe(this.body);
  }
}
