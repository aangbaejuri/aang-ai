<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/', [ChatbotController::class, 'show'])->name('chat.show');
Route::post('/', [ChatbotController::class, 'sendMessage'])->name('chat.send');

Route::get('/chat/new', [ChatbotController::class, 'newChat'])->name('chat.new');
Route::get('/chat/switch/{id}', [ChatbotController::class, 'switchChat'])->name('chat.switch');
Route::get('/chat/delete/{id}', [ChatbotController::class, 'deleteChat'])->name('chat.delete');
Route::get('/chat/clear', [ChatbotController::class, 'clearAllChats'])->name('chat.clear');
