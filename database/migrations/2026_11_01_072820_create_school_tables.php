<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->boolean('is_admin')->default(false);
        });
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_my');
            $table->unsignedTinyInteger('level')->unique();
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_my');
            $table->string('photo')->nullable();
            $table->string('subject');
            $table->string('qualification');
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->foreignId('main_grade_id')->constrained('grades');
            $table->string('phone')->nullable();
            $table->timestamps();
        });
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained();
            $table->string('section', 10);
            $table->foreignId('head_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unique(['grade_id', 'section']);
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained();
            $table->string('name_en');
            $table->string('name_my');
            $table->string('photo')->nullable();
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->text('address')->nullable();
            $table->date('enrolled_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name_en')->unique();
            $table->string('name_my');
            $table->timestamps();
        });
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_my');
            $table->unsignedTinyInteger('month')->unique();
            $table->boolean('is_final')->default(false);
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained();
            $table->foreignId('subject_id')->constrained();
            $table->unsignedTinyInteger('score');
            $table->unique(['student_id', 'exam_id', 'subject_id']);
            $table->timestamps();
        });
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'late', 'absent']);
            $table->unique(['student_id', 'date']);
            $table->timestamps();
        });
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount_due', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->enum('method', ['cash', 'KBZPay', 'WavePay', 'bank'])->nullable();
            $table->date('paid_on')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_my');
            $table->text('body_en');
            $table->text('body_my');
            $table->enum('category', ['exam', 'holiday', 'fees', 'event', 'meeting', 'general']);
            $table->date('event_date')->nullable();
            $table->string('when_text')->nullable();
            $table->string('audience_text')->nullable();
            $table->string('action_text')->nullable();
            $table->json('target')->nullable();
            $table->boolean('is_important')->default(false);
            $table->date('is_new_until')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();
        });
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->unsignedTinyInteger('period_no');
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->unique(['school_class_id', 'weekday', 'period_no']);
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['settings', 'timetables', 'announcements', 'fees', 'attendance', 'marks', 'exams', 'subjects', 'students', 'school_classes', 'teachers', 'grades'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'is_admin']);
        });
    }
};
