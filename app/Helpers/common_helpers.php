<?php

use App\Models\ErrorLogModel;
use App\Models\MailLogModel;
use App\Models\SmsTemplateModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            $message->to($data['email_id'])->subject($data['subject']);
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
