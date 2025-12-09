<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;

class UserController extends Controller
{
    public $ip_address;

    public function __construct(Request $request)
    {
        $this->ip_address = $request->header('X-Forwarded-For') ?? $request->ip();
        $this->ip_address = explode(',', $this->ip_address)[0];
    }


    public function userRegister(Request $request)
    {
        $status_code = 422;
        $errors_fields = $data = [];
        $status = false;
        $response_message = ERROR_MESSAGE;

        $rules = [
            // 'email' => ['required', 'email', 'max:50', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', Rule::unique('users', 'email')->whereNull('deleted_at'),],
            // 'mobile_number' => ['required', 'numeric', 'digits:10', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users', 'mobile_number')->whereNull('deleted_at'),],
            'first_name' => 'required|regex:/^[A-Za-z_ ]+$/|max:50',
            'middle_name' => 'sometimes|nullable|string|regex:/^[A-Za-z_ ]+$/|max:20',
            'last_name' => 'sometimes|nullable|string|regex:/^[A-Za-z_ ]+$/|max:20',
            'dob' => 'sometimes|nullable|date_format:Y-m-d',
            'city' => 'sometimes|required|string|max:50|regex:/^[a-zA-Z0-9_ .,\-\/]*(\([a-zA-Z0-9_ .,\-\/]+\))?[a-zA-Z0-9_ .,\-\/]*$/',
            'state' => 'sometimes|required|string|max:50|regex:/^[a-zA-Z0-9\s\-\.\/;,]*$/',
            'address' => 'sometimes|required|string|max:250|regex:/^[a-zA-Z0-9\s\-\.\/;,]*$/',
            'aadhar_number' => ['sometimes', 'nullable', 'digits:12', 'numeric', 'regex:/^[0-9]{12}$/', Rule::unique('users', 'aadhar_number')->whereNull('deleted_at'),],
            'pan_number' => ['sometimes', 'nullable', 'regex:/^[a-zA-Z]{5}[0-9]{4}[a-zA-Z]{1}$/', Rule::unique('users', 'pan_number')->whereNull('deleted_at'),],
            'password' => ['nullable', 'string', 'min:8', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[@$!%*#?&]/', 'confirmed'],
        ];

        $rules = [
            'email' => [
                'required',
                'email',
                'max:50',
                function ($attribute, $value, $fail) {
                    $enc = customEncrypt($value);
                    $exists = User::where('email', $enc)->exists();
                    if ($exists) {
                        $fail("The $attribute has already been taken.");
                    }
                },
            ],

            'mobile_number' => [
                'required',
                'digits:10',
                'regex:/^[6-9][0-9]{9}$/',
                function ($attribute, $value, $fail) {
                    $enc = customEncrypt($value);
                    $exists = User::where('mobile_number', $enc)->exists();

                    if ($exists) {
                        $fail("The $attribute has already been taken.");
                    }
                },
            ],
        ];



        $messages = [
            //
            'aadhar_file.mimes' => 'Only jpg, jpeg, png, pdf files are allowed for Aadhar.',
            'pan_file.mimes' => 'Only jpg, jpeg, png, pdf files are allowed for PAN.',
            'gst_file.mimes' => 'Only jpg, jpeg, png, pdf files are allowed for GST.',
            'pan_number.regex' => 'The pan number field format is invalid.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        DB::beginTransaction();
        try {
            if ($validator->fails()) {
                $response_message = $validator->errors()->first();
                $errors_fields = $validator->errors();
            } else {
                $full_name = $request->first_name;
                if ($request->middle_name) {
                    $full_name .= ' ' . $request->middle_name;
                }
                if ($request->last_name) {
                    $full_name .= ' ' . $request->last_name;
                }

                $fields = [
                    'role_id' => DOCTOR_ROLE_ID,
                    'salutations_id' => $request->salutations_id,
                    'full_name' => $full_name,
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'mobile_number' => $request->mobile_number,
                    'email' => $request->email,
                    'date_of_birth' => $request->dob,
                    'date_of_marriage' => $request->date_of_marriage,
                    'gender_master_id' => $request->gender,
                    'pincode_master_id' => $request->pincode_id,
                    'city' => $request->city,
                    'state' => $request->state,
                    'address' => $request->address,
                    'aadhar_number' => $request->aadhar_number,
                    'pan_number' => strtoupper($request->pan_number),
                    'gst_number' => $request->gst_number,
                    'education_master_id' => $request->education,
                    'martial_status_id' => $request->martial_status,
                    'branch_code' => $request->branch_code,
                ];

                $activity_type = 'create';
                $table_id = '';
                $response_message = 'Account created successfully.';

                $fields['created_by'] = 0;

                $fields['created_at'] = now();
                $user_code = generateNextUserCode(DOCTOR_ROLE_ID);

                $rep_number = 0;
                do {
                    $user_code = generateNextUserCode(DOCTOR_ROLE_ID, $rep_number);
                    $fields['user_code'] = $user_code['code'];

                    $rep_number = $rep_number + 1;

                    $is_user_code_exist = User::withTrashed()
                        ->where('user_code', $user_code['code'])
                        ->exists();
                } while ($is_user_code_exist);

                $fields['user_code'] = $user_code['code'];
                $fields['user_code_number'] = $user_code['number'];

                if ($request->has('password')) {
                    $password  = $request->password;
                    $fields['password'] =  Hash::make($password);
                }

                $insertedRecord = User::create($fields);

                $table_id = $insertedRecord->id;

                $notificationController = app(NotificationController::class);
                // Create a new notification
                $type = "User Created:";
                $title = "account created successfully";
                $body = "";
                $notificationController->InAppNotification($insertedRecord->id, $insertedRecord->id, $title, $body, $type, $insertedRecord->user_code, $data = null, 'user_create', $insertedRecord->id);

                if ($request->hasFile('aadhar_file')) {
                    $uploadResult = uploadFile($request->file('aadhar_file'), DOCTOR_DOCUMENT_PATH, $table_id, 'aadhar');

                    if ($uploadResult['error_code'] != 0) {
                        return response()->json([
                            'status' => false,
                            'is_show_alert' => IS_SHOW_ALERT,
                            'is_toast_alert' => IS_TOAST_ALERT,
                            'errors_fields' => $uploadResult['message'],
                            'data' => $data,
                            'message' => $uploadResult['message'],
                        ], 422);
                    }

                    $fields['aadhar_file'] = $uploadResult['file_name'];
                    $insertedRecord = User::where(['id' => $table_id])->update($fields);
                }

                systemActivityTrackerHelper($fields, $activity_type, $insertedRecord->id, (new User())->getTable(), $table_id, $this->ip_address);

                $status = true;
                $status_code = 200;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical('Error in UserController::userRegister', [
                'exception_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $status = 500;

            Log::info('Request Parameters:', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'parameters' => $request->all(),
            ]);
            $response_message = ERROR_MESSAGE;

            saveErrorLog($e->getMessage(), 0, $e->getLine(), $e->getFile());
        }

        return response()->json([
            'status' => $status,
            'is_show_alert' => IS_SHOW_ALERT,
            'is_toast_alert' => IS_TOAST_ALERT,
            'errors_fields' => $errors_fields,
            'data' => $data,
            'message' => $response_message,
            'redirect' => route('login'),
        ], $status_code);
    }
}
