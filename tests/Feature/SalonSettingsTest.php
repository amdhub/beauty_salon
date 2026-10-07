<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SalonSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SalonSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'administrator'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    public static function roles(): array
    {
        return [['administrator', true], ['manager', true], ['receptionist', false], ['beautician', false]];
    }

    #[DataProvider('roles')]
    public function test_only_authorized_staff_can_view_and_change_settings(string $role, bool $allowed): void
    {
        $actor = $this->actor($role);
        $this->assertSame($allowed, $actor->can('viewAny', SalonSetting::class));
        $this->assertSame($allowed, $actor->can('update', SalonSetting::class));

        $this->get(route('settings.edit'))->assertStatus($allowed ? 200 : 403);
        $this->put(route('settings.update'), ['whatsapp_number' => '+243990000123'])->assertStatus($allowed ? 302 : 403);

        $this->assertDatabaseCount('salon_settings', $allowed ? 1 : 0);
    }

    public function test_guests_cannot_view_or_change_the_responsible_number(): void
    {
        $this->get(route('settings.edit'))->assertRedirect(route('login'));
        $this->put(route('settings.update'), ['whatsapp_number' => '+243990000123'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('salon_settings', 0);
    }

    public function test_saving_normalizes_the_number_and_clearing_disables_whatsapp(): void
    {
        $actor = $this->actor();
        $this->put(route('settings.update'), ['whatsapp_number' => '00243 (990) 000-123', 'key' => 'admin_password'])
            ->assertSessionHasNoErrors()->assertRedirect(route('settings.edit'));

        $this->assertDatabaseHas('salon_settings', ['key' => SalonSetting::RESERVATION_WHATSAPP_NUMBER, 'value' => '+243990000123', 'updated_by' => $actor->id]);
        $this->get(route('settings.edit'))->assertOk()->assertSee('+243990000123')->assertSee('Redirection activée');

        $this->put(route('settings.update'), ['whatsapp_number' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('salon_settings', 1);
        $this->assertNull(SalonSetting::sole()->value);
        $this->get(route('settings.edit'))->assertSee('Redirection désactivée');
    }

    public static function invalidNumbers(): array
    {
        return [
            'local number' => ['0990000123'],
            'too short' => ['+243'],
            'too long' => ['+2431234567890123456'],
            'link instead of number' => ['https://wa.me/243990000123'],
            'array' => [['+243990000123']],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers_do_not_replace_the_saved_destination(mixed $number): void
    {
        $this->actor();
        $setting = SalonSetting::factory()->create(['value' => '+243990000123']);

        $this->put(route('settings.update'), ['whatsapp_number' => $number])->assertSessionHasErrors('whatsapp_number');

        $this->assertSame('+243990000123', $setting->fresh()->value);
    }
}
