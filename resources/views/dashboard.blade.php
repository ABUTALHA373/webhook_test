<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HookForge &bull; Dynamic Webhook Testing &amp; Callback Engine</title>
  <meta name="description" content="Production-grade dynamic webhook testing, inspection, and automated outbound callback simulation platform.">
  
  <!-- Modern Typography: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/css/app.css">
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

    <!-- Active Endpoint Selector & Quick Copy Pill -->
    <div class="header-actions">
      <div class="endpoint-picker-box">
        <span class="endpoint-label">Active Sink:</span>
        <select id="endpointSelect" class="endpoint-select" aria-label="Select Active Endpoint">
          <option value="all">⚡ All Endpoints</option>
        </select>
      </div>

      <div id="endpointUrlPill" class="url-copy-pill" title="Click to copy public webhook URL">
        <span id="endpointUrlText">{{ url('/hook/default') }}</span>
        <span>📋</span>
      </div>
    </div>
  </header>

  <!-- Navigation Bar -->
  <nav class="app-nav">
    <button class="nav-tab active" data-tab="tab-inspector">
      <span>⚡ Live Requests</span>
      <span id="reqCountBadge" class="nav-tab-badge">0</span>
    </button>
    <button class="nav-tab" data-tab="tab-endpoints">
      <span>⚙️ Endpoints &amp; Rules</span>
    </button>
    <button class="nav-tab" data-tab="tab-callbacks">
      <span>🔄 Callbacks &amp; Relays</span>
    </button>
    <button class="nav-tab" data-tab="tab-dispatcher">
      <span>🚀 Outbound Dispatcher</span>
    </button>
    <button class="nav-tab" data-tab="tab-docs">
      <span>📖 API &amp; cURL Recipes</span>
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
                <span>🗑️</span> Clear
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
                <button id="btnReplayModal" class="btn btn-primary" title="Replay this request">🔄 Replay</button>
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
         TAB 2: ENDPOINTS & RESPONSE CONFIGURATOR
         ==================================================================== -->
    <div id="tab-endpoints" class="tab-pane">
      <div class="full-tab-view">
        <div class="view-container">
          <div class="view-header">
            <div class="view-title-group">
              <h2>Webhook Endpoints &amp; Dynamic Rules</h2>
              <p>Create distinct endpoints with custom status codes, artificial latency simulation, and dynamic templated responses.</p>
            </div>
            <button id="btnOpenCreateEndpoint" class="btn btn-primary">+ Create New Endpoint</button>
          </div>

          <div class="panel-card">
            <table class="kv-table">
              <thead>
                <tr>
                  <th>Endpoint Name</th>
                  <th>Path</th>
                  <th>Default Status</th>
                  <th>Simulated Delay</th>
                  <th>Requests Captured</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="endpointsTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================================
         TAB 3: DYNAMIC CALLBACKS & RELAYS
         ==================================================================== -->
    <div id="tab-callbacks" class="tab-pane">
      <div class="full-tab-view">
        <div class="view-container">
          <div class="view-header">
            <div class="view-title-group">
              <h2>Automated Outbound Callbacks &amp; Relays</h2>
              <p>Configure automated callbacks triggered whenever a webhook arrives. Supports dynamic URLs, payload transformation, HMAC signatures, and exponential retries.</p>
            </div>
            <button id="btnOpenAddCallback" class="btn btn-primary">+ Add Callback Rule</button>
          </div>

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
              <h2>Outbound Webhook Dispatcher &amp; Event Generator</h2>
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
                <button id="btnSendDispatch" class="btn btn-primary" style="flex:1;">🚀 Send Webhook</button>
                <button id="btnSendBurst" class="btn btn-secondary" title="Send 5 requests in succession">⚡ Burst Test (5x)</button>
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

    <!-- ====================================================================
         TAB 5: API DOCUMENTATION & cURL RECIPES
         ==================================================================== -->
    <div id="tab-docs" class="tab-pane">
      <div class="full-tab-view">
        <div class="view-container">
          <div class="view-header">
            <div class="view-title-group">
              <h2>Developer API &amp; cURL Recipes</h2>
              <p>Copy-paste ready recipes to integrate and test HookForge with your favorite programming languages.</p>
            </div>
          </div>

          <div class="panel-card">
            <h3 class="panel-title">cURL Recipe: Standard Webhook</h3>
            <div class="code-container">
              <pre class="code-pre">curl -X POST "{{ url('/hook/default') }}" \
  -H "Content-Type: application/json" \
  -H "X-Custom-Token: client-token-884" \
  -d '{
    "event": "order.completed",
    "id": "ord_992819",
    "amount": 149.50,
    "callback_url": "https://httpbin.org/post"
  }'</pre>
            </div>
          </div>

          <div class="panel-card">
            <h3 class="panel-title">cURL Recipe: Dynamic Callback Trigger</h3>
            <p style="color:var(--text-secondary); font-size:0.84rem; margin-bottom:12px;">
              Include a <code>callback_url</code> in your payload. The <strong>Echo Callback</strong> rule configured on <code>default</code> will automatically relay a transformed, HMAC-signed event to that URL!
            </p>
            <div class="code-container">
              <pre class="code-pre">curl -X POST "{{ url('/hook/default') }}" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "payment.succeeded",
    "id": "pay_554422",
    "callback_url": "{{ url('/hook/stripe-mock') }}"
  }'</pre>
            </div>
          </div>

          <div class="panel-card">
            <h3 class="panel-title">Python Requests Recipe</h3>
            <div class="code-container">
              <pre class="code-pre">import requests

