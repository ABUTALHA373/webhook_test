/**
 * HookForge Enterprise Client Engine v2
 * Separate Webhook & Callback management, fully dynamic response engine
 */

(function () {
  'use strict';

  // ─── State ──────────────────────────────────────────────────────────────────
  const state = {
    endpoints: [],
    currentEndpointId: null,
    allRequests: [],
    requests: [],
    currentRequestId: null,
    currentRequestData: null,
    filterMethod: '',
    filterSearch: '',
    dispatcherPresets: {},
    activeTab: 'tab-inspector',
    activeSubtab: 'subtab-payload',
    sseConnected: false,
    endpointEditorMode: 'create', // 'create' | 'edit'
    callbackEditorMode: 'create', // 'create' | 'edit'
  };

  // ─── DOM Refs ────────────────────────────────────────────────────────────────
  const el = {
    // Header
    liveBadgeText: document.getElementById('liveBadgeText'),
    navTabs: document.querySelectorAll('.nav-tab'),
    tabPanes: document.querySelectorAll('.tab-pane'),

    // Feed Endpoint Custom Dropdown & URL Bar
    feedEndpointCustomDropdown: document.getElementById('feedEndpointCustomDropdown'),
    feedEndpointDropdownBtn: document.getElementById('feedEndpointDropdownBtn'),
    feedDropdownTitle: document.getElementById('feedDropdownTitle'),
    feedDropdownSubtitle: document.getElementById('feedDropdownSubtitle'),
    feedDropdownBadge: document.getElementById('feedDropdownBadge'),
    feedDropdownMenu: document.getElementById('feedDropdownMenu'),
    feedEndpointSelect: document.getElementById('feedEndpointSelect'),
    feedEndpointCountBadge: document.getElementById('feedEndpointCountBadge'),
    feedEndpointUrlBar: document.getElementById('feedEndpointUrlBar'),
    feedEndpointUrlText: document.getElementById('feedEndpointUrlText'),
    btnCopyFeedUrl: document.getElementById('btnCopyFeedUrl'),

    // Inspector
    requestsList: document.getElementById('requestsList'),
    emptyFeed: document.getElementById('emptyFeed'),
    searchFilter: document.getElementById('searchFilter'),
    methodFilters: document.querySelectorAll('.btn-filter'),
    btnClearFeed: document.getElementById('btnClearFeed'),
    reqCountBadge: document.getElementById('reqCountBadge'),

    // Details Pane
    detailsPaneEmpty: document.getElementById('detailsPaneEmpty'),
    detailsPaneContent: document.getElementById('detailsPaneContent'),
    detailsMethodBadge: document.getElementById('detailsMethodBadge'),
    detailsStatusBadge: document.getElementById('detailsStatusBadge'),
    detailsPath: document.getElementById('detailsPath'),
    subtabBtns: document.querySelectorAll('.subtab-btn'),
    subpaneTabs: document.querySelectorAll('.subpane-tab'),
    overviewTableBody: document.getElementById('overviewTableBody'),
    headersTableBody: document.getElementById('headersTableBody'),
    queryTableBody: document.getElementById('queryTableBody'),
    queryEmptyMsg: document.getElementById('queryEmptyMsg'),
    payloadPre: document.getElementById('payloadPre'),
    responseStatusBadge: document.getElementById('responseStatusBadge'),
    responseDurationText: document.getElementById('responseDurationText'),
    responseHeadersBody: document.getElementById('responseHeadersBody'),
    responseBodyPre: document.getElementById('responseBodyPre'),
    callbacksContainer: document.getElementById('callbacksContainer'),

    // Actions
    btnCopyCurl: document.getElementById('btnCopyCurl'),
    btnCopyFetch: document.getElementById('btnCopyFetch'),
    btnCopyPayload: document.getElementById('btnCopyPayload'),
    btnReplayModal: document.getElementById('btnReplayModal'),
    btnDeleteReq: document.getElementById('btnDeleteReq'),

    // Webhook Endpoints tab
    endpointsTableBody: document.getElementById('endpointsTableBody'),
    btnOpenCreateEndpoint: document.getElementById('btnOpenCreateEndpoint'),
    endpointEditorPanel: document.getElementById('endpointEditorPanel'),
    endpointEditorTitle: document.getElementById('endpointEditorTitle'),
    formEndpointEditor: document.getElementById('formEndpointEditor'),
    epEditorId: document.getElementById('epEditorId'),
    epEditorName: document.getElementById('epEditorName'),
    epEditorSlug: document.getElementById('epEditorSlug'),
    epEditorSecret: document.getElementById('epEditorSecret'),
    epEditorStatus: document.getElementById('epEditorStatus'),
    epEditorDelay: document.getElementById('epEditorDelay'),
    epEditorHeaderKV: document.getElementById('epEditorHeaderKV'),
    epEditorBody: document.getElementById('epEditorBody'),
    btnCloseEndpointEditor: document.getElementById('btnCloseEndpointEditor'),
    btnCancelEndpointEditor: document.getElementById('btnCancelEndpointEditor'),
    btnSubmitEndpointEditor: document.getElementById('btnSubmitEndpointEditor'),
    btnRunEvalPlayground: document.getElementById('btnRunEvalPlayground'),
    evalPreviewBox: document.getElementById('evalPreviewBox'),
    evalResultStatus: document.getElementById('evalResultStatus'),
    evalResultBody: document.getElementById('evalResultBody'),
    btnAddReqParam: document.getElementById('btnAddReqParam'),
    reqParamsList: document.getElementById('reqParamsList'),
    reqParamsEmpty: document.getElementById('reqParamsEmpty'),

    // Callbacks tab
    callbacksListContainer: document.getElementById('callbacksListContainer'),
    callbackFilterEndpoint: document.getElementById('callbackFilterEndpoint'),
    callbackRuleCount: document.getElementById('callbackRuleCount'),
    btnOpenAddCallback: document.getElementById('btnOpenAddCallback'),
    callbackEditorPanel: document.getElementById('callbackEditorPanel'),
    callbackEditorTitle: document.getElementById('callbackEditorTitle'),
    formCallbackEditor: document.getElementById('formCallbackEditor'),
    cbEditorId: document.getElementById('cbEditorId'),
    cbEditorName: document.getElementById('cbEditorName'),
    cbEditorEndpointId: document.getElementById('cbEditorEndpointId'),
    cbEditorMethod: document.getElementById('cbEditorMethod'),
    cbEditorDelay: document.getElementById('cbEditorDelay'),
    cbEditorRetries: document.getElementById('cbEditorRetries'),
    cbEditorTargetUrl: document.getElementById('cbEditorTargetUrl'),
    cbEditorPayloadMode: document.getElementById('cbEditorPayloadMode'),
    cbTemplateGroup: document.getElementById('cbTemplateGroup'),
    cbEditorTemplate: document.getElementById('cbEditorTemplate'),
    cbEditorCustomHeaders: document.getElementById('cbEditorCustomHeaders'),
    cbEditorHmacEnabled: document.getElementById('cbEditorHmacEnabled'),
    cbHmacFields: document.getElementById('cbHmacFields'),
    cbEditorHmacSecret: document.getElementById('cbEditorHmacSecret'),
    cbEditorHmacAlgo: document.getElementById('cbEditorHmacAlgo'),
    cbEditorHmacHeader: document.getElementById('cbEditorHmacHeader'),
    btnPreviewCallback: document.getElementById('btnPreviewCallback'),
    cbPreviewBox: document.getElementById('cbPreviewBox'),
    cbPreviewUrl: document.getElementById('cbPreviewUrl'),
    cbPreviewPayload: document.getElementById('cbPreviewPayload'),
    btnCloseCallbackEditor: document.getElementById('btnCloseCallbackEditor'),
    btnCancelCallbackEditor: document.getElementById('btnCancelCallbackEditor'),

    // Dispatcher
    presetSelect: document.getElementById('presetSelect'),
    dispatchUrl: document.getElementById('dispatchUrl'),
    dispatchMethod: document.getElementById('dispatchMethod'),
    dispatchPayload: document.getElementById('dispatchPayload'),
    dispatchHmacEnabled: document.getElementById('dispatchHmacEnabled'),
    dispatchHmacSecret: document.getElementById('dispatchHmacSecret'),
    dispatchHmacHeader: document.getElementById('dispatchHmacHeader'),
    btnSendDispatch: document.getElementById('btnSendDispatch'),
    btnSendBurst: document.getElementById('btnSendBurst'),
    dispatchResultCard: document.getElementById('dispatchResultCard'),
    dispatchResultStatus: document.getElementById('dispatchResultStatus'),
    dispatchResultDuration: document.getElementById('dispatchResultDuration'),
    dispatchResultBody: document.getElementById('dispatchResultBody'),
    dispatchHistoryList: document.getElementById('dispatchHistoryList'),

    // Modals
    modalReplay: document.getElementById('modalReplay'),
    replayUrlInput: document.getElementById('replayUrlInput'),
    replayMethodSelect: document.getElementById('replayMethodSelect'),
    replayBodyInput: document.getElementById('replayBodyInput'),
    btnConfirmReplay: document.getElementById('btnConfirmReplay'),

    // Toast
    toastContainer: document.getElementById('toastContainer'),

    // Theme
    btnThemeToggle: document.getElementById('btnThemeToggle'),
    themeIconSun: document.getElementById('themeIconSun'),
    themeIconMoon: document.getElementById('themeIconMoon'),

    // Quick Mock
    btnQuickMockEmpty: document.getElementById('btnQuickMockEmpty'),
  };

  // ─── Init ────────────────────────────────────────────────────────────────────
  async function init() {
    initTheme();
    setupEventListeners();
    await loadEndpoints();
    await loadDispatcherPresets();
    await loadDispatchHistory();
    initSSE();
  }

  // ─── Event Listeners ─────────────────────────────────────────────────────────
  function setupEventListeners() {
    // Navigation Tabs
    el.navTabs.forEach((tab) => {
      tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });

    // Sub-Tabs
    el.subtabBtns.forEach((btn) => {
      btn.addEventListener('click', () => switchSubtab(btn.dataset.subtab));
    });

    // Custom Dropdown for Feed Endpoint
    if (el.feedEndpointDropdownBtn && el.feedEndpointCustomDropdown) {
      el.feedEndpointDropdownBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = el.feedEndpointCustomDropdown.classList.contains('open');
        closeAllDropdowns();
        if (!isOpen) {
          el.feedEndpointCustomDropdown.classList.add('open');
          el.feedEndpointDropdownBtn.setAttribute('aria-expanded', 'true');
        }
      });
    }

    // Dismiss custom dropdowns when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.custom-dropdown')) {
        closeAllDropdowns();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeAllDropdowns();
      }
    });

    // Copy Feed Endpoint Direct Webhook URL
    if (el.btnCopyFeedUrl && el.feedEndpointUrlText) {
      el.btnCopyFeedUrl.addEventListener('click', () => {
        const text = el.feedEndpointUrlText.textContent;
        if (text) {
          navigator.clipboard.writeText(text);
          showToast('Webhook URL copied!', 'success');
        }
      });
    }

    // Method Filter Buttons
    el.methodFilters.forEach((btn) => {
      btn.addEventListener('click', () => {
        el.methodFilters.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        state.filterMethod = btn.dataset.method || '';
        applyFilters(true);
      });
    });

    // Search Box
    el.searchFilter.addEventListener('input', (e) => {
      state.filterSearch = e.target.value.trim();
      applyFilters(true);
    });

    // Clear Feed
    el.btnClearFeed.addEventListener('click', async () => {
      if (!confirm('Clear all captured requests for this view?')) return;
      try {
        await fetch('/api/requests/clear', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ endpoint_id: state.currentEndpointId }),
        });
        showToast('Requests cleared', 'info');
        state.currentRequestId = null;
        state.currentRequestData = null;
        renderDetails();
        loadRequests();
      } catch (err) {
        showToast('Error clearing requests: ' + err.message, 'error');
      }
    });

    // Copy cURL
    el.btnCopyCurl.addEventListener('click', () => {
      if (!state.currentRequestData) return;
      const req = state.currentRequestData;
      let curl = `curl -X ${req.method} "${req.url}"`;
      if (req.headers) {
        for (const [k, v] of Object.entries(req.headers)) {
          if (!['host', 'content-length'].includes(k.toLowerCase())) {
            curl += ` \\\n  -H "${k}: ${v}"`;
          }
        }
      }
      if (req.raw_body) curl += ` \\\n  -d "${req.raw_body.replace(/"/g, '\\"')}"`;
      navigator.clipboard.writeText(curl);
      showToast('cURL command copied!', 'success');
    });

    // Copy Fetch
    el.btnCopyFetch.addEventListener('click', () => {
      if (!state.currentRequestData) return;
      const req = state.currentRequestData;
      const options = { method: req.method, headers: req.headers || {} };
      if (['POST', 'PUT', 'PATCH'].includes(req.method) && req.raw_body) options.body = req.raw_body;
      navigator.clipboard.writeText(`fetch("${req.url}", ${JSON.stringify(options, null, 2)});`);
      showToast('JS Fetch snippet copied!', 'success');
    });

    // Copy Payload
    el.btnCopyPayload.addEventListener('click', () => {
      if (!state.currentRequestData) return;
      navigator.clipboard.writeText(state.currentRequestData.raw_body || '');
      showToast('Payload copied!', 'success');
    });

    // Delete Request
    el.btnDeleteReq.addEventListener('click', async () => {
      if (!state.currentRequestId) return;
      try {
        await fetch(`/api/requests/${state.currentRequestId}`, { method: 'DELETE' });
        showToast('Request deleted', 'info');
        state.currentRequestId = null;
        state.currentRequestData = null;
        renderDetails();
        loadRequests();
      } catch {
        showToast('Failed to delete request', 'error');
      }
    });

    // Replay Modal Trigger
    el.btnReplayModal.addEventListener('click', () => {
      if (!state.currentRequestData) return;
      const req = state.currentRequestData;
      el.replayUrlInput.value = req.url;
      el.replayMethodSelect.value = req.method;
      el.replayBodyInput.value = req.raw_body || '';
      openModal(el.modalReplay);
    });

    // Execute Replay
    el.btnConfirmReplay.addEventListener('click', async () => {
      if (!state.currentRequestId) return;
      const targetUrl = el.replayUrlInput.value.trim();
      const method = el.replayMethodSelect.value;
      const body = el.replayBodyInput.value;
      el.btnConfirmReplay.disabled = true;
      el.btnConfirmReplay.textContent = 'Replaying...';
      try {
        const res = await fetch(`/api/requests/${state.currentRequestId}/replay`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ target_url: targetUrl, method, body }),
        });
        const data = await res.json();
        closeModal(el.modalReplay);
        if (data.success) {
          showToast(`Replayed! Status: ${data.status_code} (${data.duration_ms}ms)`, 'success');
        } else {
          showToast(`Replay failed: ${data.error}`, 'error');
        }
      } catch (err) {
        showToast('Replay error: ' + err.message, 'error');
      } finally {
        el.btnConfirmReplay.disabled = false;
        el.btnConfirmReplay.textContent = 'Send Replay';
      }
    });

    // ── Webhook Endpoint Editor ───────────────────────────────────────────────

    // Open create form
    el.btnOpenCreateEndpoint.addEventListener('click', () => {
      openEndpointEditor('create', null);
    });

    // Close/Cancel
    el.btnCloseEndpointEditor.addEventListener('click', () => closeEndpointEditor());
    el.btnCancelEndpointEditor.addEventListener('click', () => closeEndpointEditor());

    // Add required param row
    el.btnAddReqParam.addEventListener('click', () => {
      addReqParamRow();
      syncReqParamsEmpty();
    });

    // Submit (create or update)
    el.formEndpointEditor.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = el.epEditorId.value;
      const isEdit = state.endpointEditorMode === 'edit' && id;

      const headerKV = el.epEditorHeaderKV.value.trim();
      let responseHeaders = { 'Content-Type': 'application/json' };
      if (headerKV.includes(':')) {
        const [key, ...rest] = headerKV.split(':');
        responseHeaders[key.trim()] = rest.join(':').trim();
      }

      const payload = {
        name: el.epEditorName.value.trim(),
        slug: el.epEditorSlug.value.trim() || undefined,
        secret_token: el.epEditorSecret.value.trim() || undefined,
        response_status: parseInt(el.epEditorStatus.value, 10) || 200,
        response_delay_ms: parseInt(el.epEditorDelay.value, 10) || 0,
        response_body: el.epEditorBody.value,
        response_headers: responseHeaders,
        required_params: getRequiredParams(),
      };

      el.btnSubmitEndpointEditor.disabled = true;
      el.btnSubmitEndpointEditor.textContent = 'Saving...';

      try {
        const res = await fetch(isEdit ? `/api/endpoints/${id}` : '/api/endpoints', {
          method: isEdit ? 'PUT' : 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error((await res.json()).message || 'Save failed');
        const saved = await res.json();
        showToast(`Endpoint ${isEdit ? 'updated' : 'created'}: /hook/${saved.slug}`, 'success');
        closeEndpointEditor();
        await loadEndpoints();
        renderEndpointsTable();
      } catch (err) {
        showToast('Error: ' + err.message, 'error');
      } finally {
        el.btnSubmitEndpointEditor.disabled = false;
        el.btnSubmitEndpointEditor.textContent = 'Save Endpoint';
      }
    });

    // Live Response Preview (eval playground)
    el.btnRunEvalPlayground.addEventListener('click', evaluateEndpointResponse);

    // ── Callback Rule Editor ──────────────────────────────────────────────────

    el.btnOpenAddCallback.addEventListener('click', () => {
      openCallbackEditor('create', null);
    });

    el.btnCloseCallbackEditor.addEventListener('click', () => closeCallbackEditor());
    el.btnCancelCallbackEditor.addEventListener('click', () => closeCallbackEditor());

    // Payload mode toggle
    el.cbEditorPayloadMode.addEventListener('change', () => {
      el.cbTemplateGroup.style.display = el.cbEditorPayloadMode.value === 'template' ? 'block' : 'none';
    });

    // HMAC toggle
    el.cbEditorHmacEnabled.addEventListener('change', () => {
      el.cbHmacFields.style.display = el.cbEditorHmacEnabled.checked ? 'block' : 'none';
    });

    // Callback filter by endpoint
    el.callbackFilterEndpoint.addEventListener('change', () => {
      loadCallbackRulesView();
    });

    // Submit callback rule
    el.formCallbackEditor.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = el.cbEditorId.value;
      const isEdit = state.callbackEditorMode === 'edit' && id;

      // Parse custom headers
      const rawHeaders = (el.cbEditorCustomHeaders?.value || '').trim().split('\n');
      const customHeaders = {};
      rawHeaders.forEach((line) => {
        if (line.includes(':')) {
          const [k, ...v] = line.split(':');
          customHeaders[k.trim()] = v.join(':').trim();
        }
      });

      const payload = {
        endpoint_id: parseInt(el.cbEditorEndpointId.value, 10),
        name: el.cbEditorName.value.trim(),
        target_url: el.cbEditorTargetUrl.value.trim(),
        http_method: el.cbEditorMethod.value,
        delay_seconds: parseInt(el.cbEditorDelay.value, 10) || 0,
        max_retries: parseInt(el.cbEditorRetries.value, 10) || 3,
        payload_mode: el.cbEditorPayloadMode.value,
        payload_template: el.cbEditorPayloadMode.value === 'template' ? el.cbEditorTemplate.value : null,
        custom_headers: Object.keys(customHeaders).length > 0 ? customHeaders : null,
        hmac_enabled: el.cbEditorHmacEnabled.checked,
        hmac_secret: el.cbEditorHmacEnabled.checked ? el.cbEditorHmacSecret.value : null,
        hmac_algorithm: el.cbEditorHmacAlgo.value,
        hmac_header_name: el.cbEditorHmacHeader.value,
      };

      const btnEl = document.getElementById('btnSubmitCallbackEditor');
      btnEl.disabled = true;
      btnEl.textContent = 'Saving...';

      try {
        const res = await fetch(isEdit ? `/api/callback-rules/${id}` : '/api/callback-rules', {
          method: isEdit ? 'PUT' : 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error((await res.json()).message || 'Save failed');
        showToast(`Callback rule ${isEdit ? 'updated' : 'created'}!`, 'success');
        closeCallbackEditor();
        loadCallbackRulesView();
      } catch (err) {
        showToast('Error: ' + err.message, 'error');
      } finally {
        btnEl.disabled = false;
        btnEl.textContent = 'Save Callback Rule';
      }
    });

    // Preview Callback Resolution
    if (el.btnPreviewCallback) {
      el.btnPreviewCallback.addEventListener('click', async () => {
        el.btnPreviewCallback.disabled = true;
        el.btnPreviewCallback.textContent = 'Resolving...';

        const rawHeaders = (el.cbEditorCustomHeaders?.value || '').trim().split('\n');
        const customHeaders = {};
        rawHeaders.forEach((line) => {
          if (line.includes(':')) {
            const [k, ...v] = line.split(':');
            customHeaders[k.trim()] = v.join(':').trim();
          }
        });

        try {
          const res = await fetch('/api/callback-rules/preview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              target_url: el.cbEditorTargetUrl.value.trim(),
              payload_template: el.cbEditorTemplate.value,
              payload_mode: el.cbEditorPayloadMode.value,
              custom_headers: customHeaders,
            }),
          });
          const data = await res.json();
          el.cbPreviewUrl.textContent = data.resolved_url || '(empty URL)';
          try {
            const parsed = JSON.parse(data.resolved_payload);
            el.cbPreviewPayload.innerHTML = syntaxHighlightJson(JSON.stringify(parsed, null, 2));
          } catch {
            el.cbPreviewPayload.textContent = data.resolved_payload || '(empty body)';
          }
          el.cbPreviewBox.style.display = 'block';
        } catch (err) {
          showToast('Preview error: ' + err.message, 'error');
        } finally {
          el.btnPreviewCallback.disabled = false;
          el.btnPreviewCallback.textContent = 'Test Dynamic Resolution';
        }
      });
    }

    // Dispatcher Preset Selector
    el.presetSelect.addEventListener('change', (e) => {
      const key = e.target.value;
      if (state.dispatcherPresets[key]) {
        el.dispatchPayload.value = state.dispatcherPresets[key].payload;
      }
    });

    el.btnSendDispatch.addEventListener('click', () => executeDispatch(1));
    el.btnSendBurst.addEventListener('click', () => executeDispatch(5));

    // Variable chip insert (delegated from document)
    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('chip-tag')) {
        const targetId = e.target.dataset.target;
        const varText = e.target.dataset.var;
        const target = document.getElementById(targetId);
        if (target && varText) {
          const start = target.selectionStart || target.value.length;
          const end = target.selectionEnd || target.value.length;
          target.value = target.value.substring(0, start) + varText + target.value.substring(end);
          target.focus();
          target.setSelectionRange(start + varText.length, start + varText.length);
        }
      }
    });

    // Quick Mock
    if (el.btnQuickMockEmpty) {
      el.btnQuickMockEmpty.addEventListener('click', executeQuickMock);
    }

    // Modal close buttons
    document.querySelectorAll('.modal-close, .btn-modal-cancel').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.modal-backdrop').forEach(closeModal);
      });
    });
  }

  // ─── Theme ───────────────────────────────────────────────────────────────────
  function initTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    updateThemeIcons(currentTheme);
    if (el.btnThemeToggle) {
      el.btnThemeToggle.addEventListener('click', () => {
        const active = document.documentElement.getAttribute('data-theme') || 'dark';
        const next = active === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('hookforge_theme', next);
        updateThemeIcons(next);
        showToast(`${next === 'dark' ? '🌙 Dark' : '☀️ Light'} mode activated`, 'info');
      });
    }
  }

  function updateThemeIcons(theme) {
    if (el.themeIconSun && el.themeIconMoon) {
      el.themeIconSun.style.display = theme === 'light' ? 'block' : 'none';
      el.themeIconMoon.style.display = theme === 'dark' ? 'block' : 'none';
    }
  }

  // ─── Endpoint Editor ─────────────────────────────────────────────────────────
  function openEndpointEditor(mode, endpoint) {
    state.endpointEditorMode = mode;
    if (el.reqParamsList) el.reqParamsList.innerHTML = '';

    if (mode === 'create') {
      el.endpointEditorTitle.textContent = 'Create New Webhook Endpoint';
      el.epEditorId.value = '';
      el.epEditorName.value = '';
      el.epEditorSlug.value = '';
      el.epEditorSecret.value = '';
      el.epEditorStatus.value = '200';
      el.epEditorDelay.value = '0';
      el.epEditorHeaderKV.value = '';
      el.epEditorBody.value = `{
  "status": "ok",
  "request_id": "@{{req.id}}",
  "method": "@{{req.method}}",
  "customer_id": "@{{query.customer_id || body.customer_id || 'none'}}",
  "received_at": "@{{timestamp_iso}}"
}`;
      syncReqParamsEmpty();
    } else if (endpoint) {
      el.endpointEditorTitle.textContent = `Edit Endpoint: ${endpoint.name}`;
      el.epEditorId.value = endpoint.id;
      el.epEditorName.value = endpoint.name;
      el.epEditorSlug.value = endpoint.slug;
      el.epEditorSecret.value = endpoint.secret_token || '';
      el.epEditorStatus.value = endpoint.response_status || 200;
      el.epEditorDelay.value = endpoint.response_delay_ms || 0;
      el.epEditorBody.value = endpoint.response_body || '';
      // Show first custom header if any
      const hdrs = endpoint.response_headers || {};
      const custom = Object.entries(hdrs).find(([k]) => k.toLowerCase() !== 'content-type');
      el.epEditorHeaderKV.value = custom ? `${custom[0]}: ${custom[1]}` : '';

      // Populate required params
      const reqList = Array.isArray(endpoint.required_params) ? endpoint.required_params : [];
      reqList.forEach((param) => addReqParamRow(param));
      syncReqParamsEmpty();
    }

    el.endpointEditorPanel.style.display = 'block';
    el.evalPreviewBox.style.display = 'none';
    el.endpointEditorPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function closeEndpointEditor() {
    el.endpointEditorPanel.style.display = 'none';
    el.formEndpointEditor.reset();
    if (el.reqParamsList) el.reqParamsList.innerHTML = '';
    syncReqParamsEmpty();
  }

  // ─── Required Parameters Builder ─────────────────────────────────────────────
  function addReqParamRow(data = {}) {
    if (!el.reqParamsList) return;
    const row = document.createElement('div');
    row.className = 'req-param-row';

    const paramName = data.name || '';
    const paramSource = data.source || 'query';
    const paramType = data.type || 'string';
    const paramMsg = data.error_message || '';

    row.innerHTML = `
      <div>
        <input type="text" class="form-control code-font param-name-input" placeholder="Param name (e.g. order_id)" value="${escapeHtml(paramName)}" required>
      </div>
      <div>
        <select class="form-control param-source-select">
          <option value="query" ${paramSource === 'query' ? 'selected' : ''}>Query Param (?key=val)</option>
          <option value="body" ${paramSource === 'body' ? 'selected' : ''}>Request Body (JSON)</option>
          <option value="header" ${paramSource === 'header' ? 'selected' : ''}>HTTP Header</option>
        </select>
      </div>
      <div>
        <select class="form-control param-type-select">
          <option value="string" ${paramType === 'string' ? 'selected' : ''}>String (Non-empty)</option>
          <option value="number" ${paramType === 'number' ? 'selected' : ''}>Number / Int</option>
          <option value="email" ${paramType === 'email' ? 'selected' : ''}>Email Address</option>
          <option value="url" ${paramType === 'url' ? 'selected' : ''}>URL</option>
          <option value="boolean" ${paramType === 'boolean' ? 'selected' : ''}>Boolean</option>
        </select>
      </div>
      <div>
        <input type="text" class="form-control param-msg-input" placeholder="Custom error message (optional)" value="${escapeHtml(paramMsg)}">
      </div>
      <div style="display:flex; align-items:center; gap:6px;">
        <button type="button" class="btn-insert-chip-param btn btn-secondary" style="font-size:0.75rem; padding:4px 8px; white-space:nowrap;" title="Insert token into Response Body">
          Use in Response
        </button>
        <button type="button" class="btn-remove-param" title="Remove parameter">&times;</button>
      </div>
    `;

    // Remove row button
    row.querySelector('.btn-remove-param').addEventListener('click', () => {
      row.remove();
      syncReqParamsEmpty();
    });

    // Insert token into editor body
    const insertBtn = row.querySelector('.btn-insert-chip-param');
    insertBtn.addEventListener('click', () => {
      const name = row.querySelector('.param-name-input').value.trim() || 'param';
      const source = row.querySelector('.param-source-select').value;
      const token = source === 'query' ? `@{{query.${name}}}` : (source === 'header' ? `@{{headers.${name}}}` : `@{{body.${name}}}`);
      insertTokenIntoEditor(token);
    });

    el.reqParamsList.appendChild(row);
    syncReqParamsEmpty();
  }

  function syncReqParamsEmpty() {
    if (!el.reqParamsList || !el.reqParamsEmpty) return;
    const hasRows = el.reqParamsList.children.length > 0;
    el.reqParamsEmpty.style.display = hasRows ? 'none' : 'block';
  }

  function getRequiredParams() {
    if (!el.reqParamsList) return [];
    const rows = el.reqParamsList.querySelectorAll('.req-param-row');
    const params = [];
    rows.forEach((row) => {
      const name = row.querySelector('.param-name-input')?.value.trim();
      const source = row.querySelector('.param-source-select')?.value || 'query';
      const type = row.querySelector('.param-type-select')?.value || 'string';
      const error_message = row.querySelector('.param-msg-input')?.value.trim() || undefined;
      if (name) {
        params.push({ name, source, type, error_message });
      }
    });
    return params;
  }

  function insertTokenIntoEditor(token) {
    const textarea = el.epEditorBody;
    if (!textarea) return;
    const start = textarea.selectionStart || textarea.value.length;
    const end = textarea.selectionEnd || textarea.value.length;
    const val = textarea.value;
    textarea.value = val.substring(0, start) + token + val.substring(end);
    textarea.focus();
    const newPos = start + token.length;
    textarea.setSelectionRange(newPos, newPos);
    showToast(`Inserted ${token} into response template!`, 'info');
  }

  // Live Response Evaluator (using the endpoint editor's body textarea)
  async function evaluateEndpointResponse() {
    el.btnRunEvalPlayground.disabled = true;
    el.btnRunEvalPlayground.textContent = 'Evaluating...';

    // Build a realistic sample context from current request if available
    let sampleBody = { event: 'test.event', id: 'evt_' + Math.random().toString(36).substring(2, 9), amount: 99.99, customer: { name: 'Jane Doe', tier: 'pro' } };
    if (state.currentRequestData && state.currentRequestData.raw_body) {
      try { sampleBody = JSON.parse(state.currentRequestData.raw_body); } catch { /* keep default */ }
    }

    const sampleHeaders = state.currentRequestData?.headers || { 'content-type': 'application/json', 'x-request-id': 'req_mock_001' };
    const sampleQuery = state.currentRequestData?.query_params || {
      token: 'tok_live_9941a',
      customer_id: 'cus_8820',
      tier: 'enterprise',
      order_id: 'ord_77192',
      amount: '250.00',
    };

    const payload = {
      sample_body: JSON.stringify(sampleBody),
      sample_headers: sampleHeaders,
      sample_query: sampleQuery,
      response_status: el.epEditorStatus.value || '200',
      response_body: el.epEditorBody.value,
      sample_method: 'POST',
    };

    try {
      const res = await fetch('/api/endpoints/test-eval', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();

      el.evalResultStatus.textContent = `HTTP ${data.evaluated_status}`;
      el.evalResultStatus.className = 'badge-status ' + statusClass(data.evaluated_status);

      try {
        const parsed = JSON.parse(data.evaluated_body);
        el.evalResultBody.innerHTML = syntaxHighlightJson(JSON.stringify(parsed, null, 2));
      } catch {
        el.evalResultBody.textContent = data.evaluated_body;
      }

      el.evalPreviewBox.style.display = 'block';
    } catch (err) {
      el.evalResultBody.textContent = 'Evaluation error: ' + err.message;
      el.evalPreviewBox.style.display = 'block';
    } finally {
      el.btnRunEvalPlayground.disabled = false;
      el.btnRunEvalPlayground.textContent = 'Preview Response';
    }
  }

  // ─── Callback Rule Editor ─────────────────────────────────────────────────────
  function openCallbackEditor(mode, rule) {
    state.callbackEditorMode = mode;
    if (el.cbPreviewBox) el.cbPreviewBox.style.display = 'none';

    // Populate endpoint dropdown
    el.cbEditorEndpointId.innerHTML = '';
    state.endpoints.forEach((ep) => {
      const opt = document.createElement('option');
      opt.value = ep.id;
      opt.textContent = `${ep.name} (/hook/${ep.slug})`;
      el.cbEditorEndpointId.appendChild(opt);
    });

    if (mode === 'create') {
      el.callbackEditorTitle.textContent = 'Add Callback Rule';
      el.cbEditorId.value = '';
      el.cbEditorName.value = '';
      el.cbEditorMethod.value = 'POST';
      el.cbEditorDelay.value = '0';
      el.cbEditorRetries.value = '3';
      el.cbEditorTargetUrl.value = '';
      el.cbEditorPayloadMode.value = 'template';
      if (el.cbEditorCustomHeaders) el.cbEditorCustomHeaders.value = '';
      el.cbEditorTemplate.value = `{
  "event": "WEBHOOK_RELAY",
  "source_request_id": "@{{req.id}}",
  "processed_at": "@{{timestamp_iso}}",
  "data": @{{body}}
}`;
      el.cbEditorHmacEnabled.checked = false;
      el.cbEditorHmacSecret.value = '';
      el.cbEditorHmacAlgo.value = 'sha256';
      el.cbEditorHmacHeader.value = 'X-Signature-256';
    } else if (rule) {
      el.callbackEditorTitle.textContent = `Edit Rule: ${rule.name}`;
      el.cbEditorId.value = rule.id;
      el.cbEditorName.value = rule.name;
      el.cbEditorEndpointId.value = rule.endpoint_id;
      el.cbEditorMethod.value = rule.http_method || 'POST';
      el.cbEditorDelay.value = rule.delay_seconds || 0;
      el.cbEditorRetries.value = rule.max_retries || 3;
      el.cbEditorTargetUrl.value = rule.target_url;
      el.cbEditorPayloadMode.value = rule.payload_mode || 'template';
      el.cbEditorTemplate.value = rule.payload_template || '';

      if (el.cbEditorCustomHeaders) {
        if (rule.custom_headers && typeof rule.custom_headers === 'object') {
          el.cbEditorCustomHeaders.value = Object.entries(rule.custom_headers)
            .map(([k, v]) => `${k}: ${v}`)
            .join('\n');
        } else {
          el.cbEditorCustomHeaders.value = '';
        }
      }

      el.cbEditorHmacEnabled.checked = rule.hmac_enabled;
      el.cbEditorHmacSecret.value = rule.hmac_secret || '';
      el.cbEditorHmacAlgo.value = rule.hmac_algorithm || 'sha256';
      el.cbEditorHmacHeader.value = rule.hmac_header_name || 'X-Signature-256';
    }

    // Sync visibility
    el.cbTemplateGroup.style.display = el.cbEditorPayloadMode.value === 'template' ? 'block' : 'none';
    el.cbHmacFields.style.display = el.cbEditorHmacEnabled.checked ? 'block' : 'none';

    el.callbackEditorPanel.style.display = 'block';
    el.callbackEditorPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function closeCallbackEditor() {
    el.callbackEditorPanel.style.display = 'none';
    el.formCallbackEditor.reset();
    if (el.cbPreviewBox) el.cbPreviewBox.style.display = 'none';
    el.cbHmacFields.style.display = 'none';
    el.cbTemplateGroup.style.display = 'block';
  }

  // ─── Tab Switching ────────────────────────────────────────────────────────────
  function switchTab(tabId) {
    state.activeTab = tabId;
    el.navTabs.forEach((t) => t.classList.toggle('active', t.dataset.tab === tabId));
    el.tabPanes.forEach((p) => p.classList.toggle('active', p.id === tabId));



    if (tabId === 'tab-webhooks') {
      renderEndpointsTable();
    } else if (tabId === 'tab-callbacks') {
      populateCallbackFilterDropdown();
      loadCallbackRulesView();
    } else if (tabId === 'tab-dispatcher') {
      loadDispatchHistory();
    }
  }

  function switchSubtab(subtabId) {
    state.activeSubtab = subtabId;
    el.subtabBtns.forEach((b) => b.classList.toggle('active', b.dataset.subtab === subtabId));
    el.subpaneTabs.forEach((t) => t.classList.toggle('active', t.id === subtabId));
  }

  function closeAllDropdowns() {
    document.querySelectorAll('.custom-dropdown.open').forEach((dd) => {
      dd.classList.remove('open');
      const trigger = dd.querySelector('.custom-dropdown-trigger');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
  }

  // ─── Load Endpoints ───────────────────────────────────────────────────────────
  async function loadEndpoints() {
    try {
      const res = await fetch('/api/endpoints');
      state.endpoints = await res.json();

      populateCustomEndpointDropdown();
      updateFeedEndpointBanner();
      await loadRequests(true);
    } catch (err) {
      console.error('Failed to load endpoints:', err);
    }
  }

  function populateCustomEndpointDropdown() {
    if (!el.feedDropdownMenu) return;
    el.feedDropdownMenu.innerHTML = '';

    const totalReqs = state.endpoints.reduce((sum, ep) => sum + (ep.webhook_requests_count || 0), 0);

    // "All Endpoints" Option
    const allItem = document.createElement('div');
    allItem.className = 'custom-dropdown-item' + (!state.currentEndpointId ? ' active' : '');
    allItem.dataset.value = 'all';
    allItem.innerHTML = `
      <div class="custom-dropdown-item-info">
        <span class="custom-dropdown-item-title">All Endpoints</span>
        <span class="custom-dropdown-item-desc">All incoming traffic</span>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <span class="custom-dropdown-badge">${totalReqs}</span>
        <span class="custom-dropdown-item-check">✓</span>
      </div>
    `;
    allItem.addEventListener('click', () => {
      selectEndpointFilter('all');
    });
    el.feedDropdownMenu.appendChild(allItem);

    // Each Endpoint Option
    state.endpoints.forEach((ep) => {
      const epReqCount = ep.webhook_requests_count || 0;
      const item = document.createElement('div');
      item.className = 'custom-dropdown-item' + (state.currentEndpointId === ep.id ? ' active' : '');
      item.dataset.value = String(ep.id);
      item.innerHTML = `
        <div class="custom-dropdown-item-info">
          <span class="custom-dropdown-item-title">${escapeHtml(ep.name)}</span>
          <span class="custom-dropdown-item-desc">/hook/${escapeHtml(ep.slug)}</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          <span class="custom-dropdown-badge">${epReqCount}</span>
          <span class="custom-dropdown-item-check">✓</span>
        </div>
      `;
      item.addEventListener('click', () => {
        selectEndpointFilter(ep.id);
      });
      el.feedDropdownMenu.appendChild(item);
    });
  }

  function selectEndpointFilter(id) {
    state.currentEndpointId = id === 'all' || !id ? null : parseInt(id, 10);
    closeAllDropdowns();
    updateFeedEndpointBanner();
    loadRequests(true);
  }

  function updateFeedEndpointBanner() {
    // Sync custom dropdown trigger text & badge
    if (el.feedDropdownTitle) {
      if (state.currentEndpointId) {
        const ep = state.endpoints.find((e) => e.id === state.currentEndpointId);
        if (ep) {
          el.feedDropdownTitle.textContent = ep.name;
          if (el.feedDropdownSubtitle) el.feedDropdownSubtitle.textContent = `/hook/${ep.slug}`;
          if (el.feedDropdownBadge) el.feedDropdownBadge.textContent = String(ep.webhook_requests_count || 0);
          if (el.feedEndpointCountBadge) el.feedEndpointCountBadge.textContent = ep.name;
        }
      } else {
        const totalReqs = state.endpoints.reduce((sum, ep) => sum + (ep.webhook_requests_count || 0), 0);
        el.feedDropdownTitle.textContent = 'All Endpoints';
        if (el.feedDropdownSubtitle) el.feedDropdownSubtitle.textContent = 'All incoming traffic';
        if (el.feedDropdownBadge) el.feedDropdownBadge.textContent = String(totalReqs);
        if (el.feedEndpointCountBadge) el.feedEndpointCountBadge.textContent = 'All';
      }
    }

    // Sync menu items active class
    if (el.feedDropdownMenu) {
      el.feedDropdownMenu.querySelectorAll('.custom-dropdown-item').forEach((item) => {
        const val = item.dataset.value;
        const isActive = (!state.currentEndpointId && val === 'all') || (state.currentEndpointId && val === String(state.currentEndpointId));
        item.classList.toggle('active', !!isActive);
      });
    }

    // Sync hidden select if present
    if (el.feedEndpointSelect) {
      el.feedEndpointSelect.value = state.currentEndpointId ? String(state.currentEndpointId) : 'all';
    }

    // Direct Webhook URL Bar below dropdown
    if (!el.feedEndpointUrlBar || !el.feedEndpointUrlText) return;

    if (state.currentEndpointId) {
      const ep = state.endpoints.find((e) => e.id === state.currentEndpointId);
      if (ep) {
        const url = `${window.location.origin}/hook/${ep.slug}`;
        el.feedEndpointUrlText.textContent = url;
        el.feedEndpointUrlBar.style.display = 'flex';
        if (el.dispatchUrl && (!el.dispatchUrl.value || el.dispatchUrl.value.includes('/hook/'))) {
          el.dispatchUrl.value = url;
        }
        return;
      }
    }

    el.feedEndpointUrlBar.style.display = 'none';
  }

  // ─── Filter Engine ────────────────────────────────────────────────────────────
  function applyFilters(autoSelect = true) {
    let filtered = [...state.allRequests];

    if (state.currentEndpointId) {
      filtered = filtered.filter((r) => r.endpoint_id === state.currentEndpointId);
    }

    if (state.filterMethod) {
      const target = state.filterMethod.toUpperCase();
      filtered = filtered.filter((r) => {
        const m = (r.method || '').toUpperCase();
        if (target === 'DELETE') return m === 'DELETE';
        return m === target;
      });
    }

    if (state.filterSearch) {
      const q = state.filterSearch.toLowerCase();
      filtered = filtered.filter((r) => {
        return (
          (r.path || '').toLowerCase().includes(q) ||
          (r.id || '').toLowerCase().includes(q) ||
          (r.raw_body || '').toLowerCase().includes(q) ||
          (r.method || '').toLowerCase().includes(q) ||
          (r.url || '').toLowerCase().includes(q)
        );
      });
    }

    state.requests = filtered;
    el.reqCountBadge.textContent = state.requests.length;
    renderRequestsFeed();

    if (autoSelect) {
      if (state.requests.length > 0) {
        const stillSelected = state.requests.find((r) => r.id === state.currentRequestId);
        if (!stillSelected) selectRequest(state.requests[0].id);
      } else {
        state.currentRequestId = null;
        state.currentRequestData = null;
        renderDetails();
      }
    }
  }

  // ─── Load Requests ────────────────────────────────────────────────────────────
  async function loadRequests(forceSelectFirst = false) {
    let url = '/api/requests?per_page=100';
    if (state.currentEndpointId) url += `&endpoint_id=${state.currentEndpointId}`;

    try {
      const res = await fetch(url);
      if (!res.ok) return;
      const data = await res.json();
      state.allRequests = data.data || [];
      applyFilters(forceSelectFirst);

      if (forceSelectFirst && state.requests.length > 0 && !state.currentRequestId) {
        selectRequest(state.requests[0].id);
      }
    } catch (err) {
      console.error('Failed to load requests:', err);
    }
  }

  // ─── Render Feed ──────────────────────────────────────────────────────────────
  function renderRequestsFeed() {
    el.requestsList.innerHTML = '';

    if (state.requests.length === 0) {
      el.emptyFeed.style.display = 'flex';
      return;
    }
    el.emptyFeed.style.display = 'none';

    state.requests.forEach((req) => {
      const li = document.createElement('li');
      li.className = 'request-item' + (req.id === state.currentRequestId ? ' active' : '');
      li.dataset.id = req.id;

      const method = (req.method || 'POST').toLowerCase();
      const status = req.response_status || 200;
      const sizeStr = req.content_length ? formatBytes(req.content_length) : '0 B';

      const epLabel = req.endpoint ? req.endpoint.name : (req.path.split('/')[2] || 'Default');

      li.innerHTML = `
        <div class="request-item-header">
          <div class="request-item-badges">
            <span class="badge-method ${method}">${req.method}</span>
            <span class="badge-status ${statusClass(status)}">${status}</span>
            <span class="req-ep-tag" title="Endpoint: ${escapeHtml(epLabel)}">${escapeHtml(epLabel)}</span>
          </div>
          <span class="request-time">${formatRelativeTime(req.created_at)}</span>
        </div>
        <div class="request-path" title="${escapeHtml(req.path)}">${escapeHtml(req.path)}</div>
        <div class="request-item-footer">
          <span>${sizeStr} · ${req.duration_ms || 0}ms</span>
          ${req.callback_logs_count > 0 ? `<span class="callback-indicator"><span class="indicator-dot"></span>${req.callback_logs_count} callback${req.callback_logs_count > 1 ? 's' : ''}</span>` : ''}
        </div>
      `;

      li.addEventListener('click', () => selectRequest(req.id));
      el.requestsList.appendChild(li);
    });
  }

  // ─── Select Request ───────────────────────────────────────────────────────────
  async function selectRequest(reqId) {
    state.currentRequestId = reqId;
    document.querySelectorAll('.request-item').forEach((item) => {
      item.classList.toggle('active', item.dataset.id === reqId);
    });

    try {
      const res = await fetch(`/api/requests/${reqId}`);
      if (!res.ok) return;
      state.currentRequestData = await res.json();
      renderDetails();
    } catch (err) {
      console.error('Failed to get request detail:', err);
    }
  }

  // ─── Render Details Pane ─────────────────────────────────────────────────────
  function renderDetails() {
    const req = state.currentRequestData;
    if (!req) {
      el.detailsPaneEmpty.style.display = 'flex';
      el.detailsPaneContent.style.display = 'none';
      return;
    }

    el.detailsPaneEmpty.style.display = 'none';
    el.detailsPaneContent.style.display = 'flex';

    const method = (req.method || 'POST').toLowerCase();
    el.detailsMethodBadge.className = `badge-method ${method}`;
    el.detailsMethodBadge.textContent = req.method;

    const status = req.response_status || 200;
    el.detailsStatusBadge.className = `badge-status ${statusClass(status)}`;
    el.detailsStatusBadge.textContent = status;
    el.detailsPath.textContent = req.path;

    // Overview Table
    el.overviewTableBody.innerHTML = `
      <tr><td class="kv-key">Request ID</td><td class="kv-val"><span style="user-select:all; cursor:pointer;" title="Click to copy" onclick="navigator.clipboard.writeText('${req.id}'); HookForge.showToast('ID Copied','info');">${req.id} 📋</span></td></tr>
      <tr><td class="kv-key">Endpoint</td><td class="kv-val">${req.endpoint ? `${req.endpoint.name} (<code>/hook/${req.endpoint.slug}</code>)` : '—'}</td></tr>
      <tr><td class="kv-key">Full URL</td><td class="kv-val"><code>${escapeHtml(req.url)}</code></td></tr>
      <tr><td class="kv-key">Client IP</td><td class="kv-val"><code>${req.ip_address || '—'}</code></td></tr>
      <tr><td class="kv-key">User Agent</td><td class="kv-val">${escapeHtml(req.user_agent || '—')}</td></tr>
      <tr><td class="kv-key">Content-Type</td><td class="kv-val"><code>${req.content_type || '—'}</code></td></tr>
      <tr><td class="kv-key">Content-Length</td><td class="kv-val">${formatBytes(req.content_length || 0)} (${req.content_length || 0} bytes)</td></tr>
      <tr><td class="kv-key">Server Latency</td><td class="kv-val"><strong>${req.duration_ms} ms</strong></td></tr>
      <tr><td class="kv-key">Timestamp (UTC)</td><td class="kv-val">${req.created_at}</td></tr>
    `;

    // Headers Table
    el.headersTableBody.innerHTML = '';
    if (req.headers && Object.keys(req.headers).length > 0) {
      for (const [k, v] of Object.entries(req.headers)) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="kv-key">${escapeHtml(k)}</td><td class="kv-val">${escapeHtml(v)}</td>`;
        el.headersTableBody.appendChild(tr);
      }
    } else {
      el.headersTableBody.innerHTML = '<tr><td colspan="2" style="color:var(--text-muted); text-align:center;">No headers captured</td></tr>';
    }

    // Query Params
    el.queryTableBody.innerHTML = '';
    if (req.query_params && Object.keys(req.query_params).length > 0) {
      el.queryEmptyMsg.style.display = 'none';
      for (const [k, v] of Object.entries(req.query_params)) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="kv-key">${escapeHtml(k)}</td><td class="kv-val">${escapeHtml(typeof v === 'object' ? JSON.stringify(v) : v)}</td>`;
        el.queryTableBody.appendChild(tr);
      }
    } else {
      el.queryEmptyMsg.style.display = 'block';
    }

    // Payload
    if (req.raw_body) {
      try {
        el.payloadPre.innerHTML = syntaxHighlightJson(JSON.stringify(JSON.parse(req.raw_body), null, 2));
      } catch {
        el.payloadPre.textContent = req.raw_body;
      }
    } else {
      el.payloadPre.innerHTML = '<span style="color:var(--text-muted);">// No request body payload sent</span>';
    }

    // Response Sent
    el.responseStatusBadge.className = `badge-status ${statusClass(status)}`;
    el.responseStatusBadge.textContent = status;
    el.responseDurationText.textContent = `${req.duration_ms} ms`;

    el.responseHeadersBody.innerHTML = '';
    if (req.response_headers && Object.keys(req.response_headers).length > 0) {
      for (const [k, v] of Object.entries(req.response_headers)) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="kv-key">${escapeHtml(k)}</td><td class="kv-val">${escapeHtml(Array.isArray(v) ? v.join(', ') : v)}</td>`;
        el.responseHeadersBody.appendChild(tr);
      }
    }

    if (req.response_body) {
      try {
        el.responseBodyPre.innerHTML = syntaxHighlightJson(JSON.stringify(JSON.parse(req.response_body), null, 2));
      } catch {
        el.responseBodyPre.textContent = req.response_body;
      }
    } else {
      el.responseBodyPre.innerHTML = '<span style="color:var(--text-muted);">(Empty response body)</span>';
    }

    // Triggered Callbacks
    renderTriggeredCallbacks(req.callback_logs || []);
  }

  function renderTriggeredCallbacks(logs) {
    el.callbacksContainer.innerHTML = '';
    if (!logs || logs.length === 0) {
      el.callbacksContainer.innerHTML = `
        <div style="padding:40px; text-align:center; color:var(--text-muted);">
          <p>No outbound callbacks were triggered for this webhook request.</p>
          <p style="font-size:0.8rem; margin-top:6px;">Configure callback rules under the <strong>Callback Rules</strong> tab.</p>
        </div>
      `;
      return;
    }

    logs.forEach((log) => {
      const card = document.createElement('div');
      card.className = `callback-audit-card ${log.status}`;
      const ruleName = log.callback_rule ? log.callback_rule.name : 'Automated Callback';

      card.innerHTML = `
        <div class="callback-card-header">
          <div><strong>${escapeHtml(ruleName)}</strong> · Attempt #${log.attempt}</div>
          <div><span class="badge-status status-${log.status === 'success' ? '2xx' : '5xx'}">${(log.status || 'unknown').toUpperCase()}</span></div>
        </div>
        <div class="callback-target">
          <code>${log.request_method || 'POST'} ${escapeHtml(log.target_url)}</code>
        </div>
        <div style="display:flex; gap:16px; font-size:0.78rem; color:var(--text-secondary);">
          <span>Response: <strong>HTTP ${log.response_status || 'Error'}</strong></span>
          <span>Latency: <strong>${log.duration_ms || 0} ms</strong></span>
          <span>Time: <strong>${log.created_at}</strong></span>
        </div>
        ${log.error_message ? `<div style="color:var(--color-danger); font-size:0.8rem; background:rgba(239,68,68,0.1); padding:8px; border-radius:6px;">⚠️ ${escapeHtml(log.error_message)}</div>` : ''}
        <details style="margin-top:4px;">
          <summary style="font-size:0.78rem; color:var(--accent-primary); cursor:pointer;">Inspect Outbound Payload &amp; Signature</summary>
          <div class="callback-details-drawer" style="margin-top:8px;">
            <strong>Outbound Headers Sent:</strong>
            <pre style="margin:0; background:#050811; padding:8px; border-radius:4px; font-family:var(--font-mono); font-size:0.75rem;">${escapeHtml(JSON.stringify(log.request_headers, null, 2))}</pre>
            <strong>Outbound Body Sent:</strong>
            <pre style="margin:0; background:#050811; padding:8px; border-radius:4px; font-family:var(--font-mono); font-size:0.75rem;">${escapeHtml(log.request_body || '(empty)')}</pre>
            <strong>Target Server Response:</strong>
            <pre style="margin:0; background:#050811; padding:8px; border-radius:4px; font-family:var(--font-mono); font-size:0.75rem;">${escapeHtml(log.response_body || '(empty)')}</pre>
          </div>
        </details>
      `;
      el.callbacksContainer.appendChild(card);
    });
  }

  // ─── Endpoints Table Render ───────────────────────────────────────────────────
  function renderEndpointsTable() {
    el.endpointsTableBody.innerHTML = '';

    if (state.endpoints.length === 0) {
      el.endpointsTableBody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
            No webhook endpoints yet. Click <strong>+ New Endpoint</strong> to create one.
          </td>
        </tr>
      `;
      return;
    }

    state.endpoints.forEach((ep) => {
      const url = `${window.location.origin}/hook/${ep.slug}`;
      const cbCount = ep.callback_rules ? ep.callback_rules.length : 0;
      const reqCount = ep.webhook_requests_count || 0;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="font-weight:600; color:var(--text-highlight);">${escapeHtml(ep.name)}</td>
        <td>
          <div style="display:flex; align-items:center; gap:8px;">
            <code style="color:var(--color-info);">/hook/${escapeHtml(ep.slug)}</code>
            <button class="btn-copy-inline" onclick="navigator.clipboard.writeText('${url}'); HookForge.showToast('URL copied!','success');" title="Copy URL">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
            </button>
          </div>
        </td>
        <td><span class="badge-status ${statusClass(ep.response_status || 200)}">${ep.response_status || 200}</span></td>
        <td>${ep.response_delay_ms || 0} ms</td>
        <td><span class="count-badge">${reqCount}</span></td>
        <td><span class="count-badge">${cbCount}</span></td>
        <td style="text-align:right;">
          <div style="display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap;">
            <button class="btn btn-secondary" style="padding:3px 10px; font-size:0.75rem;" onclick="HookForge.filterByEndpoint(${ep.id})" title="View requests for this endpoint only">View Requests (${reqCount})</button>
            <button class="btn btn-secondary" style="padding:3px 10px; font-size:0.75rem;" onclick="HookForge.editEndpoint(${ep.id})">Edit</button>
            <button class="btn btn-danger" style="padding:3px 10px; font-size:0.75rem;" onclick="HookForge.deleteEndpoint(${ep.id})">Delete</button>
          </div>
        </td>
      `;
      el.endpointsTableBody.appendChild(tr);
    });
  }

  // ─── Callbacks View ───────────────────────────────────────────────────────────
  function populateCallbackFilterDropdown() {
    el.callbackFilterEndpoint.innerHTML = '<option value="">All Endpoints</option>';
    state.endpoints.forEach((ep) => {
      const opt = document.createElement('option');
      opt.value = ep.id;
      opt.textContent = `${ep.name} (/hook/${ep.slug})`;
      el.callbackFilterEndpoint.appendChild(opt);
    });
  }

  async function loadCallbackRulesView() {
    try {
      let url = '/api/callback-rules';
      const filterEpId = el.callbackFilterEndpoint.value;
      if (filterEpId) url += `?endpoint_id=${filterEpId}`;

      const res = await fetch(url);
      const rules = await res.json();

      el.callbackRuleCount.textContent = `${rules.length} rule${rules.length !== 1 ? 's' : ''}`;
      el.callbacksListContainer.innerHTML = '';

      if (rules.length === 0) {
        el.callbacksListContainer.innerHTML = `
          <div style="padding:60px; text-align:center; color:var(--text-muted); background:var(--bg-surface); border-radius:10px; border:1px solid var(--border-subtle);">
            <h3 style="margin-bottom:8px;">No Callback Rules Configured</h3>
            <p style="font-size:0.86rem;">Add a callback rule to automatically forward incoming webhooks to your microservices. Supports dynamic URLs, payload transformation from request body/headers, HMAC signing, and retries.</p>
          </div>
        `;
        return;
      }

      rules.forEach((rule) => {
        const card = document.createElement('div');
        card.className = 'panel-card callback-rule-card';

        // Check if dynamic URL
        const isDynamicUrl = (rule.target_url || '').includes('{{');
        const latestLog = rule.callback_logs && rule.callback_logs.length > 0 ? rule.callback_logs[0] : null;

        let lastRunHtml = '<span class="cb-stat-pill neutral">No dispatches yet</span>';
        if (latestLog) {
          const isOk = latestLog.status === 'success' || (latestLog.response_status >= 200 && latestLog.response_status < 400);
          const scClass = isOk ? 'success' : 'error';
          const timeAgo = formatRelativeTime(latestLog.created_at);
          lastRunHtml = `<span class="cb-stat-pill ${scClass}">Last: ${latestLog.response_status || latestLog.status} (${latestLog.duration_ms}ms) · ${timeAgo}</span>`;
        }

        const totalDispatches = rule.callback_logs_count !== undefined ? rule.callback_logs_count : (rule.callback_logs ? rule.callback_logs.length : 0);

        card.innerHTML = `
          <div class="callback-rule-header">
            <div class="callback-rule-meta">
              <div class="callback-rule-name">${escapeHtml(rule.name)}</div>
              <div class="callback-rule-sub">
                <span class="badge-endpoint">Attached: <strong>${rule.endpoint ? escapeHtml(rule.endpoint.name) : 'Any'}</strong> (<code>/hook/${rule.endpoint ? escapeHtml(rule.endpoint.slug) : ''}</code>)</span>
                <button type="button" class="btn-toggle-rule-active ${rule.is_active ? 'badge-active' : 'badge-inactive'}" 
                  onclick="HookForge.toggleCallbackRuleActive(${rule.id}, ${!rule.is_active})" 
                  title="Click to ${rule.is_active ? 'pause' : 'activate'} this callback rule">
                  <span class="indicator-dot" style="background:${rule.is_active ? 'var(--color-success)' : 'var(--text-muted)'}; margin-right:4px;"></span> ${rule.is_active ? 'Active' : 'Paused'}
                </button>
              </div>
            </div>
            <div class="callback-rule-actions">
              <button class="btn btn-secondary" style="font-size:0.8rem;" onclick="HookForge.editCallbackRule(${rule.id})">Edit</button>
              <button class="btn btn-secondary" style="font-size:0.8rem;" onclick="HookForge.testCallbackRule(${rule.id})">Test Fire</button>
              <button class="btn btn-danger" style="font-size:0.8rem;" onclick="HookForge.deleteCallbackRule(${rule.id})">Delete</button>
            </div>
          </div>

          <!-- Target URL Row -->
          <div class="callback-target-bar">
            <span class="badge-method ${(rule.http_method || 'POST').toLowerCase()}">${rule.http_method || 'POST'}</span>
            <span class="callback-target-url">${escapeHtml(rule.target_url)}</span>
            ${isDynamicUrl ? '<span class="badge-dynamic-url">Dynamic URL</span>' : ''}
            <button type="button" class="btn-copy-target" onclick="HookForge.copyText('${escapeHtml(rule.target_url)}')">Copy</button>
          </div>

          <!-- Metadata Strip -->
          <div class="callback-meta-strip">
            <span class="meta-pill">Delay: <strong>${rule.delay_seconds || 0}s</strong></span>
            <span class="meta-pill">Retries: <strong>${rule.max_retries || 3}x</strong></span>
            <span class="meta-pill">Payload: <strong>${rule.payload_mode === 'passthrough' ? 'Passthrough' : (rule.payload_mode === 'empty' ? 'Empty' : 'Template')}</strong></span>
            ${rule.hmac_enabled ? `<span class="badge-chip-hmac">HMAC: ${escapeHtml(rule.hmac_algorithm?.toUpperCase())} (${escapeHtml(rule.hmac_header_name)})</span>` : ''}
            <span class="meta-pill"><strong>${totalDispatches}</strong> dispatch${totalDispatches !== 1 ? 'es' : ''}</span>
            ${lastRunHtml}
          </div>

          ${rule.payload_template && rule.payload_mode === 'template' ? `
          <div class="cb-template-toggle-box">
            <details>
              <summary style="font-size:0.8rem; color:var(--accent-primary); cursor:pointer; font-weight:600; user-select:none;">
                View Payload Template
              </summary>
              <pre class="code-pre" style="margin-top:8px; max-height:160px; overflow:auto; font-size:0.78rem;">${escapeHtml(rule.payload_template)}</pre>
            </details>
          </div>
          ` : ''}
        `;

        el.callbacksListContainer.appendChild(card);
      });
    } catch (err) {
      console.error('Failed to load callback rules:', err);
    }
  }

  // ─── Quick Mock ───────────────────────────────────────────────────────────────
  async function executeQuickMock() {
    const ep = state.endpoints.find((e) => e.id === state.currentEndpointId) || state.endpoints[0];
    const slug = ep ? ep.slug : 'default';

    const payload = {
      event: 'payment.completed',
      id: 'pay_' + Math.random().toString(36).substring(2, 9),
      amount: parseFloat((Math.random() * 250 + 25).toFixed(2)),
      currency: 'USD',
      customer: { id: 'cus_' + Math.random().toString(36).substring(2, 8), name: 'Jordan Belfort', email: 'jordan@example.com' },
      callback_url: window.location.origin + '/hook/default',
      timestamp: new Date().toISOString(),
    };

    showToast('Firing test webhook to /hook/' + slug + '...', 'info');

    try {
      const res = await fetch(`/hook/${slug}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Simulated-Source': 'HookForge-QuickMock', 'X-Test-Trace': 'trace-' + Date.now() },
        body: JSON.stringify(payload),
      });
      showToast(`Webhook ingested! Status: ${res.status}`, 'success');
      await loadRequests();
    } catch (err) {
      showToast('Quick mock failed: ' + err.message, 'error');
    }
  }

  // ─── Dispatcher ───────────────────────────────────────────────────────────────
  async function loadDispatcherPresets() {
    try {
      const res = await fetch('/api/dispatch/presets');
      state.dispatcherPresets = await res.json();
      el.presetSelect.innerHTML = '<option value="">-- Choose a Preset Payload --</option>';
      for (const [key, item] of Object.entries(state.dispatcherPresets)) {
        const opt = document.createElement('option');
        opt.value = key;
        opt.textContent = item.name;
        el.presetSelect.appendChild(opt);
      }
    } catch (err) {
      console.error('Failed to load presets:', err);
    }
  }

  async function executeDispatch(count = 1) {
    const targetUrl = el.dispatchUrl.value.trim();
    if (!targetUrl) { showToast('Please provide a target URL', 'error'); return; }

    const payload = {
      target_url: targetUrl,
      method: el.dispatchMethod.value,
      payload: el.dispatchPayload.value,
      hmac_enabled: el.dispatchHmacEnabled.checked,
      hmac_secret: el.dispatchHmacSecret.value,
      hmac_header: el.dispatchHmacHeader.value,
    };

    el.btnSendDispatch.disabled = true;
    el.btnSendDispatch.textContent = count > 1 ? `Sending ${count} requests...` : 'Sending...';

    for (let i = 0; i < count; i++) {
      try {
        const res = await fetch('/api/dispatch', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const log = await res.json();
        el.dispatchResultCard.style.display = 'block';
        el.dispatchResultStatus.textContent = `HTTP ${log.response_status || 'Error'}`;
        el.dispatchResultStatus.className = `badge-status ${log.response_status >= 200 && log.response_status < 300 ? 'status-2xx' : 'status-5xx'}`;
        el.dispatchResultDuration.textContent = `${log.duration_ms} ms`;
        el.dispatchResultBody.textContent = log.response_body || log.error_message || '(No response body)';
        showToast(`Request ${i + 1}/${count} sent: ${log.response_status}`, log.response_status >= 200 && log.response_status < 400 ? 'success' : 'error');
      } catch (err) {
        showToast('Dispatch failed: ' + err.message, 'error');
      }
    }

    el.btnSendDispatch.disabled = false;
    el.btnSendDispatch.textContent = 'Send Webhook';
    loadDispatchHistory();
  }

  async function loadDispatchHistory() {
    try {
      const res = await fetch('/api/dispatch/history');
      const list = await res.json();
      el.dispatchHistoryList.innerHTML = '';
      list.forEach((item) => {
        const li = document.createElement('li');
        li.className = 'request-item';
        li.style.cursor = 'default';
        const sc = item.response_status >= 200 && item.response_status < 300 ? 'status-2xx' : 'status-5xx';
        li.innerHTML = `
          <div class="request-item-header">
            <div class="request-item-badges">
              <span class="badge-method ${(item.method || 'POST').toLowerCase()}">${item.method}</span>
              <span class="badge-status ${sc}">${item.response_status || 'ERR'}</span>
            </div>
            <span class="request-time">${formatRelativeTime(item.created_at)}</span>
          </div>
          <div class="request-path">${escapeHtml(item.target_url)}</div>
          <div class="request-item-footer">
            <span>Latency: ${item.duration_ms}ms</span>
            ${item.error_message ? `<span style="color:var(--color-danger);">${escapeHtml(item.error_message)}</span>` : ''}
          </div>
        `;
        el.dispatchHistoryList.appendChild(li);
      });
    } catch (err) {
      console.error('Failed to load dispatch history:', err);
    }
  }

  // ─── SSE / Polling ────────────────────────────────────────────────────────────
  function initSSE() {
    try {
      const source = new EventSource('/api/stream');
      source.onopen = () => {
        state.sseConnected = true;
        el.liveBadgeText.textContent = 'LIVE STREAM ACTIVE';
      };
      source.addEventListener('request.created', (event) => {
        try {
          const data = JSON.parse(event.data);
          showToast(`New Webhook: ${data.method} → ${data.status}`, 'info');
          loadRequests(false);
        } catch { /* */ }
      });
      source.onerror = () => {
        state.sseConnected = false;
        el.liveBadgeText.textContent = 'LIVE STREAM ACTIVE';
      };
    } catch (e) {
      console.warn('SSE not supported:', e);
    }

    // Fallback polling
    setInterval(() => {
      if (state.activeTab === 'tab-inspector' && document.visibilityState !== 'hidden') {
        loadRequests(false);
      }
    }, 3500);
  }

  // ─── Modals ───────────────────────────────────────────────────────────────────
  function openModal(modal) { modal.classList.add('open'); }
  function closeModal(modal) { modal.classList.remove('open'); }

  // ─── Toast ────────────────────────────────────────────────────────────────────
  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    toast.innerHTML = `<span style="font-weight:700;">${icon}</span> <span>${escapeHtml(message)}</span>`;
    el.toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 200ms ease';
      setTimeout(() => toast.remove(), 250);
    }, 3200);
  }

  // ─── Utilities ────────────────────────────────────────────────────────────────
  function statusClass(status) {
    const s = parseInt(status, 10);
    if (s >= 200 && s < 300) return 'status-2xx';
    if (s >= 300 && s < 400) return 'status-3xx';
    if (s >= 400 && s < 500) return 'status-4xx';
    return 'status-5xx';
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatBytes(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }

  function formatRelativeTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const seconds = Math.floor((new Date() - date) / 1000);
    if (seconds < 5) return 'just now';
    if (seconds < 60) return `${seconds}s ago`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    return date.toLocaleDateString();
  }

  function syntaxHighlightJson(json) {
    json = escapeHtml(json);
    return json.replace(
      /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,
      (match) => {
        let cls = 'color: #f59e0b;';
        if (/^"/.test(match)) {
          cls = /:$/.test(match) ? 'color: var(--color-info); font-weight:600;' : 'color: #34d399;';
        } else if (/true|false/.test(match)) {
          cls = 'color: #c084fc; font-weight:600;';
        } else if (/null/.test(match)) {
          cls = 'color: #94a3b8; font-style:italic;';
        }
        return `<span style="${cls}">${match}</span>`;
      }
    );
  }

  // ─── Global Interface ─────────────────────────────────────────────────────────
  window.HookForge = {
    showToast,

    filterByEndpoint: (id) => {
      switchTab('tab-inspector');
      state.currentEndpointId = id ? parseInt(id, 10) : null;
      if (el.feedEndpointSelect) {
        el.feedEndpointSelect.value = id ? String(id) : 'all';
      }
      updateFeedEndpointBanner();
      loadRequests(true);
    },

    editEndpoint: (id) => {
      const ep = state.endpoints.find((e) => e.id === id);
      if (!ep) return;
      switchTab('tab-webhooks');
      setTimeout(() => openEndpointEditor('edit', ep), 50);
    },

    deleteEndpoint: async (id) => {
      if (!confirm('Delete this endpoint and all its captured webhooks?')) return;
      await fetch(`/api/endpoints/${id}`, { method: 'DELETE' });
      showToast('Endpoint deleted', 'info');
      state.currentEndpointId = null;
      await loadEndpoints();
      renderEndpointsTable();
    },

    editCallbackRule: async (id) => {
      try {
        const res = await fetch('/api/callback-rules');
        const rules = await res.json();
        const rule = rules.find((r) => r.id === id);
        if (!rule) return;
        openCallbackEditor('edit', rule);
      } catch (err) {
        showToast('Failed to load rule: ' + err.message, 'error');
      }
    },

    deleteCallbackRule: async (id) => {
      if (!confirm('Delete this callback rule?')) return;
      await fetch(`/api/callback-rules/${id}`, { method: 'DELETE' });
      showToast('Callback rule deleted', 'info');
      loadCallbackRulesView();
    },

    testCallbackRule: async (id) => {
      showToast('Test firing callback rule...', 'info');
      try {
        const res = await fetch(`/api/callback-rules/${id}/test`, { method: 'POST' });
        const data = await res.json();
        showToast(`Callback fired: ${data.log ? data.log.status : 'done'} (${data.log ? data.log.duration_ms : 0}ms)`, 'success');
        loadCallbackRulesView();
      } catch (err) {
        showToast('Callback test failed: ' + err.message, 'error');
      }
    },

    toggleCallbackRuleActive: async (id, newActive) => {
      try {
        const res = await fetch(`/api/callback-rules/${id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ is_active: newActive }),
        });
        if (!res.ok) throw new Error('Failed to update status');
        showToast(`Rule ${newActive ? 'activated' : 'paused'}`, 'info');
        loadCallbackRulesView();
      } catch (err) {
        showToast('Error: ' + err.message, 'error');
      }
    },

    copyText: (text) => {
      if (!text) return;
      navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'info');
      }).catch(() => {
        showToast('Could not copy', 'error');
      });
    },
  };

  // ─── Boot ─────────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', init);
})();
