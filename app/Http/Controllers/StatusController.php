<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatusController extends Controller
{
      public function index(Request $request)
      {
            $id = $request->input('uniqueId', Cache::store('redis')->get('status'));

            if (empty($id))
                  return response()->json(['status' => 'inactive']);

            return response()->json([
                  'status' => Cache::store('redis')->get("status-$id"),
                  'message' => Cache::store('redis')->get("status-$id-message"),
                  'download' => Cache::store('redis')->get("status-$id-download"),
                  'percentage' => Cache::store('redis')->get("status-$id-percentage")
            ]);
      }
}
