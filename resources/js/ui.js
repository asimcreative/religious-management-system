/**
 * RAMS — UI behaviour layer
 *
 * Progressive enhancement only. Every feature here degrades gracefully:
 * with JavaScript disabled the application still renders, navigates and
 * submits forms exactly as it does today. No backend contract is touched.
 *
 * Modules
 *   1.  Theme          — light / dark preference (localStorage)
 *   2.  Sidebar        — mobile off-canvas + desktop icon-rail collapse
 *   3.  Toasts         — flash messages and a window.rams.toast() API
 *   4.  Confirm        — accessible replacement for window.confirm()
 *   5.  Submit state   — button spinners, double-submit protection
 *   6.  Password       — reveal toggle + strength meter
 *   7.  Quick nav      — Ctrl/Cmd-K command palette over permitted pages
 *   8.  Table filter   — instant client-side row filtering
 *   9.  Auto-dismiss   — timed alerts
 *   10. Tooltips       — Bootstrap tooltip bootstrapping
 *   11. Form guard     — warn before leaving a dirty form
 *   12. Searchable select — Tom Select over employee/teacher pickers
 */

import TomSelect from 'tom-select/popular';

const STORAGE = {
    theme: 'rams.theme',
    sidebar: 'rams.sidebar.collapsed',
};

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const store = {
    get(key) {
        try { return window.localStorage.getItem(key); } catch { return null; }
    },
    set(key, value) {
        try { window.localStorage.setItem(key, value); } catch { /* private mode */ }
    },
};

/* ───────────────────────────────────────────────────────────────────────────
 * 1. Theme
 * ─────────────────────────────────────────────────────────────────────────── */

function resolveTheme() {
    const saved = store.get(STORAGE.theme);
    if (saved === 'light' || saved === 'dark') return saved;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);

    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        const isDark = theme === 'dark';
        btn.setAttribute('aria-pressed', String(isDark));

        const icon = btn.querySelector('i');
        if (icon) icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';

        const label = btn.getAttribute(isDark ? 'data-label-light' : 'data-label-dark');
        if (label) {
            btn.setAttribute('title', label);
            btn.setAttribute('aria-label', label);
        }
    });
}

function initTheme() {
    applyTheme(resolveTheme());

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-theme-toggle]');
        if (!btn) return;

        event.preventDefault();
        const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        store.set(STORAGE.theme, next);
        applyTheme(next);
    });

    // Follow the OS while the user has not made an explicit choice
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (!store.get(STORAGE.theme)) applyTheme(resolveTheme());
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 2. Sidebar
 * ─────────────────────────────────────────────────────────────────────────── */

