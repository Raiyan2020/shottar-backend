<?php

namespace App\Helpers;

class Firebase
{
    public static function notification($notifiable, $data)
    {
        $tokens = $notifiable->devices()->pluck('token')->toArray();
        self::notifyByFirebase($tokens, $data);
    }

    public static function notifyByFirebase($tokens, $data = [], $is_notification = true)
    {
        $registrationIDs = $tokens;
        $fcmMsg = [
            'title' => $data['title'],
            'body' => $data['body'],
            'sound' => "default",
            'color' => "#203E78",
        ];
        $fcmFields = [
            'registration_ids' => $registrationIDs,
            'priority' => 'high',
            'data' => $data,
        ];
        if ($is_notification) {
            $fcmFields['notification'] = $fcmMsg;
        }

        $headers = [
            'Authorization: key='.config('services.fcm.server_key'),
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
//        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/3.04679852707/messages:send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
        $result = curl_exec($ch);
        curl_close($ch);
        info($result);
        return $result;
    }

    public static function sendFCMTopic($data, $target = 'general')
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $server_key = config('services.fcm.server_key');

        $fields = [];
        $fields['data'] = $data;
        $fcmMsg = [
            'title' => $data['title'],
            'body' => $data['body'],
            'sound' => "default",
            'color' => "#203E78",
        ];
        $fields['notification'] = $fcmMsg;
        if (is_array($target)) {
            $fields['registration_ids'] = $target;
        } else {
            $fields['to'] = '/topics/'.$target; // $target is Topic_name
        }
        $headers = [
            'Content-Type:application/json',
            'Authorization:key='.$server_key,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === false) {
            die('Oops! FCM Send Error: '.curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}
