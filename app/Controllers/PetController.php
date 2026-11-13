<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Models\Vaccination;
use App\Services\PhotoUploader;
use Core\AppException;
use Core\Controller;
use Core\Database;

/**
 * Hồ sơ thú cưng của khách hàng. Mọi truy vấn đều lọc theo user_id của người đăng nhập (chống IDOR).
 */
class PetController extends Controller
{
    private const RULES = [
        'name' => 'required|max:100',
        'species' => 'required|in:dog,cat,other',
        'breed' => 'nullable|max:100',
        'age' => 'nullable|int|between:0,40',
        'weight' => 'nullable|numeric|between:0.1,150',
    ];

    private function owned(int $id): array
    {
        $pet = Pet::findOwned($id, (int)$this->user()['id']);
        $this->notFoundUnless($pet, 'Không tìm thấy thú cưng.');
        return $pet;
    }

    public function index(): void
    {
        $this->render('client/pets/index', ['title' => 'Thú cưng của tôi', 'pets' => Pet::forUser((int)$this->user()['id'])]);
    }

    public function create(): void
    {
        $this->render('client/pets/form', ['title' => 'Thêm thú cưng', 'pet' => null]);
    }

    public function store(): void
    {
        $data = $this->formData();
        $v = $this->validate($data, self::RULES, '/pets/create');
        $photo = $this->attempt(fn() => PhotoUploader::store($_FILES['photo'] ?? null), '/pets/create', null, $data);
        $id = Pet::create($v + ['user_id' => (int)$this->user()['id'], 'photo' => $photo, 'is_active' => 1, 'updated_at' => Database::now()]);
        flash('success', 'Đã thêm hồ sơ ' . e($v['name']) . '.');
        redirect('/pets/' . $id);
    }

    public function show(int $id): void
    {
        $pet = $this->owned($id);
        $this->render('client/pets/show', [
            'title' => $pet['name'],
            'pet' => $pet,
            'vaccinations' => Vaccination::forPet($id),
            'records' => HealthRecord::forPet($id),
            'album' => array_map([BookingPhoto::class, 'present'], BookingPhoto::forPet($id)),
            'bookings' => array_values(array_filter(
                Booking::list(['user_id' => (int)$this->user()['id']], [], 200),
                fn($b) => (int)$b['pet_id'] === $id
            )),
        ]);
    }

    public function edit(int $id): void
    {
        $this->render('client/pets/form', ['title' => 'Sửa hồ sơ', 'pet' => $this->owned($id)]);
    }

    public function update(int $id): void
    {
        $pet = $this->owned($id);
        $data = $this->formData();
        $v = $this->validate($data, self::RULES, "/pets/$id/edit");
        $photo = $this->attempt(fn() => PhotoUploader::store($_FILES['photo'] ?? null), "/pets/$id/edit", null, $data);
        if ($photo) {
            PhotoUploader::delete($pet['photo']);
            $v['photo'] = $photo;
        } elseif (!empty($_POST['remove_photo'])) {
            PhotoUploader::delete($pet['photo']);
            $v['photo'] = null;
        }
        Pet::updateById($id, $v);
        flash('success', 'Đã lưu hồ sơ ' . e($v['name']) . '.');
        redirect('/pets/' . $id);
    }

    /** Xóa mềm; bị chặn nếu thú cưng còn đơn đang giữ chỗ. */
    public function destroy(int $id): void
    {
        $pet = $this->owned($id);
        if (Pet::hasHoldingBookings($id)) {
            flash('error', e($pet['name']) . ' còn lịch hẹn chưa hoàn thành. Hãy hủy hoặc chờ hoàn thành các lịch này trước khi xóa hồ sơ.');
            redirect('/pets/' . $id);
        }
        Pet::updateById($id, ['is_active' => 0]);
        flash('success', 'Đã xóa hồ sơ ' . e($pet['name']) . '. Lịch sử đơn cũ vẫn được giữ lại.');
        redirect('/pets');
    }

    public function addVaccination(int $id): void
    {
        $this->owned($id);
        $data = ['vaccine_name' => $this->input('vaccine_name'), 'vaccinated_at' => $this->input('vaccinated_at'), 'next_due_at' => $this->input('next_due_at')];
        $v = $this->validate($data, [
            'vaccine_name' => 'required|max:150',
            'vaccinated_at' => 'required|date|date_not_future',
            'next_due_at' => 'nullable|date',
        ], "/pets/$id#vaccinations");
        if ($v['next_due_at'] && $v['next_due_at'] <= $v['vaccinated_at']) {
            flash('error', 'Ngày tiêm nhắc lại phải sau ngày tiêm.');
            redirect("/pets/$id#vaccinations");
        }
        Vaccination::create($v + ['pet_id' => $id]);
        flash('success', 'Đã thêm mũi tiêm.');
        redirect("/pets/$id#vaccinations");
    }

    public function deleteVaccination(int $id, int $vid): void
    {
        $this->owned($id);
        Database::query('DELETE FROM vaccinations WHERE id = ? AND pet_id = ?', [$vid, $id]);
        flash('success', 'Đã xóa mũi tiêm.');
        redirect("/pets/$id#vaccinations");
    }

    private function formData(): array
    {
        return [
            'name' => $this->input('name'),
            'species' => $this->input('species'),
            'breed' => $this->input('breed'),
            'age' => $this->input('age'),
            'weight' => $this->input('weight') !== null ? str_replace(',', '.', (string)$this->input('weight')) : null,
        ];
    }
}