function initSidebar() {
    const shell = document.querySelector('[data-app-shell]');
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    if (!shell || !sidebar) return;

    const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;

    const openMobile = () => {
        sidebar.classList.add('is-open');
        backdrop?.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
        document.querySelector('[data-sidebar-open]')?.setAttribute('aria-expanded', 'true');
        sidebar.querySelector('a, button')?.focus({ preventScroll: true });
    };

    const closeMobile = () => {
        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        document.body.style.overflow = '';
        document.querySelector('[data-sidebar-open]')?.setAttribute('aria-expanded', 'false');
    };

    // Icon-rail tooltips exist only while collapsed — when labels are visible
    // a tooltip would just repeat them.
    const syncRailTips = (collapsed) => {
        if (!window.bootstrap?.Tooltip) return;

        sidebar.querySelectorAll('[data-rail-tip]').forEach((link) => {
            const existing = window.bootstrap.Tooltip.getInstance(link);

            if (collapsed && isDesktop()) {
                if (!existing) {
                    window.bootstrap.Tooltip.getOrCreateInstance(link, {
                        title: link.dataset.railTip,
                        placement: 'right',
                        container: 'body',
                        delay: { show: 250, hide: 60 },
                    });
                }
            } else if (existing) {
                existing.dispose();
            }
        });
    };

    const setCollapsed = (collapsed) => {
        shell.classList.toggle('is-collapsed', collapsed);
        store.set(STORAGE.sidebar, collapsed ? '1' : '0');
        document.querySelectorAll('[data-sidebar-collapse]').forEach((b) => {
            b.setAttribute('aria-expanded', String(!collapsed));
        });
        syncRailTips(collapsed);
    };

    // The pre-paint stylesheet hint has done its job; keep it from winning
    // on specificity once the user expands the rail again.
    setCollapsed(store.get(STORAGE.sidebar) === '1');
    document.documentElement.classList.remove('rams-preload-collapsed');

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-sidebar-open]')) {
            event.preventDefault();
            sidebar.classList.contains('is-open') ? closeMobile() : openMobile();
            return;
        }

        if (event.target.closest('[data-sidebar-close]') || event.target.closest('[data-sidebar-backdrop]')) {
            event.preventDefault();
            closeMobile();
            return;
        }

        if (event.target.closest('[data-sidebar-collapse]')) {
            event.preventDefault();
            setCollapsed(!shell.classList.contains('is-collapsed'));
            return;
        }

        // Tapping a navigation link on mobile should close the drawer
        if (!isDesktop() && event.target.closest('[data-sidebar] a[href]')) closeMobile();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) closeMobile();
    });

    window.addEventListener('resize', () => {
        if (isDesktop() && sidebar.classList.contains('is-open')) closeMobile();
        syncRailTips(shell.classList.contains('is-collapsed'));
    });

    // Keep the active menu item in view on load (deep menus scroll)
    const active = sidebar.querySelector('.rams-sidebar__link.is-active');
    if (active) {
        const nav = sidebar.querySelector('.rams-sidebar__nav');
        if (nav && active.offsetTop > nav.clientHeight - 80) {
            nav.scrollTop = active.offsetTop - nav.clientHeight / 2;
        }
    }
}

/* ───────────────────────────────────────────────────────────────────────────
 * 3. Toasts
 * ─────────────────────────────────────────────────────────────────────────── */

const TOAST_TONES = {
    success: { icon: 'bi-check-circle-fill', tone: 'var(--rams-success-text)' },
    error: { icon: 'bi-exclamation-octagon-fill', tone: 'var(--rams-danger-text)' },
    danger: { icon: 'bi-exclamation-octagon-fill', tone: 'var(--rams-danger-text)' },
    warning: { icon: 'bi-exclamation-triangle-fill', tone: 'var(--rams-warning-text)' },
    info: { icon: 'bi-info-circle-fill', tone: 'var(--rams-info-text)' },
};

function toasterEl() {
    let el = document.querySelector('.rams-toaster');
    if (!el) {
        el = document.createElement('div');
        el.className = 'rams-toaster';
        el.setAttribute('role', 'region');
        el.setAttribute('aria-label', 'Notifications');
        document.body.appendChild(el);
    }
    return el;
}

function toast(message, options = {}) {
    const { type = 'info', title = '', timeout = 5500 } = options;
    const meta = TOAST_TONES[type] ?? TOAST_TONES.info;

    const el = document.createElement('div');
    el.className = 'rams-toast';
    el.style.setProperty('--tone', meta.tone);
    // Errors interrupt; everything else is announced politely.
    el.setAttribute('role', type === 'error' || type === 'danger' ? 'alert' : 'status');
    el.setAttribute('aria-live', type === 'error' || type === 'danger' ? 'assertive' : 'polite');

    const icon = document.createElement('i');
    icon.className = `bi ${meta.icon} rams-toast__icon`;
    icon.setAttribute('aria-hidden', 'true');

    const body = document.createElement('div');
    body.className = 'rams-toast__body';

    if (title) {
        const t = document.createElement('div');
        t.className = 'rams-toast__title';
        t.textContent = title;
        body.appendChild(t);
    }

    const text = document.createElement('div');
    text.className = title ? 'rams-toast__text' : 'rams-toast__title';
    text.textContent = message;
    body.appendChild(text);

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'rams-toast__close';
    close.setAttribute('aria-label', el.dataset.closeLabel || 'Dismiss');
    close.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';

    el.append(icon, body, close);
    toasterEl().appendChild(el);

    let timer;
    const dismiss = () => {
        window.clearTimeout(timer);
        if (prefersReducedMotion()) { el.remove(); return; }
        el.classList.add('is-leaving');
        el.addEventListener('animationend', () => el.remove(), { once: true });
    };

    close.addEventListener('click', dismiss);
    if (timeout > 0) timer = window.setTimeout(dismiss, timeout);
    el.addEventListener('mouseenter', () => window.clearTimeout(timer));
    el.addEventListener('mouseleave', () => {
        if (timeout > 0) timer = window.setTimeout(dismiss, 2500);
    });

    return dismiss;
}

