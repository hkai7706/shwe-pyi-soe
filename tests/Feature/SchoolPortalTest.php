<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolReport;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['school.admin_password' => 'TestPassword123!']);
        $this->seed(SampleDataSeeder::class);
    }

    public function test_all_public_pages_are_available_and_exclude_private_information_and_staff_links(): void
    {
        $student = Student::first();
        $privateColumns = ['parent_name', 'parent_phone', 'date_of_birth', 'address'];
        DB::enableQueryLog();
        foreach (['/', '/announcements', '/grades', '/grades/'.$student->schoolClass->grade_id, '/teachers', '/top-achievers', '/students/'.$student->id, '/overview'] as $path) {
            $response = $this->get($path)->assertOk();
            $response->assertDontSee($student->parent_name)->assertDontSee($student->parent_phone)
                ->assertDontSee('Fictional sample address')->assertDontSee('status-paid')->assertDontSee('status-unpaid')
                ->assertDontSee('/staff')->assertDontSee('Admin Login')->assertDontSee('parent_phone');
        }
        foreach (DB::getQueryLog() as $entry) {
            if (str_contains($entry['query'], 'from "students"')) {
                foreach ($privateColumns as $column) {
                    $this->assertStringNotContainsString('"'.$column.'"', $entry['query']);
                }
                $this->assertStringNotContainsString('select * from "students"', $entry['query']);
            }
        }
        DB::disableQueryLog();
    }

    public function test_exam_switch_changes_results_and_keeps_exactly_four_ordered_exams(): void
    {
        $this->assertSame([7, 10, 12, 2], Exam::orderBy('sort_order')->pluck('month')->all());
        $class = SchoolClass::with('grade')->first();
        $reports = app(SchoolReport::class);
        $july = Exam::where('month', 7)->first();
        $december = Exam::where('month', 12)->first();
        $julyRows = $reports->classRows($class, $july);
        $decemberRows = $reports->classRows($class, $december);
        $this->assertNotSame($julyRows->pluck('total')->all(), $decemberRows->pluck('total')->all());
        $response = $this->get('/grades/'.$class->grade_id.'?exam='.$july->id)->assertOk()->assertSee('July Exam Marks');
        $this->assertEquals($julyRows->first()['total'], $response->viewData('rows')->first()['total']);
        $this->get('/grades/'.$class->grade_id)->assertOk()->assertSee('December Exam Marks');
        $this->get('/top-achievers?exam='.$july->id)->assertOk()->assertSee('July Exam Marks');
    }

    public function test_tied_complete_results_share_rank_and_incomplete_results_are_not_ranked(): void
    {
        $reports = app(SchoolReport::class);
        $class = SchoolClass::with('grade')->first();
        $exam = $reports->exam();
        $ids = $class->students()->pluck('id');
        Mark::whereIn('student_id', $ids)->where('exam_id', $exam->id)->update(['score' => 90]);
        $this->assertSame([1, 1], $reports->classRows($class, $exam)->pluck('rank')->all());
        Mark::where('student_id', $ids[0])->where('exam_id', $exam->id)->first()->delete();
        $this->assertNull($reports->classRows($class, $exam)->firstWhere('student.id', $ids[0])['rank']);
    }

    public function test_fee_totals_and_statuses_are_computed(): void
    {
        $fees = collect([new Fee(['amount_due' => 100, 'amount_paid' => 100]), new Fee(['amount_due' => 200, 'amount_paid' => 75]), new Fee(['amount_due' => 300, 'amount_paid' => 0])]);
        $totals = app(SchoolReport::class)->feeTotals($fees);
        $this->assertEquals(600, $totals['due']);
        $this->assertEquals(175, $totals['paid']);
        $this->assertEquals(425, $totals['left']);
        $this->assertEquals(300, $totals['unpaid']);
        $this->assertSame(['paid', 'partial', 'unpaid'], $fees->pluck('status')->all());
        $databaseTotals = app(SchoolReport::class)->feeTotals();
        $this->assertEquals(Fee::sum('amount_due') - Fee::sum('amount_paid'), $databaseTotals['left']);
    }

    public function test_locale_switch_translates_entire_public_ui_and_is_persisted(): void
    {
        $this->post('/language/my')->assertRedirect()->assertSessionHas('locale', 'my')->assertCookie('locale', 'my');
        $this->get('/grades')->assertOk()->assertSee('အတန်းများ')->assertSee('မူကြို');
        $this->post('/language/fr')->assertNotFound();
    }

    public function test_future_notices_are_hidden_and_target_filter_is_applied(): void
    {
        Announcement::create(['title_en' => 'Future secret notice', 'title_my' => 'နမူနာ', 'body_en' => 'Later', 'body_my' => 'နမူနာ', 'category' => 'general', 'published_at' => now()->addDay()]);
        $grade = Grade::first();
        Announcement::create(['title_en' => 'Targeted notice', 'title_my' => 'နမူနာ', 'body_en' => 'Specific', 'body_my' => 'နမူနာ', 'category' => 'general', 'published_at' => now(), 'target' => [$grade->id]]);
        $this->get('/announcements')->assertDontSee('Future secret notice');
        $this->get('/announcements?grade='.$grade->id)->assertSee('Targeted notice');
        $this->get('/announcements?grade='.Grade::orderByDesc('id')->first()->id)->assertDontSee('Targeted notice');
    }

    public function test_admin_pages_and_write_actions_are_protected(): void
    {
        foreach (['', '/students', '/teachers', '/marks', '/fees', '/attendance', '/announcements', '/settings'] as $suffix) {
            $this->get('/staff'.$suffix)->assertRedirect('/staff/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
        $this->post('/staff/students', [])->assertRedirect('/staff/login');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/staff')->assertForbidden()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_admin_can_render_every_workflow(): void
    {
        $this->actingAs(User::where('is_admin', true)->first());
        foreach (['', '/students', '/students/create', '/students/'.Student::first()->id.'/edit', '/teachers', '/teachers/create', '/announcements', '/announcements/create', '/attendance', '/marks', '/fees', '/settings'] as $path) {
            $this->get('/staff'.$path)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_marks_and_attendance_validate_class_membership_and_input_ranges(): void
    {
        $this->actingAs(User::where('is_admin', true)->first());
        $student = Student::first();
        $exam = Exam::where('month', 12)->first();
        $subject = $student->marks()->first()->subject_id;
        $payload = ['class' => $student->school_class_id, 'exam' => $exam->id, 'scores' => [$student->id => [$subject => 99]]];
        $this->post('/staff/marks', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('marks', ['student_id' => $student->id, 'exam_id' => $exam->id, 'subject_id' => $subject, 'score' => 99]);
        $payload['scores'][$student->id][$subject] = 101;
        $this->post('/staff/marks', $payload)->assertSessionHasErrors();
        $other = Student::where('school_class_id', '!=', $student->school_class_id)->first();
        $this->post('/staff/attendance', ['class' => $student->school_class_id, 'date' => today()->toDateString(), 'statuses' => [$other->id => 'present']])->assertUnprocessable();
        $this->post('/staff/attendance', ['class' => $student->school_class_id, 'date' => today()->toDateString(), 'statuses' => [$student->id => 'late']])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance', ['student_id' => $student->id, 'date' => today()->toDateString(), 'status' => 'late']);
    }

    public function test_fee_payment_and_undo_recompute_balances(): void
    {
        $this->actingAs(User::where('is_admin', true)->first());
        $student = Student::first();
        $this->put('/staff/fees/'.$student->id, ['amount_due' => 1000, 'amount_paid' => 0, 'method' => 'KBZPay', 'action' => 'paid'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $student->fee()->first()->status);
        $this->assertEquals(0, $student->fee()->first()->left);
        $this->put('/staff/fees/'.$student->id, ['amount_due' => 1000, 'amount_paid' => 1000, 'action' => 'undo'])->assertSessionHasNoErrors();
        $this->assertSame('unpaid', $student->fee()->first()->status);
        $this->assertEquals(1000, $student->fee()->first()->left);
        $this->put('/staff/fees/'.$student->id, ['amount_due' => 1000, 'amount_paid' => 1001])->assertSessionHasErrors('amount_paid');
    }
}
