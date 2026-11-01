<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Fee;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;

class SchoolReport
{
    public function exam(?int $id = null): Exam
    {
        return $id ? Exam::findOrFail($id) : Exam::where('month', 12)->firstOrFail();
    }

    public function subjects(SchoolClass $class): Collection
    {
        return Subject::whereIn('name_en', $class->grade->level >= 10
            ? ['Myanmar', 'English', 'Mathematics', 'Physics', 'Chemistry', 'Biology']
            : ['Myanmar', 'English', 'Mathematics', 'Science', 'Social Studies'])->orderBy('id')->get();
    }

    public function classRows(SchoolClass $class, Exam $exam): Collection
    {
        $subjectIds = $this->subjects($class)->modelKeys();
        $students = Student::select(Student::PUBLIC_COLUMNS)->where('school_class_id', $class->id)->where('is_active', true)
            ->with(['marks' => fn ($q) => $q->where('exam_id', $exam->id)->whereIn('subject_id', $subjectIds)->with('subject')])
            ->withCount(['attendance', 'attendance as attended_count' => fn ($q) => $q->whereIn('status', ['present', 'late'])])->get();
        $rows = $students->map(function ($student) use ($subjectIds) {
            $total = $student->marks->sum('score');

            return ['student' => $student, 'scores' => $student->marks->pluck('score', 'subject_id'), 'total' => $total,
                'average' => $student->marks->count() ? round($total / $student->marks->count(), 1) : null,
                'complete' => count($subjectIds) > 0 && $student->marks->count() === count($subjectIds),
                'attendance' => $student->attendance_count ? round(100 * $student->attended_count / $student->attendance_count, 1) : null];
        })->sortBy([['total', 'desc'], ['student.name_en', 'asc']])->values();

        return $rows->map(function ($row) use ($rows) {
            $row['rank'] = $row['complete'] ? 1 + $rows->filter(fn ($other) => $other['complete'] && $other['total'] > $row['total'])->count() : null;

            return $row;
        });
    }

    public function achievers(Exam $exam): Collection
    {
        return SchoolClass::with('grade')->get()->sortBy([['grade.level', 'desc'], ['section', 'asc']])->flatMap(function ($class) use ($exam) {
            return $this->classRows($class, $exam)->where('rank', 1)->map(fn ($row) => array_merge($row, ['class' => $class]));
        })->values();
    }

    public function feeTotals(?Collection $fees = null): array
    {
        $fees ??= Fee::all();
        $due = (float) $fees->sum('amount_due');
        $paid = (float) $fees->sum('amount_paid');

        return ['due' => $due, 'paid' => $paid, 'left' => round($due - $paid, 2), 'unpaid' => $fees->where('amount_paid', 0)->sum('amount_due'),
            'percent' => $due ? round(100 * $paid / $due, 1) : 0];
    }
}
