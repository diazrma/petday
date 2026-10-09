<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\VetPanelController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/entrar', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/sair', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [FeedController::class, 'index'])->name('feed');
    Route::get('/explorar', [ExploreController::class, 'index'])->name('explore');

    // Pets
    Route::get('/pets/novo', [PetController::class, 'create'])->name('pets.create');
    Route::post('/pets', [PetController::class, 'store'])->name('pets.store');
    Route::get('/pet/{pet}', [PetController::class, 'show'])->name('pets.show');
    Route::get('/pet/{pet}/diario/{date}', [PetController::class, 'day'])->name('pets.day')->where('date', '\d{4}-\d{2}-\d{2}');
    Route::get('/pet/{pet}/editar', [PetController::class, 'edit'])->name('pets.edit');
    Route::put('/pet/{pet}', [PetController::class, 'update'])->name('pets.update');
    Route::delete('/pet/{pet}', [PetController::class, 'destroy'])->name('pets.destroy');
    Route::post('/pet/{pet}/usar', [PetController::class, 'activate'])->name('pets.activate');
    Route::post('/pet/{pet}/seguir', [PetController::class, 'follow'])->name('pets.follow');

    // Saúde
    Route::post('/pet/{pet}/saude', [HealthRecordController::class, 'store'])->name('health.store');
    Route::delete('/saude/{record}', [HealthRecordController::class, 'destroy'])->name('health.destroy');

    // Posts, patinhas e comentários
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/posts/{post}/patinha', [PostController::class, 'paw'])->name('posts.paw');
    Route::post('/posts/{post}/denunciar', [PostController::class, 'report'])->name('posts.report');
    Route::post('/posts/{post}/comentarios', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comentarios/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Stories
    Route::post('/stories', [StoryController::class, 'store'])->name('stories.store');
    Route::get('/stories/{pet}', [StoryController::class, 'show'])->name('stories.show');
    Route::delete('/stories/{story}', [StoryController::class, 'destroy'])->name('stories.destroy');
    Route::post('/stories/{story}/reagir', [StoryController::class, 'react'])->name('stories.react')->middleware('throttle:60,1');
    Route::post('/stories/{story}/recados', [StoryController::class, 'comment'])->name('stories.comments.store')->middleware('throttle:20,1');
    Route::delete('/recados/{comment}', [StoryController::class, 'destroyComment'])->name('stories.comments.destroy');

    // Agendamentos com veterinário
    Route::get('/agendamentos', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/pet/{pet}/agendar', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/pet/{pet}/agendar', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('/agendamentos/{appointment}/cancelar', [AppointmentController::class, 'cancel'])->name('appointments.cancel');

    // Notificações e conta
    Route::get('/notificacoes', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificacoes/pendentes', [NotificationController::class, 'pending'])->name('notifications.pending');
    Route::get('/conta', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/conta', [ProfileController::class, 'update'])->name('profile.update');

    // Painel do veterinário
    Route::middleware('role:vet')->prefix('clinica')->name('vet.')->group(function () {
        Route::get('/', [VetPanelController::class, 'index'])->name('index');
        Route::patch('/consultas/{appointment}', [VetPanelController::class, 'update'])->name('update');
    });

    // Painel administrativo
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/usuarios', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/{user}', [Admin\UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::patch('/usuarios/{user}/suspender', [Admin\UserController::class, 'toggleBan'])->name('users.ban');
        Route::delete('/usuarios/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/pets', [Admin\PetController::class, 'index'])->name('pets.index');
        Route::delete('/pets/{pet}', [Admin\PetController::class, 'destroy'])->name('pets.destroy');

        Route::get('/posts', [Admin\PostController::class, 'index'])->name('posts.index');
        Route::patch('/posts/{post}/ocultar', [Admin\PostController::class, 'toggleHidden'])->name('posts.hide');
        Route::delete('/posts/{post}', [Admin\PostController::class, 'destroy'])->name('posts.destroy');

        Route::get('/denuncias', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::patch('/denuncias/{report}', [Admin\ReportController::class, 'update'])->name('reports.update');

        Route::get('/consultas', [Admin\AppointmentController::class, 'index'])->name('appointments.index');
    });
});
