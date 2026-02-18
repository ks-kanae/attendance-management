<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'corrected_start_time' => ['required'],
            'corrected_end_time'   => ['required'],
            'reason'               => ['required', 'string', 'max:500'],
            'break_corrections'    => ['array'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $start = $this->input('corrected_start_time');
            $end   = $this->input('corrected_end_time');

            // ① 出勤 > 退勤
            if ($start && $end && $start >= $end) {
                $validator->errors()->add(
                    'corrected_start_time',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            $breaks = $this->input('break_corrections', []);

            foreach ($breaks as $index => $break) {

                $bStart = $break['start_time'] ?? null;
                $bEnd   = $break['end_time'] ?? null;

                // ② 休憩開始が出勤前 or 退勤後
                if ($bStart && $start && ($bStart < $start || $bStart > $end)) {
                    $validator->errors()->add(
                        "break_corrections.$index.start_time",
                        '休憩時間が不適切な値です'
                    );
                }

                // ③ 休憩終了が退勤後
                if ($bEnd && $end && $bEnd > $end) {
                    $validator->errors()->add(
                        "break_corrections.$index.end_time",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }

                // 休憩内で start > end
                if ($bStart && $bEnd && $bStart >= $bEnd) {
                    $validator->errors()->add(
                        "break_corrections.$index.start_time",
                        '休憩時間が不適切な値です'
                    );
                }
            }
        });
    }

    public function messages()
    {
        return [
            'reason.required' => '備考を記入してください',
        ];
    }
}
