/**
 * This script is responsible for handling form interactions for the RFI form.
 * 
 * Overview:
 * 1. Constants & Variables: Initial setup of important elements and data structures.
 * 
 * 2. Validation Strategies: The core logic of this script revolves around the validation
 *    of various form inputs including names, phone numbers, emails, and zip codes.
 *    For select elements, dependencies between different dropdowns are also considered.
 *    For example, a user should select a campus before they can select a program.
 * 
 * 3. Two-Step Form Handling: This form has an optional two-step submission process. 
 *    This is handled by toggling the visibility of different form parts based on user interactions.
 * 
 * 4. Event Handlers: Event listeners are set up to handle form submissions, focusing on select elements, 
 *    and transitioning between the two-steps of the form.
 *    
 * 5. On Form Submit: The form performs validation on all its fields. If the validation passes,
 *    the phone number is stripped of non-numeric characters, and then the form is submitted.
 *    If there are validation errors, error messages are displayed next to the problematic input fields.
 *
 * Note: This script heavily relies on the presence of specific HTML structures and IDs.
 *       Ensure the associated HTML does not deviate significantly from its current structure 
 *       without updating this script.
 */

// ********** Constants & Variables **********//
const rfiForm = document.querySelector("#rfiform");
const twoStepForm = document.querySelector("#rfiform.two-step");
let nextStepButton, stepOne, stepTwo, goback;

if (twoStepForm) {
    nextStepButton = twoStepForm.querySelector('.next-step');
    stepOne = twoStepForm.querySelector('.step-one');
    stepTwo = twoStepForm.querySelector('.step-two');
    goback = twoStepForm.querySelector('.goback');
}

let validatedInputs = {};

const defaultMessages = {
    CampusId: "Please select a location.",
    AreaOfStudyID: "Please select a program type.",
    ProgramId: "Please select a program.",
    firstname: "Please enter your first name.",
    lastname: "Please enter your last name.",
    email: "Please enter a valid email.",
    dayphone: "Please enter a valid phone number.",
    zipcode: "Please enter a valid zip code.",
    birthdate: "Please enter a valid birthdate.",
    HighestLevelofEducationCompleted: "Please select education level.",
    StartTermDate: "Please select a start term."
};

const validationStrategies = {
    'dayphone': validatePhone,
    'email': validateEmail,
    'firstname': validateFirstLastName,
    'lastname': validateFirstLastName,
    'postalcode': validateZip,
    'CampusId': validateSelects,
    'AreaOfStudyID': validateSelects,
    'ProgramId': validateSelects,
    'BirthDate': validateDOB,
    'HighestLevelofEducationCompleted': validateSelects,
    'StartTermDate': validateSelects,
};

let selectDependencies = {};

if (CustomRfiFormData.program_first === "true") {
    selectDependencies = {
        'ProgramId': ['AreaOfStudyID'],
        'CampusId': ['ProgramId', 'AreaOfStudyID']
    };
} else {
    selectDependencies = {
        'ProgramId': ['AreaOfStudyID', 'CampusId'],
        'AreaOfStudyID': ['CampusId']
    };
}

// Error handling 
function handleErrorMessage(inputField, errorMessages, sanitizedInputForStorage) {
    let errorMessageContainer = inputField.parentNode.querySelector('.error-message');
    let errorIndicator = inputField.parentNode.querySelector('.error');
    
    if (!errorMessageContainer) {
        errorMessageContainer = document.createElement('div');
        errorMessageContainer.classList.add('error-message');
        inputField.parentNode.appendChild(errorMessageContainer);
    }

    if (!errorIndicator) {
        errorIndicator = document.createElement('div');
        errorIndicator.classList.add('error');
        errorIndicator.textContent = '!';
        inputField.parentNode.appendChild(errorIndicator);
    }

    if (errorMessages.length > 0) {
        errorMessageContainer.textContent = errorMessages[0];
        delete validatedInputs[inputField.id];
    } else {
        errorMessageContainer.remove();
        errorIndicator.remove();
        validatedInputs[inputField.id] = sanitizedInputForStorage || inputField.value;
    }
}

