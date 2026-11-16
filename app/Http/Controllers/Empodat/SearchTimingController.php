<?php

declare(strict_types=1);

namespace App\Http\Controllers\Empodat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empodat\StoreSearchTimingRequest;
use App\Models\Backend\QueryLog;
use Carbon\Carbon;
use Illuminate\Http\Response;

class SearchTimingController extends Controller
{
    private const MAX_AGE_MINUTES = 15;

    /**
     * Store how long the user waited for the results page (write once).
     */
    public function __invoke(StoreSearchTimingRequest $request, QueryLog $queryLog): Response
    {
        $createdAt = $queryLog->getRawOriginal('created_at');

        if ($queryLog->database_key !== 'empodat'
            || $createdAt === null
            || Carbon::parse($createdAt)->lt(now()->subMinutes(self::MAX_AGE_MINUTES))) {
            return response()->noContent();
        }

        $content = json_decode((string) $queryLog->content, true);
        $content = is_array($content) ? $content : [];

        if (isset($content['dataTime'])) {
            return response()->noContent();
        }

        $content['dataTime'] = $this->format((int) $request->validated('data_ms'));
        $content['pageTime'] = $this->format((int) $request->validated('page_ms'));

        $queryLog->content = json_encode($content);
        $queryLog->save();

        return response()->noContent();
    }

    private function format(int $milliseconds): string
    {
        return number_format($milliseconds / 1000, 2, '.', '').' s';
    }
}
