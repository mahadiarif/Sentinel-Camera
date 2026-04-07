<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CameraConfigController extends Controller
{
    private const VENDOR_PROFILES = [
        'generic' => [
            'label' => 'Generic IP Camera',
            'protocols' => ['rtsp', 'http', 'https'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/stream',
            'help' => 'Use this for any camera with a custom RTSP or MJPEG path.',
        ],
        'hikvision' => [
            'label' => 'Hikvision',
            'protocols' => ['rtsp'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/Streaming/Channels/{channel}{stream_suffix}',
            'streams' => [
                'main' => 'main stream',
                'sub' => 'sub stream',
            ],
            'help' => 'Common Hikvision pattern: channel 1 main stream => /Streaming/Channels/101',
        ],
        'dahua' => [
            'label' => 'Dahua',
            'protocols' => ['rtsp'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/cam/realmonitor?channel={channel}&subtype={stream_index}',
            'streams' => [
                'main' => 'main stream',
                'sub' => 'sub stream',
            ],
            'help' => 'Dahua uses channel + subtype in the RTSP query string.',
        ],
        'uniview' => [
            'label' => 'Uniview',
            'protocols' => ['rtsp'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/media/video{stream_index}',
            'streams' => [
                'main' => 'main stream',
                'sub' => 'sub stream',
            ],
            'help' => 'Uniview commonly exposes /media/video1 and /media/video2.',
        ],
        'axis' => [
            'label' => 'Axis',
            'protocols' => ['rtsp', 'http', 'https'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/axis-media/media.amp',
            'help' => 'Axis often uses /axis-media/media.amp for both RTSP and MJPEG style feeds.',
        ],
        'reolink' => [
            'label' => 'Reolink',
            'protocols' => ['rtsp'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/h264Preview_{channel_padded}_{stream_code}',
            'streams' => [
                'main' => 'clear/main stream',
                'sub' => 'fluent/sub stream',
            ],
            'help' => 'Reolink NVR/IP cameras usually use h264Preview_01_main or h264Preview_01_sub.',
        ],
        'tapo' => [
            'label' => 'TP-Link Tapo',
            'protocols' => ['rtsp'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/stream1',
            'streams' => [
                'main' => 'stream1',
                'sub' => 'stream2',
            ],
            'help' => 'Most Tapo cameras expose /stream1 and /stream2 over RTSP.',
        ],
        'ezviz' => [
            'label' => 'EZVIZ / Hikvision Cloud-linked',
            'protocols' => ['rtsp'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/h264_stream',
            'help' => 'Some EZVIZ firmwares expose /h264_stream locally.',
        ],
        'custom' => [
            'label' => 'Custom Preset',
            'protocols' => ['rtsp', 'http', 'https'],
            'default_protocol' => 'rtsp',
            'default_port' => 554,
            'template' => '/',
            'help' => 'Use your own path with host, credentials, and port fields.',
        ],
    ];

    public function index()
    {
        $cameras = Camera::query()
            ->orderBy('id')
            ->get()
            ->map(function (Camera $camera) {
                $camera->form_settings = $this->buildFormSettings($camera);
                return $camera;
            });

        $stats = [
            'total' => $cameras->count(),
            'active' => $cameras->where('status', 'active')->count(),
            'network' => $cameras->where('type', 'rtsp')->count(),
            'usb' => $cameras->where('type', 'usb')->count(),
        ];

        $vendorProfiles = self::VENDOR_PROFILES;

        return view('cameras.index', compact('cameras', 'stats', 'vendorProfiles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $camera = Camera::create($this->validateCamera($request));

        return redirect()
            ->route('cameras.index')
            ->with('status', "{$camera->name} added. The AI worker will sync active cameras shortly.");
    }

    public function update(Request $request, Camera $camera): RedirectResponse
    {
        $camera->update($this->validateCamera($request, $camera));

        return redirect()
            ->route('cameras.index')
            ->with('status', "{$camera->name} updated. The AI worker will sync the new settings shortly.");
    }

    public function destroy(Camera $camera): RedirectResponse
    {
        $name = $camera->name;
        $camera->delete();

        return redirect()
            ->route('cameras.index')
            ->with('status', "{$name} removed. The AI worker will stop using it on the next sync.");
    }

    public function internalIndex(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if (!$token || $token !== config('app.internal_api_token', env('INTERNAL_API_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cameras = Camera::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get(['id', 'name', 'type', 'vendor', 'source', 'location', 'settings']);

        $seenSources = [];
        $cameras = $cameras
            ->reject(function (Camera $camera) use (&$seenSources) {
                $key = strtolower($camera->type . '|' . trim($camera->source));
                if (isset($seenSources[$key])) {
                    return true;
                }

                $seenSources[$key] = true;
                return false;
            })
            ->values();

        return response()->json([
            'count' => $cameras->count(),
            'synced_at' => now()->toIso8601String(),
            'cameras' => $cameras->map(fn (Camera $camera) => [
                'id' => $camera->id,
                'name' => $camera->name,
                'type' => $camera->type,
                'vendor' => $camera->vendor ?: 'generic',
                'source' => $camera->source,
                'location' => $camera->location,
                'settings' => $this->normalizeSettingsValue($camera->settings),
            ]),
        ]);
    }

    private function validateCamera(Request $request, ?Camera $camera = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cameras', 'name')->ignore($camera?->id),
            ],
            'location' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['usb', 'rtsp'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'vendor' => ['nullable', 'string', Rule::in(array_keys(self::VENDOR_PROFILES))],
            'config_mode' => ['nullable', Rule::in(['direct', 'preset'])],
            'source' => ['nullable', 'string', 'max:2048'],
            'network_protocol' => ['nullable', Rule::in(['rtsp', 'http', 'https'])],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'path' => ['nullable', 'string', 'max:1024'],
            'channel' => ['nullable', 'integer', 'min:1', 'max:64'],
            'stream_type' => ['nullable', Rule::in(['main', 'sub'])],
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['location'] = trim($validated['location']);
        $validated['vendor'] = $validated['type'] === 'usb'
            ? 'generic'
            : ($validated['vendor'] ?? 'generic');

        if ($validated['type'] === 'usb') {
            $source = trim((string) ($validated['source'] ?? ''));
            if ($source === '' || !preg_match('/^-?\d+$/', $source)) {
                throw ValidationException::withMessages([
                    'source' => 'USB camera source must be a numeric device index like 0 or 1.',
                ]);
            }

            $validated['source'] = $source;
            $validated['settings'] = [
                'config_mode' => 'direct',
            ];
        } else {
            [$source, $settings] = $this->buildNetworkSource($validated);
            $validated['source'] = $source;
            $validated['settings'] = $settings;
        }

        $sourceChanged = !$camera
            || $camera->type !== $validated['type']
            || $camera->source !== $validated['source'];

        $duplicateSource = $sourceChanged && Camera::query()
            ->where('type', $validated['type'])
            ->where('source', $validated['source'])
            ->when($camera, fn ($query) => $query->whereKeyNot($camera->id))
            ->exists();

        if ($duplicateSource) {
            throw ValidationException::withMessages([
                'source' => 'This camera source is already assigned to another camera.',
            ]);
        }

        return Arr::only($validated, [
            'name',
            'location',
            'type',
            'vendor',
            'source',
            'settings',
            'status',
        ]);
    }

    private function buildNetworkSource(array $validated): array
    {
        $mode = $validated['config_mode'] ?? 'direct';
        if ($mode === 'direct') {
            $source = $this->normalizeSource(trim((string) ($validated['source'] ?? '')));
            if (!preg_match('#^(rtsp|http|https)://#i', $source)) {
                throw ValidationException::withMessages([
                    'source' => 'Network camera source must start with rtsp://, http://, or https://',
                ]);
            }

            return [$source, [
                'config_mode' => 'direct',
            ]];
        }

        $vendor = $validated['vendor'] ?? 'generic';
        $profile = self::VENDOR_PROFILES[$vendor] ?? self::VENDOR_PROFILES['generic'];
        $protocol = $validated['network_protocol'] ?? $profile['default_protocol'];

        if (!in_array($protocol, $profile['protocols'], true)) {
            throw ValidationException::withMessages([
                'network_protocol' => 'The selected vendor profile does not support this protocol.',
            ]);
        }

        $host = trim((string) ($validated['host'] ?? ''));
        if ($host === '') {
            throw ValidationException::withMessages([
                'host' => 'Host or IP address is required for preset camera configuration.',
            ]);
        }

        $port = (int) ($validated['port'] ?? $profile['default_port']);
        $channel = (int) ($validated['channel'] ?? 1);
        $streamType = $validated['stream_type'] ?? 'main';
        $username = trim((string) ($validated['username'] ?? ''));
        $password = trim((string) ($validated['password'] ?? ''));
        $customPath = trim((string) ($validated['path'] ?? ''));

        $path = $customPath !== ''
            ? $this->normalizePath($customPath)
            : $this->buildVendorPath($vendor, $channel, $streamType);

        $credentials = '';
        if ($username !== '') {
            $credentials = rawurlencode($username);
            if ($password !== '') {
                $credentials .= ':' . rawurlencode($password);
            }
            $credentials .= '@';
        }

        $source = sprintf(
            '%s://%s%s:%d%s',
            $protocol,
            $credentials,
            $host,
            $port,
            $path,
        );

        return [$this->normalizeSource($source), array_filter([
            'config_mode' => 'preset',
            'network_protocol' => $protocol,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'path' => $path,
            'channel' => $channel,
            'stream_type' => $streamType,
        ], fn ($value) => $value !== null && $value !== '')];
    }

    private function buildVendorPath(string $vendor, int $channel, string $streamType): string
    {
        $streamSuffix = $streamType === 'sub' ? '02' : '01';
        $streamIndex = $streamType === 'sub' ? '1' : '0';
        $streamCode = $streamType === 'sub' ? 'sub' : 'main';
        $channelPadded = str_pad((string) $channel, 2, '0', STR_PAD_LEFT);

        return match ($vendor) {
            'hikvision' => "/Streaming/Channels/{$channel}{$streamSuffix}",
            'dahua' => "/cam/realmonitor?channel={$channel}&subtype={$streamIndex}",
            'uniview' => '/media/video' . ($streamType === 'sub' ? '2' : '1'),
            'axis' => '/axis-media/media.amp',
            'reolink' => "/h264Preview_{$channelPadded}_{$streamCode}",
            'tapo' => $streamType === 'sub' ? '/stream2' : '/stream1',
            'ezviz' => '/h264_stream',
            default => '/stream',
        };
    }

    private function normalizeSource(string $source): string
    {
        if (preg_match('#^https?://[^/]+/?$#i', $source)) {
            return rtrim($source, '/') . '/video';
        }

        return $source;
    }

    private function normalizePath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    private function normalizeSettingsValue(mixed $settings): array
    {
        if (is_string($settings)) {
            $decoded = json_decode($settings, true);
            $settings = is_array($decoded) ? $decoded : [];
        }

        return is_array($settings) ? $settings : [];
    }

    private function buildFormSettings(Camera $camera): array
    {
        $settings = $this->normalizeSettingsValue($camera->settings);

        if ($camera->type === 'usb') {
            return [
                'config_mode' => 'direct',
                'vendor' => 'generic',
                'network_protocol' => 'rtsp',
                'host' => '',
                'port' => 554,
                'username' => '',
                'password' => '',
                'path' => '',
                'channel' => 1,
                'stream_type' => 'main',
            ];
        }

        $mode = $settings['config_mode'] ?? 'direct';
        $vendor = $camera->vendor ?: 'generic';
        $profile = self::VENDOR_PROFILES[$vendor] ?? self::VENDOR_PROFILES['generic'];

        return [
            'config_mode' => $mode,
            'vendor' => $vendor,
            'network_protocol' => $settings['network_protocol'] ?? $profile['default_protocol'],
            'host' => $settings['host'] ?? '',
            'port' => $settings['port'] ?? $profile['default_port'],
            'username' => $settings['username'] ?? '',
            'password' => $settings['password'] ?? '',
            'path' => $settings['path'] ?? '',
            'channel' => $settings['channel'] ?? 1,
            'stream_type' => $settings['stream_type'] ?? 'main',
        ];
    }
}
