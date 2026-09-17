<?php

use Illuminate\Support\Facades\Route;
use Modules\Roadmap\Http\Controllers\RoadmapCommentController;
use Modules\Roadmap\Http\Controllers\RoadmapController;
use Modules\Roadmap\Http\Controllers\RoadmapVoteController;

Route::middleware('web')->group(function (): void {
    Route::get('/roadmap', [RoadmapController::class, 'index'])->name('roadmap.index');
    Route::get('/roadmap/{item:slug}', [RoadmapController::class, 'show'])->name('roadmap.show');

    Route::middleware('auth')->group(function (): void {
        Route::post('/roadmap', [RoadmapController::class, 'store'])->middleware('throttle:roadmap-suggest')->name('roadmap.store');
        Route::post('/roadmap/{item}/vote', [RoadmapVoteController::class, 'store'])->middleware('throttle:roadmap-vote')->name('roadmap.vote');
        Route::post('/roadmap/{item:slug}', [RoadmapCommentController::class, 'store'])->middleware('throttle:roadmap-comment')->name('roadmap.comments.store');
    });
});
