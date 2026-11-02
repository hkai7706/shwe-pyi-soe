<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['school.admin_password' => 'InitialPassword123!']);
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('is_admin', true)->first());
    }

    public function test_production_seed_has_structure_without_samples_and_does_not_reset_passwords(): void
    {
        $this->assertDatabaseCount('grades', 13);
        $this->assertDatabaseCount('school_classes', 26);
        $this->assertDatabaseCount('exams', 4);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('teachers', 0);
        $this->assertDatabaseCount('announcements', 0);
        auth()->user()->update(['password' => 'ChangedPassword123!']);
        $this->seed(DatabaseSeeder::class);
        $this->assertTrue(Hash::check('ChangedPassword123!', auth()->user()->fresh()->password));
        $this->assertDatabaseCount('users', 1);
        $this->get('/')->assertOk();
        $this->get('/staff')->assertOk();
    }

    public function test_student_crud_replaces_and_deletes_portraits_and_private_data_remains_private(): void
    {
        $data = ['name_en' => 'New Sample Student', 'name_my' => 'နမူနာ ကျောင်းသား', 'school_class_id' => SchoolClass::first()->id,
            'gender' => 'female', 'date_of_birth' => '2015-01-01', 'parent_name' => 'Private Guardian Sentinel',
            'parent_phone' => '09976543210', 'address' => 'Private Address Sentinel', 'enrolled_on' => '2026-06-01', 'is_active' => 1];
        $this->post('/staff/students', $data + ['photo' => UploadedFile::fake()->createWithContent('first.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j3ioAAAAASUVORK5CYII='))])->assertRedirect(route('admin.records', 'students'))->assertSessionHasNoErrors();
        $student = Student::firstOrFail();
        $this->assertNull($student->photo);
        $this->assertNotNull($student->photo_data);
        $this->get(route('portrait', ['type' => 'students', 'id' => $student->id]))->assertOk()->assertHeader('content-type', 'image/png');
        $this->get('/students/'.$student->id)->assertOk()->assertDontSee('Private Guardian Sentinel')->assertDontSee('09976543210')->assertDontSee('Private Address Sentinel');
        $this->put('/staff/students/'.$student->id, $data + ['photo' => UploadedFile::fake()->createWithContent('second.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j3ioAAAAASUVORK5CYII='))])->assertSessionHasNoErrors();
        $this->assertNotNull($student->fresh()->photo_data);
        $this->post('/staff/students', $data + ['photo' => UploadedFile::fake()->create('fake.php', 1)])->assertSessionHasErrors('photo');
        $this->delete('/staff/students/'.$student->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_uploaded_portraits_are_served_without_a_public_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('students/portrait.jpg', 'portrait image');

        $this->get(route('media', ['path' => 'students/portrait.jpg']))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
        $this->get(route('media', ['path' => 'students/missing.jpg']))->assertNotFound();
    }

    public function test_upper_grade_students_require_and_store_a_main_subject(): void
    {
        $this->get('/staff/students/create')->assertOk()->assertSee('Choose Biology or Economics');

        $gradeTenClass = SchoolClass::query()->whereHas('grade', fn ($query) => $query->where('level', 10))->firstOrFail();
        $data = ['name_en' => 'Upper Grade Student', 'name_my' => 'Upper Grade Student', 'school_class_id' => $gradeTenClass->id,
            'gender' => 'female', 'date_of_birth' => '2010-01-01', 'parent_name' => 'Guardian', 'parent_phone' => '09900000000',
            'address' => null, 'enrolled_on' => '2026-06-01', 'is_active' => 1];

        $this->post('/staff/students', $data)->assertSessionHasErrors('main_subject');
        $this->post('/staff/students', $data + ['main_subject' => 'biology'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', ['name_en' => 'Upper Grade Student', 'main_subject' => 'biology']);
    }

    public function test_fees_are_shown_only_for_the_selected_class(): void
    {
        $class = SchoolClass::firstOrFail();
        $student = Student::create(['name_en' => 'Fee Student', 'name_my' => 'Fee Student', 'school_class_id' => $class->id,
            'gender' => 'male', 'date_of_birth' => '2015-01-01', 'parent_name' => 'Guardian', 'parent_phone' => '09900000000',
            'enrolled_on' => '2026-06-01', 'is_active' => true]);

        $this->get('/staff/fees')->assertOk()->assertDontSee('Fee Student');
        $this->get('/staff/fees?class='.$class->id)->assertOk()->assertSee('Fee Student');
    }

    public function test_teacher_crud_and_head_assignment(): void
    {
        $data = ['name_en' => 'New Sample Teacher', 'name_my' => 'နမူနာ ဆရာ', 'main_grade_id' => Grade::first()->id, 'subject' => 'English', 'qualification' => 'B.Ed.', 'experience_years' => 8, 'phone' => '09988887777'];
        $this->post('/staff/teachers', $data)->assertSessionHasNoErrors();
        $teacher = Teacher::firstOrFail();
        $class = SchoolClass::first();
        $this->post('/staff/head-teachers', ['class' => $class->id, 'head_teacher_id' => $teacher->id])->assertSessionHasNoErrors();
        $this->assertEquals($teacher->id, $class->fresh()->head_teacher_id);
        $this->get('/staff/teachers')->assertOk()->assertDontSee('New Sample Teacher');
        $this->get('/staff/teachers?grade='.$teacher->main_grade_id)->assertOk()->assertSee('New Sample Teacher')->assertSee($class->label);
        $this->put('/staff/teachers/'.$teacher->id, array_replace($data, ['experience_years' => 9]))->assertSessionHasNoErrors();
        $this->assertEquals(9, $teacher->fresh()->experience_years);
        $this->get('/teachers')->assertOk()->assertSee('New Sample Teacher')->assertDontSee('09988887777');
        $this->delete('/staff/teachers/'.$teacher->id)->assertSessionHasNoErrors();
        $this->assertNull($class->fresh()->head_teacher_id);
    }

    public function test_announcement_composer_publishes_updates_and_deletes(): void
    {
        $data = ['title_en' => 'Sample published announcement', 'title_my' => 'နမူနာ ကြေညာချက်', 'body_en' => 'Sample body', 'body_my' => 'နမူနာ အကြောင်းအရာ',
            'category' => 'meeting', 'published_at' => now()->subMinute()->format('Y-m-d H:i:s'), 'is_important' => 1, 'target' => [Grade::first()->id]];
        $this->post('/staff/announcements', $data)->assertSessionHasNoErrors();
        $notice = Announcement::firstOrFail();
        $this->assertSame([Grade::first()->id], $notice->target);
        $this->get('/announcements')->assertOk()->assertSee('Sample published announcement')->assertSee('Important');
        $this->put('/staff/announcements/'.$notice->id, array_replace($data, ['title_en' => 'Updated notice', 'is_important' => 0]))->assertSessionHasNoErrors();
        $this->get('/announcements')->assertSee('Updated notice');
        $this->delete('/staff/announcements/'.$notice->id)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_settings_require_current_password_and_update_hashed_credentials(): void
    {
        $data = ['name_en' => 'School updated', 'name_my' => 'ကျောင်း', 'address' => 'Updated school address', 'phone' => '0912345', 'facebook_url' => 'https://www.facebook.com/example',
            'username' => 'updatedadmin', 'email' => 'updated@example.com', 'current_password' => 'wrong', 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'];
        $this->put('/staff/settings', $data)->assertSessionHasErrors('current_password');
        $this->put('/staff/settings', array_replace($data, ['current_password' => 'InitialPassword123!']))->assertSessionHasNoErrors();
        $this->assertEquals('updatedadmin', auth()->user()->fresh()->username);
        $this->assertTrue(Hash::check('NewPassword123!', auth()->user()->fresh()->password));
        $this->assertSame('School updated', Setting::valueFor('name_en'));
    }
}
