<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnswerService;

class AnswerController extends Controller
{
    /**
     * Показывает страницу для выборки фраз
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('answer/index', []);
    }

    /**
     * Выводит фразы по фильтру
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string json
     */
    public function list(Request $request)
    {
        $answers = (new AnswerService($request))->get();

        return $answers;
    }

    /**
     * Сохраняет размеченную фразу
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string json
     */
    public function store(Request $request)
    {
        $answers = (new AnswerService($request))->set();

        return $answers;
    }
}
