<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Review;
use App\Services\ActivityLogger;
use Core\Database;

class ReviewController extends AdminController
{
    public function index(): void
    {
        $this->render('admin/reviews/index', ['title' => 'Đánh giá', 'reviews' => Review::list(false, 300), 'stats' => Review::stats()]);
    }

    public function toggle(int $id): void
    {
        $r = Review::find($id);
        $this->notFoundUnless($r);
        $hidden = (int)$r['is_hidden'] ? 0 : 1;
        Database::begin();
        Database::update('reviews', ['is_hidden' => $hidden], 'id = :id', ['id' => $id]);
        ActivityLogger::log($this->user(), 'review.toggle', 'review', $id, ['to' => $hidden ? 'Ẩn' : 'Hiện']);
        Database::commit();
        flash('success', $hidden ? 'Đã ẩn đánh giá khỏi trang chủ.' : 'Đã hiện lại đánh giá.');
        redirect('/admin/reviews');
    }
}
