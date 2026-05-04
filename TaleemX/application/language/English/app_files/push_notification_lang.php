<?php
#version 1.0

// Page / sidebar
$lang['push_notification']                  = "Push Notification";
$lang['push_notification_setting']          = "Push Notification Setting";
$lang['firebase_cloud_messaging']           = "Firebase Cloud Messaging (FCM)";

// Configuration form
$lang['configuration']                      = "Configuration";
$lang['firebase_project_id']                = "Firebase Project ID";
$lang['firebase_project_id_help']           = "Optional if your pasted JSON already contains project_id (recommended). Used only when JSON has no project_id. Must match the same Firebase project as the mobile app or sends return UNREGISTERED.";
$lang['service_account_file']               = "Service Account JSON (file)";
$lang['service_account_file_help']          = "Upload the JSON key downloaded from Firebase IAM. Overrides the pasted JSON below if both are provided.";
$lang['service_account_json']               = "Service Account JSON (paste)";
$lang['service_account_json_help']          = "Alternative to file upload. Paste the full JSON key here. After a successful save, this box is left blank on purpose — the private key is not shown again in the browser.";
$lang['service_account_already_uploaded']   = "A service account is already saved. Upload a new file or paste new JSON to replace it; otherwise leave blank.";
$lang['service_account_saved_as']           = "Saved in database as";
$lang['default_channel_id']                 = "Android Channel ID";
$lang['default_channel_id_help']            = "The Android notification channel id your mobile app registered. Used so notifications display with the right importance/sound when the app is in background.";
$lang['default_sound']                      = "Default Sound";
$lang['default_click_action']               = "Default Click Action / Category";
$lang['default_click_action_help']          = "Optional. Maps to Android click_action and iOS APNs category for tap routing.";
$lang['push_notification_setting_help']     = "These credentials are used by the portal and the mobile API to send push notifications via Firebase Cloud Messaging. Each tenant can use their own Firebase project. Credentials are stored in the database only.";
$lang['push_notification_config_table_missing'] = "The push_notification_config table is missing. Run migrations/push_notification_fcm.sql against the database first.";
$lang['invalid_service_account_json']       = "The provided service account JSON is invalid. Make sure it is the full JSON key downloaded from Firebase, including client_email and private_key.";

// Send-test form
$lang['send_test_push']                     = "Send Test Push";
$lang['send_test_push_help']                = "Send a one-off push to a known device token to verify configuration. Nothing is stored against any user.";
$lang['device_token']                       = "Device Token";
$lang['view_raw_fcm_response']              = "View raw FCM response";
$lang['test_push_default_title']            = "Test notification";
$lang['test_push_default_body']             = "Hello from Smart School. If you can see this, FCM is working.";
$lang['test_push_sent']                     = "Test push sent successfully.";
$lang['test_push_failed']                   = "Test push failed";

// Page-specific keys not already present in system_lang.php
$lang['body']                               = "Body";
$lang['required_fields_missing']            = "Token, title and body are required.";
