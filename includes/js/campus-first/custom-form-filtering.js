/*
This script dynamically updates the options in the "Area of Study" and "Program" select dropdowns based on the choice made in the "Campus" dropdown.

Key Functionalities:

Sorting: The script can sort option elements alphabetically.
Grouping: Based on attributes, the script can group options, useful for creating optgroup in select elements.
Dynamic Updating: As the user makes a selection in the "Campus" dropdown, the script updates the other dropdowns (Area of Study and/or Program) to reflect relevant options. Similarly, choosing an option in "Area of Study" updates the "Program" dropdown.
Default Behavior: The script also contains a behavior when the page loads, setting the appropriate options in the dropdowns based on the number of select elements present on the page.
Assumptions & External Dependencies:

The script assumes the presence of certain DOM elements, notably the select elements.
The decision to create optgroup in the dropdowns is based on an external object CustomRfiFormData.
The options in the select dropdowns have custom data attributes (data-campus and data-aos) that the script uses to determine the grouping.
*/

// This function sorts a list of option elements alphabetically based on their text content.
function sortOptions(options) {
    return options.sort((a, b) => a.text.localeCompare(b.text));
}

// Groups a list of option elements by a specified attribute.
function groupOptionsByAttribute(options, attribute) {
    return options.reduce((groups, option) => {
        const key = option.getAttribute(attribute);
        if (!groups[key]) {
            groups[key] = [];
        }
        groups[key].push(option);
        return groups;
    }, {});
}

// Updates the options of a given select element.
// Can optionally group the options into optgroups.
function updateOptions(selectElement, optionList = [], defaultOption, createOptGroups = false) {
    if (!selectElement) return;

    selectElement.innerHTML = ''; // Clear existing options

    if (defaultOption) {
        // Add the default option (e.g., 'Select an option...')
        selectElement.add(defaultOption.cloneNode(true));
    }

    // If optgroup creation is required, group the options and add them.
    if (createOptGroups && optionList.length) {
        const groupedOptions = groupOptionsByAttribute(optionList, 'data-aos');
        
        let sortedKeys;
        if (CustomRfiFormData.opt_group_order && CustomRfiFormData.opt_group_order.length > 0) {
            // Sort optgroups based on opt_group_order array
            sortedKeys = CustomRfiFormData.opt_group_order.filter(key => groupedOptions.hasOwnProperty(key));
        } else {
            // Alphabetically sort optgroups
            sortedKeys = Object.keys(groupedOptions).sort();
        }
    
        sortedKeys.forEach(key => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = key;
            sortOptions(groupedOptions[key]).forEach(option => optgroup.appendChild(option.cloneNode(true)));
            selectElement.appendChild(optgroup);
        });
    } else {
        // Just add the sorted options directly
        sortOptions(optionList).forEach(option => selectElement.add(option));
    }    

    selectElement.selectedIndex = 0;  // Default to first option
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('rfiform');
    const campusSelect = form.querySelector('#CampusId');
    const areaSelect = form.querySelector('#AreaOfStudyID');
    const programSelect = form.querySelector('#ProgramId');
    const shouldCreateOptGroups = CustomRfiFormData.opt_group === 'true';

    const areaOptions = areaSelect ? Array.from(areaSelect.options) : [];
    const defaultAreaOption = areaOptions.shift();

    const programOptions = programSelect ? Array.from(programSelect.options) : [];
    const defaultProgramOption = programOptions.shift();

    const campusAreaMap = groupOptionsByAttribute(areaOptions, 'data-campus');
    const campusProgramMap = groupOptionsByAttribute(programOptions, 'data-campus');
    const aosProgramMap = groupOptionsByAttribute(programOptions, 'data-aos');

    form.addEventListener('change', (event) => {
        const target = event.target;
    
        if (target === campusSelect) {
            const selectedCampus = target.value;
            if (!areaSelect && programSelect) {
                updateOptions(programSelect, campusProgramMap[selectedCampus] || [], defaultProgramOption, shouldCreateOptGroups);
            } else {
                updateOptions(areaSelect, campusAreaMap[selectedCampus] || [], defaultAreaOption);
                // Reset the program select box until Area of Study is selected
                updateOptions(programSelect, [], defaultProgramOption);
            }
        } else if (target === areaSelect) {
            const selectedAOS = target.value;
            const selectedCampus = campusSelect.value;
            // Filter programs by both Campus and Area of Study
            let filteredPrograms = [];
            if (aosProgramMap[selectedAOS]) {
                filteredPrograms = aosProgramMap[selectedAOS].filter(programOption => {
                    return programOption.getAttribute('data-campus') === selectedCampus;
                });
            }
            updateOptions(programSelect, filteredPrograms, defaultProgramOption, shouldCreateOptGroups);
        }
    });

    // Get all select elements in the form
    const allSelects = form.querySelectorAll("select");

    // Filter selects that are relevant
    const relevantSelects = Array.from(allSelects).filter(select => {
        const id = select.id;
        return id === "CampusId" || id === "AreaOfStudyID" || id === "ProgramId";
    });

    // Get the count of relevant select fields
    const selectCount = relevantSelects.length;

    // Switch based on the count of relevant select fields
    switch (selectCount) {
        case 3:
            updateOptions(areaSelect, [], defaultAreaOption);
            updateOptions(programSelect, [], defaultProgramOption);
            break;
        case 2:
            if (areaSelect) {
                updateOptions(areaSelect, areaOptions, defaultAreaOption);
                updateOptions(programSelect, [], defaultProgramOption);
            } else {
                updateOptions(programSelect, [], defaultProgramOption);
            }
            break;
        case 1:
            updateOptions(programSelect, programOptions, defaultProgramOption, shouldCreateOptGroups);
            break;
    }

/*===Start Form Prepopulation===*/
    const triggerChange = (element) => {
        const changeEvent = new Event("change", {
            bubbles: true,
            cancelable: true,
        });
        element.dispatchEvent(changeEvent);
    };

    function normalizeString(str) {
        if (!str) return "";
        return str
            .toLowerCase()
            .replace(/&/g, "and")
            .replace(/[^a-z0-9]/g, "");
    }

    if (campusSelect && CustomRfiFormData.prepop_campus != "") {
        campusSelect.value = CustomRfiFormData.prepop_campus;
        triggerChange(campusSelect);
    }

    if (areaSelect && CustomRfiFormData.prepop_aos !== "") {
        const normalizedPrepopAos = normalizeString(CustomRfiFormData.prepop_aos);

        const aosOptionToSelect = Array.from(areaSelect.options).find(
            (option) => {
                const normalizedOptionText = normalizeString(
                    option.textContent || option.innerHTML
                );
                return normalizedOptionText === normalizedPrepopAos;
            }
        );

        if (aosOptionToSelect) {
            areaSelect.value = aosOptionToSelect.value;
            triggerChange(areaSelect);
        }
    }

    if (programSelect && CustomRfiFormData.prepop_program != "") {
        programSelect.value = CustomRfiFormData.prepop_program;
        triggerChange(programSelect);
    }
    /*===END Form Prepopulation===*/
});