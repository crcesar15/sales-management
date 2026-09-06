<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Pos\ListPosRegistersRequest;
use App\Http\Requests\Api\Pos\OpenPosShiftRequest;
use App\Http\Resources\CashRegister\CashRegisterResource;
use App\Http\Resources\CashRegisterShift\CashRegisterShiftResource;
use App\Http\Resources\Store\StoreResource;
use App\Models\CashRegisterShift;
use App\Models\User;
use App\Services\PosSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class PosSessionController extends Controller
{
    public function __construct(private readonly PosSessionService $posSessionService) {}

    public function session(Request $request): JsonResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS->value, $request->user());

        /** @var User $user */
        $user = $request->user();

        return response()->json($this->sessionPayload($this->posSessionService->currentShift($user)));
    }

    public function registers(ListPosRegistersRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => CashRegisterResource::collection($this->posSessionService->availableRegisters($user))->resolve(),
        ]);
    }

    public function openShift(OpenPosShiftRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        try {
            $shift = $this->posSessionService->openShift(
                $user,
                $validated['register_id'],
                (float) $validated['opening_balance'],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->sessionPayload($shift), 201);
    }

    /** @return array<string, mixed> */
    private function sessionPayload(?CashRegisterShift $shift): array
    {
        return [
            'store' => $shift?->register?->store ? (new StoreResource($shift->register->store))->resolve() : null,
            'register' => $shift?->register ? (new CashRegisterResource($shift->register))->resolve() : null,
            'shift' => $shift ? (new CashRegisterShiftResource($shift))->resolve() : null,
        ];
    }
}