url = "{{ url('/hook/default') }}"
payload = {
    "event": "invoice.paid",
    "id": "inv_7721",
    "customer": "cus_99182"
}
headers = {"Content-Type": "application/json"}

response = requests.post(url, json=payload, headers=headers)
print(f"Status: {response.status_code}")
print(response.json())</pre>
            </div>
          </div>

          <div class="panel-card">
            <h3 class="panel-title">Dynamic Variable Reference</h3>
            <table class="kv-table">
              <thead><tr><th>Variable Token</th><th>Description / Example</th></tr></thead>
              <tbody>
                <tr><td class="kv-key"><code>@{{req.id}}</code></td><td class="kv-val">Unique UUID assigned to the incoming webhook request</td></tr>
                <tr><td class="kv-key"><code>@{{req.ip}}</code></td><td class="kv-val">Remote client IP address</td></tr>
                <tr><td class="kv-key"><code>@{{timestamp_iso}}</code></td><td class="kv-val">Current ISO8601 timestamp (e.g. 2026-09-30T05:00:00Z)</td></tr>
                <tr><td class="kv-key"><code>@{{body.path.to.key}}</code></td><td class="kv-val">Nested property from incoming JSON body (e.g. <code>body.data.id</code>)</td></tr>
                <tr><td class="kv-key"><code>@{{headers.header_name}}</code></td><td class="kv-val">Value of an incoming HTTP header (e.g. <code>headers.x-request-id</code>)</td></tr>
                <tr><td class="kv-key"><code>@{{query.param_name}}</code></td><td class="kv-val">Query string parameter (e.g. <code>?token=abc</code> -> <code>query.token</code>)</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- ====================================================================
     MODALS
     ==================================================================== -->

<!-- Modal: Create Endpoint -->
<div id="modalCreateEndpoint" class="modal-backdrop">
  <div class="modal-card">
    <div class="modal-header">
      <h3 class="modal-title">Create Webhook Endpoint</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form id="formCreateEndpoint">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Endpoint Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. GitHub Webhooks" required>
        </div>

        <div class="form-group">
          <label class="form-label">
            <span>Custom Slug (Optional)</span>
            <span class="form-hint">Leave blank to auto-generate</span>
          </label>
          <input type="text" name="slug" class="form-control code-font" placeholder="e.g. github-ci">
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Response Status</label>
            <input type="number" name="response_status" class="form-control" value="200" min="100" max="599">
          </div>

          <div class="form-group">
            <label class="form-label">Simulated Delay (ms)</label>
            <input type="number" name="response_delay_ms" class="form-control" value="0" min="0" max="30000">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            <span>Dynamic Response Body</span>
            <span class="form-hint">Supports variable interpolation</span>
          </label>
          <div class="chips-row">
            <span class="chip-tag" data-target="epBodyInput" data-var="@{{req.id}}">+ req.id</span>
            <span class="chip-tag" data-target="epBodyInput" data-var="@{{timestamp_iso}}">+ timestamp</span>
            <span class="chip-tag" data-target="epBodyInput" data-var="@{{body.event}}">+ body.event</span>
          </div>
          <textarea id="epBodyInput" name="response_body" class="form-control code-font" style="margin-top:6px; min-height:120px;">{
  "status": "ok",
  "request_id": "@{{req.id}}",
  "received_at": "@{{timestamp_iso}}"
}</textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-modal-cancel">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Endpoint</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add Callback Rule -->
<div id="modalAddCallback" class="modal-backdrop">
  <div class="modal-card">
    <div class="modal-header">
      <h3 class="modal-title">Add Automated Callback Rule</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form id="formAddCallback">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Attached Endpoint</label>
          <select id="cbEndpointId" name="endpoint_id" class="form-control" required></select>
        </div>

        <div class="form-group">
          <label class="form-label">Rule Label</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Payment Microservice Relay" required>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">HTTP Method</label>
            <select name="http_method" class="form-control">
              <option value="POST">POST</option>
              <option value="PUT">PUT</option>
              <option value="PATCH">PATCH</option>
              <option value="GET">GET</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Delay (Seconds)</label>
            <input type="number" name="delay_seconds" class="form-control" value="0" min="0" max="300">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            <span>Target URL</span>
            <span class="form-hint">Static or dynamic e.g. <code>@{{body.callback_url}}</code></span>
          </label>
          <input type="text" name="target_url" class="form-control code-font" placeholder="https://api.myapp.com/webhook or @{{body.callback_url}}" required>
        </div>

        <div class="form-group">
          <label class="form-label">Payload Mode</label>
          <select name="payload_mode" class="form-control">
            <option value="template">Custom JSON Template</option>
            <option value="passthrough">Passthrough (Forward exact incoming payload)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Payload Template</label>
          <textarea name="payload_template" class="form-control code-font" style="min-height:110px;">{
  "event": "WEBHOOK_RELAY",
  "source_request_id": "@{{req.id}}",
  "processed_at": "@{{timestamp_iso}}",
  "data": @{{body}}
}</textarea>
        </div>

        <div class="form-group" style="background:var(--bg-canvas); padding:12px; border-radius:6px; border:1px solid var(--border-subtle);">
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <input type="checkbox" name="hmac_enabled">
            <span style="font-size:0.84rem; font-weight:600;">Sign Outbound Request with HMAC</span>
          </label>
          <div class="grid-2" style="margin-top:10px;">
            <input type="text" name="hmac_secret" class="form-control code-font" placeholder="HMAC Secret Key">
            <input type="text" name="hmac_header_name" class="form-control code-font" placeholder="Header Name" value="X-Signature-256">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-modal-cancel">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Callback Rule</button>
      </div>
    </form>
  </div>
</div>

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
