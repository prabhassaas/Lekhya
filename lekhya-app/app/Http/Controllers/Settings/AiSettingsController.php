<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Services\AI\AiService;
use Illuminate\Http\Request;

class AiSettingsController extends Controller
{
    private const PROVIDERS = [
        'groq'      => 'Groq (fast, recommended)',
        'anthropic' => 'Anthropic Claude',
        'ollama'    => 'Ollama (self-hosted)',
        'mock'      => 'Mock (offline demo)',
    ];

    public function edit()
    {
        $setting = $this->setting();

        return view('settings.ai', [
            'setting'   => $setting,
            'providers' => self::PROVIDERS,
            'defaults'  => [
                'text'   => config('services.ai.groq_text_model'),
                'vision' => config('services.ai.groq_vision_model'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'provider'     => 'required|in:' . implode(',', array_keys(self::PROVIDERS)),
            'api_key'      => 'nullable|string|max:255',
            'text_model'   => 'nullable|string|max:120',
            'vision_model' => 'nullable|string|max:120',
            'is_active'    => 'boolean',
        ]);

        $setting = $this->setting();
        $setting->provider     = $data['provider'];
        $setting->text_model   = ($data['text_model'] ?? null) ?: null;
        $setting->vision_model = ($data['vision_model'] ?? null) ?: null;
        $setting->is_active    = $request->boolean('is_active');

        // Only overwrite the stored key when a new one is actually typed —
        // the form never renders the existing key back, so a blank field means "keep".
        if (filled($data['api_key'] ?? null)) {
            $setting->api_key = trim($data['api_key']);
        }
        $setting->save();

        return redirect()->route('settings.ai')->with('success', 'AI settings saved.');
    }

    public function test(AiService $ai)
    {
        $setting = $this->setting();
        // A cheap round-trip that exercises the configured driver.
        $result = $ai->suggestAccount('Connection test — office stationery', 100.0, 'Test Vendor');
        $ok = ! isset($result['error']);

        $setting->update([
            'last_tested_at'   => now(),
            'last_test_status' => $ok ? 'ok' : 'failed',
        ]);

        $driver = $ai->getDriverName();
        return redirect()->route('settings.ai')->with(
            $ok ? 'success' : 'error',
            $ok
                ? "Connection OK — responding via the \"{$driver}\" driver."
                : ('Test failed: ' . ($result['error'] ?? 'unknown error') . " (driver: {$driver})")
        );
    }

    private function setting(): AiSetting
    {
        return AiSetting::firstOrNew(['tenant_id' => auth()->user()->tenant_id]);
    }
}
