<?php

defined('SUPER_ADMIN_EMAIL') or define('SUPER_ADMIN_EMAIL', 'sajidjalal@gmail.com');
defined('SUPER_ADMIN_ARRAY') or define('SUPER_ADMIN_ARRAY', [SUPER_ADMIN_EMAIL]);
defined('SMS_LEN') or define('SMS_LEN', 6);
defined('OTP_EXPIRY_TIME') or define('OTP_EXPIRY_TIME', 5);

defined('CACHE_TIME') or define('CACHE_TIME', 86400); // 86400 = 1 day

defined('SUPER_ADMIN_ROLE_ID') or define('SUPER_ADMIN_ROLE_ID', '1');
defined('ADMIN_ROLE_ID') or define('ADMIN_ROLE_ID', '2');
defined('DOCTOR_ROLE_ID') or define('DOCTOR_ROLE_ID', '3');

defined('UI_SHORT_DATE_FORMAT') or define('UI_SHORT_DATE_FORMAT', 'd-m-Y');
defined('DB_FULL_DATE_TIME') or define('DB_FULL_DATE_TIME', 'Y-m-d H:i:s');
defined('DB_DATE_FORMATE_ONLY') or define('DB_DATE_FORMATE_ONLY', 'Y-m-d');

defined('FULL_UI_DATE_FORMAT') or define('FULL_UI_DATE_FORMAT', 'D, d M Y h:i A');
defined('AP_PM_FULL_UI_DATE_FORMAT') or define('AP_PM_FULL_UI_DATE_FORMAT', 'D, d M Y h:i A');

defined('IS_BCC_SEND') or define('IS_BCC_SEND', false);
defined('BCC_MAIL_ID') or define('BCC_MAIL_ID', "sajidjalal@gmail.com");

defined('FROM_MAIL_ID') or define('FROM_MAIL_ID', "sajidjalal@gmail.com");
defined('FROM_MAIL_NAME') or define('FROM_MAIL_NAME', "App Name");
defined('ERROR_MESSAGE') or define('ERROR_MESSAGE', 'An unexpected error occurred. Please try again later');
defined('IS_SHOW_ALERT') or define('IS_SHOW_ALERT', true);
defined('IS_TOAST_ALERT') or define('IS_TOAST_ALERT', false);
defined('DOCTOR_DOCUMENT_PATH') or define('DOCTOR_DOCUMENT_PATH', 'public/uploads/doctor');