function initFlashToasts() {
    document.querySelectorAll('[data-flash]').forEach((node) => {
        toast(node.dataset.flashMessage || node.textContent.trim(), {
            type: node.dataset.flash || 'info',
            title: node.dataset.flashTitle || '',
        });
        node.remove();
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 4. Confirm dialog
 *
 * Replaces onsubmit="return confirm(...)" with an accessible modal.
 * Falls back to window.confirm() if Bootstrap's JS is unavailable.
 * ─────────────────────────────────────────────────────────────────────────── */

function buildConfirmModal() {
    const wrap = document.createElement('div');
    wrap.className = 'modal fade';
    wrap.id = 'ramsConfirm';
    wrap.tabIndex = -1;
    wrap.setAttribute('aria-hidden', 'true');
    wrap.setAttribute('aria-labelledby', 'ramsConfirmTitle');
    wrap.innerHTML = `
        <div class="modal-dialog modal-dialog-centered modal-sm-plus">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="modal-icon" data-confirm-icon><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></div>
                    <div class="min-w-0">
                        <h2 class="modal-title h6 mb-1" id="ramsConfirmTitle"></h2>
                        <p class="mb-0 fs-sm text-soft" data-confirm-text></p>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-footer border-0 pt-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-confirm-cancel></button>
                    <button type="button" class="btn btn-danger" data-confirm-accept></button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(wrap);
    return wrap;
}

function initConfirm() {
    let modalEl = null;
    let instance = null;
    let pending = null;

    // Destructive forms ship an inline window.confirm() so they stay guarded
    // without JavaScript. Once the modal is available that fallback would ask
    // the user a second time, so drop it.
    if (window.bootstrap?.Modal) {
        document.querySelectorAll('form[data-confirm-fallback]').forEach((form) => {
            form.removeAttribute('onsubmit');
            form.onsubmit = null;
        });
    }

    const ensure = () => {
        if (!window.bootstrap?.Modal) return null;
        if (!modalEl) {
            modalEl = document.getElementById('ramsConfirm') || buildConfirmModal();
            instance = window.bootstrap.Modal.getOrCreateInstance(modalEl);

            modalEl.querySelector('[data-confirm-accept]').addEventListener('click', () => {
                const target = pending;
                pending = null;
                instance.hide();
                if (!target) return;
                target.dataset.confirmed = '1';
                if (target.tagName === 'FORM') target.requestSubmit();
                else target.click();
            });

            modalEl.addEventListener('hidden.bs.modal', () => { pending = null; });
        }
        return instance;
    };

    document.addEventListener(
        'click',
        (event) => {
            const trigger = event.target.closest('[data-confirm]');
            if (!trigger) return;

            // The owning form is what we ultimately submit
            const form = trigger.tagName === 'FORM' ? trigger : trigger.closest('form');
            const subject = form ?? trigger;

            if (subject.dataset.confirmed === '1') {
                delete subject.dataset.confirmed;
                return;
            }

            const message = trigger.dataset.confirm || 'Are you sure?';
            const modal = ensure();

            if (!modal) {
                // eslint-disable-next-line no-alert
                if (!window.confirm(message)) event.preventDefault();
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const tone = trigger.dataset.confirmTone || 'danger';
            const soft = tone === 'danger' ? 'var(--rams-danger-soft)' : 'var(--rams-warning-soft)';
            const solid = tone === 'danger' ? 'var(--rams-danger-text)' : 'var(--rams-warning-text)';

            const iconBox = modalEl.querySelector('[data-confirm-icon]');
            iconBox.style.setProperty('--tone-soft', soft);
            iconBox.style.setProperty('--tone', solid);
            iconBox.querySelector('i').className = `bi ${trigger.dataset.confirmIcon || 'bi-exclamation-triangle'}`;

            modalEl.querySelector('#ramsConfirmTitle').textContent =
                trigger.dataset.confirmTitle || document.documentElement.dataset.confirmTitle || 'Please confirm';
            modalEl.querySelector('[data-confirm-text]').textContent = message;

            const accept = modalEl.querySelector('[data-confirm-accept]');
            accept.textContent =
                trigger.dataset.confirmAccept || document.documentElement.dataset.confirmAccept || 'Confirm';
            accept.className = `btn btn-${tone}`;

            modalEl.querySelector('[data-confirm-cancel]').textContent =
                trigger.dataset.confirmCancel || document.documentElement.dataset.confirmCancel || 'Cancel';

            pending = subject;
            modal.show();
        },
        true,
    );
}

/* ───────────────────────────────────────────────────────────────────────────
 * 5. Submit state
 * ─────────────────────────────────────────────────────────────────────────── */

function initSubmitState() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-loading')) return;

        // Skip only when the browser itself will block the submit — with
        // novalidate the request goes out and the spinner is correct.
        if (!form.noValidate && typeof form.checkValidity === 'function' && !form.checkValidity()) return;

        const button =
            (event.submitter && event.submitter.tagName === 'BUTTON' ? event.submitter : null) ??
            form.querySelector('button[type="submit"]');

        if (!button || button.classList.contains('is-loading')) return;

        button.classList.add('is-loading');
        button.setAttribute('aria-busy', 'true');

        // Re-enable if the navigation is cancelled or the user comes back
        const restore = () => {
            button.classList.remove('is-loading');
            button.removeAttribute('aria-busy');
        };
        window.setTimeout(restore, 12000);
        window.addEventListener('pageshow', restore, { once: true });
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 6. Password reveal + strength
 * ─────────────────────────────────────────────────────────────────────────── */

function scorePassword(value) {
    if (!value) return 0;
    let score = 0;
    if (value.length >= 12) score += 1;
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 1;
    if (/\d/.test(value)) score += 1;
    if (/[^\w\s]/.test(value)) score += 1;
    if (value.length < 8) score = Math.min(score, 1);
    return score;
}

function initPassword() {
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-password-toggle]');
        if (!btn) return;

        event.preventDefault();
        const input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;

        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(reveal));

        const icon = btn.querySelector('i');
        if (icon) icon.className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';

        const label = btn.getAttribute(reveal ? 'data-label-hide' : 'data-label-show');
        if (label) { btn.setAttribute('title', label); btn.setAttribute('aria-label', label); }
    });

    document.querySelectorAll('[data-password-meter]').forEach((input) => {
        const meter = document.querySelector(`[data-meter-for="${input.id}"]`);
        if (!meter) return;

        const hint = document.querySelector(`[data-meter-hint="${input.id}"]`);
        const labels = (meter.dataset.labels || '').split('|');

        input.addEventListener('input', () => {
            const score = scorePassword(input.value);
            meter.dataset.score = String(score);
            if (hint && labels.length >= 4) {
                hint.textContent = input.value ? labels[Math.max(score - 1, 0)] ?? '' : '';
            }
        });
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 7. Quick navigation (Ctrl/Cmd-K)
 *
 * Indexes the permission-filtered sidebar links already rendered by Blade,
 * so it can never expose a page the user is not allowed to open.
 * ─────────────────────────────────────────────────────────────────────────── */

function initQuickNav() {
    const modalEl = document.getElementById('ramsQuickNav');
    if (!modalEl || !window.bootstrap?.Modal) return;

    const input = modalEl.querySelector('[data-quicknav-input]');
    const list = modalEl.querySelector('[data-quicknav-results]');
    const empty = modalEl.querySelector('[data-quicknav-empty]');
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);

    const entries = [...document.querySelectorAll('[data-sidebar] a[href]')]
        .map((link) => ({
            href: link.getAttribute('href'),
            label: (link.querySelector('.rams-sidebar__label')?.textContent || link.textContent).trim(),
            group: link.closest('[data-nav-group]')?.dataset.navGroup || '',
            icon: link.querySelector('i')?.className || 'bi bi-arrow-right-short',
        }))
        .filter((e, i, all) => e.label && all.findIndex((x) => x.href === e.href) === i);

    let cursor = 0;

    const render = (query = '') => {
        const q = query.trim().toLowerCase();
        const matches = q
            ? entries.filter((e) => `${e.label} ${e.group}`.toLowerCase().includes(q))
            : entries;

        list.innerHTML = '';
        cursor = 0;

        matches.slice(0, 40).forEach((entry, index) => {
            const a = document.createElement('a');
            a.href = entry.href;
            a.className = `quicknav__item${index === 0 ? ' is-active' : ''}`;
            a.innerHTML = `<i class="${entry.icon}" aria-hidden="true"></i><span></span>${entry.group ? '<small></small>' : ''}`;
            a.querySelector('span').textContent = entry.label;
            if (entry.group) a.querySelector('small').textContent = entry.group;
            list.appendChild(a);
        });

        empty.hidden = matches.length > 0;
    };

    const move = (delta) => {
        const items = [...list.querySelectorAll('.quicknav__item')];
        if (!items.length) return;
        items[cursor]?.classList.remove('is-active');
        cursor = (cursor + delta + items.length) % items.length;
        items[cursor].classList.add('is-active');
        items[cursor].scrollIntoView({ block: 'nearest' });
    };

    modalEl.addEventListener('shown.bs.modal', () => { input.value = ''; render(); input.focus(); });
    input.addEventListener('input', () => render(input.value));

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') { event.preventDefault(); move(1); }
        else if (event.key === 'ArrowUp') { event.preventDefault(); move(-1); }
        else if (event.key === 'Enter') {
            const active = list.querySelector('.quicknav__item.is-active');
            if (active) { event.preventDefault(); window.location.href = active.href; }
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            modal.show();
        }
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 8. Instant table filter (client side, already-rendered rows)
 * ─────────────────────────────────────────────────────────────────────────── */

function initTableFilter() {
    document.querySelectorAll('[data-table-filter]').forEach((input) => {
        const table = document.querySelector(input.dataset.tableFilter);
        if (!table) return;

        const counter = document.querySelector(`[data-filter-count="${input.dataset.tableFilter}"]`);
        const emptyRow = table.querySelector('[data-filter-empty]');
        const rows = [...table.querySelectorAll('tbody > tr:not([data-filter-empty])')];
        const total = rows.length;

        const run = () => {
            const q = input.value.trim().toLowerCase();
            let shown = 0;

            rows.forEach((row) => {
                const hit = !q || row.textContent.toLowerCase().includes(q);
                row.hidden = !hit;
                if (hit) shown += 1;
            });

            if (emptyRow) emptyRow.hidden = shown !== 0 || total === 0;
            if (counter) counter.textContent = counter.dataset.template
                ? counter.dataset.template.replace(':shown', shown).replace(':total', total)
                : `${shown} / ${total}`;
        };

        input.addEventListener('input', run);
        input.addEventListener('search', run);
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 8b. Live checked-item counters
 *
 * Gives multi-select checkbox groups (branch assignment, class members) a
 * running count so the user can confirm their selection without counting.
 * ─────────────────────────────────────────────────────────────────────────── */

function initCheckedCounters() {
    document.querySelectorAll('[data-count-for]').forEach((badge) => {
        const selector = badge.dataset.countFor;
        const inputs = [...document.querySelectorAll(selector)];
        if (!inputs.length) return;

        const update = () => {
            badge.textContent = String(inputs.filter((input) => input.checked).length);
        };

        inputs.forEach((input) => input.addEventListener('change', update));
        update();
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 8c. Attendance sheet helpers
 *
 * Marking a class of 30 people one row at a time is the slowest task in the
 * app. A bulk setter plus a live present/absent tally removes most of that
 * work — while every row remains an ordinary form control that posts exactly
 * as before.
 * ─────────────────────────────────────────────────────────────────────────── */

function initBulkApply() {
    document.querySelectorAll('[data-bulk-apply]').forEach((button) => {
        button.addEventListener('click', () => {
            const targets = document.querySelectorAll(button.dataset.bulkApply);
            const source = button.dataset.bulkFrom ? document.querySelector(button.dataset.bulkFrom) : null;
            const value = source ? source.value : (button.dataset.bulkValue ?? '');

            targets.forEach((field) => {
                if (field.value === value) return;
                field.value = value;
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
}

// Variant where the target selector itself comes from a <select> — used by the
// Salah grid to target one prayer column or every column at once.
function initDynamicBulkApply() {
    document.querySelectorAll('[data-bulk-apply-dynamic]').forEach((button) => {
        button.addEventListener('click', () => {
            const scopeSelect = document.querySelector(button.dataset.bulkApplyDynamic);
            const valueSelect = button.dataset.bulkFrom ? document.querySelector(button.dataset.bulkFrom) : null;
            if (!scopeSelect) return;

            const value = valueSelect ? valueSelect.value : '';
            document.querySelectorAll(scopeSelect.value).forEach((field) => {
                if (field.value === value) return;
                field.value = value;
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
}

function initAttendanceTally() {
    document.querySelectorAll('[data-attendance-sheet]').forEach((sheet) => {
        const fields = [...sheet.querySelectorAll('[data-attendance-field]')];
        if (!fields.length) return;

        const presentEl = document.querySelector(sheet.dataset.attendancePresent);
        const absentEl = document.querySelector(sheet.dataset.attendanceAbsent);
        const barEl = document.querySelector(sheet.dataset.attendanceBar);

        const update = () => {
            const present = fields.filter((field) => field.value === '').length;
            const absent = fields.length - present;

            if (presentEl) presentEl.textContent = String(present);
            if (absentEl) absentEl.textContent = String(absent);
            if (barEl) barEl.style.width = `${Math.round((present / fields.length) * 100)}%`;

            fields.forEach((field) => {
                field.closest('tr')?.classList.toggle('is-absent', field.value !== '');
            });
        };

        fields.forEach((field) => field.addEventListener('change', update));
        update();
    });
}

// Suspends the per-student grid when the teacher/qari is marked absent — the
// class didn't happen, so no per-student pick is meaningful. Fields stay in
// the DOM and submittable (inert, not disabled) so the roster-match
// validation on the server still sees every active member's id.
function initTeacherAbsenceToggle() {
    document.querySelectorAll('[data-teacher-absent-toggle]').forEach((toggle) => {
        const fieldsBlock = document.querySelector('[data-teacher-absent-fields]');
        const requiredFields = [...document.querySelectorAll('[data-teacher-absent-required]')];
        const sheet = document.querySelector('[data-attendance-sheet]');
        const tbody = sheet?.querySelector('tbody');
        const statusFields = sheet ? [...sheet.querySelectorAll('[data-attendance-field]')] : [];

        const sync = () => {
            const absent = toggle.checked;

            fieldsBlock?.toggleAttribute('hidden', !absent);
            requiredFields.forEach((field) => field.toggleAttribute('required', absent));

            sheet?.classList.toggle('attendance-sheet--suspended', absent);
            if (absent) {
                tbody?.setAttribute('inert', '');
                statusFields.forEach((field) => {
                    field.value = '';
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                });
            } else {
                tbody?.removeAttribute('inert');
            }
        };

        toggle.addEventListener('change', sync);
        sync();
    });
}

// Opposite polarity from the teacher-absence toggle: checked is the normal,
// default state here ("Taleem held"), so the reason field appears when the
// user *un*checks it, not when they check it. No grid to suspend — Taleem is
// independent of prayer attendance.
function initTaleemToggle() {
    document.querySelectorAll('[data-taleem-toggle]').forEach((toggle) => {
        const fieldsBlock = document.querySelector('[data-taleem-fields]');
        const requiredFields = [...document.querySelectorAll('[data-taleem-required]')];

        const sync = () => {
            const held = toggle.checked;

            fieldsBlock?.toggleAttribute('hidden', held);
            requiredFields.forEach((field) => field.toggleAttribute('required', !held));
        };

        toggle.addEventListener('change', sync);
        sync();
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 8d. Shell-owned form triggers
 *
 * The account menu is rendered above the page content, so a submit button in it
 * would be the first submit button on every screen — and "activate the page's
 * submit button" would sign the user out instead of saving their work. The menu
 * therefore renders plain buttons that post one of the forms parked at the end
 * of <body>. Without JavaScript the menu's <noscript> forms take over.
 * ─────────────────────────────────────────────────────────────────────────── */

function initShellForms() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-shell-submit]');
        if (!trigger) return;

        const form = document.querySelector(trigger.dataset.shellSubmit);
        if (!form) return;

        event.preventDefault();

        const field = trigger.dataset.shellField;
        if (field) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) input.value = trigger.dataset.shellValue ?? '';
        }

        form.requestSubmit();
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 9. Auto-dismiss alerts
 * ─────────────────────────────────────────────────────────────────────────── */

function initAutoDismiss() {
    document.querySelectorAll('[data-auto-dismiss]').forEach((el) => {
        const delay = Number(el.dataset.autoDismiss) || 6000;
        window.setTimeout(() => {
            if (window.bootstrap?.Alert) window.bootstrap.Alert.getOrCreateInstance(el).close();
            else el.remove();
        }, delay);
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 10. Tooltips
 * ─────────────────────────────────────────────────────────────────────────── */

function initTooltips() {
    if (!window.bootstrap?.Tooltip) return;
    // Touch devices get no hover — a tooltip there only blocks the tap target.
    if (window.matchMedia('(hover: none)').matches) return;

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        window.bootstrap.Tooltip.getOrCreateInstance(el, { container: 'body', delay: { show: 400, hide: 80 } });
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 11. Unsaved-changes guard
 * ─────────────────────────────────────────────────────────────────────────── */

function initFormGuard() {
    document.querySelectorAll('form[data-guard]').forEach((form) => {
        let dirty = false;
        let submitting = false;

        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });
        form.addEventListener('submit', () => { submitting = true; });

        window.addEventListener('beforeunload', (event) => {
            if (!dirty || submitting) return;
            event.preventDefault();
            event.returnValue = '';
        });
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 12. Searchable select
 *
 * Enhances an ordinary <select> — already fully populated and permission-
 * scoped server-side — into a type-to-filter picker. No new endpoint, no
 * change to what gets submitted: the underlying <select> is what posts, Tom
 * Select only changes how it is browsed. Matches on whatever text each
 * <option> carries, which is always "name (code)" for employee/teacher
 * pickers — one box searches both.
 *
 * `data-searchable-select-freeform` marks the handful of report search boxes
 * that used to be a plain <input name="search">: the server still runs the
 * exact same LIKE query it always did, so typing something that matches no
 * employee must still submit as free text instead of being rejected — that is
 * what `create` here allows.
 * ─────────────────────────────────────────────────────────────────────────── */

function initSearchableSelect() {
    document.querySelectorAll('[data-searchable-select], [data-searchable-select-freeform]').forEach((select) => {
        if (select.tomselect) return;

        const freeform = select.hasAttribute('data-searchable-select-freeform');
        const settings = {
            plugins: ['dropdown_input'],
            maxOptions: 100,
            dropdownParent: 'body',
            create: freeform,
            createOnBlur: freeform,
            persist: false,
        };

        // A plain <select> already carries its own <option>s. A freeform
        // input has none — its suggestions travel in a JSON attribute
        // instead, and the field keeps posting whatever text ends up in it
        // (picked or typed) under its original name either way.
        if (freeform && select.dataset.employeeOptions) {
            let options = [];
            try {
                options = JSON.parse(select.dataset.employeeOptions);
            } catch {
                options = [];
            }

            Object.assign(settings, {
                options,
                valueField: 'value',
                labelField: 'text',
                searchField: ['text'],
                maxItems: 1,
            });
        }

        new TomSelect(select, settings);
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * Boot
 * ─────────────────────────────────────────────────────────────────────────── */

function boot() {
    initTheme();
    initSidebar();
    initFlashToasts();
    initConfirm();
    initSubmitState();
    initPassword();
    initQuickNav();
    initTableFilter();
    initCheckedCounters();
    initBulkApply();
    initDynamicBulkApply();
    initAttendanceTally();
    initTeacherAbsenceToggle();
    initTaleemToggle();
    initShellForms();
    initAutoDismiss();
    initTooltips();
    initFormGuard();
    initSearchableSelect();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

window.rams = { toast, applyTheme };

export { toast };