// First and Last name validations
function validateFirstLastName(inputField) {
    let inputValue = inputField.value;
    let errorMessages = [];

    // Remove digits
    if (/\d/.test(inputValue)) {
        inputValue = inputValue.replace(/\d/g, "");
        inputField.value = inputValue;
    }

    // Check for minimum length
    if (inputValue.length < 2) {
        errorMessages.push(defaultMessages[inputField.id]);
    }

    // Limit maximum length
    if (inputValue.length > 25) {
        inputValue = inputValue.slice(0, 25);
        inputField.value = inputValue;
    }

    // Remove invalid characters
    if (/[^A-Za-z\s'-]/.test(inputValue)) {
        inputValue = inputValue.replace(/[^A-Za-z\s'-]/g, "");
        inputField.value = inputValue;
    }

    // Trim leading spaces
    if (/^\s/.test(inputValue)) {
        inputValue = inputValue.trimStart();
        inputField.value = inputValue;
    }

    // Validate for at least two alphabetic characters
    if (!/[A-Za-z].*[A-Za-z]/.test(inputValue)) {
        errorMessages.push(defaultMessages[inputField.id]);
    }

    // Sanitize input for storage
    let sanitizedInputForStorage = inputValue.replace(/['-\s]/g, "");
    handleErrorMessage(inputField, errorMessages, sanitizedInputForStorage);
}


// Phone number validation
function validatePhone(inputField) {
    let inputValue = inputField.value;
    
    // Strip out all unwanted characters except for digits, parentheses, spaces, and hyphens.
    let strippedValue = inputValue.replace(/[^\d\(\) -]/g, '');
    
    // If the stripped value differs from the original input value, update the input field
    if (strippedValue !== inputValue) {
        inputField.value = strippedValue;
    }
    
    // Extract just the digits from the stripped value.
    let sanitizedInput = strippedValue.replace(/\D/g, '');
    
    let formattedInput;
    if (sanitizedInput.length > 0) {
        if (sanitizedInput.length < 4) {
            formattedInput = `(${sanitizedInput}`;
        } else if (sanitizedInput.length < 7) {
            formattedInput = `(${sanitizedInput.substring(0, 3)}) ${sanitizedInput.substring(3)}`;
        } else {
            formattedInput = `(${sanitizedInput.substring(0, 3)}) ${sanitizedInput.substring(3, 6)}-${sanitizedInput.substring(6, 10)}`;
            sanitizedInput = sanitizedInput.substring(0, 10);  // Limit to 10 digits
        }

        inputField.value = formattedInput;
    }

    // Check if phone number is of valid length
    if (sanitizedInput.length < 10) {
        handleErrorMessage(inputField, [defaultMessages.dayphone]);
    } else {
        handleErrorMessage(inputField, []);
    }
}

// Email validation
function validateEmail(inputField) {
    let inputValue = inputField.value;
    let errorMessages = [];

    // Check if there's more than one "@" symbol
    if ((inputValue.match(/@/g) || []).length > 1) {
        errorMessages.push(defaultMessages.email);
    }

    // Check if the email format is valid
    if (!/\S+@\S+\.\S+/.test(inputValue)) {
        errorMessages.push(defaultMessages.email);
    }

    // Check if the email length is within the limit
    if (inputValue.length > 75) {
        inputValue = inputValue.slice(0, 75);
        inputField.value = inputValue;
    }

    // Display error messages if any
    handleErrorMessage(inputField, errorMessages);
}


// Zipcode validation
function validateZip(inputField) {
    let inputValue = inputField.value;
    let sanitizedInput = inputValue.replace(/\D/g, '');

    if (sanitizedInput.length > 5)
        sanitizedInput = sanitizedInput.substring(0, 5);

    inputField.value = sanitizedInput;

    if (sanitizedInput.length < 5)
        handleErrorMessage(inputField, [defaultMessages.zipcode]);
    else 
        handleErrorMessage(inputField, []);
}

// Birthdate validation
function validateDOB(inputField) {
    let inputValue = inputField.value;
    let errorMessages = [];

    // Strip out all unwanted characters except for digits.
    let digitsOnly = inputValue.replace(/[^\d]/g, '');

    // Autoformatting: insert slashes at appropriate places.
    if (digitsOnly.length <= 2) {
        inputValue = digitsOnly;
    } else if (digitsOnly.length <= 4) {
        inputValue = digitsOnly.substring(0, 2) + '/' + digitsOnly.substring(2, 4);
    } else {
        inputValue = digitsOnly.substring(0, 2) + '/' + digitsOnly.substring(2, 4) + '/' + digitsOnly.substring(4, 8);
    }

    inputField.value = inputValue;

    if (inputValue.length !== 10) {
        errorMessages.push(defaultMessages.birthdate);
    } else {
        const dateParts = inputValue.split('/');
        const month = parseInt(dateParts[0], 10);
        const day = parseInt(dateParts[1], 10);
        const year = parseInt(dateParts[2], 10);
        const currentYear = new Date().getFullYear();

        if (month < 1 || month > 12) {
            errorMessages.push(defaultMessages.birthdate);
        }

        if (day < 1 || day > 31) {
            errorMessages.push(defaultMessages.birthdate);
        }

        if ((currentYear - year) < 16) {
            errorMessages.push("You must be at least 16 years old.");
        }
    }

    handleErrorMessage(inputField, errorMessages);
}

// Fields added with the custom_rfi_form_fields filter (marked with data-rfi-custom)
function validateCustomField(inputField) {
    const value = inputField.value.trim();
    const label = inputField.getAttribute("aria-label") || "this field";
    let errorMessages = [];

    if (inputField.required && value === "") {
        errorMessages.push(`Please fill in ${label}.`);
    } else if (value !== "" && inputField.type === "email" && !/\S+@\S+\.\S+/.test(value)) {
        errorMessages.push(defaultMessages.email);
    } else if (value !== "" && inputField.type === "url" && !/^https?:\/\/\S+$/i.test(value)) {
        errorMessages.push("Please enter a valid URL, starting with https://.");
    }

    handleErrorMessage(inputField, errorMessages);
}

// Validation function for a field: a built-in strategy, or the generic one for custom fields
function getValidator(inputField) {
    return validationStrategies[inputField.id] || (inputField.dataset.rfiCustom ? validateCustomField : null);
}

// Update your handleSelectFocus() function to handle dependencies
function handleSelectFocus(selectField) {
    // Get the ids of the select fields this one depends on
    let dependencyIds = selectDependencies[selectField.id];
  
    // If there are dependencies
    if (dependencyIds) {
        dependencyIds.forEach(dependencyId => {
            let dependencySelect = document.querySelector(`#${dependencyId}`);
            // If the dependency select field exists and it's set to the default value
            if (dependencySelect && dependencySelect.value === "default")
                handleErrorMessage(dependencySelect, [defaultMessages[dependencyId]]);
        });
    }
}


// Update your validateSelects() function
function validateSelects(selectField) {
    let selectValue = selectField.value;

    if(selectValue === "default")
        handleErrorMessage(selectField, [defaultMessages[selectField.id]]);
    else
        handleErrorMessage(selectField, []);
}

// ********** Event Handlers **********//

function handleTwoStepForm() {
    if (twoStepForm && CustomRfiFormData.form_steps === '2') {
        nextStepButton.addEventListener('click', function() {
            const inputs = stepOne.querySelectorAll('input, select, textarea');
            let hasError = false;
            inputs.forEach(input => {
                if (input.type === 'hidden') return;
                const validate = getValidator(input);
                if (validate) {
                    validate(input);
                    if (input.parentNode.querySelector('.error-message')) hasError = true;
                }
            });

            if (!hasError) {
                stepOne.classList.add('hide-step');
                stepTwo.classList.remove('hide-step');
            }
        });

        // Assuming you have a reference to the goback button as `gobackButton`
        goback.addEventListener('click', function() {
            stepOne.classList.remove('hide-step');
            stepTwo.classList.add('hide-step');
        });
    }
}

rfiForm.addEventListener('submit', function(event) {
    event.preventDefault();
    
    const inputs = CustomRfiFormData.form_steps === '2' ? stepTwo.querySelectorAll('input, select, textarea') : rfiForm.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        if(input.type === 'hidden') return;
        const validate = getValidator(input);
        if (validate) validate(input);
    });

    const errorMessages = document.querySelectorAll('.error-message');
    if (errorMessages.length === 0) {
        if (validatedInputs.dayphone) { // Check if 'dayphone' exists
            document.querySelector('#rfiform #phone').value = validatedInputs.dayphone.replace(/\D/g, '');
        }
        rfiForm.submit();
    }

});

document.querySelectorAll('#rfiform select').forEach(selectElement => {
    // if (selectElement.id !== 'CampusId') {
        selectElement.addEventListener('focus', function() {
            handleSelectFocus(this);
        });
    // }
});

handleTwoStepForm();