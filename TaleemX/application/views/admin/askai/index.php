<?php
/**
 * Ask AI — chatbot UI
 *
 * This view is intentionally self-contained: all styles use the shared
 * ss-* design tokens (see backend/dist/css/ss-theme.css), so it follows
 * the brand color picker, light/dark mode, and RTL automatically.
 *
 * Replace the mock adapter at the bottom (window.AskAIApi) with the real
 * API call once the backend route is ready. The swap point is marked with
 * "=== SWAP API HERE ===".
 */
?>
<style type="text/css">
    /* ---------------------------------------------------------------
     * Ask AI — page-local styles (scoped under .askai-page only).
     * Uses shared --ss-* tokens so the brand color + dark mode flow
     * through automatically. Do not add raw hex values here.
     * --------------------------------------------------------------- */
    .askai-page {
        --askai-sidebar-w: 280px;
        --askai-bubble-radius: 14px;
        --askai-avatar-size: 34px;
        --askai-gap: var(--ss-space-4);

        position: relative;
        display: flex;
        gap: var(--askai-gap);
        height: calc(100vh - 140px);
        min-height: 560px;
        padding: var(--ss-space-4);
        box-sizing: border-box;
    }
    .askai-page *, .askai-page *::before, .askai-page *::after { box-sizing: border-box; }

    /* ---------- Sidebar (conversations) ---------- */
    .askai-sidebar {
        flex: 0 0 var(--askai-sidebar-w);
        display: flex;
        flex-direction: column;
        background: var(--ss-surface-card);
        border: 1px solid var(--ss-border-color);
        border-radius: var(--ss-radius-lg);
        box-shadow: var(--ss-shadow-sm);
        overflow: hidden;
    }
    .askai-sidebar__head {
        padding: var(--ss-space-4);
        border-bottom: 1px solid var(--ss-border-color-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--ss-space-2);
    }
    .askai-sidebar__title {
        margin: 0;
        font-size: var(--ss-font-size-md);
        font-weight: var(--ss-font-weight-semibold);
        color: var(--ss-text-heading);
        display: flex;
        align-items: center;
        gap: var(--ss-space-2);
    }
    .askai-sidebar__title .askai-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--ss-color-success);
        box-shadow: 0 0 0 3px var(--ss-color-success-soft);
    }
    .askai-new-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        font-size: var(--ss-font-size-sm);
        font-weight: var(--ss-font-weight-medium);
        color: #fff;
        background: linear-gradient(135deg, var(--ss-color-brown, #442F24), #442F24);
        border: 1px solid var(--ss-color-brown, #442F24);
        border-radius: var(--ss-radius-md);
        cursor: pointer;
        transition: var(--ss-transition-fast);
    }
    .askai-new-btn:hover,
    .askai-new-btn:focus {
        color: #fff;
        background: linear-gradient(135deg, #3a271d, #24150f);
        border-color: #3a271d;
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--ss-color-brown-rgb, 68, 47, 36), 0.22);
    }
    .askai-new-btn i { font-size: 12px; }

    .askai-search {
        padding: var(--ss-space-3) var(--ss-space-4);
        border-bottom: 1px solid var(--ss-border-color-light);
    }
    .askai-search input {
        width: 100%;
        height: 34px;
        padding: 0 10px 0 32px;
        border: 1px solid var(--ss-border-color);
        border-radius: var(--ss-radius-md);
        background: var(--ss-surface-body);
        color: var(--ss-text-default);
        font-size: var(--ss-font-size-sm);
        transition: var(--ss-transition-fast);
    }
    .askai-search { position: relative; }
    .askai-search .fa-search {
        position: absolute;
        top: 50%;
        inset-inline-start: calc(var(--ss-space-4) + 10px);
        transform: translateY(-50%);
        color: var(--ss-text-muted);
        font-size: 12px;
        pointer-events: none;
    }
    .askai-search input:focus {
        outline: none;
        border-color: var(--ss-color-primary);
        box-shadow: var(--ss-shadow-focus);
    }

    .askai-convo-list {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: var(--ss-space-2);
        margin: 0;
        list-style: none;
    }
    .askai-convo-item {
        display: flex;
        align-items: center;
        gap: var(--ss-space-2);
        padding: 10px 12px;
        border-radius: var(--ss-radius-md);
        color: var(--ss-text-default);
        font-size: var(--ss-font-size-sm);
        cursor: pointer;
        transition: var(--ss-transition-fast);
        position: relative;
    }
    .askai-convo-item:hover { background: var(--ss-color-primary-soft); }
    .askai-convo-item.is-active {
        background: var(--ss-color-primary-soft);
        color: var(--ss-color-primary);
        font-weight: var(--ss-font-weight-medium);
    }
    .askai-convo-item .askai-convo-title {
        flex: 1 1 auto;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .askai-convo-item .askai-convo-del {
        opacity: 0;
        border: none;
        background: transparent;
        color: var(--ss-color-danger);
        cursor: pointer;
        padding: 2px 6px;
        border-radius: var(--ss-radius-sm);
        transition: var(--ss-transition-fast);
    }
    .askai-convo-item:hover .askai-convo-del { opacity: 1; }
    .askai-convo-item .askai-convo-del:hover { background: var(--ss-color-danger-soft); }

    .askai-convo-empty {
        padding: var(--ss-space-6) var(--ss-space-4);
        text-align: center;
        color: var(--ss-text-muted);
        font-size: var(--ss-font-size-sm);
    }

    .askai-sidebar__foot {
        padding: var(--ss-space-3) var(--ss-space-4);
        border-top: 1px solid var(--ss-border-color-light);
        font-size: var(--ss-font-size-xs);
        color: var(--ss-text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* ---------- Main chat panel ---------- */
    .askai-main {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        background: var(--ss-surface-card);
        border: 1px solid var(--ss-border-color);
        border-radius: var(--ss-radius-lg);
        box-shadow: var(--ss-shadow-sm);
        overflow: hidden;
        min-width: 0;
    }
    .askai-main__head {
        padding: var(--ss-space-3) var(--ss-space-5);
        border-bottom: 1px solid var(--ss-border-color-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--ss-space-3);
    }
    .askai-brand {
        display: flex;
        align-items: center;
        gap: var(--ss-space-3);
    }
    .askai-brand__logo {
        width: 38px; height: 38px;
        border-radius: 50%;
        display: grid; place-items: center;
        color: #fff;
        background: linear-gradient(135deg, var(--ss-color-brown, #442F24), #442F24);
        box-shadow: 0 4px 12px rgba(var(--ss-color-brown-rgb, 68, 47, 36), 0.40);
        font-size: 16px;
    }
    .askai-brand__meta h4 {
        margin: 0; font-size: var(--ss-font-size-lg);
        font-weight: var(--ss-font-weight-semibold);
        color: var(--ss-text-heading);
        line-height: 1.2;
    }
    .askai-brand__meta small {
        display: inline-flex; align-items: center; gap: 5px;
        color: var(--ss-text-muted);
        font-size: var(--ss-font-size-xs);
    }
    .askai-brand__meta small::before {
        content: ""; width: 6px; height: 6px; border-radius: 50%;
        background: var(--ss-color-success);
        box-shadow: 0 0 0 3px var(--ss-color-success-soft);
    }

    .askai-head-actions { display: flex; gap: 6px; }
    .askai-icon-btn {
        width: 34px; height: 34px;
        display: inline-grid; place-items: center;
        background: transparent;
        color: var(--ss-text-muted);
        border: 1px solid var(--ss-border-color-light);
        border-radius: var(--ss-radius-md);
        cursor: pointer;
        transition: var(--ss-transition-fast);
    }
    .askai-icon-btn:hover {
        color: var(--ss-color-primary);
        background: var(--ss-color-primary-soft);
        border-color: var(--ss-color-primary);
    }

    /* ---------- Messages ---------- */
    .askai-messages {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: var(--ss-space-5) var(--ss-space-5);
        display: flex;
        flex-direction: column;
        gap: var(--ss-space-4);
        background:
            radial-gradient(1200px 400px at 50% -200px, rgba(var(--ss-color-primary-rgb), 0.06), transparent 60%),
            var(--ss-surface-card);
    }
    .askai-msg { display: flex; gap: var(--ss-space-3); width: 100%; max-width: 100%; }
    .askai-msg--user { flex-direction: row-reverse; }
    .askai-msg__avatar {
        width: var(--askai-avatar-size);
        height: var(--askai-avatar-size);
        flex: 0 0 var(--askai-avatar-size);
        border-radius: 50%;
        display: grid; place-items: center;
        color: var(--ss-text-on-primary);
        font-size: 14px;
        font-weight: var(--ss-font-weight-semibold);
    }
    .askai-msg--ai .askai-msg__avatar {
        background: linear-gradient(135deg, var(--ss-color-brown, #442F24), #442F24);
    }
    .askai-msg--user .askai-msg__avatar {
        background: var(--ss-color-neutral-400);
    }

    .askai-msg__bubble {
        width: fit-content;
        max-width: min(680px, 78%);
        min-width: 64px;
        padding: 12px 14px;
        border-radius: var(--askai-bubble-radius);
        line-height: var(--ss-line-height-relaxed);
        font-size: var(--ss-font-size-md);
        color: var(--ss-text-default);
        position: relative;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    .askai-msg--ai .askai-msg__bubble {
        background: var(--ss-surface-body);
        border: 1px solid var(--ss-border-color-light);
        border-top-inline-start-radius: 4px;
    }
    .askai-msg--ai .askai-msg__bubble.is-error {
        background: var(--ss-color-danger-soft);
        border-color: var(--ss-color-danger);
        color: var(--ss-color-danger);
    }
    .askai-msg--user .askai-msg__bubble {
        background: var(--ss-color-primary);
        color: var(--ss-text-on-primary);
        border-top-inline-end-radius: 4px;
    }
    .askai-msg__bubble p { margin: 0 0 8px; }
    .askai-msg__bubble p:last-child { margin-bottom: 0; }
    .askai-msg__bubble pre {
        background: rgba(0,0,0,0.55);
        color: #eaeaea;
        padding: 10px 12px;
        border-radius: var(--ss-radius-md);
        overflow-x: auto;
        font-size: 12.5px;
        margin: 8px 0;
    }
    .askai-msg__bubble code {
        background: rgba(0,0,0,0.08);
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 0.92em;
    }
    .askai-msg--user .askai-msg__bubble code {
        background: rgba(255,255,255,0.22);
    }
    .askai-msg__meta {
        margin-top: 4px;
        font-size: var(--ss-font-size-xs);
        color: var(--ss-text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .askai-msg--user .askai-msg__meta { justify-content: flex-end; }

    .askai-msg__actions {
        margin-top: 6px;
        display: flex;
        gap: 4px;
    }
    .askai-msg__actions button {
        border: none;
        background: transparent;
        color: var(--ss-text-muted);
        padding: 4px 6px;
        border-radius: var(--ss-radius-sm);
        cursor: pointer;
        font-size: 12px;
        transition: var(--ss-transition-fast);
    }
    .askai-msg__actions button:hover {
        color: var(--ss-color-primary);
        background: var(--ss-color-primary-soft);
    }
    .askai-msg__actions button.is-liked { color: var(--ss-color-success); }
    .askai-msg__actions button.is-disliked { color: var(--ss-color-danger); }

    /* Typing indicator */
    .askai-typing {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 2px;
    }
    .askai-typing span {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: var(--ss-color-primary);
        opacity: 0.5;
        animation: askaiBlink 1.2s infinite ease-in-out;
    }
    .askai-typing span:nth-child(2) { animation-delay: 0.18s; }
    .askai-typing span:nth-child(3) { animation-delay: 0.36s; }
    @keyframes askaiBlink {
        0%, 80%, 100% { transform: scale(0.7); opacity: 0.35; }
        40% { transform: scale(1); opacity: 1; }
    }

    /* ---------- Welcome / empty state ---------- */
    .askai-welcome {
        margin: auto;
        max-width: 720px;
        width: 100%;
        text-align: center;
        padding: var(--ss-space-6) var(--ss-space-4);
    }
    .askai-welcome__logo {
        width: 64px; height: 64px;
        margin: 0 auto var(--ss-space-4);
        border-radius: 50%;
        display: grid; place-items: center;
        color: #fff;
        background: linear-gradient(135deg, var(--ss-color-brown, #442F24), #442F24);
        font-size: 26px;
        box-shadow: 0 10px 30px rgba(var(--ss-color-brown-rgb, 68, 47, 36), 0.40);
    }
    .askai-welcome h2 {
        margin: 0 0 6px;
        font-size: var(--ss-font-size-2xl);
        font-weight: var(--ss-font-weight-semibold);
        color: var(--ss-text-heading);
    }
    .askai-welcome p {
        margin: 0 auto var(--ss-space-5);
        color: var(--ss-text-muted);
        max-width: 520px;
    }
    .askai-suggestions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--ss-space-3);
        text-align: start;
    }
    .askai-suggestion {
        display: flex;
        gap: 10px;
        padding: 14px 16px;
        border: 1px solid var(--ss-border-color-light);
        border-radius: var(--ss-radius-md);
        background: var(--ss-surface-body);
        color: var(--ss-text-default);
        cursor: pointer;
        transition: var(--ss-transition-fast);
        text-align: start;
    }
    .askai-suggestion:hover {
        border-color: var(--ss-color-primary);
        background: var(--ss-color-primary-soft);
        transform: translateY(-1px);
        box-shadow: var(--ss-shadow-sm);
    }
    .askai-suggestion__icon {
        flex: 0 0 32px;
        width: 32px; height: 32px;
        border-radius: var(--ss-radius-md);
        background: var(--ss-color-primary-soft);
        color: var(--ss-color-primary);
        display: grid; place-items: center;
        font-size: 14px;
    }
    .askai-suggestion__text strong {
        display: block;
        font-weight: var(--ss-font-weight-semibold);
        margin-bottom: 2px;
        color: var(--ss-text-heading);
    }
    .askai-suggestion__text span {
        font-size: var(--ss-font-size-sm);
        color: var(--ss-text-muted);
    }

    /* ---------- Composer ---------- */
    .askai-composer {
        padding: var(--ss-space-3) var(--ss-space-5) var(--ss-space-4);
        border-top: 1px solid var(--ss-border-color-light);
        background: var(--ss-surface-card);
    }
    .askai-composer__wrap {
        display: flex;
        align-items: flex-end;
        gap: var(--ss-space-2);
        padding: 8px 8px 8px 12px;
        border: 1px solid var(--ss-border-color);
        border-radius: var(--ss-radius-xl);
        background: var(--ss-surface-body);
        transition: var(--ss-transition-fast);
    }
    .askai-composer__wrap:focus-within {
        border-color: var(--ss-color-primary);
        box-shadow: var(--ss-shadow-focus);
    }
    .askai-composer textarea {
        flex: 1 1 auto;
        border: none;
        resize: none;
        outline: none;
        background: transparent;
        color: var(--ss-text-default);
        font-family: var(--ss-font-family);
        font-size: var(--ss-font-size-md);
        line-height: var(--ss-line-height-normal);
        padding: 6px 4px;
        max-height: 180px;
        min-height: 24px;
    }
    .askai-composer textarea::placeholder { color: var(--ss-text-muted); }
    .askai-composer__actions { display: flex; gap: 4px; align-items: center; }
    .askai-attach-btn {
        width: 36px; height: 36px;
        display: inline-grid; place-items: center;
        border: none;
        background: transparent;
        color: var(--ss-text-muted);
        border-radius: 50%;
        cursor: pointer;
        transition: var(--ss-transition-fast);
    }
    .askai-attach-btn:hover { color: var(--ss-color-primary); background: var(--ss-color-primary-soft); }
    .askai-send-btn {
        width: 38px; height: 38px;
        display: inline-grid; place-items: center;
        border: none;
        background: var(--ss-color-primary);
        color: var(--ss-text-on-primary);
        border-radius: 50%;
        cursor: pointer;
        transition: var(--ss-transition-fast);
    }
    .askai-send-btn[disabled] {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .askai-send-btn:not([disabled]):hover {
        background: var(--ss-color-primary-hover);
        transform: translateY(-1px);
    }

    .askai-composer__hint {
        margin-top: 6px;
        font-size: var(--ss-font-size-xs);
        color: var(--ss-text-muted);
        display: flex;
        justify-content: space-between;
    }
    .askai-kbd {
        display: inline-block;
        padding: 1px 6px;
        border: 1px solid var(--ss-border-color);
        border-bottom-width: 2px;
        border-radius: 4px;
        background: var(--ss-surface-body);
        font-size: 11px;
        font-family: var(--ss-font-family);
        color: var(--ss-text-muted);
    }

    /* ---------- Dark mode tweaks (only where bs vars don't cover) ---------- */
    body.dark .askai-msg__bubble pre { background: #111; }
    body.dark .askai-msg--ai .askai-msg__bubble {
        background: var(--ss-color-neutral-100);
        border-color: var(--ss-color-neutral-200);
    }
    body.dark .askai-suggestion { background: var(--ss-color-neutral-100); }

    /* ---------- Responsive ---------- */
    @media (max-width: 991px) {
        .askai-suggestions { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .askai-page {
            height: calc(100vh - 120px);
            flex-direction: column;
            padding: var(--ss-space-2);
        }
        .askai-sidebar {
            flex: 0 0 auto;
            max-height: 220px;
        }
        .askai-msg__bubble { max-width: 86%; }
    }
</style>

<div class="content-wrapper">
    <section class="content ss-box-border">
        <div class="askai-page" id="askaiPage">

            <!-- ========== SIDEBAR ========== -->
            <aside class="askai-sidebar">
                <div class="askai-sidebar__head">
                    <h5 class="askai-sidebar__title">
                        <span class="askai-dot" aria-hidden="true"></span>
                        <?php echo isset($this->lang) ? 'Ask AI' : 'Ask AI'; ?>
                    </h5>
                    <button type="button" class="askai-new-btn" id="askaiNewChat" title="New chat">
                        <i class="fa fa-plus"></i> New
                    </button>
                </div>

                <div class="askai-search">
                    <i class="fa fa-search"></i>
                    <input type="text" id="askaiSearch" placeholder="Search conversations...">
                </div>

                <ul class="askai-convo-list" id="askaiConvoList">
                    <li class="askai-convo-empty" id="askaiConvoEmpty">
                        No conversations yet.<br>Start a new chat to begin.
                    </li>
                </ul>

                <div class="askai-sidebar__foot">
                    <i class="fa fa-shield"></i>
                    <span>Conversations saved locally on this device</span>
                </div>
            </aside>

            <!-- ========== MAIN CHAT ========== -->
            <section class="askai-main">
                <header class="askai-main__head">
                    <div class="askai-brand">
                        <div class="askai-brand__logo" aria-hidden="true">
                            <i class="fa fa-magic"></i>
                        </div>
                        <div class="askai-brand__meta">
                            <h4>School Assistant</h4>
                            <small>Online &middot; powered by AI</small>
                        </div>
                    </div>
                    <div class="askai-head-actions">
                        <button type="button" class="askai-icon-btn" id="askaiExport" title="Export conversation">
                            <i class="fa fa-download"></i>
                        </button>
                        <button type="button" class="askai-icon-btn" id="askaiClear" title="Clear conversation">
                            <i class="fa fa-trash-o"></i>
                        </button>
                    </div>
                </header>

                <div class="askai-messages" id="askaiMessages">
                    <!-- Welcome / suggestions are rendered by JS on fresh chats -->
                </div>

                <footer class="askai-composer">
                    <div class="askai-composer__wrap">
                        <button type="button" class="askai-attach-btn" id="askaiAttach" title="Attach (coming soon)">
                            <i class="fa fa-paperclip"></i>
                        </button>
                        <textarea id="askaiInput" rows="1" placeholder="Ask me anything about your school..." maxlength="4000"></textarea>
                        <div class="askai-composer__actions">
                            <button type="button" class="askai-send-btn" id="askaiSend" disabled title="Send">
                                <i class="fa fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="askai-composer__hint">
                        <span><span class="askai-kbd">Enter</span> to send &middot; <span class="askai-kbd">Shift + Enter</span> for new line</span>
                        <span><span id="askaiCharCount">0</span> / 4000</span>
                    </div>
                </footer>
            </section>

        </div>
    </section>
</div>

<script>
(function () {
    'use strict';

    // =========================================================================
    // Same-origin API endpoint. Backend proxy will call the real AI service.
    // =========================================================================
    const ASKAI_URL = (typeof baseurl !== 'undefined' ? baseurl : '/') + 'admin/askai/ask';

    window.AskAIApi = {
        async sendMessage(text) {
            const res  = await fetch(ASKAI_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question: text })
            });
            const data = await res.json();
            if (res.ok) return data.answer;
            throw new Error(data.error || ('Request failed (HTTP ' + res.status + ').'));
        }
    };

    // =========================================================================
    // State + storage
    // =========================================================================
    const STORAGE_KEY = 'askai.conversations.v1';
    const ACTIVE_KEY  = 'askai.activeId.v1';

    /** @type {{id:string,title:string,updatedAt:number,messages:Array<{role:'user'|'ai',text:string,ts:number,reaction?:string}>}[]} */
    let conversations = loadConversations();
    let activeId = localStorage.getItem(ACTIVE_KEY) || null;
    if (activeId && !conversations.find(c => c.id === activeId)) activeId = null;

    // =========================================================================
    // DOM refs
    // =========================================================================
    const els = {
        list:      document.getElementById('askaiConvoList'),
        empty:     document.getElementById('askaiConvoEmpty'),
        search:    document.getElementById('askaiSearch'),
        newBtn:    document.getElementById('askaiNewChat'),
        messages:  document.getElementById('askaiMessages'),
        input:     document.getElementById('askaiInput'),
        send:      document.getElementById('askaiSend'),
        attach:    document.getElementById('askaiAttach'),
        charCount: document.getElementById('askaiCharCount'),
        clearBtn:  document.getElementById('askaiClear'),
        exportBtn: document.getElementById('askaiExport'),
    };

    // =========================================================================
    // Render
    // =========================================================================
    function renderSidebar(filter) {
        const q = (filter || '').trim().toLowerCase();
        const items = conversations
            .slice()
            .sort((a, b) => b.updatedAt - a.updatedAt)
            .filter(c => !q || c.title.toLowerCase().includes(q));

        els.list.innerHTML = '';
        if (!items.length) {
            const li = document.createElement('li');
            li.className = 'askai-convo-empty';
            li.innerHTML = q
                ? 'No conversations match your search.'
                : 'No conversations yet.<br>Start a new chat to begin.';
            els.list.appendChild(li);
            return;
        }

        items.forEach(c => {
            const li = document.createElement('li');
            li.className = 'askai-convo-item' + (c.id === activeId ? ' is-active' : '');
            li.dataset.id = c.id;
            li.innerHTML =
                '<i class="fa fa-comments-o"></i>' +
                '<span class="askai-convo-title">' + escapeHTML(c.title || 'Untitled') + '</span>' +
                '<button type="button" class="askai-convo-del" title="Delete" data-del="' + c.id + '">' +
                '<i class="fa fa-times"></i></button>';
            els.list.appendChild(li);
        });
    }

    function renderMessages() {
        els.messages.innerHTML = '';
        const convo = getActive();
        if (!convo || !convo.messages.length) {
            renderWelcome();
            return;
        }
        convo.messages.forEach(m => els.messages.appendChild(messageNode(m)));
        scrollToBottom();
    }

    function renderWelcome() {
        const suggestions = [
            { icon: 'fa-graduation-cap', title: 'How to create an exam?',        sub: 'Show me the steps to create an exam' },
            { icon: 'fa-users',           title: 'Give me list of students of grade 1.', sub: 'Show all students in grade 1' }
        ];

        const wrap = document.createElement('div');
        wrap.className = 'askai-welcome';
        wrap.innerHTML =
            '<div class="askai-welcome__logo"><i class="fa fa-magic"></i></div>' +
            '<h2>How can I help you today?</h2>' +
            '<p>Ask about students, reports, attendance, fees, or anything your smart school does. Pick a starter below or type your own question.</p>' +
            '<div class="askai-suggestions" id="askaiSuggestions"></div>';
        els.messages.appendChild(wrap);

        const grid = wrap.querySelector('#askaiSuggestions');
        suggestions.forEach(s => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'askai-suggestion';
            btn.innerHTML =
                '<span class="askai-suggestion__icon"><i class="fa ' + s.icon + '"></i></span>' +
                '<span class="askai-suggestion__text"><strong>' + escapeHTML(s.title) + '</strong>' +
                '<span>' + escapeHTML(s.sub) + '</span></span>';
            btn.addEventListener('click', () => {
                els.input.value = s.title + ' \u2014 ' + s.sub;
                els.input.dispatchEvent(new Event('input'));
                els.input.focus();
            });
            grid.appendChild(btn);
        });
    }

    function messageNode(m) {
        const row = document.createElement('div');
        row.className = 'askai-msg askai-msg--' + (m.role === 'user' ? 'user' : 'ai');

        const avatar = document.createElement('div');
        avatar.className = 'askai-msg__avatar';
        avatar.innerHTML = m.role === 'user'
            ? '<i class="fa fa-user"></i>'
            : '<i class="fa fa-magic"></i>';

        const wrap = document.createElement('div');
        wrap.style.minWidth = '0';
        wrap.style.flex = '1 1 auto';
        wrap.style.display = 'flex';
        wrap.style.flexDirection = 'column';
        wrap.style.alignItems = m.role === 'user' ? 'flex-end' : 'flex-start';

        const bubble = document.createElement('div');
        bubble.className = 'askai-msg__bubble' + (m.isError ? ' is-error' : '');
        bubble.innerHTML = m.role === 'user'
            ? escapeHTML(m.text).replace(/\n/g, '<br>')
            : renderMarkdown(m.text);
        wrap.appendChild(bubble);

        const meta = document.createElement('div');
        meta.className = 'askai-msg__meta';
        meta.innerHTML = '<span>' + formatTime(m.ts) + '</span>';
        wrap.appendChild(meta);

        if (m.role === 'ai') {
            const actions = document.createElement('div');
            actions.className = 'askai-msg__actions';
            actions.innerHTML =
                '<button type="button" data-act="copy" title="Copy"><i class="fa fa-copy"></i></button>' +
                '<button type="button" data-act="like" title="Helpful" class="' + (m.reaction === 'like' ? 'is-liked' : '') + '"><i class="fa fa-thumbs-o-up"></i></button>' +
                '<button type="button" data-act="dislike" title="Not helpful" class="' + (m.reaction === 'dislike' ? 'is-disliked' : '') + '"><i class="fa fa-thumbs-o-down"></i></button>' +
                '<button type="button" data-act="regen" title="Regenerate"><i class="fa fa-refresh"></i></button>';
            actions.addEventListener('click', (e) => {
                const btn = e.target.closest('button'); if (!btn) return;
                const act = btn.dataset.act;
                if (act === 'copy') {
                    navigator.clipboard && navigator.clipboard.writeText(m.text);
                    flash(btn, 'fa-check');
                } else if (act === 'like' || act === 'dislike') {
                    m.reaction = (m.reaction === act) ? null : act;
                    save();
                    renderMessages();
                } else if (act === 'regen') {
                    regenerate(m);
                }
            });
            wrap.appendChild(actions);
        }

        row.appendChild(avatar);
        row.appendChild(wrap);
        return row;
    }

    // =========================================================================
    // Interactions
    // =========================================================================
    function createConversation(firstMessage) {
        const convo = {
            id: 'c_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7),
            title: (firstMessage || 'New chat').slice(0, 40),
            updatedAt: Date.now(),
            messages: []
        };
        conversations.push(convo);
        activeId = convo.id;
        localStorage.setItem(ACTIVE_KEY, activeId);
        save();
        return convo;
    }

    function getActive() {
        if (!activeId) return null;
        return conversations.find(c => c.id === activeId) || null;
    }

    async function handleSend() {
        const text = els.input.value.trim();
        if (!text) return;

        let convo = getActive();
        if (!convo) convo = createConversation(text);
        if (convo.messages.length === 0) convo.title = text.slice(0, 40);

        convo.messages.push({ role: 'user', text: text, ts: Date.now() });
        convo.updatedAt = Date.now();
        save();
        renderSidebar(els.search.value);
        renderMessages();

        els.input.value = '';
        els.input.style.height = 'auto';
        updateSendState();

        showTyping();
        try {
            const history = convo.messages.map(m => ({ role: m.role, text: m.text }));
            const reply = await window.AskAIApi.sendMessage(text, convo.id, history);
            convo.messages.push({ role: 'ai', text: reply, ts: Date.now() });
            convo.updatedAt = Date.now();
            save();
        } catch (err) {
            const msg = (err && err.message) ? err.message : 'Something went wrong reaching the AI service.';
            convo.messages.push({
                role: 'ai',
                text: '\u26A0\uFE0F ' + msg,
                ts: Date.now(),
                isError: true
            });
            save();
            console.error(err);
        } finally {
            hideTyping();
            renderSidebar(els.search.value);
            renderMessages();
        }
    }

    async function regenerate(aiMsg) {
        const convo = getActive(); if (!convo) return;
        const idx = convo.messages.indexOf(aiMsg); if (idx === -1) return;
        const lastUser = convo.messages.slice(0, idx).reverse().find(m => m.role === 'user');
        if (!lastUser) return;

        convo.messages.splice(idx, 1);
        save();
        renderMessages();
        showTyping();
        try {
            const history = convo.messages.map(m => ({ role: m.role, text: m.text }));
            const reply = await window.AskAIApi.sendMessage(lastUser.text, convo.id, history);
            convo.messages.push({ role: 'ai', text: reply, ts: Date.now() });
            convo.updatedAt = Date.now();
            save();
        } catch (err) {
            const msg = (err && err.message) ? err.message : 'Something went wrong reaching the AI service.';
            convo.messages.push({
                role: 'ai',
                text: '\u26A0\uFE0F ' + msg,
                ts: Date.now(),
                isError: true
            });
            save();
        } finally {
            hideTyping();
            renderMessages();
            renderSidebar(els.search.value);
        }
    }

    function showTyping() {
        const row = document.createElement('div');
        row.className = 'askai-msg askai-msg--ai';
        row.id = 'askaiTyping';
        row.innerHTML =
            '<div class="askai-msg__avatar"><i class="fa fa-magic"></i></div>' +
            '<div class="askai-msg__bubble"><div class="askai-typing"><span></span><span></span><span></span></div></div>';
        els.messages.appendChild(row);
        scrollToBottom();
    }
    function hideTyping() {
        const el = document.getElementById('askaiTyping');
        if (el) el.remove();
    }

    function clearActive() {
        const convo = getActive(); if (!convo) return;
        if (!confirm('Clear this conversation?')) return;
        convo.messages = [];
        convo.updatedAt = Date.now();
        save();
        renderMessages();
        renderSidebar(els.search.value);
    }

    function deleteConversation(id) {
        if (!confirm('Delete this conversation?')) return;
        conversations = conversations.filter(c => c.id !== id);
        if (activeId === id) activeId = null;
        save();
        renderSidebar(els.search.value);
        renderMessages();
    }

    function exportActive() {
        const convo = getActive();
        if (!convo || !convo.messages.length) {
            alert('Nothing to export yet.');
            return;
        }
        const lines = ['# ' + convo.title, ''];
        convo.messages.forEach(m => {
            lines.push('**' + (m.role === 'user' ? 'You' : 'Assistant') + '** (' + formatTime(m.ts) + ')');
            lines.push('');
            lines.push(m.text);
            lines.push('');
            lines.push('---');
            lines.push('');
        });
        const blob = new Blob([lines.join('\n')], { type: 'text/markdown;charset=utf-8' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = (convo.title || 'chat').replace(/[^a-z0-9]+/gi, '_') + '.md';
        document.body.appendChild(a); a.click();
        setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 100);
    }

    // =========================================================================
    // Utils
    // =========================================================================
    function loadConversations() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return [];
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }
    function save() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(conversations));
        if (activeId) localStorage.setItem(ACTIVE_KEY, activeId);
        else          localStorage.removeItem(ACTIVE_KEY);
    }
    function escapeHTML(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    // Minimal markdown: **bold**, *italic*, `code`, ```blocks```, newlines.
    function renderMarkdown(s) {
        if (s == null) return '';
        let out = escapeHTML(s);
        out = out.replace(/```([\s\S]*?)```/g, (_, code) =>
            '<pre><code>' + code.replace(/^\n+|\n+$/g, '') + '</code></pre>');
        out = out.replace(/`([^`]+)`/g, '<code>$1</code>');
        out = out.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        out = out.replace(/(^|[\s(])\*([^*\s][^*]*?)\*(?=[\s.,!?)]|$)/g, '$1<em>$2</em>');
        out = out.split(/\n{2,}/).map(p => {
            if (/^<pre>/.test(p)) return p;
            return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
        }).join('');
        return out;
    }
    function formatTime(ts) {
        const d = new Date(ts || Date.now());
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        return hh + ':' + mm;
    }
    function scrollToBottom() {
        els.messages.scrollTop = els.messages.scrollHeight;
    }
    function updateSendState() {
        els.send.disabled = !els.input.value.trim();
        els.charCount.textContent = String(els.input.value.length);
    }
    function autosize() {
        els.input.style.height = 'auto';
        els.input.style.height = Math.min(els.input.scrollHeight, 180) + 'px';
    }
    function flash(btn, iconClass) {
        const i = btn.querySelector('i'); if (!i) return;
        const prev = i.className;
        i.className = 'fa ' + iconClass;
        setTimeout(() => { i.className = prev; }, 900);
    }

    // =========================================================================
    // Events
    // =========================================================================
    els.input.addEventListener('input', () => { autosize(); updateSendState(); });
    els.input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    });
    els.send.addEventListener('click', handleSend);
    els.newBtn.addEventListener('click', () => {
        activeId = null;
        localStorage.removeItem(ACTIVE_KEY);
        renderSidebar(els.search.value);
        renderMessages();
        els.input.focus();
    });
    els.search.addEventListener('input', () => renderSidebar(els.search.value));

    els.list.addEventListener('click', (e) => {
        const delBtn = e.target.closest('[data-del]');
        if (delBtn) {
            e.stopPropagation();
            deleteConversation(delBtn.dataset.del);
            return;
        }
        const item = e.target.closest('.askai-convo-item');
        if (!item) return;
        activeId = item.dataset.id;
        save();
        renderSidebar(els.search.value);
        renderMessages();
    });

    els.clearBtn.addEventListener('click', clearActive);
    els.exportBtn.addEventListener('click', exportActive);
    els.attach.addEventListener('click', () => {
        alert('File attachments will be available once the API is connected.');
    });

    // =========================================================================
    // Boot
    // =========================================================================
    renderSidebar('');
    renderMessages();
    updateSendState();
    setTimeout(() => els.input.focus(), 100);
})();
</script>
