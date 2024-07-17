<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;

class SearchHelper
{
    /**
     * Store the search request data in the session.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public static function storeSearchRequest($request)
    {
        // Store all the request data in the session under 'previous_search'
        Session::put('previous_search', $request->all());
    }

    /**
     * Retrieve the previous search request data from the session.
     *
     * @return array
     */
    public static function getPreviousSearchRequest()
    {
        // Get the previous search data from the session, or an empty array if it doesn't exist
        return Session::get('previous_search', []);
    }
}
