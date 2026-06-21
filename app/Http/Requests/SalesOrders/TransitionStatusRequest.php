<?php

declare(strict_types=1);

namespace App\Http\Requests\SalesOrders;

use App\Enums\PermissionsEnum;
use App\Models\SalesOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class TransitionStatusRequest extends FormRequest
{
    private const TRANSITION_MAP = [
        'draft' => ['sent', 'paid', 'held', 'cancelled'],
        'held' => ['draft', 'cancelled'],
        'sent' => ['paid', 'cancelled'],
        'paid' => ['cancelled'],
        'cancelled' => [],
    ];

    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::SALES_MANAGE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:draft,sent,paid,held,cancelled'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var SalesOrder|null $salesOrder */
            $salesOrder = $this->route('salesOrder');

            if ($salesOrder === null) {
                return;
            }

            $newStatus = (string) $this->input('status');
            $currentStatus = $salesOrder->status->value;
            $allowed = self::TRANSITION_MAP[$currentStatus];

            if (! in_array($newStatus, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "Cannot transition order from {$currentStatus} to {$newStatus}."
                );
            }
        });
    }
}
