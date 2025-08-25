<?php

namespace App\Domain\Admin\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;

class AdminService
{
    public function getDashboardStats(): array
    {
        return [
            'users' => $this->getUserStats(),
            'courses' => $this->getCourseStats(),
            'enrollments' => $this->getEnrollmentStats(),
            'certificates' => $this->getCertificateStats(),
            'activity' => $this->getRecentActivity()
        ];
    }

    public function getUserStats(): array
    {
        $total = User::count();
        $activeToday = User::where('last_login_at', '>=', now()->startOfDay())->count();
        $newThisMonth = User::whereMonth('created_at', now()->month)->count();
        
        $roleDistribution = User::select('role', DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->get()
            ->mapWithKeys(function ($item) {
                $roleName = match($item->role) {
                    1 => 'admin',
                    2 => 'student', 
                    3 => 'teacher',
                    default => 'unknown'
                };
                return [$roleName => $item->count];
            });

        return [
            'total_users' => $total,
            'active_today' => $activeToday,
            'new_this_month' => $newThisMonth,
            'role_distribution' => $roleDistribution,
            'growth_rate' => $this->calculateGrowthRate('users', 'created_at')
        ];
    }

    public function getCourseStats(): array
    {
        $totalCourses = DB::table('courses')->count();
        $publishedCourses = DB::table('courses')->where('is_published', true)->count();
        $draftCourses = $totalCourses - $publishedCourses;
        $newThisMonth = DB::table('courses')->whereMonth('created_at', now()->month)->count();

        $coursesWithEnrollments = DB::table('courses')
            ->leftJoin('course_enrollments', 'courses.id', '=', 'course_enrollments.course_id')
            ->select('courses.*', DB::raw('COUNT(course_enrollments.id) as enrollment_count'))
            ->groupBy('courses.id')
            ->orderByDesc('enrollment_count')
            ->limit(10)
            ->get();

        return [
            'total_courses' => $totalCourses,
            'published_courses' => $publishedCourses,
            'draft_courses' => $draftCourses,
            'new_this_month' => $newThisMonth,
            'most_popular' => $coursesWithEnrollments,
            'growth_rate' => $this->calculateGrowthRate('courses', 'created_at')
        ];
    }

    public function getEnrollmentStats(): array
    {
        $totalEnrollments = DB::table('course_enrollments')->count();
        $activeEnrollments = DB::table('course_enrollments')->where('status', 'active')->count();
        $completedEnrollments = DB::table('course_enrollments')->where('status', 'completed')->count();
        $newThisMonth = DB::table('course_enrollments')->whereMonth('enrolled_at', now()->month)->count();

        $monthlyEnrollments = DB::table('course_enrollments')
            ->select(DB::raw('DATE_FORMAT(enrolled_at, "%Y-%m") as month'), DB::raw('COUNT(*) as count'))
            ->where('enrolled_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'total_enrollments' => $totalEnrollments,
            'active_enrollments' => $activeEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'new_this_month' => $newThisMonth,
            'completion_rate' => $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0,
            'monthly_trends' => $monthlyEnrollments,
            'growth_rate' => $this->calculateGrowthRate('course_enrollments', 'enrolled_at')
        ];
    }

    public function getCertificateStats(): array
    {
        $totalCertificates = DB::table('certificates')->count();
        $newThisMonth = DB::table('certificates')->whereMonth('issued_at', now()->month)->count();
        
        $certificatesByMonth = DB::table('certificates')
            ->select(DB::raw('DATE_FORMAT(issued_at, "%Y-%m") as month'), DB::raw('COUNT(*) as count'))
            ->where('issued_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'total_certificates' => $totalCertificates,
            'new_this_month' => $newThisMonth,
            'monthly_trends' => $certificatesByMonth,
            'growth_rate' => $this->calculateGrowthRate('certificates', 'issued_at')
        ];
    }

    public function getSystemHealth(): array
    {
        $diskUsage = $this->getDiskUsage();
        $dbSize = $this->getDatabaseSize();
        
        return [
            'status' => 'operational',
            'disk_usage' => $diskUsage,
            'database_size' => $dbSize,
            'last_backup' => $this->getLastBackupDate(),
            'active_sessions' => $this->getActiveSessionsCount(),
            'error_rate' => $this->getErrorRate()
        ];
    }

    public function getDetailedUserReport(array $filters = []): array
    {
        $query = User::query();
        
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $users = $query->with(['enrollments.course'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return [
            'users' => $users,
            'summary' => [
                'total' => $users->total(),
                'active' => $users->where('active', 1)->count(),
                'inactive' => $users->where('active', 0)->count()
            ]
        ];
    }

    public function getDetailedCourseReport(array $filters = []): array
    {
        $query = DB::table('courses')
            ->leftJoin('users', 'courses.teacher_id', '=', 'users.id')
            ->leftJoin('course_enrollments', 'courses.id', '=', 'course_enrollments.course_id')
            ->select([
                'courses.*',
                'users.name as teacher_name',
                DB::raw('COUNT(course_enrollments.id) as total_enrollments'),
                DB::raw('COUNT(CASE WHEN course_enrollments.status = "completed" THEN 1 END) as completed_enrollments')
            ])
            ->groupBy('courses.id');

        if (!empty($filters['teacher_id'])) {
            $query->where('courses.teacher_id', $filters['teacher_id']);
        }

        if (!empty($filters['is_published'])) {
            $query->where('courses.is_published', $filters['is_published']);
        }

        $courses = $query->get();

        return [
            'courses' => $courses,
            'summary' => [
                'total_courses' => $courses->count(),
                'published' => $courses->where('is_published', 1)->count(),
                'draft' => $courses->where('is_published', 0)->count(),
                'total_enrollments' => $courses->sum('total_enrollments')
            ]
        ];
    }

    public function getRecentActivity(int $limit = 20): array
    {
        $activities = collect();

        $recentEnrollments = DB::table('course_enrollments')
            ->join('users', 'course_enrollments.student_id', '=', 'users.id')
            ->join('courses', 'course_enrollments.course_id', '=', 'courses.id')
            ->select([
                'course_enrollments.enrolled_at as date',
                'users.name as user_name',
                'courses.title as course_title',
                DB::raw('"enrollment" as type')
            ])
            ->where('course_enrollments.enrolled_at', '>=', now()->subDays(7));

        $recentCertificates = DB::table('certificates')
            ->join('users', 'certificates.student_id', '=', 'users.id')
            ->join('courses', 'certificates.course_id', '=', 'courses.id')
            ->select([
                'certificates.issued_at as date',
                'users.name as user_name',
                'courses.title as course_title',
                DB::raw('"certificate" as type')
            ])
            ->where('certificates.issued_at', '>=', now()->subDays(7));

        $recentCourses = DB::table('courses')
            ->join('users', 'courses.teacher_id', '=', 'users.id')
            ->select([
                'courses.created_at as date',
                'users.name as user_name',
                'courses.title as course_title',
                DB::raw('"course_created" as type')
            ])
            ->where('courses.created_at', '>=', now()->subDays(7));

        return $recentEnrollments
            ->union($recentCertificates)
            ->union($recentCourses)
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'type' => $activity->type,
                    'user_name' => $activity->user_name,
                    'course_title' => $activity->course_title,
                    'date' => $activity->date,
                    'description' => $this->getActivityDescription($activity)
                ];
            })
            ->values()
            ->all();
    }

