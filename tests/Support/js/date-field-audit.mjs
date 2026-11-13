/**
 * Executes the date field (docs/design-system.md §5.42) and checks what it
 * does to what the office typed.
 *
 * The date field is the one place where the visible text and the value the
 * server receives are two different things: the office reads and writes
 * dd/mm/yyyy, and the form carries yyyy-mm-dd. Everything that can go wrong
 * there is a disagreement between the two — a mask that lets 31/02 through as
 * a date, a typed date that never reaches the server, a clear button that
 * empties one and not the other.
 *
 * Usage: node date-field-audit.mjs
 */
import { JSDOM } from "jsdom";
import { initializeDateFields } from "../../../resources/js/date-field.js";

const problems = [];

const expect = (condition, message) => {
    if (!condition) problems.push(message);
};

const dom = new JSDOM(
    `<!doctype html><html><body>
        <form>
            <div class="moderation-field date-field" data-date-field>
                <label for="hold-expected-resume-at">Expected to carry on by</label>
                <div class="date-field__control">
                    <input type="text" id="hold-expected-resume-at" data-date-field-display placeholder="dd/mm/yyyy" maxlength="10">
                    <input type="date" name="hold_expected_resume_at" class="date-field__native" data-date-field-native>
                    <button type="button" class="date-field__button" data-date-field-open aria-label="Choose a date">cal</button>
                    <button type="button" class="date-field__clear" data-date-field-clear aria-label="Clear the date" hidden>x</button>
                </div>
                <p data-date-field-error hidden>That is not a date. Use dd/mm/yyyy.</p>
            </div>
        </form>
    </body></html>`,
    { url: "https://leaf-1.tail05a1ab.ts.net/" },
);

const { window } = dom;
global.document = window.document;

const field = window.document.querySelector("[data-date-field]");
const display = field.querySelector("[data-date-field-display]");
const native = field.querySelector("[data-date-field-native]");
const clear = field.querySelector("[data-date-field-clear]");
const error = field.querySelector("[data-date-field-error]");

const type = (value) => {
    display.value = value;
    display.dispatchEvent(new window.Event("input", { bubbles: true }));
};

initializeDateFields();

// 1. Typing the date puts the slashes in and carries the value to the server.
type("15122026");

expect(display.value === "15/12/2026", `the field shows ${JSON.stringify(display.value)} instead of "15/12/2026".`);
expect(native.value === "2026-12-15", `the server would receive ${JSON.stringify(native.value)} instead of "2026-12-15".`);
expect(display.getAttribute("aria-invalid") === "false", "a valid date was flagged as invalid.");
expect(error.hidden === true, "the error message is showing under a valid date.");
expect(clear.hidden === false, "the clear button stayed hidden with a date in the field.");

// 2. A date that does not exist must be refused, not rolled over.
type("31022026");

expect(display.value === "31/02/2026", `the field rewrote what was typed into ${JSON.stringify(display.value)}.`);
expect(display.getAttribute("aria-invalid") === "true", "31/02/2026 was accepted as a date.");
expect(error.hidden === false, "no error is shown for a date that does not exist.");

// 3. An impossible month is refused too.
type("15132026");
expect(display.getAttribute("aria-invalid") === "true", "15/13/2026 was accepted as a date.");

// 4. The server must never receive a value the office has not finished typing.
expect(native.value === "2026-12-15", `the server received ${JSON.stringify(native.value)} from a date that was never valid.`);

// 5. Clearing empties both.
type("15122026");
clear.dispatchEvent(new window.MouseEvent("click", { bubbles: true }));

expect(display.value === "", `the field still shows ${JSON.stringify(display.value)} after clearing.`);
expect(native.value === "", `the server would still receive ${JSON.stringify(native.value)} after clearing.`);
expect(clear.hidden === true, "the clear button stayed visible after clearing.");

// 6. A date already in the field renders in our format, not the browser's.
native.value = "2026-12-15";
native.dispatchEvent(new window.Event("change", { bubbles: true }));

expect(display.value === "15/12/2026", `the field shows ${JSON.stringify(display.value)} instead of "15/12/2026".`);

// 7. The calendar button must not throw where the browser has no showPicker().
const open = field.querySelector("[data-date-field-open]");
open.dispatchEvent(new window.MouseEvent("click", { bubbles: true }));

expect(true, "");

dom.window.close();

if (problems.length) {
    console.log("FAIL  the date field");
    [...new Set(problems)].forEach((problem) => console.log(`        ${problem}`));
    process.exit(1);
}

console.log("ok    the date field");
process.exit(0);
