/**
 * HookForge Enterprise Client Engine
 * Real-time SSE Stream, Interactive Inspector, Dynamic Callbacks, Outbound Dispatcher
 */

(function () {
  'use strict';

  // State
  const state = {
    endpoints: [],
    currentEndpointId: null,
    requests: [],
    currentRequestId: null,
    currentRequestData: null,
    filterMethod: '',
    filterSearch: '',
    dispatcherPresets: {},
    activeTab: 'tab-inspector',
    activeSubtab: 'subtab-payload',
    sseConnected: false,
  };

  // DOM Elements
  const el = {
    // Header
    endpointSelect: document.getElementById('endpointSelect'),
    endpointUrlPill: document.getElementById('endpointUrlPill'),
    endpointUrlText: document.getElementById('endpointUrlText'),
    liveBadgeText: document.getElementById('liveBadgeText'),
    navTabs: document.querySelectorAll('.nav-tab'),
    tabPanes: document.querySelectorAll('.tab-pane'),

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

    // Sub-panes
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

    // Endpoints View
    endpointsTableBody: document.getElementById('endpointsTableBody'),
    btnOpenCreateEndpoint: document.getElementById('btnOpenCreateEndpoint'),

    // Callbacks View
    callbacksEndpointSelect: document.getElementById('callbacksEndpointSelect'),
    callbacksListContainer: document.getElementById('callbacksListContainer'),
    btnOpenAddCallback: document.getElementById('btnOpenAddCallback'),

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
    modalCreateEndpoint: document.getElementById('modalCreateEndpoint'),
    formCreateEndpoint: document.getElementById('formCreateEndpoint'),
    modalAddCallback: document.getElementById('modalAddCallback'),
    formAddCallback: document.getElementById('formAddCallback'),
    modalReplay: document.getElementById('modalReplay'),
    replayUrlInput: document.getElementById('replayUrlInput'),
    replayMethodSelect: document.getElementById('replayMethodSelect'),
    replayBodyInput: document.getElementById('replayBodyInput'),
    btnConfirmReplay: document.getElementById('btnConfirmReplay'),

    // Toast
    toastContainer: document.getElementById('toastContainer'),
  };

  // Initialize
  async function init() {
    setupEventListeners();
    await loadEndpoints();
    await loadDispatcherPresets();
    await loadDispatchHistory();
    initSSE();
  }

  // Event Listeners
  function setupEventListeners() {
    // Navigation Tabs
    el.navTabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const targetId = tab.dataset.tab;
        switchTab(targetId);
      });
    });

    // Sub-Tabs
    el.subtabBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetId = btn.dataset.subtab;
        switchSubtab(targetId);
      });
    });

    // Endpoint Selector
    el.endpointSelect.addEventListener('change', (e) => {
      const epId = e.target.value;
      if (epId === 'all') {
        state.currentEndpointId = null;
      } else {
        state.currentEndpointId = parseInt(epId, 10);
      }
      updateEndpointUrlBar();
      loadRequests();
    });

    // Copy Webhook URL
    el.endpointUrlPill.addEventListener('click', () => {
      const url = el.endpointUrlText.textContent;
      navigator.clipboard.writeText(url);
      showToast('Webhook URL copied to clipboard!', 'success');
    });

    // Method Filter Buttons
    el.methodFilters.forEach((btn) => {
      btn.addEventListener('click', () => {
        el.methodFilters.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        state.filterMethod = btn.dataset.method || '';
        loadRequests();
      });
    });

    // Search Box
    let searchTimeout;
    el.searchFilter.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        state.filterSearch = e.target.value.trim();
        loadRequests();
      }, 250);
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

    // Copy as cURL
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
      if (req.raw_body) {
        const escaped = req.raw_body.replace(/"/g, '\\"');
        curl += ` \\\n  -d "${escaped}"`;
      }
      navigator.clipboard.writeText(curl);
      showToast('cURL command copied!', 'success');
    });

    // Copy as Fetch
    el.btnCopyFetch.addEventListener('click', () => {
      if (!state.currentRequestData) return;
      const req = state.currentRequestData;
      const options = {
        method: req.method,
        headers: req.headers || {},
      };
      if (['POST', 'PUT', 'PATCH'].includes(req.method) && req.raw_body) {
        options.body = req.raw_body;
      }
      const fetchStr = `fetch("${req.url}", ${JSON.stringify(options, null, 2)});`;
      navigator.clipboard.writeText(fetchStr);
      showToast('JavaScript Fetch snippet copied!', 'success');
    });

    // Copy Payload
    el.btnCopyPayload.addEventListener('click', () => {
      if (!state.currentRequestData) return;
      const body = state.currentRequestData.raw_body || '';
      navigator.clipboard.writeText(body);
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
      } catch (err) {
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

    // Create Endpoint Modal
    el.btnOpenCreateEndpoint.addEventListener('click', () => {
      openModal(el.modalCreateEndpoint);
    });

    el.formCreateEndpoint.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(el.formCreateEndpoint);
      const payload = {
        name: formData.get('name'),
        slug: formData.get('slug'),
        response_status: parseInt(formData.get('response_status') || '200', 10),
        response_delay_ms: parseInt(formData.get('response_delay_ms') || '0', 10),
        response_body: formData.get('response_body'),
      };

      try {
        const res = await fetch('/api/endpoints', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error('Failed to create endpoint');
        const created = await res.json();
        showToast(`Endpoint /hook/${created.slug} created!`, 'success');
        closeModal(el.modalCreateEndpoint);
        el.formCreateEndpoint.reset();
        await loadEndpoints();
        state.currentEndpointId = created.id;
        el.endpointSelect.value = created.id;
        updateEndpointUrlBar();
        loadRequests();
      } catch (err) {
        showToast('Error: ' + err.message, 'error');
      }
    });

    // Add Callback Rule Modal
    el.btnOpenAddCallback.addEventListener('click', () => {
      const epSelect = document.getElementById('cbEndpointId');
      epSelect.innerHTML = '';
      state.endpoints.forEach((ep) => {
        const opt = document.createElement('option');
        opt.value = ep.id;
        opt.textContent = `${ep.name} (/hook/${ep.slug})`;
        if (state.currentEndpointId === ep.id) opt.selected = true;
        epSelect.appendChild(opt);
      });
      openModal(el.modalAddCallback);
    });

    el.formAddCallback.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(el.formAddCallback);
      const payload = {
        endpoint_id: parseInt(formData.get('endpoint_id'), 10),
        name: formData.get('name'),
        target_url: formData.get('target_url'),
        http_method: formData.get('http_method'),
        delay_seconds: parseInt(formData.get('delay_seconds') || '0', 10),
        payload_mode: formData.get('payload_mode'),
        payload_template: formData.get('payload_template'),
        hmac_enabled: formData.get('hmac_enabled') === 'on',
        hmac_secret: formData.get('hmac_secret'),
        hmac_algorithm: formData.get('hmac_algorithm'),
        hmac_header_name: formData.get('hmac_header_name'),
        max_retries: parseInt(formData.get('max_retries') || '3', 10),
      };

      try {
        const res = await fetch('/api/callback-rules', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error('Failed to create callback rule');
        showToast('Dynamic Callback Rule created!', 'success');
        closeModal(el.modalAddCallback);
        el.formAddCallback.reset();
        loadCallbackRulesView();
      } catch (err) {
        showToast('Error: ' + err.message, 'error');
      }
    });

    // Modal Close buttons
    document.querySelectorAll('.modal-close, .btn-modal-cancel').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.modal-backdrop').forEach(closeModal);
      });
    });

    // Dispatcher Preset Selector
    el.presetSelect.addEventListener('change', (e) => {
      const key = e.target.value;
      if (state.dispatcherPresets[key]) {
        el.dispatchPayload.value = state.dispatcherPresets[key].payload;
      }
    });

    // Dispatcher: Send Request
    el.btnSendDispatch.addEventListener('click', () => executeDispatch(1));
    el.btnSendBurst.addEventListener('click', () => executeDispatch(5));

    // Variable Chips click-to-insert
    document.querySelectorAll('.chip-tag').forEach((chip) => {
      chip.addEventListener('click', () => {
        const targetInputId = chip.dataset.target;
        const targetInput = document.getElementById(targetInputId);
        if (targetInput) {
          const variable = chip.dataset.var;
          const start = targetInput.selectionStart || targetInput.value.length;
          const end = targetInput.selectionEnd || targetInput.value.length;
          const val = targetInput.value;
          targetInput.value = val.substring(0, start) + variable + val.substring(end);
          targetInput.focus();
        }
      });
    });
  }

  // Tabs Switching
  function switchTab(tabId) {
    state.activeTab = tabId;
    el.navTabs.forEach((tab) => {
      tab.classList.toggle('active', tab.dataset.tab === tabId);
    });
    el.tabPanes.forEach((pane) => {
      pane.classList.toggle('active', pane.id === tabId);
    });

    if (tabId === 'tab-endpoints') {
      renderEndpointsTable();
    } else if (tabId === 'tab-callbacks') {
      loadCallbackRulesView();
    } else if (tabId === 'tab-dispatcher') {
      loadDispatchHistory();
    }
  }

  function switchSubtab(subtabId) {
    state.activeSubtab = subtabId;
    el.subtabBtns.forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.subtab === subtabId);
    });
    el.subpaneTabs.forEach((tab) => {
      tab.classList.toggle('active', tab.id === subtabId);
    });
  }

  // Load Endpoints
  async function loadEndpoints() {
    try {
      const res = await fetch('/api/endpoints');
      state.endpoints = await res.json();

      // Populate header dropdown
      el.endpointSelect.innerHTML = '<option value="all">⚡ All Endpoints</option>';
      state.endpoints.forEach((ep) => {
        const opt = document.createElement('option');
        opt.value = ep.id;
        opt.textContent = `${ep.name} (/hook/${ep.slug})`;
        el.endpointSelect.appendChild(opt);
      });

      if (!state.currentEndpointId && state.endpoints.length > 0) {
        state.currentEndpointId = state.endpoints[0].id;
        el.endpointSelect.value = state.endpoints[0].id;
      }

      updateEndpointUrlBar();
      loadRequests();
    } catch (err) {
      console.error('Failed to load endpoints:', err);
    }
  }

  function updateEndpointUrlBar() {
    const currentEp = state.endpoints.find((e) => e.id === state.currentEndpointId);
    if (currentEp) {
      const fullUrl = `${window.location.origin}/hook/${currentEp.slug}`;
      el.endpointUrlText.textContent = fullUrl;
      el.endpointUrlPill.style.display = 'flex';
      // Also update dispatcher default URL
      if (!el.dispatchUrl.value || el.dispatchUrl.value.includes('/hook/')) {
        el.dispatchUrl.value = fullUrl;
      }
    } else {
      el.endpointUrlText.textContent = `${window.location.origin}/hook/...`;
      el.endpointUrlPill.style.display = 'none';
    }
  }

  // Load Requests Feed
  async function loadRequests() {
    let url = '/api/requests?per_page=50';
    if (state.currentEndpointId) url += `&endpoint_id=${state.currentEndpointId}`;
    if (state.filterMethod) url += `&method=${state.filterMethod}`;
    if (state.filterSearch) url += `&search=${encodeURIComponent(state.filterSearch)}`;

    try {
      const res = await fetch(url);
      const data = await res.json();
      state.requests = data.data || [];
      el.reqCountBadge.textContent = data.total || state.requests.length;
      renderRequestsFeed();

      // If a request is currently selected, refresh its details
      if (state.currentRequestId) {
        const found = state.requests.find((r) => r.id === state.currentRequestId);
        if (found) {
          selectRequest(found.id, false);
        } else if (state.requests.length > 0) {
          selectRequest(state.requests[0].id);
        } else {
          state.currentRequestId = null;
          state.currentRequestData = null;
          renderDetails();
        }
      } else if (state.requests.length > 0) {
        selectRequest(state.requests[0].id);
      } else {
        renderDetails();
      }
    } catch (err) {
      console.error('Failed to load requests:', err);
    }
  }

  // Render Feed List
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
      const statusClass = `status-${Math.floor(status / 100)}xx`;
      const timeStr = formatRelativeTime(req.created_at);
      const sizeStr = req.content_length ? formatBytes(req.content_length) : '0 B';

      li.innerHTML = `
        <div class="request-item-header">
          <div class="request-item-badges">
            <span class="badge-method ${method}">${req.method}</span>
            <span class="badge-status ${statusClass}">${status}</span>
          </div>
          <span class="request-time">${timeStr}</span>
        </div>
        <div class="request-path" title="${escapeHtml(req.path)}">${escapeHtml(req.path)}</div>
        <div class="request-item-footer">
          <span>${sizeStr} · ${req.duration_ms || 0}ms</span>
          ${req.callback_logs_count > 0 ? `<span class="callback-indicator">🔄 ${req.callback_logs_count} callback${req.callback_logs_count > 1 ? 's' : ''}</span>` : ''}
        </div>
      `;

      li.addEventListener('click', () => selectRequest(req.id));
      el.requestsList.appendChild(li);
    });
  }

  // Select Single Request & Load Full Details
  async function selectRequest(reqId, updateListSelection = true) {
    state.currentRequestId = reqId;

    if (updateListSelection) {
      document.querySelectorAll('.request-item').forEach((item) => {
        item.classList.toggle('active', item.dataset.id === reqId);
      });
    }

    try {
      const res = await fetch(`/api/requests/${reqId}`);
      if (!res.ok) return;
      state.currentRequestData = await res.json();
      renderDetails();
    } catch (err) {
      console.error('Failed to get request detail:', err);
    }
  }

  // Render Details Pane
  function renderDetails() {
    const req = state.currentRequestData;
    if (!req) {
      el.detailsPaneEmpty.style.display = 'flex';
      el.detailsPaneContent.style.display = 'none';
      return;
    }

    el.detailsPaneEmpty.style.display = 'none';
    el.detailsPaneContent.style.display = 'flex';

    // Header bar
    const method = (req.method || 'POST').toLowerCase();
    el.detailsMethodBadge.className = `badge-method ${method}`;
    el.detailsMethodBadge.textContent = req.method;

    const status = req.response_status || 200;
    const statusClass = `status-${Math.floor(status / 100)}xx`;
    el.detailsStatusBadge.className = `badge-status ${statusClass}`;
    el.detailsStatusBadge.textContent = status;

    el.detailsPath.textContent = req.path;

    // 1. Overview Table
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

    // 2. Headers Table
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

    // 3. Query Params
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

    // 4. Payload Tab
    if (req.raw_body) {
      try {
        const obj = JSON.parse(req.raw_body);
        el.payloadPre.innerHTML = syntaxHighlightJson(JSON.stringify(obj, null, 2));
      } catch (e) {
        el.payloadPre.textContent = req.raw_body;
      }
    } else {
      el.payloadPre.innerHTML = '<span style="color:var(--text-muted);">// No request body payload sent</span>';
    }

    // 5. Response Sent Tab
    el.responseStatusBadge.className = `badge-status ${statusClass}`;
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
        const obj = JSON.parse(req.response_body);
        el.responseBodyPre.innerHTML = syntaxHighlightJson(JSON.stringify(obj, null, 2));
      } catch (e) {
        el.responseBodyPre.textContent = req.response_body;
      }
    } else {
      el.responseBodyPre.innerHTML = '<span style="color:var(--text-muted);">(Empty response body)</span>';
    }

    // 6. Triggered Callbacks Tab
    renderTriggeredCallbacks(req.callback_logs || []);
  }

  function renderTriggeredCallbacks(logs) {
    el.callbacksContainer.innerHTML = '';
    if (!logs || logs.length === 0) {
      el.callbacksContainer.innerHTML = `
        <div style="padding:40px; text-align:center; color:var(--text-muted);">
          <p>No outbound callbacks were triggered for this webhook request.</p>
          <p style="font-size:0.8rem; margin-top:6px;">You can configure automated callbacks under the <strong>Callbacks & Relays</strong> tab.</p>
        </div>
      `;
      return;
    }

    logs.forEach((log) => {
      const card = document.createElement('div');
      card.className = `callback-audit-card ${log.status}`;

      const statusBadge = `<span class="badge-status status-${log.status === 'success' ? '2xx' : '5xx'}">${(log.status || 'unknown').toUpperCase()}</span>`;
      const ruleName = log.callback_rule ? log.callback_rule.name : 'Automated Callback';

      card.innerHTML = `
        <div class="callback-card-header">
          <div>
            <strong>${escapeHtml(ruleName)}</strong> · Attempt #${log.attempt}
          </div>
          <div>${statusBadge}</div>
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
          <summary style="font-size:0.78rem; color:#818cf8; cursor:pointer;">Inspect Outbound Payload & Signature</summary>
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

  // Endpoints Table View
  function renderEndpointsTable() {
    el.endpointsTableBody.innerHTML = '';
    state.endpoints.forEach((ep) => {
      const tr = document.createElement('tr');
      const url = `${window.location.origin}/hook/${ep.slug}`;
      tr.innerHTML = `
        <td style="font-weight:600; color:var(--text-highlight);">${escapeHtml(ep.name)}</td>
        <td><code>/hook/${escapeHtml(ep.slug)}</code></td>
        <td><span class="badge-status status-2xx">${ep.response_status}</span></td>
        <td>${ep.response_delay_ms} ms</td>
        <td>${ep.webhook_requests_count || 0}</td>
        <td>
          <button class="btn btn-secondary" style="padding:3px 8px; font-size:0.74rem;" onclick="navigator.clipboard.writeText('${url}'); HookForge.showToast('Copied URL','info');">Copy URL</button>
          <button class="btn btn-danger" style="padding:3px 8px; font-size:0.74rem;" onclick="HookForge.deleteEndpoint(${ep.id})">Delete</button>
        </td>
      `;
      el.endpointsTableBody.appendChild(tr);
    });
  }

  // Callbacks View
  async function loadCallbackRulesView() {
    try {
      const res = await fetch('/api/callback-rules');
      const rules = await res.json();
      el.callbacksListContainer.innerHTML = '';

      if (rules.length === 0) {
        el.callbacksListContainer.innerHTML = `
          <div style="padding:48px; text-align:center; color:var(--text-muted); background:var(--bg-surface); border-radius:10px; border:1px solid var(--border-subtle);">
            <h3>No Dynamic Callback Rules Configured</h3>
            <p style="margin-top:6px; font-size:0.86rem;">Add a callback rule to automatically forward incoming webhooks to your microservices or API with HMAC signatures and retries.</p>
          </div>
        `;
        return;
      }

      rules.forEach((rule) => {
        const card = document.createElement('div');
        card.className = 'panel-card';
        card.style.display = 'flex';
        card.style.flexDirection = 'column';
        card.style.gap = '12px';

        card.innerHTML = `
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
              <h3 style="color:var(--text-highlight); font-size:1.05rem;">${escapeHtml(rule.name)}</h3>
              <span style="font-size:0.8rem; color:var(--text-muted);">Endpoint: <strong>${rule.endpoint ? rule.endpoint.name : 'Unknown'}</strong> (<code>/hook/${rule.endpoint ? rule.endpoint.slug : ''}</code>)</span>
            </div>
            <div style="display:flex; gap:8px;">
              <button class="btn btn-secondary" onclick="HookForge.testCallbackRule(${rule.id})">🚀 Test Fire</button>
              <button class="btn btn-danger" onclick="HookForge.deleteCallbackRule(${rule.id})">Delete</button>
            </div>
          </div>
          <div style="background:var(--bg-canvas); padding:12px; border-radius:6px; border:1px solid var(--border-subtle); font-family:var(--font-mono); font-size:0.83rem;">
            <div><strong>Target:</strong> ${rule.http_method || 'POST'} <span style="color:#38bdf8;">${escapeHtml(rule.target_url)}</span></div>
            <div style="margin-top:4px;"><strong>Mode:</strong> ${rule.payload_mode} · <strong>Delay:</strong> ${rule.delay_seconds}s · <strong>Retries:</strong> ${rule.max_retries}x (backoff ${rule.retry_backoff_seconds}s)</div>
            ${rule.hmac_enabled ? `<div style="margin-top:4px; color:#34d399;">🔒 HMAC Signed (${rule.hmac_algorithm}) in header <code>${rule.hmac_header_name}</code></div>` : ''}
          </div>
        `;
        el.callbacksListContainer.appendChild(card);
      });
    } catch (err) {
      console.error('Failed to load callback rules:', err);
    }
  }

  // Webhook Dispatcher
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
    if (!targetUrl) {
      showToast('Please provide a target URL', 'error');
      return;
    }

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
    el.btnSendDispatch.textContent = '🚀 Send Webhook';
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
        const statusClass = item.response_status >= 200 && item.response_status < 300 ? 'status-2xx' : 'status-5xx';
        li.innerHTML = `
          <div class="request-item-header">
            <div class="request-item-badges">
              <span class="badge-method ${(item.method || 'POST').toLowerCase()}">${item.method}</span>
              <span class="badge-status ${statusClass}">${item.response_status || 'ERR'}</span>
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

  // Real-Time Server-Sent Events (SSE)
  function initSSE() {
    try {
      const source = new EventSource('/api/stream');

      source.onopen = () => {
        state.sseConnected = true;
        el.liveBadgeText.textContent = 'LIVE STREAM ACTIVE';
      };

      source.addEventListener('request.created', (event) => {
        const data = JSON.parse(event.data);
        // Refresh feed if matches current filter
        if (!state.currentEndpointId || state.currentEndpointId === data.endpoint_id) {
          loadRequests();
          showToast(`New Webhook: ${data.method} (Status ${data.status})`, 'info');
        }
      });

      source.onerror = () => {
        state.sseConnected = false;
        el.liveBadgeText.textContent = 'STREAM RECONNECTING';
      };
    } catch (e) {
      console.warn('SSE stream not supported or blocked:', e);
    }
  }

  // Modal Helpers
  function openModal(modal) {
    modal.classList.add('open');
  }

  function closeModal(modal) {
    modal.classList.remove('open');
  }

  // Toast System
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

  // Utilities
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
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }

  function formatRelativeTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
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
      function (match) {
        let cls = 'color: #f59e0b;'; // number
        if (/^"/.test(match)) {
          if (/:$/.test(match)) {
            cls = 'color: #38bdf8; font-weight:600;'; // key
          } else {
            cls = 'color: #34d399;'; // string
          }
        } else if (/true|false/.test(match)) {
          cls = 'color: #c084fc; font-weight:600;'; // boolean
        } else if (/null/.test(match)) {
          cls = 'color: #94a3b8; font-style:italic;'; // null
        }
        return `<span style="${cls}">${match}</span>`;
      }
    );
  }

  // Global interface for inline event handlers
  window.HookForge = {
    showToast,
    deleteEndpoint: async (id) => {
      if (!confirm('Delete this endpoint and all its captured webhooks?')) return;
      await fetch(`/api/endpoints/${id}`, { method: 'DELETE' });
      showToast('Endpoint deleted', 'info');
      state.currentEndpointId = null;
      await loadEndpoints();
      renderEndpointsTable();
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
        showToast(`Callback test executed: Status ${data.log ? data.log.status : 'done'} (${data.log ? data.log.duration_ms : 0}ms)`, 'success');
      } catch (err) {
        showToast('Callback test failed: ' + err.message, 'error');
      }
    },
  };

  // Start on DOM ready
  document.addEventListener('DOMContentLoaded', init);
})();
