<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Storage\S3DailyStatsService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the Telescope view.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('telescope::layout', [
            'cssFile' => Telescope::$useDarkTheme ? 'app-dark.css' : 'app.css',
            'telescopeScriptVariables' => Telescope::scriptVariables(),
        ]);
    }

    public function stats(Request $request)
    {
        $date = $request->input('date');
        return response()->json(app(S3DailyStatsService::class)->getStats($date));
    }
}
