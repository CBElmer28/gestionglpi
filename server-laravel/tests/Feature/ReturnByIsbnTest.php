<?php

use App\Models\Book;
use App\Models\Genre;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\Scan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** JPEG mínimo válido (1x1) para simular las capturas del celular. */
function jpegCapture(string $name): UploadedFile
{
    $bytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');

    return UploadedFile::fake()->createWithContent($name, $bytes);
}

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolesAndPermissionsSeeder::class);

    Http::fake([
        '*/initSession' => Http::response(['session_token' => 'fake-token'], 200),
        '*/LibrosAsset/*' => Http::response(['id' => 123, 'message' => 'Sincronizado'], 201),
        '*/LibrosAsset' => Http::response(['id' => 123], 201),
    ]);

    $this->bibliotecario = User::factory()->create([
        'role_id' => Role::where('slug', 'bibliotecario')->first()->id,
    ]);

    $this->book = Book::create([
        'title'        => 'Libro de Devolución',
        'author'       => 'Autor Test',
        'isbn'         => '9788466615853',
        'edition'      => '1ra',
        'genre_id'     => Genre::factory()->create()->id,
        'publisher_id' => Publisher::factory()->create()->id,
        'status'       => 'Prestado',
    ]);

    $this->loan = Loan::create([
        'book_id'     => $this->book->id,
        'user_name'   => 'Ana Gómez',
        'loan_date'   => Carbon::yesterday()->toDateString(),
        'return_date' => Carbon::tomorrow()->toDateString(),
        'status'      => 'Activo',
    ]);
});

test('returning by ISBN closes the loan, frees the book and stores both captures', function () {
    $this->actingAs($this->bibliotecario)
        ->post('/api/loans/return-by-isbn', [
            'isbn'        => '978-8466615853',
            'device_info' => 'Celular (Pixel)',
            'front_photo' => jpegCapture('portada.jpg'),
            'back_photo'  => jpegCapture('contraportada.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('book_status', 'Disponible');

    $loan = $this->loan->refresh();
    expect($loan->status)->toBe('Devuelto');
    expect($loan->returned_by)->toBe($this->bibliotecario->id);
    Storage::disk('public')->assertExists([$loan->return_front_photo, $loan->return_back_photo]);

    expect($this->book->refresh()->status)->toBe('Disponible');

    $scan = Scan::where('book_id', $this->book->id)->where('result', 'devolucion')->first();
    expect($scan)->not->toBeNull();
    expect($scan->photo_path)->toBe($loan->return_front_photo);
    expect($scan->photo_back_path)->toBe($loan->return_back_photo);
});

test('both captures are mandatory to return by ISBN', function () {
    $this->actingAs($this->bibliotecario)
        ->post('/api/loans/return-by-isbn', [
            'isbn'        => '9788466615853',
            'front_photo' => jpegCapture('portada.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('back_photo');

    expect($this->loan->refresh()->status)->toBe('Activo');
    expect($this->book->refresh()->status)->toBe('Prestado');
});

test('an overdue loan can also be returned by ISBN', function () {
    $this->loan->update(['status' => 'Atrasado']);

    $this->actingAs($this->bibliotecario)
        ->post('/api/loans/return-by-isbn', [
            'isbn'        => '9788466615853',
            'front_photo' => jpegCapture('portada.jpg'),
            'back_photo'  => jpegCapture('contraportada.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    expect($this->loan->refresh()->status)->toBe('Devuelto');
});

test('returning a book without an open loan is rejected', function () {
    $this->loan->update(['status' => 'Devuelto']);
    $this->book->update(['status' => 'Disponible']);

    $this->actingAs($this->bibliotecario)
        ->post('/api/loans/return-by-isbn', [
            'isbn'        => '9788466615853',
            'front_photo' => jpegCapture('portada.jpg'),
            'back_photo'  => jpegCapture('contraportada.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(409);

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('an unknown ISBN returns 404', function () {
    $this->actingAs($this->bibliotecario)
        ->post('/api/loans/return-by-isbn', [
            'isbn'        => '9780000000002',
            'front_photo' => jpegCapture('portada.jpg'),
            'back_photo'  => jpegCapture('contraportada.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(404);
});

test('users without loans.manage cannot return by ISBN', function () {
    $lector = User::factory()->create([
        'role_id' => Role::where('slug', 'lector')->first()->id,
    ]);

    $this->actingAs($lector)
        ->post('/api/loans/return-by-isbn', [
            'isbn'        => '9788466615853',
            'front_photo' => jpegCapture('portada.jpg'),
            'back_photo'  => jpegCapture('contraportada.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(403);

    expect($this->loan->refresh()->status)->toBe('Activo');
});
