<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        DB::transaction(function () {
            foreach (Grade::orderBy('level')->get() as $grade) {
                $subjects = Subject::whereIn('name_en', $grade->level >= 10
                    ? ['Myanmar', 'English', 'Mathematics', 'Physics', 'Chemistry', 'Biology']
                    : ['Myanmar', 'English', 'Mathematics', 'Science', 'Social Studies'])->get();
                foreach ($grade->classes as $class) {
                    $teacher = Teacher::firstOrCreate(['name_en' => 'Sample Teacher '.$grade->level.$class->section],
                        ['name_my' => 'နမူနာ ဆရာ '.$grade->level.$class->section, 'subject' => $class->section === 'A' ? 'Mathematics' : 'English',
                            'qualification' => 'B.Ed. (Sample)', 'experience_years' => 5 + $grade->level, 'main_grade_id' => $grade->id, 'phone' => '09900000000']);
                    $class->update(['head_teacher_id' => $teacher->id]);
                    for ($day = 1; $day <= 5; $day++) {
                        foreach ($subjects as $period => $subject) {
                            Timetable::updateOrCreate(['school_class_id' => $class->id, 'weekday' => $day, 'period_no' => $period + 1], ['subject_id' => $subject->id, 'teacher_id' => $teacher->id]);
                        }
                    }
                    for ($number = 1; $number <= 2; $number++) {
                        $student = Student::firstOrCreate(['name_en' => 'Sample '.($number === 1 ? 'Thiri' : 'Min').' '.$grade->level.$class->section],
                            ['name_my' => 'နမူနာ '.($number === 1 ? 'သီရိ' : 'မင်း').' '.$grade->level.$class->section,
                                'school_class_id' => $class->id, 'gender' => $number === 1 ? 'female' : 'male',
                                'date_of_birth' => now()->subYears(5 + $grade->level)->toDateString(), 'parent_name' => 'Fictional Guardian '.$class->id.$number,
                                'parent_phone' => '09912345678', 'address' => 'Fictional sample address', 'enrolled_on' => now()->month(6)->startOfMonth()->toDateString(), 'is_active' => true]);
                        foreach (Exam::all() as $exam) {
                            foreach ($subjects as $subject) {
                                Mark::updateOrCreate(['student_id' => $student->id, 'exam_id' => $exam->id, 'subject_id' => $subject->id],
                                    ['score' => 55 + (($student->id * (7 + $exam->sort_order * 6) + $subject->id * 3 + $exam->sort_order * 11) % 46)]);
                            }
                        }
                        for ($day = 1; $day <= 20; $day++) {
                            $date = now()->subDays($day);
                            if ($date->isWeekend()) {
                                continue;
                            }
                            Attendance::where('student_id', $student->id)->whereDate('date', $date)->firstOrNew()
                                ->fill(['student_id' => $student->id, 'date' => $date->toDateString(),
                                    'status' => ($student->id + $day) % 11 === 0 ? 'absent' : (($student->id + $day) % 7 === 0 ? 'late' : 'present')])->save();
                        }
                        $paid = [0, 75000, 150000][$student->id % 3];
                        Fee::firstOrCreate(['student_id' => $student->id], ['amount_due' => 150000, 'amount_paid' => $paid, 'method' => $paid ? 'cash' : null,
                            'paid_on' => $paid ? today() : null, 'note' => 'Fictional sample fee']);
                    }
                }
            }
            $titles = ['December examination schedule', 'A restful holiday ahead', 'School payment information', 'A day to discover and create', 'Parent and teacher meeting', 'Welcome to our school community'];
            $titlesMy = ['ဒီဇင်ဘာ စာမေးပွဲ အစီအစဉ်', 'ကျောင်းပိတ်ရက် အသိပေးချက်', 'ကျောင်းလခဆိုင်ရာ အချက်အလက်', 'ဖန်တီးမှုနှင့် လေ့လာရေးနေ့', 'မိဘဆရာ တွေ့ဆုံပွဲ', 'ကျောင်းမိသားစုမှ ကြိုဆိုပါသည်'];
            foreach (['exam', 'holiday', 'fees', 'event', 'meeting', 'general'] as $index => $category) {
                Announcement::firstOrCreate(['title_en' => $titles[$index]], ['title_my' => $titlesMy[$index],
                    'body_en' => 'This is a fictional sample notice. Please contact the school for confirmed information.',
                    'body_my' => 'ဤသည် နမူနာကြေညာချက် ဖြစ်ပါသည်။ အတည်ပြု အချက်အလက်အတွက် ကျောင်းသို့ ဆက်သွယ်ပါ။', 'category' => $category,
                    'event_date' => today()->addDays(7 + $index), 'when_text' => today()->addDays(7 + $index)->format('d M Y'),
                    'audience_text' => 'All grades / အတန်းအားလုံး', 'action_text' => 'Contact the school / ကျောင်းသို့ ဆက်သွယ်ပါ',
                    'target' => null, 'is_important' => $index === 0, 'is_new_until' => today()->addDays(30), 'published_at' => now()->subDays($index)]);
            }
        });
    }
}
