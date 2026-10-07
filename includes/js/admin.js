document.addEventListener('DOMContentLoaded', function() {
// Define configurations for conditional fields
const fieldConfigurations = [
    {
        selectSelector: 'select#campus_type',
        expectedValue: 'single',
        fieldSelector: '#campus_id',
        type: 'select'
    },
    {
        selectSelector: 'input[name="custom_rfi_form_settings[form_fields][]"][value="highestEducation"]',
        expectedValue: true,
        fieldSelector: '#highest_education',
        type: 'checkbox'
    },
    {
        selectSelector: 'input[name="custom_rfi_form_settings[form_fields][]"][value="startTerm"]',
        expectedValue: true,
        fieldSelector: '#start_term',
        type: 'checkbox'
    },
    {
        selectSelector: 'input[name="custom_rfi_form_settings[program_option_group]"]',
        expectedValue: 'true',
        fieldSelector: '#opt_group_order_repeater',
        type: 'radio'
    },
    {
        selectSelector: 'input[name="custom_rfi_form_settings[thank_you_url_type]"]',
        expectedValue: 'external',
        fieldSelector: '#external_thank_you_url',
        type: 'radio'
    },
    {
        selectSelector: 'input[name="custom_rfi_form_settings[thank_you_url_type]"]',
        expectedValue: 'internal',
        fieldSelector: '#internal_thank_you_url',
        type: 'radio'
    },
    // Add more configurations as needed
];

// Function to toggle the visibility of fields based on configurations
function toggleConditionalFields() {
    fieldConfigurations.forEach(function(config) {
        let showField = false;
        let inputElement = null;

        if (config.type === 'radio') {
            let radioElements = document.querySelectorAll(config.selectSelector);
            radioElements.forEach(function(radio) {
                if (radio.checked) {
                    inputElement = radio;
                }
            });
            if (inputElement) {
                showField = (inputElement.value === config.expectedValue);
            }
        } else {
            inputElement = document.querySelector(config.selectSelector);
            if (inputElement) {
                if (config.type === 'select') {
                    showField = (inputElement.value === config.expectedValue);
                } else if (config.type === 'checkbox') {
                    showField = (inputElement.checked === config.expectedValue);
                }
            }
        }

        // Get the field element to show/hide
        let fieldElement = document.querySelector(config.fieldSelector);
        if (fieldElement) {
            // Try to find the closest 'tr' (table row), if applicable
            let fieldRow = fieldElement.closest('tr');
            if (fieldRow) {
                fieldRow.style.display = showField ? '' : 'none';
            } else {
                // If not inside a 'tr', toggle the field element itself
                fieldElement.style.display = showField ? '' : 'none';
            }
        }
    });
}

// Initial check on page load
toggleConditionalFields();

// Add event listeners to each input element
fieldConfigurations.forEach(function(config) {
    if (config.type === 'radio') {
        let inputElements = document.querySelectorAll(config.selectSelector);
        inputElements.forEach(function(inputElement) {
            inputElement.addEventListener('change', toggleConditionalFields);
        });
    } else {
        let inputElement = document.querySelector(config.selectSelector);
        if (inputElement) {
            inputElement.addEventListener('change', toggleConditionalFields);
        }
    }
});


    // Handling repeater fields
    let repeater = document.getElementById('opt_group_order_repeater');
    if (repeater) {
        // Attach the 'add-row' click event to the button directly
        let addRowButton = repeater.querySelector('.add-row');
        if (addRowButton) {
            addRowButton.addEventListener('click', function(e) {
                e.preventDefault();
                let table = e.target.closest('table');
                let tbody = table.querySelector('tbody');
                let lastRow = tbody.querySelector('tr:last-child');
                let newRow = lastRow.cloneNode(true);

                // Clear the values in the new row
                newRow.querySelectorAll('input').forEach(function(input) {
                    input.value = '';
                });
                newRow.querySelectorAll('p.option-index').forEach(function(indexText) {
                    indexText.textContent = parseInt(indexText.textContent) + 1;
                });

                tbody.appendChild(newRow);
            });
        }

        // Use event delegation for the 'remove-row' buttons
        repeater.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                e.preventDefault();
                let row = e.target.closest('tr');
                let tbody = row.closest('tbody');
                if (tbody.querySelectorAll('tr').length > 1) {
                    row.remove();
                } else {
                    alert('You need at least one row.');
                }
            }
        });
    }
});
