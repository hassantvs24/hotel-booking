<?php

namespace App\Http\Controllers\API\Portal\Legal;

use App\Http\Controllers\BaseController;
use App\Models\LegalDocument;
use Illuminate\Http\JsonResponse;

/** The public text of a legal page (no login needed: it is linked from the footer and from the sign-up steps) */
class LegalController extends BaseController
{
    public function show(string $slug): JsonResponse
    {
        $doc = LegalDocument::where('slug', $slug)->first();

        if (!$doc) {
            return $this->sendError('This page does not exist.', [], 404);
        }

        return $this->sendSuccess([
            'slug'       => $doc->slug,
            'title'      => $doc->title,
            'body'       => $doc->body,
            'version'    => $doc->version,
            'updated_at' => $doc->updated_at,
        ]);
    }
}
