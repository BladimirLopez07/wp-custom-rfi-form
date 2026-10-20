const test = require("node:test");
const assert = require("node:assert/strict");
const { loadForm, choose, optionValues } = require("./helpers");

const SCRIPT = "program-first/custom-form-filtering.js";

const AREA_AND_PROGRAMS = `
    <select id="AreaOfStudyID">
        <option value="default">Select a program type</option>
        <option data-aos="Business" data-aos-id="1">Business</option>
        <option data-aos="Health" data-aos-id="2">Health</option>
    </select>
    <select id="ProgramId">
        <option value="default">Select a Program</option>
        <option value="P1" data-aos="Business" data-campus="C1,C2">MBA</option>
        <option value="P2" data-aos="Health" data-campus="C2">BSN</option>
    </select>`;

const CAMPUS_SELECT = `
    <select id="CampusId">
        <option value="default">Select a Campus</option>
        <option value="C1">Online</option>
        <option value="C2">Main</option>
    </select>`;

// When "Campus" is turned off, the form carries the configured campus in a hidden input.
const HIDDEN_CAMPUS = `<input type="hidden" name="CampusId" id="CampusId" value="C1">`;

test("works when the campus is a hidden field", () => {
    const { window, document, errors } = loadForm(AREA_AND_PROGRAMS + HIDDEN_CAMPUS, { scripts: [SCRIPT] });

    choose(window, document.getElementById("AreaOfStudyID"), "Business");
    choose(window, document.getElementById("ProgramId"), "P1");

    assert.deepEqual(errors, []);
    assert.deepEqual(optionValues(document.getElementById("ProgramId")), ["default", "P1"]);
    assert.equal(document.getElementById("CampusId").value, "C1", "The hidden campus must keep its value.");
});

test("with a campus selector, choosing a program narrows the campuses", () => {
    const { window, document, errors } = loadForm(AREA_AND_PROGRAMS + CAMPUS_SELECT, { scripts: [SCRIPT] });

    choose(window, document.getElementById("AreaOfStudyID"), "Health");
    choose(window, document.getElementById("ProgramId"), "P2");

    assert.deepEqual(errors, []);
    assert.deepEqual(optionValues(document.getElementById("CampusId")), ["default", "C2"]);
});
