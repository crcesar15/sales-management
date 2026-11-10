<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class IndexDashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'preset' => ['sometimes', Rule::in(['today', 'last_7_days', 'last_30_days', 'month_to_date', 'custom'])],
            'comparison' => ['sometimes', Rule::in(['automatic', 'off'])],
            'from' => ['nullable', 'required_if:preset,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:preset,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
            'store_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->input('preset') !== 'custom') {
                return;
            }
            $timezone = (string) Setting::get('timezone', 'UTC');
            $from = CarbonImmutable::parse($this->string('from')->toString(), $timezone);
            $to = CarbonImmutable::parse($this->string('to')->toString(), $timezone);
            if ($to->greaterThan(CarbonImmutable::now($timezone)->startOfDay())) {
                $validator->errors()->add('to', __('The end date cannot be in the future.'));
            }
            if ($from->diffInDays($to) > 365) {
                $validator->errors()->add('to', __('Choose a reporting range of one year or less.'));
            }
        });
    }
}
