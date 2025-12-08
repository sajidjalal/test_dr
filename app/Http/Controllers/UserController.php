<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            'email' => 'required|sometimes|exists:users,id,deleted_at,NULL',
            'salutations_id' => 'sometimes|nullable|numeric',
            'first_name' => 'required|regex:/^[A-Za-z_ ]+$/|max:50',
            'middle_name' => 'sometimes|nullable|string|regex:/^[A-Za-z_ ]+$/|max:20',
            'last_name' => 'sometimes|nullable|string|regex:/^[A-Za-z_ ]+$/|max:20',
            'dob' => 'sometimes|nullable|date_format:Y-m-d',
            'city' => 'sometimes|required|string|max:50|regex:/^[a-zA-Z0-9_ .,\-\/]*(\([a-zA-Z0-9_ .,\-\/]+\))?[a-zA-Z0-9_ .,\-\/]*$/',
            'state' => 'sometimes|required|string|max:50|regex:/^[a-zA-Z0-9\s\-\.\/;,]*$/',
            'address' => 'sometimes|required|string|max:250|regex:/^[a-zA-Z0-9\s\-\.\/;,]*$/',
            'aadhar_number' => 'sometimes|nullable|digits:12|numeric|regex:/^[0-9]{12}$/|exists:users,aadhar_number,deleted_at,NULL',
            'pan_number' => 'sometimes|nullable|string|regex:/^[a-zA-Z]{5}[0-9]{4}[a-zA-Z]{1}$/||exists:users,pan_number,deleted_at,NUL',
            'password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/[a-z]/',      // at least one lowercase
                'regex:/[A-Z]/',      // at least one uppercase
                'regex:/[0-9]/',      // at least one number
                'regex:/[@$!%*#?&]/', // at least one special character
                'confirmed'           // confirms against password_confirmation
            ],
        ];

        // log::info('check mail data' . $request->id);

        $is_login = 0;
        if (isset($request->is_login)) {
            if ($request->is_login == 'on') {
                $is_login = 1;
            }
        }

        $rules['mobile_number'] = [
            'required',
            'digits:10',
            'numeric',
            'regex:/^[6-9][0-9]{9}$/',
        ];

        $rules['email'] = [
            'required',
            'email',
            'max:50',
            'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        ];

        if ($is_login) {
            $rules['mobile_number'] = [
                'required',
                'digits:10',
                'numeric',
                'regex:/^[6-9][0-9]{9}$/',
                function ($attribute, $value, $fail) use ($request) {
                    // Encrypt the mobile number
                    $encryptedMobileNumber = customEncrypt($value);

                    // Perform the optimized query
                    $exists = DB::table('customers')
                        ->whereNull('deleted_at')
                        ->where('mobile_number', $encryptedMobileNumber)
                        ->where('is_login', 1)
                        ->when($request->id, function ($query) use ($request) {
                            // Ignore the current record if updating
                            $query->where('id', '!=', $request->id);
                        })->exists(); // Efficient existence check

                    if ($exists) {
                        $fail('The mobile number is already in use. Please use a different mobile number or disable login for this account.');
                    }
                },
            ];

            $rules['email'] = [
                'required',
                'email',
                'max:50',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                function ($attribute, $value, $fail) use ($request) {
                    // Encrypt the email address
                    $encryptedEmail = customEncrypt($value);

                    // Perform the optimized query
                    $exists = DB::table('customers')
                        ->whereNull('deleted_at')
                        ->where('email', $encryptedEmail)
                        ->where('is_login', 1)
                        ->where('role_id', '!=', POS_ROLE_ID) // Additional condition for role_id
                        ->when($request->id, function ($query) use ($request) {
                            // Ignore the current record if updating
                            $query->where('id', '!=', $request->id);
                        })->exists(); // Efficient existence check

                    if ($exists) {
                        $fail('The email address is already in use. Please use a different email or disable login for this account.');
                    }
                },
            ];
        }




        $messages = [
            //
            'aadhar_file.mimes' => 'Only jpg, jpeg, png, pdf files are allowed for Aadhar.',
            'pan_file.mimes' => 'Only jpg, jpeg, png, pdf files are allowed for PAN.',
            'gst_file.mimes' => 'Only jpg, jpeg, png, pdf files are allowed for GST.',
            'pan_number.regex' => 'The pan number field format is invalid.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

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
                    'salutations_id' => $request->salutations_id,
                    'full_name' => $full_name,
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'mobile_number' => customEncrypt($request->mobile_number),
                    'email' => customEncrypt($request->email),
                    'date_of_birth' => $request->dob,
                    'date_of_marriage' => $request->date_of_marriage,
                    'gender_master_id' => $request->gender,
                    'is_login' => $is_login,
                    'status' => STATUS_ACTIVE,
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
                    'customer_company_id' => $request->parent_company_name,
                    'customer_category' => $request->customer_category,
                ];

                $activity_type = 'create';
                $table_id = '';
                $response_message = 'Customer created successfully.';
                if ($request->id) {
                    $response_message = 'Customer updated successfully.';
                    $fields['updated_by'] = $userInfo['id'];
                    $fields['updated_by_role_id'] = $userInfo['role_id'];
                    $fields['updated_at'] = now();
                    $tableStatus = CustomersModel::where([
                        'id' => $request->id,
                    ])->update($fields);
                    $activity_type = 'update';

                    $table_id = $request->id;

                    if ($brokerInfo['update_notification']) {
                        // Create a new notification
                        $notificationController = app(NotificationController::class);

                        // Create a new notification
                        $notificationController->createNotification(
                            'customer_update',
                            $userInfo['role_id'],
                            'high',
                            'Customer Management',
                            'Customer Updated',
                            [
                                'message' => "Customer {$full_name} updated successfully.",
                            ],
                            route('edit.customer', ['id' => customEncryptForUrl($table_id)]),
                            $userInfo['id']
                        );
                    }
                } else {
                    $fields['created_by'] = $userInfo['id'];
                    $fields['created_by_role_id'] = $userInfo['role_id'];
                    $fields['created_at'] = now();
                    $user_code = generateNextUserCode(CUSTOMER_ROLE_ID); //temp

                    $rep_number = 0;
                    do {
                        $user_code = generateNextUserCode(CUSTOMER_ROLE_ID, $rep_number); //temp
                        $fields['user_code'] = $user_code['code'];

                        $rep_number = $rep_number + 1;

                        $is_user_code_exist = CustomersModel::withTrashed()
                            ->where('user_code', $user_code['code'])
                            ->exists();
                    } while ($is_user_code_exist);

                    $fields['user_code'] = $user_code['code'];
                    $fields['user_code_number'] = $user_code['number'];
                    $fields['password'] =  Hash::make($request->password);

                    $fields['selected_financial_year'] = FinancialYearModel::orderBy('id', 'desc')->first()->id;

                    $tableStatus = CustomersModel::create($fields);
                    $table_id = $tableStatus->id;

                    if ($tableStatus) {
                        // Create a new notification
                        $notificationController = app(NotificationController::class);

                        // Create a new notification
                        $notificationController->createNotification(
                            'customer_create',
                            $userInfo['role_id'],
                            'high',
                            'Customer Management',
                            'Customer Created',
                            [
                                'message' => "Customer {$tableStatus->full_name} created successfully.",
                            ],
                            route('edit.customer', ['id' => customEncryptForUrl($table_id)]),
                            $userInfo['id']
                        );
                    }
                }

                if ($table_id) {
                    $data['add_gi_link'] = route('add.gi.policy');
                    $data['add_li_link'] = route('add.li.policy');
                    $data['customer_id'] = customEncryptForUrl($table_id);
                }

                if ($request->hasFile('aadhar_file')) {
                    $uploadResult = uploadFile($request->file('aadhar_file'), CUSTOMER_DOCUMENT_PATH, $table_id, 'aadhar');

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
                }

                if ($request->hasFile('pan_file')) {
                    $uploadResult = uploadFile($request->file('pan_file'), CUSTOMER_DOCUMENT_PATH, $table_id, 'pan');

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

                    $fields['pan_file'] = $uploadResult['file_name'];
                }

                if ($request->hasFile('gst_file')) {
                    $uploadResult = uploadFile($request->file('gst_file'), CUSTOMER_DOCUMENT_PATH, $table_id, 'gst');

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

                    $fields['gst_file'] = $uploadResult['file_name'];
                }

                $tableStatus = CustomersModel::where(['id' => $table_id])->update($fields);

                systemActivityTrackerHelper($fields, $activity_type, $userInfo['id'], (new CustomersModel())->getTable(), $table_id, $this->ip_address);

                $status = true;
                $status_code = 200;
            }
        } catch (\Exception $e) {
            Log::critical('Error in CustomersController::addEditCustomer', [
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
            trackBrokerErrorLog($e->getMessage(), $userInfo, $e->getLine(), $e->getFile());
        }

        return response()->json(['status' => $status, 'is_show_alert' => IS_SHOW_ALERT, 'is_toast_alert' => IS_TOAST_ALERT, 'errors_fields' => $errors_fields, 'data' => $data, 'message' => $response_message, 'redirect' => route('customer')], $status_code);
    }
}
