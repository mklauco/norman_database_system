<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DatabaseEntity;
use App\Models\Backend\Template;
use App\Models\Backend\File;
use App\Models\Backend\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardMainController extends Controller
{
    /**
    * Display the dashboard index.
    *
    * @return \Illuminate\View\View
    */
    public function index()
    {
        $user = User::with('tokens')->find(Auth::id());
        
        // Get database entities for dashboard display with query log counts
        $databaseEntities = DatabaseEntity::where('show_in_dashboard', true)
        ->where('dashboard_route_name', 'not like', '%https%')
        ->select('database_entities.*', DB::raw('COUNT(query_logs.id) as query_log_count'))
        ->leftJoin('query_logs', 'database_entities.code', '=', 'query_logs.database_key')
        ->groupBy('database_entities.id')  // Add all columns from database_entities needed for groupBy
        ->orderBy('name')
        ->get();
        
        
        // Get database entities with active templates
        $entitiesWithTemplates = DatabaseEntity::where('show_in_dashboard', true)
        ->where('dashboard_route_name', 'not like', '%https%')
        ->whereHas('templates', function($query) {
            $query->where('is_active', true);
        })
        ->orderBy('name')
        ->take(6)
        ->get();
        
        // System statistics
        $statistics = [
            'total_templates' => Template::count(),
            'total_files' => File::count(),
            'total_projects' => Project::count(),
            'user_files' => File::where('uploaded_by', Auth::id())->count(),
            'recent_activity' => $this->getRecentActivity(),
        ];
        
        // Quick access links
        $quickAccessLinks = [
            [
                'name' => 'Templates',
                'route' => 'templates.index',
                'icon' => 'fas fa-file-alt',
                'color' => 'blue'
            ],
            [
                'name' => 'Files',
                'route' => 'files.index',
                'icon' => 'fas fa-upload',
                'color' => 'green'
            ],
            [
                'name' => 'Projects',
                'route' => 'projects.index',
                'icon' => 'fas fa-project-diagram',
                'color' => 'purple'
            ],
            [
                'name' => 'Substances',
                'route' => 'substances.filter',
                'icon' => 'fas fa-flask',
                'color' => 'red'
                ]
            ];
            
            // Admin process groups
            $adminProcessGroups = [
                [
                    'name' => 'Empodat',
                    'processes' => [
                        [
                            'name' => 'Generate Countries',
                            'route' => 'cod.unique.search.countries',
                            'method' => 'POST'
                        ],
                        [
                            'name' => 'Generate Ecosystems',
                            'route' => 'cod.unique.search.matrices',
                            'method' => 'POST'
                        ],
                        [
                            'name' => 'Update DB Counts',
                            'route' => 'update.dbentities.counts',
                            'method' => 'POST'
                            ]
                            ]
                        ],
                        [
                            'name' => 'Database Counts',
                            'processes' => [
                                [
                                    'name' => 'SLE',
                                    'route' => 'slehome.countAll',
                                    'method' => 'GET'
                                ],
                                [
                                    'name' => 'Bioassay',
                                    'route' => 'bioassay.countAll',
                                    'method' => 'GET'
                                ],
                                [
                                    'name' => 'Indoor',
                                    'route' => 'indoor.countAll',
                                    'method' => 'GET'
                                ],
                                [
                                    'name' => 'Passive',
                                    'route' => 'passive.countAll',
                                    'method' => 'GET'
                                ],
                                [
                                    'name' => 'Prioritisation',
                                    'route' => 'prioritisation.countAll',
                                    'method' => 'GET'
                                ],
                                [
                                    'name' => 'ARBG',
                                    'route' => 'arbg.countAll',
                                    'method' => 'GET'
                                ],
                                [
                                    'name' => 'Lowest PNEC',
                                    'route' => 'ecotox.lowestpnec.countAll',
                                    'method' => 'GET'
                                ],
                                [
                                    'name' => 'Ecotox ',
                                    'route' => 'ecotox.ecotox.countAll',
                                    'method' => 'GET'
                                ],
                                [
                                    'name' => 'Entire Ecotox DB',
                                    'route' => 'ecotox.countAll',
                                    'method' => 'GET'
                                ],
                                ]
                                ]
                            ];
                            
                            return view('dashboard.index', [
                                'user' => $user,
                                'databaseEntities' => $databaseEntities,
                                'entitiesWithTemplates' => $entitiesWithTemplates,
                                'statistics' => $statistics,
                                'quickAccessLinks' => $quickAccessLinks,
                                'adminProcessGroups' => $adminProcessGroups,
                                'currentDate' => now()
                            ]);
                        }
                        
                        /**
                        * Get recent activity for the dashboard.
                        *
                        * @return array
                        */
                        private function getRecentActivity()
                        {
                            // Recent uploads - files uploaded in the last 30 days
                            $recentUploads = File::with('uploader')
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get()
                            ->map(function($file) {
                                return [
                                    'type' => 'file_upload',
                                    'title' => $file->name ?? $file->original_name,
                                    'user' => $file->uploader->name ?? 'Unknown',
                                    'date' => $file->created_at,
                                    'url' => route('files.show', $file->id)
                                ];
                            });
                            
                            // Recent templates - templates created in the last 30 days
                            $recentTemplates = Template::with(['creator', 'databaseEntity'])
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get()
                            ->map(function($template) {
                                return [
                                    'type' => 'template_create',
                                    'title' => $template->name,
                                    'entity' => $template->databaseEntity->name ?? 'Unknown',
                                    'user' => $template->creator->name ?? 'Unknown',
                                    'date' => $template->created_at,
                                    'url' => route('templates.show', $template->id)
                                ];
                            });
                            
                            // Combine all activities
                            $allActivities = $recentUploads->toArray();
                            foreach ($recentTemplates->toArray() as $template) {
                                $allActivities[] = $template;
                            }
                            
                            // Sort the combined array by date
                            usort($allActivities, function($a, $b) {
                                return strtotime($b['date']) - strtotime($a['date']);
                            });
                            
                            // Take only the 5 most recent activities
                            return array_slice($allActivities, 0, 5);
                        }
                    }