    private function calculateGrowthRate(string $table, string $dateColumn): float
    {
        $currentMonth = DB::table($table)
            ->whereMonth($dateColumn, now()->month)
            ->count();
            
        $previousMonth = DB::table($table)
            ->whereMonth($dateColumn, now()->subMonth()->month)
            ->count();

        if ($previousMonth === 0) return 100;

        return round((($currentMonth - $previousMonth) / $previousMonth) * 100, 1);
    }

    private function getDiskUsage(): array
    {
        $bytes = disk_free_space('/');
        $totalBytes = disk_total_space('/');
        $usedBytes = $totalBytes - $bytes;

        return [
            'used_gb' => round($usedBytes / (1024**3), 2),
            'total_gb' => round($totalBytes / (1024**3), 2),
            'usage_percentage' => round(($usedBytes / $totalBytes) * 100, 1)
        ];
    }

    private function getDatabaseSize(): string
    {
        $size = DB::selectOne("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ");

        return $size->size_mb . ' MB';
    }

    private function getLastBackupDate(): ?string
    {
        return null;
    }

    private function getActiveSessionsCount(): int
    {
        return DB::table('sessions')->count();
    }

    private function getErrorRate(): float
    {
        return 0.1;
    }

    private function getActivityDescription($activity): string
    {
        return match($activity->type) {
            'enrollment' => "{$activity->user_name} se inscribió en {$activity->course_title}",
            'certificate' => "{$activity->user_name} obtuvo certificado de {$activity->course_title}",
            'course_created' => "{$activity->user_name} creó el curso {$activity->course_title}",
            default => 'Actividad desconocida'
        };
    }
}