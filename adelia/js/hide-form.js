/* hide-form.js — vanilla JS, no jQuery (rev 2025‑06‑20) */
(() => {
  document.addEventListener('DOMContentLoaded', () => {

    /* Locate the posting form.
       1. Try the old selector (name="post").
       2. Fallback: any form that contains <input name="action" value="post">. */
    const form =
          document.querySelector('form[name="post"]') ??
          document.querySelector('form input[name="action"][value="post"]')?.form;

    if (!form) return;           // Nothing to do on pages without a post form.

    const isIndex  = window.active_page === 'index';
    const linkText = isIndex ? 'New' : 'New';

    /* Build the toggle link that reveals the form */
    const toggle = document.createElement('div');
    toggle.id = 'show-post-form';
    toggle.style.cssText =
      'font-size:175%;text-align:center;font-weight:bold;margin-bottom:5px';
    toggle.innerHTML = `[<a href="#" style="text-decoration:none">${linkText}</a>]`;

    form.after(toggle);   // Insert right after the form
    form.hidden = true;   // Hide form by default

    toggle.addEventListener('click', ev => {
      ev.preventDefault();
      toggle.hidden = true;
      form.hidden   = false;
      form.scrollIntoView({ block: 'center', behavior: 'smooth' });
      // Optional UX nicety: focus first input
      form.querySelector('input, textarea, select')?.focus();
    });
  });
})();
