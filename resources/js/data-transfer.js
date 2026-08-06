/**
 * RAMS — import / export behaviour
 *
 * Progressive enhancement only. With JavaScript off the toolbar still exports
 * (plain links), the import dialog still submits (plain multipart form), the
 * preview still renders and the confirm button still imports. Nothing below
 * changes a backend contract; it only makes the same flows nicer to use.
 *
 * Modules
 *   1. Print         — the toolbar's print button
 *   2. Selection     — row checkboxes drive "Export selected" and bulk actions
 *   3. Import poll   — live progress for a queued import
 *   4. Columns       — show/hide columns, remembered per module
 *   5. Copy          — the visible table onto the clipboard
 */

/* Small helpers shared by the modules below. */
const toast = (message, tone = 'info') => {
    if (window.rams?.toast) window.rams.toast(message, tone);
};

const visibleTable = () => document.querySelector('.table-wrap table');

/* ───────────────────────────────────────────────────────────────────────────
 * 1. Print
 * ─────────────────────────────────────────────────────────────────────────── */

function initPrint() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-print]');
        if (!trigger) return;

        event.preventDefault();
        window.print();
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 2. Row selection
 *
 * "Export selected" stays disabled until something is selected, because an
 * enabled button that produces an empty file is worse than a disabled one.
 * ─────────────────────────────────────────────────────────────────────────── */

function selectedIds() {
    return Array.from(document.querySelectorAll('[data-row-select]:checked'))
        .map((box) => box.value)
        .filter(Boolean);
}

function refreshSelectionState() {
    const ids = selectedIds();
    const master = document.querySelector('[data-row-select-all]');
    const boxes = document.querySelectorAll('[data-row-select]');

    if (master) {
        master.checked = boxes.length > 0 && ids.length === boxes.length;
        master.indeterminate = ids.length > 0 && ids.length < boxes.length;
    }

    document.querySelectorAll('[data-export-selected]').forEach((item) => {
        const base = item.getAttribute('data-export-selected');
        if (!base) return;

        if (ids.length === 0) {
            item.setAttribute('disabled', 'disabled');
            item.removeAttribute('data-href');
            return;
        }

        const url = new URL(base, window.location.origin);
        ids.forEach((id) => url.searchParams.append('ids[]', id));

        item.removeAttribute('disabled');
        item.setAttribute('data-href', url.toString());
    });

    document.querySelectorAll('[data-selection-count]').forEach((el) => {
        el.textContent = String(ids.length);
    });

    document.querySelectorAll('[data-selection-bar]').forEach((bar) => {
        bar.classList.toggle('d-none', ids.length === 0);
    });

    document.querySelectorAll('[data-bulk-delete]').forEach((button) => {
        const template = button.getAttribute('data-confirm-template') ?? '';
        button.setAttribute('data-confirm', template.replace(':count', String(ids.length)));
    });
}

function initSelection() {
    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-row-select-all]')) {
            const checked = event.target.checked;
            document.querySelectorAll('[data-row-select]').forEach((box) => { box.checked = checked; });
        }

        if (event.target.matches('[data-row-select], [data-row-select-all]')) {
            refreshSelectionState();
        }
    });

    // The dropdown entry is a <button> so it can be disabled; navigating it
    // by hand keeps that behaviour without faking a disabled anchor.
    document.addEventListener('click', (event) => {
        const item = event.target.closest('[data-export-selected]');
        if (!item || item.hasAttribute('disabled')) return;

        const href = item.getAttribute('data-href');
        if (href) window.location.href = href;
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-selection-clear]')) return;

        document.querySelectorAll('[data-row-select], [data-row-select-all]').forEach((box) => {
            box.checked = false;
            box.indeterminate = false;
        });
        refreshSelectionState();
    });

    if (document.querySelector('[data-row-select]')) refreshSelectionState();
}

/* ───────────────────────────────────────────────────────────────────────────
 * 4. Column visibility
 *
 * Driven entirely by the rendered table: the headings are the list, and a
 * column is hidden by its position. No per-module configuration, and nothing
 * to keep in step when a module adds a column.
 * ─────────────────────────────────────────────────────────────────────────── */

function readHidden(key) {
    try {
        const stored = JSON.parse(window.localStorage.getItem(key) ?? '[]');
        return Array.isArray(stored) ? stored.map(Number) : [];
    } catch {
        return [];
    }
}

function writeHidden(key, hidden) {
    try {
        window.localStorage.setItem(key, JSON.stringify(hidden));
    } catch { /* private mode */ }
}

function applyColumnVisibility(table, hidden) {
    const total = table.querySelectorAll('thead th').length;

    for (let index = 1; index <= total; index += 1) {
        const isHidden = hidden.includes(index);

        table.querySelectorAll(
            `thead th:nth-child(${index}), tbody td:nth-child(${index})`,
        ).forEach((cell) => {
            // A cell spanning the table is an empty state, not a data cell;
            // hiding it would leave the user staring at nothing.
            if (cell.hasAttribute('colspan')) return;
            cell.classList.toggle('is-column-hidden', isHidden);
        });
    }
}

