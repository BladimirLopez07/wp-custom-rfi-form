
document.addEventListener('DOMContentLoaded', () => {
    // Initialize variables to hold campus options and a fragment
    const campusSelect = document.getElementById('CampusId');
    const campusFragment = document.createDocumentFragment();
    const programSelect = document.getElementById('ProgramId');
    const programFragment = document.createDocumentFragment();
    const aosSelect = document.querySelector("#AreaOfStudyID");

    // Function to clear options of a select element and keep the default one
    const clearOptions = (selectElement) => {
        const defaultOptionText = selectElement.options[0].textContent;
        selectElement.innerHTML = '';
        const defaultOption = document.createElement('option');
        defaultOption.value = 'default';
        defaultOption.textContent = defaultOptionText;
        selectElement.appendChild(defaultOption);
    }

    

    // Function to sort select options alphabetically, keeping default option at the top
    const sortSelectOptions = (selectElement) => {
        const options = Array.from(selectElement.options);
        const selectedOptionValue = selectElement.value;

        options.sort((a, b) => {
            if (a.value === 'default') return -1; // Move default option to the top
            if (b.value === 'default') return 1;
            return a.text.localeCompare(b.text);
        });

        selectElement.innerHTML = '';
        options.forEach(option => selectElement.add(option));

        // Ensure the previously selected option remains selected if it exists
        selectElement.value = selectedOptionValue || 'default';
    };

    // Populate campus options fragment
    Array.from(campusSelect.options).forEach(option => {
        campusFragment.appendChild(option.cloneNode(true));
    });

    // Populate program options fragment
    Array.from(programSelect.options).forEach(option => {
        programFragment.appendChild(option.cloneNode(true));
    });

    // Clear and sort selects
    clearOptions(campusSelect);
    
    if (aosSelect) { 
        clearOptions(programSelect);
        sortSelectOptions(aosSelect);
    }
    sortSelectOptions(programSelect);
    sortSelectOptions(campusSelect);
    

    // Ensure default options are selected
    campusSelect.value = 'default';
    programSelect.value = 'default';
    if (aosSelect) aosSelect.value = 'default';

    // Function to filter campus options based on selected program's data-campus attribute
    const filterCampusOptions = (selectedProgramOption) => {
        const selectedCampusIds = selectedProgramOption.getAttribute('data-campus').split(',');
        clearOptions(campusSelect);
        campusFragment.childNodes.forEach(option => {
            if (selectedCampusIds.includes(option.value)) {
                campusSelect.appendChild(option.cloneNode(true));
            }
        });
        sortSelectOptions(campusSelect);
    };

    // Function to filter program options based on selected Area of Study's data-aos attribute
    const filterProgramOptions = (selectedAosOption) => {
        const selectedAos = selectedAosOption.getAttribute('data-aos');
        clearOptions(programSelect);
        programFragment.childNodes.forEach(option => {
            if (selectedAos === option.getAttribute('data-aos')) {
                programSelect.appendChild(option.cloneNode(true));
            }
        });
        sortSelectOptions(programSelect);
    };

    // Listen for change event on ProgramId select
    programSelect.addEventListener('change', (event) => {
        const selectedOption = event.target.selectedOptions[0];
        if (selectedOption.value === 'default') {
            clearOptions(campusSelect);
        } else {
            filterCampusOptions(selectedOption);
        }
    });

    // Listen for change event on AreaOfStudyID select
    if (aosSelect) {
        aosSelect.addEventListener('change', (event) => {
            const selectedOption = event.target.selectedOptions[0];
            if (selectedOption.value === 'default') {
                clearOptions(programSelect);
                clearOptions(campusSelect);
            } else {
                filterProgramOptions(selectedOption);
            }
        });
    }

     /*===Start Form Prepopulation===*/
    const triggerChange = element => {
        const changeEvent = new Event('change', {
            'bubbles': true,
            'cancelable': true
        });
        element.dispatchEvent(changeEvent);
    }

    function normalizeString(str) {
        if (!str) return "";
        return str
            .toLowerCase()
            .replace(/&/g, "and")
            .replace(/[^a-z0-9]/g, "");
    }

    if (aosSelect && CustomRfiFormData.prepop_aos !== "") {
        const normalizedPrepopAos = normalizeString(CustomRfiFormData.prepop_aos);

        const aosOptionToSelect = Array.from(aosSelect.options).find(
            (option) => {
                const normalizedOptionText = normalizeString(
                    option.textContent || option.innerHTML
                );
                return normalizedOptionText === normalizedPrepopAos;
            }
        );

        if (aosOptionToSelect) {
            aosSelect.value = aosOptionToSelect.value;
            triggerChange(aosSelect);
        }
    }

    if(programSelect && CustomRfiFormData.prepop_program != '') {
        programSelect.value = CustomRfiFormData.prepop_program;
        triggerChange(programSelect);
    }

    if(campusSelect && CustomRfiFormData.prepop_campus != '') {
        campusSelect.value = CustomRfiFormData.prepop_campus;
        triggerChange(campusSelect);
    }

    /*===END Form Prepopulation===*/
});