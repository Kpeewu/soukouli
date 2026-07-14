<?php

namespace Tests\Feature;

use App\Models\AnneeScolaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnneeScolaireSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_year_only_updates_the_calling_user(): void
    {
        $anneeCourante = AnneeScolaire::create(['annee' => '2025-2026', 'courant' => true]);
        $anneeArchivee = AnneeScolaire::create(['annee' => '2024-2025', 'courant' => false]);

        $userA = User::create(['username' => 'userA', 'password' => 'secret']);
        $userB = User::create(['username' => 'userB', 'password' => 'secret']);

        $response = $this->actingAs($userA)->getJson(route('changeYear', $anneeArchivee));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame($anneeArchivee->id, $userA->fresh()->annee_scolaire_id);
        $this->assertNull($userB->fresh()->annee_scolaire_id);
        $this->assertTrue(AnneeScolaire::getAnneeScolaire()->is($anneeCourante));
    }

    public function test_second_user_is_unaffected_by_first_users_switch(): void
    {
        $anneeCourante = AnneeScolaire::create(['annee' => '2025-2026', 'courant' => true]);
        $anneeArchivee = AnneeScolaire::create(['annee' => '2024-2025', 'courant' => false]);

        $userA = User::create(['username' => 'userA', 'password' => 'secret']);
        $userB = User::create(['username' => 'userB', 'password' => 'secret']);

        $this->actingAs($userA)->getJson(route('changeYear', $anneeArchivee))->assertOk();

        $this->actingAs($userA);
        $this->assertTrue(AnneeScolaire::getAnneeScolaireActive()->is($anneeArchivee));

        $this->actingAs($userB);
        $this->assertTrue(AnneeScolaire::getAnneeScolaireActive()->is($anneeCourante));
    }
}
