/**
 * This script handles tracking and cookie management for the RFI form.
 * 
 * Overview:
 * 1. The script initializes an empty array _etq if it doesn't already exist, and pushes '_etPageView' to it. 
 *    This is likely used for tracking page views.
 *
 * 2. RfiDefaultTrackIds: Contains the default tracking IDs from the CustomRfiFormData.
 *
 * 3. RfiTracking Object: 
 *    - `campaign_cookie_name`, `session_cookie_name`, `init_lead_cookie_name`: Definitions for cookie names.
 *    - `get_full_query_string()`: Fetches the entire query string from the current URL.
 *    - `set_cookie()`: Sets a cookie with provided name, value, and optional parameters.
 *    - `init()`: Initializes tracking by checking and setting various cookies and updating form inputs based on them.
 *    - `generate_guid()`: Generates a universally unique identifier (UUID/GUID).
 *    - `is_valid_guid()`: Validates if a provided string is a valid GUID format.
 *    - `get_query_string_by_name()`: Retrieves the value of a specific query string parameter from the URL.
 *    - `get_cookie()`: Fetches the value of a specific cookie.
 *    - `parse_all_cookies()`: Parses all set cookies into an object.
 *    - `create_session_cookie()`: Creates a session cookie with a provided name and value.
 * 
 * Purpose:
 * The primary goal of the script is to manage and handle tracking cookies for user sessions, 
 * lead initiation URLs, campaign tracking, and more. These cookies help in understanding user interactions,
 * sources of traffic, and behavior on the site.
 * 
 * Implementation Details:
 * - If a user visits the platform with a `trackid` in the URL, it will supersede any existing campaign cookie.
 * - If no `trackid` is in the URL, the existing campaign cookie is used. 
 *   If no such cookie exists, the default tracking ID is used.
 * - The script also initializes a lead URL cookie (`_InitialLeadUrl`) to store the URL that led the user to fill out the form.
 * - When the script is initialized, the values of these cookies are inserted into the corresponding form fields.
 * - Additionally, the `RfiAdditionalFields` cookie is set with the full query string of the current URL.
 * 
 * Note: For maintaining accurate tracking and capturing user behavior, it's crucial that the script remains intact.
 * Ensure that changes made to the script don't disrupt its core functionalities and tracking logic.
 */


// Another copy of this script (e.g. from the theme) may already be on the page; reuse it.
if (!window.RfiTracking) {
    window._etq = window._etq || [];
    window._etq.push(['_etPageView']);
}

var RfiDefaultTrackIds = window.RfiDefaultTrackIds || {
    trackid: (window.CustomRfiFormData || {}).gp_trackid
};

var RfiTracking = window.RfiTracking || {
    campaign_cookie_name: '_CampaignTrackID',
    session_cookie_name: '_Session',
    init_lead_cookie_name: '_InitialLeadUrl', // Adding new cookie name
    trackid: RfiDefaultTrackIds.trackid,

     // Retrieve the full query string from the current URL
     get_full_query_string: () => {
        const queryString = window.location.search.slice(1);
        return queryString;
    },

    // Set a cookie with options
    set_cookie: (name, value, options = {}) => {
        let cookieString = `${name}=${value}; path=/`;

        if (options.days) {
            const date = new Date();
            date.setTime(date.getTime() + (options.days * 24 * 60 * 60 * 1000));
            cookieString += `; expires=${date.toUTCString()}`;
        }

        if (options.secure) {
            cookieString += `; secure`;
        }

        document.cookie = cookieString;
        return value;
    },

    init: () => {
        // Check if _InitialLeadUrl cookie is set, if not, set it
        let initLeadUrl = RfiTracking.get_cookie(RfiTracking.init_lead_cookie_name);
        if (!initLeadUrl) {
            RfiTracking.create_session_cookie(RfiTracking.init_lead_cookie_name, window.location.href);
            initLeadUrl = window.location.href;
        }

        // Set the value of #LeadInitiatingUrl to the _InitialLeadUrl cookie value
        const leadInitUrlElement = document.querySelector('#LeadInitiatingUrl');
        if (leadInitUrlElement) {
            leadInitUrlElement.value = initLeadUrl;
        }

        // Set the RfiAdditionalFields cookie with the full query string
        const fullQueryString = RfiTracking.get_full_query_string();
        if (fullQueryString) {
            RfiTracking.set_cookie('RfiAdditionalFields', fullQueryString, { days: 30 });
        }

        const qstrackid = RfiTracking.get_query_string_by_name('trackid');
        const campaign_cookie = RfiTracking.get_cookie(RfiTracking.campaign_cookie_name);

        if (qstrackid !== 0 && RfiTracking.is_valid_guid(qstrackid)) {
            RfiTracking.create_session_cookie(RfiTracking.campaign_cookie_name, qstrackid);
            const sessionGuid = RfiTracking.generate_guid();
            RfiTracking.create_session_cookie(RfiTracking.session_cookie_name, sessionGuid);
            RfiTracking.trackid = qstrackid;
        } else if (campaign_cookie) {
            RfiTracking.trackid = campaign_cookie;
        } else {
            const defaultTrackId = RfiDefaultTrackIds?.tv_trackid && document.referrer === "" 
              ? RfiDefaultTrackIds.tv_trackid 
              : RfiDefaultTrackIds.trackid;

            RfiTracking.create_session_cookie(RfiTracking.campaign_cookie_name, defaultTrackId);
            const sessionGuid = RfiTracking.generate_guid();
            RfiTracking.create_session_cookie(RfiTracking.session_cookie_name, sessionGuid);
            RfiTracking.trackid = defaultTrackId;
        }

        // Set the value of #TrackingSessionGUID to the _Session cookie value
        const sessionGuidElement = document.querySelector('#TrackingSessionGUID');
        if (sessionGuidElement) {
            sessionGuidElement.value = RfiTracking.get_cookie(RfiTracking.session_cookie_name);
        }
    },

    generate_guid: () => {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    },

    is_valid_guid: guid => /^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/.test(guid.toUpperCase()),

    get_query_string_by_name: name => {
        const result = new RegExp(`[\\?&]${name}=([^&#]*)`).exec(window.location.href.toLowerCase());
        return result ? result[1] : 0;
    },

    get_cookie: cookieName => {
        const cookies = RfiTracking.parse_all_cookies();
        return cookies[cookieName] ?? null;
    },

    parse_all_cookies: () => document.cookie.split(';').reduce((acc, cookie) => {
        const [name, ...rest] = cookie.split('=');
        acc[name.trim()] = decodeURIComponent(rest.join('=').trim());
        return acc;
    }, {}),

    create_session_cookie: (name = '', value = '') => {
        if (!name || !value) {
            return null;
        }
        document.cookie = `${name}=${value}; path=/`;
        return value;
    }
};
