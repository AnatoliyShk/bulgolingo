<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VitalsCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class VitalsController extends Controller
{
    /**
     * navigator.sendBeacon() posts the JSON body as text/plain, so Laravel
     * never parses it into the request bag — decode it by hand instead.
     */
    public function store(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        $validator = Validator::make($payload, [
            'name' => ['required', 'string', Rule::in(['CLS', 'INP', 'LCP', 'TTFB'])],
            'value' => ['required', 'numeric'],
            'route' => ['required', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'inputDelay' => ['nullable', 'numeric'],
            'processing' => ['nullable', 'numeric'],
            'presentation' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->noContent(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        VitalsCache::record($data['name'], [
            ...$data,
            'recorded_at' => now()->toIso8601String(),
        ]);

        return response()->noContent();
    }
}
