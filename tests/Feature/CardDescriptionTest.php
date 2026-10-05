<?php

use App\Livewire\Dashboard;
use App\Livewire\SharedVariables\Environment\Index as SharedVariablesEnvironmentIndex;
use App\Livewire\SharedVariables\Project\Index as SharedVariablesProjectIndex;
use App\Models\InstanceSettings;
use App\Models\PrivateKey;
use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    InstanceSettings::unguarded(fn () => InstanceSettings::query()->create(['id' => 0]));

    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->user->teams()->attach($this->team, ['role' => 'owner']);
    $this->actingAs($this->user);
    session(['currentTeam' => $this->team]);

    $this->described = Project::factory()->create(['team_id' => $this->team->id, 'name' => 'Described', 'description' => 'Has a description']);
    $this->undescribed = Project::factory()->create(['team_id' => $this->team->id, 'name' => 'Undescribed', 'description' => null]);
});

function emptyCardDescriptionCount(string $html): int
{
    return preg_match_all('/<p class="mt-0\.5 truncate text-\[11px\][^"]*">\s*<\/p>/', $html);
}

it('does not render an empty description under dashboard project and server titles', function () {
    $privateKey = PrivateKey::factory()->create(['team_id' => $this->team->id]);
    Server::factory()->create(['team_id' => $this->team->id, 'private_key_id' => $privateKey->id, 'name' => 'Undescribed server', 'description' => null]);

    $html = Livewire::test(Dashboard::class)
        ->assertSee('Undescribed')
        ->assertSee('Undescribed server')
        ->assertSee('Has a description')
        ->html();

    expect(emptyCardDescriptionCount($html))->toBe(0);
});

it('does not render an empty description on shared variable project and environment cards', function () {
    $this->undescribed->environments()->update(['description' => null]);

    $projects = Livewire::test(SharedVariablesProjectIndex::class)
        ->assertSee('Undescribed')
        ->assertSee('Has a description')
        ->html();
    $environments = Livewire::test(SharedVariablesEnvironmentIndex::class)
        ->assertSee('Undescribed')
        ->html();

    expect(emptyCardDescriptionCount($projects))->toBe(0)
        ->and(emptyCardDescriptionCount($environments))->toBe(0);
});
