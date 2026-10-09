<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DirectoryNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_resolve_names_without_private_profile_fields(): void
    {
        $staff = User::factory()->create(['role' => 'STAFF', 'status' => 'ACTIVE']);
        $student = User::factory()->create(['role' => 'STUDENT', 'status' => 'ACTIVE', 'name' => 'Nguyễn Minh Anh']);
        Sanctum::actingAs($staff);
        $this->getJson('/api/v1/directory/names?ids[]='.$student->id)->assertOk()->assertExactJson(['data' => [['id' => $student->id, 'name' => 'Nguyễn Minh Anh', 'role' => 'STUDENT']]]);
    }

    public function test_student_cannot_lookup_other_students(): void
    {
        $student = User::factory()->create(['role' => 'STUDENT', 'status' => 'ACTIVE']);
        $other = User::factory()->create(['role' => 'STUDENT', 'status' => 'ACTIVE']);
        Sanctum::actingAs($student);
        $this->getJson('/api/v1/directory/names?ids[]='.$other->id)->assertOk()->assertJsonCount(0, 'data');
        $student->update(['status' => 'LOCKED']);
        $this->getJson('/api/v1/directory/names?ids[]='.$student->id)->assertForbidden();
    }

    public function test_directory_requires_authentication(): void
    {
        $this->getJson('/api/v1/directory/names?ids[]=1')->assertUnauthorized();
    }
}
