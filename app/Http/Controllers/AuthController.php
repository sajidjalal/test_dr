<?php

namespace App\Http\Controllers;

use App\Models\DeviceTokenModel;
use App\Models\OtpHistoryModel;
use App\Models\RolesModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public $ip_address;

    public function __construct(Request $request)
    {
        $this->ip_address = $request->header('X-Forwarded-For') ?? $request->ip();
        $this->ip_address = explode(',', $this->ip_address)[0];
    }

    public function generateOtp(Request $request)
    {
        $data = [];
        $status = false;
        $status_code = 422;
        $errors_fields = [];
        $response_message = 'Unable to send OTP. Please try again later.';
        try {
            $rules = [
                'template_code' => 'required|exists:sms_template,template_code',
                'mobile_number' => 'nullable|required_without:email_id|digits:10|numeric|exists:users,mobile_number',
                'email_id' => 'nullable|required_without:mobile_number|email|exists:users,email_id|max:80',
            ];

            $messages = [
                'mobile_number.required' => 'Mobile number is required',
                'email_id.required' => 'Email Id is required',
            ];
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $response_message = $validator->errors()->first();
                $errors_fields = $validator->errors();
            } else {

                $fetch_account_query = User::select('id', 'role_id', 'mobile_number', 'email_id', 'status');

                if (isset($request->mobile_number)) {
                    $fetch_account_query->where('mobile_number', $request->mobile_number);
                }

                if (isset($request->email_id)) {
                    $fetch_account_query->where('email_id', $request->email_id);
                }

                $fetch_account = $fetch_account_query->first();

                // If account doesn't exist or is deactivated
                if ($fetch_account) {
                    if ($fetch_account->status == 0) {
                        $response_message = 'Account is deactivated. Please contact the administrator.';
                        $errors_fields = [
                            'mobile_number' => $response_message,
                            'email_id' => $response_message,
                        ];
                    } else {
                        $otp_code = rand(pow(10, SMS_LEN - 1), pow(10, SMS_LEN) - 1);
                        $this->send_account_verify_otp($request, $otp_code);

                        $status = true;
                        $status_code = 200;
                        $response_message = 'OTP sent successfully';
                    }
                } else {
                    $response_message = 'Account Not found';
                }

                // Check if the role is active
                $roleStatus = RolesModel::where([
                    'status' => 1,
                    'id' => $fetch_account->role_id,
                ])->exists();

                if (!$roleStatus) {
                    $response_message = 'Account is deactivated. Please contact the admin.';
                    $errors_fields = [
                        'mobile_number' => $response_message,
                        'email_id' => $response_message,
                    ];

                    return Response::json([
                        'status' => $status,
                        'data' => $data,
                        'message' => $response_message,
                        'errors_fields' => $errors_fields,
                    ], $status_code,);
                }
            }
        } catch (\Exception $e) {
            saveErrorLog($e->getMessage(), false, 0, $e->getLine(), $e->getFile(), true);

            Log::critical('AuthController::generateOtp');
            Log::critical($e);
        }

        return Response::json([
            'status' => $status,
            'data' => $data,
            'message' => $response_message,
            'errors_fields' => $errors_fields,
        ], $status_code,);
    }

    public function send_account_verify_otp($request, $otp_code, $old_otp_time_from = NULL, $old_otp_time_to = NULL)
    {
        $current_time = Carbon::now();
        $otp_time_from = $current_time->format(DB_FULL_DATE_TIME);
        $otp_time_to = Carbon::parse($current_time->addMinutes(OTP_EXPIRY_TIME))->format(DB_FULL_DATE_TIME);

        if ($old_otp_time_from && $old_otp_time_to) {
            $otp_time_from = Carbon::parse($old_otp_time_from)->format(DB_FULL_DATE_TIME);
            $otp_time_to = Carbon::parse($old_otp_time_to)->format(DB_FULL_DATE_TIME);
        }

        $OtpHistoryFields = [
            'user_id' => $request->user_id ?? null,
            'type' => 'login',
            'template_code' => $request->template_code,
            'email_id' => $request->email_id ?? '',
            'mobile_number' => $request->mobile_number ?? '',
            'otp_code' => $otp_code,
            'otp_time_from' => $otp_time_from,
            'otp_time_to' => $otp_time_to,
            'generate_request_ip' => $this->ip_address,
        ];

        if ($request->mobile_number) {
            $OtpHistoryFields['sent_on_mobile'] = 1;
            SendMobileOTP($request->mobile_number, $otp_code, $request->template_code);
        }

        if ($request->email_id) {
            $user_profile = [];
            $user_profile['id'] = 0;

            $mail_data = [];
            $mail_data['user_data'] = $user_profile;
            $mail_data['user_type'] = $request->request_type;
            $mail_data['email_id'] = $request->email_id;
            $mail_data['otp_code'] = $otp_code;
            $mail_data['template_name'] = 'mail.verifiedOtp';
            $mail_data['subject'] = $request->request_type . ' Account Verification';

            if (env('IS_SEND_GRID_MAIL_USE')) {
                $sendGridMailData = ['mail_data' => $mail_data];
                $data = [
                    "subject" => $mail_data['subject'],
                    "sendGridMailData" => $sendGridMailData,
                    'html_content' => view($mail_data['template_name'], $sendGridMailData)->render()
                ];
                $cc = $bcc = false;
                if (IS_BCC_SEND) {
                    $bcc = BCC_MAIL_ID;
                }
                $attachments = [];

                sendGridCurl($request->email_id, FROM_MAIL_ID, FROM_MAIL_NAME, $data, env('SEND_GRID_TEMPLATE_ID'), FROM_MAIL_ID,  $cc, '', $bcc, $attachments);
            } else {
                mail_sending_helper($mail_data);
            }


            $OtpHistoryFields['sent_on_email'] = 1;
        }

        $OtpHistoryFields['email_id'] = $request->email_id ?? NULL;
        $OtpHistoryFields['mobile_number'] = $request->mobile_number ?? NULL;


        OtpHistoryModel::create($OtpHistoryFields);
    }

    public function verifyOtp(Request $request)
    {
        $data = [];
        $status = false;
        $status_code = 422;
        $errors_fields = [];
        $response_message = 'Unable To Verify OTP.';

        try {
            $rules = [
                'template_code' => 'required|exists:sms_template,template_code',
                'mobile_number' => 'nullable|required_without:email_id|digits:10|numeric|exists:users,mobile_number',
                'email_id' => 'nullable|required_without:mobile_number|email|exists:users,email_id|max:80',
                'otp_code' => 'required',
            ];

            $messages = [
                'id.required' => 'ID is invalid',
            ];
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $response_message = $validator->errors()->first();
                $errors_fields = $validator->errors();
            } else {

                $otpQuery = OtpHistoryModel::where([
                    ['template_code', $request->template_code],
                    ['otp_code', $request->otp_code],
                    ['generate_request_ip', $this->ip_address],
                    ['is_verify', 0],
                    ['type', 'login'],
                ])
                    ->when($request->filled('mobile_number'), fn($q) => $q->where('mobile_number', $request->mobile_number))
                    ->when($request->filled('email_id'), fn($q) => $q->where('email_id', $request->email_id))
                    ->latest();

                $previousRecord = $otpQuery->first();

                if ($previousRecord) {

                    $now = Carbon::now();

                    $start = Carbon::createFromTimeString($previousRecord->otp_time_from);
                    $end = Carbon::createFromTimeString($previousRecord->otp_time_to);

                    if ($now->between($start, $end)) {

                        // Check for active user
                        $userQuery = User::where('status', 1)
                            ->when($request->filled('mobile_number'), fn($q) => $q->where('mobile_number', $request->mobile_number))
                            ->when($request->filled('email_id'), fn($q) => $q->orWhere('email_id', $request->email_id));

                        $is_user_exist = $userQuery->first();

                        if ($request->ajax()) {
                            Auth::guard('web')->login($is_user_exist);

                            $data['redirect'] = route('dashboard');
                            if (!in_array($is_user_exist->role_id, ADMIN_ROLE_LIST)) {
                                $data['redirect'] = route('task.list');
                            }
                        } else {
                            // $token = JWTAuth::fromUser($is_user_exist);

                            $token = JWTAuth::claims([
                                'id' => $is_user_exist->id,
                                'email_id' => $is_user_exist->email_id,
                                'mobile_number' => $is_user_exist->mobile_number,
                                'role_id' => $is_user_exist->role_id,
                            ])->fromUser($is_user_exist);

                            header('Authorization:' . $token);

                            $device_details = DeviceTokenModel::create([
                                'user_id' => $is_user_exist->id,
                                'device_token' => $request->device_token,
                                'device_id' => $request->device_id,
                                'device_model' => $request->device_model,
                                'os' => $request->os,
                                'os_version' => $request->os_version,
                                'app_version' => $request->app_version,
                            ]);
                            $is_user_exist->save();
                        }

                        $status_code = 200;
                        $status = true;
                        $response_message = 'OTP verify successfully';

                        $previousRecord->update([
                            'is_verify' => '1',
                            'verify_request_ip' => $this->ip_address,
                        ]);
                    } else {
                        $response_message = 'OTP Expired';
                        $errors_fields = [
                            'otp_code' => 'OTP Expired',
                        ];
                    }
                } else {
                    $response_message = 'Invalid OTP Enter';
                    $errors_fields = [
                        'otp_code' => 'Invalid OTP Enter',
                    ];
                }
            }
        } catch (\Exception $e) {
            saveErrorLog($e->getMessage(), 'AuthController::verifyOtp', $e->getLine(), $e->getFile());

            Log::critical('AuthController::verifyOtp');
            Log::critical($e);
        }

        return Response::json([
            'status' => $status,
            'data' => $data,
            'message' => $response_message,
            'errors_fields' => $errors_fields,
        ], $status_code,);
    }

    public function logout(Request $request)
    {
        if (session()->has('impersonate')) {
            return $this->leaveImpersonation();
        }

        Auth::logout();
        $request->session()->invalidate();

        // Regenerate the CSRF token after logout
        $request->session()->regenerateToken();

        return redirect('/login')->with('message', 'Logged out successfully.');
    }

    public function impersonate($id)
    {
        $userToImpersonate = User::findOrFail($id);
        if (Auth::user()->canImpersonate()) {
            session(['impersonate' => Auth::id()]);
            Auth::user()->impersonate($userToImpersonate);
        }
        return redirect('/dashboard');
    }

    public function leaveImpersonation()
    {
        if (session()->has('impersonate')) {
            $superAdminId = session()->get('impersonate');
            Auth::loginUsingId($superAdminId);
            session()->forget('impersonate');
            return redirect()->route('dashboard')->with('message', 'You have stopped impersonating.');
        }

        return redirect()->back()->withErrors('No impersonation session found.');
    }
}
