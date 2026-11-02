<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\SchoolReport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct(private SchoolReport $reports) {}

    private function render(string $page, array $data = []): View
    {
        return view('portal.'.$page, $data + ['page' => $page, 'settings' => Setting::pluck('value', 'key')]);
    }

    public function home(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $exam = $this->reports->exam($request->integer('exam') ?: null);
        $studentCount = Student::where('is_active', true)->count();
        $students = $this->studentSearch($request->input('search'));

        return $this->render('home', ['studentCount' => $studentCount, 'teacherCount' => Teacher::count(), 'showCommunitySummary' => $studentCount >= 20,
            'students' => $students,
            'gradeCount' => Grade::count(), 'notice' => Announcement::published()->orderByDesc('is_important')->latest('published_at')->first(),
            'achievers' => $this->reports->achievers($exam)->take(4), 'exam' => $exam, 'exams' => Exam::orderBy('sort_order')->get()]);
    }

    public function announcements(Request $request): View
    {
        $request->validate(['grade' => ['nullable', 'integer', 'exists:grades,id'], 'category' => ['nullable', 'in:exam,holiday,fees,event,meeting,general']]);
        $query = Announcement::published()->orderByDesc('is_important')->latest('published_at');
        if ($request->filled('grade')) {
            $query->where(fn ($q) => $q->whereNull('target')->orWhereJsonContains('target', $request->integer('grade')));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return $this->render('announcements', ['notices' => $query->paginate(12)->withQueryString(), 'grades' => Grade::orderBy('level')->get()]);
    }

    public function grades(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);

        return $this->render('grades', ['grades' => Grade::withCount(['classes', 'teachers'])->with(['classes' => fn ($q) => $q->withCount(['students' => fn ($s) => $s->where('is_active', true)])])->orderBy('level')->get(),
            'students' => $this->studentSearch($request->input('search'))]);
    }

    public function grade(Request $request, Grade $grade): View
    {
        $exam = $this->reports->exam($request->integer('exam') ?: null);
        $classes = $grade->classes()->with(['grade', 'headTeacher' => fn ($q) => $q->select(Teacher::PUBLIC_COLUMNS)])->orderBy('section')->get();
        $class = $request->filled('class') ? $classes->firstWhere('id', $request->integer('class')) : $classes->first();
        abort_unless($class, 404);
        $rows = $this->reports->classRows($class, $exam);

        return $this->render('grade', compact('grade', 'classes', 'class', 'exam', 'rows') + ['exams' => Exam::orderBy('sort_order')->get(),
            'subjects' => $this->reports->subjects($class), 'timetable' => $class->timetables()->with(['subject', 'teacher' => fn ($q) => $q->select(Teacher::PUBLIC_COLUMNS)])->orderBy('period_no')->get(),
            'studentCount' => $class->students()->where('is_active', true)->count()]);
    }

    public function student(int $student): View
    {
        $student = Student::select(Student::PUBLIC_COLUMNS)->where('is_active', true)
            ->with(['schoolClass.grade', 'schoolClass.headTeacher' => fn ($q) => $q->select(Teacher::PUBLIC_COLUMNS)])->findOrFail($student);
        $progress = Exam::orderBy('sort_order')->get()->map(fn ($exam) => ['exam' => $exam, 'row' => $this->reports->classRows($student->schoolClass, $exam)->firstWhere('student.id', $student->id)]);

        return $this->render('student', compact('student', 'progress'));
    }

    public function teachers(): View
    {
        return $this->render('teachers', ['grades' => Grade::with(['teachers' => fn ($q) => $q->select(Teacher::PUBLIC_COLUMNS)->with('headedClasses.grade')])->orderByDesc('level')->get()]);
    }

    public function achievers(Request $request): View
    {
        $exam = $this->reports->exam($request->integer('exam') ?: null);

        return $this->render('achievers', ['exam' => $exam, 'exams' => Exam::orderBy('sort_order')->get(), 'achievers' => $this->reports->achievers($exam)]);
    }

    public function overview(): View
    {
        return $this->render('overview');
    }

    private function studentSearch(?string $search): Collection
    {
        if (blank($search)) {
            return collect();
        }

        return Student::select(Student::PUBLIC_COLUMNS)->where('is_active', true)
            ->where(fn ($query) => $query->where('name_en', 'like', '%'.$search.'%')->orWhere('name_my', 'like', '%'.$search.'%'))
            ->with('schoolClass.grade')->limit(8)->get();
    }
}
