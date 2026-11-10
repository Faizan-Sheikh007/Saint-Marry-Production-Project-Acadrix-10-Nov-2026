/*
 * Acadrix automatic AJAX filters
 * --------------------------------
 * Makes every GET filter form inside the application work without requiring
 * the user to click Filter / Apply / Load / Search.
 *
 * Supported forms:
 *   - form[data-ajax-filter]  -> handled by this global AJAX loader
 *   - form[data-ajax-form]    -> page-specific AJAX handler gets first chance
 *   - any other GET form inside main.page-content -> global AJAX fallback
 *
 * Behaviour:
 *   - select / checkbox / radio / date / month => refresh immediately
 *   - text / search / number / email / tel      => debounced refresh
 *   - previous request is aborted when a newer filter is selected
 *   - URL/history is kept in sync
 *   - normal submit buttons still work as a progressive fallback
 */
(function () {
  'use strict';

  const MAIN_SELECTOR = 'main.page-content';
  const FILTER_SELECTOR = [
    'form[data-ajax-filter]',
    'form[data-ajax-form]',
    'main.page-content form[method="GET"]',
    'main.page-content form[method="get"]'
  ].join(',');
  const FILTER_LINK_SELECTOR = 'a[data-ajax-filter-link]';
  const TEXT_TYPES = new Set(['text', 'search', 'email', 'tel', 'url', 'number']);

  const timers = new WeakMap();
  const recentSubmit = new WeakMap();
  let activeController = null;
  let activeRequestToken = 0;
  let ajaxHistoryActive = false;

  // Marker useful when checking the browser console after deployment.
  window.AcadrixAjaxFiltersVersion = '2026-09-07.4';

  function sameOrigin(url) {
    try {
      return new URL(url, window.location.href).origin === window.location.origin;
    } catch (_) {
      return false;
    }
  }

  function formMethod(form) {
    return (form.getAttribute('method') || 'GET').trim().toUpperCase();
  }

  function isFilterForm(form) {
    if (!form || !(form instanceof HTMLFormElement)) return false;
    if (!form.matches(FILTER_SELECTOR)) return false;
    if (form.hasAttribute('data-no-ajax') || form.hasAttribute('data-ajax-manual')) return false;
    if ((form.getAttribute('target') || '').toLowerCase() === '_blank') return false;
    if (formMethod(form) !== 'GET') return false;
    return sameOrigin(form.getAttribute('action') || window.location.href);
  }

  function isSpecialisedForm(form) {
    return form && form.hasAttribute('data-ajax-form');
  }

  function formUrl(form) {
    const action = form.getAttribute('action') || window.location.href;
    const url = new URL(action, window.location.href);
    const params = new URLSearchParams();
    const data = new FormData(form);

    for (const pair of data.entries()) {
      const key = pair[0];
      const value = pair[1];
      if (typeof File !== 'undefined' && value instanceof File) continue;

      let control = null;
      try { control = form.elements.namedItem(key); } catch (_) {}
      const isSingleControl = control && typeof control.length === 'undefined';
      const isHidden = !!(isSingleControl && control.type === 'hidden');

      if (String(value) === '' && !isHidden) continue;
      params.append(key, String(value));
    }

    url.search = params.toString();
    return url.toString();
  }

  function clearTimer(form) {
    const timer = timers.get(form);
    if (timer) clearTimeout(timer);
    timers.delete(form);
  }

  function debounceMs(form) {
    const value = Number(form.getAttribute('data-ajax-debounce'));
    return Number.isFinite(value) && value >= 0 ? value : 350;
  }

  function rememberSubmit(form, url) {
    recentSubmit.set(form, { url: url || formUrl(form), at: Date.now() });
  }

  function wasJustSubmitted(form, url, threshold) {
    const last = recentSubmit.get(form);
    if (!last) return false;
    return last.url === url && (Date.now() - last.at) < (threshold || 250);
  }

  function controlCanAutoSubmit(control) {
    if (!control || !control.form || !isFilterForm(control.form)) return false;
    if (control.disabled || control.readOnly || control.hasAttribute('data-ajax-no-autosubmit')) return false;

    if (control.tagName === 'SELECT' || control.tagName === 'TEXTAREA') return true;
    if (control.tagName !== 'INPUT') return false;

    const type = (control.type || 'text').toLowerCase();
    return !['hidden', 'file', 'submit', 'reset', 'button', 'image'].includes(type);
  }

  function showLoading() {
    const main = document.querySelector(MAIN_SELECTOR);
    if (main) {
      main.classList.add('ax-ajax-loading');
      main.setAttribute('aria-busy', 'true');
    }

    let bar = document.getElementById('axAjaxProgress');
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'axAjaxProgress';
      bar.className = 'ax-ajax-progress';
      bar.innerHTML = '<span></span>';
      document.body.appendChild(bar);
    }
    bar.classList.add('show');
  }

  function hideLoading() {
    const main = document.querySelector(MAIN_SELECTOR);
    if (main) {
      main.classList.remove('ax-ajax-loading');
      main.removeAttribute('aria-busy');
    }
    const bar = document.getElementById('axAjaxProgress');
    if (bar) bar.classList.remove('show');
  }

  function nodesCompatible(a, b) {
    if (!a || !b || a.nodeType !== b.nodeType) return false;
    if (a.nodeType === Node.ELEMENT_NODE) return a.tagName === b.tagName;
    return true;
  }

  function directChildById(parent, id, startNode) {
    if (!id) return null;
    let node = startNode || parent.firstChild;
    while (node) {
      if (node.nodeType === Node.ELEMENT_NODE && node.id === id) return node;
      node = node.nextSibling;
    }
    return null;
  }

  function syncAttributes(current, incoming) {
    const runtimeAttrs = new Set(['data-ax-date-enhanced', 'data-ax-last-value']);

    Array.from(current.attributes).forEach(function (attr) {
      if (runtimeAttrs.has(attr.name)) return;
      if (!incoming.hasAttribute(attr.name)) current.removeAttribute(attr.name);
    });

    Array.from(incoming.attributes).forEach(function (attr) {
      if (current.getAttribute(attr.name) !== attr.value) current.setAttribute(attr.name, attr.value);
    });
  }

  function syncFormState(current, incoming) {
    const tag = current.tagName;

    if (tag === 'INPUT') {
      const type = (current.type || '').toLowerCase();
      if (type === 'checkbox' || type === 'radio') current.checked = incoming.checked;
      else if (type !== 'file') current.value = incoming.value;
    } else if (tag === 'TEXTAREA') {
      current.value = incoming.value;
    } else if (tag === 'OPTION') {
      current.selected = incoming.selected;
    } else if (tag === 'SELECT') {
      Array.from(current.options).forEach(function (option, index) {
        if (incoming.options[index]) option.selected = incoming.options[index].selected;
      });
      current.value = incoming.value;
    }
  }

  function morphNode(current, incoming, preserveNode) {
    // The form that initiated an AJAX filter request is already in the
    // correct client-side state. Cascading filters often populate their
    // child <select> options in a change handler before this request starts.
    // Replacing/morphing that form with the server HTML would wipe those
    // freshly populated options (e.g. Session -> Program -> Semester).
    if (preserveNode && current === preserveNode) return current;

    if (!nodesCompatible(current, incoming)) {
      const replacement = incoming.cloneNode(true);
      current.replaceWith(replacement);
      return replacement;
    }

    if (current.nodeType === Node.TEXT_NODE || current.nodeType === Node.COMMENT_NODE) {
      if (current.nodeValue !== incoming.nodeValue) current.nodeValue = incoming.nodeValue;
      return current;
    }

    syncAttributes(current, incoming);
    morphChildren(current, incoming, preserveNode);
    syncFormState(current, incoming);
    return current;
  }

  function morphChildren(currentParent, incomingParent, preserveNode) {
    let cursor = currentParent.firstChild;

    Array.from(incomingParent.childNodes).forEach(function (incomingChild) {
      let match = null;
      const incomingId = incomingChild.nodeType === Node.ELEMENT_NODE ? incomingChild.id : '';

      if (incomingId) match = directChildById(currentParent, incomingId, cursor);

      if (!match && cursor && nodesCompatible(cursor, incomingChild)) {
        const cursorId = cursor.nodeType === Node.ELEMENT_NODE ? cursor.id : '';
        if (!incomingId || !cursorId || incomingId === cursorId) match = cursor;
      }

      if (!match && !incomingId) {
        let scan = cursor;
        while (scan) {
          const scanId = scan.nodeType === Node.ELEMENT_NODE ? scan.id : '';
          if (!scanId && nodesCompatible(scan, incomingChild)) {
            match = scan;
            break;
          }
          scan = scan.nextSibling;
        }
      }

      if (!match) {
        currentParent.insertBefore(incomingChild.cloneNode(true), cursor);
        return;
      }

      if (match !== cursor) currentParent.insertBefore(match, cursor);
      morphNode(match, incomingChild, preserveNode);
      cursor = match.nextSibling;
    });

    while (cursor) {
      const next = cursor.nextSibling;
      cursor.remove();
      cursor = next;
    }
  }

  function captureFocus() {
    const el = document.activeElement;
    if (!el || el === document.body) return null;

    return {
      id: el.id || '',
      name: el.getAttribute ? (el.getAttribute('name') || '') : '',
      start: typeof el.selectionStart === 'number' ? el.selectionStart : null,
      end: typeof el.selectionEnd === 'number' ? el.selectionEnd : null
    };
  }

  function restoreFocus(saved) {
    if (!saved) return;
    let el = null;

    if (saved.id) el = document.getElementById(saved.id);
    if (!el && saved.name) {
      try {
        const escaped = window.CSS && CSS.escape ? CSS.escape(saved.name) : saved.name.replace(/["\\]/g, '\\$&');
        el = document.querySelector('[name="' + escaped + '"]');
      } catch (_) {}
    }

    if (!el || typeof el.focus !== 'function') return;
    try { el.focus({ preventScroll: true }); } catch (_) { el.focus(); }

    if (saved.start !== null && typeof el.setSelectionRange === 'function') {
      try { el.setSelectionRange(saved.start, saved.end === null ? saved.start : saved.end); } catch (_) {}
    }
  }

  function updateSidebar(newDoc) {
    const currentSidebar = document.querySelector('.sidebar');
    const incomingSidebar = newDoc.querySelector('.sidebar');
    if (!currentSidebar || !incomingSidebar) return;

    currentSidebar.querySelectorAll('.active').forEach(function (el) { el.classList.remove('active'); });
    incomingSidebar.querySelectorAll('.active').forEach(function (incoming) {
      const href = incoming.getAttribute('href');
      if (!href) return;
      currentSidebar.querySelectorAll('a').forEach(function (link) {
        if (link.getAttribute('href') === href) link.classList.add('active');
      });
    });
  }

  function fireLoaded(url) {
    document.dispatchEvent(new CustomEvent('acadrix:ajax-filter-loaded', { detail: { url: url } }));
  }

  async function loadUrl(url, options) {
    options = options || {};
    const push = options.push !== false;
    const fallback = options.fallback !== false;

    if (!sameOrigin(url)) {
      window.location.assign(url);
      return;
    }

    if (activeController) activeController.abort();
    const controller = new AbortController();
    activeController = controller;
    const token = ++activeRequestToken;
    const focus = captureFocus();
    showLoading();

    try {
      const response = await fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        signal: controller.signal,
        headers: {
          'Accept': 'text/html, application/xhtml+xml',
          // Do not send X-Requested-With here. Controllers that return a bare
          // fragment for their own specialised AJAX flow must return the full
          // layout to the global fallback loader.
          'X-Acadrix-Ajax-Filter': '1'
        }
      });

      if (!response.ok) throw new Error('HTTP ' + response.status);

      const html = await response.text();
      const newDoc = new DOMParser().parseFromString(html, 'text/html');
      const currentMain = document.querySelector(MAIN_SELECTOR);
      const incomingMain = newDoc.querySelector(MAIN_SELECTOR);

      if (!currentMain || !incomingMain) throw new Error('AJAX response does not contain main.page-content');
      if (token !== activeRequestToken) return;

      morphNode(currentMain, incomingMain, options.preserveForm || null);
      updateSidebar(newDoc);

      if (newDoc.title) document.title = newDoc.title;
      if (push) {
        history.pushState(Object.assign({}, history.state || {}, { acadrixAjaxFilter: true }), '', url);
        ajaxHistoryActive = true;
      }

      restoreFocus(focus);
      fireLoaded(url);
    } catch (error) {
      if (error && error.name === 'AbortError') return;
      console.error('Acadrix AJAX filter failed:', error);
      if (fallback) window.location.assign(url);
    } finally {
      if (activeController === controller) {
        activeController = null;
        hideLoading();
      }
    }
  }

  // Auto-submit a specialised partial-AJAX form through its existing submit
  // handler. If the page-specific handler is missing/not initialised, fall back
  // to the global full-page AJAX renderer instead of doing nothing.
  function triggerSpecialised(form, url) {
    let event;
    try {
      event = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: null });
    } catch (_) {
      event = new Event('submit', { bubbles: true, cancelable: true });
    }

    const notCancelled = form.dispatchEvent(event);
    if (notCancelled) loadUrl(url, { preserveForm: form });
  }

  function submitAutomatically(form) {
    if (!isFilterForm(form)) return;

    clearTimer(form);
    const url = formUrl(form);
    if (wasJustSubmitted(form, url, 300)) return;
    rememberSubmit(form, url);

    if (isSpecialisedForm(form)) triggerSpecialised(form, url);
    else loadUrl(url, { preserveForm: form });
  }

  function scheduleAutoSubmit(form, delay) {
    if (!isFilterForm(form)) return;
    clearTimer(form);

    const run = function () {
      timers.delete(form);
      // Let all page-specific change handlers finish first. This is important
      // for Session -> Program -> Semester/Part cascading selects.
      submitAutomatically(form);
    };

    timers.set(form, setTimeout(run, Math.max(0, delay || 0)));
  }

  // Manual submit buttons continue to use AJAX. Page-specific data-ajax-form
  // submit handlers are registered before this script and therefore get first
  // chance; when they call preventDefault() we deliberately do nothing here.
  document.addEventListener('submit', function (event) {
    const form = event.target && event.target.closest ? event.target.closest('form') : null;
    if (!isFilterForm(form)) return;

    clearTimer(form);
    const url = formUrl(form);
    rememberSubmit(form, url);

    if (event.defaultPrevented) return;

    event.preventDefault();
    loadUrl(url, { preserveForm: form });
  });

  // Selects / radios / checkboxes / date / month / etc.
  document.addEventListener('change', function (event) {
    const control = event.target;
    if (!controlCanAutoSubmit(control)) return;

    const type = (control.type || '').toLowerCase();
    if (control.tagName === 'TEXTAREA' || TEXT_TYPES.has(type)) return;

    // 1ms/next-task instead of a synchronous nested submit, so any cascade
    // listener attached by the page can repopulate dependent selects first.
    scheduleAutoSubmit(control.form, 1);
  });

  // Search/text fields use debounce to avoid a request on every keystroke.
  document.addEventListener('input', function (event) {
    if (event.isComposing) return;
    const control = event.target;
    if (!controlCanAutoSubmit(control)) return;

    const type = (control.type || '').toLowerCase();
    if (control.tagName !== 'TEXTAREA' && !TEXT_TYPES.has(type)) return;

    scheduleAutoSubmit(control.form, debounceMs(control.form));
  });

  // Reset/filter-navigation links and pagination stay AJAX.
  document.addEventListener('click', function (event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
    if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
    if (link.hasAttribute('data-ajax-nav')) return;

    const explicit = link.matches(FILTER_LINK_SELECTOR);
    const pagination = !!link.closest('nav[role="navigation"][aria-label*="Pagination" i], nav[aria-label="Pagination Navigation"]');
    const pageHasFilters = !!document.querySelector(FILTER_SELECTOR);

    if (!explicit && !(pagination && pageHasFilters)) return;
    if (!sameOrigin(link.href)) return;

    event.preventDefault();
    loadUrl(link.href);
  });

  window.addEventListener('popstate', function () {
    if (!ajaxHistoryActive && !(history.state && history.state.acadrixAjaxFilter)) return;
    loadUrl(window.location.href, { push: false, fallback: true });
  });

  // Public helper used by any page that wants to trigger the same behaviour.
  window.AcadrixAjaxFilters = {
    version: window.AcadrixAjaxFiltersVersion,
    load: function (url, options) { return loadUrl(url, options || {}); },
    submit: function (form) {
      if (!isFilterForm(form)) return false;
      submitAutomatically(form);
      return true;
    },
    isFilterForm: isFilterForm
  };

  document.documentElement.classList.add('ax-auto-filters-ready');
})();
