# Custom RFI Form

A WordPress plugin that adds a request-for-information (RFI) lead form to school marketing sites. The form loads campuses and programs from a partner lead API, guides the visitor through choosing a program, and submits the lead back to the API with tracking and attribution data.

- One-step or two-step layout
- Campus-first or program-first program selection, with dependent dropdowns
- Configurable fields, colors, disclaimer, thank-you page and API environment (production, QA or staging)
- Directory responses cached in transients; one click in the toolbar clears the cache
- Campaign tracking (track ID, session GUID, landing URL, UTM parameters, Google and Microsoft Ads click IDs)
- Extra fields (text, email, phone, number, URL or textarea) in any position, added with a filter

**Requirements:** WordPress 5.6+, PHP 7.4+

## Installation

1. Copy this folder to `wp-content/plugins/custom-rfi-form/`.
2. Activate **Custom RFI Form** under **Plugins**.
3. Open **Settings → Form Settings** and fill in:
   - the **Form Track ID**
   - the **Server Switch**
   - the API URL for that server, either on the settings page or in `wp-config.php` (see [API endpoints](#api-endpoints))
4. Add `[custom_rfi_form]` to a page.

Until the API URL for the selected server is set, administrators see a notice, and the form makes no API requests.

## Usage

```text
[custom_rfi_form]
[custom_rfi_form steps="2" form_title="Get Program Info" thank_you_page="https://example.edu/thanks/"]
[custom_rfi_form feature_list_id="1234" prepop_campus="C1" prepop_aos="Business" prepop_program="P1"]
```

| Attribute            | Description                                                                  |
| -------------------- | ---------------------------------------------------------------------------- |
| `steps`              | `1` / `one` or `2` / `two`. Defaults to the **Form Steps** setting.          |
| `feature_list_id`    | Feature list ID. Defaults to the **Global Feature ID** setting.              |
| `prepop_campus`      | Campus ID to preselect.                                                      |
| `prepop_aos`         | Area of study to preselect, matched by name (case and punctuation ignored). |
| `prepop_program`     | Program ID to preselect.                                                     |
| `form_title`         | Heading above the form.                                                      |
| `submit_button_text` | Submit button label.                                                         |
| `thank_you_page`     | URL to redirect to after a successful submission.                            |

The form uses fixed element IDs, so only one form per page is supported.

## Settings

| Setting                         | Purpose                                                                                  |
| ------------------------------- | ---------------------------------------------------------------------------------------- |
| Debug Mode                      | Administrators who submit the form see the JSON payload instead of it being sent.        |
| Form Steps                      | One-step or two-step layout.                                                             |
| Form Track ID                   | Campaign track ID (the API key). Required. Public: it is rendered in the page.           |
| Global Feature ID               | Default feature list ID.                                                                 |
| Form Name                       | Sent with the lead as `FormName`.                                                        |
| Server Switch                   | Which API URL to use: production, QA or staging.                                         |
| Programs Before Campus          | Program-first (`programsformicrosites`) or campus-first (`campusesformicrosites`) data.  |
| Campus Type / Campus ID         | Restrict the directory to a single campus.                                               |
| Form Fields                     | Which optional fields to show. Email is always shown.                                    |
| Highest Level of Education      | Options as `Label : value`, one per line.                                                |
| Start Term                      | Options as `Label : value`, one per line.                                                |
| Program Option Group            | Group programs into `<optgroup>`s by area of study.                                      |
| Option Group Order              | Custom order of those groups (alphabetical when empty).                                  |
| Disclaimer                      | Consent text shown below the form and sent as `UserAgreement`.                           |
| Thank You URL Type / URL        | Internal page or external URL to redirect to. Falls back to the home page.               |
| Return LeadId                   | Ask the API to return a lead ID even for unsuccessful leads.                             |
| Honey Pot Field                 | Adds a hidden field; submissions that fill it are discarded.                             |
| Color Scheme                    | Form colors, output as CSS custom properties.                                            |
| API Endpoints                   | See [API endpoints](#api-endpoints).                                                     |

The settings page and the **Clear API Cache** toolbar button require the `manage_options` capability. Use a filter to allow other roles:

```php
add_filter( 'custom_rfi_form_settings_capability', fn() => 'edit_pages' );
```

## API endpoints

No API hostnames are hard-coded. Configure them under **Settings → Form Settings → API Endpoints**, or lock them in `wp-config.php`. A constant always wins over the setting, and its field on the settings page becomes read-only.

| Setting             | Constant                            | Notes                                                              |
| ------------------- | ----------------------------------- | ------------------------------------------------------------------ |
| Production API URL  | `CUSTOM_RFI_FORM_API_URL_PROD`      | Base URL, e.g. `https://partners.example.com/api`                  |
| QA API URL          | `CUSTOM_RFI_FORM_API_URL_QA`        | Base URL                                                           |
| Staging API URL     | `CUSTOM_RFI_FORM_API_URL_STAGE`     | Base URL; plain HTTP allowed for internal hosts                    |
| Zip Code Lookup URL | `CUSTOM_RFI_FORM_POSTAL_LOOKUP_URL` | Optional; `?ZipCode=` is appended                                  |
| Track ID Check URL  | `CUSTOM_RFI_FORM_TRACKID_CHECK_URL` | Optional; called from the browser with `?apikey=` appended         |
| (constant only)     | `CUSTOM_RFI_FORM_UPDATE_URL`        | Optional; `plugin-info.json` URL that turns on self-hosted updates |

The plugin appends `/directory/campusesformicrosites`, `/directory/programsformicrosites` and `/institutions/lead-save` to the API base URL.

```php
// wp-config.php
define( 'CUSTOM_RFI_FORM_API_URL_PROD', 'https://partners.example.com/api' );
define( 'CUSTOM_RFI_FORM_API_URL_QA', 'https://partners.qa.example.com/api' );
define( 'CUSTOM_RFI_FORM_POSTAL_LOOKUP_URL', 'https://forms.example.com/FormValidation/GetCityStateCountry' );
define( 'CUSTOM_RFI_FORM_UPDATE_URL', 'https://updates.example.com/plugin-info.json' );
```

Every URL, from the settings or a constant, must:

- use HTTPS (only the staging URL may use plain HTTP)
- include a host
- contain no credentials, query string or fragment

Invalid values are rejected with an error on save, and invalid constants are ignored.

**Why `wp-config.php`?** Leads, which include names, emails and phone numbers, are posted to these URLs. A constant keeps the endpoint in server configuration, under version control or deployment tooling. Even an attacker who takes over a wp-admin account cannot redirect lead data to another server. If you use the settings page instead, only users with `manage_options` can change the URLs.

If the zip lookup or track ID check URL is empty, that step is skipped.

## Adding fields

Add your own fields from a small plugin or your theme, without editing this plugin:

```php
add_filter( 'custom_rfi_form_fields', function ( $fields ) {
    $fields['job_title'] = array(
        'label'    => 'Job Title',
        'priority' => 25,   // Between Last Name (20) and Phone (30)
        'required' => true,
    );
    return $fields;
} );
```

Fields are validated in the browser and on the server, and sent with the lead automatically. The tutorial **[Adding custom fields](docs/adding-custom-fields.md)** covers every option, the built-in field order and troubleshooting.

## How it works

### Rendering the form

1. `Custom_RFI_Form_Shortcode` reads the settings and shortcode attributes.
2. `Custom_RFI_Form_Api` asks the directory API for campuses and programs. Responses are cached for 24 hours. Failures are cached for one minute, so an API outage does not slow every page view.
3. The one-step or two-step template renders the shared components: program selectors, contact fields and hidden tracking fields.
4. The scripts are enqueued and receive their configuration as `window.CustomRfiFormData`:
   - `rfi-tracking.js` manages the campaign and session cookies.
   - `custom-form-filtering.js` (campus-first or program-first) narrows the dependent dropdowns.
   - `custom-form-validation.js` validates the fields and handles the two-step flow.
   - `custom-form-additional.js` fills in the device type and cookie support, and checks the track ID.

### Submitting a lead

The form posts to `admin-post.php?action=custom_rfi_form_submit`. The handler in `includes/custom-form-submission.php`:

1. Verifies the nonce and silently drops honeypot hits.
2. Keeps only an allow-list of known fields, unslashed and sanitized.
3. Normalizes the name, phone, email, zip and birth date.
4. Looks up the city, state and country from the zip code, if a lookup URL is configured.
5. Adds the user agent, IP address, analytics click IDs and landing-page query parameters as `AdditionalQuestions`.
6. Posts the JSON to `<API URL>/institutions/lead-save`.
7. Redirects to the thank-you page with the lead ID, processing status, and SHA-256 hashes of the email and E.164 phone. The hashes are for ad platforms' enhanced conversions.

The thank-you URL rendered into the form is signed with an HMAC. The handler only follows it when the signature matches, so the field cannot be used as an open redirect.

## Project structure

```text
custom-rfi-form.php                      Bootstrap: constants, includes, hooks, optional update checker
includes/
  class-custom-rfi-form-plugin.php       Settings page (Settings API)
  class-custom-form-shortcode.php        [custom_rfi_form] rendering and asset loading
  custom-form-submission.php             Lead submission handler
  form/
    helper-functions.php                 Settings, API endpoints, cache, display options
    fields.php                           Field registry and the custom_rfi_form_fields filter
    class-custom-rfi-form-api.php        Cached directory API client
    custom-form-one-step.php             One-step template
    custom-form-two-steps.php            Two-step template
    components/                          Field partials (selectors, inputs, hidden fields)
  settings-classes/                      Settings field definitions, renderers, sanitizer
  js/  css/                              Front-end and admin assets
plugin-update-checker/                   Third-party library (MIT), loaded only when CUSTOM_RFI_FORM_UPDATE_URL is set
docs/
  adding-custom-fields.md                Tutorial: adding fields to the form
```

## Security notes

- **No secrets or hostnames in the code.** The Form Track ID is a public campaign identifier: it is rendered in every page and can be overridden with `?trackid=`. Endpoints are configuration (see [API endpoints](#api-endpoints)), and can be locked in `wp-config.php`.
- **Escaping.** All output is escaped, including shortcode attributes, which any Contributor can set.
- **Request handling.** Submissions require a nonce. Only allow-listed fields are forwarded to the API.
- **Debug mode** never shows payloads to visitors.
- **Settings** are saved through the Settings API, with allow-lists. Endpoint URLs must use HTTPS and contain no credentials.

## Known limitations

- **Page caching and nonces.** WordPress nonces expire after 12–24 hours. If pages with the form are cached longer than that, visitors get an "expired session" error. Exclude those pages from full-page caching or keep the cache TTL under 12 hours.
- **Names.** The server keeps only ASCII letters and hyphens in names, so "José" and "Mary Ann" are changed. Relax this only once the lead API is confirmed to accept other characters.
- **Staging** may use plain HTTP and is meant only for internal environments.

## Author

Cristian Bladimir Lopez Hurtarte
