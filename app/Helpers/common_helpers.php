<?php

use App\Models\ErrorLogModel;
use App\Models\MailLogModel;
use App\Models\RolesModel;
use App\Models\SmsTemplateModel;
use App\Models\SystemActivityTrackerModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function common_helper($data = '')
{
    $text = 'Common Helper';
    Log::critical(json_encode($text));
    dd($text);
}

function saveErrorLog($message, $type, $line = null, $file = null)
{
    ErrorLogModel::create([
        'message' => $message,
        'type' => $type,
        'line' => $line,
        'file' => $file,
    ]);
}

function SendMobileOTP($mobile_number, $otp_code, $template_code, $type = '')
{
    $status = 0;
    $reason = "";
    if (env('APP_ENV') == 'prod') {

        $smsTemplateDetails = SmsTemplateModel::where('template_code', $template_code)->first();

        if (!$smsTemplateDetails) {
            $status = 0;
            $reason = "SMS Template not found";
        } else {
            $sms_text = str_replace('$otp_code', $otp_code, $smsTemplateDetails->description);

            // $sms_content = urlencode($sms_text);
            $sms_content = ($sms_text);

            $user_id = env('SMS_USER_ID');
            $password = env('SMS_PASSWORD');

            $url = "https://enterprise.smsgupshup.com/GatewayAPI/rest";
            try {

                $response = Http::timeout(30)->get($url, [
                    'method'      => 'SendMessage',
                    'send_to'     => $mobile_number,
                    'msg'         => $sms_content,
                    'msg_type'    => 'TEXT',
                    'userid'      => $user_id,
                    'auth_scheme' => 'plain',
                    'password'    => $password,
                    'v'           => '1.1',
                    'format'      => 'text',
                ]);

                $body = $response->body();
                // Check if the response contains success
                if (strpos($body, 'success') !== false) {
                    $status = 1;
                    $reason = "SMS sent successfully.";
                } else {
                    $status = 3;
                    $reason = "API failed";
                }
            } catch (\Exception $e) {
                $status = 4;
                $reason = $e->getMessage();
            }
        }
    } else {
        $status = 2;
        $reason = "In development mode.";
    }

    return [
        'status' => $status,
        'reason' => $reason,
    ];
}

function convertEmailsToArray($emails)
{
    if (empty($emails)) return [];
    return is_array($emails) ? $emails : (json_decode($emails, true) ?: explode(',', $emails));
}

function formatEmailArray($emails)
{
    return array_map(fn($email) => ['email' => strtolower(trim($email))], array_filter(array_unique((array) $emails)));
}

function sendGridCurl($to, $from, $from_name, $data, $template_id, $replyto, $cc = [], $reply_to_name = null, $bcc = [], $attachments = [], $returnResponseFlag = false)
{
    if (config('app.env') !== 'prod') {
        return ['message' => 'Email only gets triggered in prod environment'];
    }

    $to = convertEmailsToArray($to);
    $cc = convertEmailsToArray($cc);
    $bcc = convertEmailsToArray($bcc);

    $post_fields = [
        'from' => ['email' => $from, 'name' => $from_name],
        'personalizations' => [[
            'dynamic_template_data' => $data,
            'to' => formatEmailArray($to),
        ]],
        'template_id' => trim($template_id),
    ];

    if (!empty($cc)) {
        $post_fields['personalizations'][0]['cc'] = formatEmailArray($cc);
    }

    if (!empty($bcc)) {
        $post_fields['personalizations'][0]['bcc'] = formatEmailArray($bcc);
    }

    if (!empty($data['category'])) {
        $post_fields['categories'] = [(string)$data['category']];
    }

    if (!empty($attachments) && is_array($attachments)) {
        $post_fields['attachments'] = $attachments;
    }

    // Reply-To configuration
    if (is_array($replyto)) {
        $post_fields['reply_to_list'] = formatEmailArray($replyto);
    } elseif (!empty($replyto)) {
        $post_fields['reply_to'] = [
            'email' => $replyto,
            'name' => $reply_to_name ?? $from_name,
        ];
    }

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($post_fields),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . env('SENDGRID_API_KEY'),
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $error = curl_errno($ch);
    curl_close($ch);

    logEmailDetails($data, $error, $response);

    if ($returnResponseFlag) {
        return ['response' => $response, 'error' => $error];
    }

    return ($error || !empty($response)) ? false : true;
}



function isJson($string)
{
    Log::info('Helpers::isJson()');
    json_decode($string);

    return json_last_error() == JSON_ERROR_NONE;
}

function logEmailDetails($data, $error, $response)
{
    $mailData = $data['sendGridMailData']['mail_data'] ?? null;
    if (!$mailData) {
        return;
    }

    $status = ($error || !empty($response)) ? 0 : 1;
    createMailLog($mailData, $status);
}

function mail_sending_helper($data, $file = '')
{
    if (env('APP_ENV') !== 'prod') {
        return true;
    }

    $status = 0;

    try {
        Mail::send($data['template_name'], ['mail_data' => $data], function ($message) use ($data, $file) {
            $message->to($data['email'])->subject($data['subject']);
            if (!empty($file)) {
                $message->attach($file);
            }
        });
        $status = 1;
    } catch (\Exception $e) {
        Log::critical('Error in mail_sending_helper: ' . $e->getMessage(), ['exception' => $e]);
    }

    return createMailLog($data, $status);
}


function createMailLog($mailData, $status)
{
    return MailLogModel::create([
        'user_id'       => $mailData['user_data']['id'] ?? 0,
        'template_name' => $mailData['template_name'] ?? '',
        'subject'       => $mailData['subject'] ?? '',
        'data'          => json_encode($mailData),
        'status'        => $status,
        'created_by'    => $mailData['user_data']['id'] ?? 0,
    ]);
}


