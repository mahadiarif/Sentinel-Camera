<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CameraController extends Controller
{
    public function frameView(int $cameraId)
    {
        $path = public_path("frames/cam_{$cameraId}.jpg");

        if (!file_exists($path) || filesize($path) === 0) {
            return response('', 204);
        }

        $data = @file_get_contents($path);
        if ($data === false) {
            return response('', 204);
        }

        return response($data, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => strlen($data),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function streamView(Request $request, int $cameraId)
    {
        $path = public_path("frames/cam_{$cameraId}.jpg");
        $boundary = 'frame';

        return response()->stream(function () use ($path, $boundary) {
            @set_time_limit(0);
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');

            $lastHash = null;
            $idleLoops = 0;

            while (true) {
                clearstatcache(true, $path);

                if (connection_aborted()) {
                    break;
                }

                if (!file_exists($path) || filesize($path) === 0) {
                    $idleLoops++;
                    usleep(120000);
                    if ($idleLoops > 300) {
                        break;
                    }
                    continue;
                }

                $data = @file_get_contents($path);
                if ($data === false || $data === '') {
                    usleep(120000);
                    continue;
                }

                $hash = md5($data);
                if ($hash === $lastHash) {
                    usleep(45000);
                    continue;
                }

                $lastHash = $hash;
                $idleLoops = 0;

                echo "--{$boundary}\r\n";
                echo "Content-Type: image/jpeg\r\n";
                echo 'Content-Length: ' . strlen($data) . "\r\n\r\n";
                echo $data . "\r\n";

                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();

                usleep(30000);
            }
        }, 200, [
            'Content-Type' => "multipart/x-mixed-replace; boundary={$boundary}",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
