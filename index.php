<?php
require_once(__DIR__ . '/lib/ZohoAPIConnections.php');

if (isset($_POST["submit"])) {

    $target_dir = __DIR__ . "/files/";
    $fileArr = array();
    $fileUploadedArr = array();
    $fileArr = $_FILES;
    foreach ($fileArr as $key => $value) {
        $fileName = uploadfiles($target_dir, $value);
        if (isset($fileName['fileName'])) {
            $fileUploadedArr[] = $fileName['fileName'];
        }
    }

    $data = array();
    $data["First_Name"] = $_POST['fname'];
    $data["Last_Name"] =  $_POST['lname'];
    $data["Lead_Source"] = "Test";
    $data["Mobile"] =  $_POST['phone_number'];;
    $data["Lead_Owner"] = "subashini@gmail.lk";
    $data["Inquiry_Email"] = '';
    $data["Business_Type"] =  $_POST['business_type'];;
    $data["Product"] =  $_POST['product'];;
    $data["Inquiry_Message"] = "Custom message";
    $data["Send_Notification_Email"] = true;

    $response = storeLeads($data);

    if (isset($response['code']) && $response['code'] == 'SUCCESS') {
        if (isset($response['details']['id'])) {
            $id = $response['details']['id'];
            foreach ($fileUploadedArr as $name) {                
                $upload = storeFiles($id, $name, $target_dir);
                if (isset($response['code']) && $response['code'] == 'SUCCESS') {
                   
                }
            }
            header('Location: form.php?success=y');
            exit;
        } else {
            header('Location: form.php?success=n');
            exit;
        }
    } else {
        header('Location: form.php?success=n');
        exit;
        print_r($response);
    }

}
?>