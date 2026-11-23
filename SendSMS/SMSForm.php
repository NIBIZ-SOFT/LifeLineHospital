<?php

// Full Stable Version 9.0.0

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$MainContent .= '<form method="post" action="' . ApplicationURL("{$_REQUEST["Base"]}", "SMSSendAction") . '">

    <div class="mb-3">
        <label class="form-label">Send To</label>

        <select name="SendType" id="SendType" class="form-select">
            <option value="All">All Customers</option>
            <option value="Single">Single Number</option>
        </select>
    </div>

    <div class="mb-3" id="SingleNumberGroup" style="display:none;">
        <label class="form-label">Mobile Number</label>

        <input
            type="text"
            name="MobileNumber"
            class="form-control"
            placeholder="Enter mobile number"
        >
    </div>

    <div class="mb-3">
        <label class="form-label">Message</label>

        <textarea
            name="Message"
            class="form-control"
            rows="5"
            placeholder="Write your message here..."
            required
        ></textarea>
    </div>

    <button type="submit" class="btn btn-primary">
        Send SMS
    </button>

</form>

<script>
document.getElementById("SendType").addEventListener("change", function() {

    if (this.value === "Single") {
        document.getElementById("SingleNumberGroup").style.display = "block";
    } else {
        document.getElementById("SingleNumberGroup").style.display = "none";
    }

});
</script>';