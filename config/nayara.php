<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Isolation Enforcement Flag
    |--------------------------------------------------------------------------
    |
    | Controls whether tenant-scoped filtering and authorization are enforced
    | on ERP endpoints. Must remain FALSE by default on legacy/unmigrated
    | databases and only enabled per deployment or in isolated tests.
    |
    */
    'tenant_isolation_enabled' => (bool) env('NAYARA_TENANT_ISOLATION_ENABLED', false),
];
