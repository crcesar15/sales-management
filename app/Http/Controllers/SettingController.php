<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Settings\UpdateFinanceSettingsRequest;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Settings\UpdateTaxSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class SettingController extends Controller
{
    private readonly SettingsService $service;

    public function __construct(SettingsService $service)
    {
        $this->service = $service;
    }

    public function index(): Response
    {
        $this->authorize(PermissionsEnum::SETTINGS_MANAGE);

        return Inertia::render('Settings/Index', [
            'settings' => $this->service->all(),
            'groups' => ['general', 'tax', 'finance'],
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $this->service->updateGroup('general', $request->validated());

        cache()->forget('settings');

        return redirect()->route('settings');
    }

    public function updateTax(UpdateTaxSettingsRequest $request): RedirectResponse
    {
        $this->service->updateGroup('tax', $request->validated());

        cache()->forget('settings');

        return redirect()->route('settings');
    }

    public function updateFinance(UpdateFinanceSettingsRequest $request): RedirectResponse
    {
        $this->service->updateGroup('finance', $request->validated());

        cache()->forget('settings');

        return redirect()->route('settings');
    }
}
