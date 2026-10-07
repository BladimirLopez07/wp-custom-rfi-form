# Tutorial: Adding custom fields to the form

This tutorial shows how to add your own fields to the Custom RFI Form, such as a company name, a job title or a free-text question. You can choose where each one appears among the built-in fields. You do this with a WordPress filter, so you never edit the plugin's files, and your fields survive plugin updates.

**You will need:**
- the plugin installed and its API URL configured (see the [README](../README.md#installation))
- a place for a few lines of PHP: a small plugin of your own (recommended) or your theme's `functions.php`

**Time:** about 10 minutes.

---

## 1. Create a small plugin for your fields

Your fields belong to your site, not to the form plugin, so keep them in their own file. A [must-use plugin](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/) is the simplest option: WordPress loads it automatically, and it can't be deactivated by accident.

Create `wp-content/mu-plugins/rfi-form-fields.php`. Create the `mu-plugins` folder if it does not exist.

```php
<?php
/**
 * Plugin Name: RFI Form – Site Fields
 * Description: Extra fields for the Custom RFI Form.
 */

add_filter( 'custom_rfi_form_fields', function ( $fields ) {
    // Your fields go here (step 2).
    return $fields;
} );
```

> **Prefer the theme?** The same `add_filter()` call works in `functions.php`. Keep in mind that switching themes removes the fields.

## 2. Add your first field

The filter receives every field in the form, keyed by an ID. Add an entry and return the array:

```php
add_filter( 'custom_rfi_form_fields', function ( $fields ) {
    $fields['company'] = array(
        'label'    => 'Company',
        'priority' => 35, // Between Phone (30) and Email (40)
    );

    return $fields;
} );
```

Reload a page with the `[custom_rfi_form]` shortcode. A **Company** input now appears between the phone and email fields.

The array key (`company`) is also the name the field is submitted under, unless you set `name` yourself.

## 3. Choose the order with `priority`

Fields are shown from the lowest `priority` to the highest. The built-in fields use multiples of ten, so there is room between any two of them:

| Built-in field | Key        | Priority |
| -------------- | ---------- | -------- |
| First Name     | `fname`    | 10       |
| Last Name      | `lname`    | 20       |
| Phone Number   | `phone`    | 30       |
| Email          | `email`    | 40       |
| Honeypot       | `honeypot` | 50 (hidden) |
| Zip Code       | `zip`      | 60       |
| Birth date     | `birthday` | 70       |

- A field with priority `5` comes first.
- A field with priority `65` comes between Zip Code and Birth date.
- A field without a priority gets `100` and goes last.
- Fields with the same priority keep the order you added them in.

These fields are the contact-details part of the form. In the two-step layout they appear in **step 2**. The program selectors in step 1 are not part of this list.

## 4. Pick a field type and options

```php
add_filter( 'custom_rfi_form_fields', function ( $fields ) {
    $fields['job_title'] = array(
        'label'       => 'Job Title',
        'type'        => 'text',
        'priority'    => 25,
        'required'    => true,
        'max_length'  => 60,
        'placeholder' => 'e.g. Registered Nurse',
    );

    $fields['questions'] = array(
        'label'    => 'Questions for an advisor',
        'type'     => 'textarea',
        'priority' => 90,
    );

    return $fields;
} );
```

All options:

| Option                | Default         | Description |
| --------------------- | --------------- | ----------- |
| `label`               | the key         | Label and placeholder text. |
| `type`                | `text`          | `text`, `email`, `tel`, `number`, `url` or `textarea`. |
| `priority`            | `100`           | Position in the form (see step 3). |
| `required`            | `false`         | The form will not submit while the field is empty. Checked in the browser and again on the server. |
| `max_length`          | `255`           | Maximum characters. Enforced in the browser and on the server. |
| `placeholder`         | the label       | Placeholder text, if it should differ from the label. |
| `name`                | the key         | Name the value is submitted under. Letters, numbers and underscores; must start with a letter. |
| `enabled`             | `true`          | Set to `false` to hide the field without deleting its definition. |
| `additional_question` | `true`          | How the value is sent to the lead API (see step 5). |

## 5. Understand what is sent to the lead API

When the visitor submits, the plugin reads each custom field, cleans the value according to its type, and adds it to the lead.

- **By default** (`'additional_question' => true`), the value is added to the lead's `AdditionalQuestions` list, which accepts any key:

  ```json
  "AdditionalQuestions": [
      { "QuestionKey": "job_title", "QuestionValue": "Registered Nurse" }
  ]
  ```

  Use this for anything the lead API does not have a dedicated field for.

- **With `'additional_question' => false`**, the value is sent as a top-level field, e.g. `"Address1": "..."`. Use this only when the lead API has a field with exactly that name.

Values are cleaned by type: email addresses with `sanitize_email()`, URLs with `esc_url_raw()`, numbers are parsed, textareas keep their line breaks, and everything else goes through `sanitize_text_field()`.

**To check the payload before going live:**
1. Turn on **Debug Mode** in **Settings → Form Settings**.
2. Submit the form while logged in as an administrator.

The plugin shows the JSON it would send, and sends nothing.

## 6. Change or hide built-in fields

You can also adjust the built-in fields through the same filter. They accept four options: `label`, `priority`, `max_length` and `enabled`.

```php
add_filter( 'custom_rfi_form_fields', function ( $fields ) {
    $fields['fname']['label']    = 'Given Name';
    $fields['zip']['priority']   = 15;    // Right after First Name
    $fields['birthday']['enabled'] = false;

    return $fields;
} );
```

Other options on built-in fields are ignored. Their names, types and validation stay fixed because the lead API and the form scripts depend on them. Email can't be turned off, because the lead API requires it. To turn the other built-in fields on or off, use **Form Fields** in the settings.

## 7. Troubleshooting

**My field does not appear.** The plugin skips fields it can't use, and reports each one through WordPress's "doing it wrong" notice. Turn on `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`, reload the page, and check `wp-content/debug.log`. You'll see a message like:

```text
Field "1st_choice" skipped: names must start with a letter and contain only letters, numbers and underscores.
```

The usual causes:
- **Invalid name.** It doesn't start with a letter, or it contains dashes or spaces.
- **Reserved name.** The name is already used by the form, for example `email`, `CampusId` or `APIKey`. Pick a different key, or set `name`.
- **Duplicate name.** Two custom fields have the same name.
- **Unsupported type.** For example, `select` or `checkbox`.

**The value is missing from the lead.** Check that the field is `enabled`. Then use Debug Mode (step 5) to see whether the value is in the payload. If it is, the lead API received it.

**The form says "Please fill in …" on submit.** The field is `required`. Remove `'required' => true` if it should be optional.

## Complete example

```php
<?php
/**
 * Plugin Name: RFI Form – Site Fields
 * Description: Extra fields for the Custom RFI Form.
 */

add_filter( 'custom_rfi_form_fields', function ( $fields ) {
    $fields['job_title'] = array(
        'label'      => 'Job Title',
        'priority'   => 25,
        'required'   => true,
        'max_length' => 60,
    );

    $fields['employer'] = array(
        'label'    => 'Employer',
        'priority' => 26,
    );

    $fields['questions'] = array(
        'label'      => 'Questions for an advisor',
        'type'       => 'textarea',
        'priority'   => 90,
        'max_length' => 500,
    );

    $fields['birthday']['enabled'] = false;

    return $fields;
} );
```

The resulting order is:
1. First Name
2. Last Name
3. Job Title
4. Employer
5. Phone Number
6. Email
7. Zip Code
8. Questions for an advisor

Each field appears only if it is enabled.

## How it works (for maintainers)

- `includes/form/fields.php` builds the list:
  1. `custom_rfi_form_core_fields()` defines the built-in fields.
  2. `custom_rfi_form_get_fields()` applies the filter, validates every definition, and sorts the list by priority.
- `components/contact-fields.php` renders the list, using `components/input-field.php` for each field.
- In the browser, `custom-form-validation.js` validates custom fields (`data-rfi-custom`) with `validateCustomField()`. Built-in fields keep their own validators.
- On submit, `custom_rfi_form_get_custom_field_values()` reads and sanitizes the custom values, and rejects the submission if a required one is empty. `custom_rfi_form_build_lead()` then adds them to the payload.

Every custom field passes through that single registry, so a field registered with the filter is accepted on submit automatically. The submit handler otherwise accepts only an allow-list of fields.
