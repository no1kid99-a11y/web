<?php
declare(strict_types=1);

namespace App\Validators;

use Core\Database;

/**
 * Kiểm tra dữ liệu đầu vào.
 * Quy tắc: required, nullable, string, int, numeric, email, min:n, max:n, between:a,b,
 * in:a,b,c, date, date_after_or_today, confirmed, phone, unique:table,column[,ignoreId], time.
 */
class Validator
{
    private array $errors = [];
    private array $validated = [];

    private const LABELS = [
        'name' => 'Họ tên', 'email' => 'Email', 'password' => 'Mật khẩu', 'phone' => 'Số điện thoại',
        'address' => 'Địa chỉ', 'species' => 'Loài', 'breed' => 'Giống', 'age' => 'Tuổi',
        'weight' => 'Cân nặng', 'price' => 'Giá', 'duration_minutes' => 'Thời lượng',
        'description' => 'Mô tả', 'start_time' => 'Giờ bắt đầu', 'end_time' => 'Giờ kết thúc',
        'vaccine_name' => 'Tên vắc-xin', 'vaccinated_at' => 'Ngày tiêm', 'next_due_at' => 'Ngày tiêm nhắc lại',
        'rating' => 'Số sao', 'comment' => 'Bình luận', 'reason' => 'Lý do', 'note' => 'Ghi chú',
        'role' => 'Vai trò', 'current_password' => 'Mật khẩu hiện tại',
    ];

    public function __construct(private array $data, private array $rules)
    {
        $this->run();
    }

    private function label(string $field): string
    {
        return self::LABELS[$field] ?? $field;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleStr) {
            $rules = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $empty = $value === null || $value === '' || $value === [];
            if ($empty) {
                if (in_array('required', $rules, true)) {
                    $this->errors[] = $this->label($field) . ' không được để trống.';
                } else {
                    $this->validated[$field] = null;
                }
                continue;
            }
            $ok = true;
            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $err = $this->check($name, $arg, $field, $value);
                if ($err) {
                    $this->errors[] = $err;
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                if (in_array('int', $rules, true)) {
                    $value = (int)$value;
                } elseif (in_array('numeric', $rules, true)) {
                    $value = (float)$value;
                }
                $this->validated[$field] = $value;
            }
        }
    }

    private function check(string $name, ?string $arg, string $field, mixed $v): ?string
    {
        $L = $this->label($field);
        $len = is_string($v) ? mb_strlen($v) : 0;
        switch ($name) {
            case 'required':
            case 'nullable':
            case 'string':
                return null;
            case 'int':
                return filter_var($v, FILTER_VALIDATE_INT) === false ? "$L phải là số nguyên." : null;
            case 'numeric':
                return !is_numeric($v) ? "$L phải là số." : null;
            case 'email':
                return filter_var($v, FILTER_VALIDATE_EMAIL) === false ? "$L không hợp lệ." : null;
            case 'phone':
                return !preg_match('/^(0|\+84)[0-9]{9,10}$/', preg_replace('/[\s.]/', '', (string)$v)) ? "$L không hợp lệ (10–11 chữ số)." : null;
            case 'min':
                if (is_numeric($v) && !is_string($v)) {
                    return $v < (float)$arg ? "$L phải từ $arg trở lên." : null;
                }
                return $len < (int)$arg ? "$L phải có ít nhất $arg ký tự." : null;
            case 'max':
                return $len > (int)$arg ? "$L tối đa $arg ký tự." : null;
            case 'between':
                [$a, $b] = explode(',', (string)$arg);
                return (!is_numeric($v) || $v < $a || $v > $b) ? "$L phải trong khoảng $a–$b." : null;
            case 'in':
                return !in_array((string)$v, explode(',', (string)$arg), true) ? "$L không hợp lệ." : null;
            case 'date':
                $d = \DateTime::createFromFormat('Y-m-d', (string)$v);
                return (!$d || $d->format('Y-m-d') !== $v) ? "$L không đúng định dạng ngày." : null;
            case 'date_not_future':
                return $v > date('Y-m-d') ? "$L không được ở tương lai." : null;
            case 'date_after_or_today':
                return $v < date('Y-m-d') ? "$L không được ở quá khứ." : null;
            case 'time':
                return !preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string)$v) ? "$L không đúng định dạng giờ." : null;
            case 'confirmed':
                return ($this->data[$field . '_confirmation'] ?? null) !== $v ? "$L nhập lại không khớp." : null;
            case 'unique':
                [$table, $col, $ignore] = array_pad(explode(',', (string)$arg), 3, null);
                $sql = "SELECT COUNT(*) FROM $table WHERE $col = ?" . ($ignore ? ' AND id <> ?' : '');
                $params = $ignore ? [$v, (int)$ignore] : [$v];
                return (int)Database::value($sql, $params) > 0 ? "$L đã được sử dụng." : null;
        }
        throw new \InvalidArgumentException("Quy tắc không hỗ trợ: $name");
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }
}
