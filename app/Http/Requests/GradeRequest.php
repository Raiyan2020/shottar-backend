<?php

namespace App\Http\Requests;

use App\Models\IosBundleProduct;
use App\Rules\IosProductIdRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $grade = $this->route('grade');
        $gradeId = is_object($grade) ? $grade->id : $grade;

        $rules = [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'all_materials_price' => 'nullable|numeric|min:0',
            'icon_number' => 'required|integer|min:0',
            'status' => 'required|boolean',
            'order_by' => 'nullable|integer|min:0',
            'discount_2_materials' => 'required|numeric|min:0|max:100',
            'discount_3_materials' => 'required|numeric|min:0|max:100',
            'discount_4_materials' => 'required|numeric|min:0|max:100',
            'discount_5_materials' => 'required|numeric|min:0|max:100',
            'discount_6_materials' => 'required|numeric|min:0|max:100',
            'discount_7_materials' => 'required|numeric|min:0|max:100',
            'discount_all_materials' => 'required|numeric|min:0|max:100',
            'ios_product_ids' => 'nullable|array',
        ];

        $existing = $gradeId
            ? IosBundleProduct::where('grade_id', $gradeId)->get()->keyBy('semester_id')
            : collect();

        foreach (array_keys($this->input('ios_product_ids', [])) as $semesterId) {
            $current = optional($existing->get($semesterId))->ios_product_id;
            $locked = IosProductIdRule::isLocked($current) ? $current : null;

            $rules["ios_product_ids.{$semesterId}"] = [
                'nullable',
                'string',
                'max:100',
                new IosProductIdRule(
                    ignoreBundleGradeId: $gradeId ? (int) $gradeId : null,
                    ignoreBundleSemesterId: (int) $semesterId,
                    lockedOriginal: $locked,
                ),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ids = collect($this->input('ios_product_ids', []))
                ->filter(fn ($value) => is_string($value) && $value !== '')
                ->map(fn ($value) => trim($value));

            $counts = $ids->countBy();
            foreach ($ids as $semesterId => $value) {
                if (($counts[$value] ?? 0) > 1) {
                    $validator->errors()->add(
                        "ios_product_ids.{$semesterId}",
                        __('general.ios_product_id_duplicate_in_form')
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'ios_product_ids.*.max' => __('general.ios_product_id_max'),
        ];
    }

    public function attributes(): array
    {
        return [
            'ios_product_ids' => __('general.ios_bundle_product_id'),
            'ios_product_ids.*' => __('general.ios_bundle_product_id'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('ios_product_ids');
        if (! is_array($ids)) {
            return;
        }

        $clean = [];
        foreach ($ids as $semesterId => $value) {
            if (! is_string($value)) {
                $clean[$semesterId] = null;
                continue;
            }
            $value = trim($value);
            $clean[$semesterId] = $value === '' ? null : $value;
        }

        $this->merge(['ios_product_ids' => $clean]);
    }
}
