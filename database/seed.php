<?php
/**
 * Dữ liệu mẫu: 2 Admin, 3 Nhân viên, 3 Khách hàng, 5 thú cưng, dịch vụ, combo, khung giờ,
 * các đơn ở đủ trạng thái (ngày tính tương đối theo hôm nay), sổ sức khỏe, đánh giá.
 */
declare(strict_types=1);

use Core\Database;

function seed_database(): void
{
    $now = Database::now();
    $d = fn(int $offset) => date('Y-m-d', strtotime("$offset days"));
    $dt = fn(int $offset, string $time) => date('Y-m-d', strtotime("$offset days")) . ' ' . $time;

    $user = function (string $name, string $email, string $pass, string $phone, string $role, ?string $address = null, ?int $createdBy = null) use ($now): int {
        return Database::insert('users', [
            'name' => $name, 'email' => $email, 'password' => password_hash($pass, PASSWORD_DEFAULT),
            'phone' => $phone, 'address' => $address, 'role' => $role, 'status' => 'active',
            'must_change_password' => 0, 'created_by' => $createdBy, 'created_at' => $now, 'updated_at' => $now,
        ]);
    };

    // --- Tài khoản ---
    $admin  = $user('Nguyễn Minh Quân', 'admin@petcare.vn', 'Admin@123', '0901000001', 'admin', '12 Láng Hạ, Đống Đa, Hà Nội');
    $user('Trần Thu Trang', 'trang.tran@petcare.vn', 'Admin@123', '0901000002', 'admin', null, $admin);
    $an    = $user('Lê Văn An', 'an.le@petcare.vn', 'Staff@123', '0912000001', 'staff', null, $admin);
    $binh  = $user('Phạm Thị Bình', 'binh.pham@petcare.vn', 'Staff@123', '0912000002', 'staff', null, $admin);
    $chau  = $user('Hoàng Minh Châu', 'chau.hoang@petcare.vn', 'Staff@123', '0912000003', 'staff', null, $admin);
    $ha    = $user('Đỗ Thu Hà', 'ha.do@gmail.com', 'Khach@123', '0987000001', 'client', '25 Nguyễn Trãi, Thanh Xuân, Hà Nội');
    $huy   = $user('Vũ Quang Huy', 'huy.vu@gmail.com', 'Khach@123', '0987000002', 'client', '88 Cầu Giấy, Cầu Giấy, Hà Nội');
    $lan   = $user('Bùi Lan Anh', 'lananh.bui@gmail.com', 'Khach@123', '0987000003', 'client', '5 Kim Mã, Ba Đình, Hà Nội');

    // --- Thú cưng ---
    $pet = fn(int $uid, string $name, string $species, string $breed, int $age, float $w) => Database::insert('pets', [
        'user_id' => $uid, 'name' => $name, 'species' => $species, 'breed' => $breed, 'age' => $age,
        'weight' => $w, 'photo' => null, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $lu   = $pet($ha, 'Lu', 'dog', 'Poodle', 3, 5.2);
    $mun  = $pet($ha, 'Mun', 'cat', 'Anh lông ngắn', 2, 4.1);
    $bo   = $pet($huy, 'Bơ', 'dog', 'Corgi', 4, 11.5);
    $miu  = $pet($lan, 'Miu', 'cat', 'Ba Tư', 5, 3.8);
    $kem  = $pet($lan, 'Kem', 'dog', 'Shiba Inu', 1, 8.0);

    $vacc = fn(int $pid, string $name, int $off, ?int $nextOff) => Database::insert('vaccinations', [
        'pet_id' => $pid, 'vaccine_name' => $name, 'vaccinated_at' => $d($off),
        'next_due_at' => $nextOff === null ? null : $d($nextOff), 'created_at' => $now,
    ]);
    $vacc($lu, 'Vắc-xin 7 bệnh (DHPPi+L)', -200, 165);
    $vacc($lu, 'Dại (Rabies)', -150, 215);
    $vacc($mun, 'Vắc-xin 4 bệnh mèo (FVRCP)', -90, 275);
    $vacc($bo, 'Vắc-xin 7 bệnh (DHPPi+L)', -320, 20);
    $vacc($kem, 'Vắc-xin 5 bệnh', -40, 325);

    // --- Dịch vụ ---
    $svc = fn(string $name, string $desc, int $price, int $min) => Database::insert('services', [
        'name' => $name, 'description' => $desc, 'price' => $price, 'duration_minutes' => $min,
        'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $tam    = $svc('Tắm và sấy khô', 'Tắm bằng sữa tắm chuyên dụng theo loại lông, sấy và chải khô.', 150000, 45);
    $tia    = $svc('Cắt tỉa lông tạo kiểu', 'Tỉa gọn hoặc tạo kiểu theo giống, vệ sinh vùng nhạy cảm.', 250000, 60);
    $mong   = $svc('Cắt móng và vệ sinh tai', 'Cắt, mài móng; lau sạch tai bằng dung dịch chuyên dụng.', 80000, 20);
    $dao    = $svc('Dắt đi dạo', 'Dắt đi dạo quanh khu vực nhà, vận động và đi vệ sinh.', 100000, 45);
    $mas    = $svc('Massage thư giãn', 'Massage nhẹ nhàng giúp thú cưng thư giãn, lưu thông máu.', 120000, 30);
    $rang   = $svc('Vệ sinh răng miệng', 'Chải răng, xịt thơm miệng, kiểm tra cao răng.', 90000, 20);

    // --- Combo ---
    $combo = function (string $name, string $desc, int $price, array $services) use ($now): int {
        $id = Database::insert('combos', ['name' => $name, 'description' => $desc, 'price' => $price, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        foreach ($services as $s) {
            Database::insert('combo_service', ['combo_id' => $id, 'service_id' => $s]);
        }
        return $id;
    };
    $tietKiem = $combo('Combo Tiết kiệm', 'Tắm sấy kèm cắt móng, vệ sinh tai. Gói chăm sóc cơ bản hằng tuần.', 200000, [$tam, $mong]);
    $toanDien = $combo('Combo Toàn diện', 'Tắm sấy, cắt móng, vệ sinh tai và răng miệng trong một lần.', 280000, [$tam, $mong, $rang]);
    $spa      = $combo('Combo Spa thư giãn', 'Tắm sấy và massage thư giãn, phù hợp thú cưng hay căng thẳng.', 240000, [$tam, $mas]);

    // --- Khung giờ ---
    $slots = [];
    foreach ([['08:00:00', '10:00:00'], ['10:00:00', '12:00:00'], ['13:00:00', '15:00:00'], ['15:00:00', '17:00:00']] as [$s, $e]) {
        $slots[] = Database::insert('time_slots', ['start_time' => $s, 'end_time' => $e, 'is_active' => 1]);
    }
    [$s8, $s10, $s13, $s15] = $slots;

    // --- Đơn hẹn ---
    $prices = Database::fetchAll('SELECT id, price FROM services');
    $prices = array_column($prices, 'price', 'id');
    $booking = function (int $uid, int $pid, int $off, int $slot, int $staff, string $status, ?int $comboId, array $serviceIds, string $address, ?string $reason = null, ?int $cancelledBy = null) use ($prices, $now, $d): int {
        if ($comboId) {
            $serviceIds = array_column(Database::fetchAll('SELECT service_id FROM combo_service WHERE combo_id = ?', [$comboId]), 'service_id');
            $total = (int)Database::value('SELECT price FROM combos WHERE id = ?', [$comboId]);
        } else {
            $total = array_sum(array_map(fn($s) => $prices[$s], $serviceIds));
        }
        $retail = array_sum(array_map(fn($s) => $prices[$s], $serviceIds));
        $id = Database::insert('bookings', [
            'user_id' => $uid, 'pet_id' => $pid, 'combo_id' => $comboId, 'time_slot_id' => $slot, 'staff_id' => $staff,
            'booking_date' => $d($off), 'address' => $address, 'total_price' => $total,
            'discount_amount' => max(0, $retail - $total), 'status' => $status, 'note' => null,
            'cancel_reason' => $reason, 'cancelled_by' => $cancelledBy,
            'created_at' => date('Y-m-d H:i:s', strtotime(($off - 2) . ' days')), 'updated_at' => $now,
        ]);
        foreach ($serviceIds as $s) {
            Database::insert('booking_services', ['booking_id' => $id, 'service_id' => $s, 'price' => $prices[$s]]);
        }
        return $id;
    };
    $addrHa = '25 Nguyễn Trãi, Thanh Xuân, Hà Nội';
    $addrHuy = '88 Cầu Giấy, Cầu Giấy, Hà Nội';
    $addrLan = '5 Kim Mã, Ba Đình, Hà Nội';

    $health = fn(int $pid, int $bid, int $by, ?float $w, string $note, string $at) => Database::insert('health_records', [
        'pet_id' => $pid, 'booking_id' => $bid, 'recorded_by' => $by, 'weight' => $w, 'note' => $note, 'recorded_at' => $at,
    ]);
    $review = fn(int $bid, int $uid, int $rating, string $comment, string $at) => Database::insert('reviews', [
        'booking_id' => $bid, 'user_id' => $uid, 'rating' => $rating, 'comment' => $comment, 'is_hidden' => 0, 'created_at' => $at,
    ]);

    // Đã hoàn thành (doanh thu các tháng trước)
    $b = $booking($ha, $lu, -150, $s8, $an, 'completed', $toanDien, [], $addrHa);
    $health($lu, $b, $an, 4.8, 'Da lông khỏe, tai sạch. Nên chải lông 2 lần/tuần.', $dt(-150, '09:40:00'));
    $b = $booking($huy, $bo, -120, $s10, $binh, 'completed', null, [$tam, $tia], $addrHuy);
    $health($bo, $b, $binh, 11.0, 'Lông rụng nhiều theo mùa, đã tỉa gọn. Cân nặng ổn định.', $dt(-120, '11:45:00'));
    $b = $booking($lan, $miu, -95, $s13, $chau, 'completed', $spa, [], $addrLan);
    $health($miu, $b, $chau, 3.7, 'Mèo hơi căng thẳng lúc đầu, sau massage đã hợp tác tốt.', $dt(-95, '14:30:00'));
    $review($b, $lan, 5, 'Bạn Châu rất kiên nhẫn với Miu, bé về thơm và mượt lắm!', $dt(-95, '18:00:00'));
    $b = $booking($ha, $mun, -64, $s15, $binh, 'completed', $tietKiem, [], $addrHa);
    $health($mun, $b, $binh, 4.0, 'Móng dài, đã cắt gọn. Tai hơi bẩn, đã vệ sinh.', $dt(-64, '16:20:00'));
    $review($b, $ha, 4, 'Dịch vụ tốt, đúng giờ. Mong có thêm lựa chọn sữa tắm.', $dt(-64, '20:00:00'));
    $b = $booking($huy, $bo, -33, $s8, $an, 'completed', $toanDien, [], $addrHuy);
    $health($bo, $b, $an, 11.3, 'Cao răng nhẹ, khuyên vệ sinh răng định kỳ mỗi tháng.', $dt(-33, '09:30:00'));
    $review($b, $huy, 5, 'Anh An làm rất cẩn thận, giải thích kỹ tình trạng răng của Bơ.', $dt(-33, '12:00:00'));
    $b = $booking($lan, $kem, -12, $s10, $chau, 'completed', null, [$tam, $dao], $addrLan);
    $health($kem, $b, $chau, 8.0, 'Năng động, đi dạo 30 phút. Da không có dấu hiệu bất thường.', $dt(-12, '11:50:00'));
    $review($b, $lan, 5, 'Kem rất thích chị Châu. Sẽ đặt tiếp!', $dt(-12, '19:00:00'));
    $b = $booking($ha, $lu, -5, $s13, $an, 'completed', $spa, [], $addrHa);
    $health($lu, $b, $an, 5.2, 'Tăng 0,4 kg so với lần trước, ăn uống tốt.', $dt(-5, '14:40:00'));
    $b = $booking($huy, $bo, -3, $s15, $binh, 'cancelled', null, [$dao], $addrHuy, 'Khách hàng hủy', $huy);

    // Hôm nay
    $booking($ha, $lu, 0, $s8, $an, 'in_progress', $tietKiem, [], $addrHa);
    $booking($huy, $bo, 0, $s15, $chau, 'confirmed', null, [$tam, $mong], $addrHuy);

    // Sắp tới
    $booking($huy, $bo, 1, $s8, $an, 'confirmed', $tietKiem, [], $addrHuy);
    $booking($ha, $mun, 1, $s8, $binh, 'pending', null, [$mong, $rang], $addrHa);
    $booking($lan, $miu, 1, $s10, $binh, 'pending', $spa, [], $addrLan);
    $booking($ha, $lu, 2, $s13, $an, 'confirmed', $toanDien, [], $addrHa);
    $booking($lan, $kem, 3, $s15, $binh, 'pending', null, [$tam, $dao], $addrLan);
}
