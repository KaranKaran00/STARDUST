document.addEventListener('DOMContentLoaded', function () {
  // --- Mobile nav: clone the desktop links/actions into a stacked panel ---
  var toggle = document.getElementById('navToggle');
  var mobilePanel = document.getElementById('navLinksMobile');
  if (toggle && mobilePanel) {
    var links = document.querySelector('.nav-links');
    var actions = document.querySelector('.nav-actions');
    if (links) {
      var linksClone = links.cloneNode(true);
      linksClone.classList.remove('nav-links');
      linksClone.style.cssText = 'display:flex;flex-direction:column;gap:14px;';
      mobilePanel.appendChild(linksClone);
    }
    if (actions) {
      var actionsClone = actions.cloneNode(true);
      actionsClone.style.cssText = 'display:flex;flex-direction:column;gap:10px;';
      mobilePanel.appendChild(actionsClone);
    }
    mobilePanel.style.cssText = 'flex-direction:column;gap:14px;padding:16px 24px 20px;border-top:1px solid var(--line);';
    toggle.addEventListener('click', function () {
      var isHidden = mobilePanel.hasAttribute('hidden');
      if (isHidden) { mobilePanel.removeAttribute('hidden'); mobilePanel.style.display = 'flex'; toggle.setAttribute('aria-expanded', 'true'); }
      else { mobilePanel.setAttribute('hidden', ''); mobilePanel.style.display = 'none'; toggle.setAttribute('aria-expanded', 'false'); }
    });
  }

  // --- File input: show the chosen filename ---
  document.querySelectorAll('input[type=file]').forEach(function (input) {
    var hint = input.parentElement.querySelector('.file-name-hint');
    input.addEventListener('change', function () {
      if (hint && input.files.length) hint.textContent = 'Selected: ' + input.files[0].name;
    });
  });

  // --- Filter bar: submit on change so results update immediately ---
  document.querySelectorAll('.filter-bar[data-autosubmit]').forEach(function (form) {
    form.querySelectorAll('select, input[type=text]').forEach(function (el) {
      el.addEventListener('change', function () { form.submit(); });
    });
  });

  // --- Confirm before destructive actions ---
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
      if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
});
