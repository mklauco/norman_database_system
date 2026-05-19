<?php

namespace App\Http\Controllers\SLE;

use App\Http\Controllers\Controller;
use App\Models\SLE\SuspectListExchangeSource;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SuspectListExchangeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'role:admin|super_admin|sle',
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Load data from online source on each page refresh
        $this->loadDataFromOnlineSource();

        $sleSources = SuspectListExchangeSource::orderBy('order', 'asc')->where('show', 1)->get();

        return view('sle.database', [
            'sleSources' => $sleSources,
        ]);
    }

    /**
     * Display the main SLE page with online data (primary interface).
     */
    public function main()
    {
        $onlineData = $this->fetchOnlineData();

        return view('sle.index', [
            'onlineData' => $onlineData['table_data'],
            'onlineContent' => $onlineData['content'],
        ]);
    }

    /**
     * Load data from the online source and update the database
     */
    private function loadDataFromOnlineSource()
    {
        try {
            // Fetch HTML content from the online source
            $response = Http::get('https://www.norman-network.com/nds/SLE/index_body.php');

            if ($response->successful()) {
                $htmlContent = $response->body();

                // Parse HTML
                $dom = new DOMDocument;
                libxml_use_internal_errors(true);
                $dom->loadHTML($htmlContent);

                $xpath = new DOMXPath($dom);
                $rows = $xpath->query('//tr');

                $now = now();

                foreach ($rows as $row) {
                    $cells = $xpath->query('.//td', $row);

                    if ($cells->length >= 6) {
                        // Get code from first column - try anchor id first, then text content
                        $codeAnchor = $xpath->query('.//a[@id]', $cells->item(0))->item(0);
                        $code = $codeAnchor && method_exists($codeAnchor, 'getAttribute') ? $codeAnchor->getAttribute('id') : trim($cells->item(0)->textContent);

                        if ($code) {
                            // Get name from second column - try anchor id first, then text content
                            $nameAnchor = $xpath->query('.//a[@id]', $cells->item(1))->item(0);
                            $name = $nameAnchor && method_exists($nameAnchor, 'getAttribute') ? $nameAnchor->getAttribute('id') : trim($cells->item(1)->textContent);

                            // Get description from third column
                            $description = trim($cells->item(2)->textContent);

                            // Get innerHTML for link columns
                            $linkFullList = $this->getInnerHTML($cells->item(3));
                            $linkInchikeyList = $this->getInnerHTML($cells->item(4));
                            $linkReferences = $this->getInnerHTML($cells->item(5));

                            // Update or insert
                            DB::table('suspect_list_exchange_sources')->updateOrInsert(
                                ['code' => $code],
                                [
                                    'name' => $name,
                                    'description' => $description,
                                    'link_full_list' => $linkFullList,
                                    'link_inchikey_list' => $linkInchikeyList,
                                    'link_references' => $linkReferences,
                                    'updated_at' => $now,
                                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                                ]
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but don't break the page
            Log::error('Failed to load SLE data from online source: '.$e->getMessage());
        }
    }

    /**
     * Get inner HTML content from a DOM element
     */
    private function getInnerHTML($element): ?string
    {
        if (! $element) {
            return null;
        }

        $innerHTML = '';
        foreach ($element->childNodes as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return trim($innerHTML) ?: null;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sleSource = new SuspectListExchangeSource;
        $isCreate = true;

        return view('sle.upsert', compact('sleSource', 'isCreate'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'show' => 'boolean',
            'link_full_list' => 'nullable|string',
            'link_inchikey_list' => 'nullable|string',
            'link_references' => 'nullable|string',
        ]);

        $validated['added_by'] = Auth::id();
        $validated['show'] = $request->has('show') ? 1 : 0;

        SuspectListExchangeSource::create($validated);

        return redirect()->route('sle.index')->with('success', 'Suspect List Exchange Source created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sleSource = SuspectListExchangeSource::findOrFail($id);

        return view('sle.show', compact('sleSource'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sleSource = SuspectListExchangeSource::findOrFail($id);
        $isCreate = false;

        return view('sle.upsert', compact('sleSource', 'isCreate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sleSource = SuspectListExchangeSource::findOrFail($id);

        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'show' => 'boolean',
            'link_full_list' => 'nullable|string',
            'link_inchikey_list' => 'nullable|string',
            'link_references' => 'nullable|string',
        ]);

        $validated['show'] = $request->has('show') ? 1 : 0;

        $sleSource->update($validated);

        return redirect()->route('sle.index')->with('success', 'Suspect List Exchange Source updated successfully.');
    }

    /**
     * Remove the specified resource in storage.
     */
    public function destroy(string $id)
    {
        $sleSource = SuspectListExchangeSource::findOrFail($id);
        $sleSource->delete();

        return redirect()->route('sle.index')->with('success', 'Suspect List Exchange Source deleted successfully.');
    }

    /**
     * Manually refresh data from the online source
     */
    public function refresh()
    {
        try {
            $this->loadDataFromOnlineSource();

            return redirect()->route('sle.sources.index')->with('success', 'SLE data refreshed successfully from online source.');
        } catch (\Exception $e) {
            return redirect()->route('sle.sources.index')->with('error', 'Failed to refresh SLE data: '.$e->getMessage());
        }
    }

    /**
     * View data directly from the online source without storing in database
     */
    public function viewOnline()
    {
        try {
            $onlineData = $this->fetchOnlineData();

            return view('sle.view-online', [
                'onlineData' => $onlineData['table_data'],
                'onlineContent' => $onlineData['content'],
                'lastUpdated' => now(),
            ]);
        } catch (\Exception $e) {
            return redirect()->route('sle.sources.index')->with('error', 'Failed to load online data: '.$e->getMessage());
        }
    }

    /**
     * Fetch data from the online source and return as array (without storing)
     */
    private function fetchOnlineData(): array
    {
        $data = [
            'content' => [],
            'table_data' => [],
        ];

        try {
            // Fetch HTML content from the online source
            $response = Http::get('https://www.norman-network.com/nds/SLE/index_body.php');

            if ($response->successful()) {
                $htmlContent = $response->body();

                // Parse HTML
                $dom = new DOMDocument;
                libxml_use_internal_errors(true);
                $dom->loadHTML($htmlContent);

                $xpath = new DOMXPath($dom);

                // Extract paragraph content above the table (exclude table content)
                $allParagraphs = $xpath->query('//p');
                foreach ($allParagraphs as $paragraph) {
                    // Check if this paragraph is inside a table by traversing up the DOM tree
                    $currentNode = $paragraph;
                    $isInTable = false;

                    while ($currentNode && $currentNode->nodeName !== 'body' && $currentNode->nodeName !== 'html') {
                        if ($currentNode->nodeName === 'table') {
                            $isInTable = true;
                            break;
                        }
                        $currentNode = $currentNode->parentNode;
                    }

                    // Only add content if it's not inside a table
                    if (! $isInTable) {
                        $content = $this->getInnerHTML($paragraph);
                        if (! empty($content)) {
                            // Clean the content: remove &nbsp; and Unicode non-breaking spaces and check if it's actually meaningful
                            $cleanContent = str_replace('&nbsp;', '', $content);
                            $cleanContent = str_replace("\u{A0}", '', $cleanContent); // Remove Unicode non-breaking space
                            $cleanContent = trim($cleanContent);

                            // Only add content if it's not empty after cleaning
                            if (! empty($cleanContent)) {
                                // Add link-lime-text class to all links in the content
                                $content = preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $content);
                                $data['content'][] = $content;
                            }
                        }
                    }
                }

                // Extract table data
                $rows = $xpath->query('//tr');

                foreach ($rows as $row) {
                    $cells = $xpath->query('.//td', $row);

                    if ($cells->length >= 6) {
                        // Get code from first column - try anchor id first, then text content
                        $codeAnchor = $xpath->query('.//a[@id]', $cells->item(0))->item(0);
                        $code = $codeAnchor && method_exists($codeAnchor, 'getAttribute') ? $codeAnchor->getAttribute('id') : trim($cells->item(0)->textContent);

                        if ($code) {
                            // Get name from second column - try anchor id first, then text content
                            $nameAnchor = $xpath->query('.//a[@id]', $cells->item(1))->item(0);
                            $name = $nameAnchor && method_exists($nameAnchor, 'getAttribute') ? $nameAnchor->getAttribute('id') : trim($cells->item(1)->textContent);

                            // Get description from third column
                            $description = trim($cells->item(2)->textContent);

                            // Get innerHTML for link columns
                            $linkFullList = $this->getInnerHTML($cells->item(3));
                            $linkInchikeyList = $this->getInnerHTML($cells->item(4));
                            $linkReferences = $this->getInnerHTML($cells->item(5));

                            $data['table_data'][] = [
                                'code' => $code,
                                'name' => $name,
                                'description' => $description,
                                'link_full_list' => $linkFullList,
                                'link_inchikey_list' => $linkInchikeyList,
                                'link_references' => $linkReferences,
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch SLE data from online source: '.$e->getMessage());
            throw $e;
        }

        // Log content extraction for debugging
        Log::info('SLE content extraction: '.count($data['content']).' paragraphs, '.count($data['table_data']).' table rows');

        return $data;
    }
}
