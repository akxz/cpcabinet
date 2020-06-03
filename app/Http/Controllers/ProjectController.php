<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProjectService;

class ProjectController extends Controller
{
    /**
     * Выводит список проектов
     *
     * @return string json
     */
    public function index()
    {
        $projects = (new ProjectService())->get();

        return $projects;
    }
}
