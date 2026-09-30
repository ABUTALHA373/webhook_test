<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HookForge • Dynamic Webhook Testing & Callback Engine</title>
  <meta name="description" content="Production-grade dynamic webhook testing, inspection, and automated outbound callback simulation platform.">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/css/app.css">
  <script>
    (function() {
      const saved = localStorage.getItem('hookforge_theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
      document.documentElement.setAttribute('data-theme', saved);
    })();
  </script>
</head>
<body>

<div class="app-container">
  <!-- Top Application Header -->
  <header class="app-header">
    <div class="brand-section">
      <div class="brand-logo" title="HookForge Engine">
        <svg viewBox="0 0 24 24">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
        </svg>
      </div>
      <div class="brand-title">
        HookForge <span class="brand-badge">PRO</span>
      </div>
      <div class="live-stream-badge" title="Real-Time Server-Sent Events Connection">
        <span class="pulse-dot"></span>
        <span id="liveBadgeText">LIVE STREAM ACTIVE</span>
      </div>
    </div>

    <!-- Header Actions (Theme Switcher) -->
    <div class="header-actions">
      <button id="btnThemeToggle" class="btn-theme-toggle" title="Toggle Light/Dark Theme">
        <svg id="themeIconSun" style="display:none;" viewBox="0 0 24 24"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/></svg>
        <svg id="themeIconMoon" viewBox="0 0 24 24"><path d="M12.3 2a10 10 0 0 0-.19 14 9.92 9.92 0 0 0 7.9 3.89c.35 0 .69-.03 1.03-.07A10 10 0 1 1 12.3 2z"/></svg>
      </button>
    </div>
  </header>

  <!-- Navigation Bar -->
  <nav class="app-nav">
    <button class="nav-tab active" data-tab="tab-inspector">
      <span>Live Requests</span>
      <span id="reqCountBadge" class="nav-tab-badge">0</span>
    </button>
    <button class="nav-tab" data-tab="tab-webhooks">
      <span>Webhook Endpoints</span>
    </button>
    <button class="nav-tab" data-tab="tab-callbacks">
      <span>Callback Rules</span>
    </button>
    <button class="nav-tab" data-tab="tab-dispatcher">
      <span>Outbound Dispatcher</span>
    </button>
  </nav>

  <!-- Main Body Content Area -->
  <main class="app-body">

    <!-- ====================================================================
         TAB 1: LIVE REQUESTS INSPECTOR (2-Pane Split)
         ==================================================================== -->
    <div id="tab-inspector" class="tab-pane active">
      <div class="inspector-layout">
        <!-- Left Pane: Incoming Requests Feed -->
        <aside class="requests-feed">
          <div class="feed-toolbar">
            <!-- Dedicated Endpoint Filter (Custom Dropdown) -->
            <div class="feed-endpoint-filter">
              <div class="feed-ep-header">
                <span>Endpoint Filter</span>
                <span id="feedEndpointCountBadge" class="feed-ep-badge">All</span>
              </div>

              <!-- Custom Dropdown Container -->
              <div class="custom-dropdown" id="feedEndpointCustomDropdown">
                <button type="button" class="custom-dropdown-trigger" id="feedEndpointDropdownBtn" aria-haspopup="listbox" aria-expanded="false">
                  <div class="custom-dropdown-content">
                    <span class="custom-dropdown-title" id="feedDropdownTitle">All Endpoints</span>
                    <span class="custom-dropdown-subtitle" id="feedDropdownSubtitle">All incoming traffic</span>
                  </div>
                  <div class="custom-dropdown-meta">
                    <span class="custom-dropdown-badge" id="feedDropdownBadge">0</span>
                    <svg class="custom-dropdown-chevron" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </button>
                <div class="custom-dropdown-menu" id="feedDropdownMenu" role="listbox">
                  <!-- Dynamically populated by JS -->
                </div>
              </div>

              <!-- Native select preserved for syncing -->
              <select id="feedEndpointSelect" style="display:none;">
                <option value="all">All Endpoints</option>
              </select>
            </div>

            <!-- Active Endpoint Direct Webhook URL Bar -->
            <div id="feedEndpointUrlBar" class="feed-endpoint-banner" style="display:none;">
              <span id="feedEndpointUrlText" class="feed-url-text code-font"></span>
              <button type="button" id="btnCopyFeedUrl" class="btn-copy-feed-url" title="Copy Webhook URL">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                  <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span>Copy</span>
              </button>
            </div>

            <div class="search-box">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input type="text" id="searchFilter" placeholder="Filter by path, payload keyword, ID..." autocomplete="off">
            </div>

            <div class="filter-row">
              <div class="method-filters">
                <button class="btn-filter active" data-method="">ALL</button>
                <button class="btn-filter" data-method="POST">POST</button>
                <button class="btn-filter" data-method="GET">GET</button>
                <button class="btn-filter" data-method="PUT">PUT</button>
                <button class="btn-filter" data-method="PATCH">PATCH</button>
                <button class="btn-filter" data-method="DELETE">DEL</button>
              </div>

              <button id="btnClearFeed" class="btn-clear-feed" title="Clear captured requests">
                Clear
              </button>
            </div>
          </div>

          <!-- List of Captured Requests -->
          <ul id="requestsList" class="requests-list"></ul>

          <!-- Empty Feed State -->
          <div id="emptyFeed" class="empty-feed">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p><strong>Waiting for incoming webhooks...</strong></p>
            <p style="font-size: 0.78rem;">Send any HTTP request to the webhook URL above, or use the <strong>Outbound Dispatcher</strong> to fire test events.</p>
            <button id="btnQuickMockEmpty" class="btn-quick-mock" style="margin-top: 14px;">
              Send Sample Webhook
            </button>
          </div>
        </aside>

        <!-- Right Pane: Request Deep Inspector -->
        <section class="request-details-pane">
          <!-- Details Empty State -->
          <div id="detailsPaneEmpty" class="empty-feed" style="margin: auto;">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"></path>
            </svg>
            <p>Select a webhook request from the feed to inspect headers, payload, response, and automated callbacks.</p>
          </div>

          <!-- Details Content (Shown when a request is active) -->
          <div id="detailsPaneContent" style="display: none; flex-direction: column; height: 100%;">
            <!-- Header Bar -->
            <div class="details-header">
              <div class="details-title-row">
                <span id="detailsMethodBadge" class="badge-method post">POST</span>
                <span id="detailsStatusBadge" class="badge-status status-2xx">200</span>
                <span id="detailsPath" class="detailsPath">/hook/default</span>
              </div>

              <div class="details-actions">
                <button id="btnCopyCurl" class="btn btn-secondary" title="Copy as cURL command">cURL</button>
                <button id="btnCopyFetch" class="btn btn-secondary" title="Copy as JS Fetch snippet">Fetch</button>
                <button id="btnReplayModal" class="btn btn-primary" title="Replay this request">Replay</button>
                <button id="btnDeleteReq" class="btn btn-danger" title="Delete request">Delete</button>
              </div>
            </div>

            <!-- Subtabs Navigation -->
            <div class="subtabs-bar">
              <button class="subtab-btn" data-subtab="subtab-overview">Overview</button>
              <button class="subtab-btn" data-subtab="subtab-headers">Headers</button>
              <button class="subtab-btn" data-subtab="subtab-query">Query Params</button>
              <button class="subtab-btn active" data-subtab="subtab-payload">Payload / Body</button>
              <button class="subtab-btn" data-subtab="subtab-response">Response Sent</button>
              <button class="subtab-btn" data-subtab="subtab-callbacks">Triggered Callbacks</button>
            </div>

            <!-- Subpane Body -->
            <div class="subpane-body">
              <!-- 1. Overview -->
              <div id="subtab-overview" class="subpane-tab">
                <table class="kv-table">
                  <tbody id="overviewTableBody"></tbody>
                </table>
              </div>

              <!-- 2. Headers -->
              <div id="subtab-headers" class="subpane-tab">
                <table class="kv-table">
                  <thead>
                    <tr><th>Header Name</th><th>Value</th></tr>
                  </thead>
                  <tbody id="headersTableBody"></tbody>
                </table>
              </div>

              <!-- 3. Query Parameters -->
              <div id="subtab-query" class="subpane-tab">
                <p id="queryEmptyMsg" style="color:var(--text-muted); display:none;">No query string parameters present in this request.</p>
                <table class="kv-table">
                  <thead>
                    <tr><th>Parameter</th><th>Value</th></tr>
                  </thead>
                  <tbody id="queryTableBody"></tbody>
                </table>
              </div>

              <!-- 4. Payload / Body -->
              <div id="subtab-payload" class="subpane-tab active">
                <div class="code-container">
                  <div class="code-header">
                    <span class="code-title">Incoming Payload (Formatted JSON / Raw)</span>
                    <div class="code-actions">
                      <button id="btnCopyPayload" class="btn-copy-code">Copy Raw</button>
                    </div>
                  </div>
                  <pre id="payloadPre" class="code-pre"></pre>
                </div>
              </div>

              <!-- 5. Response Sent -->
              <div id="subtab-response" class="subpane-tab">
                <div style="display:flex; gap:16px; align-items:center; margin-bottom:16px;">
                  <span>HTTP Status: <span id="responseStatusBadge" class="badge-status status-2xx">200</span></span>
                  <span>Simulated Duration: <strong id="responseDurationText">0 ms</strong></span>
                </div>
                <h4 style="font-size:0.82rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px;">Response Headers</h4>
                <table class="kv-table" style="margin-bottom:20px;">
                  <tbody id="responseHeadersBody"></tbody>
                </table>
                <h4 style="font-size:0.82rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px;">Response Body</h4>
                <div class="code-container">
                  <pre id="responseBodyPre" class="code-pre"></pre>
                </div>
              </div>

              <!-- 6. Triggered Callbacks -->
              <div id="subtab-callbacks" class="subpane-tab">
                <div id="callbacksContainer" class="callbacks-card-list"></div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- ====================================================================
         TAB 2: WEBHOOK ENDPOINTS — Full Management
         ==================================================================== -->
    <div id="tab-webhooks" class="tab-pane">
      <div class="full-tab-view">
        <div class="view-container">

          <!-- Header -->
          <div class="view-header">
            <div class="view-title-group">
              <h2>Webhook Endpoints</h2>
              <p>Create webhook receivers with custom slugs, status codes, simulated latency, and fully dynamic response bodies powered by incoming request data.</p>
            </div>
            <button id="btnOpenCreateEndpoint" class="btn btn-primary">+ New Endpoint</button>
          </div>

          <!-- Endpoints Table -->
          <div class="panel-card" id="endpointsPanelCard">
            <table class="kv-table" id="endpointsTable">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>URL / Slug</th>
                  <th>Response</th>
                  <th>Delay</th>
                  <th>Requests</th>
                  <th>Callbacks</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody id="endpointsTableBody"></tbody>
            </table>
          </div>

          <!-- Inline Endpoint Editor (hidden by default, shown on create/edit) -->
          <div id="endpointEditorPanel" class="panel-card" style="display:none; margin-top:20px;">
            <div class="editor-panel-header">
              <h3 id="endpointEditorTitle" class="panel-title" style="margin:0;">Configure Endpoint</h3>
              <button id="btnCloseEndpointEditor" class="btn btn-secondary" style="padding:4px 10px;">✕ Close</button>
            </div>

            <form id="formEndpointEditor" style="margin-top:20px;">
              <input type="hidden" id="epEditorId" value="">

              <div class="grid-3">
                <div class="form-group">
                  <label class="form-label">Endpoint Name <span style="color:var(--color-danger);">*</span></label>
                  <input type="text" id="epEditorName" class="form-control" placeholder="e.g. GitHub Webhooks" required>
                </div>
                <div class="form-group">
                  <label class="form-label">
                    <span>Custom Slug</span>
                    <span class="form-hint">Auto-generated if blank</span>
                  </label>
                  <input type="text" id="epEditorSlug" class="form-control code-font" placeholder="e.g. github-ci">
                </div>
                <div class="form-group">
                  <label class="form-label">
                    <span>Secret Token</span>
                    <span class="form-hint">Optional HMAC verification</span>
                  </label>
                  <input type="text" id="epEditorSecret" class="form-control code-font" placeholder="whsec_...">
                </div>
              </div>

              <div class="grid-3">
                <div class="form-group">
                  <label class="form-label">Response Status</label>
                  <input type="number" id="epEditorStatus" class="form-control" value="200" min="100" max="599">
                  <span class="form-hint" style="margin-top:4px; display:block;">Supports dynamic token e.g. <code>@{{body.code||200}}</code></span>
                </div>
                <div class="form-group">
                  <label class="form-label">Simulated Delay (ms)</label>
                  <input type="number" id="epEditorDelay" class="form-control" value="0" min="0" max="30000">
                </div>
                <div class="form-group">
                  <label class="form-label">
                    <span>Custom Response Header</span>
                    <span class="form-hint">Key: Value</span>
                  </label>
                  <input type="text" id="epEditorHeaderKV" class="form-control code-font" placeholder="X-Custom: @{{req.id}}">
                </div>
              </div>

              <!-- Dynamic Response Body — with variable reference sidebar -->
              <div class="grid-2" style="align-items:flex-start;">
                <div class="form-group">
                  <label class="form-label">
                    <span>Dynamic Response Body</span>
                    <span class="form-hint">Use tokens to reference incoming request data</span>
                  </label>
                  <!-- Quick Insert Chips -->
                  <div class="chips-row" style="margin-bottom:8px;">
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{req.id}}">req.id</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{req.method}}">req.method</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{timestamp_iso}}">timestamp</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{query.customer_id}}">query.customer_id</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{query.token}}">query.token</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{query.order_id}}">query.order_id</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{param.tier}}">param.tier</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{body.event}}">body.event</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{body.id}}">body.id</span>
                    <span class="chip-tag" data-target="epEditorBody" data-var="@{{headers.x-token}}">header.x-token</span>
                  </div>
                  <textarea id="epEditorBody" class="form-control code-font" style="min-height:200px;">{
  "status": "ok",
  "request_id": "@{{req.id}}",
  "method": "@{{req.method}}",
  "received_params": {
    "customer_id": "@{{query.customer_id || body.customer_id || 'none'}}",
    "token": "@{{query.token || 'none'}}"
  },
  "received_at": "@{{timestamp_iso}}"
}</textarea>
                </div>

                <!-- Variable Reference Card -->
                <div class="var-reference-card">
                  <div class="var-ref-title">Dynamic Variable Reference</div>
                  <table class="var-ref-table">
                    <tbody>
                      <tr>
                        <td class="var-ref-token"><code>@{{query.param}}</code></td>
                        <td class="var-ref-desc">Query string param (e.g. <code>?customer_id=12</code>)</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{param.name}}</code></td>
                        <td class="var-ref-desc">Parameter from query string or body</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{query}}</code></td>
                        <td class="var-ref-desc">All query params formatted as JSON</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{body.key}}</code></td>
                        <td class="var-ref-desc">JSON body field (e.g. <code>body.user.name</code>)</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{headers.x-token}}</code></td>
                        <td class="var-ref-desc">Incoming HTTP header value</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{req.id}}</code></td>
                        <td class="var-ref-desc">Unique UUID assigned to request</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{req.ip}}</code></td>
                        <td class="var-ref-desc">Sender IP address</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{timestamp_iso}}</code></td>
                        <td class="var-ref-desc">Current ISO8601 server timestamp</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{val||default}}</code></td>
                        <td class="var-ref-desc">Fallback value if token is missing</td>
                      </tr>
                      <tr>
                        <td class="var-ref-token"><code>@{{upper(query.x)}}</code></td>
                        <td class="var-ref-desc">Transform: <code>upper()</code>, <code>lower()</code>, <code>default()</code></td>
                      </tr>
                    </tbody>
                  </table>
                  <!-- Live Eval Button -->
                  <button type="button" id="btnRunEvalPlayground" class="btn btn-primary" style="width:100%; margin-top:12px;">Preview Response</button>
                  <div id="evalPreviewBox" style="display:none; margin-top:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                      <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">EVALUATED STATUS:</span>
                      <span id="evalResultStatus" class="badge-status status-2xx">HTTP 200</span>
                    </div>
                    <pre id="evalResultBody" class="code-pre" style="max-height:180px; overflow:auto; font-size:0.78rem;"></pre>
                  </div>
                </div>
              </div>

              <!-- Required Parameters Builder -->
              <div class="req-params-section">
                <div class="req-params-header">
                  <div>
                    <div class="req-params-title">Required Parameters & Constraints</div>
                    <div class="req-params-hint">Define parameters (from URL query string, request body, or headers) that MUST be present. If missing or invalid, returns HTTP 422 with validation errors and logs the request to the inspector. Click <strong>"Use in Response"</strong> to return any parameter in your dynamic response.</div>
                  </div>
                  <button type="button" id="btnAddReqParam" class="btn btn-secondary" style="font-size:0.8rem; white-space:nowrap;">+ Add Required Parameter</button>
                </div>

                <div id="reqParamsList" class="req-params-list">
                  <!-- rows injected by JS -->
                </div>

                <!-- Empty state -->
                <div id="reqParamsEmpty" class="req-params-empty">
                  No required parameters — all incoming requests will be accepted regardless of content.
                </div>
              </div>

              <div class="form-actions" style="margin-top:20px;">
                <button type="submit" id="btnSubmitEndpointEditor" class="btn btn-primary">Save Endpoint</button>
                <button type="button" id="btnCancelEndpointEditor" class="btn btn-secondary">Cancel</button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>

    <!-- ====================================================================
         TAB 3: CALLBACK RULES — Separate & Full Management
         ==================================================================== -->
    <div id="tab-callbacks" class="tab-pane">
      <div class="full-tab-view">
        <div class="view-container">

          <!-- Header -->
          <div class="view-header">
            <div class="view-title-group">
              <h2>Callback Rules</h2>
              <p>Configure automated outbound callbacks triggered on every incoming webhook. Each rule can forward, transform, sign with HMAC, and retry independently.</p>
            </div>
            <button id="btnOpenAddCallback" class="btn btn-primary">+ Add Callback Rule</button>
          </div>

          <!-- Filter by endpoint -->
          <div class="panel-card" style="padding:14px 20px; margin-bottom:16px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <span style="font-size:0.84rem; font-weight:600; color:var(--text-secondary);">Filter by endpoint:</span>
            <select id="callbackFilterEndpoint" class="form-control" style="max-width:280px;">
              <option value="">All Endpoints</option>
            </select>
            <span id="callbackRuleCount" style="margin-left:auto; font-size:0.8rem; color:var(--text-muted);"></span>
          </div>

          <!-- Inline Callback Rule Editor (Positioned at top so it is immediately visible) -->
          <div id="callbackEditorPanel" class="panel-card" style="display:none; margin-bottom:20px; border-left:4px solid var(--accent-primary);">
            <div class="editor-panel-header">
              <h3 id="callbackEditorTitle" class="panel-title" style="margin:0;">Configure Callback Rule</h3>
              <button id="btnCloseCallbackEditor" class="btn btn-secondary" style="padding:4px 10px;">✕ Close</button>
            </div>

            <form id="formCallbackEditor" style="margin-top:20px;">
              <input type="hidden" id="cbEditorId" value="">

              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">Rule Name <span style="color:var(--color-danger);">*</span></label>
                  <input type="text" id="cbEditorName" class="form-control" placeholder="e.g. Payment Microservice Relay" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Attached Endpoint <span style="color:var(--color-danger);">*</span></label>
                  <select id="cbEditorEndpointId" class="form-control" required></select>
                </div>
              </div>

              <div class="grid-3">
                <div class="form-group">
                  <label class="form-label">HTTP Method</label>
                  <select id="cbEditorMethod" class="form-control">
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                    <option value="GET">GET</option>
                    <option value="DELETE">DELETE</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Delay (seconds)</label>
                  <input type="number" id="cbEditorDelay" class="form-control" value="0" min="0" max="300">
                </div>
                <div class="form-group">
                  <label class="form-label">Max Retries</label>
                  <input type="number" id="cbEditorRetries" class="form-control" value="3" min="0" max="10">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <span>Target URL <span style="color:var(--color-danger);">*</span></span>
                  <span class="form-hint">Supports dynamic tokens — e.g. <code>@{{body.callback_url}}</code> or <code>https://api.myapp.com/@{{query.tenant}}</code></span>
                </label>
                <input type="text" id="cbEditorTargetUrl" class="form-control code-font" placeholder="https://api.myapp.com/hooks or @{{body.callback_url}}" required>
              </div>

              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">
                    <span>Custom HTTP Headers (Optional)</span>
                    <span class="form-hint">Format: <code>Header: Value</code> (one per line)</span>
                  </label>
                  <textarea id="cbEditorCustomHeaders" class="form-control code-font" style="min-height:80px;" placeholder="Authorization: Bearer @{{headers.x-token}}
