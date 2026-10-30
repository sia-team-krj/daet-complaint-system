/**
 * Executes a page's inline scripts and reports anything that throws.
 *
 * Every other test in this repo reads a template as text and reasons about it,
 * which is how a temporal dead zone went unnoticed: a `const` referenced from a
 * function that is called before the declaration is reached throws a
 * ReferenceError at runtime, and no amount of reading the source will show it.
 * Worse, the exception aborts the rest of the initialiser, so the symptom
 * appeared somewhere else entirely — the form's map never appeared, with nothing
 * on the page mentioning a problem.
 *
 * So the script is actually run. Anything thrown during the first moments after
 * load is collected and reported, and the caller fails on it.
 *
 * Usage: node inline-script-audit.mjs <html-file> [<label>]
 */
import { readFileSync } from "node:fs";
import { JSDOM, VirtualConsole } from "jsdom";

const [htmlPath, label = htmlPath] = process.argv.slice(2);

if (!htmlPath) {
    console.error("usage: node inline-script-audit.mjs <html-file> [<label>]");
    process.exit(2);
}

const html = readFileSync(htmlPath, "utf8");

const problems = [];
const virtualConsole = new VirtualConsole();

// jsdom reports a thrown error inside a script either way depending on where it
// lands, so both channels are watched.
virtualConsole.on("jsdomError", (error) => {
    // A missing canvas or a blocked request is not a bug in the page.
    const message = String(error.message ?? error);
    if (/getContext|Not implemented|Could not load|not implemented/i.test(message)) return;
    problems.push(`jsdomError: ${message.split("\n")[0]}`);
});
virtualConsole.on("error", (...args) => {
    problems.push(`console.error: ${args.map(String).join(" ").split("\n")[0]}`);
});

const dom = new JSDOM(html, {
    runScripts: "dangerously",
    pretendToBeVisual: true,
    url: "https://leaf-1.tail05a1ab.ts.net/",
    virtualConsole,
});

const { window } = dom;

// jsdom has no layout engine, so give elements a size. Without it code that
// measures its own elements sees zero and takes a different path, which would
// hide bugs rather than find them.
for (const [property, value] of [["offsetHeight", 600], ["offsetWidth", 900], ["clientHeight", 600], ["clientWidth", 900]]) {
    Object.defineProperty(window.HTMLElement.prototype, property, {
        configurable: true,
        get() {
            return this.tagName === "BODY" ? value : value;
        },
    });
}
window.HTMLElement.prototype.getBoundingClientRect = () => ({
    x: 0, y: 0, top: 0, left: 0, right: 900, bottom: 600, width: 900, height: 600,
    toJSON() {},
});
window.scrollTo = () => {};

// Uncaught errors that escape a listener or a timer.
window.addEventListener("error", (event) => {
    if (event.error) {
        problems.push(`window.onerror: ${String(event.error.message ?? event.error).split("\n")[0]}`);
    }
});
process.on("unhandledRejection", (reason) => {
    problems.push(`unhandledRejection: ${String(reason).split("\n")[0]}`);
});

// The page's initialisers are scheduled on a timer, so give them room to run.
await new Promise((resolve) => setTimeout(resolve, 700));

// jsdom swallows nothing, but an exception inside a setTimeout callback surfaces
// as a jsdomError above. Close the window cleanly either way.
dom.window.close();

if (problems.length) {
    console.log(`FAIL  ${label}`);
    [...new Set(problems)].forEach((p) => console.log(`        ${p}`));
    process.exit(1);
}

console.log(`ok    ${label}`);
process.exit(0);
