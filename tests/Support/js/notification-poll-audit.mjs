/**
 * Drives the notification poller against a stubbed fetch.
 *
 * The badge and feed only work if three things line up: the controller calls
 * the unread-count endpoint, writes the result into the badge, and re-fetches
 * the dropdown markup when the bell opens. Reading the source cannot prove the
 * async ordering or the DOM contract, so this runs the real controller.
 *
 * Usage: node notification-poll-audit.mjs <shell-js> [<label>]
 */
import { readFileSync } from "node:fs";
import { JSDOM } from "jsdom";

const [shellPath, label = shellPath] = process.argv.slice(2);

if (!shellPath) {
    console.error("usage: node notification-poll-audit.mjs <shell-js> [<label>]");
    process.exit(2);
}

const shell = readFileSync(shellPath, "utf8");

const html = `<!DOCTYPE html>
<html lang="en">
<body>
<nav id="navbar" data-site-navbar>
  <div class="nav-notification">
    <button type="button" class="nav-bell" id="notification-bell"
            data-notification-toggle
            data-notification-count-url="/notifications/unread-count"
            data-notification-feed-url="/notifications/feed"
            aria-controls="notification-dropdown"
            aria-expanded="false"
            aria-label="Notifications">
      <span class="nav-bell__badge" data-notification-badge hidden></span>
    </button>
    <div class="nav-dropdown" id="notification-dropdown"
         data-notification-dropdown aria-hidden="true">
      <a href="#old" class="notification-feed__item">Old item</a>
    </div>
  </div>
</nav>
<script>${shell}</script>
</body>
</html>`;

const calls = [];
const problems = [];

const dom = new JSDOM(html, {
    runScripts: "dangerously",
    pretendToBeVisual: true,
    url: "https://example.test/",
    beforeParse(window) {
        window.fetch = async (url) => {
            calls.push(String(url));

            if (String(url).includes("unread-count")) {
                return {
                    ok: true,
                    json: async () => ({ badge: "3", unread: 3 }),
                };
            }

            return {
                ok: true,
                text: async () => '<a href="#fresh" class="notification-feed__item">Fresh item</a>',
            };
        };
    },
});

const { window } = dom;
const { document } = window;
const wait = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
const bell = document.querySelector("#notification-bell");
const panel = document.querySelector("#notification-dropdown");
const badge = document.querySelector("[data-notification-badge]");

const expect = (condition, message) => {
    if (!condition) problems.push(message);
};

const click = (element) => {
    element.dispatchEvent(new window.MouseEvent("click", { bubbles: true, cancelable: true }));
};

// The initial resetShell() should already have polled once.
await wait(25);

expect(calls.some((url) => url.endsWith("/notifications/unread-count")), "the poller never called the unread-count endpoint on load.");
expect(badge?.textContent === "3", `the badge shows ${JSON.stringify(badge?.textContent)} instead of "3".`);
expect(badge?.hidden === false, "the badge stayed hidden after an unread count was returned.");
expect(
    bell?.getAttribute("aria-label") === "3 unread notifications",
    `the bell announces ${JSON.stringify(bell?.getAttribute("aria-label"))}.`,
);

// Opening the panel must refresh the feed, not just reuse the server render.
click(bell);
await wait(25);

expect(panel?.classList.contains("open"), "the bell did not open the panel.");
expect(calls.some((url) => url.endsWith("/notifications/feed")), "the bell never fetched the notification feed on open.");
expect(
    panel?.innerHTML.includes("Fresh item"),
    "the panel still shows the markup from the initial page load.",
);

dom.window.close();

if (problems.length) {
    console.log(`FAIL  ${label}`);
    [...new Set(problems)].forEach((problem) => console.log(`        ${problem}`));
    process.exit(1);
}

console.log(`ok    ${label}`);
process.exit(0);
