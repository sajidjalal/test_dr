<?php

namespace App\Http\Controllers;

use App\Models\DeviceTokenModel;
use App\Models\InAppNotificationsModel;
use App\Models\PushNotificationsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function InAppNotification($user_id, $created_by, $title, $body, $type = "info", $code = null,  $data = null, $table_name = null, $table_id = null)
    {

        $notification = InAppNotificationsModel::create([
            'user_id' => $user_id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'code' => $code,
            'data' => json_encode($data),
            'table_name' => $table_name,
            'table_id' => $table_id,
            'created_by' => $created_by,
        ]);

        DB::table('users')->where('id', $user_id)->increment('notification_count');

        return $notification;
    }

    public function PushAppNotification($user_id, $created_by, $title, $body, $type = "info", $data = null)
    {

        $notification = PushNotificationsModel::create([
            'user_id' => $user_id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
            'created_by' => $created_by,
        ]);

        $userDetails = DeviceTokenModel::where('user_id', $user_id)->orderBy('id', 'desc')->first();
        if ($userDetails && $userDetails->device_token) {
            $status = 2;
            $responseData = $this->sendFirebaseNotification($userDetails->device_token, $title, $body);
            if ($responseData['status'] == 1) {
                $status = 1;
            }
            $notification->status = $status;
            $notification->save();
        }

        $notification->status = 1;
        $notification->save();
    }
}
