<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Preferences\UpdateUserPreferencesRequest;
use App\Services\UserPreferenceService;
use Illuminate\Http\RedirectResponse;

final class UserPreferenceController extends Controller
{
    public function __construct(private readonly UserPreferenceService $userPreferenceService) {}

    public function update(UpdateUserPreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->userPreferenceService->update($user, $request->validated());

        return redirect()->back();
    }
}
