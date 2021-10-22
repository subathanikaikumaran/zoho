<!DOCTYPE html>
<html>

<body>
    <div class="alert alert-success" id="response-message-success" style="display: none; color:green">Thanks for contacting us! We will get in touch with you shortly.</div>
    <div class="alert alert-danger" id="response-message-fail" style="display: none; color:darkred"> There was a problem with your submission. </div>
    <form action="index.php" method="post" enctype="multipart/form-data">
        <div>
            <label>First Name<span class="text-danger">*</span>
                <input type="text" name="fname" id="fname" placeholder="First Name" required maxlength="100" minlength="3">
            </label>

            <label>Last Name<span class="text-danger">*</span>
                <input type="text" name="lname" id="lname" placeholder="Last Name" required maxlength="100" minlength="3">
            </label>

            <label>Phone<span class="text-danger">*</span>
                <input type="tel" name="phone_number" id="phone" placeholder="Contact Number" required onkeypress="return isNumberKey(event)" maxlength="10" minlength="10">
            </label>
        </div>

        <input type="file" name="fileToUpload1" id="fileToUpload1" accept="application/pdf, image/jpeg">
        <br />
        <input type="file" name="fileToUpload2" id="fileToUpload2" accept="application/pdf, image/jpeg">
        <br />
        <input type="file" name="fileToUpload3" id="fileToUpload3" accept="application/pdf, image/jpeg">
        <br />
        <input type="submit" value="Upload Image" name="submit">
    </form>

</body>
<script>
    var url_string = window.location.href
    var url = new URL(url_string);
    var success = url.searchParams.get("success");

    if (success !== null) {
        if (success === 'y') {
            document.getElementById('response-message-success').style.display = 'block';
        } else if (success === 'n') {
            document.getElementById('response-message-fail').style.display = 'block';
        }
    }
</script>

</html>