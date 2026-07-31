<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'validuser',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_cannot_create_duplicate_employee_email(): void
    {
        Employee::factory()->create([
            'email' => 'duplicate@example.com',
            'phone' => '+15551111111',
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->postJson('/api/employees', [
                             'first_name' => 'John',
                             'last_name'  => 'Doe',
                             'email'      => 'duplicate@example.com',
                             'phone'      => '+15552222222',
                             'salary'     => 50000,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_create_duplicate_employee_phone(): void
    {
        Employee::factory()->create([
            'email' => 'unique1@example.com',
            'phone' => '+15551111111',
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->postJson('/api/employees', [
                             'first_name' => 'Jane',
                             'last_name'  => 'Doe',
                             'email'      => 'unique2@example.com',
                             'phone'      => '+15551111111',
                             'salary'     => 50000,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['phone']);
    }

    public function test_validates_employee_salary_non_negative(): void
    {
        $response = $this->actingAs($this->user, 'api')
                         ->postJson('/api/employees', [
                             'first_name' => 'Alice',
                             'last_name'  => 'Smith',
                             'email'      => 'alice@example.com',
                             'salary'     => -500,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['salary']);
    }

    public function test_csv_import_returns_row_error_messages_with_employee_id(): void
    {
        /** @var EmployeeService $service */
        $service = app(EmployeeService::class);

        $csvContent = "id,first_name,last_name,email,phone,address,salary\n";
        $csvContent .= "101,Valid,Employee,valid.emp@example.com,+15550001,\"City\",5000.00\n";
        $csvContent .= "102,Invalid,Email,not-an-email,+15550002,\"City\",5000.00\n";
        $csvContent .= "103,Negative,Salary,negative@example.com,+15550003,\"City\",-100.00\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $result = $service->importEmployeesFromFile($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed_rows']);
        $this->assertEquals(2, $result['skipped_rows']);
        $this->assertCount(2, $result['errors']);
        $this->assertStringContainsString('[Employee ID: 102]', $result['errors'][0]);
        $this->assertStringContainsString('[Employee ID: 103]', $result['errors'][1]);
    }

    public function test_csv_import_displays_error_when_database_data_already_exists(): void
    {
        // Pre-create employee in database
        $existing = Employee::factory()->create([
            'email' => 'existing.user@example.com',
            'phone' => '+15559998877',
        ]);

        /** @var EmployeeService $service */
        $service = app(EmployeeService::class);

        $csvContent = "first_name,last_name,email,phone,address,salary\n";
        $csvContent .= "Duplicate,User,existing.user@example.com,+15550000,\"City\",5000.00\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $result = $service->importEmployeesFromFile($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['processed_rows']);
        $this->assertEquals(1, $result['skipped_rows']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Duplicate database entry', $result['errors'][0]);
        $this->assertStringContainsString('existing.user@example.com', $result['errors'][0]);
    }

    public function test_validates_uploaded_file_name_and_displays_error(): void
    {
        $csvContent = "first_name,last_name,email,phone,address,salary\n";
        $invalidFile = UploadedFile::fake()->createWithContent('wrong_filename.csv', $csvContent);

        $response = $this->actingAs($this->user, 'api')
                         ->postJson('/api/employees/import', [
                             'file' => $invalidFile,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['file']);

        $errorMessage = $response->json('errors.file.0');
        $this->assertStringContainsString("The uploaded file name 'wrong_filename.csv' is invalid", $errorMessage);
    }
}
