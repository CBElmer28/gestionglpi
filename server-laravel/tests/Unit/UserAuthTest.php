<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password is encrypted and verifiable', function () {
    $password = 'secret-123';
    
    $user = User::factory()->create([
        'password' => $password
    ]);

    // La contraseña en la BD no debe ser igual a la de texto plano
    expect($user->password)->not->toBe($password);
    
    // Pero el Hash debe validarla correctamente
    expect(Hash::check($password, $user->password))->toBeTrue();
});

test('user roles logic detects admin and specific roles', function () {
    $admin = User::factory()->make(['role' => 'admin']);
    $bibliotecario = User::factory()->make(['role' => 'bibliotecario']);

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($bibliotecario->isAdmin())->toBeFalse()
        ->and($bibliotecario->hasRole('bibliotecario'))->toBeTrue();
});
