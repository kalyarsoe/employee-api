<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user for authentication
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_user_can_login_and_receive_passport_token(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'access_token',
                     'token_type',
                     'user' => ['id', 'username', 'email'],
                 ]);
    }

    public function test_employee_repository_crud_and_soft_delete(): void
    {
        /** @var EmployeeRepositoryInterface $repository */
        $repository = app(EmployeeRepositoryInterface::class);

        // Create
        $employee = $repository->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'phone' => '+15550001',
            'address' => 'Scranton, PA',
            'salary' => 95000.00,
        ]);

        $this->assertDatabaseHas('employees', ['email' => 'michael.scott@dundermifflin.com']);

        // Read
        $found = $repository->find($employee->id);
        $this->assertNotNull($found);
        $this->assertEquals('Michael', $found->first_name);

        // Update
        $updated = $repository->update($employee->id, ['salary' => 100000.00]);
        $this->assertEquals(100000.00, $updated->salary);

        // Soft Delete
        $result = $repository->delete($employee->id);
        $this->assertTrue($result);
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);

        // Restore
        $restoreResult = $repository->restore($employee->id);
        $this->assertTrue($restoreResult);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);

        // Force Delete
        $forceResult = $repository->forceDelete($employee->id);
        $this->assertTrue($forceResult);
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_faker_employee_generation_service(): void
    {
        /** @var EmployeeService $service */
        $service = app(EmployeeService::class);

        $initialCount = Employee::count();
        $result = $service->generateFakerEmployees(100);

        $this->assertTrue($result['success']);
        $this->assertEquals(100, $result['count']);
        $this->assertEquals($initialCount + 100, Employee::count());
    }

    public function test_csv_import_service(): void
    {
        /** @var EmployeeService $service */
        $service = app(EmployeeService::class);

        $csvContent = "first_name,last_name,email,phone,address,salary\n";
        $csvContent .= "Jim,Halpert,jim.halpert@dundermifflin.com,+15550002,\"Scranton, PA\",70000.00\n";
        $csvContent .= "Pam,Beesly,pam.beesly@dundermifflin.com,+15550003,\"Scranton, PA\",60000.00\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $result = $service->importEmployeesFromFile($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed_rows']);
        $this->assertDatabaseHas('employees', ['email' => 'jim.halpert@dundermifflin.com']);
        $this->assertDatabaseHas('employees', ['email' => 'pam.beesly@dundermifflin.com']);
    }
}
