/* inline-expanding.js — vanilla JS replacement (no jQuery) */
(() => {
  'use strict';

  /** Read user-set limit; fall back to 5 */
  const MAX = (() => {
    const n = parseInt(localStorage.inline_expand_max, 10);
    return Number.isFinite(n) && n >= 0 ? n : 5;
  })();

  class LoaderQueue {
    #loading = 0;
    #queue   = [];

    constructor(max) {
      this.max = max;    // 0 = unlimited
    }

    #next() {
      if (this.#queue.length && (this.#loading < this.max || this.max === 0)) {
        this.#loading++;
        (this.#queue.shift())();
      }
    }

    add(anchor) {
      const thumb = anchor.querySelector('img.post-image');
      const full  = document.createElement('img');
      full.className = 'full-image';
      full.hidden    = true;
      full.alt       = 'Full-size';
      anchor.append(full);

      const start = () => {
        anchor.dataset.loading = 'true';
        thumb.style.opacity = '0.35';
        full.src = anchor.href;
      };

      full.addEventListener('load', () => {
        anchor.dataset.loading = '';
        thumb.hidden  = true;
        full.hidden   = false;
        thumb.style.opacity = '';
        this.#loading--;
        this.#next();
      }, { once: true });

      if (this.#loading < this.max || this.max === 0) {
        this.#loading++;
        start();
      } else {
        this.#queue.push(start);
      }
    }

    collapse(anchor) {
      const thumb = anchor.querySelector('img.post-image');
      const full  = anchor.querySelector('img.full-image');
      if (full) full.remove();
      thumb.hidden = false;
      thumb.style.opacity = '';
      anchor.dataset.expanded = '';
      anchor.dataset.loading  = '';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const loader = new LoaderQueue(MAX);

    document.querySelectorAll('a').forEach((a) => {
      const thumb = a.querySelector('img.post-image');
      if (!thumb) return;                          // not a thumbnail link

      a.addEventListener('click', (ev) => {
        // allow middle-click / Ctrl-click to open in new tab
        if (ev.ctrlKey || ev.button === 1) return;

        ev.preventDefault();
        if (a.dataset.expanded === 'true') {
          loader.collapse(a);
        } else {
          a.dataset.expanded = 'true';
          loader.add(a);
        }
      });
    });
  });
})();