function generateUniqueNumber($prefix = null)
{
    $uniquePart = uniqid(); // Use any method to generate a unique part
    $uniqueNumber = strtoupper($uniquePart);
    if ($prefix) {
        $uniqueNumber = $prefix . '-' . $uniqueNumber;
    }
    return $uniqueNumber;
}


function customEncrypt($string)
{
    $string = strval($string);

    $key = strval(env('SECRET_KEY', 'your-encrypt-key'));

    if (empty($key)) {
        throw new Exception("Encryption key is missing or empty. Set the SECRET_KEY in the environment.");
    }

    $encrypted = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $encrypted .= chr(ord($string[$i]) ^ ord($key[$i % strlen($key)]));
    }

    return base64_encode($encrypted);
}

function customDecrypt($encryptedString)
{
    $key = strval(env('SECRET_KEY', 'your-encrypt-key'));
    $encryptedString = base64_decode($encryptedString);
    $decrypted = '';
    for ($i = 0; $i < strlen($encryptedString); $i++) {
        $decrypted .= chr(ord($encryptedString[$i]) ^ ord($key[$i % strlen($key)]));
    }
    return $decrypted;
}

function generateNextUserCode($role_id, $rep_number = 0)
{
    $prefix = RolesModel::whereKey($role_id)->value('role_prefix') ?? 'DR';

    $number = User::withTrashed()
        ->where('role_id', $role_id)
        ->max('user_code_number') + 1;

    if ($number < 1) {
        $number = 1;
    }

    return [
        'status' => true,
        'code'   => sprintf('%s%04d', $prefix, $number),
        'prefix' => $prefix,
        'number' => $number
    ];
}


function uploadFile($file, $folderPath, $id, $docOf)
{
    $status = false;

    $file_size = $file_name = $folder = $extension = $org_file_name = $message = $file_type = "";

    try {
        $disk = env('MEDIA_DISK', 'public');
        $timestamp = time();
        $extension = strtolower($file->getClientOriginalExtension());
        $org_file_name = $file->getClientOriginalName();

        // Get file size in bytes
        $file_size = $file->getSize();

        // Remove extension from original name for length calculation
        $orgNameWithoutExt = pathinfo($org_file_name, PATHINFO_FILENAME);

        // Create the base part
        $baseName = "{$timestamp}-{$id}-{$docOf}";

        // Remaining characters allowed for original file name part
        $maxLength = 80;
        $extraLength = strlen($baseName . '.' . $extension);

        $allowedForName = $maxLength - $extraLength - 1; // 1 for dash
        if ($allowedForName < 0) $allowedForName = 0;

        // Truncate original name safely
        $safeName = substr($orgNameWithoutExt, 0, $allowedForName);

        // Final file name
        $file_name = "{$baseName}-{$safeName}.{$extension}";

        $folder = "{$folderPath}/{$id}";

        // Ensure the folder exists

        if ($disk != "s3") {
            if (!Storage::disk($disk)->exists($folder)) {
                Storage::disk($disk)->makeDirectory($folder);
            }
        }
        Storage::disk($disk)->putFileAs($folder, $file, $file_name);

        // Determine file type
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $pdfExtensions = ['pdf'];
        $wordExtensions = ['doc', 'docx'];
        $excelExtensions = ['xls', 'xlsx', 'csv'];
        $pptExtensions = ['ppt', 'pptx'];
        $txtExtensions = ['txt'];

        if (in_array($extension, $imageExtensions)) {
            $file_type = 'image';
        } elseif (in_array($extension, $pdfExtensions)) {
            $file_type = 'pdf';
        } elseif (in_array($extension, $wordExtensions)) {
            $file_type = 'word';
        } elseif (in_array($extension, $excelExtensions)) {
            $file_type = 'excel';
        } elseif (in_array($extension, $pptExtensions)) {
            $file_type = 'ppt';
        } elseif (in_array($extension, $txtExtensions)) {
            $file_type = 'text';
        } else {
            $file_type = 'other';
        }

        $status = true;
        $message = 'File uploaded successfully.';
    } catch (\Exception $e) {
        Log::error('File upload failed.', [
            'message' => $e->getMessage(),
            'file'    => isset($file_name) ? $file_name : '',
            'folder'  => isset($folder) ? $folder : '',
        ]);

        $message = 'File upload failed: ' . $e->getMessage();
    }

    return [
        'status' => $status,
        'org_file_name' => $org_file_name,
        'file_name' => $file_name,
        'folder' => $folder,
        'extension' => $extension,
        'file_type' => $file_type,
        'size' => $file_size, // in bytes
        'message' => $message,
    ];
}

function systemActivityTrackerHelper($data, $type, $created_by, $table_name, $table_id = '', $ip_address = '', $description = '')
{
    try {
        $systemActivityData = [];
        $systemActivityData['type'] = $type;
        $systemActivityData['type_of_summary'] = $type;
        $systemActivityData['table_name'] = $table_name;
        $systemActivityData['table_id'] = $table_id;
        $systemActivityData['description'] = $description;
        $systemActivityData['data'] = json_encode($data, true);
        $systemActivityData['ip_address'] = $ip_address;
        $systemActivityData['created_by'] = $created_by;

        SystemActivityTrackerModel::create($systemActivityData);
    } catch (\Exception $e) {
        Log::critical('crm_helpers::systemActivityTrackerHelper() : ');
        Log::critical($e);
    }
}
