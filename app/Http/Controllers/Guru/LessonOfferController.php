<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\LessonOffer;
use App\Models\Teacher;
use Illuminate\View\View;

class LessonOfferController extends Controller
{
    public function index(): View
    {
        $offers = LessonOffer::query()
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->with('student')
            ->get();

        $teacher = Teacher::query()
            ->where('user_id', auth()->id())
            ->first();

        return view('guru.lesson-offers.index', [
            'offers' => $offers,
            'teacher' => $teacher,
            'defaultContact' => config('bimbel.admin_whatsapp'),
        ]);
    }
}
