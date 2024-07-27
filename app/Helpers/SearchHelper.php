<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;

class SearchHelper
{
    /**
     * Store or update the search request data in the session.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public static function storeSearchRequest($request)
    {
        // Get the existing search data from the session
        $existingSearch = Session::get('previous_search', []);
        
        // Merge the existing data with the new request data
        $newSearchData = array_merge($existingSearch, $request->all());
        
        // Store the merged data back in the session
        Session::put('previous_search', $newSearchData);
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
