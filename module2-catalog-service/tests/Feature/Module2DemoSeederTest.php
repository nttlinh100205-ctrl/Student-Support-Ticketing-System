<?php

namespace Tests\Feature;

use App\Models\SupportDepartment;
use App\Models\SupportFaq;
use App\Models\SupportType;
use App\Models\SupportTypeField;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Module2DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Module2DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private function counts(): array
    {
        return [
            SupportDepartment::count(),
            SupportType::count(),
            SupportTypeField::count(),
            SupportFaq::count(),
        ];
    }

    public function test_demo_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(Module2DemoSeeder::class);
        $first = $this->counts();

        $this->seed(Module2DemoSeeder::class);

        $this->assertSame($first, $this->counts());
        $this->assertSame([5, 8], array_slice($first, 0, 2));
    }

    public function test_demo_seeder_works_after_base_seeder(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(Module2DemoSeeder::class);

        $this->assertSame(5, SupportDepartment::count());
        $this->assertSame(8, SupportType::count());

        $refund = SupportType::where('code', 'HOANHP')->firstOrFail();

        $this->assertSame(10, $refund->sla_days);
        $this->assertSame('KHTC', $refund->department->code);
        $this->assertTrue($refund->fields()->where('field_key', 'bank_account')->exists());

        // FAQ đã ẩn trong dữ liệu demo vẫn giữ trạng thái ẩn.
        $this->assertFalse(
            SupportFaq::where('question', 'Lịch thi học kỳ hè đã có chưa?')->value('is_active')
        );
    }
}
