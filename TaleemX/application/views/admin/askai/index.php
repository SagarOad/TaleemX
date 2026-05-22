<?php
/**
 * Ask AI — chatbot UI
 *
 * Renders the rich AI replies (KPIs, tables, charts, cards) plus follow-up
 * suggestion chips and source badges. All styles use the shared ss-* design
 * tokens so brand color / dark mode / RTL flow through automatically.
 */
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
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
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .askai-arabic-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
        margin: 0;
        font-weight: var(--ss-font-weight-medium);
        color: var(--ss-text-default);
    }
    .askai-arabic-toggle input {
        width: 16px;
        height: 16px;
        accent-color: var(--ss-color-primary);
        cursor: pointer;
        margin: 0;
    }
    .askai-arabic-toggle__ar {
        font-size: var(--ss-font-size-sm);
        line-height: 1;
    }
    .askai-arabic-toggle__sub {
        font-size: var(--ss-font-size-xs);
        color: var(--ss-text-muted);
        font-weight: var(--ss-font-weight-regular);
    }
    .askai-msg__bubble[dir="rtl"] {
        text-align: right;
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

    /* ---------- Rich result blocks (KPI / cards / table / chart) ---------- */
    .askai-result {
        margin-top: 10px;
        width: 100%;
        max-width: min(720px, 92%);
        background: var(--ss-surface-card);
        border: 1px solid var(--ss-border-color-light);
        border-radius: var(--ss-radius-md);
        overflow: hidden;
        box-shadow: var(--ss-shadow-sm);
    }
    .askai-result__head {
        padding: 8px 14px;
        font-size: var(--ss-font-size-xs);
        font-weight: var(--ss-font-weight-medium);
        color: var(--ss-text-muted);
        background: var(--ss-color-primary-soft);
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 1px solid var(--ss-border-color-light);
    }
    .askai-result__head i { color: var(--ss-color-primary); }
    .askai-result__body { padding: 14px; }

    .askai-kpi {
        display: flex;
        align-items: baseline;
        gap: 14px;
        padding: 8px 4px;
    }
    .askai-kpi__value {
        font-size: 38px;
        line-height: 1;
        font-weight: var(--ss-font-weight-bold, 700);
        color: var(--ss-color-primary);
    }
    .askai-kpi__label {
        font-size: var(--ss-font-size-sm);
        color: var(--ss-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .askai-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
    }
    .askai-card {
        background: var(--ss-surface-body);
        border: 1px solid var(--ss-border-color-light);
        border-radius: var(--ss-radius-md);
        padding: 10px 12px;
        font-size: var(--ss-font-size-sm);
    }
    .askai-card__row { display: flex; gap: 8px; margin-bottom: 4px; }
    .askai-card__row:last-child { margin-bottom: 0; }
    .askai-card__key {
        flex: 0 0 38%;
        color: var(--ss-text-muted);
        font-size: var(--ss-font-size-xs);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .askai-card__val {
        flex: 1 1 auto;
        color: var(--ss-text-default);
        font-weight: var(--ss-font-weight-medium);
        word-break: break-word;
    }

    .askai-table-wrap {
        overflow-x: auto;
        margin: 0;
    }
    .askai-table {
        width: 100%;
        border-collapse: collapse;
        font-size: var(--ss-font-size-sm);
    }
    .askai-table th,
    .askai-table td {
        text-align: start;
        padding: 8px 10px;
        border-bottom: 1px solid var(--ss-border-color-light);
        white-space: nowrap;
    }
    .askai-table th {
        background: var(--ss-color-primary-soft);
        color: var(--ss-color-primary);
        font-weight: var(--ss-font-weight-semibold);
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.4px;
    }
    .askai-table tr:last-child td { border-bottom: none; }
    .askai-table tr:hover td { background: var(--ss-color-primary-soft); }

    .askai-chart {
        position: relative;
        width: 100%;
        height: 280px;
    }

    .askai-result__foot {
        padding: 6px 14px 10px;
        font-size: var(--ss-font-size-xs);
        color: var(--ss-text-muted);
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid var(--ss-border-color-light);
    }
    .askai-result__foot button {
        border: 1px solid var(--ss-border-color);
        background: var(--ss-surface-body);
        color: var(--ss-text-muted);
        padding: 3px 10px;
        border-radius: var(--ss-radius-pill, 12px);
        font-size: 11px;
        cursor: pointer;
        transition: var(--ss-transition-fast);
    }
    .askai-result__foot button:hover {
        color: var(--ss-color-primary);
        border-color: var(--ss-color-primary);
        background: var(--ss-color-primary-soft);
    }

    /* ---------- Source badge ---------- */
    .askai-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10.5px;
        font-weight: var(--ss-font-weight-medium);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        background: var(--ss-color-primary-soft);
        color: var(--ss-color-primary);
    }
    .askai-badge--learned { background: #d4f3e0; color: #1a7a3d; }
    .askai-badge--curated { background: #cce5ff; color: #0b5ed7; }
    .askai-badge--llm     { background: #fff3cd; color: #946100; }
    .askai-badge--manual  { background: #e8e0ff; color: #4f2da3; }
    .askai-badge--deterministic { background: #e2e3e5; color: #41464b; }

    /* ---------- Follow-up suggestion chips ---------- */
    .askai-followups {
        margin-top: 10px;
        width: 100%;
        max-width: min(720px, 92%);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .askai-followups__label {
        font-size: var(--ss-font-size-xs);
        color: var(--ss-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .askai-followups__list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .askai-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: var(--ss-surface-body);
        border: 1px solid var(--ss-border-color);
        border-radius: var(--ss-radius-pill, 18px);
        color: var(--ss-text-default);
        font-size: var(--ss-font-size-sm);
        cursor: pointer;
        transition: var(--ss-transition-fast);
        text-align: start;
        line-height: 1.3;
    }
    .askai-chip:hover {
        border-color: var(--ss-color-primary);
        background: var(--ss-color-primary-soft);
        color: var(--ss-color-primary);
        transform: translateY(-1px);
    }
    .askai-chip i { color: var(--ss-color-primary); font-size: 11px; }

    /* ---------- Executive briefing dashboard ---------- */
    .askai-exec { display: flex; flex-direction: column; gap: 16px; width: 100%; }
    .askai-exec-health {
        display: flex; flex-wrap: wrap; align-items: center; gap: 16px;
        padding: 14px 16px; border-radius: var(--ss-radius-md, 8px);
        background: linear-gradient(135deg, var(--ss-color-primary-soft) 0%, var(--ss-surface-body) 100%);
        border: 1px solid var(--ss-border-color);
    }
    .askai-exec-health__score {
        font-size: 2.2rem; font-weight: 700; line-height: 1;
        color: var(--ss-color-primary); min-width: 72px;
    }
    .askai-exec-health__score.is-strong { color: #1a7a3d; }
    .askai-exec-health__score.is-watch { color: #946100; }
    .askai-exec-health__score.is-critical { color: #b42318; }
    .askai-exec-health__meta { flex: 1; min-width: 200px; }
    .askai-exec-health__rating { font-weight: 600; font-size: 1.05rem; margin-bottom: 4px; }
    .askai-exec-health__summary { font-size: var(--ss-font-size-sm); color: var(--ss-text-muted); line-height: 1.45; }
    .askai-exec-kpis {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px;
    }
    .askai-exec-kpi {
        padding: 10px 12px; border-radius: 8px; border: 1px solid var(--ss-border-color);
        background: var(--ss-surface-body);
    }
    .askai-exec-kpi.is-strong { border-color: #86d9a8; background: #f0faf4; }
    .askai-exec-kpi.is-watch { border-color: #f5d88a; background: #fffbeb; }
    .askai-exec-kpi.is-critical { border-color: #f5b4b0; background: #fef3f2; }
    .askai-exec-kpi__val { font-size: 1.15rem; font-weight: 600; display: block; }
    .askai-exec-kpi__lbl { font-size: 11px; color: var(--ss-text-muted); line-height: 1.3; }
    .askai-exec-section__title {
        font-size: var(--ss-font-size-sm); font-weight: 600;
        margin: 0 0 8px; color: var(--ss-text-default);
        display: flex; align-items: center; gap: 6px;
    }
    .askai-exec-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 768px) { .askai-exec-charts { grid-template-columns: 1fr; } }
    .askai-exec-chart-box {
        padding: 10px; border: 1px solid var(--ss-border-color);
        border-radius: 8px; background: var(--ss-surface-body);
    }
    .askai-exec-chart-box canvas { max-height: 220px; }
    .askai-exec-lists { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 900px) { .askai-exec-lists { grid-template-columns: 1fr; } }

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
        .askai-result { max-width: 100%; }
        .askai-followups { max-width: 100%; }
        .askai-kpi__value { font-size: 30px; }
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
                        <span>
                            <span class="askai-kbd">Enter</span> to send &middot;
                            <span class="askai-kbd">Shift + Enter</span> for new line
                        </span>
                        <label class="askai-arabic-toggle" for="askaiArabic" title="Translate the assistant reply into Modern Standard Arabic">
                            <input type="checkbox" id="askaiArabic" />
                            <span class="askai-arabic-toggle__ar" lang="ar">العربية</span>
                            <span class="askai-arabic-toggle__sub">Arabic reply</span>
                        </label>
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
    const BASE_URL     = (typeof baseurl !== 'undefined' ? baseurl : '/');
    const ASKAI_URL    = BASE_URL + 'admin/askai/ask';
    const FEEDBACK_URL = BASE_URL + 'admin/askai/feedback';

    window.AskAIApi = {
        async sendMessage(text, respondArabic) {
            const res  = await fetch(ASKAI_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question: text,
                    respond_arabic: !!respondArabic
                })
            });
            const data = await res.json();
            if (res.ok) {
                return {
                    answer:         data.answer || '',
                    request_id:     data.request_id || '',
                    source:         data.source || '',
                    status:         data.status || 'ok',
                    sql:            data.sql || '',
                    presentation:   data.presentation || 'text',
                    structured_data: data.structured_data || null,
                    suggestions:    Array.isArray(data.suggestions) ? data.suggestions : [],
                    intent:         data.intent || 'general',
                    module:         data.module || 'general',
                    module_label:   data.module_label || ''
                };
            }
            throw new Error(data.error || ('Request failed (HTTP ' + res.status + ').'));
        },
        async sendFeedback(requestId, verdict, note) {
            if (!requestId) return { ok: false, reason: 'no request_id' };
            const res = await fetch(FEEDBACK_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    request_id: requestId,
                    verdict:    verdict,
                    note:       note || ''
                })
            });
            try { return await res.json(); } catch (e) { return { ok: false }; }
        }
    };

    // =========================================================================
    // State + storage
    // =========================================================================
    const STORAGE_KEY = 'askai.conversations.v1';
    const ACTIVE_KEY  = 'askai.activeId.v1';
    const ARABIC_KEY  = 'askai.respondArabic.v1';

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
        arabic:    document.getElementById('askaiArabic'),
    };

    if (els.arabic) {
        els.arabic.checked = localStorage.getItem(ARABIC_KEY) === '1';
        els.arabic.addEventListener('change', () => {
            localStorage.setItem(ARABIC_KEY, els.arabic.checked ? '1' : '0');
        });
    }

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
            { icon: 'fa-line-chart',  title: 'How is our school performing overall?',    sub: 'Executive briefing: score, charts, risk, and priorities' },
            { icon: 'fa-exclamation-triangle', title: 'Give me risk analysis of the school', sub: 'Risk areas, severity, and recommended actions' },
            { icon: 'fa-wrench',      title: 'What can we improve at our school?',       sub: 'Gaps ranked by issue count with priority notes' },
            { icon: 'fa-sitemap',     title: 'What departments do we have?',             sub: 'HR department list and staff headcount' },
            { icon: 'fa-money',       title: 'What is our profit this month?',           sub: 'Income minus expenses and payroll' },
            { icon: 'fa-calendar',    title: 'Show attendance summary per student this month', sub: 'Present, absent, late days and attendance %' },
            { icon: 'fa-question-circle', title: 'What is this platform and how does it work?', sub: 'Tour of features in plain English' }
        ];

        const wrap = document.createElement('div');
        wrap.className = 'askai-welcome';
        wrap.innerHTML =
            '<div class="askai-welcome__logo"><i class="fa fa-magic"></i></div>' +
            '<h2>How can I help you today?</h2>' +
            '<p>Ask anything about your school — performance, risk, profit, growth, students, fees, attendance, staff, admissions, exams, behaviour, expenses. I answer with charts, tables, and quick stats where it helps.</p>' +
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
                els.input.value = s.title;
                els.input.dispatchEvent(new Event('input'));
                handleSend();
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
        if (m.role === 'ai' && m.respond_arabic) {
            bubble.setAttribute('dir', 'rtl');
            bubble.setAttribute('lang', 'ar');
        }
        bubble.innerHTML = m.role === 'user'
            ? escapeHTML(m.text).replace(/\n/g, '<br>')
            : renderMarkdown(m.text);
        wrap.appendChild(bubble);

        // Rich result block (KPI / cards / table / chart) — AI messages only.
        if (m.role === 'ai' && m.structured_data) {
            const resultBlock = buildResultBlock(m);
            if (resultBlock) wrap.appendChild(resultBlock);
        }

        // Follow-up suggestion chips.
        if (m.role === 'ai' && Array.isArray(m.suggestions) && m.suggestions.length) {
            wrap.appendChild(buildFollowupBlock(m.suggestions, m.respond_arabic));
        }

        const meta = document.createElement('div');
        meta.className = 'askai-msg__meta';
        const timeSpan = '<span>' + formatTime(m.ts) + '</span>';
        const badgeHTML = (m.role === 'ai' && !m.isError) ? sourceBadge(m) : '';
        meta.innerHTML = timeSpan + badgeHTML;
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
                    const previous = m.reaction;
                    m.reaction = (m.reaction === act) ? null : act;
                    save();
                    renderMessages();
                    if (m.reaction === 'like' && previous !== 'like') {
                        sendFeedback(m, 'good');
                    } else if (m.reaction === 'dislike' && previous !== 'dislike') {
                        sendFeedback(m, 'bad');
                    }
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
    // Rich result rendering
    // =========================================================================
    function sourceBadge(m) {
        const src = (m.source || '').toLowerCase();
        if (!src) return '';
        const map = {
            'curated_trusted':  ['curated', 'curated', 'Verified example'],
            'learned_trusted':  ['learned', 'learned', 'From past 👍 feedback'],
            'llm':              ['llm',     'llm',     'AI-generated'],
            'manual':           ['manual',  'manual',  'From product manual'],
            'deterministic':    ['deterministic', 'deterministic', 'Built-in shortcut'],
            'executive_briefing': ['executive', 'curated', 'Executive school briefing']
        };
        const conf = map[src] || ['llm', 'llm', src];
        return '<span class="askai-badge askai-badge--' + conf[1] + '" title="' + escapeHTML(conf[2]) + '">'
             + '<i class="fa fa-shield"></i> ' + escapeHTML(conf[0]) + '</span>';
    }

    function buildResultBlock(m) {
        const sd = m.structured_data;
        if (!sd || !sd.kind) return null;

        const wrapper = document.createElement('div');
        wrapper.className = 'askai-result';
        if (m.respond_arabic) {
            wrapper.setAttribute('dir', 'rtl');
            wrapper.setAttribute('lang', 'ar');
        }

        const head = document.createElement('div');
        head.className = 'askai-result__head';
        const moduleLabel = m.module_label || '';
        const iconClass = presentationIcon(sd.kind);
        const headLabel = humanPresentationLabel(sd.kind)
            + (moduleLabel ? ' · ' + moduleLabel : '');
        head.innerHTML = '<i class="fa ' + iconClass + '"></i>'
            + '<span>' + escapeHTML(headLabel) + '</span>';
        wrapper.appendChild(head);

        const body = document.createElement('div');
        body.className = 'askai-result__body';

        if (sd.kind === 'executive_briefing') {
            body.appendChild(renderExecutiveBriefing(sd, m.respond_arabic));
            wrapper.appendChild(body);
            return wrapper;
        }

        if (sd.kind === 'kpi') {
            body.appendChild(renderKpi(sd));
        } else if (sd.kind === 'cards') {
            body.appendChild(renderCards(sd));
        } else if (sd.kind === 'table') {
            body.appendChild(renderTable(sd));
        } else if (sd.kind === 'bar_chart' || sd.kind === 'line_chart' || sd.kind === 'pie_chart') {
            const canvasWrap = document.createElement('div');
            canvasWrap.className = 'askai-chart';
            const canvas = document.createElement('canvas');
            canvasWrap.appendChild(canvas);
            body.appendChild(canvasWrap);
            // Defer until canvas is attached + Chart.js is loaded.
            scheduleChartRender(canvas, sd);
        }

        wrapper.appendChild(body);

        // Footer: shown-count and a Copy CSV button for tabular outputs.
        if (sd.kind === 'cards' || sd.kind === 'table') {
            const foot = document.createElement('div');
            foot.className = 'askai-result__foot';
            const total = sd.row_count || sd.shown_count || 0;
            const shown = sd.shown_count || 0;
            const countTxt = total > shown
                ? 'Showing ' + shown + ' of ' + total + ' rows'
                : (total + ' row' + (total === 1 ? '' : 's'));
            foot.innerHTML = '<span>' + escapeHTML(countTxt) + '</span>'
                + '<button type="button" data-act="copy-csv"><i class="fa fa-copy"></i> Copy as CSV</button>';
            foot.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-act="copy-csv"]');
                if (!btn) return;
                const csv = tableToCsv(sd.columns || [], sd.rows || []);
                navigator.clipboard && navigator.clipboard.writeText(csv);
                btn.innerHTML = '<i class="fa fa-check"></i> Copied';
                setTimeout(() => { btn.innerHTML = '<i class="fa fa-copy"></i> Copy as CSV'; }, 1500);
            });
            wrapper.appendChild(foot);
        }
        return wrapper;
    }

    function presentationIcon(kind) {
        switch (kind) {
            case 'kpi':        return 'fa-tachometer';
            case 'cards':      return 'fa-th-large';
            case 'table':      return 'fa-table';
            case 'bar_chart':  return 'fa-bar-chart';
            case 'line_chart': return 'fa-line-chart';
            case 'pie_chart':  return 'fa-pie-chart';
            case 'executive_briefing': return 'fa-line-chart';
            default:           return 'fa-database';
        }
    }
    function humanPresentationLabel(kind) {
        switch (kind) {
            case 'executive_briefing': return 'Executive briefing';
            case 'kpi':        return 'Quick stat';
            case 'cards':      return 'Records';
            case 'table':      return 'Data table';
            case 'bar_chart':  return 'Bar chart';
            case 'line_chart': return 'Trend';
            case 'pie_chart':  return 'Distribution';
            default:           return 'Result';
        }
    }

    function renderExecutiveBriefing(sd, respondArabic) {
        const root = document.createElement('div');
        root.className = 'askai-exec';
        const h = sd.health || {};
        const level = h.rating_level || 'watch';

        const healthEl = document.createElement('div');
        healthEl.className = 'askai-exec-health';
        const scoreEl = document.createElement('div');
        scoreEl.className = 'askai-exec-health__score is-' + level;
        scoreEl.textContent = (h.score != null ? h.score : '—') + '/100';
        const meta = document.createElement('div');
        meta.className = 'askai-exec-health__meta';
        meta.innerHTML = '<div class="askai-exec-health__rating">' + escapeHTML(h.rating || '') + '</div>'
            + '<div class="askai-exec-health__summary">' + escapeHTML(h.summary || '') + '</div>';
        healthEl.appendChild(scoreEl);
        healthEl.appendChild(meta);
        root.appendChild(healthEl);

        const kpis = sd.kpis || [];
        if (kpis.length) {
            const grid = document.createElement('div');
            grid.className = 'askai-exec-kpis';
            kpis.forEach(k => {
                const tile = document.createElement('div');
                tile.className = 'askai-exec-kpi' + (k.status ? ' is-' + k.status : '');
                tile.innerHTML = '<span class="askai-exec-kpi__val">' + escapeHTML(String(k.value == null ? '—' : k.value)) + '</span>'
                    + '<span class="askai-exec-kpi__lbl">' + escapeHTML(k.label || '') + '</span>';
                grid.appendChild(tile);
            });
            root.appendChild(grid);
        }

        const charts = document.createElement('div');
        charts.className = 'askai-exec-charts';
        [['kpi_chart', 'Key metrics'], ['risk_chart', 'Risk signals'], ['admissions_chart', 'Admissions trend'], ['class_attendance_chart', 'Class attendance %']].forEach(([key, title]) => {
            const ch = sd[key];
            if (!ch || !ch.labels || !ch.labels.length) return;
            const box = document.createElement('div');
            box.className = 'askai-exec-chart-box';
            box.innerHTML = '<div class="askai-exec-section__title"><i class="fa fa-bar-chart"></i> ' + escapeHTML(title) + '</div>';
            const canvas = document.createElement('canvas');
            box.appendChild(canvas);
            charts.appendChild(box);
            scheduleChartRender(canvas, ch);
        });
        if (charts.children.length) root.appendChild(charts);

        const lists = document.createElement('div');
        lists.className = 'askai-exec-lists';
        if (sd.risk_table) {
            const sec = document.createElement('div');
            sec.innerHTML = '<div class="askai-exec-section__title"><i class="fa fa-exclamation-triangle"></i> '
                + (respondArabic ? 'تحليل المخاطر' : 'Risk analysis') + '</div>';
            sec.appendChild(renderTable(sd.risk_table));
            lists.appendChild(sec);
        }
        if (sd.gaps_table) {
            const sec = document.createElement('div');
            sec.innerHTML = '<div class="askai-exec-section__title"><i class="fa fa-flag"></i> '
                + (respondArabic ? 'أهم الفجوات' : 'Where to improve') + '</div>';
            sec.appendChild(renderTable(sd.gaps_table));
            lists.appendChild(sec);
        }
        if (lists.children.length) root.appendChild(lists);

        return root;
    }

    function renderKpi(sd) {
        const wrap = document.createElement('div');
        wrap.className = 'askai-kpi';
        const value = (sd.value !== null && sd.value !== undefined) ? sd.value : sd.raw_value;
        const display = (typeof value === 'number')
            ? value.toLocaleString()
            : String(value == null ? '—' : value);
        wrap.innerHTML =
            '<span class="askai-kpi__value">' + escapeHTML(display) + '</span>'
            + '<span class="askai-kpi__label">' + escapeHTML(sd.label || '') + '</span>';
        return wrap;
    }

    function renderCards(sd) {
        const wrap = document.createElement('div');
        wrap.className = 'askai-cards';
        const cols = sd.columns || [];
        (sd.rows || []).forEach(row => {
            const card = document.createElement('div');
            card.className = 'askai-card';
            cols.forEach((col, i) => {
                const val = row[i];
                if (val === null || val === '' || val === undefined) return;
                const r = document.createElement('div');
                r.className = 'askai-card__row';
                r.innerHTML = '<span class="askai-card__key">' + escapeHTML(prettyColumn(col)) + '</span>'
                    + '<span class="askai-card__val">' + escapeHTML(displayCell(val)) + '</span>';
                card.appendChild(r);
            });
            wrap.appendChild(card);
        });
        return wrap;
    }

    function renderTable(sd) {
        const tableWrap = document.createElement('div');
        tableWrap.className = 'askai-table-wrap';
        const table = document.createElement('table');
        table.className = 'askai-table';
        const cols = sd.columns || [];
        const thead = document.createElement('thead');
        const trh = document.createElement('tr');
        cols.forEach(c => {
            const th = document.createElement('th');
            th.textContent = prettyColumn(c);
            trh.appendChild(th);
        });
        thead.appendChild(trh);
        table.appendChild(thead);
        const tbody = document.createElement('tbody');
        (sd.rows || []).forEach(row => {
            const tr = document.createElement('tr');
            cols.forEach((_, i) => {
                const td = document.createElement('td');
                const val = row[i];
                td.textContent = displayCell(val);
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        tableWrap.appendChild(table);
        return tableWrap;
    }

    function prettyColumn(name) {
        return String(name == null ? '' : name)
            .replace(/_/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
    }

    /** Strip HTML from DB fields (homework descriptions, etc.) for display. */
    function stripHtml(s) {
        if (s == null) return '';
        const t = String(s);
        if (t.indexOf('<') === -1 && t.indexOf('&') === -1) return t;
        const el = document.createElement('div');
        el.innerHTML = t;
        return (el.textContent || el.innerText || '').replace(/\s+/g, ' ').trim();
    }

    function displayCell(val) {
        if (val === null || val === undefined) return '—';
        return stripHtml(String(val));
    }

    function tableToCsv(columns, rows) {
        const esc = v => {
            if (v === null || v === undefined) return '';
            const s = String(v);
            return (s.includes(',') || s.includes('"') || s.includes('\n'))
                ? '"' + s.replace(/"/g, '""') + '"' : s;
        };
        const head = (columns || []).map(esc).join(',');
        const body = (rows || []).map(r => r.map(esc).join(',')).join('\n');
        return head + '\n' + body;
    }

    function scheduleChartRender(canvas, sd) {
        const tryRender = () => {
            if (typeof Chart === 'undefined') {
                setTimeout(tryRender, 100);
                return;
            }
            if (!canvas.isConnected) {
                setTimeout(tryRender, 50);
                return;
            }
            renderChart(canvas, sd);
        };
        tryRender();
    }

    function renderChart(canvas, sd) {
        const palette = chartPalette(sd.labels ? sd.labels.length : 1);
        const labels = sd.labels || [];
        const datasets = (sd.datasets || []).map((ds, i) => {
            const isPie = sd.kind === 'pie_chart';
            return {
                label: ds.label || 'Value',
                data: ds.data || [],
                backgroundColor: isPie
                    ? palette
                    : (sd.kind === 'line_chart' ? hexToRgba(palette[0], 0.18) : palette[0]),
                borderColor: isPie ? '#ffffff' : palette[0],
                borderWidth: isPie ? 2 : 2,
                fill: sd.kind === 'line_chart',
                tension: 0.3,
                pointRadius: 3
            };
        });
        const type = sd.kind === 'bar_chart'  ? 'bar'
                   : sd.kind === 'line_chart' ? 'line'
                   : sd.kind === 'pie_chart'  ? 'doughnut'
                   : 'bar';
        try {
            new Chart(canvas, {
                type: type,
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: (sd.kind === 'pie_chart'),
                            position: 'bottom'
                        },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: (sd.kind === 'pie_chart') ? {} : {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        } catch (e) {
            console.error('Chart render failed:', e);
        }
    }

    function chartPalette(n) {
        const base = ['#5b6cff','#ff8a5c','#28c76f','#f1c40f','#9b59b6','#1abc9c','#e84393','#0984e3','#fdcb6e','#6c5ce7'];
        const out = [];
        for (let i = 0; i < Math.max(1, n); i++) out.push(base[i % base.length]);
        return out;
    }
    function hexToRgba(hex, a) {
        const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        if (!m) return 'rgba(91,108,255,' + a + ')';
        return 'rgba(' + parseInt(m[1],16) + ',' + parseInt(m[2],16) + ',' + parseInt(m[3],16) + ',' + a + ')';
    }

    function buildFollowupBlock(suggestions, respondArabic) {
        const wrap = document.createElement('div');
        wrap.className = 'askai-followups';
        const isAr = !!respondArabic;
        if (isAr) {
            wrap.setAttribute('dir', 'rtl');
            wrap.setAttribute('lang', 'ar');
        }
        wrap.innerHTML = '<div class="askai-followups__label">'
            + '<i class="fa fa-lightbulb-o"></i> '
            + (isAr ? 'جرّب أيضاً' : 'Try asking next') + '</div>'
            + '<div class="askai-followups__list"></div>';
        const list = wrap.querySelector('.askai-followups__list');
        suggestions.slice(0, 5).forEach(q => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'askai-chip';
            chip.innerHTML = '<i class="fa fa-arrow-right"></i><span>'
                + escapeHTML(q) + '</span>';
            chip.addEventListener('click', () => {
                els.input.value = q;
                els.input.dispatchEvent(new Event('input'));
                handleSend();
            });
            list.appendChild(chip);
        });
        return wrap;
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
            const respondArabic = els.arabic && els.arabic.checked;
            const reply = await window.AskAIApi.sendMessage(text, respondArabic);
            convo.messages.push(buildAiMessage(reply, respondArabic));
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

    function buildAiMessage(reply, respondArabic) {
        return {
            role:           'ai',
            text:           reply.answer || '',
            ts:             Date.now(),
            request_id:     reply.request_id || '',
            source:         reply.source || '',
            status:         reply.status || 'ok',
            sql:            reply.sql || '',
            presentation:   reply.presentation || 'text',
            structured_data: reply.structured_data || null,
            suggestions:    Array.isArray(reply.suggestions) ? reply.suggestions : [],
            intent:         reply.intent || 'general',
            module:         reply.module || 'general',
            module_label:   reply.module_label || '',
            respond_arabic: !!respondArabic
        };
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
            const respondArabic = els.arabic && els.arabic.checked;
            const reply = await window.AskAIApi.sendMessage(lastUser.text, respondArabic);
            convo.messages.push(buildAiMessage(reply, respondArabic));
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

    function showToast(text, kind) {
        let host = document.getElementById('askaiToastHost');
        if (!host) {
            host = document.createElement('div');
            host.id = 'askaiToastHost';
            host.style.cssText = 'position:fixed;bottom:20px;inset-inline-end:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(host);
        }
        const el = document.createElement('div');
        const bg = kind === 'error' ? '#dc3545' : (kind === 'info' ? '#6c757d' : '#28a745');
        el.style.cssText = 'background:' + bg + ';color:#fff;padding:10px 14px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.2);font-size:13px;max-width:320px;';
        el.textContent = text;
        host.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; }, 2400);
        setTimeout(() => { el.remove(); }, 2800);
    }

    async function sendFeedback(m, verdict) {
        if (!m.request_id) {
            showToast('Feedback unavailable for this answer.', 'info');
            return;
        }
        try {
            const res = await window.AskAIApi.sendFeedback(m.request_id, verdict, '');
            if (res && res.learned) {
                showToast('Thanks — I will remember this for next time.', 'success');
            } else if (res && res.ok) {
                showToast(verdict === 'good'
                    ? 'Thanks — already covered by a verified answer.'
                    : 'Thanks — we will review this.', 'info');
            } else {
                showToast((res && res.reason) || 'Could not save feedback right now.', 'error');
            }
        } catch (e) {
            showToast('Could not reach the feedback endpoint.', 'error');
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
