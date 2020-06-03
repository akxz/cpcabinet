<?php

namespace App\Services;

use DB;

class ProjectService
{
    /**
     * Список проектов
     *
     * @return string json
     */
    public function get()
    {
        $allowed = config('projects.allowed');

        $projects = DB::connection('cpdb')
            ->table('callcenter_projects')
            ->select('id', 'name')
            ->whereIn('id', $allowed)
            ->get()
            ->toArray();

        return json_encode(['data' => $projects]);
    }
}
