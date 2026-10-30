/**
 * Drives the real navbar shell controller and reports any surface left open.
 *
 * This controller is a single delegated click listener, so every behaviour worth
 * guarding lives inside one `if` block per button. Reading the source cannot
 * tell you whether the calls in a block are the right ones — only running it
 * can. That is how the notification panel came to survive the hamburger: the
 * branch closed the account menus and the notification branch closed the mobile
 * menu, but the hamburger branch never closed the notification panel, so both
 * were left open. Every source-reading test in the suite passed while the bar
 * behaved wrongly on a phone.
 *
 * So the real `shell.js` is loaded into jsdom against the real markup contract
 * (the data attributes and `aria-controls` wiring the Blade partials emit) and
 * the surfaces are opened and closed by dispatching real clicks.
 *
 * Usage: node shell-controller-audit.mjs <shell-js> [<label>]
 */
import { readFileSync } from "node:fs";
import { JSDOM, VirtualConsole } from "jsdom";

const [shellPath, label = shellPath] = process.argv.slice(2);

if (!shellPath) {
    console.error("usage: node shell-controller-audit.mjs <shell-js> [<label>]");
    process.exit(2);
}

const shell = readFileSync(shellPath, "utf8");

// The navbar contract, reduced to the four surfaces the controller arbitrates
// between. Classes and ids are the ones the Blade partials and the stylesheet
// agree on, because the controller finds surfaces by `aria-controls`.
const html = `<!DOCTYPE html>
<html lang="en">
<body>
<nav id="navbar" data-site-navbar>
  <div class="nav-notification">
    <button type="button" class="nav-bell" id="notification-bell"
            data-notification-toggle aria-controls="notification-dropdown" aria-expanded="false"></button>
    <div class="nav-dropdown nav-notification__dropdown nav-dropdown-wrap"
         id="notification-dropdown" data-notification-dropdown aria-hidden="true">
      <a href="#one" class="notification-feed__item">One</a>
    </div>
  </div>
  <ul class="nav-actions">
    <li class="nav-dropdown-wrap">
      <button type="button" class="nav-user-btn" data-user-menu-toggle
              aria-controls="user-dropdown" aria-expanded="false"></button>
      <div class="nav-dropdown" id="user-dropdown" data-user-dropdown aria-hidden="true"></div>
    </li>
  </ul>
  <div class="nav-mobile-right">
    <button type="button" data-mobile-menu-toggle
            aria-controls="mobile-menu" aria-expanded="false"></button>
  </div>
  <div id="mobile-menu" data-mobile-menu aria-hidden="true" inert>
    <a href="#" id="mobile-link">Dashboard</a>
  </div>
</nav>
<main><p id="page">Page content</p></main>
<script>${shell}</script>
</body>
</html>`;

const problems = [];
const virtualConsole = new VirtualConsole();
virtualConsole.on("jsdomError", (error) => {
    const message = String(error.message ?? error);
    if (/getContext|Not implemented|Could not load|not implemented/i.test(message)) return;
    problems.push(`jsdomError: ${message.split("\n")[0]}`);
});

const dom = new JSDOM(html, {
    runScripts: "dangerously",
    pretendToBeVisual: true,
    url: "https://leaf-1.tail05a1ab.ts.net/",
    virtualConsole,
});

const { window } = dom;
const { document } = window;
const $ = (selector) => document.querySelector(selector);
const bell = $("#notification-bell");
const panel = $("#notification-dropdown");
const accountButton = $("[data-user-menu-toggle]");
const accountDropdown = $("#user-dropdown");
const hamburger = $("[data-mobile-menu-toggle]");
const drawer = $("#mobile-menu");

const click = (element) =>
    element.dispatchEvent(new window.MouseEvent("click", { bubbles: true, cancelable: true }));

const isOpen = (element, className) => element.classList.contains(className);

const describe = (element, className) =>
    `${element.id || element.tagName.toLowerCase()}:${isOpen(element, className) ? "open" : "closed"}`;

/** Asserts the whole bar is in the expected state after a step. */
const expectBar = (step, expected) => {
    const actual = {
        panel: isOpen(panel, "open"),
        account: isOpen(accountDropdown, "open"),
        drawer: isOpen(drawer, "is-open"),
    };

    for (const [surface, shouldBeOpen] of Object.entries(expected)) {
        if (actual[surface] !== shouldBeOpen) {
            problems.push(
                `${step}: ${surface} should be ${shouldBeOpen ? "open" : "closed"}, `
                + `it was ${actual[surface] ? "open" : "closed"} `
                + `(panel ${describe(panel, "open")}, account ${describe(accountDropdown, "open")}, `
                + `drawer ${describe(drawer, "is-open")}).`,
            );
        }
    }
};

const noneOpen = { panel: false, account: false, drawer: false };

// Nothing is open on load.
expectBar("on load", noneOpen);

// The bell opens its own panel and nothing else.
click(bell);
expectBar("bell tapped", { panel: true, account: false, drawer: false });

// The reported bug: the drawer must not open on top of a live panel.
click(hamburger);
expectBar("hamburger tapped with the panel open", { panel: false, account: false, drawer: true });

// The mirror image, which already worked: the bell closes the drawer.
click(bell);
expectBar("bell tapped with the drawer open", { panel: true, account: false, drawer: false });

// The account popover evicts the panel.
click(accountButton);
expectBar("account tapped with the panel open", { panel: false, account: true, drawer: false });

// The bell evicts the account popover.
click(bell);
expectBar("bell tapped with the account popover open", { panel: true, account: false, drawer: false });

// A second tap closes the surface it opened.
click(bell);
expectBar("bell tapped twice", noneOpen);

// A tap outside the bar closes whatever is open.
click(bell);
click($("#page"));
expectBar("tapped outside", noneOpen);

// Escape closes it too, and does not depend on focus being inside the panel.
click(bell);
document.dispatchEvent(new window.KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
expectBar("escape pressed", noneOpen);

// Tapping the account button twice returns the bar to nothing open.
click(accountButton);
click(accountButton);
expectBar("account tapped twice", noneOpen);

// Every closed surface is also announced closed to assistive technology.
click(bell);
click(hamburger);
if (bell.getAttribute("aria-expanded") !== "false" || panel.getAttribute("aria-hidden") !== "true") {
    problems.push(
        `the bell still reports expanded after the drawer closed it `
        + `(aria-expanded=${bell.getAttribute("aria-expanded")}, aria-hidden=${panel.getAttribute("aria-hidden")}).`,
    );
}

// The document-level lock that stops the page scrolling behind the drawer.
if (!document.documentElement.classList.contains("mobile-menu-open")) {
    problems.push("the drawer opened without locking document scroll.");
}

dom.window.close();

if (problems.length) {
    console.log(`FAIL  ${label}`);
    [...new Set(problems)].forEach((problem) => console.log(`        ${problem}`));
    process.exit(1);
}

console.log(`ok    ${label}`);
process.exit(0);
