/**
 * Drives the filing form the way a resident does and reads back what the
 * "Ready to submit" row says.
 *
 * The row is the last thing a resident sees before sending, so it has to show
 * them something they can recognise. It used to show a pair of decimal degrees,
 * and then it showed a barangay derived from a single reference point per
 * barangay — which is wrong wherever a point sits further from its own
 * barangay's reference point than from a neighbour's, and Daet is only about
 * 10 km across. Residents were shown the wrong area for their own home.
 *
 * So the row now shows the barangay the resident named, and the important
 * guarantee is the negative one: no pin, however badly placed, may override it.
 *
 * Usage: node filing-summary-audit.mjs <html-file>
 */
import { readFileSync } from "node:fs";
import { JSDOM, VirtualConsole } from "jsdom";

const htmlPath = process.argv[2];
if (!htmlPath) {
    console.error("usage: node filing-summary-audit.mjs <html-file>");
    process.exit(2);
}

const html = readFileSync(htmlPath, "utf8");

const problems = [];
const virtualConsole = new VirtualConsole();
virtualConsole.on("jsdomError", (error) => {
    const message = String(error.message ?? error);
    if (/getContext|Not implemented|Could not load/i.test(message)) return;
    problems.push(`jsdomError: ${message.split("\n")[0]}`);
});

const dom = new JSDOM(html, {
    runScripts: "dangerously",
    pretendToBeVisual: true,
    url: "https://leaf-1.tail05a1ab.ts.net/complaints/create",
    virtualConsole,
});

const { window } = dom;
const { document } = window;

for (const [property, value] of [["offsetHeight", 600], ["offsetWidth", 900]]) {
    Object.defineProperty(window.HTMLElement.prototype, property, {
        configurable: true, get: () => value,
    });
}
window.HTMLElement.prototype.getBoundingClientRect = () => ({
    x: 0, y: 0, top: 0, left: 0, right: 900, bottom: 600, width: 900, height: 600,
    toJSON() {},
});
window.scrollTo = () => {};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const row = () => document.getElementById("review-location")?.textContent?.trim() ?? null;
const field = (id) => document.getElementById(id);

const check = (label, ok, detail) => {
    if (!ok) problems.push(`${label}: ${detail}`);
    return ok;
};

/** Type into a field the way a resident does, and let the summary react. */
const type = async (id, value) => {
    field(id).value = value;
    field(id).dispatchEvent(new window.Event("input", { bubbles: true }));
    await sleep(50);
};

/** Drop a pin without touching anything else. */
const dropPin = async (lat, lng) => {
    await type("latitude", String(lat));
    await type("longitude", String(lng));
};

await sleep(250);

check("summary row exists", row() !== null, "#review-location is not rendered");
check("the form asks for a barangay", !!field("barangay"), "#barangay is not on the form");

const prefilled = field("barangay")?.value?.trim() ?? "";
check(
    "the barangay field comes prefilled from the account",
    prefilled !== "",
    "It is empty, so a resident has to type what they already told us at registration.",
);

// The stated barangay is what the row shows.
await type("barangay", "Gahonon");
check(
    "the row shows the barangay the resident named",
    row() === "Gahonon",
    `row said ${JSON.stringify(row())}`,
);

// The negative guarantee, and the reason this audit exists. Points chosen to
// sit in other barangays, including the far side of the municipality.
const elsewhere = [
    ["Dogongan", 14.10300, 122.90602],
    ["Calasgasan", 14.08588, 122.93686],
    ["Bagasbas", 14.13720, 122.98160],
    ["Alawihao", 14.10970, 122.93031],
];

for (const [name, lat, lng] of elsewhere) {
    await dropPin(lat, lng);
    check(
        `a pin in ${name} does not override the resident's own barangay`,
        row() === "Gahonon",
        `row said ${JSON.stringify(row())}; #barangay value=${JSON.stringify(field("barangay")?.value)}; lat=${JSON.stringify(field("latitude")?.value)}`,
    );
}

// A landmark is more specific than a barangay, so it is shown too.
await type("address_text", "In front of Daet Public Market");
check(
    "a written landmark is shown alongside the barangay",
    row() === "Gahonon · In front of Daet Public Market",
    `row said ${JSON.stringify(row())}`,
);

// Nothing at all.
await type("barangay", "");
await type("address_text", "");
await type("latitude", "");
await type("longitude", "");
check(
    "an empty form says it has no location",
    row() === "No location added",
    `row said ${JSON.stringify(row())}`,
);

// Never a coordinate, in any state.
const states = [
    ["Gahonon", 14.12373, 122.94668],
    ["Dogongan", 14.10300, 122.90602],
    ["", 14.5995, 120.9842],
];

for (const [barangay, lat, lng] of states) {
    await type("barangay", barangay);
    await type("address_text", "");
    await dropPin(lat, lng);
    const shown = row() ?? "";
    check(
        `no coordinates leak into the row (barangay ${JSON.stringify(barangay)})`,
        !/\b1[34]\.\d{2,}|\b12[0-2]\.\d{2,}/.test(shown),
        `row showed ${JSON.stringify(shown)}`,
    );
}

dom.window.close();

if (problems.length) {
    console.log("FAIL  the Ready to submit row");
    [...new Set(problems)].slice(0, 12).forEach((p) => console.log(`        ${p}`));
    if (problems.length > 12) console.log(`        ... and ${problems.length - 12} more`);
    process.exit(1);
}

console.log("ok    the Ready to submit row shows the resident's own barangay, and no pin overrides it");
process.exit(0);
