<?php

use App\Models\Book;
use App\Models\Genre;
use App\Repositories\BookRepository;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->repository = new BookRepository();
});

test('it filters books by genre', function () {
    $genre1 = Genre::factory()->create();
    $genre2 = Genre::factory()->create();

    Book::factory()->count(3)->create(['genre_id' => $genre1->id]);
    Book::factory()->count(2)->create(['genre_id' => $genre2->id]);

    $results = $this->repository->all(['genre_id' => $genre1->id]);

    expect($results->total())->toBe(3);
    foreach ($results as $book) {
        expect($book->genre_id)->toBe($genre1->id);
    }
});

test('it filters books by status', function () {
    Book::factory()->count(3)->create(['status' => 'Disponible']);
    Book::factory()->count(2)->create(['status' => 'Prestado']);

    $results = $this->repository->all(['status' => 'Disponible']);

    expect($results->total())->toBe(3);
});

test('it filters books by global query', function () {
    Book::factory()->create(['title' => 'Quijote de la Mancha']);
    Book::factory()->create(['author' => 'Miguel de Cervantes']);
    Book::factory()->create(['isbn' => '9876543210']);
    Book::factory()->create(['title' => 'Otro Libro']);

    // Búsqueda por título
    $resTitle = $this->repository->all(['q' => 'Quijote']);
    expect($resTitle->total())->toBe(1);

    // Búsqueda por autor
    $resAuthor = $this->repository->all(['q' => 'Cervantes']);
    expect($resAuthor->total())->toBe(1);
});

test('it returns all records without pagination', function () {
    Book::factory()->count(15)->create();

    $results = $this->repository->all(['per_page' => 'all']);

    expect($results)->toBeInstanceOf(Collection::class);
    expect($results)->toHaveCount(15);
});

test('it filters by multiple criteria', function () {
    $genre = Genre::factory()->create();
    Book::factory()->create([
        'title' => 'Libro Especial',
        'genre_id' => $genre->id,
        'status' => 'Disponible'
    ]);
    Book::factory()->create(['title' => 'Libro Especial', 'status' => 'Prestado']);

    $results = $this->repository->all([
        'title' => 'Especial',
        'genre_id' => $genre->id,
        'status' => 'Disponible'
    ]);

    expect($results->total())->toBe(1);
});
