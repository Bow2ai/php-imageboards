/**
 * style.js – Vanilla‑JS theme selector for vichan
 * ------------------------------------------------
 * Converts the hidden <div class="styles"> link list into a
 * fixed‑position <select> so users can switch themes.
 *
 * This version places the selector in the **bottom‑right** corner.
 */

document.addEventListener('DOMContentLoaded', () => {
  // locate the link list vichan outputs
  const stylesDiv = document.querySelector('div.styles');
  if (!stylesDiv) return; // bail if not found

  /* ---------- build the <select> ---------- */
  const select = document.createElement('select');

  Array.from(stylesDiv.children).forEach((linkEl, idx) => {
    const cleanName = linkEl.textContent.replace(/^\[|\]$/g, '');
    const linkId = `style-select-${idx}`;
    linkEl.id = linkId;

    const opt = document.createElement('option');
    opt.textContent = cleanName;
    opt.dataset.linkId = linkId;
    if (linkEl.classList.contains('selected')) opt.selected = true;
    select.appendChild(opt);
  });

  /* ---------- react to user choice ---------- */
  select.addEventListener('change', e => {
    const linkId = e.target.selectedOptions[0].dataset.linkId;
    document.getElementById(linkId)?.click();
  });

  /* ---------- hide original list & show UI ---------- */
  stylesDiv.style.display = 'none';

  const wrapper = document.createElement('div');
  wrapper.id = 'style-select-wrapper';
  wrapper.style.cssText =
    'position:fixed;bottom:10px;right:10px;z-index:9999;' +
    'background:rgba(255,255,255,.8);padding:4px;border-radius:4px;';

  wrapper.append('Style: ', select);
  document.body.appendChild(wrapper);
});
