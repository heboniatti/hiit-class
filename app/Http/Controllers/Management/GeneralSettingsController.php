<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Support\HiitSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    public function __construct(private HiitSettings $settings) {}

    /**
     * Show the gym's rules.
     */
    public function edit(): Response
    {
        return Inertia::render('management/settings', [
            'settings' => $this->settings->all(),
        ]);
    }

    /**
     * Change the rules that govern check-in.
     *
     * These are the numbers PerformCheckIn and CancelCheckIn read, so a change
     * here takes effect on the very next check-in.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            HiitSettings::CHECKIN_OPENS_HOURS_BEFORE => ['required', 'integer', 'min:1', 'max:720'],
            HiitSettings::CHECKIN_CLOSES_MINUTES_BEFORE => ['required', 'integer', 'min:0', 'max:1440'],
            HiitSettings::CANCELLATION_DEADLINE_MINUTES => ['required', 'integer', 'min:0', 'max:10080'],
            HiitSettings::SESSION_HORIZON_WEEKS => ['required', 'integer', 'min:1', 'max:52'],
            HiitSettings::LOW_FREQUENCY_THRESHOLD => ['required', 'integer', 'min:0', 'max:50'],
        ], [
            'required' => 'Informe um valor.',
            'integer' => 'Informe um número inteiro.',
        ]);

        foreach ($validated as $key => $value) {
            $this->settings->set($key, (int) $value);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Configurações salvas.')]);

        return back();
    }
}
