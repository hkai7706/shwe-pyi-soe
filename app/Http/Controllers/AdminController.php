<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\SchoolReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(private SchoolReport $reports) {}

    private function render(string $page, array $data = []): View
    {
        return view('admin.'.$page, $data + ['page' => $page, 'classes' => SchoolClass::with('grade')->get()->sortBy([['grade.level', 'desc'], ['section', 'asc']]),
            'grades' => Grade::orderByDesc('level')->get(), 'settings' => Setting::pluck('value', 'key')]);
    }

    private function model(string $resource): string
    {
        return match ($resource) {
            'students' => Student::class,'teachers' => Teacher::class,'announcements' => Announcement::class,default => abort(404)
        };
    }

    public function overview(Request $request): View
    {
        $exam = $this->reports->exam($request->integer('exam') ?: null);
        $attendance = Attendance::whereDate('date', today())->get();

        return $this->render('overview', ['studentCount' => Student::where('is_active', true)->count(), 'teacherCount' => Teacher::count(),
            'attendancePercent' => $attendance->count() ? round(100 * $attendance->whereIn('status', ['present', 'late'])->count() / $attendance->count(), 1) : null,
            'fees' => $this->reports->feeTotals(), 'achievers' => $this->reports->achievers($exam), 'exam' => $exam, 'exams' => Exam::orderBy('sort_order')->get()]);
    }

    public function records(Request $request, string $resource): View
    {
        $request->validate(['grade' => ['nullable', 'exists:grades,id'], 'class' => ['nullable', 'exists:school_classes,id'], 'search' => ['nullable', 'string', 'max:100']]);
        $query = ($this->model($resource))::query();
        if ($resource === 'students') {
            $query->select('students.*')
                ->join('school_classes', 'students.school_class_id', '=', 'school_classes.id')
                ->join('grades', 'school_classes.grade_id', '=', 'grades.id')
                ->with(['schoolClass.grade', 'fee'])
                ->withCount(['attendance', 'attendance as attended_count' => fn ($q) => $q->whereIn('status', ['present', 'late'])]);
            if ($request->filled('grade')) {
                $query->whereHas('schoolClass', fn ($q) => $q->where('grade_id', $request->grade));
            }
            if ($request->filled('class')) {
                $query->where('school_class_id', $request->class);
            }
            $query->orderByDesc('grades.level')
                ->orderByRaw("case students.main_subject when 'biology' then 0 when 'economics' then 1 else 2 end")
                ->orderBy('school_classes.section')
                ->orderBy('students.name_en');
        } elseif ($resource === 'teachers') {
            $query->with(['grade', 'headedClasses.grade'])->orderByDesc('main_grade_id')->orderBy('name_en');
        } else {
            $query->orderByDesc('is_important')->latest('published_at');
        }
        if ($request->filled('search')) {
            $field = $resource === 'announcements' ? 'title' : 'name';
            $query->where(fn ($q) => $q->where($field.'_en', 'like', '%'.$request->search.'%')->orWhere($field.'_my', 'like', '%'.$request->search.'%'));
        }

        return $this->render('records', ['resource' => $resource, 'records' => $query->paginate(30)->withQueryString(),
            'teachers' => $resource === 'teachers' ? Teacher::orderBy('name_en')->get() : collect()]);
    }

    public function edit(string $resource, ?int $id = null): View
    {
        $model = $this->model($resource);

        return $this->render('edit', ['resource' => $resource, 'record' => $id ? $model::findOrFail($id) : new $model]);
    }

    public function save(Request $request, string $resource, ?int $id = null): RedirectResponse
    {
        $model = $this->model($resource);
        $record = $id ? $model::findOrFail($id) : new $model;
        $rules = match ($resource) {
            'students' => ['name_en' => 'required|string|max:255', 'name_my' => 'required|string|max:255', 'school_class_id' => 'required|exists:school_classes,id',
                'main_subject' => ['nullable', Rule::requiredIf(fn (): bool => SchoolClass::query()->whereKey($request->input('school_class_id'))
                    ->whereHas('grade', fn ($query) => $query->where('level', '>=', 10))->exists()), Rule::in(['biology', 'economics'])],
                'gender' => ['required', Rule::in(['male', 'female', 'other'])], 'date_of_birth' => 'required|date|before:today', 'parent_name' => 'required|string|max:255',
                'parent_phone' => 'required|string|max:40', 'address' => 'nullable|string|max:2000', 'enrolled_on' => 'required|date', 'is_active' => 'boolean'],
            'teachers' => ['name_en' => 'required|string|max:255', 'name_my' => 'required|string|max:255', 'main_grade_id' => 'required|exists:grades,id',
                'subject' => 'required|string|max:255', 'qualification' => 'required|string|max:255', 'experience_years' => 'required|integer|min:0|max:80', 'phone' => 'nullable|string|max:40'],
            'announcements' => ['title_en' => 'required|string|max:255', 'title_my' => 'required|string|max:255', 'body_en' => 'required|string|max:20000', 'body_my' => 'required|string|max:20000',
                'category' => ['required', Rule::in(['exam', 'holiday', 'fees', 'event', 'meeting', 'general'])], 'event_date' => 'nullable|date',
                'when_text' => 'nullable|string|max:255', 'audience_text' => 'nullable|string|max:255', 'action_text' => 'nullable|string|max:255',
                'target' => 'nullable|array', 'target.*' => 'integer|exists:grades,id', 'is_important' => 'boolean', 'is_new_until' => 'nullable|date', 'published_at' => 'required|date']
        };
        if ($resource !== 'announcements') {
            $rules['photo'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'];
        }
        $data = $request->validate($rules);
        unset($data['photo']);
        if ($resource === 'students') {
            $data['is_active'] = $request->boolean('is_active');
            $class = SchoolClass::with('grade')->findOrFail($data['school_class_id']);
            if ($class->grade->level < 10) {
                $data['main_subject'] = null;
            }
        }
        if ($resource === 'announcements') {
            $data['is_important'] = $request->boolean('is_important');
            $data['target'] = $request->filled('target') ? array_map('intval', $request->input('target')) : null;
        }
        $oldPhoto = $record->photo;
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store($resource, 'public');
        }
        try {
            $record->fill($data)->save();
        } catch (\Throwable $exception) {
            if (isset($data['photo'])) {
                Storage::disk('public')->delete($data['photo']);
            }
            throw $exception;
        }
        if (isset($data['photo']) && $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return to_route('admin.records', $resource)->with('success', __('ui.saved'));
    }

    public function delete(string $resource, int $id): RedirectResponse
    {
        $record = ($this->model($resource))::findOrFail($id);
        $photo = $record->photo;
        $record->delete();
        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        return back()->with('success', __('ui.deleted'));
    }

    private function selectedClass(Request $request): ?SchoolClass
    {
        return $request->filled('class') ? SchoolClass::with('grade')->findOrFail($request->integer('class')) : SchoolClass::with('grade')->first();
    }

    public function attendance(Request $request): View
    {
        $request->validate(['date' => 'nullable|date']);
        $class = $this->selectedClass($request);
        $date = $request->input('date', today()->toDateString());

        return $this->render('attendance', ['class' => $class, 'date' => $date, 'students' => $class?->students()->where('is_active', true)->orderBy('name_en')->get() ?? collect(),
            'entries' => $class ? Attendance::whereDate('date', $date)->whereIn('student_id', $class->students()->select('id'))->pluck('status', 'student_id') : collect()]);
    }

    public function saveAttendance(Request $request): RedirectResponse
    {
        $data = $request->validate(['class' => 'required|exists:school_classes,id', 'date' => 'required|date|before_or_equal:today', 'statuses' => 'required|array', 'statuses.*' => ['required', Rule::in(['present', 'late', 'absent'])]]);
        $ids = Student::where('school_class_id', $data['class'])->where('is_active', true)->pluck('id');
        abort_unless(count(array_diff(array_keys($data['statuses']), $ids->all())) === 0, 422);
        DB::transaction(function () use ($data) {
            foreach ($data['statuses'] as $id => $status) {
                Attendance::where('student_id', $id)->whereDate('date', $data['date'])->firstOrNew()
                    ->fill(['student_id' => $id, 'date' => $data['date'], 'status' => $status])->save();
            }
        });

        return back()->with('success', __('ui.saved'));
    }

    public function marks(Request $request): View
    {
        $class = $this->selectedClass($request);
        $exam = $this->reports->exam($request->integer('exam') ?: null);

        return $this->render('marks', ['class' => $class, 'exam' => $exam, 'exams' => Exam::orderBy('sort_order')->get(),
            'subjects' => $class ? $this->reports->subjects($class) : collect(), 'rows' => $class ? $this->reports->classRows($class, $exam) : collect()]);
    }

    public function saveMarks(Request $request): RedirectResponse
    {
        $data = $request->validate(['class' => 'required|exists:school_classes,id', 'exam' => 'required|exists:exams,id',
            'scores' => 'required|array', 'scores.*' => 'array', 'scores.*.*' => 'nullable|integer|min:0|max:100']);
        $class = SchoolClass::with('grade')->findOrFail($data['class']);
        $students = $class->students()->where('is_active', true)->pluck('id')->all();
        $subjects = $this->reports->subjects($class)->modelKeys();
        DB::transaction(function () use ($data, $students, $subjects) {
            foreach ($data['scores'] as $student => $scores) {
                abort_unless(in_array((int) $student, $students, true), 422);
                foreach ($scores as $subject => $score) {
                    abort_unless(in_array((int) $subject, $subjects, true), 422);
                    $key = ['student_id' => $student, 'subject_id' => $subject, 'exam_id' => $data['exam']];
                    if ($score === null) {
                        Mark::where($key)->delete();
                    } else {
                        Mark::updateOrCreate($key, ['score' => $score]);
                    }
                }
            }
        });

        return back()->with('success', __('ui.saved'));
    }

    public function fees(Request $request): View
    {
        $request->validate(['class' => ['nullable', 'exists:school_classes,id']]);
        $class = $request->filled('class') ? SchoolClass::with('grade')->findOrFail($request->integer('class')) : null;
        $students = $class?->students()
            ->with('fee')
            ->orderByRaw("case main_subject when 'biology' then 0 when 'economics' then 1 else 2 end")
            ->orderBy('name_en')
            ->get() ?? collect();
        $fees = $students->pluck('fee')->filter();

        return $this->render('fees', ['class' => $class, 'students' => $students, 'totals' => $this->reports->feeTotals($fees)]);
    }

    public function saveFee(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate(['amount_due' => 'required|numeric|min:0|max:999999999', 'amount_paid' => 'required|numeric|min:0|lte:amount_due',
            'method' => ['nullable', Rule::in(['cash', 'KBZPay', 'WavePay', 'bank'])], 'paid_on' => 'nullable|date', 'note' => 'nullable|string|max:2000',
            'action' => ['nullable', Rule::in(['paid', 'undo', 'save'])]]);
        if (($data['action'] ?? '') === 'paid') {
            $data['amount_paid'] = $data['amount_due'];
            $data['paid_on'] = today();
        }
        if (($data['action'] ?? '') === 'undo') {
            $data['amount_paid'] = 0;
            $data['paid_on'] = null;
            $data['method'] = null;
        }
        unset($data['action']);
        if ((float) $data['amount_paid'] > 0 && empty($data['method'])) {
            return back()->withErrors(['method' => __('ui.method_required')]);
        }
        if ((float) $data['amount_paid'] === 0.0) {
            $data['paid_on'] = null;
            $data['method'] = null;
        }
        $student->fee()->updateOrCreate(['student_id' => $student->id], $data);

        return back()->with('success', __('ui.saved'));
    }

    public function assignHead(Request $request): RedirectResponse
    {
        $data = $request->validate(['class' => 'required|exists:school_classes,id', 'head_teacher_id' => 'nullable|exists:teachers,id']);
        SchoolClass::findOrFail($data['class'])->update(['head_teacher_id' => $data['head_teacher_id']]);

        return back()->with('success', __('ui.saved'));
    }

    public function settings(): View
    {
        return $this->render('settings');
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['name_en' => 'required|string|max:255', 'name_my' => 'required|string|max:255', 'address' => 'required|string|max:2000',
            'phone' => 'nullable|string|max:40', 'facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:100', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required', 'current_password'], 'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        DB::transaction(function () use ($data, $user) {
            foreach (['name_en', 'name_my', 'address', 'phone', 'facebook_url'] as $key) {
                Setting::updateOrCreate(['key' => $key], ['value' => $data[$key] ?? '']);
            }
            $account = ['username' => $data['username'], 'email' => $data['email']];
            if (! empty($data['password'])) {
                $account['password'] = $data['password'];
            }
            $user->update($account);
        });
        $request->session()->regenerate();

        return back()->with('success', __('ui.saved'));
    }
}
