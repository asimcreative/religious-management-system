import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Bootstrap 5 — only the plugins this application actually renders.
 *
 * The wholesale `import * as bootstrap from 'bootstrap'` bundle also ships
 * Carousel, Offcanvas, Popover, ScrollSpy, Tab, Toast and Button, none of
 * which appear in any view (the sidebar, toaster and spinners are custom).
 * Importing only what is used keeps behaviour identical and the bundle smaller.
 *
 * `window.bootstrap` stays exposed so data-bs-* markup and ui.js keep working
 * exactly as before.
 */
import Alert from 'bootstrap/js/dist/alert';
import Collapse from 'bootstrap/js/dist/collapse';
import Dropdown from 'bootstrap/js/dist/dropdown';
import Modal from 'bootstrap/js/dist/modal';
import Tooltip from 'bootstrap/js/dist/tooltip';

window.bootstrap = { Alert, Collapse, Dropdown, Modal, Tooltip };
