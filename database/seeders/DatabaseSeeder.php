<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Comment;
use App\Models\HealthRecord;
use App\Models\Paw;
use App\Models\Post;
use App\Models\Report;
use App\Models\Story;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin PetDay', 'username' => 'admin', 'email' => 'admin@petday.test',
            'password' => 'password', 'role' => 'admin', 'city' => 'São Paulo',
        ]);

        $vets = collect([
            ['Dra. Camila Rocha', 'camilavet', 'Clínica Patas & Cia', 'SP-18452', 'Clínico geral', 'Rua das Flores, 120 - Pinheiros'],
            ['Dr. Rafael Nunes', 'rafaelvet', 'Hospital Vet 24h Amigo Fiel', 'SP-22019', 'Ortopedia', 'Av. Paulista, 900'],
            ['Dra. Beatriz Lima', 'biavet', 'Gatil Clínico Miau', 'SP-30771', 'Medicina felina', 'Rua Augusta, 45'],
        ])->map(fn ($v) => User::create([
            'name' => $v[0], 'username' => $v[1], 'email' => $v[1].'@petday.test', 'password' => 'password',
            'role' => 'vet', 'clinic_name' => $v[2], 'crmv' => $v[3], 'specialty' => $v[4], 'clinic_address' => $v[5], 'city' => 'São Paulo',
        ]));

        $petsData = [
            ['Rodrigo Cardoso', 'tutor', [['Thor', 'dog', 'Golden Retriever', 'male', '-3 years +12 days', 32.5, ['Brincalhão', 'Guloso', 'Sociável'], 'Rei do parque e devorador de bolinhas 🎾'], ['Mel', 'cat', 'SRD', 'female', '-2 years', 4.2, ['Dorminhoco', 'Dramático'], 'Rainha da casa. Durmo 18h por dia e julgo todos. 😼']]],
            ['Ana Souza', 'anasouza', [['Luna', 'cat', 'Siamês', 'female', '-2 years', 4.1, ['Dramático', 'Carente'], 'Miau é a minha linguagem do amor.'], ['Pipoca', 'rodent', 'Hamster sírio', 'female', '-8 months', 0.15, ['Curioso'], 'Pequena e veloz 🐹']]],
            ['Lucas Pereira', 'lucasp', [['Bidu', 'dog', 'SRD', 'male', '-5 years', 18, ['Protetor', 'Aventureiro'], 'Adotado em 2021, melhor decisão da vida.']]],
            ['Júlia Martins', 'juliam', [['Nala', 'dog', 'Border Collie', 'female', '-1 years', 15.2, ['Curioso', 'Bagunceiro', 'Brincalhão'], 'Mais inteligente que eu, com certeza.'], ['Kiwi', 'bird', 'Calopsita', 'male', '-3 years', 0.09, ['Sociável'], 'Assobio o hino do meu time todo dia 🎶']]],
            ['Pedro Alves', 'pedroa', [['Nina', 'cat', 'Persa', 'female', '-6 years', 5, ['Preguiçoso', 'Dorminhoco'], 'Só acordo para comer.']]],
            ['Mariana Costa', 'maric', [['Paçoca', 'dog', 'Pug', 'male', '-4 years', 8.3, ['Dorminhoco', 'Guloso', 'Dramático'], 'Ronco profissional 😴'], ['Nescau', 'rabbit', 'Mini Lop', 'male', '-1 years', 1.6, ['Tímido'], 'Cenoura > tudo 🥕']]],
        ];

        $images = $this->downloadImages();
        $allPets = collect();
        $tutors = collect();

        foreach ($petsData as [$name, $username, $pets]) {
            $user = User::create([
                'name' => $name, 'username' => $username, 'email' => $username.'@petday.test',
                'password' => 'password', 'role' => 'tutor', 'city' => fake()->randomElement(['São Paulo', 'Campinas', 'Santos', 'Rio de Janeiro']),
            ]);
            $tutors->push($user);
            foreach ($pets as $i => [$pname, $species, $breed, $gender, $birth, $weight, $personality, $bio]) {
                $pet = $user->pets()->create([
                    'name' => $pname, 'species' => $species, 'breed' => $breed, 'gender' => $gender,
                    // Só a Mel faz aniversário hoje (para mostrar o card de parabéns)
                    'birthdate' => $pname === 'Mel' ? now()->modify($birth) : now()->modify($birth)->subDays(rand(20, 300)), 'weight' => $weight, 'personality' => $personality,
                    'bio' => $bio, 'color' => Catalog::PET_COLORS[$allPets->count() % count(Catalog::PET_COLORS)],
                    'avatar' => $this->takeImage($images, $species),
                ]);
                $allPets->push($pet);
                if ($i === 0) {
                    $user->update(['active_pet_id' => $pet->id]);
                }
            }
        }

        foreach ($tutors as $tutor) {
            $tutor->following()->sync($allPets->where('user_id', '!=', $tutor->id)->random(5)->pluck('id'));
        }

        $captions = [
            'moment' => ['Olha essa carinha 🥹', 'Modo fofura ativado', 'Me pegaram no flagra!', 'Domingo perfeito'],
            'walk' => ['Passeio de 5km hoje! 🦮', 'Encontrei 3 amigos no parque', 'Cheirei TODAS as árvores', 'Dia de trilha!'],
            'meal' => ['Ração nova aprovada ✅', 'Roubei um pedaço de frango 🍗', 'Petisco merecido', 'Hora do almoço!'],
            'nap' => ['Soneca das 14h 💤', 'Não me acorde', 'O sofá é meu agora', 'Sonhando com petiscos'],
            'play' => ['Destruí mais um brinquedo 🧸', 'Bolinha é vida 🎾', 'Pega-pega no quintal', 'Cabo de guerra: venci!'],
            'milestone' => ['Aprendi a dar a patinha! 🏆', 'Primeiro banho sem chorar', 'Hoje faz 1 ano que fui adotado ❤️', 'Aprendi a sentar!'],
            'health' => ['Vacina tomada, sou corajoso 💉', 'Check-up em dia!', 'Dentes limpinhos ✨'],
        ];

        foreach ($allPets as $pet) {
            for ($d = 60; $d >= 0; $d--) {
                if (fake()->boolean($pet->name === 'Thor' ? 80 : 40)) {
                    $type = fake()->randomElement(array_keys($captions));
                    $date = now()->subDays($d);
                    Post::create([
                        'pet_id' => $pet->id, 'user_id' => $pet->user_id, 'type' => $type,
                        'mood' => fake()->boolean(80) ? fake()->randomElement(array_keys(Catalog::MOODS)) : null,
                        'body' => fake()->randomElement($captions[$type]),
                        'image' => fake()->boolean(60) ? $this->takeImage($images, $pet->species) : null,
                        'location' => fake()->boolean(30) ? fake()->randomElement(['Parque Ibirapuera', 'Casa', 'Praia', 'Pet shop', 'Quintal']) : null,
                        'diary_date' => $date->toDateString(),
                        'created_at' => $date->copy()->setTime(rand(7, 22), rand(0, 59)),
                    ]);
                }
            }
        }

        $everyone = $tutors->merge($vets);
        $comments = ['Que fofura! 😍', 'Manda um beijo pra ele!', 'Hahaha amei', 'Que lindeza 🐾', 'Parabéns!!', 'Quero apertar 🥺', 'Esse é dos meus!'];
        foreach (Post::all() as $post) {
            $pawers = $everyone->random(rand(0, $everyone->count()));
            foreach ($pawers as $u) {
                Paw::create(['post_id' => $post->id, 'user_id' => $u->id]);
            }
            $n = fake()->boolean(40) ? rand(1, 3) : 0;
            for ($i = 0; $i < $n; $i++) {
                $u = $tutors->random();
                Comment::create(['post_id' => $post->id, 'user_id' => $u->id, 'pet_id' => $u->active_pet_id, 'body' => fake()->randomElement($comments)]);
            }
            $post->forceFill(['paws_count' => $pawers->count(), 'comments_count' => $n])->saveQuietly();
        }

        foreach ($allPets->random(5) as $pet) {
            foreach (range(1, rand(1, 3)) as $i) {
                Story::create([
                    'pet_id' => $pet->id, 'user_id' => $pet->user_id,
                    'image' => fake()->boolean(60) ? $this->takeImage($images, $pet->species) : null,
                    'caption' => fake()->randomElement(['Bom dia! ☀️', 'Olha quem acordou', 'Partiu passeio!', 'Hora do banho 🛁']),
                    'background' => fake()->randomElement(Catalog::STORY_BACKGROUNDS),
                    'sticker' => fake()->randomElement(['🦴', '🎾', '💤', '❤️', null]),
                    'expires_at' => now()->addHours(rand(2, 23)),
                ]);
            }
        }

        foreach ($allPets as $pet) {
            HealthRecord::create(['pet_id' => $pet->id, 'kind' => 'vaccine', 'title' => $pet->species === 'cat' ? 'V4 Felina' : 'V10', 'applied_on' => now()->subMonths(11), 'next_due_on' => now()->addDays(rand(5, 40))]);
            HealthRecord::create(['pet_id' => $pet->id, 'kind' => 'deworming', 'title' => 'Vermífugo', 'applied_on' => now()->subMonths(2), 'next_due_on' => now()->addMonth()]);
            foreach ([true, false] as $future) {
                Appointment::create([
                    'pet_id' => $pet->id, 'tutor_id' => $pet->user_id, 'vet_id' => $vets->random()->id,
                    'scheduled_at' => ($future ? now()->addDays(rand(1, 20)) : now()->subDays(rand(5, 40)))->setTime(rand(8, 17), fake()->randomElement([0, 30])),
                    'type' => fake()->randomElement(array_keys(Catalog::APPOINTMENT_TYPES)),
                    'reason' => fake()->randomElement(['Check-up anual', 'Está coçando muito', 'Vacina anual', 'Mancando da pata traseira', null]),
                    'status' => $future ? fake()->randomElement(['pending', 'confirmed']) : 'completed',
                    'vet_notes' => $future ? null : fake()->randomElement(['Tudo ótimo! Retorno em 1 ano.', 'Receitado antialérgico por 7 dias.', 'Peso ideal, manter dieta.']),
                ]);
            }
        }

        if ($post = Post::inRandomOrder()->first()) {
            Report::create(['user_id' => $tutors->last()->id, 'reportable_type' => Post::class, 'reportable_id' => $post->id, 'reason' => 'Conteúdo que não é de pet']);
        }
    }

    /** Baixa fotos reais de cães e gatos (APIs públicas). Offline → posts só de texto. */
    private function downloadImages(): array
    {
        $out = ['dog' => [], 'cat' => []];
        try {
            $dogs = Http::timeout(10)->get('https://dog.ceo/api/breeds/image/random/24')->json('message') ?? [];
            $cats = collect(Http::timeout(10)->get('https://api.thecatapi.com/v1/images/search?limit=10&mime_types=jpg')->json() ?? [])->pluck('url')->all();
            foreach (['dog' => $dogs, 'cat' => $cats] as $kind => $urls) {
                foreach ($urls as $i => $url) {
                    $res = Http::timeout(15)->get($url);
                    if ($res->successful() && str_starts_with((string) $res->header('Content-Type'), 'image/')) {
                        $path = "seed/{$kind}-{$i}.jpg";
                        Storage::disk('public')->put($path, $res->body());
                        $out[$kind][] = $path;
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->command?->warn('Sem internet para fotos de exemplo: '.$e->getMessage());
        }
        $this->command?->info('Fotos de exemplo: '.count($out['dog']).' cães, '.count($out['cat']).' gatos');

        return $out;
    }

    private function takeImage(array $images, string $species): ?string
    {
        $pool = $images[$species] ?? [];

        return $pool ? $pool[array_rand($pool)] : null;
    }
}
