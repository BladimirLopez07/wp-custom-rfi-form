const test = require("node:test");
const assert = require("node:assert/strict");
const { loadForm, choose, optionValues } = require("./helpers");

const SCRIPT = "campus-first/custom-form-filtering.js";

// Campus and program selectors, without Area of Study: programs are grouped into optgroups.
const FORM = `
    <select id="CampusId">
        <option value="default">Select a Campus</option>
        <option value="C1">Online</option>
    </select>
    <select id="ProgramId">
        <option value="default">Select a Program</option>
        <option value="P1" data-campus="C1" data-aos="Health">BSN</option>
        <option value="P2" data-campus="C1" data-aos="Business">MBA</option>
        <option value="P3" data-campus="C1" data-aos="Arts">BA History</option>
    </select>`;

function groupLabels(select) {
    return Array.from(select.querySelectorAll("optgroup")).map((group) => group.label);
}

test("groups missing from the custom order are kept, after the ordered ones", () => {
    const { window, document, errors } = loadForm(FORM, {
        data: { opt_group: "true", opt_group_order: ["Business"] },
        scripts: [SCRIPT],
    });

    choose(window, document.getElementById("CampusId"), "C1");

    const programs = document.getElementById("ProgramId");
    assert.deepEqual(groupLabels(programs), ["Business", "Arts", "Health"]);
    assert.deepEqual(optionValues(programs), ["default", "P2", "P3", "P1"]);
    assert.deepEqual(errors, []);
});

test("without a custom order, groups are alphabetical", () => {
    const { window, document } = loadForm(FORM, { data: { opt_group: "true" }, scripts: [SCRIPT] });

    choose(window, document.getElementById("CampusId"), "C1");

    assert.deepEqual(groupLabels(document.getElementById("ProgramId")), ["Arts", "Business", "Health"]);
});

test("orderGroupKeys ignores unknown names in the custom order", () => {
    const { window } = loadForm(FORM, { scripts: [SCRIPT] });

    assert.deepEqual(
        Array.from(window.orderGroupKeys(["Health", "Arts", "Business"], ["Nursing", "Business"])),
        ["Business", "Arts", "Health"]
    );
});
