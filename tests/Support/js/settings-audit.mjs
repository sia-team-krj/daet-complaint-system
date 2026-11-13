/**
 * Executes the settings page's script (docs/design-system.md §5.45) and checks
 * what it does to the markup.
 *
 * Everything else in this repo reads a template as text. That is the right
 * tool for most things and useless for this one: the save row only appears
 * when the form has really changed, and the name rule only speaks when the
 * office types a name that breaks it. Both are behaviour, and behaviour has
 * to be run.
 *
 * Usage: node settings-audit.mjs
 */
import { JSDOM } from "jsdom";
import { initializeSettings } from "../../../resources/js/settings.js";

const problems = [];

const expect = (condition, message) => {
    if (!condition) problems.push(message);
};

const dom = new JSDOM(
    `<!doctype html><html><body>
        <section class="tr-card settings-card" id="settings-profile">
            <form data-loading-submit method="POST" action="/settings/profile" data-settings-form>
                <div class="settings-field">
                    <label class="settings-field__label" for="settings-first-name">First name</label>
                    <input id="settings-first-name" type="text" name="first_name" class="settings-input" value="Pedro" data-name-field="first_name" data-name-message="A name may only contain letters, spaces, hyphens and dots.">
                    <p class="settings-field__error" data-name-error="first_name" hidden></p>
                </div>
                <div class="settings-field">
                    <label class="settings-field__label" for="settings-last-name">Last name</label>
                    <input id="settings-last-name" type="text" name="last_name" class="settings-input" value="Penduko" data-name-field="last_name" data-name-message="A name may only contain letters, spaces, hyphens and dots.">
                    <p class="settings-field__error" data-name-error="last_name" hidden></p>
                </div>
                <div class="settings-save-row">
                    <p class="settings-save-row__state" data-save-state role="status" aria-live="polite" data-dirty-message="You have unsaved changes."></p>
                    <div class="settings-save-row__actions">
                        <button type="button" class="tr-button tr-button--secondary" data-discard hidden>Discard</button>
                        <button type="submit" class="tr-button tr-button--primary" data-save-button>Save changes</button>
                    </div>
                </div>
            </form>
        </section>
    </body></html>`,
    { url: "https://leaf-1.tail05a1ab.ts.net/settings" },
);

const { window } = dom;
global.document = window.document;
// Node's own FormData will not take a jsdom form; the page's does.
global.FormData = window.FormData;

const first = window.document.querySelector('[data-name-field="first_name"]');
const last = window.document.querySelector('[data-name-field="last_name"]');
const firstError = window.document.querySelector('[data-name-error="first_name"]');
const state = window.document.querySelector('[data-save-state]');
const discard = window.document.querySelector('[data-discard]');
const card = window.document.querySelector('.settings-card');

const type = (input, value) => {
    input.value = value;
    input.dispatchEvent(new window.Event("input", { bubbles: true }));
};

initializeSettings();

// 1. A name that carries digits is refused, in the field, as you type.
type(first, "Juan123");

expect(!firstError.hidden, "A name with digits was accepted.");
expect(first.getAttribute("aria-invalid") === "true", "The invalid name was not marked for assistive technology.");

// 2. A name that breaks the rule is not blocked while it is still being typed
//    empty — the server answers the empty case, not the field.
type(first, "");

expect(firstError.hidden, "An empty name was flagged by the field; the server answers that case.");

// 3. A proper name passes.
type(first, "Juan Carlo");

expect(firstError.hidden, "A valid name was flagged.");
expect(first.getAttribute("aria-invalid") === null, "A valid name was left marked invalid.");

// 4. The save row says something is unsaved, and offers the discard.
expect(discard.hidden === false, "The save row did not offer to discard the changes.");
expect(card.classList.contains("is-dirty"), "The card did not know it had unsaved changes.");
expect(state.textContent.includes("unsaved"), "The save row did not say what was unsaved.");

// 5. Discard puts the form back the way it was.
type(last, "Something else");
discard.dispatchEvent(new window.MouseEvent("click", { bubbles: true }));

expect(last.value === "Penduko", "The discard button did not put the field back.");
expect(discard.hidden === true, "The discard button stayed visible after discarding.");

// 6. The save button is bound to the form, so the page can save it.
const form = window.document.querySelector('[data-settings-form]');
expect(form !== null, "The save row has no form to save.");

dom.window.close();

if (problems.length) {
    console.log("FAIL  the settings page");
    [...new Set(problems)].forEach((problem) => console.log(`        ${problem}`));
    process.exit(1);
}

console.log("ok    the settings page");
process.exit(0);
