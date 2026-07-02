<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'cek.peran' => \App\Http\Middleware\CekPeran::class,
            'cek.peserta' => \App\Http\Middleware\CekPeserta::class,
            'cek.sesi.tes' => \App\Http\Middleware\CekSesiTes::class,
            'cek.akses.menu' => \App\Http\Middleware\CekAksesMenu::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function ($response, \Throwable $e, $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi kedaluwarsa. Silakan muat ulang halaman dan coba lagi.',
                ], 419);
            }

            $message = 'Sesi kedaluwarsa. Silakan coba login kembali.';

            if ($request->is('peserta/*')) {
                return redirect()->route('peserta.login')->with('error', $message);
            }

            if ($request->is('login/token') || $request->is('ujian/*')) {
                return redirect()->route('login.token')->with('error', $message);
            }

            if ($request->is('login') || $request->is('admin/*')) {
                return redirect()->route('login')->with('error', $message);
            }

            return redirect()->back()->with('error', $message);
        });

        $exceptions->renderable(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            return back()
                ->withInput()
                ->with('error', 'Ukuran data terlalu besar! Pastikan setiap file yang diupload tidak melebihi 2MB. Total upload maksimal ' . ini_get('post_max_size') . '.');
        });
    })->create();
