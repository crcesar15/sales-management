<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Pos\ClosePosShiftRequest;
use App\Http\Requests\Api\Pos\ListPosRegistersRequest;
use App\Http\Requests\Api\Pos\OpenPosShiftRequest;
use App\Http\Requests\Api\Pos\StorePosMovementRequest;
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
                $validated['opening_notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->sessionPayload($shift), 201);
    }

    public function closingSummary(Request $request): JsonResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS->value, $request->user());

        /** @var User $user */
        $user = $request->user();

        try {
            return response()->json(['data' => $this->posSessionService->closingSummary($user)]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function closeShift(ClosePosShiftRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        try {
            $shift = $this->posSessionService->closeShift(
                $user,
                (float) $validated['closing_balance'],
                $validated['closing_notes'] ?? null,
                $validated['discrepancy_reason'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $summary = $this->posSessionService->reconciliation($shift);

        return response()->json([
            'shift' => (new CashRegisterShiftResource($shift))->resolve(),
            'summary' => [
                ...$summary,
                'counted_cash' => (float) $shift->closing_balance,
                'difference' => (float) $shift->difference,
            ],
        ]);
    }

    public function addMovement(StorePosMovementRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        try {
            $shift = $this->posSessionService->addMovement(
                $user,
                $validated['type'],
                (float) $validated['amount'],
                $validated['reason'],
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
