<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function addEditBroker(Request $request)
    {
        $status_code = 422;

        $errors_fields = $data = [];
        $status = false;
        $rules = [
            'id' => 'sometimes|nullable|exists:brokers,id,deleted_at,NULL',
            'name' => [
                'required',
                'regex:/^[0-9A-Za-z_ .,]+$/',
                'max:80',
                Rule::unique('brokers', 'name')->whereNull('deleted_at')
                    ->ignore($request->id)
            ],

            'display_name' => [
                'required',
                'regex:/^[0-9A-Za-z_ .,]+$/',
                'max:80',
                Rule::unique('brokers', 'display_name')->whereNull('deleted_at')
                    ->ignore($request->id)
            ],
            'address' => 'sometimes|nullable|string|max:250|regex:/^[a-zA-Z0-9\s\-\.\/;,]*$/',
            'status' => 'required|between:0,1',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id,deleted_at,NULL',
            'ic_product_ids' => 'sometimes|nullable|array',
            'ic_product_ids.*' => 'exists:ic_product,id,deleted_at,NULL',
        ];


        $messages = [
            //
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        try {
            if ($validator->fails()) {
                $response_message = $validator->errors()->first();
                $errors_fields = $validator->errors();
            } else {
                DB::beginTransaction();

                $fields = [
                    'name' => $request->name,
                    'display_name' => $request->display_name,
                    'status' => $request->status,
                    'address' => $request->address,
                ];

                $response_message = 'Broker created successfully.';
                if ($request->id) {

                    $response_message = 'Broker updated successfully.';
                    $fields['updated_by'] = $userInfo['id'];
                    $fields['updated_at'] = now();
                    BrokersModel::where([
                        'id' => $request->id,
                    ])->update($fields);
                    $broker_id = $request->id;
                } else {
                    $fields['code'] = generateUniqueNumber("BRK");
                    $fields['created_by'] = $userInfo['id'];
                    $fields['created_at'] = now();

                    $create_status = BrokersModel::create($fields);
                    $broker_id = $create_status->id;
                }

                $selectedCoordinatorIds = $request->input('user_ids');
                // $selectedIcProductIds = $request->input('ic_product_ids');

                $brokerObject = BrokersModel::find($broker_id);
                // Sync coordinators
                $brokerObject->coordinators()->sync($selectedCoordinatorIds);

                // Sync IC Products
                // $brokerObject->icProducts()->sync($selectedIcProductIds);

                $status = true;
                $status_code = 200;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical('Error in BrokerController::addEditBroker', [
                'exception_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $status = false;
            $status_code = 500;
            Log::info('Request Parameters:', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'parameters' => $request->all(),
            ]);
            $response_message = ERROR_MESSAGE;

            saveErrorLog($e->getMessage(), true, $e->getLine(), $e->getFile());
        }

        return response()->json(['status' => $status, 'is_show_alert' => IS_SHOW_ALERT, 'is_toast_alert' => IS_TOAST_ALERT, 'errors_fields' => $errors_fields, 'data' => $data, 'message' => $response_message,], $status_code);
    }
}
