/* Device type, cookie support and track ID checks for the RFI form */

document.addEventListener("DOMContentLoaded", () => {
    if (!document.getElementById("rfiform") || !window.RfiTracking) {
        return;
    }

    RfiTracking.init();

    /*===Check Device Type===*/
    function detectDevice() {
        const mobileRegex =
            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
        return mobileRegex.test(navigator.userAgent) ? "Mobile" : "Desktop";
    }
    setFieldValue("DeviceType", detectDevice());
    /*===END Device Type===*/

    /*===Check if cookies are enabled===*/
    setFieldValue("Cookies", navigator.cookieEnabled);
    /*===END cookies enabled===*/

    /*===Check the campaign track ID and fall back to the default if invalid===*/
    const trackId = RfiTracking.get_cookie(RfiTracking.campaign_cookie_name);
    if (trackId) {
        validateTrackId(trackId);
    }
    /*===END TrackID===*/
});

function setFieldValue(id, value) {
    const field = document.getElementById(id);
    if (field) {
        field.value = value;
    }
}

async function validateTrackId(trackId) {
    const checkUrl = (window.CustomRfiFormData || {}).trackid_check_url;
    const defaultTrackId = String(RfiDefaultTrackIds.trackid || "");
    if (!checkUrl || trackId.toUpperCase() === defaultTrackId.toUpperCase()) {
        return;
    }

    let response;
    try {
        response = await fetch(
            `${checkUrl}?apikey=${encodeURIComponent(trackId)}`
        );
    } catch (error) {
        // Network failure: keep the default track ID rendered in the form.
        return;
    }

    setFieldValue("APIKey", response.ok ? trackId : defaultTrackId);

    // Diagnostic fields, sent with the lead
    setFieldValue("CheckAPIKey", response.ok ? "Valid" : "Invalid");
    setFieldValue("CheckAPIStatus", response.status);

    if (!response.ok) {
        let errorMessage = "Unknown error occurred.";
        try {
            const jsonResponse = await response.json();
            if (jsonResponse.Messages && jsonResponse.Messages.length > 0) {
                errorMessage = jsonResponse.Messages[0].Message;
            }
        } catch (error) {
            // Response was not JSON; keep the generic message.
        }
        setFieldValue("CheckAPIErrorMessage", errorMessage);
    }
}
