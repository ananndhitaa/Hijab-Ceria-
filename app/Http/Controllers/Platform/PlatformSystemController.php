<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformSystemController extends Controller
{
    /**
     * Display safe platform system monitoring metrics.
     */
    public function index(): View
    {
        $systemData = [
            'os'                   => PHP_OS,
            'php_version'          => PHP_VERSION,
            'laravel_version'      => app()->version(),
            'server_software'      => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI / Integrated Server',
            'database_connection'  => DB::connection()->getDriverName(),
            'database_version'     => $this->getDbVersion(),
            'database_name'        => DB::connection()->getDatabaseName(),
            'tenant_isolation'     => config('nayara.tenant_isolation_enabled', false),
            'app_env'              => config('app.env'),
            'app_debug'            => config('app.debug'),
            'app_timezone'         => config('app.timezone'),
            'cache_driver'         => config('cache.default'),
            'session_driver'       => config('session.driver'),
            'queue_connection'     => config('queue.default'),
            'total_tables'         => $this->getTotalTablesCount(),
            'migrations_count'     => DB::table('migrations')->count(),
        ];

        return view('platform.system.index', compact('systemData'));
    }

    protected function getDbVersion(): string
    {
        try {
            $row = DB::select('SELECT VERSION() as v');
            return $row[0]->v ?? 'Unknown';
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    protected function getTotalTablesCount(): int
    {
        try {
            if (DB::connection()->getDriverName() === 'sqlite') {
                return count(DB::select("SELECT name FROM sqlite_master WHERE type='table'"));
            }
            return count(DB::select('SHOW TABLES'));
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
