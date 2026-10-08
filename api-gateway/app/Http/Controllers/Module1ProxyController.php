<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Module1ProxyController extends Controller
{
    public function proxy(
        Request $request,
        ?string $path = null
    ) {
        $baseUrl = rtrim(
            (string) config('gateway.auth_service_url'),
            '/'
        );

        $targetUrl = $baseUrl.'/'.ltrim(
            $request->path(),
            '/'
        );

        /*
         * Giu query string neu request co.
         */
        if (! empty($request->query())) {
            $targetUrl .= '?'.http_build_query(
                $request->query()
            );
        }

        try {
            /*
             * =================================================
             * FILE UPLOAD
             * =================================================
             *
             * Khong proxy nguyen Content-Type multipart tu
             * browser vi boundary cu se khong con hop le.
             *
             * Laravel HTTP Client se tu tao multipart boundary.
             */
            if ($request->hasFile('avatar')) {
                $avatar = $request->file(
                    'avatar'
                );

                if (! $avatar->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' =>
                            'File anh tai len khong hop le.',
                    ], 422);
                }

                $realPath =
                    $avatar->getRealPath();

                if (
                    $realPath === false ||
                    ! is_file($realPath)
                ) {
                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Khong doc duoc file anh.',
                    ], 422);
                }

                $stream = fopen(
                    $realPath,
                    'r'
                );

                if ($stream === false) {
                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Khong mo duoc file anh.',
                    ], 422);
                }

                try {
                    $client = Http::acceptJson()
                        ->timeout(30);

                    /*
                     * Chuyen JWT sang Module 1.
                     */
                    $authorization =
                        $request->header(
                            'Authorization'
                        );

                    if ($authorization) {
                        $client =
                            $client->withHeaders([
                                'Authorization' =>
                                    $authorization,
                            ]);
                    }

                    /*
                     * attach() + post() de Laravel tao
                     * multipart/form-data dung chuan.
                     */
                    $upstreamResponse =
                        $client
                            ->attach(
                                'avatar',
                                $stream,
                                $avatar
                                    ->getClientOriginalName(),
                                [
                                    'Content-Type' =>
                                        $avatar
                                            ->getMimeType()
                                        ?: 'application/octet-stream',
                                ]
                            )
                            ->post(
                                $targetUrl
                            );
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                return response(
                    $upstreamResponse->body(),
                    $upstreamResponse->status()
                )->withHeaders([
                    'Content-Type' =>
                        $upstreamResponse->header(
                            'Content-Type',
                            'application/json'
                        ),
                ]);
            }

            /*
             * =================================================
             * REQUEST THONG THUONG
             * =================================================
             */
            $excludedHeaders = [
                'host',
                'content-length',
                'connection',
                'transfer-encoding',
            ];

            $headers = collect(
                $request->headers->all()
            )
                ->except($excludedHeaders)
                ->map(function (array $values) {
                    return implode(
                        ', ',
                        $values
                    );
                })
                ->all();

            $client = Http::withHeaders(
                $headers
            )->timeout(30);

            $method = strtoupper(
                $request->method()
            );

            $options = [];

            if (
                ! in_array(
                    $method,
                    [
                        'GET',
                        'HEAD',
                    ],
                    true
                )
            ) {
                $client =
                    $client->withBody(
                        $request->getContent(),
                        $request->header(
                            'Content-Type',
                            'application/json'
                        )
                    );
            }

            $upstreamResponse =
                $client->send(
                    $method,
                    $targetUrl,
                    $options
                );

            return response(
                $upstreamResponse->body(),
                $upstreamResponse->status()
            )->withHeaders([
                'Content-Type' =>
                    $upstreamResponse->header(
                        'Content-Type',
                        'application/json'
                    ),
            ]);
        } catch (
            ConnectionException $exception
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Auth Service khong phan hoi.',
            ], 503);
        }
    }
public function storage(string $path)
{
    $baseUrl = rtrim(
        (string) config('gateway.auth_service_url'),
        '/'
    );

    $targetUrl = $baseUrl
        .'/storage/'
        .ltrim($path, '/');

    try {
        $upstreamResponse = Http::timeout(30)
            ->get($targetUrl);
    } catch (ConnectionException $exception) {
        return response()->json([
            'success' => false,
            'message' => 'Auth Service khong phan hoi.',
        ], 503);
    }

    if (! $upstreamResponse->successful()) {
        return response(
            $upstreamResponse->body(),
            $upstreamResponse->status()
        );
    }

    return response(
        $upstreamResponse->body(),
        200
    )->withHeaders([
        'Content-Type' =>
            $upstreamResponse->header(
                'Content-Type',
                'application/octet-stream'
            ),

        'Cache-Control' =>
            'public, max-age=3600',
    ]);
}
}