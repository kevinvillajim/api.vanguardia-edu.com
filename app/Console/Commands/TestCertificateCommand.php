<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Course\Models\CourseEnrollment;
use App\Services\CourseConfigService;
use App\Domain\Course\Services\CertificateService;

class TestCertificateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:certificate {enrollmentId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test certificate eligibility for an enrollment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $enrollmentId = $this->argument('enrollmentId');
        
        $enrollment = CourseEnrollment::find($enrollmentId);

        if (!$enrollment) {
            $this->error("Enrollment not found");
            return;
        }

        $this->info("Enrollment ID: " . $enrollment->id);
        $this->info("Student ID: " . $enrollment->student_id);
        $this->info("Progress Percentage: " . $enrollment->progress_percentage);

        $progress = $enrollment->calculateProgress();
        $this->info("Calculated Progress: " . json_encode($progress));

        $thresholds = CourseConfigService::getCertificateThresholds();
        $this->info("Certificate Thresholds: " . json_encode($thresholds));

        $this->info("Can get virtual certificate: " . ($enrollment->canGetVirtualCertificate() ? 'YES' : 'NO'));
        $this->info("Can get complete certificate: " . ($enrollment->canGetCompleteCertificate() ? 'YES' : 'NO'));
        
        // Test CertificateService
        $certificateService = new CertificateService();
        $this->info("Testing CertificateService...");
        
        try {
            // Check for existing certificates first
            $existingVirtual = $enrollment->certificates()->virtual()->valid()->first();
            $this->info("Existing virtual certificate: " . ($existingVirtual ? 'YES - ID: ' . $existingVirtual->id : 'NO'));
            
            $virtualCert = $certificateService->generateVirtualCertificate($enrollment);
            $this->info("Virtual certificate generated: " . ($virtualCert ? 'YES - ID: ' . $virtualCert->id : 'NO'));
        } catch (\Exception $e) {
            $this->error("Error generating virtual certificate: " . $e->getMessage());
        }
    }
}
