/**
 * Loads form markup and the plugin's scripts into a simulated browser (jsdom).
 *
 * Uncaught errors from the scripts, including errors thrown inside event listeners,
 * are collected in `errors` so a test can assert that none happened.
 */
const fs = require("node:fs");
const path = require("node:path");
const { JSDOM, VirtualConsole } = require("jsdom");

const scriptsDir = path.join(__dirname, "..", "..", "includes", "js");

function loadForm(formHtml, { data = {}, scripts = [] } = {}) {
    const errors = [];
    const virtualConsole = new VirtualConsole();
    virtualConsole.on("jsdomError", (error) => errors.push(error));

    const dom = new JSDOM(
        `<!doctype html><body><form id="rfiform" class="rfiform one-step">${formHtml}</form></body>`,
        { runScripts: "outside-only", virtualConsole }
    );
    const { window } = dom;

    // What the shortcode outputs as window.CustomRfiFormData
    window.CustomRfiFormData = {
        opt_group: "false",
        opt_group_order: [],
        form_steps: "1",
        prepop_campus: "",
        prepop_aos: "",
        prepop_program: "",
        program_first: "false",
        ...data,
    };

    for (const script of scripts) {
        window.eval(fs.readFileSync(path.join(scriptsDir, script), "utf8"));
    }
    window.document.dispatchEvent(new window.Event("DOMContentLoaded"));

    return { window, document: window.document, errors };
}

function choose(window, select, value) {
    select.value = value;
    select.dispatchEvent(new window.Event("change", { bubbles: true }));
}

function optionValues(select) {
    return Array.from(select.options).map((option) => option.value);
}

module.exports = { loadForm, choose, optionValues };
