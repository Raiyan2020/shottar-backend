<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;

class FirebaseService
{
    public function getAccessToken()
    {
        $serviceAccountFile = $this->resolveCredentialsPath();
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        if (! is_file($serviceAccountFile)) {
            throw new \Exception(
                'Firebase service account file not found at: '.$serviceAccountFile
                .' — upload the JSON to storage/firebase/ and set FIREBASE_CREDENTIALS in .env'
            );
        }

        $credentials = new ServiceAccountCredentials($scopes, $serviceAccountFile);
        $authToken = $credentials->fetchAuthToken();

        if (! isset($authToken['access_token'])) {
            throw new \Exception('Failed to retrieve Firebase access token.');
        }

        return $authToken['access_token'];
    }

    protected function resolveCredentialsPath(): string
    {
        $configured = config('services.firebase.credentials')
            ?: env('FIREBASE_CREDENTIALS')
            ?: env('GOOGLE_APPLICATION_CREDENTIALS');

        if (is_string($configured) && $configured !== '') {
            // Absolute path or project-relative path.
            if (str_starts_with($configured, '/')) {
                return $configured;
            }

            return base_path($configured);
        }

        $candidates = [
            storage_path('firebase/service_account.json'),
            storage_path('firebase/firebase_credentials.json'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return storage_path('firebase/service_account.json');
    }
}
