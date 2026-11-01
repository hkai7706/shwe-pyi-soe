<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! User::where('is_admin', true)->exists()) {
            $password = config('school.admin_password') ?: Str::password(24);
            if (! config('school.admin_password')) {
                $this->command?->warn('Save initial admin password: '.$password);
            }
            User::create(['name' => config('school.admin_name'), 'username' => config('school.admin_username'),
                'email' => config('school.admin_email'), 'password' => $password, 'is_admin' => true]);
        }
        for ($level = 0; $level <= 12; $level++) {
            $grade = Grade::firstOrCreate(['level' => $level], ['name_en' => $level ? 'Grade '.$level : 'KG',
                'name_my' => $level ? 'အတန်း '.strtr((string) $level, ['0' => '၀', '1' => '၁', '2' => '၂', '3' => '၃', '4' => '၄', '5' => '၅', '6' => '၆', '7' => '၇', '8' => '၈', '9' => '၉']) : 'မူကြို', 'sort_order' => $level]);
            foreach (['A', 'B'] as $section) {
                SchoolClass::firstOrCreate(['grade_id' => $grade->id, 'section' => $section]);
            }
        }
        foreach (['Myanmar' => 'မြန်မာစာ', 'English' => 'အင်္ဂလိပ်စာ', 'Mathematics' => 'သင်္ချာ', 'Science' => 'သိပ္ပံ', 'Social Studies' => 'လူမှုရေး', 'Physics' => 'ရူပဗေဒ', 'Chemistry' => 'ဓာတုဗေဒ', 'Biology' => 'ဇီဝဗေဒ'] as $en => $my) {
            Subject::firstOrCreate(['name_en' => $en], ['name_my' => $my]);
        }
        foreach ([[7, 'July Exam', 'ဇူလိုင် စာမေးပွဲ'], [10, 'October Exam', 'အောက်တိုဘာ စာမေးပွဲ'], [12, 'December Exam', 'ဒီဇင်ဘာ စာမေးပွဲ'], [2, 'February Exam', 'ဖေဖော်ဝါရီ နောက်ဆုံးစာမေးပွဲ']] as $order => [$month,$en,$my]) {
            Exam::updateOrCreate(['month' => $month], ['name_en' => $en, 'name_my' => $my, 'is_final' => $month === 2, 'sort_order' => $order]);
        }
        foreach (['name_en' => 'Shwe Pyi Soe Private High School', 'name_my' => 'ရွှေပြည်စိုးကိုယ်ပိုင်အထက်တန်းကျောင်း',
            'address' => 'No.18 Si Thu St., corner of Pearl Kan St., Ward 65, South Dagon Township, Yangon, Myanmar',
            'phone' => '', 'facebook_url' => 'https://www.facebook.com/profile.php?id=100063652883524'] as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
