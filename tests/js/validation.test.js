const test = require("node:test");
const assert = require("node:assert/strict");
const { loadForm } = require("./helpers");

const SCRIPT = "custom-form-validation.js";

const FORM = `
    <div class="field"><div class="inner"><input id="firstname" type="text"></div></div>
    <div class="field"><div class="inner"><input id="company" type="text" aria-label="Company" data-rfi-custom="1" required></div></div>`;

function errorFor(input) {
    const message = input.parentNode.querySelector(".error-message");
    return message ? message.textContent : null;
}

test("names keep accents, apostrophes and spaces", () => {
    const { window, document } = loadForm(FORM, { scripts: [SCRIPT] });
    const input = document.getElementById("firstname");

    for (const name of ["José", "Zoë", "O'Connor", "O’Connor", "Mary Ann", "Anne-Marie"]) {
        input.value = name;
        window.validateFirstLastName(input);
        assert.equal(input.value, name);
        assert.equal(errorFor(input), null, `${name} should be valid.`);
    }
});

test("digits and symbols are removed from names, and one letter is not enough", () => {
    const { window, document } = loadForm(FORM, { scripts: [SCRIPT] });
    const input = document.getElementById("firstname");

    input.value = "Jo3!";
    window.validateFirstLastName(input);
    assert.equal(input.value, "Jo");

    input.value = "J";
    window.validateFirstLastName(input);
    assert.equal(errorFor(input), "Please enter your first name.");
});

test("required custom fields use the generic validator", () => {
    const { window, document } = loadForm(FORM, { scripts: [SCRIPT] });
    const input = document.getElementById("company");

    window.getValidator(input)(input);
    assert.equal(errorFor(input), "Please fill in Company.");

    input.value = "Acme";
    window.getValidator(input)(input);
    assert.equal(errorFor(input), null);
});
