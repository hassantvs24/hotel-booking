<?php

namespace App\Http\Controllers\API\Admin\Notification;

use App\Http\Controllers\BaseController;
use App\Services\Notify\NotificationCategories;
use App\Services\Notify\NotificationPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** A person's own choices for how each kind of notification reaches them. */
class NotificationPreferenceController extends BaseController
{
    public function __construct(private readonly NotificationPreferences $prefs) {}

    public function show(Request $request): JsonResponse
    {
        return $this->sendSuccess($this->payload($this->prefs->for($request->user())));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['preferences' => ['required', 'array']]);

        return $this->sendSuccess($this->payload($this->prefs->save($request->user(), $data['preferences'])), 'Saved.');
    }

    private function payload(array $prefs): array
    {
        $rows = [];

        foreach (NotificationCategories::ALL as $key => $look) {
            $rows[] = ['category' => $key, 'label' => $look['label'], 'hint' => $look['hint']] + $prefs[$key];
        }

        return ['preferences' => $rows];
    }
}