X-Source: HookForge-Relay"></textarea>
                </div>

                <div class="form-group">
                  <label class="form-label">Payload Mode</label>
                  <select id="cbEditorPayloadMode" class="form-control">
                    <option value="template">Custom JSON Template</option>
                    <option value="passthrough">Passthrough (Forward exact incoming body)</option>
                    <option value="empty">Empty Body</option>
                  </select>
                  <span class="form-hint" style="margin-top:6px; display:block;">Passthrough forwards the raw incoming payload exactly as received.</span>
                </div>
              </div>

              <div class="form-group" id="cbTemplateGroup">
                <label class="form-label">
                  <span>Payload Template</span>
                  <span class="form-hint">Use <code>@{{body.x}}</code>, <code>@{{req.id}}</code>, <code>@{{headers.x-token}}</code>, <code>@{{query.token}}</code>, etc.</span>
                </label>
                <div class="chips-row" style="margin-bottom:8px;">
                  <span class="chip-tag" data-target="cbEditorTemplate" data-var="@{{req.id}}">req.id</span>
                  <span class="chip-tag" data-target="cbEditorTemplate" data-var="@{{timestamp_iso}}">timestamp</span>
                  <span class="chip-tag" data-target="cbEditorTemplate" data-var="@{{body.event}}">body.event</span>
                  <span class="chip-tag" data-target="cbEditorTemplate" data-var="@{{body.id}}">body.id</span>
                  <span class="chip-tag" data-target="cbEditorTemplate" data-var="@{{body}}">full body</span>
                  <span class="chip-tag" data-target="cbEditorTemplate" data-var="@{{query.customer_id}}">query.customer_id</span>
                  <span class="chip-tag" data-target="cbEditorTemplate" data-var="@{{headers.x-request-id}}">header</span>
                </div>
                <textarea id="cbEditorTemplate" class="form-control code-font" style="min-height:130px;">{
  "event": "WEBHOOK_RELAY",
  "source_request_id": "@{{req.id}}",
  "processed_at": "@{{timestamp_iso}}",
  "data": @{{body}}
}</textarea>
              </div>

              <!-- HMAC Signature Section -->
              <div class="form-group" style="background:var(--bg-canvas); padding:14px; border-radius:8px; border:1px solid var(--border-subtle);">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom:10px;">
                  <input type="checkbox" id="cbEditorHmacEnabled">
                  <span style="font-size:0.9rem; font-weight:600;">Sign Outbound Request with HMAC</span>
                </label>
                <div id="cbHmacFields" style="display:none;">
                  <div class="grid-3">
                    <div class="form-group" style="margin-bottom:0;">
                      <label class="form-label">HMAC Secret</label>
                      <input type="text" id="cbEditorHmacSecret" class="form-control code-font" placeholder="whsec_...">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                      <label class="form-label">Algorithm</label>
                      <select id="cbEditorHmacAlgo" class="form-control">
                        <option value="sha256">SHA-256</option>
                        <option value="sha1">SHA-1</option>
                        <option value="sha512">SHA-512</option>
                        <option value="md5">MD5</option>
                      </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                      <label class="form-label">Signature Header</label>
                      <input type="text" id="cbEditorHmacHeader" class="form-control code-font" value="X-Signature-256">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Preview Resolution Box -->
              <div style="margin-top:14px;">
                <button type="button" id="btnPreviewCallback" class="btn btn-secondary" style="font-size:0.82rem;">Test Dynamic Resolution</button>
                <div id="cbPreviewBox" style="display:none; margin-top:12px; background:var(--bg-canvas); border:1px solid var(--border-subtle); border-radius:8px; padding:14px;">
                  <div style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:6px;">Resolved Outbound URL:</div>
                  <div id="cbPreviewUrl" class="code-font" style="color:var(--color-info); word-break:break-all; margin-bottom:10px; font-size:0.85rem;"></div>
                  <div style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:6px;">Resolved Payload:</div>
                  <pre id="cbPreviewPayload" class="code-pre" style="max-height:160px; overflow:auto; font-size:0.78rem;"></pre>
                </div>
              </div>

              <div class="form-actions" style="margin-top:20px;">
                <button type="submit" id="btnSubmitCallbackEditor" class="btn btn-primary">Save Callback Rule</button>
                <button type="button" id="btnCancelCallbackEditor" class="btn btn-secondary">Cancel</button>
              </div>
            </form>
          </div>

          <!-- Callback Rules List -->
          <div id="callbacksListContainer" style="display:flex; flex-direction:column; gap:16px;"></div>

        </div>
      </div>
    </div>

    <!-- ====================================================================
         TAB 4: WEBHOOK DISPATCHER / SIMULATOR
         ==================================================================== -->
    <div id="tab-dispatcher" class="tab-pane">
      <div class="full-tab-view">
        <div class="view-container">
          <div class="view-header">
            <div class="view-title-group">
              <h2>Outbound Webhook Dispatcher</h2>
              <p>Simulate real-world webhooks (Stripe, GitHub, Shopify, Slack) or send custom JSON events to test your local/remote microservices.</p>
            </div>
          </div>

          <div class="grid-2">
            <!-- Left: Dispatcher Form -->
            <div class="panel-card">
              <h3 class="panel-title">Dispatch Configuration</h3>

              <div class="form-group">
                <label class="form-label">Preset Template</label>
                <select id="presetSelect" class="form-control">
                  <option value="">-- Choose a Preset --</option>
                </select>
              </div>

              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">HTTP Method</label>
                  <select id="dispatchMethod" class="form-control">
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                    <option value="GET">GET</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">Target URL</label>
                  <input type="text" id="dispatchUrl" class="form-control code-font" placeholder="http://localhost:8000/hook/default">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <span>Payload Body (JSON)</span>
                  <span class="form-hint">Supports dynamic variables</span>
                </label>
                <textarea id="dispatchPayload" class="form-control code-font" style="min-height: 220px;" placeholder="{}"></textarea>
              </div>

              <div class="form-group" style="background:var(--bg-canvas); padding:12px; border-radius:6px; border:1px solid var(--border-subtle);">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                  <input type="checkbox" id="dispatchHmacEnabled">
                  <span style="font-size:0.84rem; font-weight:600;">Enable HMAC Signature Header</span>
                </label>
                <div class="grid-2" style="margin-top:10px;">
                  <input type="text" id="dispatchHmacSecret" class="form-control code-font" placeholder="Signing secret (e.g. whsec_...)" value="whsec_test_secret_123">
                  <input type="text" id="dispatchHmacHeader" class="form-control code-font" placeholder="Header name" value="X-Signature-256">
                </div>
              </div>

              <div style="display:flex; gap:10px; margin-top:16px;">
                <button id="btnSendDispatch" class="btn btn-primary" style="flex:1;">Send Webhook</button>
                <button id="btnSendBurst" class="btn btn-secondary" title="Send 5 requests in succession">Burst Test (5x)</button>
              </div>
            </div>

            <!-- Right: Dispatch Result & History -->
            <div style="display:flex; flex-direction:column; gap:16px;">
              <!-- Live Response Result -->
              <div id="dispatchResultCard" class="panel-card" style="display:none;">
                <h3 class="panel-title">Response Received</h3>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                  <span id="dispatchResultStatus" class="badge-status status-2xx">HTTP 200</span>
                  <span id="dispatchResultDuration" style="font-size:0.8rem; color:var(--text-muted);">0 ms</span>
                </div>
                <pre id="dispatchResultBody" class="code-pre" style="max-height:220px; background:#050811; border-radius:6px; padding:12px; font-size:0.8rem;"></pre>
              </div>

              <!-- Dispatch History -->
              <div class="panel-card" style="flex:1; display:flex; flex-direction:column;">
                <h3 class="panel-title">Recent Dispatches</h3>
                <ul id="dispatchHistoryList" class="requests-list" style="max-height:360px;"></ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- ====================================================================
     MODALS
     ==================================================================== -->

<!-- Modal: Replay Request -->
<div id="modalReplay" class="modal-backdrop">
  <div class="modal-card">
    <div class="modal-header">
      <h3 class="modal-title">Replay Webhook Request</h3>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Target URL</label>
          <input type="text" id="replayUrlInput" class="form-control code-font">
        </div>
        <div class="form-group">
          <label class="form-label">HTTP Method</label>
          <select id="replayMethodSelect" class="form-control">
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="PATCH">PATCH</option>
            <option value="GET">GET</option>
            <option value="DELETE">DELETE</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Payload Body</label>
        <textarea id="replayBodyInput" class="form-control code-font" style="min-height:180px;"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-modal-cancel">Cancel</button>
      <button type="button" id="btnConfirmReplay" class="btn btn-primary">Send Replay</button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<script src="/js/app.js"></script>
</body>
</html>