function initColumnVisibility() {
    const panel = document.querySelector('[data-column-toggle]');
    const table = visibleTable();
    const list = panel?.querySelector('[data-column-list]');

    if (!panel || !table || !list) return;

    const key = panel.getAttribute('data-storage-key') ?? 'rams.columns';
    let hidden = readHidden(key);

    const headings = Array.from(table.querySelectorAll('thead th'));

    headings.forEach((th, offset) => {
        const index = offset + 1;
        const label = th.textContent.trim();

        // The row-number and actions columns are furniture, not data.
        if (label === '' || label === '#' || th.classList.contains('col-actions')) return;

        const id = `${key}-col-${index}`;
        const wrapper = document.createElement('label');
        wrapper.className = 'dropdown-item form-check';
        wrapper.setAttribute('for', id);

        const box = document.createElement('input');
        box.type = 'checkbox';
        box.className = 'form-check-input';
        box.id = id;
        box.checked = !hidden.includes(index);

        box.addEventListener('change', () => {
            hidden = box.checked ? hidden.filter((i) => i !== index) : [...hidden, index];
            writeHidden(key, hidden);
            applyColumnVisibility(table, hidden);
        });

        wrapper.append(box, document.createTextNode(` ${label}`));
        list.appendChild(wrapper);
    });

    panel.querySelector('[data-column-reset]')?.addEventListener('click', () => {
        hidden = [];
        writeHidden(key, hidden);
        applyColumnVisibility(table, hidden);
        list.querySelectorAll('input[type="checkbox"]').forEach((box) => { box.checked = true; });
    });

    applyColumnVisibility(table, hidden);
}

/* ───────────────────────────────────────────────────────────────────────────
 * 5. Copy
 *
 * Copies what the user can see — hidden columns and filtered-out rows stay
 * out — as tab-separated text, which pastes straight into a spreadsheet.
 * ─────────────────────────────────────────────────────────────────────────── */

function tableAsText(table) {
    const rows = [];

    table.querySelectorAll('thead tr, tbody tr').forEach((tr) => {
        const cells = Array.from(tr.querySelectorAll('th, td')).filter(
            (cell) => !cell.classList.contains('is-column-hidden')
                && !cell.classList.contains('col-actions')
                && !cell.hasAttribute('colspan'),
        );

        if (cells.length === 0) return;

        rows.push(cells.map((cell) => cell.innerText.replace(/\s+/g, ' ').trim()).join('\t'));
    });

    return rows;
}

function initCopy() {
    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-copy-table]');
        if (!trigger) return;

        const table = visibleTable();
        if (!table) return;

        const rows = tableAsText(table);

        try {
            await navigator.clipboard.writeText(rows.join('\n'));
            toast(
                (trigger.getAttribute('data-copied-message') ?? ':count')
                    .replace(':count', String(Math.max(0, rows.length - 1))),
                'success',
            );
        } catch {
            toast(trigger.getAttribute('data-failed-message') ?? '', 'danger');
        }
    });
}

/* ───────────────────────────────────────────────────────────────────────────
 * 3. Queued import progress
 *
 * Polls the status endpoint until the run reaches a final state, then stops.
 * Poll failures back off rather than hammering a server that is already
 * struggling, and give up after a few consecutive errors.
 * ─────────────────────────────────────────────────────────────────────────── */

function setText(selector, value) {
    document.querySelectorAll(selector).forEach((el) => { el.textContent = value; });
}

function initImportPoll() {
    const config = window.ramsImportPoll;
    if (!config?.url) return;

    const bar = document.querySelector('[data-import-progress]');
    let delay = config.interval || 2500;
    let failures = 0;

    const tick = async () => {
        try {
            const response = await fetch(config.url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            failures = 0;
            delay = config.interval || 2500;

            setText('[data-import-status-label]', data.label);
            setText('[data-import-total]', new Intl.NumberFormat().format(data.total));
            setText('[data-import-created]', new Intl.NumberFormat().format(data.created));
            setText('[data-import-updated]', new Intl.NumberFormat().format(data.updated));
            setText('[data-import-skipped]', new Intl.NumberFormat().format(data.skipped));
            setText('[data-import-failed]', new Intl.NumberFormat().format(data.failed));

            if (bar) {
                bar.style.width = `${data.progress}%`;
                bar.closest('[role="progressbar"]')?.setAttribute('aria-valuenow', String(data.progress));
            }

            if (data.finished) {
                // Reload once so the finished screen shows the error report
                // link and the full summary the server renders.
                window.location.reload();
                return;
            }
        } catch {
            failures += 1;
            if (failures >= 5) return;
            delay = Math.min(delay * 2, 30000);
        }

        window.setTimeout(tick, delay);
    };

    window.setTimeout(tick, delay);
}

/* ───────────────────────────────────────────────────────────────────────────
 * Boot
 * ─────────────────────────────────────────────────────────────────────────── */

/* ───────────────────────────────────────────────────────────────────────────
 * 6. Permission groups
 *
 * A role screen carries well over a hundred checkboxes. Granting a whole
 * module should be one click, not twelve.
 * ─────────────────────────────────────────────────────────────────────────── */

function initPermissionGroups() {
    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-permission-toggle]');
        if (!toggle) return;

        const group = toggle.closest('fieldset');
        if (!group) return;

        const boxes = Array.from(group.querySelectorAll('input[type="checkbox"]:not([disabled])'));
        if (boxes.length === 0) return;

        // Toggle towards whichever state most of the group is not already in.
        const shouldCheck = boxes.some((box) => !box.checked);
        boxes.forEach((box) => { box.checked = shouldCheck; });
    });
}

function boot() {
    initPrint();
    initSelection();
    initImportPoll();
    initColumnVisibility();
    initCopy();
    initPermissionGroups();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { refreshSelectionState };
