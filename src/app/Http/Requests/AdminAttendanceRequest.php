<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AdminAttendanceRequest extends FormRequest
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
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
            'breaks'     => 'array',
            'breaks.*.start_time' => 'nullable|date_format:H:i',
            'breaks.*.end_time'   => 'nullable|date_format:H:i',
            'reason'     => 'required|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $start = $this->input('start_time');
            $end   = $this->input('end_time');

            if ($start && $end) {
                $startTime = Carbon::parse($start);
                $endTime   = Carbon::parse($end);

                // ① 出勤 > 退勤
                if ($startTime >= $endTime) {
                    $validator->errors()->add(
                        'start_time',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }
            }

            $breaks = $this->input('breaks', []);
            foreach ($breaks as $index => $break) {
                $bStart = $break['start_time'] ?? null;
                $bEnd   = $break['end_time'] ?? null;

                // **既存データがある場合 or どちらか入力されている場合のみチェック**
                $hasExisting = isset($this->attendance) && $this->attendance->breaks->get($index);
                if (!$hasExisting && !$bStart && !$bEnd) {
                continue; // 空白で新規ならスキップ
                }

                if ($bStart) $bStartTime = Carbon::parse($bStart);
                if ($bEnd)   $bEndTime   = Carbon::parse($bEnd);

                // ② 休憩開始が出勤前 or 退勤後
                if (isset($bStartTime, $startTime, $endTime) &&
                    ($bStartTime < $startTime || $bStartTime > $endTime)
                ) {
                    $validator->errors()->add(
                        "breaks.$index.start_time",
                        '休憩時間が不適切な値です'
                    );
                }

                // ③ 休憩終了が退勤後
                if (isset($bEndTime, $endTime) && $bEndTime > $endTime) {
                    $validator->errors()->add(
                        "breaks.$index.end_time",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }

                // 休憩 start > end
                if (isset($bStartTime, $bEndTime) && $bStartTime >= $bEndTime) {
                    $validator->errors()->add(
                        "breaks.$index.start_time",
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
