<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;

class FirebaseService
{
    public function getAccessToken()
    {
        // مسار ملف حساب الخدمة
        $serviceAccountFile = storage_path('firebase/service_account.json');
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        // التحقق من وجود الملف
        if (!file_exists($serviceAccountFile)) {
            throw new \Exception('Service account file not found at: ' . $serviceAccountFile);
        }

        // تحميل بيانات حساب الخدمة
        $credentials = new ServiceAccountCredentials($scopes, $serviceAccountFile);

        // جلب رمز التوثيق
        $authToken = $credentials->fetchAuthToken();

        // التحقق من وجود رمز الوصول
        if (!isset($authToken['access_token'])) {
            throw new \Exception('Failed to retrieve access token.');
        }

        // إرجاع رمز الوصول فقط
        return $authToken['access_token'];
    }

}
